<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

/**
 * Status buttons for an order: one action per target status, shown only
 * when the move is allowed from the current status for the logged-in role.
 */
class OrderActions
{
    /** @return list<Action> */
    public static function transitions(): array
    {
        return array_map(fn (OrderStatus $to) => self::transition($to), [
            OrderStatus::New,
            OrderStatus::Confirmed,
            OrderStatus::Picking,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ]);
    }

    public static function transition(OrderStatus $to): Action
    {
        $action = Action::make('to_'.$to->value)
            ->label($to->actionLabel())
            ->icon($to->icon())
            ->color($to === OrderStatus::Cancelled ? 'danger' : $to->color())
            ->visible(fn (Order $record): bool => in_array($to, app(OrderWorkflow::class)->availableFor($record, auth()->user()), true))
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record) => "{$to->actionLabel()}: {$record->number()}")
            ->modalDescription(fn (Order $record) => self::explain($record, $to))
            ->action(function (Order $record, array $data, Action $action, $livewire) use ($to) {
                try {
                    $order = app(OrderWorkflow::class)->transition($record, $to, auth()->user(), 'admin', $data['note'] ?? null);
                } catch (ValidationException $e) {
                    Notification::make()->title('Not possible')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                    $action->halt();

                    return;
                }

                Notification::make()
                    ->title("{$order->number()} is now {$order->status->label()}")
                    ->body($to === OrderStatus::Shipped ? "Invoice {$order->invoice_number} issued." : null)
                    ->success()
                    ->send();

                // On the order page, reload so the details, lines and buttons all show the new status.
                if ($livewire instanceof ViewRecord) {
                    $livewire->redirect(OrderResource::getUrl('view', ['record' => $order]), navigate: true);
                }
            });

        if ($to === OrderStatus::Cancelled) {
            $action->schema([
                Textarea::make('note')->label('Reason')->required()->maxLength(500)->rows(3),
            ]);
        }

        return $action;
    }

    private static function explain(Order $order, OrderStatus $to): string
    {
        return match ($to) {
            OrderStatus::New => 'The order goes to the queue for confirmation.',
            OrderStatus::Confirmed => 'Stock is taken for every line now. If any product is short, nothing changes. The customer gets an email.',
            OrderStatus::Picking => 'The warehouse starts putting the order together.',
            OrderStatus::Shipped => 'The order leaves the warehouse. An invoice number is issued and the customer gets an email.',
            OrderStatus::Delivered => 'The customer has received the goods.',
            OrderStatus::Cancelled => $order->status->holdsStock()
                ? 'The order is cancelled and its stock goes back on the shelf. The customer gets an email.'
                : 'The order is cancelled. No stock was taken for it yet.',
            default => '',
        };
    }
}

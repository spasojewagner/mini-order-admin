<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Order $order */
        $order = $this->getRecord();

        return "{$order->number()} · ".($order->customer?->displayName() ?? 'Order');
    }

    protected function getHeaderActions(): array
    {
        return [
            ...OrderActions::transitions(),
            ActionGroup::make([
                EditAction::make()->label('Edit customer / note')
                    ->visible(fn (Order $record) => $record->isEditable() && auth()->user()->can('update', $record)),
                Action::make('invoice')->label('Open invoice')->icon('heroicon-o-document-text')
                    ->visible(fn (Order $record) => (bool) $record->invoice_number)
                    ->url(fn (Order $record) => route('invoices.show', $record), shouldOpenInNewTab: true),
            ])->icon('heroicon-m-ellipsis-horizontal')->color('gray'),
        ];
    }
}

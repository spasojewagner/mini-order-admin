<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderWorkflow;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/** Kanban view of the work in progress: move an order one column to the right with one click. */
class OrderBoard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|UnitEnum|null $navigationGroup = 'Warehouse';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Order board';

    protected static ?string $title = 'Order board';

    protected string $view = 'filament.pages.order-board';

    public function getSubheading(): ?string
    {
        return 'New orders on the left, shipped in the last 3 days on the right. Buttons show only the steps your role may take.';
    }

    /** @return array<string, array{status: OrderStatus, orders: Collection}> */
    public function getColumns(): array
    {
        $columns = [OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Picking, OrderStatus::Shipped];

        return collect($columns)->mapWithKeys(fn (OrderStatus $status) => [$status->value => [
            'status' => $status,
            'orders' => Order::query()
                ->status($status)
                ->when($status === OrderStatus::Shipped, fn ($q) => $q->where('shipped_at', '>=', now()->subDays(3)))
                ->with('customer')
                ->withCount('items')
                ->oldest()
                ->limit(40)
                ->get(),
        ]])->all();
    }

    /** @return list<OrderStatus> forward steps only; cancelling is done on the order page with a reason */
    public function nextSteps(Order $order): array
    {
        return array_values(array_filter(
            app(OrderWorkflow::class)->availableFor($order, auth()->user()),
            fn (OrderStatus $s) => $s !== OrderStatus::Cancelled,
        ));
    }

    public function move(int $orderId, string $to): void
    {
        $order = Order::findOrFail($orderId);
        $target = OrderStatus::from($to);
        $workflow = app(OrderWorkflow::class);

        if (! $workflow->userMay(auth()->user(), $order->status, $target)) {
            Notification::make()->title('Your role cannot take this step')->danger()->send();

            return;
        }

        try {
            $order = $workflow->transition($order, $target, auth()->user(), 'admin');
        } catch (ValidationException $e) {
            Notification::make()->title('Not possible')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();

            return;
        }

        Notification::make()->title("{$order->number()} → {$order->status->label()}")->success()->send();
    }

    public function orderUrl(Order $order): string
    {
        return OrderResource::getUrl('view', ['record' => $order]);
    }
}

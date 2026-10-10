<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\SalesReport;
use App\Support\Settings;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New order'),
        ];
    }

    public function getTabs(): array
    {
        $count = fn (OrderStatus ...$s) => Order::query()->status(...$s)->count();
        $hours = Settings::int('orders.waiting_hours');
        $overdue = app(SalesReport::class)->waitingTooLong($hours)->count();

        return [
            'all' => Tab::make('All'),
            'to_confirm' => Tab::make('To confirm')
                ->icon('heroicon-m-inbox-arrow-down')
                ->badge($count(OrderStatus::New) ?: null)->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->status(OrderStatus::New)),
            'warehouse' => Tab::make('For the warehouse')
                ->icon('heroicon-m-archive-box')
                ->badge($count(OrderStatus::Confirmed, OrderStatus::Picking) ?: null)->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->status(OrderStatus::Confirmed, OrderStatus::Picking)),
            'overdue' => Tab::make("Over {$hours} h")
                ->icon('heroicon-m-clock')
                ->badge($overdue ?: null)->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('id', app(SalesReport::class)->waitingTooLong($hours)->select('id'))),
            'shipped' => Tab::make('Shipped')
                ->modifyQueryUsing(fn (Builder $query) => $query->status(OrderStatus::Shipped)),
            'done' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query) => $query->status(OrderStatus::Delivered)),
            'drafts' => Tab::make('Drafts')
                ->modifyQueryUsing(fn (Builder $query) => $query->status(OrderStatus::Draft)),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->status(OrderStatus::Cancelled)),
        ];
    }
}

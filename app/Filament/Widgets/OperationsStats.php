<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Pages\OrderBoard;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Order;
use App\Models\Product;
use App\Support\Money;
use App\Support\SalesReport;
use App\Support\Settings;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** What needs doing today: orders to confirm, to pick, overdue, and stock to reorder. */
class OperationsStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Today';

    protected ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $hours = Settings::int('orders.waiting_hours');
        $overdue = app(SalesReport::class)->waitingTooLong($hours)->count();
        $toConfirm = Order::query()->status(OrderStatus::New)->count();
        $toPick = Order::query()->status(OrderStatus::Confirmed, OrderStatus::Picking)->count();
        $lowStock = Product::query()->active()->lowStock()->count();

        return [
            Stat::make('Waiting for confirmation', $toConfirm)
                ->description('new orders from the portal and sales')
                ->icon('heroicon-o-inbox-arrow-down')
                ->color($toConfirm > 0 ? 'info' : 'gray')
                ->url(OrderResource::getUrl('index', ['tab' => 'to_confirm'])),
            Stat::make('For the warehouse', $toPick)
                ->description('confirmed or being picked')
                ->icon('heroicon-o-archive-box')
                ->color($toPick > 0 ? 'warning' : 'gray')
                ->url(OrderBoard::getUrl()),
            Stat::make("Waiting over {$hours} h", $overdue)
                ->description($overdue ? 'open orders stuck in one status' : 'nothing is overdue')
                ->icon('heroicon-o-clock')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->url(OrderResource::getUrl('index', ['tab' => 'overdue'])),
            Stat::make('Products to reorder', $lowStock)
                ->description('at or below reorder level · stock value '.Money::short(app(SalesReport::class)->stockValue()))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'success')
                ->url(ProductResource::getUrl('index', ['tab' => 'low_stock'])),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Kupci', Customer::count())
                ->description('Ukupno kupaca')
                ->color('info'),

            Stat::make('Proizvodi', Product::count())
                ->description('Ukupno proizvoda')
                ->color('gray'),

            Stat::make('Porudžbine', Order::count())
                ->description('Ukupno porudžbina')
                ->color('warning'),

            Stat::make('Vrednost potvrđenih', number_format(Order::where('status', 'confirmed')->sum('total_amount'), 2) . ' RSD')
                ->description('Confirmed porudžbine')
                ->color('success'),
        ];
    }
}
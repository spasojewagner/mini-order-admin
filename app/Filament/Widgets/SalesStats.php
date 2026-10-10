<?php

namespace App\Filament\Widgets;

use App\Support\Money;
use App\Support\Period;
use App\Support\SalesReport;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Revenue, order count, average order and time to ship for the chosen period. */
class SalesStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isSales();
    }

    protected function getHeading(): ?string
    {
        return 'Sales · '.$this->period()->label();
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $report = app(SalesReport::class);
        $p = $this->period();

        $revenue = $report->revenue($p->from, $p->to);
        $previous = $report->revenue($p->previousFrom, $p->previousTo);
        $orders = $report->orderCount($p->from, $p->to);
        $previousOrders = $report->orderCount($p->previousFrom, $p->previousTo);
        $daysToShip = $report->averageDaysToShip($p->from, $p->to);

        $daily = array_values($report->dailyRevenue($p->days() > 92 ? $p->to->copy()->subDays(89)->startOfDay() : $p->from, $p->to));

        return [
            $this->compare(Stat::make('Revenue (excl. VAT)', Money::short($revenue)), $revenue, $previous)
                ->chart($daily)
                ->icon('heroicon-o-banknotes'),
            $this->compare(Stat::make('Orders confirmed', number_format($orders, 0, ',', '.')), $orders, $previousOrders)
                ->icon('heroicon-o-shopping-bag'),
            Stat::make('Average order', Money::short($orders ? $revenue / $orders : 0))
                ->description('per confirmed order')
                ->icon('heroicon-o-calculator'),
            Stat::make('Order → shipped', $daysToShip === null ? '—' : number_format($daysToShip, 1, ',', '.').' days')
                ->description('average for orders shipped in the period')
                ->color($daysToShip !== null && $daysToShip > 3 ? 'warning' : 'success')
                ->icon('heroicon-o-truck'),
        ];
    }

    private function compare(Stat $stat, float|int $current, float|int $previous): Stat
    {
        if ($previous == 0) {
            return $stat->description('no data for the previous period')->color('gray');
        }

        $change = ($current - $previous) / $previous * 100;
        $up = $change >= 0;

        return $stat
            ->description(($up ? '+' : '').number_format($change, 1, ',', '.').'% vs previous period')
            ->descriptionIcon($up ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($up ? 'success' : 'danger');
    }

    private function period(): Period
    {
        return Period::fromKey($this->pageFilters['period'] ?? null);
    }
}

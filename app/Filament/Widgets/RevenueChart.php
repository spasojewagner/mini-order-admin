<?php

namespace App\Filament\Widgets;

use App\Support\Period;
use App\Support\SalesReport;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isSales();
    }

    public function getHeading(): string
    {
        return 'Revenue per '.($this->monthly() ? 'month' : 'day').' (RSD, excl. VAT)';
    }

    protected function getData(): array
    {
        $report = app(SalesReport::class);
        $p = Period::fromKey($this->pageFilters['period'] ?? null);

        if ($this->monthly()) {
            $series = $report->monthlyRevenue(12);
            $labels = array_keys($series);
        } else {
            $series = $report->dailyRevenue($p->from, $p->to);
            $labels = array_map(fn ($d) => Carbon::parse($d)->format('d.m.'), array_keys($series));
        }

        return [
            'datasets' => [[
                'label' => 'Revenue',
                'data' => array_values($series),
                'fill' => 'start',
                'tension' => 0.3,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return $this->monthly() ? 'bar' : 'line';
    }

    private function monthly(): bool
    {
        return in_array($this->pageFilters['period'] ?? null, ['90d', 'year'], true);
    }
}

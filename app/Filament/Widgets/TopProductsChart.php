<?php

namespace App\Filament\Widgets;

use App\Support\Period;
use App\Support\SalesReport;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Str;

class TopProductsChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Best sellers (revenue)';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isSales();
    }

    protected function getData(): array
    {
        $p = Period::fromKey($this->pageFilters['period'] ?? null);
        $top = app(SalesReport::class)->topProducts($p->from, $p->to, 8);

        return [
            'datasets' => [[
                'label' => 'Revenue (RSD)',
                'data' => array_column($top, 'revenue'),
                'backgroundColor' => '#6366f1',
                'borderRadius' => 4,
            ]],
            'labels' => array_map(fn ($row) => Str::limit($row['product'], 28), $top),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}

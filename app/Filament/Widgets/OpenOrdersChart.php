<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OpenOrdersChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Open orders by status';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $statuses = [OrderStatus::Draft, OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Picking];
        $counts = Order::query()
            ->whereIn('status', array_map(fn ($s) => $s->value, $statuses))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'datasets' => [[
                'label' => 'Orders',
                'data' => array_map(fn (OrderStatus $s) => (int) ($counts[$s->value] ?? 0), $statuses),
                'backgroundColor' => ['#94a3b8', '#38bdf8', '#6366f1', '#f59e0b'],
                'borderWidth' => 0,
            ]],
            'labels' => array_map(fn (OrderStatus $s) => $s->label(), $statuses),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
            'cutout' => '65%',
        ];
    }
}

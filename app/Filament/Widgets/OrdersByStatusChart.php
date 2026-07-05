<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersByStatusChart extends ChartWidget
{
    protected ?string $heading = 'Porudžbine po statusu';

    protected function getData(): array
    {
        $statuses = ['draft', 'new', 'confirmed', 'in_progress', 'shipped', 'cancelled'];

        // Broj porudžbina po statusu — grupisano u bazi
        $counts = Order::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $data = [];
        foreach ($statuses as $status) {
            $data[] = $counts[$status] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Broj porudžbina',
                    'data' => $data,
                ],
            ],
            'labels' => $statuses,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
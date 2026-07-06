<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Poslednjih 5 porudžbina')
            ->query(
                Order::query()->latest()->limit(5)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('id')
                    ->label('#'),

                TextColumn::make('customer.name')
                    ->label('Kupac'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('total_amount')
                    ->label('Ukupno')
                    ->money('RSD'),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y'),
            ]);
    }
}
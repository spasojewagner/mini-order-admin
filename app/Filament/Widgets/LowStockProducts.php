<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockProducts extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isWarehouse();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Reorder soon')
            ->query(Product::query()->active()->lowStock())
            ->defaultSort('stock_quantity')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Stock is healthy')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->columns([
                TextColumn::make('name')->description(fn (Product $record) => $record->sku),
                TextColumn::make('category')->color('gray'),
                TextColumn::make('stock_quantity')->label('In stock')->badge()
                    ->color(fn (Product $record) => $record->stock_quantity === 0 ? 'danger' : 'warning'),
                TextColumn::make('reorder_level')->label('Reorder at'),
            ])
            ->recordActions([
                Action::make('open')->label('Receive goods')->icon('heroicon-m-plus-circle')
                    ->url(fn (Product $record) => ProductResource::getUrl('view', ['record' => $record])),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\StockActions;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium')
                    ->description(fn (Product $record) => $record->sku),
                TextColumn::make('sku')->label('SKU')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category')->sortable()->badge()->color('gray'),
                TextColumn::make('price')->label('Price')->money('RSD')->sortable()->alignEnd(),
                TextColumn::make('stock_quantity')->label('In stock')->sortable()->alignEnd()->badge()
                    ->color(fn (Product $record) => $record->stock_quantity === 0 ? 'danger' : ($record->isLowStock() ? 'warning' : 'success')),
                TextColumn::make('reorder_level')->label('Reorder at')->alignEnd()->color('gray')->toggleable(),
                IconColumn::make('is_active')->label('For sale')->boolean()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn () => Product::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->all()),
                TernaryFilter::make('is_active')->label('For sale'),
            ])
            ->recordActions([
                StockActions::receive()->iconButton()->tooltip('Receive goods'),
                ActionGroup::make([
                    ViewAction::make()->label('Stock history'),
                    StockActions::correct(),
                    EditAction::make(),
                ]),
            ]);
    }
}

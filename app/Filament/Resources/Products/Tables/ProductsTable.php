<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('price')
                    ->label('Cena')
                    ->money('RSD')
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Lager')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktivan')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Svi')
                    ->trueLabel('Aktivni')
                    ->falseLabel('Neaktivni'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
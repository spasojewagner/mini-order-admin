<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Enums\StockMovementType;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** The product's stock history. Read-only: it is written by StockService. */
class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Stock history';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'order']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('type')->badge()
                    ->formatStateUsing(fn (StockMovementType $state) => $state->label())
                    ->color(fn (StockMovementType $state) => $state->color()),
                TextColumn::make('quantity')->label('Change')->alignEnd()->weight('semibold')
                    ->formatStateUsing(fn (int $state) => sprintf('%+d', $state))
                    ->color(fn (int $state) => $state < 0 ? 'danger' : 'success'),
                TextColumn::make('balance_after')->label('Balance')->alignEnd(),
                TextColumn::make('order_id')->label('Order')->placeholder('—')
                    ->formatStateUsing(fn (StockMovement $record) => $record->order?->number())
                    ->url(fn (StockMovement $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                TextColumn::make('user.name')->label('By')->placeholder('System'),
                TextColumn::make('note')->placeholder('—')->wrap(),
            ])
            ->filters([
                SelectFilter::make('type')->options(StockMovementType::options()),
            ]);
    }
}

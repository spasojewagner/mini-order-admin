<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\OrderStatus;
use App\Models\Customer;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount('orders')
                ->withSum(['orders as revenue' => fn (Builder $q) => $q
                    ->whereIn('status', OrderStatus::revenueValues())
                    ->where('confirmed_at', '>=', now()->subYear())], 'total_amount')
                ->withMax('orders as last_order_at', 'created_at'))
            ->defaultSort('revenue', 'desc')
            ->columns([
                TextColumn::make('company_name')->label('Customer')
                    ->formatStateUsing(fn (Customer $record) => $record->displayName())
                    ->default('—')
                    ->description(fn (Customer $record) => $record->company_name ? $record->name : 'Individual')
                    ->weight('medium')
                    ->searchable(['company_name', 'name'])
                    ->sortable(),
                TextColumn::make('tax_id')->label('PIB')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('email')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('phone')->searchable()->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orders_count')->label('Orders')->sortable()->alignEnd(),
                TextColumn::make('revenue')->label('Revenue 12 m')->money('RSD')->sortable()->alignEnd()->placeholder('—'),
                TextColumn::make('last_order_at')->label('Last order')->since()->sortable()->placeholder('never')
                    ->dateTimeTooltip('d.m.Y H:i'),
            ])
            ->filters([
                SelectFilter::make('type')->options(['company' => 'Company', 'individual' => 'Individual']),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ]);
    }
}

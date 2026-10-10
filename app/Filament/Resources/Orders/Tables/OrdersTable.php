<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer')->withCount('items'))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')->label('Order')
                    ->formatStateUsing(fn (Order $record) => $record->number())
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.company_name')->label('Customer')
                    ->formatStateUsing(fn (Order $record) => $record->customer?->displayName())
                    ->description(fn (Order $record) => $record->customer?->company_name ? $record->customer->name : null)
                    ->searchable(['company_name', 'name', 'tax_id']),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => $state->color())
                    ->icon(fn (OrderStatus $state) => $state->icon())
                    ->sortable(),
                TextColumn::make('items_count')->label('Lines')->alignCenter()->color('gray'),
                TextColumn::make('total_amount')->label('Total (net)')->money('RSD')->sortable()->alignEnd(),
                TextColumn::make('source')->label('Channel')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'portal' => 'Portal',
                        'mcp' => 'AI (MCP)',
                        default => 'Office',
                    })
                    ->toggleable(),
                TextColumn::make('created_at')->label('Placed')->dateTime('d.m.Y H:i')->sortable()
                    ->description(fn (Order $record) => $record->created_at->diffForHumans()),
                TextColumn::make('invoice_number')->label('Invoice')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::options())->multiple(),
                SelectFilter::make('source')->label('Channel')
                    ->options(['admin' => 'Back office', 'portal' => 'Customer portal', 'mcp' => 'AI client (MCP)']),
                SelectFilter::make('customer')->relationship('customer', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Customer $record) => $record->displayName())
                    ->searchable()->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Placed from'),
                        DatePicker::make('until')->label('Placed until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    ...OrderActions::transitions(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete drafts and cancelled')
                        ->visible(fn () => auth()->user()?->isAdmin())
                        ->using(fn ($records) => $records
                            ->filter(fn (Order $o) => in_array($o->status, [OrderStatus::Draft, OrderStatus::Cancelled], true))
                            ->each->delete()),
                ]),
            ]);
    }
}

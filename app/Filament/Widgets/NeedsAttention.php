<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\SalesReport;
use App\Support\Settings;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Open orders that have sat in one status longer than the waiting limit, oldest first. */
class NeedsAttention extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        $hours = Settings::int('orders.waiting_hours');

        return $table
            ->heading("Needs attention · open longer than {$hours} h")
            ->query(app(SalesReport::class)->waitingTooLong($hours)->with('customer')->withCount('items'))
            ->defaultSort('created_at')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nothing is waiting too long')
            ->emptyStateDescription('Every open order moved within the limit.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->columns([
                TextColumn::make('id')->label('Order')->formatStateUsing(fn (Order $record) => $record->number())->weight('semibold'),
                TextColumn::make('customer.company_name')->label('Customer')
                    ->formatStateUsing(fn (Order $record) => $record->customer?->displayName())->searchable(['company_name', 'name']),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => $state->color()),
                TextColumn::make('waiting')->label('Waiting')
                    ->state(fn (Order $record) => $record->statusSince()->diffForHumans(short: true, parts: 2, syntax: CarbonInterface::DIFF_ABSOLUTE))
                    ->color('danger'),
                TextColumn::make('next')->label('Next step')
                    ->state(fn (Order $record) => match ($record->status) {
                        OrderStatus::New => 'Sales: confirm',
                        OrderStatus::Confirmed => 'Warehouse: start picking',
                        OrderStatus::Picking => 'Warehouse: ship',
                        default => '—',
                    })->color('gray'),
                TextColumn::make('total_amount')->label('Total')->money('RSD')->alignEnd(),
            ])
            ->recordActions([
                Action::make('open')->label('Open')->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
            ]);
    }
}

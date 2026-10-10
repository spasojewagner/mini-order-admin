<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Support\Period;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCustomers extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isSales();
    }

    public function table(Table $table): Table
    {
        $p = Period::fromKey($this->pageFilters['period'] ?? null);
        $inPeriod = fn (Builder $q) => $q->whereIn('status', OrderStatus::revenueValues())->whereBetween('confirmed_at', [$p->from, $p->to]);

        return $table
            ->heading('Top customers')
            ->query(Customer::query()
                ->whereHas('orders', $inPeriod)
                ->withSum(['orders as revenue' => $inPeriod], 'total_amount')
                ->withCount(['orders as order_count' => $inPeriod])
                ->orderByDesc('revenue')
                ->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('company_name')->label('Customer')
                    ->formatStateUsing(fn (Customer $record) => $record->displayName())
                    ->description(fn (Customer $record) => $record->order_count.' orders')
                    ->url(fn (Customer $record) => CustomerResource::getUrl('view', ['record' => $record])),
                TextColumn::make('revenue')->label('Revenue')->money('RSD')->alignEnd(),
            ]);
    }
}

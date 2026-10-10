<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Numbers for the dashboard and the AI tools. Revenue = orders confirmed in
 * the period that were not cancelled, in RSD without VAT. Kept free of
 * database-specific date functions so it runs on SQLite and MySQL alike.
 */
class SalesReport
{
    private function revenueOrders(Carbon $from, Carbon $to): Builder
    {
        return Order::query()->revenue()->whereBetween('confirmed_at', [$from, $to]);
    }

    public function revenue(Carbon $from, Carbon $to): float
    {
        return round((float) $this->revenueOrders($from, $to)->sum('total_amount'), 2);
    }

    public function orderCount(Carbon $from, Carbon $to): int
    {
        return $this->revenueOrders($from, $to)->count();
    }

    public function averageOrder(Carbon $from, Carbon $to): float
    {
        $count = $this->orderCount($from, $to);

        return $count ? round($this->revenue($from, $to) / $count, 2) : 0.0;
    }

    /** @return array<string, float> Y-m-d => revenue, every day present */
    public function dailyRevenue(Carbon $from, Carbon $to): array
    {
        $totals = $this->revenueOrders($from, $to)->get(['confirmed_at', 'total_amount'])
            ->groupBy(fn (Order $o) => $o->confirmed_at->toDateString())
            ->map(fn (Collection $orders) => round((float) $orders->sum('total_amount'), 2));

        $days = [];
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $days[$day->toDateString()] = $totals[$day->toDateString()] ?? 0.0;
        }

        return $days;
    }

    /** @return array<string, float> "Oct 2026" => revenue for the last $months months */
    public function monthlyRevenue(int $months = 6): array
    {
        $from = now()->subMonths($months - 1)->startOfMonth();
        $totals = $this->revenueOrders($from, now()->endOfDay())->get(['confirmed_at', 'total_amount'])
            ->groupBy(fn (Order $o) => $o->confirmed_at->format('Y-m'))
            ->map(fn (Collection $orders) => round((float) $orders->sum('total_amount'), 2));

        $result = [];
        for ($month = $from->copy(); $month->lte(now()); $month->addMonth()) {
            $result[$month->format('M Y')] = $totals[$month->format('Y-m')] ?? 0.0;
        }

        return $result;
    }

    public function topProducts(Carbon $from, Carbon $to, int $limit = 5): array
    {
        return OrderItem::query()
            ->whereIn('order_id', $this->revenueOrders($from, $to)->select('id'))
            ->selectRaw('product_id, product_name, SUM(quantity) as quantity, SUM(subtotal) as revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product' => $row->product_name,
                'quantity' => (int) $row->quantity,
                'revenue' => round((float) $row->revenue, 2),
            ])->all();
    }

    public function topCustomers(Carbon $from, Carbon $to, int $limit = 5): array
    {
        return $this->revenueOrders($from, $to)
            ->with('customer:id,name,company_name')
            ->selectRaw('customer_id, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('customer_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'customer_id' => $row->customer_id,
                'customer' => $row->customer?->displayName(),
                'orders' => (int) $row->orders,
                'revenue' => round((float) $row->revenue, 2),
            ])->all();
    }

    /** Average days from order to shipment, for orders shipped in the period. */
    public function averageDaysToShip(Carbon $from, Carbon $to): ?float
    {
        $orders = Order::query()->whereBetween('shipped_at', [$from, $to])->get(['created_at', 'shipped_at']);

        return $orders->isEmpty()
            ? null
            : round($orders->avg(fn (Order $o) => $o->created_at->diffInMinutes($o->shipped_at) / 1440), 1);
    }

    /** Open orders that have been in their current status longer than $hours. */
    public function waitingTooLong(int $hours): Builder
    {
        $limit = now()->subHours($hours);

        return Order::query()->where(fn (Builder $q) => $q
            ->orWhere(fn ($q) => $q->where('status', OrderStatus::New->value)->where('created_at', '<', $limit))
            ->orWhere(fn ($q) => $q->where('status', OrderStatus::Confirmed->value)->where('confirmed_at', '<', $limit))
            ->orWhere(fn ($q) => $q->where('status', OrderStatus::Picking->value)->where('picking_at', '<', $limit)));
    }

    public function stockValue(): float
    {
        return round((float) Product::query()->active()->selectRaw('SUM(price * stock_quantity) as value')->value('value'), 2);
    }
}

<?php

namespace Tests;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderWorkflow;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function staff(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    protected function product(int $stock = 50, float $price = 100, int $reorderLevel = 10): Product
    {
        return Product::factory()->create(['stock_quantity' => $stock, 'price' => $price, 'reorder_level' => $reorderLevel]);
    }

    /**
     * An order built through the real workflow and moved step by step to $status.
     *
     * @param  array<int, array{0: Product, 1: int}>  $lines
     */
    protected function order(array $lines, OrderStatus $status = OrderStatus::Draft, ?Customer $customer = null): Order
    {
        $workflow = app(OrderWorkflow::class);
        $admin = User::firstWhere('role', 'admin') ?? $this->staff('admin');

        $order = $workflow->create(
            $customer ?? Customer::factory()->create(),
            array_map(fn ($l) => ['product_id' => $l[0]->id, 'quantity' => $l[1]], $lines),
            by: $admin,
        );

        $path = match ($status) {
            OrderStatus::Draft => [],
            OrderStatus::New => [OrderStatus::New],
            OrderStatus::Confirmed => [OrderStatus::Confirmed],
            OrderStatus::Picking => [OrderStatus::Confirmed, OrderStatus::Picking],
            OrderStatus::Shipped => [OrderStatus::Confirmed, OrderStatus::Picking, OrderStatus::Shipped],
            OrderStatus::Delivered => [OrderStatus::Confirmed, OrderStatus::Picking, OrderStatus::Shipped, OrderStatus::Delivered],
            OrderStatus::Cancelled => [OrderStatus::Cancelled],
        };

        foreach ($path as $step) {
            $order = $workflow->transition($order, $step, $admin);
        }

        return $order;
    }
}

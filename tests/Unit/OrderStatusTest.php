<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_the_happy_path_is_allowed_step_by_step(): void
    {
        $path = [OrderStatus::Draft, OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Picking, OrderStatus::Shipped, OrderStatus::Delivered];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue($path[$i]->canTransitionTo($path[$i + 1]), "{$path[$i]->value} → {$path[$i + 1]->value}");
        }
    }

    public function test_final_statuses_go_nowhere(): void
    {
        $this->assertSame([], OrderStatus::Delivered->allowedNext());
        $this->assertSame([], OrderStatus::Cancelled->allowedNext());
    }

    public function test_shipped_orders_can_no_longer_be_cancelled(): void
    {
        $this->assertFalse(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled));
        $this->assertTrue(OrderStatus::Picking->canTransitionTo(OrderStatus::Cancelled));
    }

    public function test_stock_is_held_from_confirmation_on(): void
    {
        $this->assertFalse(OrderStatus::New->holdsStock());
        $this->assertTrue(OrderStatus::Confirmed->holdsStock());
        $this->assertTrue(OrderStatus::Delivered->holdsStock());
        $this->assertFalse(OrderStatus::Cancelled->holdsStock());
        $this->assertSame(['confirmed', 'in_progress', 'shipped', 'delivered'], OrderStatus::revenueValues());
        $this->assertSame(['new', 'confirmed', 'in_progress'], OrderStatus::openValues());
    }
}

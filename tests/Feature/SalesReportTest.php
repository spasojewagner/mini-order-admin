<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Services\OrderWorkflow;
use App\Support\Period;
use App\Support\SalesReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_counts_confirmed_orders_only_and_not_cancelled_ones(): void
    {
        $product = $this->product(price: 1000);
        $this->order([[$product, 2]], OrderStatus::Delivered);    // 2000
        $this->order([[$product, 1]], OrderStatus::Confirmed);    // 1000
        $this->order([[$product, 5]], OrderStatus::New);          // not yet revenue
        $cancelled = $this->order([[$product, 3]], OrderStatus::Confirmed);
        app(OrderWorkflow::class)->transition($cancelled, OrderStatus::Cancelled);

        $report = app(SalesReport::class);
        $from = now()->subDay();
        $to = now()->addMinute();

        $this->assertEquals(3000, $report->revenue($from, $to));
        $this->assertSame(2, $report->orderCount($from, $to));
        $this->assertEquals(1500, $report->averageOrder($from, $to));
        $this->assertSame($product->id, $report->topProducts($from, $to)[0]['product_id']);
        $this->assertSame(3, $report->topProducts($from, $to)[0]['quantity']);
    }

    public function test_waiting_too_long_looks_at_the_time_in_the_current_status(): void
    {
        $old = $this->order([[$this->product(), 1]], OrderStatus::New);
        $old->forceFill(['created_at' => now()->subHours(60)])->save();
        $fresh = $this->order([[$this->product(), 1]], OrderStatus::New);
        $stuck = $this->order([[$this->product(), 1]], OrderStatus::Confirmed);
        $stuck->forceFill(['created_at' => now()->subDays(5), 'confirmed_at' => now()->subHours(50)])->save();

        $ids = app(SalesReport::class)->waitingTooLong(48)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$old->id, $stuck->id], $ids);
        $this->assertNotContains($fresh->id, $ids);
    }

    public function test_periods_compare_with_the_same_length_before(): void
    {
        $p = Period::fromKey('30d');

        $this->assertSame(30, $p->days());
        $this->assertTrue($p->previousTo->lt($p->from));
        $this->assertSame(30, (int) $p->previousFrom->diffInDays($p->previousTo) + 1);
        $this->assertSame('30d', Period::fromKey('nonsense')->key);
    }
}

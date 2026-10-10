<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The demo data has to be as consistent as data the app would produce itself. */
class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_data_adds_up(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(400, Order::count());

        // Stock equals the sum of its movements and never went negative.
        foreach (Product::all() as $product) {
            $this->assertSame((int) StockMovement::where('product_id', $product->id)->sum('quantity'), $product->stock_quantity, $product->sku);
        }
        $this->assertSame(0, StockMovement::where('balance_after', '<', 0)->count());

        // Order totals equal their lines.
        $mismatch = DB::table('orders')
            ->join(DB::raw('(select order_id, sum(subtotal) s from order_items group by order_id) t'), 't.order_id', '=', 'orders.id')
            ->whereRaw('abs(orders.total_amount - t.s) > 0.01')->count();
        $this->assertSame(0, $mismatch);

        // Every shipped order has a unique invoice; nothing earlier has one.
        $this->assertSame(Order::whereNotNull('shipped_at')->count(), Order::whereNotNull('invoice_number')->distinct()->count('invoice_number'));
        $this->assertSame(0, Order::whereIn('status', ['draft', 'new', 'confirmed', 'in_progress'])->whereNotNull('invoice_number')->count());

        // Something to show on the board and in "Needs attention".
        foreach ([OrderStatus::New, OrderStatus::Confirmed, OrderStatus::Picking, OrderStatus::Shipped] as $status) {
            $this->assertGreaterThan(0, Order::query()->status($status)->count(), $status->value);
        }
        $this->assertGreaterThan(0, Product::query()->active()->lowStock()->count());
    }
}

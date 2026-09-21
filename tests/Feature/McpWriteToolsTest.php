<?php

namespace Tests\Feature;

use App\Mcp\Servers\OrderAdminServer;
use App\Mcp\Tools\ConfirmOrderTool;
use App\Mcp\Tools\CreateOrderTool;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpWriteToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_alat_kreira_porudzbinu_sa_tacnim_totalom(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 50]);

        $response = OrderAdminServer::tool(CreateOrderTool::class, [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 300,
        ]);
    }

    public function test_alat_ne_kreira_porudzbinu_za_nepostojeceg_kupca(): void
    {
        $product = Product::factory()->create();

        $response = OrderAdminServer::tool(CreateOrderTool::class, [
            'customer_id' => 99999,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_potvrda_skida_lager_i_menja_status(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 10]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 0,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100,
            'quantity' => 4,
            'subtotal' => 400,
        ]);

        OrderAdminServer::tool(ConfirmOrderTool::class, [
            'order_id' => $order->id,
        ])->assertOk();

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame(6, $product->fresh()->stock_quantity);
    }

    public function test_potvrda_ne_prolazi_kad_nema_dovoljno_lagera(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 2]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 0,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100,
            'quantity' => 5,
            'subtotal' => 500,
        ]);

        OrderAdminServer::tool(ConfirmOrderTool::class, [
            'order_id' => $order->id,
        ])->assertHasErrors();

        // Najvazniji deo: stanje se nije promenilo ni delimicno.
        $this->assertSame('draft', $order->fresh()->status);
        $this->assertSame(2, $product->fresh()->stock_quantity);
    }
}

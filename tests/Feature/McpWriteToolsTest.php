<?php

namespace Tests\Feature;

use App\Mcp\Servers\OrderAdminServer;
use App\Mcp\Tools\ConfirmOrderTool;
use App\Mcp\Tools\CreateOrderTool;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Tools\ToolSearch;
use Tests\TestCase;

class McpWriteToolsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Katalog sa alatima koje ovi testovi pozivaju.
     */
    private function catalog(): ToolSearch
    {
        return new ToolSearch([
            CreateOrderTool::class,
            ConfirmOrderTool::class,
        ]);
    }

    /**
     * Alati su iza kataloga, pa se zovu kroz execute_tools - isto kao sto
     * bi ih pozvao model. ExecuteTools se pravi rucno jer ga kontejner ne
     * ume sam da sastavi (trazi listu alata kao argument konstruktora).
     */
    private function callTool(string $name, array $arguments)
    {
        [, $executeTools] = $this->catalog()->tools();

        return OrderAdminServer::tool($executeTools, [
            'calls' => [
                ['name' => $name, 'arguments' => $arguments],
            ],
        ]);
    }

    public function test_server_ne_oglasava_pojedinacne_alate(): void
    {
        OrderAdminServer::tools()->assertNotRegistered([
            CreateOrderTool::class,
            ConfirmOrderTool::class,
        ]);
    }

    public function test_pretraga_kataloga_nalazi_alat_po_smislu(): void
    {
        [$searchTools] = $this->catalog()->tools();

        OrderAdminServer::tool($searchTools, ['query' => 'potvrda porudzbine'])
            ->assertOk()
            ->assertSee('confirm-order-tool');
    }

    public function test_alat_kreira_porudzbinu_sa_tacnim_totalom(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 50]);

        $this->callTool('create-order-tool', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 300,
        ]);
    }

    public function test_alat_ne_kreira_porudzbinu_za_nepostojeceg_kupca(): void
    {
        $product = Product::factory()->create();

        $this->callTool('create-order-tool', [
            'customer_id' => 99999,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertHasErrors([
            'Kupac sa tim ID-jem ne postoji. Pronadji kupca preko search-customers-tool.',
        ]);

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

        $this->callTool('confirm-order-tool', [
            'order_id' => $order->id,
        ])->assertOk();

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame(6, $product->fresh()->stock_quantity);
    }

    public function test_potvrda_ne_prolazi_kad_nema_dovoljno_lagera(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Test Proizvod',
            'price' => 100,
            'stock_quantity' => 2,
        ]);

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

        $this->callTool('confirm-order-tool', [
            'order_id' => $order->id,
        ])->assertHasErrors([
            'Nedovoljno lagera za \'Test Proizvod\'. Dostupno: 2, traženo: 5.',
        ]);

        // Najvazniji deo: stanje se nije promenilo ni delimicno.
        $this->assertSame('draft', $order->fresh()->status);
        $this->assertSame(2, $product->fresh()->stock_quantity);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_moze_da_se_kreira_porudzbina_sa_stavkama(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 50]);

        $this->post('/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        // Porudžbina je u bazi sa tačnim totalom (100 * 3 = 300)
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'total_amount' => 300,
        ]);

        // Stavka je kreirana
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 100,
        ]);
    }

    public function test_potvrda_porudzbine_skida_lager(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 10]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 300,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100,
            'quantity' => 3,
            'subtotal' => 300,
        ]);

        // Potvrdi kroz Service
        app(OrderConfirmationService::class)->confirm($order);

        // Lager je smanjen sa 10 na 7
        $this->assertEquals(7, $product->fresh()->stock_quantity);

        // Status je confirmed
        $this->assertEquals('confirmed', $order->fresh()->status);
    }

    public function test_potvrda_ne_prolazi_ako_nema_lagera(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 2]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total_amount' => 500,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100,
            'quantity' => 5, // više nego što ima (2)
            'subtotal' => 500,
        ]);

        // Očekujemo grešku
        $this->expectException(ValidationException::class);

        try {
            app(OrderConfirmationService::class)->confirm($order);
        } finally {
            // Lager NIJE promenjen (ostao 2)
            $this->assertEquals(2, $product->fresh()->stock_quantity);
            // Status NIJE promenjen (ostao draft)
            $this->assertEquals('draft', $order->fresh()->status);
        }
    }
}
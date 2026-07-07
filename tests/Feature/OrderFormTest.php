<?php

namespace Tests\Feature;

use App\Livewire\OrderForm;
use App\Livewire\CustomerSearch;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_form_dodaje_proizvod_i_racuna_total(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 50]);

        Livewire::test(OrderForm::class)
            ->set('customerId', $customer->id)
            ->call('addProduct', $product->id)
            ->assertCount('items', 1)
            ->set('items.0.quantity', 3)
            ->assertSet('total', 300); // 100 * 3
    }

    public function test_isti_proizvod_dvaput_povecava_kolicinu(): void
    {
        $product = Product::factory()->create(['price' => 50, 'stock_quantity' => 50]);

        Livewire::test(OrderForm::class)
            ->call('addProduct', $product->id)
            ->call('addProduct', $product->id)
            ->assertCount('items', 1)          // i dalje jedna stavka
            ->assertSet('items.0.quantity', 2); // ali količina 2
    }

    public function test_order_form_snima_porudzbinu(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 50]);

        Livewire::test(OrderForm::class)
            ->set('customerId', $customer->id)
            ->call('addProduct', $product->id)
            ->set('items.0.quantity', 2)
            ->call('save');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'total_amount' => 200,
        ]);
    }

    public function test_stock_validation_sprecava_prekoracenje(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100, 'stock_quantity' => 2]);

        Livewire::test(OrderForm::class)
            ->set('customerId', $customer->id)
            ->call('addProduct', $product->id)
            ->set('items.0.quantity', 5) // više nego lager (2)
            ->call('save')
            ->assertHasErrors('stock'); // greška zbog lagera

        // Porudžbina NIJE napravljena
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_search_filtrira(): void
    {
        Customer::factory()->create(['name' => 'Petar Petrovic']);
        Customer::factory()->create(['name' => 'Marko Markovic']);

        Livewire::test(CustomerSearch::class)
            ->set('search', 'Petar')
            ->assertSee('Petar Petrovic')
            ->assertDontSee('Marko Markovic');
    }
}
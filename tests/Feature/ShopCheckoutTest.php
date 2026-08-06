<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCheckoutTest extends TestCase
{
    use RefreshDatabase;

    // Pomoćna: napravi kupca sa nalogom
    private function customerUser(): User
    {
        $customer = Customer::factory()->create();

        return User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);
    }

    public function test_katalog_prikazuje_samo_aktivne_proizvode(): void
    {
        Product::factory()->create(['name' => 'Aktivan proizvod', 'is_active' => true]);
        Product::factory()->create(['name' => 'Neaktivan proizvod', 'is_active' => false]);

        $response = $this->get('/shop');

        $response->assertSuccessful();
        // Neaktivan proizvod ne sme da se pojavi u podacima koje dobija React
        $response->assertInertia(fn ($page) => $page
            ->component('Shop/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Aktivan proizvod')
        );
    }

    public function test_porucivanje_pravi_porudzbinu_na_ulogovanog_kupca(): void
    {
        $user = $this->customerUser();
        $product = Product::factory()->create([
            'price' => 250,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        // Dodaj u korpu pa poruči
        $this->actingAs($user)
            ->post("/cart/{$product->id}", ['quantity' => 2]);

        $response = $this->actingAs($user)
            ->post('/checkout', ['note' => 'Test napomena']);

        // Porudžbina je napravljena na kupca iz naloga, sa tačnim totalom (250 * 2)
        $this->assertDatabaseHas('orders', [
            'customer_id' => $user->customer_id,
            'total_amount' => 500,
            'status' => 'draft',
            'note' => 'Test napomena',
        ]);

        // Stavka je kreirana sa kopiranom cenom
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 250,
        ]);

        // Korpa je ispražnjena posle poručivanja
        $this->assertEmpty(session()->get('cart', []));
    }

    public function test_porucivanje_ne_prolazi_ako_nema_dovoljno_lagera(): void
    {
        $user = $this->customerUser();
        $product = Product::factory()->create([
            'price' => 100,
            'stock_quantity' => 1,
            'is_active' => true,
        ]);

        // U korpu ide 5 komada, a na lageru je 1
        $this->actingAs($user)
            ->post("/cart/{$product->id}", ['quantity' => 5]);

        $this->actingAs($user)->post('/checkout');

        // Porudžbina NIJE napravljena
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_kupac_ne_moze_da_vidi_tudju_porudzbinu(): void
    {
        $user = $this->customerUser();
        $drugiKupac = Customer::factory()->create();

        $tudjaPorudzbina = Order::create([
            'customer_id' => $drugiKupac->id,
            'status' => 'draft',
            'total_amount' => 100,
        ]);

        $response = $this->actingAs($user)->get("/my-orders/{$tudjaPorudzbina->id}");

        $response->assertForbidden();
    }

    public function test_neulogovan_korisnik_ne_moze_na_checkout(): void
    {
        $response = $this->get('/checkout');

        // Preusmerava na login
        $response->assertRedirect('/login');
    }
}
<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private function customerUser(): User
    {
        return User::factory()->create(['role' => 'customer', 'customer_id' => Customer::factory()->create()->id]);
    }

    public function test_guests_land_on_the_catalog_and_staff_on_the_back_office(): void
    {
        $this->get('/')->assertRedirect(route('shop.index'));
        $this->actingAs($this->staff('sales'))->get('/')->assertRedirect('/admin');
    }

    public function test_the_catalog_shows_active_products_with_availability_not_counts(): void
    {
        Product::factory()->create(['name' => 'Still water', 'stock_quantity' => 3, 'reorder_level' => 10]);
        Product::factory()->create(['name' => 'Discontinued', 'is_active' => false]);

        $this->get('/shop')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Shop/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Still water')
            ->where('products.data.0.availability', 'low'));
    }

    public function test_checkout_creates_a_new_order_for_the_customer_without_taking_stock(): void
    {
        $user = $this->customerUser();
        $product = $this->product(stock: 10, price: 250);

        $this->actingAs($user)->post("/cart/{$product->id}", ['quantity' => 2]);
        $this->actingAs($user)->post('/checkout', ['note' => 'Back entrance'])->assertRedirect();

        $order = Order::sole();
        $this->assertSame($user->customer_id, $order->customer_id);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame('portal', $order->source);
        $this->assertEquals(500, $order->total_amount);
        $this->assertSame(10, $product->fresh()->stock_quantity); // taken only when confirmed
        $this->assertSame([], session('cart', []));
    }

    public function test_checkout_refuses_more_than_is_in_stock(): void
    {
        $user = $this->customerUser();
        $product = $this->product(stock: 1);

        $this->actingAs($user)->post("/cart/{$product->id}", ['quantity' => 5]);
        $this->actingAs($user)->post('/checkout')->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guests_must_log_in_to_check_out(): void
    {
        $this->get('/checkout')->assertRedirect('/login');
    }

    public function test_a_customer_can_withdraw_a_new_order_but_not_a_confirmed_one(): void
    {
        $user = $this->customerUser();
        $customer = $user->customer;
        $new = $this->order([[$this->product(), 1]], OrderStatus::New, $customer);
        $confirmed = $this->order([[$this->product(), 1]], OrderStatus::Confirmed, $customer);

        $this->actingAs($user)->post("/my-orders/{$new->id}/cancel")->assertSessionHas('success');
        $this->actingAs($user)->post("/my-orders/{$confirmed->id}/cancel")->assertSessionHas('error');

        $this->assertSame(OrderStatus::Cancelled, $new->fresh()->status);
        $this->assertSame(OrderStatus::Confirmed, $confirmed->fresh()->status);
        $this->assertDatabaseHas('order_events', ['order_id' => $new->id, 'to_status' => 'cancelled', 'via' => 'portal']);
    }

    public function test_customers_see_only_their_own_orders_and_invoices(): void
    {
        $user = $this->customerUser();
        $mine = $this->order([[$this->product(), 1]], OrderStatus::Shipped, $user->customer);
        $theirs = $this->order([[$this->product(), 1]], OrderStatus::Shipped);

        $this->actingAs($user)->get("/my-orders/{$mine->id}")->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('order.number', $mine->number())
                ->where('order.invoice_number', $mine->invoice_number)
                ->where('order.steps.3.done', true)
                ->where('order.steps.4.done', false));
        $this->actingAs($user)->get("/invoices/{$mine->id}")->assertOk()->assertSee($mine->invoice_number)->assertSee('VAT 20%');

        $this->actingAs($user)->get("/my-orders/{$theirs->id}")->assertForbidden();
        $this->actingAs($user)->get("/invoices/{$theirs->id}")->assertForbidden();
    }

    public function test_an_order_without_an_invoice_has_no_invoice_page(): void
    {
        $order = $this->order([[$this->product(), 1]], OrderStatus::Confirmed);

        $this->actingAs($this->staff('sales'))->get("/invoices/{$order->id}")->assertNotFound();
    }
}

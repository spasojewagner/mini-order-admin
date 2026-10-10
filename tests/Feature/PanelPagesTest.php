<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Every back-office screen renders on the demo data, for each role that may see it. */
class PanelPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function as(string $role): User
    {
        return User::firstWhere('role', $role);
    }

    public function test_admin_sees_every_screen(): void
    {
        $order = Order::query()->whereNotNull('invoice_number')->first();
        $draft = Order::query()->where('status', 'draft')->first();
        $product = Product::first();
        $customer = Customer::first();

        $this->actingAs($this->as('admin'));

        foreach ([
            '/admin', '/admin?filters[period]=year',
            '/admin/orders', '/admin/orders?tab=overdue', '/admin/orders/create', "/admin/orders/{$order->id}", "/admin/orders/{$draft->id}/edit",
            '/admin/order-board',
            '/admin/products', '/admin/products?tab=low_stock', '/admin/products/create', "/admin/products/{$product->id}", "/admin/products/{$product->id}/edit",
            '/admin/customers', '/admin/customers/create', "/admin/customers/{$customer->id}", "/admin/customers/{$customer->id}/edit",
            '/admin/conversations', '/admin/conversations/1/edit',
            '/admin/agent-runs', '/admin/sms-messages',
            '/admin/users', '/admin/settings', '/admin/api-tokens',
            "/invoices/{$order->id}",
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_sales_sees_sales_screens_only(): void
    {
        $this->actingAs($this->as('sales'));
        foreach (['/admin', '/admin/orders', '/admin/customers', '/admin/conversations', '/admin/products', '/admin/order-board', '/admin/agent-runs'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/admin/users', '/admin/settings', '/admin/sms-messages'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_warehouse_sees_warehouse_screens_only(): void
    {
        $this->actingAs($this->as('warehouse'));
        foreach (['/admin', '/admin/orders', '/admin/products', '/admin/order-board'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/admin/customers', '/admin/conversations', '/admin/users', '/admin/settings', '/admin/agent-runs'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_customers_use_the_portal_not_the_panel(): void
    {
        $customer = $this->as('customer');
        $own = Order::query()->where('customer_id', $customer->customer_id)->whereNotNull('invoice_number')->first();
        $other = Order::query()->where('customer_id', '!=', $customer->customer_id)->whereNotNull('invoice_number')->first();

        $this->actingAs($customer);
        $this->get('/admin')->assertForbidden();
        $this->get('/')->assertRedirect(route('my-orders.index'));
        foreach (['/shop', '/cart', '/my-orders', "/my-orders/{$own->id}", "/invoices/{$own->id}", '/profile'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get("/my-orders/{$other->id}")->assertForbidden();
        $this->get("/invoices/{$other->id}")->assertForbidden();
    }
}

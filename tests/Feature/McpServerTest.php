<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mcp\Servers\OrderAdminServer;
use App\Mcp\Tools\ChangeOrderStatusTool;
use App\Mcp\Tools\CreateOrderTool;
use App\Mcp\Tools\GetOrderTool;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\LowStockProductsTool;
use App\Mcp\Tools\SalesSummaryTool;
use App\Mcp\Tools\SearchCustomersTool;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Tools\ToolSearch;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/mcp/order-admin';

    private function rpc(?string $token, string $method = 'tools/list', array $params = [])
    {
        // Each call is a fresh HTTP request: drop the user the guard remembered from the last one.
        $this->app['auth']->forgetGuards();

        return $this->withHeaders(array_filter([
            'Authorization' => $token ? "Bearer {$token}" : null,
            'Accept' => 'application/json, text/event-stream',
        ]))->postJson(self::ENDPOINT, ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
    }

    /** Tools sit behind the catalog, so they are called the way a model calls them: via execute_tools. */
    private function callTool(string $tool, string $name, array $arguments)
    {
        [, $execute] = (new ToolSearch([$tool]))->tools();

        return OrderAdminServer::tool($execute, ['calls' => [['name' => $name, 'arguments' => $arguments]]]);
    }

    public function test_the_endpoint_needs_a_staff_token(): void
    {
        $this->rpc(null)->assertUnauthorized();

        $customer = User::factory()->create(['role' => 'customer']);
        $this->rpc($customer->createToken('x')->plainTextToken)->assertForbidden();

        $sales = $this->staff('sales');
        $response = $this->rpc($sales->createToken('Claude Desktop')->plainTextToken);
        $response->assertOk();
        $this->assertStringContainsString('search_tools', $response->getContent());
        $this->assertStringNotContainsString('create-order-tool', $response->getContent());
    }

    public function test_the_catalog_finds_tools_by_meaning(): void
    {
        [$search] = (new ToolSearch([CreateOrderTool::class, ChangeOrderStatusTool::class, SalesSummaryTool::class]))->tools();

        OrderAdminServer::tool($search, ['query' => 'confirm an order'])->assertOk()->assertSee('change-order-status-tool');
        OrderAdminServer::tool($search, ['query' => 'revenue last month'])->assertOk()->assertSee('sales-summary-tool');
    }

    public function test_sales_can_create_a_draft_order(): void
    {
        $this->actingAs($this->staff('sales'));
        $customer = Customer::factory()->create();
        $product = $this->product(price: 250);

        $this->callTool(CreateOrderTool::class, 'create-order-tool', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk()->assertSee('draft order SO-');

        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id, 'status' => 'draft', 'source' => 'mcp', 'total_amount' => 500]);
        $this->assertDatabaseHas('order_events', ['type' => 'created', 'via' => 'mcp']);
    }

    public function test_the_warehouse_cannot_create_orders(): void
    {
        $this->actingAs($this->staff('warehouse'));

        $this->callTool(CreateOrderTool::class, 'create-order-tool', [
            'customer_id' => Customer::factory()->create()->id,
            'items' => [['product_id' => $this->product()->id, 'quantity' => 1]],
        ])->assertHasErrors(['Only sales and admin accounts can create orders.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unknown_ids_come_back_as_readable_errors(): void
    {
        $this->actingAs($this->staff('sales'));

        $this->callTool(CreateOrderTool::class, 'create-order-tool', [
            'customer_id' => 9999,
            'items' => [['product_id' => $this->product()->id, 'quantity' => 1]],
        ])->assertHasErrors(['No customer with that ID.']);
    }

    public function test_status_changes_follow_the_role_rules(): void
    {
        $product = $this->product(stock: 10);
        $order = $this->order([[$product, 3]], OrderStatus::New);

        $this->actingAs($this->staff('warehouse'));
        $this->callTool(ChangeOrderStatusTool::class, 'change-order-status-tool', ['order_id' => $order->id, 'status' => 'confirmed'])
            ->assertHasErrors();
        $this->assertSame(OrderStatus::New, $order->fresh()->status);

        $this->actingAs($this->staff('sales'));
        $this->callTool(ChangeOrderStatusTool::class, 'change-order-status-tool', ['order_id' => $order->id, 'status' => 'confirmed'])
            ->assertOk()->assertSee('Stock was taken');
        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'to_status' => 'confirmed', 'via' => 'mcp']);
    }

    public function test_a_shortage_is_explained_to_the_model(): void
    {
        $order = $this->order([[$this->product(stock: 1), 5]], OrderStatus::New);
        $this->actingAs($this->staff('sales'));

        $this->callTool(ChangeOrderStatusTool::class, 'change-order-status-tool', ['order_id' => $order->id, 'status' => 'confirmed'])
            ->assertHasErrors(['Not enough stock']);
    }

    public function test_read_tools_answer_with_real_data(): void
    {
        $this->actingAs($this->staff('sales'));
        $low = $this->product(stock: 3, reorderLevel: 5);
        $low->update(['name' => 'Espresso beans 1 kg']);
        $this->product(stock: 50, reorderLevel: 5);
        $order = $this->order([[$low, 1]], OrderStatus::Shipped, Customer::factory()->create(['company_name' => 'Kafe Sunce', 'type' => 'company']));

        $this->callTool(LowStockProductsTool::class, 'low-stock-products-tool', [])->assertOk()->assertSee('Espresso beans 1 kg');
        $this->callTool(SearchCustomersTool::class, 'search-customers-tool', ['search' => 'Sunce'])->assertOk()->assertSee('Kafe Sunce');
        $this->callTool(ListOrdersTool::class, 'list-orders-tool', ['status' => 'shipped'])->assertOk()->assertSee($order->number());
        $this->callTool(GetOrderTool::class, 'get-order-tool', ['order_id' => $order->id])->assertOk()->assertSee($order->invoice_number);
        $this->callTool(SalesSummaryTool::class, 'sales-summary-tool', [])->assertOk()->assertSee('Kafe Sunce');
    }
}

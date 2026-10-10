<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\ApiTokens;
use App\Filament\Pages\OrderBoard;
use App\Filament\Pages\Settings as SettingsPage;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Support\Settings;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** The buttons in the panel call the same rules as everything else. */
class BackOfficeActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_sales_confirms_an_order_from_its_page(): void
    {
        $product = $this->product(stock: 10);
        $order = $this->order([[$product, 4]], OrderStatus::New);
        $this->actingAs($this->staff('sales'));

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertActionVisible('to_confirmed')
            ->assertActionHidden('to_in_progress')
            ->callAction('to_confirmed');

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        $this->assertSame(6, $product->fresh()->stock_quantity);
    }

    public function test_the_warehouse_cannot_confirm_but_can_start_picking(): void
    {
        $new = $this->order([[$this->product(), 1]], OrderStatus::New);
        $confirmed = $this->order([[$this->product(), 1]], OrderStatus::Confirmed);
        $this->actingAs($this->staff('warehouse'));

        Livewire::test(ViewOrder::class, ['record' => $new->getRouteKey()])->assertActionHidden('to_confirmed');
        Livewire::test(ViewOrder::class, ['record' => $confirmed->getRouteKey()])->callAction('to_in_progress');

        $this->assertSame(OrderStatus::Picking, $confirmed->fresh()->status);
    }

    public function test_cancelling_from_the_panel_asks_for_a_reason(): void
    {
        $order = $this->order([[$this->product(), 1]], OrderStatus::Confirmed);
        $this->actingAs($this->staff('admin'));

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('to_cancelled', data: ['note' => ''])
            ->assertHasActionErrors(['note' => 'required']);

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('to_cancelled', data: ['note' => 'Duplicate order']);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'to_status' => 'cancelled', 'description' => 'Duplicate order']);
    }

    public function test_the_board_moves_orders_one_step(): void
    {
        $order = $this->order([[$this->product(), 1]], OrderStatus::Picking);
        $this->actingAs($this->staff('warehouse'));

        Livewire::test(OrderBoard::class)
            ->assertSee($order->number())
            ->call('move', $order->id, 'shipped');

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->invoice_number);
    }

    public function test_the_board_refuses_steps_the_role_may_not_take(): void
    {
        $order = $this->order([[$this->product(), 1]], OrderStatus::New);
        $this->actingAs($this->staff('warehouse'));

        Livewire::test(OrderBoard::class)->call('move', $order->id, 'confirmed');

        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }

    public function test_receiving_goods_from_the_product_page(): void
    {
        $product = $this->product(stock: 2);
        $this->actingAs($this->staff('warehouse'));

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
            ->callAction('receive', data: ['quantity' => 48, 'note' => 'Supplier delivery 77']);

        $this->assertSame(50, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'receipt', 'quantity' => 48, 'note' => 'Supplier delivery 77']);
    }

    public function test_sales_cannot_change_stock(): void
    {
        $product = $this->product(stock: 2);
        $this->actingAs($this->staff('sales'));

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])
            ->assertActionHidden('receive')
            ->assertActionHidden('correct');
    }

    public function test_admins_change_settings_without_a_deploy(): void
    {
        $this->actingAs($this->staff('admin'));

        Livewire::test(SettingsPage::class)
            ->assertSet('data.monitor.backlog_threshold', 10)
            ->set('data.monitor.backlog_threshold', 15)
            ->set('data.invoice.vat_rate', 10)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(15, Settings::int('monitor.backlog_threshold'));
        $this->assertSame(10, Settings::int('invoice.vat_rate'));
    }

    public function test_a_staff_member_creates_and_revokes_an_mcp_token(): void
    {
        $this->actingAs($user = $this->staff('sales'));

        $page = Livewire::test(ApiTokens::class)->callAction('create', data: ['name' => 'Claude Desktop']);

        $this->assertNotNull($page->get('newToken'));
        $token = $user->tokens()->sole();
        $this->assertSame('Claude Desktop', $token->name);

        $page->call('revoke', $token->id);
        $this->assertSame(0, $user->tokens()->count());
    }
}

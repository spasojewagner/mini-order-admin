<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderStatusChanged;
use App\Services\OrderWorkflow;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function workflow(): OrderWorkflow
    {
        return app(OrderWorkflow::class);
    }

    public function test_create_merges_duplicate_lines_copies_prices_and_logs_the_event(): void
    {
        $water = $this->product(price: 390);
        $coffee = $this->product(price: 2450);

        $order = $this->workflow()->create(Customer::factory()->create(), [
            ['product_id' => $water->id, 'quantity' => 2],
            ['product_id' => $coffee->id, 'quantity' => 1],
            ['product_id' => $water->id, 'quantity' => 3],
        ], 'Back door', via: 'portal', status: OrderStatus::New);

        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame('portal', $order->source);
        $this->assertCount(2, $order->items);
        $this->assertSame(5, $order->items->firstWhere('product_id', $water->id)->quantity);
        $this->assertEquals(5 * 390 + 2450, $order->fresh()->total_amount);
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'type' => 'created', 'to_status' => 'new', 'via' => 'portal']);
    }

    public function test_an_order_needs_at_least_one_line(): void
    {
        $this->expectException(ValidationException::class);

        $this->workflow()->create(Customer::factory()->create(), []);
    }

    public function test_confirming_takes_stock_for_every_line_and_records_it(): void
    {
        $a = $this->product(stock: 10);
        $b = $this->product(stock: 5);
        $order = $this->order([[$a, 4], [$b, 5]]);

        $order = $this->workflow()->transition($order, OrderStatus::Confirmed);

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertSame(6, $a->fresh()->stock_quantity);
        $this->assertSame(0, $b->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $a->id, 'order_id' => $order->id, 'type' => 'sale', 'quantity' => -4, 'balance_after' => 6]);
    }

    public function test_a_shortage_on_one_line_confirms_nothing(): void
    {
        $plenty = $this->product(stock: 100);
        $short = $this->product(stock: 2);
        $order = $this->order([[$plenty, 10], [$short, 3]]);

        try {
            $this->workflow()->transition($order, OrderStatus::Confirmed);
            $this->fail('Expected a stock error.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Not enough stock', collect($e->errors())->flatten()->first());
        }

        $this->assertSame(OrderStatus::Draft, $order->fresh()->status);
        $this->assertSame(100, $plenty->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_steps_cannot_be_skipped_or_reversed(): void
    {
        $order = $this->order([[$this->product(), 1]], OrderStatus::Confirmed);

        foreach ([OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::New, OrderStatus::Draft] as $to) {
            try {
                $this->workflow()->transition($order, $to);
                $this->fail("Confirmed → {$to->value} should not be allowed.");
            } catch (ValidationException) {
                $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
            }
        }
    }

    public function test_cancelling_after_confirmation_puts_the_stock_back(): void
    {
        $product = $this->product(stock: 20);
        $order = $this->order([[$product, 8]], OrderStatus::Picking);
        $this->assertSame(12, $product->fresh()->stock_quantity);

        $order = $this->workflow()->transition($order, OrderStatus::Cancelled, note: 'Customer closed the shop');

        $this->assertSame(20, $product->fresh()->stock_quantity);
        $this->assertNotNull($order->cancelled_at);
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'type' => 'cancellation', 'quantity' => 8]);
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'to_status' => 'cancelled', 'description' => 'Customer closed the shop']);
    }

    public function test_cancelling_before_confirmation_does_not_touch_stock(): void
    {
        $product = $this->product(stock: 20);
        $this->order([[$product, 8]], OrderStatus::Cancelled);

        $this->assertSame(20, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_shipping_issues_sequential_invoice_numbers_for_the_year(): void
    {
        $first = $this->order([[$this->product(), 1]], OrderStatus::Shipped);
        $second = $this->order([[$this->product(), 1]], OrderStatus::Shipped);
        $year = now()->format('Y');

        $this->assertSame("INV-{$year}-00001", $first->invoice_number);
        $this->assertSame("INV-{$year}-00002", $second->invoice_number);
        $this->assertNotNull($first->invoiced_at);

        // Delivering does not issue a new number.
        $delivered = $this->workflow()->transition($first, OrderStatus::Delivered);
        $this->assertSame("INV-{$year}-00001", $delivered->invoice_number);
    }

    public function test_lines_are_locked_once_the_order_is_confirmed(): void
    {
        $order = $this->order([[$this->product(), 2]], OrderStatus::Confirmed);

        $this->expectException(ValidationException::class);
        $order->items()->first()->update(['quantity' => 50]);
    }

    public function test_editing_a_draft_line_recalculates_the_total(): void
    {
        $product = $this->product(price: 100);
        $order = $this->order([[$product, 2]]);

        /** @var OrderItem $item */
        $item = $order->items()->first();
        $item->update(['quantity' => 5, 'subtotal' => 500]);

        $this->assertEquals(500, $order->fresh()->total_amount);
    }

    public function test_the_customer_is_emailed_about_confirmation_and_shipping(): void
    {
        Notification::fake();
        $customer = Customer::factory()->create(['email' => 'buyer@shop.example']);

        $order = $this->order([[$this->product(), 1]], OrderStatus::Shipped, $customer);

        Notification::assertSentTo($customer, OrderStatusChanged::class, fn ($n) => $n->status === OrderStatus::Confirmed);
        Notification::assertSentTo($customer, OrderStatusChanged::class, fn ($n) => $n->status === OrderStatus::Shipped);
        Notification::assertSentToTimes($customer, OrderStatusChanged::class, 2); // not for "picking"
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'type' => 'email_sent']);
    }

    public function test_customer_emails_can_be_switched_off(): void
    {
        Notification::fake();
        Settings::put(['notifications.customer_emails' => false]);

        $this->order([[$this->product(), 1]], OrderStatus::Confirmed);

        Notification::assertNothingSent();
    }

    public function test_a_mail_failure_is_recorded_but_does_not_undo_the_step(): void
    {
        Notification::shouldReceive('send')->andThrow(new RuntimeException('SMTP down'));
        $customer = Customer::factory()->create(['email' => 'buyer@shop.example']);
        $order = $this->order([[$this->product(), 1]], customer: $customer);

        $order = $this->workflow()->transition($order, OrderStatus::Confirmed);

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'type' => 'email_failed', 'description' => 'SMTP down']);
    }

    public function test_roles_decide_who_may_take_each_step(): void
    {
        $workflow = $this->workflow();
        $sales = $this->staff('sales');
        $warehouse = $this->staff('warehouse');
        $admin = $this->staff('admin');

        $this->assertTrue($workflow->userMay($sales, OrderStatus::New, OrderStatus::Confirmed));
        $this->assertFalse($workflow->userMay($warehouse, OrderStatus::New, OrderStatus::Confirmed));
        $this->assertTrue($workflow->userMay($warehouse, OrderStatus::Confirmed, OrderStatus::Picking));
        $this->assertFalse($workflow->userMay($sales, OrderStatus::Picking, OrderStatus::Shipped));
        $this->assertTrue($workflow->userMay($sales, OrderStatus::Confirmed, OrderStatus::Cancelled));
        $this->assertFalse($workflow->userMay($sales, OrderStatus::Picking, OrderStatus::Cancelled));
        $this->assertTrue($workflow->userMay($admin, OrderStatus::Picking, OrderStatus::Cancelled));
        $this->assertFalse($workflow->userMay(null, OrderStatus::New, OrderStatus::Confirmed));

        $order = $this->order([[$this->product(), 1]], OrderStatus::Confirmed);
        $this->assertSame([OrderStatus::Picking], $workflow->availableFor($order, $warehouse));
        $this->assertSame([OrderStatus::Cancelled], $workflow->availableFor($order, $sales));
    }

    public function test_status_cannot_be_mass_assigned(): void
    {
        $order = Order::create(['customer_id' => Customer::factory()->create()->id, 'status' => 'delivered', 'total_amount' => 0]);

        $this->assertSame(OrderStatus::Draft, $order->fresh()->status);
    }

    public function test_every_status_change_lands_in_the_timeline(): void
    {
        $order = $this->order([[$this->product(), 1]], OrderStatus::Delivered);

        $types = $order->events()->reorder('id')->pluck('type')->all();
        $this->assertSame(['created', 'status_changed', 'status_changed', 'status_changed', 'invoiced', 'status_changed'], array_values(array_filter($types, fn ($t) => $t !== 'email_sent')));
        $this->assertSame(StockMovementType::Sale, $order->stockMovements()->first()->type);
    }
}

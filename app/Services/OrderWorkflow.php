<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Creates orders and moves them through their life. Filament, the customer
 * portal, the MCP server and the AI agent all go through here, so the rules
 * (allowed transitions, stock, invoice numbers, the timeline) hold everywhere.
 */
class OrderWorkflow
{
    /** Statuses the customer hears about by email. */
    private const NOTIFY = [OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Cancelled];

    public function __construct(
        private StockService $stock,
        private InvoiceNumbers $invoices,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function create(
        Customer|int $customer,
        array $items,
        ?string $note = null,
        string $via = 'admin',
        ?User $by = null,
        OrderStatus $status = OrderStatus::Draft,
    ): Order {
        $lines = $this->mergeLines($items);

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'An order needs at least one line.']);
        }

        return DB::transaction(function () use ($customer, $lines, $note, $via, $by, $status) {
            $order = new Order([
                'customer_id' => $customer instanceof Customer ? $customer->id : $customer,
                'note' => $note ?: null,
                'source' => in_array($via, ['admin', 'portal', 'mcp'], true) ? $via : 'admin',
                'total_amount' => 0,
            ]);
            $order->status = $status;
            $order->save();

            $products = Product::query()->whereIn('id', array_keys($lines))->get()->keyBy('id');
            $total = 0;

            foreach ($lines as $productId => $quantity) {
                $product = $products->get($productId);
                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages(['items' => "Product #{$productId} does not exist or is not for sale."]);
                }

                $subtotal = round((float) $product->price * $quantity, 2);
                $order->items()->createQuietly([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);
                $total += $subtotal;
            }

            $order->forceFill(['total_amount' => $total])->save();
            $this->log($order, 'created', null, $status, $via, $by);

            return $order;
        });
    }

    public function transition(Order $order, OrderStatus $to, ?User $by = null, string $via = 'admin', ?string $note = null): Order
    {
        $order = DB::transaction(function () use ($order, $to, $by, $via, $note) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => "Order {$locked->number()} is {$from->label()} and cannot be moved to {$to->label()}.",
                ]);
            }

            $locked->load('items');

            if ($to === OrderStatus::Confirmed) {
                if ($locked->items->isEmpty()) {
                    throw ValidationException::withMessages(['items' => 'An order without lines cannot be confirmed.']);
                }
                // All lines or nothing: a shortage on any line rolls the whole confirmation back.
                foreach ($locked->items as $item) {
                    $this->stock->move($item->product, -$item->quantity, StockMovementType::Sale, $locked, $by);
                }
            }

            if ($to === OrderStatus::Cancelled && $from->holdsStock()) {
                foreach ($locked->items as $item) {
                    $this->stock->move($item->product, $item->quantity, StockMovementType::Cancellation, $locked, $by, $note);
                }
            }

            $locked->status = $to;
            if ($column = $to->timestampColumn()) {
                $locked->{$column} = now();
            }

            if ($to === OrderStatus::Shipped && ! $locked->invoice_number) {
                $locked->invoice_number = $this->invoices->next();
                $locked->invoiced_at = now();
            }

            $locked->save();
            $this->log($locked, 'status_changed', $from, $to, $via, $by, $note);

            if ($to === OrderStatus::Shipped && $locked->wasChanged('invoice_number')) {
                $this->log($locked, 'invoiced', null, null, $via, $by, "Invoice {$locked->invoice_number}");
            }

            return $locked;
        });

        $this->notifyCustomer($order, $to, $via);

        return $order->refresh();
    }

    /** Statuses this user may move the order to right now, in the order the buttons should appear. */
    public function availableFor(Order $order, ?User $user): array
    {
        return array_values(array_filter(
            $order->status->allowedNext(),
            fn (OrderStatus $to) => $this->userMay($user, $order->status, $to),
        ));
    }

    /**
     * Who does what: sales handles the commercial side (submit, confirm,
     * cancel before picking), the warehouse moves goods (picking, shipping,
     * delivery), admins can do everything.
     */
    public function userMay(?User $user, OrderStatus $from, OrderStatus $to): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return match ($to) {
            OrderStatus::New, OrderStatus::Confirmed => $user->isSales(),
            OrderStatus::Picking, OrderStatus::Shipped, OrderStatus::Delivered => $user->isWarehouse(),
            OrderStatus::Cancelled => $user->isSales() && in_array($from, [OrderStatus::Draft, OrderStatus::New, OrderStatus::Confirmed], true),
            default => false,
        };
    }

    public function log(Order $order, string $type, ?OrderStatus $from, ?OrderStatus $to, string $via, ?User $by, ?string $description = null): OrderEvent
    {
        return OrderEvent::create([
            'order_id' => $order->id,
            'user_id' => $by?->id,
            'type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'via' => $via,
            'description' => $description,
        ]);
    }

    private function notifyCustomer(Order $order, OrderStatus $to, string $via): void
    {
        if (! in_array($to, self::NOTIFY, true) || ! Settings::bool('notifications.customer_emails')) {
            return;
        }

        $customer = $order->customer;
        if (! $customer?->email) {
            return;
        }

        try {
            $customer->notify(new OrderStatusChanged($order, $to));
            $this->log($order, 'email_sent', null, $to, 'system', null, "Status email to {$customer->email}");
        } catch (Throwable $e) {
            // A mail server problem must not undo a confirmed order; it is recorded instead.
            report($e);
            $this->log($order, 'email_failed', null, $to, 'system', null, mb_substr($e->getMessage(), 0, 480));
        }
    }

    /**
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $items
     * @return array<int, int> product_id => total quantity
     */
    private function mergeLines(array $items): array
    {
        $lines = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($productId > 0 && $quantity > 0) {
                $lines[$productId] = ($lines[$productId] ?? 0) + $quantity;
            }
        }

        return $lines;
    }
}

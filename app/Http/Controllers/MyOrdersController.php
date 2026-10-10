<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Services\OrderWorkflow;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MyOrdersController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->where('customer_id', $request->user()->customer_id)
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->through(fn (Order $order) => $this->summary($order));

        return Inertia::render('Shop/MyOrders', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): Response
    {
        $this->authorizeOwner($request, $order);
        $order->load(['items', 'events']);

        return Inertia::render('Shop/MyOrderDetail', [
            'order' => [
                ...$this->summary($order),
                'note' => $order->note,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'unit_price' => (float) $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => (float) $item->subtotal,
                ]),
                'totals' => Money::totals($order->total_amount),
                'steps' => $this->steps($order),
                'can_cancel' => $order->status === OrderStatus::New,
                'history' => $order->events
                    ->whereIn('type', ['created', 'status_changed', 'invoiced'])
                    ->map(fn (OrderEvent $e) => [
                        'id' => $e->id,
                        'at' => $e->created_at->toIso8601String(),
                        'text' => match ($e->type) {
                            'created' => 'Order placed',
                            'invoiced' => $e->description,
                            default => $e->to_status?->label() ?? '',
                        },
                    ])->values(),
            ],
        ]);
    }

    /** A customer may withdraw an order until the company confirms it. */
    public function cancel(Request $request, Order $order, OrderWorkflow $workflow): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        if ($order->status !== OrderStatus::New) {
            return back()->with('error', 'This order is already being processed. Please contact us to change it.');
        }

        try {
            $workflow->transition($order, OrderStatus::Cancelled, $request->user(), 'portal', 'Cancelled by the customer');
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', "Order {$order->number()} cancelled.");
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_if($order->customer_id !== $request->user()->customer_id, 403);
    }

    private function summary(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number(),
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'status_color' => $order->status->color(),
            'created_at' => $order->created_at->toIso8601String(),
            'total_amount' => (float) $order->total_amount,
            'items_count' => $order->items_count ?? $order->items->count(),
            'invoice_number' => $order->invoice_number,
            'invoice_url' => $order->invoice_number ? route('invoices.show', $order) : null,
        ];
    }

    /** Progress bar for the customer: placed → confirmed → picking → shipped → delivered. */
    private function steps(Order $order): array
    {
        $steps = [
            ['label' => 'Placed', 'at' => $order->created_at],
            ['label' => 'Confirmed', 'at' => $order->confirmed_at],
            ['label' => 'Picking', 'at' => $order->picking_at],
            ['label' => 'Shipped', 'at' => $order->shipped_at],
            ['label' => 'Delivered', 'at' => $order->delivered_at],
        ];

        return array_map(fn ($s) => ['label' => $s['label'], 'at' => $s['at']?->toIso8601String(), 'done' => $s['at'] !== null], $steps);
    }
}

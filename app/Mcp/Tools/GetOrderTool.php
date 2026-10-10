<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Returns one order in full: customer, lines, totals with VAT, invoice number and the timeline of who changed what and when. Use it when someone asks about a specific order.')]
class GetOrderTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ], ['order_id.exists' => 'No order with that ID.']);

        $order = Order::with(['customer', 'items', 'events.user'])->findOrFail($validated['order_id']);

        return Response::text(json_encode([
            'id' => $order->id,
            'number' => $order->number(),
            'status' => $order->status->value,
            'source' => $order->source,
            'customer' => [
                'id' => $order->customer_id,
                'name' => $order->customer?->displayName(),
                'email' => $order->customer?->email,
                'phone' => $order->customer?->phone,
            ],
            'lines' => $order->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'product' => $i->product_name,
                'quantity' => $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'subtotal' => (float) $i->subtotal,
            ]),
            'totals' => Money::totals($order->total_amount),
            'invoice_number' => $order->invoice_number,
            'note' => $order->note,
            'timeline' => $order->events->map(fn (OrderEvent $e) => [
                'at' => $e->created_at->toDateTimeString(),
                'event' => $e->summary(),
                'note' => $e->description,
            ]),
        ], JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()->description('Order ID (the number without the SO- prefix).')->required(),
        ];
    }
}

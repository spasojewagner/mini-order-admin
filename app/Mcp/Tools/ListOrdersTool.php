<?php

namespace App\Mcp\Tools;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists orders with customer, status, total (RSD, excl. VAT) and how many hours the order has been in its current status. Use it for questions like which orders are waiting, what shipped this week, or how big the backlog is. Waiting orders are "new" (not yet confirmed); "confirmed" and "in_progress" (picking) are waiting for the warehouse.')]
class ListOrdersTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', OrderStatus::values())],
            'open_only' => ['nullable', 'boolean'],
            'customer_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::query()
            ->with('customer:id,name,company_name')
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($validated['open_only'] ?? false, fn ($q) => $q->open())
            ->when($validated['customer_id'] ?? null, fn ($q, $id) => $q->where('customer_id', $id))
            ->oldest()
            ->limit($validated['limit'] ?? 25)
            ->get()
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'number' => $o->number(),
                'customer' => $o->customer?->displayName(),
                'status' => $o->status->value,
                'total' => (float) $o->total_amount,
                'created_at' => $o->created_at->toDateTimeString(),
                'hours_in_status' => (int) $o->statusSince()->diffInHours(now()),
            ]);

        return Response::text($orders->toJson());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(OrderStatus::values())
                ->description('Only orders in this status.'),
            'open_only' => $schema->boolean()
                ->description('Only orders someone still has to act on (new, confirmed, in_progress).')
                ->default(false),
            'customer_id' => $schema->integer()->description('Only orders of this customer.'),
            'limit' => $schema->integer()->description('How many orders, oldest first. At most 100.')->default(25),
        ];
    }
}

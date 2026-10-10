<?php

namespace App\Mcp\Tools;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderWorkflow;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Moves an order to its next status: confirmed (checks and takes stock for every line, all or nothing), in_progress (picking), shipped (issues the invoice), delivered, or cancelled (returns stock if it was taken). Only allowed steps work, and only for the right role: sales confirms and cancels, the warehouse picks, ships and delivers, admins do everything. This changes data.')]
class ChangeOrderStatusTool extends Tool
{
    public function handle(Request $request, OrderWorkflow $workflow): Response
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['required', 'in:'.implode(',', OrderStatus::values())],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'order_id.exists' => 'No order with that ID.',
        ]);

        $user = $request->user();
        $order = Order::findOrFail($validated['order_id']);
        $to = OrderStatus::from($validated['status']);

        if (! $user instanceof User || ! $workflow->userMay($user, $order->status, $to)) {
            $allowed = collect($workflow->availableFor($order, $user instanceof User ? $user : null))->map->value->implode(', ');

            return Response::error("This account may not move {$order->number()} from {$order->status->value} to {$to->value}."
                .($allowed ? " Allowed for you: {$allowed}." : ''));
        }

        try {
            $order = $workflow->transition($order, $to, $user, 'mcp', $validated['note'] ?? null);
        } catch (ValidationException $e) {
            // Returned as a message, not a crash, so the model can explain what went wrong.
            return Response::error(collect($e->errors())->flatten()->implode(' '));
        }

        $extra = match ($to) {
            OrderStatus::Confirmed => ' Stock was taken for every line.',
            OrderStatus::Shipped => " Invoice {$order->invoice_number} was issued.",
            OrderStatus::Cancelled => ' Any stock taken for it was returned.',
            default => '',
        };

        return Response::text("Order {$order->number()} is now {$order->status->value}.{$extra}");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()->description('Order ID.')->required(),
            'status' => $schema->string()->enum(OrderStatus::values())->description('Target status.')->required(),
            'note' => $schema->string()->description('Why, e.g. the reason for a cancellation. Shown in the order timeline.'),
        ];
    }
}

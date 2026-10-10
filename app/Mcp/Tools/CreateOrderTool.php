<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\OrderWorkflow;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates a draft order for a customer with one or more lines. It does not take stock: that happens when the order is confirmed with change-order-status-tool. Find IDs first with search-customers-tool and list-products-tool. Requires a sales or admin token.')]
class CreateOrderTool extends Tool
{
    public function handle(Request $request, OrderWorkflow $workflow): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! ($user->isAdmin() || $user->isSales())) {
            return Response::error('Only sales and admin accounts can create orders.');
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'customer_id.exists' => 'No customer with that ID. Find the customer with search-customers-tool.',
            'items.required' => 'An order needs at least one line.',
            'items.*.product_id.exists' => 'One of the products does not exist. Check the ID with list-products-tool.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ]);

        try {
            $order = $workflow->create($validated['customer_id'], $validated['items'], $validated['note'] ?? null, 'mcp', $user);
        } catch (ValidationException $e) {
            return Response::error(collect($e->errors())->flatten()->implode(' '));
        }

        return Response::text(
            "Created draft order {$order->number()} (id {$order->id}) with {$order->items()->count()} line(s), "
            .'total '.number_format((float) $order->total_amount, 2, '.', '').' RSD excl. VAT. '
            .'It is not confirmed yet and no stock was taken.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()->description('Customer ID.')->required(),
            'items' => $schema->array()
                ->description('Order lines. The same product twice is merged into one line.')
                ->min(1)
                ->items($schema->object([
                    'product_id' => $schema->integer()->description('Product ID.')->required(),
                    'quantity' => $schema->integer()->description('Quantity, at least 1.')->required(),
                ]))
                ->required(),
            'note' => $schema->string()->description('Note for the order, e.g. delivery instructions.'),
        ];
    }
}

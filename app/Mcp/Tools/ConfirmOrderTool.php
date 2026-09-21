<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use App\Services\OrderConfirmationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Potvrdjuje porudzbinu: proverava lager za sve stavke, skida ga i menja status u confirmed. Radi samo za porudzbine u statusu draft ili new. Ovo menja lager u bazi.')]
class ConfirmOrderTool extends Tool
{
    public function handle(Request $request, OrderConfirmationService $service): Response
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ], [
            'order_id.exists' => 'Porudzbina sa tim ID-jem ne postoji.',
        ]);

        $order = Order::findOrFail($validated['order_id']);

        try {
            $service->confirm($order);
        } catch (ValidationException $e) {
            // Greska se vraca kao poruka, ne kao pad - da model moze
            // da je procita i objasni korisniku sta je poslo naopako.
            return Response::error(
                collect($e->errors())->flatten()->implode(' ')
            );
        }

        return Response::text(
            "Porudzbina #{$order->id} je potvrdjena. Lager je skinut. "
            ."Ukupna vrednost: {$order->fresh()->total_amount}."
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()
                ->description('ID porudzbine koja se potvrdjuje.')
                ->required(),
        ];
    }
}

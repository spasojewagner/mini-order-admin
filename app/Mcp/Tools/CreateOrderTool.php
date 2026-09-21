<?php

namespace App\Mcp\Tools;

use App\Services\OrderCreationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Kreira novu porudzbinu u statusu draft za zadatog kupca, sa jednom ili vise stavki. Ne skida lager - to radi tek potvrda porudzbine. Koristi search-customers-tool i list-products-tool da nadjes ID-jeve pre poziva.')]
class CreateOrderTool extends Tool
{
    public function handle(Request $request, OrderCreationService $service): Response
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'customer_id.exists' => 'Kupac sa tim ID-jem ne postoji. Pronadji kupca preko search-customers-tool.',
            'items.required' => 'Porudzbina mora imati bar jednu stavku.',
            'items.*.product_id.exists' => 'Jedan od proizvoda ne postoji. Proveri ID preko list-products-tool.',
            'items.*.quantity.min' => 'Kolicina mora biti bar 1.',
        ]);

        $order = $service->create(
            $validated['customer_id'],
            $validated['items'],
            $validated['note'] ?? null
        );

        $order->load('items');

        return Response::text(
            "Kreirana porudzbina #{$order->id} u statusu draft. "
            ."Stavki: {$order->items->count()}. Ukupno: {$order->total_amount}. "
            .'Porudzbina jos nije potvrdjena i lager nije skinut.'
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()
                ->description('ID kupca za koga se pravi porudzbina.')
                ->required(),

            'items' => $schema->array()
                ->description('Stavke porudzbine.')
                ->min(1)
                ->items($schema->object([
                    'product_id' => $schema->integer()
                        ->description('ID proizvoda.')
                        ->required(),
                    'quantity' => $schema->integer()
                        ->description('Kolicina, najmanje 1.')
                        ->required(),
                ]))
                ->required(),

            'note' => $schema->string()
                ->description('Napomena uz porudzbinu.'),
        ];
    }
}

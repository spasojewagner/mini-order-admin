<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Vraca proizvode kojima je lager pao na ili ispod zadatog praga. Koristi kada korisnik pita sta treba naruciti ili cega ponestaje.')]
class LowStockProductsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'threshold' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $threshold = $validated['threshold'] ?? 10;

        $products = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity')
            ->get(['id', 'sku', 'name', 'stock_quantity']);

        if ($products->isEmpty()) {
            return Response::text("Nema proizvoda sa lagerom na ili ispod {$threshold}.");
        }

        return Response::text($products->toJson());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'threshold' => $schema->integer()
                ->description('Granica lagera. Podrazumevano 10.')
                ->default(10),
        ];
    }
}

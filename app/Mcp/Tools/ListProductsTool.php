<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Vraca listu aktivnih proizvoda sa cenom i trenutnim stanjem lagera. Koristi kada korisnik pita sta ima na stanju, koliko cega ima ili koliko nesto kosta.')]
class ListProductsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'only_in_stock' => ['nullable', 'boolean'],
        ]);

        $products = Product::query()
            ->where('is_active', true)
            ->when($validated['search'] ?? null, function ($query, $search) {
                // Zagrada je obavezna: bez nje SQL veze AND jace od OR
                // pa bi filter is_active otpao za deo rezultata.
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($validated['only_in_stock'] ?? false, fn ($q) => $q->where('stock_quantity', '>', 0))
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'sku', 'name', 'price', 'stock_quantity']);

        return Response::text($products->toJson());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Pretraga po nazivu ili SKU oznaci proizvoda.'),

            'only_in_stock' => $schema->boolean()
                ->description('Ako je true, vraca samo proizvode kojih ima na lageru.')
                ->default(false),
        ];
    }
}

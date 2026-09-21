<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Vraca listu aktivnih proizvoda sa cenom i trenutnim stanjem lagera. Koristi kada korisnik pita sta ima na stanju, koliko cega ima ili koliko nesto kosta.')]
class ListProductsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'price', 'stock_quantity']);

        return Response::text($products->toJson());
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        // Bez parametara u ovoj fazi - alat uvek vraca sve aktivne proizvode.
        // Filteri (pretraga, samo na lageru) dolaze u feature/26.
        return [];
    }
}
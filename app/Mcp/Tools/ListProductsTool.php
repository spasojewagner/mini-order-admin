<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists active products with SKU, category, price (RSD, excl. VAT), stock and reorder level. Use it when someone asks what is in stock, how much of something there is, or what it costs.')]
class ListProductsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:60'],
            'only_in_stock' => ['nullable', 'boolean'],
        ]);

        $products = Product::query()
            ->active()
            // The parentheses matter: without them OR would escape the is_active filter.
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($validated['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($validated['only_in_stock'] ?? false, fn ($q) => $q->where('stock_quantity', '>', 0))
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'sku', 'name', 'category', 'price', 'stock_quantity', 'reorder_level']);

        return Response::text($products->toJson());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Part of the product name or SKU.'),
            'category' => $schema->string()->description('Exact category name, e.g. "Beverages".'),
            'only_in_stock' => $schema->boolean()->description('Only products with stock above zero.')->default(false),
        ];
    }
}

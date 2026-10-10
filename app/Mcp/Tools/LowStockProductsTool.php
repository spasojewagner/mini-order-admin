<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists active products at or below their reorder level (each product has its own), lowest first. Use it when someone asks what to reorder or what is running out.')]
class LowStockProductsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $products = Product::query()
            ->active()
            ->when(
                isset($validated['threshold']),
                fn ($q) => $q->where('stock_quantity', '<=', $validated['threshold']),
                fn ($q) => $q->lowStock(),
            )
            ->orderBy('stock_quantity')
            ->get(['id', 'sku', 'name', 'category', 'stock_quantity', 'reorder_level']);

        if ($products->isEmpty()) {
            return Response::text('No product is at or below its reorder level.');
        }

        return Response::text($products->toJson());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'threshold' => $schema->integer()
                ->description('Optional fixed limit instead of each product\'s own reorder level.'),
        ];
    }
}

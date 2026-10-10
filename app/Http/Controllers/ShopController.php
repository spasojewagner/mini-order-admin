<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search'));
        $category = $request->input('category');

        $products = Product::query()
            ->active()
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(18)
            ->withQueryString()
            ->through(fn (Product $p) => $this->card($p));

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'categories' => Product::query()->active()->whereNotNull('category')
                ->distinct()->orderBy('category')->pluck('category'),
            'filters' => ['search' => $search, 'category' => $category],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_if(! $product->is_active, 404);

        return Inertia::render('Shop/Show', ['product' => $this->card($product)]);
    }

    /** What a customer sees: price and availability, not the exact warehouse count. */
    private function card(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'category' => $p->category,
            'price' => (float) $p->price,
            'stock' => $p->stock_quantity,
            'availability' => match (true) {
                $p->stock_quantity <= 0 => 'out',
                $p->stock_quantity <= $p->reorder_level => 'low',
                default => 'in',
            },
        ];
    }
}

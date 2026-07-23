<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    // Katalog — samo aktivni proizvodi
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $products = Product::query()
            ->where('is_active', true)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'filters' => ['search' => $search],
        ]);
    }

    // Detalj proizvoda
    public function show(Product $product): Response
    {
        // Neaktivan proizvod se ne prikazuje u prodavnici
        abort_if(! $product->is_active, 404);

        return Inertia::render('Shop/Show', [
            'product' => $product,
        ]);
    }
}
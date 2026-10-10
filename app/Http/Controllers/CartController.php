<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Shop/Cart', [
            'items' => Cart::lines(),
            'totals' => Cart::totals(),
        ]);
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10000']]);
        abort_if(! $product->is_active, 404);

        Cart::add($product, (int) $data['quantity']);

        return back()->with('success', "{$product->name} added to your cart.");
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10000']]);
        Cart::set($product, (int) $data['quantity']);

        return back();
    }

    public function remove(Product $product): RedirectResponse
    {
        Cart::remove($product);

        return back()->with('success', "{$product->name} removed from your cart.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    // Prikaz korpe
    public function index(): Response
    {
        return Inertia::render('Shop/Cart', [
            'items' => $this->cartItems(),
            'total' => $this->cartTotal(),
        ]);
    }

    // Dodavanje proizvoda u korpu
    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        abort_if(! $product->is_active, 404);

        $cart = session()->get('cart', []);
        $quantity = (int) $request->quantity;

        // Ako je proizvod već u korpi — saberi količine
        if (isset($cart[$product->id])) {
            $cart[$product->id] += $quantity;
        } else {
            $cart[$product->id] = $quantity;
        }

        session()->put('cart', $cart);

        return back()->with('success', 'Proizvod je dodat u korpu.');
    }

    // Promena količine
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = session()->get('cart', []);

        if (isset($cart[$product->id])) {
            $cart[$product->id] = (int) $request->quantity;
            session()->put('cart', $cart);
        }

        return back();
    }

    // Uklanjanje stavke
    public function remove(Product $product)
    {
        $cart = session()->get('cart', []);
        unset($cart[$product->id]);
        session()->put('cart', $cart);

        return back()->with('success', 'Stavka je uklonjena iz korpe.');
    }

    // Pomoćna: stavke korpe sa svežim podacima iz baze
    private function cartItems(): array
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return [];
        }

        $products = Product::whereIn('id', array_keys($cart))->get();

        return $products->map(function ($product) use ($cart) {
            $quantity = $cart[$product->id];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'quantity' => $quantity,
                'stock' => $product->stock_quantity,
                'subtotal' => (float) $product->price * $quantity,
            ];
        })->values()->all();
    }

    private function cartTotal(): float
    {
        return collect($this->cartItems())->sum('subtotal');
    }
}
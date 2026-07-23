<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\OrderCreationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    // Pregled pre poručivanja
    public function index(): Response
    {
        return Inertia::render('Shop/Checkout', [
            'items' => $this->cartItems(),
            'total' => $this->cartTotal(),
        ]);
    }

    // Kreiranje porudžbine — koristi postojeći servis
    public function store(Request $request, OrderCreationService $service)
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Korpa je prazna.');
        }

        $customerId = $request->user()->customer_id;

        if (! $customerId) {
            return redirect()->route('cart.index')
                ->with('error', 'Vaš nalog nije povezan sa kupcem.');
        }

        // Provera lagera pre kreiranja (sveže iz baze)
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

        foreach ($cart as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product || ! $product->is_active) {
                return back()->with('error', 'Neki proizvod iz korpe više nije dostupan.');
            }

            if ($product->stock_quantity < $quantity) {
                return back()->with(
                    'error',
                    "Nedovoljno lagera za '{$product->name}'. Dostupno: {$product->stock_quantity}."
                );
            }
        }

        // Pretvori korpu u format koji servis očekuje
        $items = [];
        foreach ($cart as $productId => $quantity) {
            $items[] = ['product_id' => $productId, 'quantity' => $quantity];
        }

        $order = $service->create($customerId, $items, $request->note);

        // Isprazni korpu
        session()->forget('cart');

        return redirect()->route('my-orders.show', $order->id)
            ->with('success', 'Porudžbina je uspešno kreirana.');
    }

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
                'price' => (float) $product->price,
                'quantity' => $quantity,
                'subtotal' => (float) $product->price * $quantity,
            ];
        })->values()->all();
    }

    private function cartTotal(): float
    {
        return collect($this->cartItems())->sum('subtotal');
    }
}
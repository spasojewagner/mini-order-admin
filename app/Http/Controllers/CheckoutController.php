<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Services\OrderWorkflow;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (Cart::count() === 0) {
            return redirect()->route('cart.index');
        }

        return Inertia::render('Shop/Checkout', [
            'items' => Cart::lines(),
            'totals' => Cart::totals(),
        ]);
    }

    public function store(Request $request, OrderWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        $lines = Cart::lines();
        if ($lines === []) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $customerId = $request->user()->customer_id;
        if (! $customerId) {
            return redirect()->route('cart.index')->with('error', 'Your account is not linked to a customer yet. Please contact us.');
        }

        // Stock is reserved only when the company confirms the order, but a
        // request for more than is on the shelf is refused right away.
        foreach ($lines as $line) {
            if (! $line['available']) {
                return back()->with('error', "{$line['name']} is no longer available.");
            }
            if ($line['stock'] < $line['quantity']) {
                return back()->with('error', "Only {$line['stock']} × {$line['name']} in stock.");
            }
        }

        try {
            $order = $workflow->create(
                $customerId,
                array_map(fn ($l) => ['product_id' => $l['id'], 'quantity' => $l['quantity']], $lines),
                $data['note'] ?? null,
                via: 'portal',
                by: $request->user(),
                status: OrderStatus::New,
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        Cart::clear();

        return redirect()->route('my-orders.show', $order)
            ->with('success', "Order {$order->number()} received. We will confirm it shortly.");
    }
}

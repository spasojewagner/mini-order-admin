<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\OrderConfirmationService;
class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer')
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('orders.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'status' => 'draft',
                'total_amount' => 0,
                'note' => $validated['note'] ?? null,
            ]);

            $total = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $product->price * $item['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total_amount' => $total]);
        });

        return redirect()->route('orders.index')
            ->with('success', 'Porudžbina je uspešno kreirana.');
    }

    public function show(Order $order)
    {
        $order->load('customer', 'items');

        return view('orders.show', compact('order'));
    }
    // Potvrda porudžbine — poziva Service klasu
    public function confirm(Order $order, OrderConfirmationService $service)
    {
        try {
            $service->confirm($order);

            return redirect()->route('orders.show', $order)
                ->with('success', 'Porudžbina je potvrđena i lager je ažuriran.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('orders.show', $order)
                ->with('error', collect($e->errors())->flatten()->first());
        }
    }
}
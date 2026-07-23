<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyOrdersController extends Controller
{
    // Lista porudžbina ulogovanog kupca
    public function index(Request $request): Response
    {
        $orders = Order::where('customer_id', $request->user()->customer_id)
            ->latest()
            ->paginate(10);

        return Inertia::render('Shop/MyOrders', [
            'orders' => $orders,
        ]);
    }

    // Detalj — samo svoja porudžbina
    public function show(Request $request, Order $order): Response
    {
        // Kupac sme da vidi samo svoje porudžbine
        abort_if($order->customer_id !== $request->user()->customer_id, 403);

        $order->load('items');

        return Inertia::render('Shop/MyOrderDetail', [
            'order' => $order,
        ]);
    }
}
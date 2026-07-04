<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderCreationService
{
    /**
     * Kreira porudžbinu sa stavkama.
     * $items: niz [['product_id' => x, 'quantity' => y], ...]
     */
    public function create($customerId, array $items, ?string $note = null): Order
    {
        return DB::transaction(function () use ($customerId, $items, $note) {
            $order = Order::create([
                'customer_id' => $customerId,
                'status' => 'draft',
                'total_amount' => 0,
                'note' => $note ?: null,
            ]);

            $total = 0;
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $product->price * (int) $item['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => (int) $item['quantity'],
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total_amount' => $total]);

            return $order;
        });
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::where('is_active', true)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $statuses = ['draft', 'new', 'confirmed', 'in_progress', 'shipped', 'cancelled'];

        // Napravi 15 demo porudžbina
        foreach (range(1, 15) as $i) {
            $customer = $customers->random();

            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => $statuses[array_rand($statuses)],
                'total_amount' => 0,
                'note' => null,
            ]);

            $total = 0;

            // Svaka porudžbina dobija 1–4 nasumične stavke
            $itemCount = rand(1, 4);
            foreach (range(1, $itemCount) as $j) {
                $product = $products->random();
                $quantity = rand(1, 5);
                $subtotal = $product->price * $quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total_amount' => $total]);
        }
    }
}
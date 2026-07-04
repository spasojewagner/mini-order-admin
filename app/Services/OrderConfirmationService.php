<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderConfirmationService
{
    /**
     * Potvrđuje porudžbinu: proverava lager, skida ga i menja status.
     * Sve u jednoj transakciji — ili sve prođe, ili ništa.
     */
    public function confirm(Order $order): void
    {
        // Dozvoli potvrdu samo iz draft ili new statusa
        if (! in_array($order->status, ['draft', 'new'])) {
            throw ValidationException::withMessages([
                'order' => 'Porudžbina se može potvrditi samo iz statusa draft ili new.',
            ]);
        }

        DB::transaction(function () use ($order) {
            // Učitaj stavke sa zaključanim proizvodima (lock za istovremene potvrde)
            $order->load('items');

            foreach ($order->items as $item) {
                // lockForUpdate zaključava red proizvoda dok traje transakcija
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'stock' => "Proizvod '{$item->product_name}' više ne postoji.",
                    ]);
                }

                if ($product->stock_quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'stock' => "Nedovoljno lagera za '{$product->name}'. Dostupno: {$product->stock_quantity}, traženo: {$item->quantity}.",
                    ]);
                }
            }

            // Ako je provera prošla za SVE stavke, tek onda skidaj lager
            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                $product->decrement('stock_quantity', $item->quantity);
            }

            $order->update(['status' => 'confirmed']);
        });
    }
}
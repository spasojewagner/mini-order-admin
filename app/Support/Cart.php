<?php

namespace App\Support;

use App\Models\Product;

/** The portal cart, kept in the session as [product_id => quantity]. */
class Cart
{
    private const KEY = 'cart';

    /** @return array<int, int> */
    public static function raw(): array
    {
        return session()->get(self::KEY, []);
    }

    public static function add(Product $product, int $quantity): void
    {
        $cart = self::raw();
        $cart[$product->id] = ($cart[$product->id] ?? 0) + $quantity;
        session()->put(self::KEY, $cart);
    }

    public static function set(Product $product, int $quantity): void
    {
        $cart = self::raw();
        if (isset($cart[$product->id])) {
            $cart[$product->id] = $quantity;
            session()->put(self::KEY, $cart);
        }
    }

    public static function remove(Product $product): void
    {
        $cart = self::raw();
        unset($cart[$product->id]);
        session()->put(self::KEY, $cart);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
    }

    public static function count(): int
    {
        return (int) array_sum(self::raw());
    }

    /** Lines with fresh prices and stock from the database. */
    public static function lines(): array
    {
        $cart = self::raw();
        if ($cart === []) {
            return [];
        }

        return Product::query()->whereIn('id', array_keys($cart))->orderBy('name')->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category,
                'price' => (float) $product->price,
                'quantity' => $cart[$product->id],
                'stock' => $product->stock_quantity,
                'available' => $product->is_active,
                'subtotal' => round((float) $product->price * $cart[$product->id], 2),
            ])->values()->all();
    }

    /** @return array{net: float, vat: float, gross: float, vat_rate: int} */
    public static function totals(): array
    {
        return Money::totals(collect(self::lines())->sum('subtotal'));
    }
}

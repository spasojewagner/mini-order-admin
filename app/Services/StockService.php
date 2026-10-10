<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place that changes products.stock_quantity. Every change writes a
 * stock movement with its reason, so the current stock can always be explained.
 */
class StockService
{
    public function move(
        Product $product,
        int $quantity,
        StockMovementType $type,
        ?Order $order = null,
        ?User $user = null,
        ?string $note = null,
        ?Carbon $at = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $type, $order, $user, $note, $at) {
            // Lock the row so two confirmations cannot sell the same units.
            $locked = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $balance = $locked->stock_quantity + $quantity;

            if ($balance < 0) {
                throw ValidationException::withMessages([
                    'stock' => "Not enough stock for '{$locked->name}': {$locked->stock_quantity} available, ".abs($quantity).' needed.',
                ]);
            }

            $locked->forceFill(['stock_quantity' => $balance])->save();
            $product->forceFill(['stock_quantity' => $balance])->syncOriginalAttribute('stock_quantity');

            return StockMovement::create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $balance,
                'order_id' => $order?->id,
                'user_id' => $user?->id,
                'note' => $note,
                'created_at' => $at ?? now(),
            ]);
        });
    }

    public function opening(Product $product, int $quantity, ?User $user = null): ?StockMovement
    {
        return $quantity > 0
            ? $this->move($product, $quantity, StockMovementType::Opening, user: $user, note: 'Opening balance')
            : null;
    }

    public function receive(Product $product, int $quantity, ?User $user = null, ?string $note = null): StockMovement
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Received quantity must be at least 1.']);
        }

        return $this->move($product, $quantity, StockMovementType::Receipt, user: $user, note: $note);
    }

    /** Set stock to a counted value (stocktake). Records the difference, never a silent overwrite. */
    public function correctTo(Product $product, int $counted, ?User $user, string $reason, StockMovementType $type = StockMovementType::Adjustment): ?StockMovement
    {
        if ($counted < 0) {
            throw ValidationException::withMessages(['quantity' => 'Counted quantity cannot be negative.']);
        }

        $delta = $counted - $product->fresh()->stock_quantity;

        return $delta === 0 ? null : $this->move($product, $delta, $type, user: $user, note: $reason);
    }
}

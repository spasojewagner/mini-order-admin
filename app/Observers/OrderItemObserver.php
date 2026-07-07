<?php

namespace App\Observers;

use App\Models\OrderItem;

class OrderItemObserver
{
    public function saved(OrderItem $item): void
    {
        $this->recalculateTotal($item);
    }

    public function deleted(OrderItem $item): void
    {
        $this->recalculateTotal($item);
    }

    private function recalculateTotal(OrderItem $item): void
    {
        $order = $item->order;
        if ($order) {
            $order->update([
                'total_amount' => $order->items()->sum('subtotal'),
            ]);
        }
    }
}
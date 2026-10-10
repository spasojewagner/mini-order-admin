<?php

namespace App\Observers;

use App\Models\OrderItem;
use Illuminate\Validation\ValidationException;

class OrderItemObserver
{
    /** Lines of a confirmed order are locked: stock was already taken for them. */
    public function saving(OrderItem $item): void
    {
        $this->guardEditable($item);
    }

    public function deleting(OrderItem $item): void
    {
        $this->guardEditable($item);
    }

    public function saved(OrderItem $item): void
    {
        $this->recalculateTotal($item);
    }

    public function deleted(OrderItem $item): void
    {
        $this->recalculateTotal($item);
    }

    private function guardEditable(OrderItem $item): void
    {
        $order = $item->order;
        if ($order && ! $order->isEditable()) {
            throw ValidationException::withMessages([
                'items' => "Lines of order {$order->number()} cannot change: it is already {$order->status->label()}.",
            ]);
        }
    }

    private function recalculateTotal(OrderItem $item): void
    {
        $order = $item->order;
        if ($order) {
            $order->forceFill(['total_amount' => $order->items()->sum('subtotal')])->save();
        }
    }
}

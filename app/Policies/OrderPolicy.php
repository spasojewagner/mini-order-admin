<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

/** Status changes have their own rules in OrderWorkflow::userMay(). */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSales();
    }

    /** Customer, note and lines can change only before confirmation. */
    public function update(User $user, Order $order): bool
    {
        return ($user->isAdmin() || $user->isSales()) && $order->isEditable();
    }

    /** Only orders that never touched stock: drafts and cancelled ones. */
    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin() && in_array($order->status, [OrderStatus::Draft, OrderStatus::Cancelled], true);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}

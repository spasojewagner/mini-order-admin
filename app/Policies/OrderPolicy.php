<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSales() || $user->isWarehouse();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isSales() || $user->isWarehouse();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSales();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->isSales();
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
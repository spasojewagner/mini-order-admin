<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isStaff();
    }

    /** Catalogue and prices are an admin decision. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    /** Receiving goods and stock corrections: the warehouse and admins. */
    public function adjustStock(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isWarehouse();
    }

    /** A product that was ever ordered stays, so old orders and invoices still make sense. Deactivate it instead. */
    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin() && ! $product->orderItems()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

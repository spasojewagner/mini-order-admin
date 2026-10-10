<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSales();
    }

    public function view(User $user, Customer $customer): bool
    {
        // The warehouse opens customers from an order to see the delivery address.
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSales();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->isAdmin() || $user->isSales();
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin() && ! $customer->orders()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

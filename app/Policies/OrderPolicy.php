<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Order $order): bool { return true; }
    public function create(User $user): bool { return $user->isAdmin() || $user->isAgent(); }
    public function update(User $user, Order $order): bool { return $user->isAdmin() || $user->isAgent(); }
    public function delete(User $user, Order $order): bool { return $user->isAdmin(); }
    public function deleteAny(User $user): bool { return $user->isAdmin(); }
}
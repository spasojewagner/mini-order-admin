<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy
{
    // Svi ulogovani vide listu i pojedinačni zapis
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    // Kreiranje i izmena — admin i agent
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAgent();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->isAdmin() || $user->isAgent();
    }

    // Brisanje — samo admin
    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
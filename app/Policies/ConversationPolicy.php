<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSales();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\AgentRun;
use App\Models\User;

class AgentRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSales();
    }

    public function view(User $user, AgentRun $run): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AgentRun $run): bool
    {
        return false;
    }

    public function delete(User $user, AgentRun $run): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

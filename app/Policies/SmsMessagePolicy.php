<?php

namespace App\Policies;

use App\Models\SmsMessage;
use App\Models\User;

/** SMS drafts come from the AI agent; an admin approves or rejects them. */
class SmsMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, SmsMessage $sms): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    /** The text of a pending draft may be corrected before approval. */
    public function update(User $user, SmsMessage $sms): bool
    {
        return $user->isAdmin() && $sms->status === 'pending';
    }

    public function delete(User $user, SmsMessage $sms): bool
    {
        return $user->isAdmin() && $sms->status !== 'sent';
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}

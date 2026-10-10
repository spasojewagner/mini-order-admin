<?php

namespace App\Services;

use App\Contracts\MessageSender;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SmsApprovalService
{
    public function __construct(private MessageSender $sender) {}

    /**
     * Approves and sends the message. If sending fails the status stays
     * pending, so it can be tried again.
     */
    public function approve(SmsMessage $sms, User $user): string
    {
        $this->guardPending($sms);

        $this->sender->send($sms->phone, $sms->body);

        $sms->update([
            'status' => 'sent',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'sent_at' => now(),
        ]);

        return "Message #{$sms->id} approved and sent.";
    }

    public function reject(SmsMessage $sms, User $user): string
    {
        $this->guardPending($sms);

        $sms->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return "Message #{$sms->id} rejected; it was not sent.";
    }

    private function guardPending(SmsMessage $sms): void
    {
        if ($sms->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => "Message #{$sms->id} was already handled (status: {$sms->status}).",
            ]);
        }
    }
}

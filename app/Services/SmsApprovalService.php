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
     * Odobrava i salje poruku. Ako slanje pukne, status ostaje pending
     * pa moze da se pokusa ponovo.
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

        return "Poruka #{$sms->id} je odobrena i poslata.";
    }

    public function reject(SmsMessage $sms, User $user): string
    {
        $this->guardPending($sms);

        $sms->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return "Poruka #{$sms->id} je odbijena i nije poslata.";
    }

    private function guardPending(SmsMessage $sms): void
    {
        if ($sms->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => "Poruka #{$sms->id} je vec obradjena (status: {$sms->status}).",
            ]);
        }
    }
}

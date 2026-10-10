<?php

namespace App\Services;

use App\Models\SmsMessage;

class SmsDraftService
{
    /** Saves an SMS draft that waits for a person to approve it. Sends nothing. */
    public function draft(string $body, ?string $reason = null): string
    {
        $phone = config('services.sms.alert_recipient');

        if (blank($phone)) {
            return 'No phone number is configured (SMS_ALERT_RECIPIENT). No draft was saved.';
        }

        // One pending draft is enough. Without this check every scheduled run
        // would add another one and the approver would get a pile of duplicates.
        if (SmsMessage::pending()->exists()) {
            return 'An SMS draft is already waiting for approval. No new draft was saved.';
        }

        $sms = SmsMessage::create([
            'phone' => $phone,
            'body' => $body,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        return "SMS draft #{$sms->id} saved; it waits for approval and has not been sent.";
    }
}

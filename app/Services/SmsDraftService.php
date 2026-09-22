<?php

namespace App\Services;

use App\Models\SmsMessage;

class SmsDraftService
{
    /**
     * Pravi SMS draft koji ceka ljudsko odobrenje. Ne salje nista.
     */
    public function draft(string $body, ?string $reason = null): string
    {
        $phone = config('services.sms.alert_recipient');

        if (blank($phone)) {
            return 'Broj telefona nije podesen (SMS_ALERT_RECIPIENT). Draft nije napravljen.';
        }

        // Jedan draft na cekanju je dovoljan. Bez ovoga bi svako pokretanje
        // schedulera dodavalo novi red i covek bi dobio gomilu istih poruka.
        if (SmsMessage::pending()->exists()) {
            return 'Vec postoji SMS draft koji ceka odobrenje. Nov nije napravljen.';
        }

        $sms = SmsMessage::create([
            'phone' => $phone,
            'body' => $body,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        return "SMS draft #{$sms->id} je sacuvan i ceka odobrenje. Nije poslat.";
    }
}

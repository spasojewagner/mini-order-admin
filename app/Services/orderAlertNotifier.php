<?php

namespace App\Services;

use App\Mail\OrderAlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class OrderAlertNotifier
{
    /**
     * Koliko dugo se isti alarm ne ponavlja.
     */
    public const COOLDOWN_HOURS = 6;

    public const CACHE_KEY = 'order_monitor.alert_sent';

    /**
     * Salje alarm ako nije vec poslat. Vraca poruku o ishodu.
     */
    public function send(string $subject, string $body): string
    {
        if (Cache::has(self::CACHE_KEY)) {
            return 'Alarm je vec poslat u poslednjih '
                .self::COOLDOWN_HOURS.' sati. Mejl nije poslat ponovo.';
        }

        $recipient = config('mail.order_alert_recipient');

        if (blank($recipient)) {
            return 'Primalac nije podesen (ORDER_ALERT_RECIPIENT). Mejl nije poslat.';
        }

        Mail::to($recipient)->send(new OrderAlertMail($subject, $body));

        Cache::put(
            self::CACHE_KEY,
            now()->toIso8601String(),
            now()->addHours(self::COOLDOWN_HOURS)
        );

        return "Mejl je poslat na {$recipient}.";
    }
}

<?php

namespace App\Services;

use App\Mail\OrderAlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class OrderAlertNotifier
{
    /** How long the same alert is not repeated. */
    public const COOLDOWN_HOURS = 6;

    public const CACHE_KEY = 'order_monitor.alert_sent';

    /**
     * Sends the alert unless one went out recently. Returns what happened, for the agent to read.
     *
     * @param  array<int, array{id: int, customer: string, amount: float}>  $orders
     */
    public function send(string $subject, string $summary, array $orders, ?string $recommendation = null): string
    {
        if (Cache::has(self::CACHE_KEY)) {
            return 'An alert was already sent in the last '
                .self::COOLDOWN_HOURS.' hours. No email was sent this time.';
        }

        $recipient = config('mail.order_alert_recipient');

        if (blank($recipient)) {
            return 'No recipient is configured (ORDER_ALERT_RECIPIENT). No email was sent.';
        }

        Mail::to($recipient)->send(
            new OrderAlertMail($subject, $summary, $orders, $recommendation)
        );

        Cache::put(
            self::CACHE_KEY,
            now()->toIso8601String(),
            now()->addHours(self::COOLDOWN_HOURS)
        );

        return "Email sent to {$recipient}.";
    }
}

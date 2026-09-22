<?php

namespace App\Services\Senders;

use App\Contracts\MessageSender;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppSender implements MessageSender
{
    public function send(string $recipient, string $body): void
    {
        $token = config('services.whatsapp.token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $version = config('services.whatsapp.version');

        if (blank($token) || blank($phoneNumberId)) {
            throw new RuntimeException(
                'WhatsApp nije podesen (WHATSAPP_TOKEN, WHATSAPP_PHONE_NUMBER_ID).'
            );
        }

        $response = Http::withToken($token)
            ->timeout(15)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($recipient, '+'),
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp je odbio poruku: '.$response->body());
        }
    }
}

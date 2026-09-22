<?php

namespace App\Services\Senders;

use App\Contracts\MessageSender;
use Illuminate\Support\Facades\Log;

class LogSender implements MessageSender
{
    public function send(string $recipient, string $body): void
    {
        Log::info('[ALERT] poruka za '.$recipient, ['body' => $body]);
    }
}

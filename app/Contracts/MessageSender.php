<?php

namespace App\Contracts;

interface MessageSender
{
    /**
     * Sends the message to the recipient. Throws if it fails.
     */
    public function send(string $recipient, string $body): void;
}

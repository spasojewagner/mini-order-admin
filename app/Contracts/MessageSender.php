<?php

namespace App\Contracts;

interface MessageSender
{
    /**
     * Salje poruku primaocu. Baca izuzetak ako ne uspe.
     */
    public function send(string $recipient, string $body): void;
}

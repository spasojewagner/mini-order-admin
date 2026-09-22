<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{id: int, customer: string, amount: float}>  $orders
     */
    public function __construct(
        public string $subjectLine,
        public string $summary,
        public array $orders,
        public ?string $recommendation = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-alert');
    }
}

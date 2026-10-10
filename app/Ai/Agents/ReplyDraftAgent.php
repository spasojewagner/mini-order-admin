<?php

namespace App\Ai\Agents;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Support\Settings;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Drafts a reply to a customer conversation. It gets the conversation and the
 * customer's recent orders as context, so "where is my order?" gets a real
 * answer. The draft is saved as a message marked AI-generated for a person to
 * check and edit; nothing is sent to the customer automatically.
 */
#[Provider(Lab::Groq)]
#[Model('openai/gpt-oss-120b')]
class ReplyDraftAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $company = Settings::get('company.name');

        return <<<TXT
        You write replies for the sales team of {$company}, a wholesale distributor, to messages
        from business customers (shops, cafés, hotels). Write the reply only, ready to send:
        no subject line, no notes to the team, no placeholders in brackets.
        Reply in the language of the customer's last message (often Serbian, in Latin script).
        Be short, warm and concrete. Use the order data you are given: order numbers, statuses,
        dates and amounts. Never promise a delivery date, a discount or anything else that is
        not in the data; offer to check instead. Sign as "{$company} sales team".
        TXT;
    }

    public static function promptFor(Conversation $conversation): string
    {
        $conversation->loadMissing(['customer', 'messages']);
        $customer = $conversation->customer;

        $orders = $customer
            ? Order::query()->where('customer_id', $customer->id)->latest()->limit(5)->get()
                ->map(fn (Order $o) => sprintf(
                    '- %s placed %s, status %s%s, total %s RSD excl. VAT%s',
                    $o->number(),
                    $o->created_at->format('d.m.Y'),
                    $o->status->label(),
                    $o->status->timestampColumn() && $o->{$o->status->timestampColumn()} ? ' since '.$o->{$o->status->timestampColumn()}->format('d.m.Y H:i') : '',
                    number_format((float) $o->total_amount, 2, ',', '.'),
                    $o->invoice_number ? ", invoice {$o->invoice_number}" : '',
                ))->implode("\n")
            : '';

        $transcript = $conversation->messages->sortBy('created_at')->take(-12)
            ->map(fn (Message $m) => ($m->sender === 'customer' ? 'Customer' : 'Us').': '.$m->body)
            ->implode("\n");

        return implode("\n\n", array_filter([
            'Customer: '.($customer ? $customer->displayName().($customer->company_name ? " (contact: {$customer->name})" : '') : 'unknown'),
            "Channel: {$conversation->channel}. Subject: ".($conversation->subject ?: '—'),
            $orders ? "Recent orders of this customer:\n{$orders}" : 'This customer has no orders yet.',
            "Conversation so far (oldest first):\n{$transcript}",
            'Write our next reply.',
        ]));
    }
}

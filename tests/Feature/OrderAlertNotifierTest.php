<?php

namespace Tests\Feature;

use App\Mail\OrderAlertMail;
use App\Services\OrderAlertNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderAlertNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(OrderAlertNotifier::CACHE_KEY);
        config(['mail.order_alert_recipient' => 'manager@example.com']);
    }

    /**
     * @return array<int, array{id: int, customer: string, amount: float}>
     */
    private function orders(): array
    {
        return [
            ['id' => 2, 'customer' => 'Market Lipa d.o.o.', 'amount' => 39945.79],
            ['id' => 8, 'customer' => 'Kafe Sunce', 'amount' => 1617.93],
        ];
    }

    public function test_sends_the_email_when_none_went_out_recently(): void
    {
        Mail::fake();

        $result = (new OrderAlertNotifier)->send(
            'Order backlog above the limit',
            '11 open orders, the oldest waiting since Monday.',
            $this->orders(),
            'Confirm the oldest orders first.',
        );

        Mail::assertSent(OrderAlertMail::class, 1);
        $this->assertStringContainsString('Email sent to manager@example.com', $result);
    }

    public function test_the_email_carries_the_orders_it_was_given(): void
    {
        Mail::fake();

        (new OrderAlertNotifier)->send(
            'Alert',
            'Summary.',
            $this->orders(),
        );

        Mail::assertSent(OrderAlertMail::class, function (OrderAlertMail $mail) {
            return $mail->subjectLine === 'Alert'
                && count($mail->orders) === 2
                && $mail->orders[0]['id'] === 2
                && $mail->recommendation === null;
        });
    }

    public function test_does_not_send_twice_within_the_cooldown(): void
    {
        Mail::fake();
        $notifier = new OrderAlertNotifier;

        $notifier->send('Alert', 'First.', $this->orders());
        $result = $notifier->send('Alert', 'Second.', $this->orders());

        // The point: one email in total, not two.
        Mail::assertSent(OrderAlertMail::class, 1);
        $this->assertStringContainsString('already sent', $result);
    }

    public function test_does_not_send_without_a_recipient(): void
    {
        Mail::fake();
        config(['mail.order_alert_recipient' => null]);

        $result = (new OrderAlertNotifier)->send('Alert', 'Summary.', $this->orders());

        Mail::assertNothingSent();
        $this->assertStringContainsString('No recipient', $result);
    }
}

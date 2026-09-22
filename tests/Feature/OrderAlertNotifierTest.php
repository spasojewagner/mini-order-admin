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
        config(['mail.order_alert_recipient' => 'menadzer@test.com']);
    }

    public function test_salje_mejl_kada_nije_skoro_slat(): void
    {
        Mail::fake();

        $result = (new OrderAlertNotifier)->send('Alarm', 'Ima 11 neobradjenih.');

        Mail::assertSent(OrderAlertMail::class, 1);
        $this->assertStringContainsString('poslat', $result);
    }

    public function test_ne_salje_drugi_put_u_istom_periodu(): void
    {
        Mail::fake();
        $notifier = new OrderAlertNotifier;

        $notifier->send('Alarm', 'Prvi put.');
        $result = $notifier->send('Alarm', 'Drugi put.');

        // Najvaznije: ukupno jedan mejl, ne dva.
        Mail::assertSent(OrderAlertMail::class, 1);
        $this->assertStringContainsString('vec poslat', $result);
    }

    public function test_ne_salje_kada_primalac_nije_podesen(): void
    {
        Mail::fake();
        config(['mail.order_alert_recipient' => null]);

        $result = (new OrderAlertNotifier)->send('Alarm', 'Telo.');

        Mail::assertNothingSent();
        $this->assertStringContainsString('nije podesen', $result);
    }
}

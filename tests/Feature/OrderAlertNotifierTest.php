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

    /**
     * @return array<int, array{id: int, customer: string, amount: float}>
     */
    private function orders(): array
    {
        return [
            ['id' => 2, 'customer' => 'Petar Petrovic', 'amount' => 39945.79],
            ['id' => 8, 'customer' => 'Ana Anic', 'amount' => 1617.93],
        ];
    }

    public function test_salje_mejl_kada_nije_skoro_slat(): void
    {
        Mail::fake();

        $result = (new OrderAlertNotifier)->send(
            'Prekoracen prag neobradjenih porudzbina',
            'Ima 11 neobradjenih porudzbina, najstarije cekaju od jula.',
            $this->orders(),
            'Prvo resiti najstarije porudzbine.',
        );

        Mail::assertSent(OrderAlertMail::class, 1);
        $this->assertStringContainsString('poslat', $result);
    }

    public function test_mejl_nosi_prosledjene_porudzbine(): void
    {
        Mail::fake();

        (new OrderAlertNotifier)->send(
            'Alarm',
            'Sazetak.',
            $this->orders(),
        );

        Mail::assertSent(OrderAlertMail::class, function (OrderAlertMail $mail) {
            return $mail->subjectLine === 'Alarm'
                && count($mail->orders) === 2
                && $mail->orders[0]['id'] === 2
                && $mail->recommendation === null;
        });
    }

    public function test_ne_salje_drugi_put_u_istom_periodu(): void
    {
        Mail::fake();
        $notifier = new OrderAlertNotifier;

        $notifier->send('Alarm', 'Prvi put.', $this->orders());
        $result = $notifier->send('Alarm', 'Drugi put.', $this->orders());

        // Najvaznije: ukupno jedan mejl, ne dva.
        Mail::assertSent(OrderAlertMail::class, 1);
        $this->assertStringContainsString('vec poslat', $result);
    }

    public function test_ne_salje_kada_primalac_nije_podesen(): void
    {
        Mail::fake();
        config(['mail.order_alert_recipient' => null]);

        $result = (new OrderAlertNotifier)->send('Alarm', 'Sazetak.', $this->orders());

        Mail::assertNothingSent();
        $this->assertStringContainsString('nije podesen', $result);
    }
}

<?php

namespace Tests\Feature;

use App\Models\SmsMessage;
use App\Services\SmsDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsDraftServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sms.alert_recipient' => '+381601234567']);
    }

    public function test_pravi_draft_koji_ceka_odobrenje(): void
    {
        $result = (new SmsDraftService)->draft(
            'Hitno: 30 neobradjenih porudzbina.',
            'Prag je dvostruko prekoracen.'
        );

        $this->assertDatabaseHas('sms_messages', [
            'phone' => '+381601234567',
            'body' => 'Hitno: 30 neobradjenih porudzbina.',
            'status' => 'pending',
        ]);

        // Nista nije poslato - samo sacuvano.
        $this->assertNull(SmsMessage::first()->sent_at);
        $this->assertStringContainsString('ceka odobrenje', $result);
    }

    public function test_ne_pravi_drugi_draft_dok_prvi_ceka(): void
    {
        $service = new SmsDraftService;

        $service->draft('Prvi.');
        $result = $service->draft('Drugi.');

        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertStringContainsString('Vec postoji', $result);
    }

    public function test_pravi_nov_draft_kada_je_prethodni_obradjen(): void
    {
        $service = new SmsDraftService;

        $service->draft('Prvi.');
        SmsMessage::first()->update(['status' => 'rejected']);

        $service->draft('Drugi.');

        $this->assertDatabaseCount('sms_messages', 2);
    }

    public function test_ne_pravi_draft_kada_broj_nije_podesen(): void
    {
        config(['services.sms.alert_recipient' => null]);

        $result = (new SmsDraftService)->draft('Telo.');

        $this->assertDatabaseCount('sms_messages', 0);
        $this->assertStringContainsString('nije podesen', $result);
    }
}

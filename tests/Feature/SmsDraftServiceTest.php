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

    public function test_saves_a_draft_that_waits_for_approval(): void
    {
        $result = (new SmsDraftService)->draft(
            'Urgent: 30 open orders.',
            'Twice the limit.'
        );

        $this->assertDatabaseHas('sms_messages', [
            'phone' => '+381601234567',
            'body' => 'Urgent: 30 open orders.',
            'status' => 'pending',
        ]);

        // Nothing was sent, only saved.
        $this->assertNull(SmsMessage::first()->sent_at);
        $this->assertStringContainsString('waits for approval', $result);
    }

    public function test_no_second_draft_while_one_is_pending(): void
    {
        $service = new SmsDraftService;

        $service->draft('First.');
        $result = $service->draft('Second.');

        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertStringContainsString('already waiting', $result);
    }

    public function test_a_new_draft_once_the_previous_was_handled(): void
    {
        $service = new SmsDraftService;

        $service->draft('First.');
        SmsMessage::first()->update(['status' => 'rejected']);

        $service->draft('Second.');

        $this->assertDatabaseCount('sms_messages', 2);
    }

    public function test_no_draft_without_a_phone_number(): void
    {
        config(['services.sms.alert_recipient' => null]);

        $result = (new SmsDraftService)->draft('Body.');

        $this->assertDatabaseCount('sms_messages', 0);
        $this->assertStringContainsString('No phone number', $result);
    }
}

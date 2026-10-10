<?php

namespace Tests\Feature;

use App\Contracts\MessageSender;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\SmsApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class SmsApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private function sms(): SmsMessage
    {
        return SmsMessage::create([
            'phone' => '+381601234567',
            'body' => 'Urgent: 30 open orders.',
            'status' => 'pending',
        ]);
    }

    public function test_approving_sends_and_records_who_approved(): void
    {
        $sent = [];
        $sender = new class($sent) implements MessageSender
        {
            public function __construct(public array &$sent) {}

            public function send(string $recipient, string $body): void
            {
                $this->sent[] = [$recipient, $body];
            }
        };

        $user = User::factory()->create();
        $sms = $this->sms();

        (new SmsApprovalService($sender))->approve($sms, $user);

        $this->assertCount(1, $sent);
        $this->assertSame('sent', $sms->fresh()->status);
        $this->assertSame($user->id, $sms->fresh()->approved_by);
        $this->assertNotNull($sms->fresh()->sent_at);
    }

    public function test_rejecting_sends_nothing(): void
    {
        $sender = new class implements MessageSender
        {
            public function send(string $recipient, string $body): void
            {
                throw new RuntimeException('Must not be sent when rejected.');
            }
        };

        $user = User::factory()->create();
        $sms = $this->sms();

        (new SmsApprovalService($sender))->reject($sms, $user);

        $this->assertSame('rejected', $sms->fresh()->status);
        $this->assertNull($sms->fresh()->sent_at);
    }

    public function test_cannot_be_approved_twice(): void
    {
        $sender = new class implements MessageSender
        {
            public int $calls = 0;

            public function send(string $recipient, string $body): void
            {
                $this->calls++;
            }
        };

        $user = User::factory()->create();
        $sms = $this->sms();
        $service = new SmsApprovalService($sender);

        $service->approve($sms, $user);

        $this->expectException(ValidationException::class);
        $service->approve($sms->fresh(), $user);
    }

    public function test_stays_pending_when_sending_fails(): void
    {
        $sender = new class implements MessageSender
        {
            public function send(string $recipient, string $body): void
            {
                throw new RuntimeException('Gateway unavailable.');
            }
        };

        $user = User::factory()->create();
        $sms = $this->sms();

        try {
            (new SmsApprovalService($sender))->approve($sms, $user);
        } catch (RuntimeException) {
            // expected
        }

        // The point: not marked as sent, so it can be tried again.
        $this->assertSame('pending', $sms->fresh()->status);
        $this->assertNull($sms->fresh()->sent_at);
    }
}

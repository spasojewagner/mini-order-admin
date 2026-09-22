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
            'body' => 'Hitno: 30 neobradjenih.',
            'status' => 'pending',
        ]);
    }

    public function test_odobravanje_salje_poruku_i_belezi_ko_je_odobrio(): void
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

    public function test_odbijanje_ne_salje_nista(): void
    {
        $sender = new class implements MessageSender
        {
            public function send(string $recipient, string $body): void
            {
                throw new RuntimeException('Ne sme da se posalje kod odbijanja.');
            }
        };

        $user = User::factory()->create();
        $sms = $this->sms();

        (new SmsApprovalService($sender))->reject($sms, $user);

        $this->assertSame('rejected', $sms->fresh()->status);
        $this->assertNull($sms->fresh()->sent_at);
    }

    public function test_ne_moze_dvaput_da_se_odobri(): void
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

    public function test_status_ostaje_pending_ako_slanje_pukne(): void
    {
        $sender = new class implements MessageSender
        {
            public function send(string $recipient, string $body): void
            {
                throw new RuntimeException('Gateway nedostupan.');
            }
        };

        $user = User::factory()->create();
        $sms = $this->sms();

        try {
            (new SmsApprovalService($sender))->approve($sms, $user);
        } catch (RuntimeException) {
            // ocekivano
        }

        // Najvaznije: poruka nije oznacena kao poslata, moze da se pokusa ponovo.
        $this->assertSame('pending', $sms->fresh()->status);
        $this->assertNull($sms->fresh()->sent_at);
    }
}

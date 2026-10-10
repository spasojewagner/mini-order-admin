<?php

namespace Tests\Feature;

use App\Ai\Agents\OrderMonitorAgent;
use App\Ai\Agents\ReplyDraftAgent;
use App\Ai\Tools\DraftSmsTool;
use App\Ai\Tools\SendAlertMailTool;
use App\Enums\OrderStatus;
use App\Filament\Resources\Conversations\Pages\EditConversation;
use App\Filament\Resources\Conversations\RelationManagers\MessagesRelationManager;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\LowStockProductsTool;
use App\Models\AgentRun;
use App\Models\Conversation;
use App\Models\Customer;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\McpServerTool;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class AiAgentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_monitor_reads_through_the_mcp_tools_and_only_drafts_sms(): void
    {
        $tools = collect((new OrderMonitorAgent)->tools());

        $this->assertCount(4, $tools);
        // Read tools are the MCP server's own tools, wrapped, not copies.
        $this->assertCount(2, $tools->filter(fn ($t) => $t instanceof McpServerTool));
        $this->assertTrue(McpServerTool::supports(new ListOrdersTool));
        $this->assertTrue(McpServerTool::supports(new LowStockProductsTool));
        $this->assertCount(1, $tools->filter(fn ($t) => $t instanceof SendAlertMailTool));
        // If a tool that really sends an SMS ever shows up here, this fails on purpose.
        $this->assertCount(1, $tools->filter(fn ($t) => $t instanceof DraftSmsTool));
    }

    public function test_the_monitor_thresholds_come_from_settings(): void
    {
        Settings::put(['monitor.backlog_threshold' => 7, 'monitor.urgent_threshold' => 31, 'monitor.max_age_days' => 4]);

        $instructions = (string) (new OrderMonitorAgent)->instructions();

        $this->assertStringContainsString('more than 7 open orders', $instructions);
        $this->assertStringContainsString('more than 31 open orders', $instructions);
        $this->assertStringContainsString('4 days (hours_in_status > 4 × 24)', $instructions);
    }

    public function test_every_monitor_run_is_logged(): void
    {
        OrderMonitorAgent::fake(['There are 3 open orders, below the limit. Nothing sent.']);

        $this->artisan('app:monitor-orders')->assertSuccessful();

        $run = AgentRun::sole();
        $this->assertSame('completed', $run->status);
        $this->assertSame('schedule', $run->trigger);
        $this->assertStringContainsString('3 open orders', $run->response);
        $this->assertNotNull($run->duration_ms);
    }

    public function test_a_failed_run_is_logged_with_its_error(): void
    {
        OrderMonitorAgent::fake(fn () => throw new RuntimeException('Groq is unreachable'));
        $admin = $this->staff('admin');

        $this->artisan('app:monitor-orders', ['--manual' => true, '--user' => $admin->id])->assertFailed();

        $run = AgentRun::sole();
        $this->assertSame('failed', $run->status);
        $this->assertSame('manual', $run->trigger);
        $this->assertSame($admin->id, $run->user_id);
        $this->assertStringContainsString('Groq is unreachable', $run->error);
    }

    public function test_tool_side_effects_are_recorded_on_the_run(): void
    {
        config(['services.sms.alert_recipient' => '+381601234567']);
        $run = AgentRun::begin('Order monitor');

        (new DraftSmsTool)->handle(new Request(['body' => '30 open orders, oldest 9 days.', 'reason' => 'Email was ignored for a week.']));

        $this->assertSame('draft_sms', $run->fresh()->actions[0]['tool']);
        $this->assertDatabaseHas('sms_messages', ['status' => 'pending', 'reason' => 'Email was ignored for a week.']);
    }

    public function test_the_reply_draft_knows_the_customers_orders(): void
    {
        $customer = Customer::factory()->create(['company_name' => 'Kafe Sunce', 'type' => 'company']);
        $order = $this->order([[$this->product(), 2]], OrderStatus::Shipped, $customer);
        $conversation = Conversation::create(['customer_id' => $customer->id, 'channel' => 'viber', 'status' => 'open', 'subject' => 'Delivery']);
        $conversation->messages()->create(['sender' => 'customer', 'body' => 'Gde je naša porudžbina?']);

        $prompt = ReplyDraftAgent::promptFor($conversation);

        $this->assertStringContainsString('Kafe Sunce', $prompt);
        $this->assertStringContainsString($order->number(), $prompt);
        $this->assertStringContainsString('Shipped', $prompt);
        $this->assertStringContainsString($order->invoice_number, $prompt);
        $this->assertStringContainsString('Customer: Gde je naša porudžbina?', $prompt);
    }

    public function test_the_draft_button_saves_an_ai_message_for_review(): void
    {
        ReplyDraftAgent::fake(['Poštovani, porudžbina je poslata danas. Srdačan pozdrav, prodaja.']);
        $this->actingAs($sales = $this->staff('sales'));
        $conversation = Conversation::create(['customer_id' => Customer::factory()->create()->id, 'channel' => 'email', 'status' => 'open']);
        $conversation->messages()->create(['sender' => 'customer', 'body' => 'Kada stiže roba?']);

        Livewire::test(MessagesRelationManager::class, ['ownerRecord' => $conversation, 'pageClass' => EditConversation::class])
            ->callTableAction('draftReply');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender' => 'admin',
            'user_id' => $sales->id,
            'ai_generated' => true,
            'body' => 'Poštovani, porudžbina je poslata danas. Srdačan pozdrav, prodaja.',
        ]);
        ReplyDraftAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'Kada stiže roba?'));
    }
}

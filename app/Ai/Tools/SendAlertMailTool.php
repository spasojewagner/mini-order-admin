<?php

namespace App\Ai\Tools;

use App\Models\AgentRun;
use App\Services\OrderAlertNotifier;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SendAlertMailTool implements Tool
{
    public function __construct(
        private OrderAlertNotifier $notifier = new OrderAlertNotifier,
    ) {}

    public function description(): Stringable|string
    {
        return 'Emails the manager about the order backlog. Use it only when the number of open '
            .'orders really is above the threshold. At most one email per '
            .OrderAlertNotifier::COOLDOWN_HOURS.' hours is sent; later calls are refused.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $validated = $request->validate([
                'subject' => ['required', 'string', 'max:150'],
                'summary' => ['required', 'string', 'max:500'],
                'orders' => ['required', 'array', 'min:1', 'max:20'],
                'orders.*.id' => ['required', 'integer'],
                'orders.*.customer' => ['required', 'string', 'max:150'],
                'orders.*.amount' => ['required', 'numeric'],
                'recommendation' => ['nullable', 'string', 'max:500'],
            ]);
        } catch (ValidationException $e) {
            return 'Invalid arguments: '.collect($e->errors())->flatten()->implode(' ');
        }

        $result = $this->notifier->send(
            $validated['subject'],
            $validated['summary'],
            $validated['orders'],
            $validated['recommendation'] ?? null,
        );

        AgentRun::record('send_alert_mail', $result);

        return $result;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()->description('Short, specific subject line.')->required(),
            'summary' => $schema->string()
                ->description('One sentence: how many orders are open and since when. No list here; that goes in orders.')
                ->required(),
            'orders' => $schema->array()
                ->description('Orders for the table in the email, most urgent first. At most 20.')
                ->min(1)
                ->max(20)
                ->items($schema->object([
                    'id' => $schema->integer()->description('Order id.')->required(),
                    'customer' => $schema->string()->description('Customer name.')->required(),
                    'amount' => $schema->number()->description('Order total.')->required(),
                ]))
                ->required(),
            'recommendation' => $schema->string()->description('One sentence: what to handle first and why.'),
        ];
    }
}

<?php

namespace App\Ai\Tools;

use App\Models\AgentRun;
use App\Services\SmsDraftService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DraftSmsTool implements Tool
{
    public function __construct(
        private SmsDraftService $drafts = new SmsDraftService,
    ) {}

    public function description(): Stringable|string
    {
        return 'Prepares an SMS to the manager and saves it as a draft that waits for a person '
            .'to approve it. It does NOT send anything. Use it only when the situation is urgent '
            .'enough that an email is not enough.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $validated = $request->validate([
                'body' => ['required', 'string', 'max:300'],
                'reason' => ['nullable', 'string', 'max:300'],
            ]);
        } catch (ValidationException $e) {
            return 'Invalid arguments: '.collect($e->errors())->flatten()->implode(' ');
        }

        $result = $this->drafts->draft($validated['body'], $validated['reason'] ?? null);
        AgentRun::record('draft_sms', $result);

        return $result;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'body' => $schema->string()
                ->description('SMS text, at most 300 characters. Short and specific, no greeting.')
                ->required(),
            'reason' => $schema->string()
                ->description('Why an SMS and not just an email. The person approving it reads this.'),
        ];
    }
}

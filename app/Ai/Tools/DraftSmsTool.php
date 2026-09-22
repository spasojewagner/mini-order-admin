<?php

namespace App\Ai\Tools;

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
        return 'Priprema SMS poruku za menadzera i cuva je kao draft koji ceka '
            .'ljudsko odobrenje. NE salje SMS. Koristi kada je stanje toliko '
            .'hitno da mejl nije dovoljan.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $validated = $request->validate([
                'body' => ['required', 'string', 'max:300'],
                'reason' => ['nullable', 'string', 'max:300'],
            ]);
        } catch (ValidationException $e) {
            return 'Neispravni argumenti: '.collect($e->errors())->flatten()->implode(' ');
        }

        return $this->drafts->draft($validated['body'], $validated['reason'] ?? null);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'body' => $schema->string()
                ->description('Tekst SMS poruke, najvise 300 znakova. '
                    .'Kratko i konkretno, bez pozdrava.')
                ->required(),

            'reason' => $schema->string()
                ->description('Zasto smatras da je potreban SMS a ne samo mejl. '
                    .'Ovo cita covek koji odobrava.'),
        ];
    }
}

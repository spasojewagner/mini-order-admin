<?php

namespace App\Ai\Tools;

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
        return 'Salje mejl menadzeru o neobradjenim porudzbinama. '
            .'Koristi ovaj alat samo kada broj neobradjenih porudzbina '
            .'stvarno prelazi zadati prag. Salje se najvise jednom u '
            .OrderAlertNotifier::COOLDOWN_HOURS.' sati.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $validated = $request->validate([
                'subject' => ['required', 'string', 'max:150'],
                'body' => ['required', 'string', 'max:4000'],
            ]);
        } catch (ValidationException $e) {
            // Gresku vracamo kao tekst da je model procita i ispravi,
            // umesto da cela komanda pukne.
            return 'Neispravni argumenti: '.collect($e->errors())->flatten()->implode(' ');
        }

        return $this->notifier->send($validated['subject'], $validated['body']);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()
                ->description('Naslov mejla, kratak i konkretan.')
                ->required(),

            'body' => $schema->string()
                ->description('Telo mejla: koliko ima neobradjenih porudzbina, '
                    .'koje su najhitnije i zasto. Obican tekst, bez HTML-a.')
                ->required(),
        ];
    }
}

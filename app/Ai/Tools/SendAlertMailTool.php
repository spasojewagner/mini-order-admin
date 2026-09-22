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
                'summary' => ['required', 'string', 'max:500'],
                'orders' => ['required', 'array', 'min:1', 'max:20'],
                'orders.*.id' => ['required', 'integer'],
                'orders.*.customer' => ['required', 'string', 'max:150'],
                'orders.*.amount' => ['required', 'numeric'],
                'recommendation' => ['nullable', 'string', 'max:500'],
            ]);
        } catch (ValidationException $e) {
            return 'Neispravni argumenti: '.collect($e->errors())->flatten()->implode(' ');
        }

        return $this->notifier->send(
            $validated['subject'],
            $validated['summary'],
            $validated['orders'],
            $validated['recommendation'] ?? null,
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()
                ->description('Naslov mejla, kratak i konkretan.')
                ->required(),

            'summary' => $schema->string()
                ->description('Jedna recenica: koliko ima neobradjenih porudzbina '
                    .'i od kada cekaju. Bez nabrajanja - to ide u orders.')
                ->required(),

            'orders' => $schema->array()
                ->description('Porudzbine koje treba prikazati u tabeli, '
                    .'najhitnije prvo. Najvise 20.')
                ->min(1)
                ->max(20)
                ->items($schema->object([
                    'id' => $schema->integer()->description('Broj porudzbine.')->required(),
                    'customer' => $schema->string()->description('Ime kupca.')->required(),
                    'amount' => $schema->number()->description('Ukupna vrednost.')->required(),
                ]))
                ->required(),

            'recommendation' => $schema->string()
                ->description('Jedna recenica sta prvo resiti i zasto.'),
        ];
    }
}

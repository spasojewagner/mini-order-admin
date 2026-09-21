<?php

namespace App\Mcp\Tools;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Pretrazuje kupce po imenu, nazivu firme, emailu ili telefonu. Koristi kada korisnik pominje kupca po imenu ili trazi njegove kontakt podatke.')]
class SearchCustomersTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'search.required' => 'Morate uneti pojam za pretragu, npr. ime kupca ili naziv firme.',
            'search.min' => 'Pojam za pretragu mora imati bar 2 znaka.',
        ]);

        $search = $validated['search'];

        $customers = Customer::query()
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(25)
            ->get(['id', 'type', 'name', 'company_name', 'email', 'phone']);

        if ($customers->isEmpty()) {
            return Response::text('Nema kupca koji odgovara pojmu: '.$search);
        }

        return Response::text($customers->toJson());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Ime kupca, naziv firme, email ili telefon. Najmanje 2 znaka.')
                ->required(),
        ];
    }
}

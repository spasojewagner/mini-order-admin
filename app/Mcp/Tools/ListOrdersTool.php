<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Vraca porudzbine sa kupcem, statusom i ukupnom vrednoscu. Koristi kada korisnik pita koje porudzbine cekaju, sta je poslato ili koliko ima neobradjenih porudzbina.')]
class ListOrdersTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:draft,new,confirmed,in_progress,shipped,cancelled'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = Order::query()
            ->with('customer:id,name,company_name')
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->limit($validated['limit'] ?? 25)
            ->get(['id', 'customer_id', 'status', 'total_amount', 'created_at']);

        return Response::text($orders->toJson());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['draft', 'new', 'confirmed', 'in_progress', 'shipped', 'cancelled'])
                ->description('Filtrira porudzbine po statusu. Neobradjene su draft i new.'),

            'limit' => $schema->integer()
                ->description('Koliko porudzbina vratiti, najvise 100.')
                ->default(25),
        ];
    }
}

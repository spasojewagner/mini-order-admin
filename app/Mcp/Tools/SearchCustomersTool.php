<?php

namespace App\Mcp\Tools;

use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Finds customers by contact name, company name, tax ID, email or phone. Use it to get a customer ID before creating an order, or to look up contact details.')]
class SearchCustomersTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'search.required' => 'Give a search term, e.g. a contact or company name.',
            'search.min' => 'The search term needs at least 2 characters.',
        ]);

        $search = $validated['search'];

        $customers = Customer::query()
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('tax_id', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"))
            ->withCount('orders')
            ->limit(25)
            ->get(['id', 'type', 'name', 'company_name', 'tax_id', 'email', 'phone', 'address']);

        if ($customers->isEmpty()) {
            return Response::text("No customer matches \"{$search}\".");
        }

        return Response::text($customers->toJson());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Contact name, company name, tax ID, email or phone. At least 2 characters.')
                ->required(),
        ];
    }
}

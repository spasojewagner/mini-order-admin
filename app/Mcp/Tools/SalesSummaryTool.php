<?php

namespace App\Mcp\Tools;

use App\Support\SalesReport;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Sales figures for a period: revenue (confirmed orders, RSD excl. VAT), number of orders, average order value, top products and top customers. Use it for questions like "how did we do last month" or "who are our biggest customers".')]
class SalesSummaryTool extends Tool
{
    public function handle(Request $request, SalesReport $report): Response
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : now()->subDays(29)->startOfDay();
        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();

        return Response::text(json_encode([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'revenue' => $report->revenue($from, $to),
            'orders' => $report->orderCount($from, $to),
            'average_order' => $report->averageOrder($from, $to),
            'top_products' => $report->topProducts($from, $to, 5),
            'top_customers' => $report->topCustomers($from, $to, 5),
        ], JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()->description('Start date, YYYY-MM-DD. Default: 30 days ago.'),
            'to' => $schema->string()->description('End date, YYYY-MM-DD. Default: today.'),
        ];
    }
}

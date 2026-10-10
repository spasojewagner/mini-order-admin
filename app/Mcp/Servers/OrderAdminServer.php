<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ChangeOrderStatusTool;
use App\Mcp\Tools\CreateOrderTool;
use App\Mcp\Tools\GetOrderTool;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\ListProductsTool;
use App\Mcp\Tools\LowStockProductsTool;
use App\Mcp\Tools\SalesSummaryTool;
use App\Mcp\Tools\SearchCustomersTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tools\ToolSearch;

#[Name('Order Admin')]
#[Version('1.0.0')]
#[Instructions('Back office of a B2B wholesale distributor: customers (shops, cafés, hotels), products and stock, orders and sales. Amounts are RSD without VAT. Use these tools instead of guessing. Orders move draft → new → confirmed → in_progress (picking) → shipped → delivered, or cancelled; the server enforces the allowed steps and the caller\'s role.')]
class OrderAdminServer extends Server
{
    /**
     * The tools sit behind a ToolSearch catalog: clients see only search_tools
     * and execute_tools, which keeps the tool list short for the model.
     */
    protected array $tools = [
        ToolSearch::class => [
            ListProductsTool::class,
            SearchCustomersTool::class,
            ListOrdersTool::class,
            GetOrderTool::class,
            LowStockProductsTool::class,
            SalesSummaryTool::class,
            CreateOrderTool::class,
            ChangeOrderStatusTool::class,
        ],
    ];

    protected array $resources = [];

    protected array $prompts = [];
}

<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ConfirmOrderTool;
use App\Mcp\Tools\CreateOrderTool;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\ListProductsTool;
use App\Mcp\Tools\LowStockProductsTool;
use App\Mcp\Tools\SearchCustomersTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tools\ToolSearch;

#[Name('Order Admin Server')]
#[Version('0.4.0')]
#[Instructions('Pristup podacima iz mini-order-admin sistema: kupci, proizvodi, lager i porudzbine. Koristi ove alate umesto nagadjanja kada korisnik pita o stanju u prodavnici.')]

class OrderAdminServer extends Server
{
    protected array $tools = [
        ToolSearch::class => [
            ListProductsTool::class,
            SearchCustomersTool::class,
            ListOrdersTool::class,
            LowStockProductsTool::class,
            CreateOrderTool::class,
            ConfirmOrderTool::class,
        ],
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}

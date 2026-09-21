<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use App\Mcp\Tools\ListProductsTool;
#[Name('Order Admin Server')]
#[Version('0.1.0')]
#[Instructions('Pristup podacima iz mini-order-admin sistema: kupci, proizvodi, lager i porudzbine. Koristi ove alate umesto nagadjanja kada korisnik pita o stanju u prodavnici.')]
class OrderAdminServer extends Server
{
    protected array $tools = [
        ListProductsTool::class,
    ];
    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}

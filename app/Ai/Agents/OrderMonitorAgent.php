<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SendAlertMailTool;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\LowStockProductsTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\McpServerTool;
use Stringable;

#[Provider(Lab::Groq)]
#[Model('openai/gpt-oss-120b')]
#[MaxSteps(5)]
class OrderMonitorAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
        Ti pratis porudzbine u mini-order-admin sistemu.

        Neobradjene porudzbine su one u statusu draft ili new.

        Uvek koristi alate da procitas stvarno stanje - nikad ne nagadjaj
        brojeve i nikad ne izmisljaj porudzbine koje nisi video kroz alat.

        Odgovaraj na srpskom, kratko i konkretno, bez uvoda i izvinjavanja.
        Kad navodis porudzbine, navedi broj, kupca i vrednost.

        Kada te pozovu da proveris stanje: procitaj porudzbine kroz alat.
        Ako je neobradjenih vise od 10, posalji mejl alatom za slanje.
        U telo mejla stavi tacan broj, koje porudzbine najduze cekaju i sta
        treba prvo resiti. Ako ih je 10 ili manje, ne salji nista i samo
        kratko javi koliko ih ima.
        TXT;
    }

    /**
     * Read alati su isti oni koje koristi MCP server - ne duplira se logika.
     * Slanje mejla je jedini write alat i ima sopstvenu zastitu od ponavljanja.
     */
    public function tools(): iterable
    {
        return [
            new McpServerTool(new ListOrdersTool),
            new McpServerTool(new LowStockProductsTool),
            new SendAlertMailTool,
        ];
    }
}

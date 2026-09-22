<?php

namespace Tests\Feature;

use App\Ai\Agents\OrderMonitorAgent;
use App\Ai\Tools\DraftSmsTool;
use App\Ai\Tools\SendAlertMailTool;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\LowStockProductsTool;
use Laravel\Ai\Tools\McpServerTool;
use Tests\TestCase;

class OrderMonitorAgentTest extends TestCase
{
    public function test_agent_koristi_postojece_mcp_alate_za_citanje(): void
    {
        $tools = collect((new OrderMonitorAgent)->tools());

        $this->assertCount(4, $tools);

        // Read alati su obmotani MCP alati - ne pisu se ponovo za agenta.
        $this->assertCount(2, $tools->filter(fn ($t) => $t instanceof McpServerTool));

        $this->assertTrue(McpServerTool::supports(new ListOrdersTool));
        $this->assertTrue(McpServerTool::supports(new LowStockProductsTool));
    }

    public function test_agent_salje_mejl_sam_a_sms_samo_priprema(): void
    {
        $tools = collect((new OrderMonitorAgent)->tools());

        // Mejl agent salje sam.
        $this->assertCount(1, $tools->filter(fn ($t) => $t instanceof SendAlertMailTool));

        // SMS samo priprema kao draft. Ako se ovde ikad pojavi alat koji
        // stvarno salje SMS, ovaj test pada namerno.
        $this->assertCount(1, $tools->filter(fn ($t) => $t instanceof DraftSmsTool));
    }
}

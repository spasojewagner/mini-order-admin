<?php

namespace Tests\Feature;

use App\Ai\Agents\OrderMonitorAgent;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\LowStockProductsTool;
use Laravel\Ai\Tools\McpServerTool;
use Tests\TestCase;

class OrderMonitorAgentTest extends TestCase
{
    public function test_agent_koristi_postojece_mcp_alate(): void
    {
        $tools = collect((new OrderMonitorAgent)->tools());

        $this->assertCount(2, $tools);
        $this->assertTrue($tools->every(fn ($t) => $t instanceof McpServerTool));
        $this->assertTrue(McpServerTool::supports(new ListOrdersTool));
        $this->assertTrue(McpServerTool::supports(new LowStockProductsTool));
    }
}

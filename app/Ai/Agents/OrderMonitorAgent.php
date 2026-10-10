<?php

namespace App\Ai\Agents;

use App\Ai\Tools\DraftSmsTool;
use App\Ai\Tools\SendAlertMailTool;
use App\Mcp\Tools\ListOrdersTool;
use App\Mcp\Tools\LowStockProductsTool;
use App\Support\Settings;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\McpServerTool;
use Stringable;

/**
 * Runs every hour (routes/console.php) and from the "Run now" button in the
 * panel. Reads the backlog, emails the manager when it is too big, and for an
 * emergency prepares an SMS that a person has to approve before it is sent.
 * The thresholds come from Settings, so they can change without a deploy.
 */
#[Provider(Lab::Groq)]
#[Model('openai/gpt-oss-120b')]
#[MaxSteps(6)]
class OrderMonitorAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $backlog = Settings::int('monitor.backlog_threshold');
        $urgent = Settings::int('monitor.urgent_threshold');
        $days = Settings::int('monitor.max_age_days');

        return <<<TXT
        You watch the order backlog of a wholesale distributor.
        Open orders are those with status new (waiting for confirmation), confirmed or in_progress
        (waiting for the warehouse). Always read the real state through the tools; never guess
        numbers and never mention an order you have not seen in a tool result.
        Answer in English, briefly, without preamble. When you list orders, give the number,
        the customer and the amount.

        When asked to check the backlog:
        1. List open orders (open_only = true, limit 100) and check low stock.
        2. If there are more than {$backlog} open orders, send one email with the alert tool: the exact
           count, the orders that have waited longest (oldest first, at most 20), and one sentence on
           what to handle first. Mention low-stock products if they block open orders.
        3. If it is an emergency, more than {$urgent} open orders or any order open for more than
           {$days} days (hours_in_status > {$days} × 24), also prepare an SMS draft. It is not sent
           until a person approves it, so explain in the reason field why email is not enough.
        4. If there are {$backlog} open orders or fewer, send nothing and just say how many there are.
        TXT;
    }

    /**
     * The read tools are the MCP server's own tools, not copies. The agent may
     * send the email itself; the SMS is only drafted for human approval.
     */
    public function tools(): iterable
    {
        return [
            new McpServerTool(new ListOrdersTool),
            new McpServerTool(new LowStockProductsTool),
            new SendAlertMailTool,
            new DraftSmsTool,
        ];
    }
}

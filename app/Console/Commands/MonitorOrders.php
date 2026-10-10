<?php

namespace App\Console\Commands;

use App\Ai\Agents\OrderMonitorAgent;
use App\Models\AgentRun;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class MonitorOrders extends Command
{
    protected $signature = 'app:monitor-orders {--manual : Started by a person, not the scheduler} {--user= : ID of the user who started it}';

    protected $description = 'Let the AI agent check the order backlog and alert the manager if needed';

    public function handle(): int
    {
        $run = AgentRun::begin(
            'Order monitor',
            $this->option('manual') ? 'manual' : 'schedule',
            $this->option('user') ? User::find($this->option('user')) : null,
        );
        $started = hrtime(true);

        try {
            $response = (string) (new OrderMonitorAgent)->prompt('Check the order backlog and act on the instructions.');
        } catch (Throwable $e) {
            $run->fail($e->getMessage(), $this->elapsed($started));
            $this->error('The agent failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $run->finish($response, $this->elapsed($started));
        $this->line($response);

        return self::SUCCESS;
    }

    private function elapsed(int $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }
}

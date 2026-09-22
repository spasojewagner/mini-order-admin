<?php

namespace App\Console\Commands;

use App\Ai\Agents\OrderMonitorAgent;
use Illuminate\Console\Command;

class MonitorOrders extends Command
{
    protected $signature = 'app:monitor-orders';

    protected $description = 'Agent proverava neobradjene porudzbine i po potrebi salje mejl';

    public function handle(): int
    {
        $response = (new OrderMonitorAgent)->prompt(
            'Proveri koliko ima neobradjenih porudzbina i postupi po instrukcijama.'
        );

        $this->line((string) $response);

        return self::SUCCESS;
    }
}

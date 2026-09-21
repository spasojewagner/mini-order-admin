<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Groq)]
#[Model('openai/gpt-oss-120b')]
class OrderMonitorAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'Ti si asistent koji prati porudzbine u mini-order-admin sistemu. '
            .'Odgovaraj kratko i na srpskom, bez uvoda i bez izvinjavanja.';
    }
}

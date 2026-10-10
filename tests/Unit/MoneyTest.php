<?php

namespace Tests\Unit;

use App\Support\Money;
use App\Support\Settings;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_vat_is_added_on_top_of_net_prices(): void
    {
        Settings::flush();

        $this->assertSame(['net' => 1000.0, 'vat' => 200.0, 'gross' => 1200.0, 'vat_rate' => 20], Money::totals(1000));
        $this->assertSame('1.234,50 RSD', Money::format(1234.5));
    }
}

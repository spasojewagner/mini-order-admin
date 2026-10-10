<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Settings;

/** Sequential invoice numbers per year: INV-2026-00001, INV-2026-00002, ... */
class InvoiceNumbers
{
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = Settings::get('invoice.prefix')."-{$year}-";

        $last = Order::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('invoice_number');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}

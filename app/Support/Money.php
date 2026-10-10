<?php

namespace App\Support;

class Money
{
    /** 1.234,50 RSD: documents and detail screens. */
    public static function format(float|string|null $amount, bool $withCurrency = true): string
    {
        $formatted = number_format((float) $amount, 2, ',', '.');

        return $withCurrency ? "{$formatted} RSD" : $formatted;
    }

    /** 1.235 RSD: dashboards and lists, same as the panel's tables. */
    public static function short(float|string|null $amount): string
    {
        return number_format(round((float) $amount), 0, ',', '.').' RSD';
    }

    /**
     * Prices are kept without VAT (B2B); VAT is added on the invoice.
     *
     * @return array{net: float, vat: float, gross: float, vat_rate: int}
     */
    public static function totals(float|string $net): array
    {
        $rate = Settings::int('invoice.vat_rate');
        $net = round((float) $net, 2);
        $vat = round($net * $rate / 100, 2);

        return ['net' => $net, 'vat' => $vat, 'gross' => round($net + $vat, 2), 'vat_rate' => $rate];
    }
}

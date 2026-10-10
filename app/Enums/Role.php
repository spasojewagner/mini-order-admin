<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Warehouse = 'warehouse';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Sales => 'Sales',
            self::Warehouse => 'Warehouse',
            self::Customer => 'Customer (portal)',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /** @return array<string, string> */
    public static function staffOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $r) => $r->isStaff())
            ->mapWithKeys(fn (self $r) => [$r->value => $r->label()])
            ->all();
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => $r->label()])->all();
    }
}

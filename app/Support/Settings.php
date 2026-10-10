<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Application settings an administrator can change from the panel
 * (Settings page). Every key has a default here, so a fresh install works.
 */
class Settings
{
    public const DEFAULTS = [
        'company.name' => 'Sava Trade d.o.o.',
        'company.address' => 'Bulevar oslobođenja 12, 21000 Novi Sad, Serbia',
        'company.tax_id' => '109876543',
        'company.registration_number' => '21345678',
        'company.bank_account' => '160-0000000123456-78',
        'company.email' => 'office@savatrade.example',
        'company.phone' => '+381 21 555 0100',

        'invoice.prefix' => 'INV',
        'invoice.vat_rate' => 20,
        'invoice.payment_days' => 15,

        'orders.waiting_hours' => 48,

        'monitor.backlog_threshold' => 10,
        'monitor.urgent_threshold' => 25,
        'monitor.max_age_days' => 7,

        'notifications.customer_emails' => true,
    ];

    private const CACHE_KEY = 'app.settings';

    /** @return array<string, mixed> */
    public static function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => rescue(
            fn () => Setting::query()->pluck('value', 'key')->all(),
            [],
            report: false,
        ));

        return array_replace(self::DEFAULTS, $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function int(string $key): int
    {
        return (int) self::get($key);
    }

    public static function bool(string $key): bool
    {
        return filter_var(self::get($key), FILTER_VALIDATE_BOOLEAN);
    }

    /** @param  array<string, mixed>  $values */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

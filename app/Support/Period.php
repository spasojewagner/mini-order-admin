<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Reporting periods for the dashboard filter, with the period before for comparisons. */
final class Period
{
    public const OPTIONS = [
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
        'last_month' => 'Last month',
        '90d' => 'Last 90 days',
        'year' => 'This year',
    ];

    public function __construct(
        public readonly string $key,
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly Carbon $previousFrom,
        public readonly Carbon $previousTo,
    ) {}

    public static function fromKey(?string $key): self
    {
        $key = array_key_exists((string) $key, self::OPTIONS) ? $key : '30d';
        $now = now();

        [$from, $to] = match ($key) {
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };

        // The same length of time right before, e.g. "this month so far" vs the same days last month.
        [$previousFrom, $previousTo] = match ($key) {
            'month' => [$from->copy()->subMonthNoOverflow(), $to->copy()->subMonthNoOverflow()],
            'last_month' => [$from->copy()->subMonthNoOverflow()->startOfMonth(), $from->copy()->subMonthNoOverflow()->endOfMonth()],
            'year' => [$from->copy()->subYear(), $to->copy()->subYear()],
            default => [$from->copy()->subDays(self::daysBetween($from, $to)), $from->copy()->subSecond()],
        };

        return new self($key, $from, $to, $previousFrom, $previousTo);
    }

    public function label(): string
    {
        return self::OPTIONS[$this->key];
    }

    public function days(): int
    {
        return self::daysBetween($this->from, $this->to);
    }

    /** Calendar days covered, counting both ends (Carbon 3 returns fractional diffs). */
    private static function daysBetween(Carbon $from, Carbon $to): int
    {
        return (int) round($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay())) + 1;
    }
}

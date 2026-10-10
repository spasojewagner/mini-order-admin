<?php

namespace App\Enums;

/**
 * The life of an order. Every status change goes through
 * App\Services\OrderWorkflow, which checks it against canTransitionTo().
 */
enum OrderStatus: string
{
    case Draft = 'draft';
    case New = 'new';
    case Confirmed = 'confirmed';
    case Picking = 'in_progress';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::New, self::Confirmed, self::Cancelled],
            self::New => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Picking, self::Cancelled],
            self::Picking => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedNext(), true);
    }

    /** Stock is reserved (taken off the shelf) from confirmation until the order leaves or is cancelled. */
    public function holdsStock(): bool
    {
        return in_array($this, [self::Confirmed, self::Picking, self::Shipped, self::Delivered], true);
    }

    /** Lines can be changed only before the order is confirmed. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::New], true);
    }

    /** Waiting for someone in the company to act. */
    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Confirmed, self::Picking], true);
    }

    /** Counts as revenue (confirmed and later, not cancelled). */
    public function isRevenue(): bool
    {
        return $this->holdsStock();
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::New => 'New',
            self::Confirmed => 'Confirmed',
            self::Picking => 'Picking',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Verb for the button that moves an order into this status. */
    public function actionLabel(): string
    {
        return match ($this) {
            self::Draft => 'Back to draft',
            self::New => 'Submit',
            self::Confirmed => 'Confirm',
            self::Picking => 'Start picking',
            self::Shipped => 'Mark shipped',
            self::Delivered => 'Mark delivered',
            self::Cancelled => 'Cancel order',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::New => 'info',
            self::Confirmed => 'primary',
            self::Picking => 'warning',
            self::Shipped => 'success',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::New => 'heroicon-o-inbox-arrow-down',
            self::Confirmed => 'heroicon-o-check-circle',
            self::Picking => 'heroicon-o-archive-box',
            self::Shipped => 'heroicon-o-truck',
            self::Delivered => 'heroicon-o-home-modern',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }

    /** Column on orders that records when the order entered this status. */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Confirmed => 'confirmed_at',
            self::Picking => 'picking_at',
            self::Shipped => 'shipped_at',
            self::Delivered => 'delivered_at',
            self::Cancelled => 'cancelled_at',
            default => null,
        };
    }

    /** @return array<string, string> value => label, for selects and filters */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }

    /** @return list<string> */
    public static function revenueValues(): array
    {
        return array_values(array_map(fn (self $s) => $s->value, array_filter(self::cases(), fn (self $s) => $s->isRevenue())));
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return array_values(array_map(fn (self $s) => $s->value, array_filter(self::cases(), fn (self $s) => $s->isOpen())));
    }
}

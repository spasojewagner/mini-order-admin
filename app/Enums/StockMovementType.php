<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case Receipt = 'receipt';
    case Sale = 'sale';
    case Cancellation = 'cancellation';
    case Adjustment = 'adjustment';
    case Sync = 'sync';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening balance',
            self::Receipt => 'Goods received',
            self::Sale => 'Order confirmed',
            self::Cancellation => 'Order cancelled',
            self::Adjustment => 'Stock correction',
            self::Sync => 'ERP sync',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Opening, self::Receipt => 'success',
            self::Sale => 'info',
            self::Cancellation => 'warning',
            self::Adjustment => 'danger',
            self::Sync => 'gray',
        };
    }

    /** Types a person can record by hand from the products screen. */
    public static function manualOptions(): array
    {
        return [
            self::Receipt->value => self::Receipt->label(),
            self::Adjustment->value => self::Adjustment->label(),
        ];
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->label()])->all();
    }
}

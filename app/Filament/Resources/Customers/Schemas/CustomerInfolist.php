<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name')->label(fn (Customer $record) => $record->company_name ? 'Contact person' : 'Name'),
                    TextEntry::make('tax_id')->label('Tax ID (PIB)')->placeholder('—'),
                    TextEntry::make('email')->placeholder('—')->copyable(),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('address')->label('Delivery address')->placeholder('—')->columnSpan(2),
                    TextEntry::make('revenue')->label('Revenue, last 12 months')
                        ->state(fn (Customer $record) => Money::short($record->orders()
                            ->whereIn('status', OrderStatus::revenueValues())
                            ->where('confirmed_at', '>=', now()->subYear())
                            ->sum('total_amount'))),
                    TextEntry::make('user.email')->label('Portal account')->placeholder('none'),
                ]),
        ]);
    }
}

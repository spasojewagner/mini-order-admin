<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Only the customer and the note are edited here. Lines are managed below the
 * order, and the status changes only through the status buttons.
 */
class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Customer $record) => $record->company_name
                            ? "{$record->company_name} ({$record->name})"
                            : $record->name)
                        ->searchable(['name', 'company_name', 'tax_id', 'email'])
                        ->preload()
                        ->required(),
                    Textarea::make('note')
                        ->label('Note')
                        ->placeholder('Delivery instructions, customer reference…')
                        ->maxLength(1000)
                        ->rows(3),
                ]),
        ]);
    }
}

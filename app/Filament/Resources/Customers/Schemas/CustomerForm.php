<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tip')
                    ->options([
                        'individual' => 'Fizičko lice',
                        'company' => 'Firma',
                    ])
                    ->default('individual')
                    ->required(),

                TextInput::make('name')
                    ->label('Ime')
                    ->required()
                    ->maxLength(255),

                TextInput::make('company_name')
                    ->label('Naziv firme')
                    ->maxLength(255),

                TextInput::make('tax_id')
                    ->label('PIB')
                    ->maxLength(50),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Telefon')
                    ->maxLength(50),

                Textarea::make('address')
                    ->label('Adresa')
                    ->maxLength(255),
            ]);
    }
}
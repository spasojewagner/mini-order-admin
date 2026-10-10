<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ToggleButtons::make('type')
                        ->options(['company' => 'Company', 'individual' => 'Individual'])
                        ->icons(['company' => 'heroicon-o-building-office-2', 'individual' => 'heroicon-o-user'])
                        ->default('company')
                        ->inline()
                        ->live()
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('company_name')->label('Company name')->maxLength(255)
                        ->visible(fn (Get $get) => $get('type') === 'company')
                        ->required(fn (Get $get) => $get('type') === 'company'),
                    TextInput::make('tax_id')->label('Tax ID (PIB)')->length(9)->numeric()
                        ->visible(fn (Get $get) => $get('type') === 'company'),
                    TextInput::make('name')->label(fn (Get $get) => $get('type') === 'company' ? 'Contact person' : 'Name')
                        ->required()->maxLength(255),
                    TextInput::make('email')->email()->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('phone')->tel()->maxLength(50),
                    TextInput::make('address')->label('Delivery address')->maxLength(255)->columnSpanFull(),
                ]),
        ]);
    }
}

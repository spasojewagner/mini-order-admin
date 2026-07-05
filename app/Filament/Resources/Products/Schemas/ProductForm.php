<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Naziv')
                    ->required()
                    ->maxLength(255),

                TextInput::make('sku')
                    ->label('SKU')
                    ->maxLength(100),

                TextInput::make('price')
                    ->label('Cena')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),

                TextInput::make('stock_quantity')
                    ->label('Lager')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),

                Toggle::make('is_active')
                    ->label('Aktivan')
                    ->default(true),
            ]);
    }
}
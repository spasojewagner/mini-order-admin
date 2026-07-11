<?php

namespace App\Filament\Resources\Conversations\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Kupac')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('subject')
                    ->label('Tema')
                    ->maxLength(255),

                Select::make('channel')
                    ->label('Kanal')
                    ->options([
                        'web' => 'Web',
                        'viber' => 'Viber',
                        'whatsapp' => 'WhatsApp',
                        'instagram' => 'Instagram',
                        'phone' => 'Telefon',
                    ])
                    ->default('web')
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'waiting' => 'Waiting',
                        'closed' => 'Closed',
                    ])
                    ->default('open')
                    ->required(),
            ]);
    }
}
<?php

namespace App\Filament\Resources\Conversations\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConversationForm
{
    public const CHANNELS = [
        'web' => 'Web',
        'email' => 'Email',
        'viber' => 'Viber',
        'whatsapp' => 'WhatsApp',
        'phone' => 'Phone',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('customer_id')->label('Customer')
                        ->relationship('customer', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Customer $record) => $record->displayName())
                        ->searchable(['name', 'company_name'])
                        ->preload(),
                    TextInput::make('subject')->maxLength(255),
                    Select::make('channel')->options(self::CHANNELS)->default('web')->required(),
                    ToggleButtons::make('status')
                        ->options(['open' => 'Open', 'waiting' => 'Waiting for customer', 'closed' => 'Closed'])
                        ->colors(['open' => 'success', 'waiting' => 'warning', 'closed' => 'gray'])
                        ->default('open')->inline()->required(),
                ]),
        ]);
    }
}

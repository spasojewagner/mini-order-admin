<?php

namespace App\Filament\Resources\SmsMessages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SmsMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                Textarea::make('reason')
                    ->columnSpanFull(),
                TextInput::make('approved_by')
                    ->numeric(),
                DateTimePicker::make('approved_at'),
                DateTimePicker::make('sent_at'),
            ]);
    }
}

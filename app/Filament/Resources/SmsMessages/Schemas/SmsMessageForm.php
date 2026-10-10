<?php

namespace App\Filament\Resources\SmsMessages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Correct a pending draft before approving it. */
class SmsMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->schema([
                TextInput::make('phone')->tel()->required(),
                Textarea::make('body')->label('Text')->required()->maxLength(300)->rows(4),
            ]),
        ]);
    }
}

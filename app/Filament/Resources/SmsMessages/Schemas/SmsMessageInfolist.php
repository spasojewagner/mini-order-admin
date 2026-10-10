<?php

namespace App\Filament\Resources\SmsMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SmsMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->columnSpanFull()->schema([
                TextEntry::make('status')->badge()
                    ->color(fn (string $state) => ['pending' => 'warning', 'sent' => 'success', 'rejected' => 'danger'][$state] ?? 'gray'),
                TextEntry::make('phone')->label('To'),
                TextEntry::make('created_at')->label('Drafted')->dateTime('d.m.Y H:i'),
                TextEntry::make('body')->label('Text')->columnSpanFull(),
                TextEntry::make('reason')->label('Why the agent wants an SMS')->placeholder('—')->columnSpanFull(),
                TextEntry::make('approvedBy.name')->label('Decided by')->placeholder('—'),
                TextEntry::make('approved_at')->label('Decided')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextEntry::make('sent_at')->label('Sent')->dateTime('d.m.Y H:i')->placeholder('—'),
            ]),
        ]);
    }
}

<?php

namespace App\Filament\Resources\SmsMessages;

use App\Filament\Resources\SmsMessages\Pages\CreateSmsMessage;
use App\Filament\Resources\SmsMessages\Pages\EditSmsMessage;
use App\Filament\Resources\SmsMessages\Pages\ListSmsMessages;
use App\Filament\Resources\SmsMessages\Pages\ViewSmsMessage;
use App\Filament\Resources\SmsMessages\Schemas\SmsMessageForm;
use App\Filament\Resources\SmsMessages\Schemas\SmsMessageInfolist;
use App\Filament\Resources\SmsMessages\Tables\SmsMessagesTable;
use App\Models\SmsMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SmsMessageResource extends Resource
{
    protected static ?string $model = SmsMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'body';

    public static function form(Schema $schema): Schema
    {
        return SmsMessageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SmsMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmsMessagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsMessages::route('/'),
            'create' => CreateSmsMessage::route('/create'),
            'view' => ViewSmsMessage::route('/{record}'),
            'edit' => EditSmsMessage::route('/{record}/edit'),
        ];
    }
}

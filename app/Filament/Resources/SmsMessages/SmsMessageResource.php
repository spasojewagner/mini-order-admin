<?php

namespace App\Filament\Resources\SmsMessages;

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
use UnitEnum;

class SmsMessageResource extends Resource
{
    protected static ?string $model = SmsMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = 'AI assistant';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'SMS approvals';

    protected static ?string $modelLabel = 'SMS draft';

    protected static ?string $recordTitleAttribute = 'body';

    public static function getNavigationBadge(): ?string
    {
        $pending = SmsMessage::query()->pending()->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

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

    public static function getPages(): array
    {
        return [
            'index' => ListSmsMessages::route('/'),
            'view' => ViewSmsMessage::route('/{record}'),
            'edit' => EditSmsMessage::route('/{record}/edit'),
        ];
    }
}

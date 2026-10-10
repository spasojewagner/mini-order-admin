<?php

namespace App\Filament\Resources\Users;

use App\Enums\Role;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Customer;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Users & roles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                Select::make('role')->options(Role::options())->required()->live()
                    ->helperText('Sales: customers, orders, inbox. Warehouse: stock, picking, shipping. Admin: everything.'),
                Select::make('customer_id')->label('Customer (for portal accounts)')
                    ->options(fn () => Customer::query()->orderBy('company_name')->get()->mapWithKeys(fn (Customer $c) => [$c->id => $c->displayName()]))
                    ->searchable()
                    ->visible(fn (Get $get) => $get('role') === Role::Customer->value)
                    ->required(fn (Get $get) => $get('role') === Role::Customer->value),
                TextInput::make('password')->password()->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->minLength(8)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave empty to keep the current password.' : null),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('customer')->withCount('tokens'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium')->description(fn (User $record) => $record->email),
                TextColumn::make('role')->badge()
                    ->formatStateUsing(fn (?string $state) => Role::tryFrom((string) $state)?->label() ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'admin' => 'danger', 'sales' => 'primary', 'warehouse' => 'warning', default => 'gray',
                    }),
                TextColumn::make('customer.company_name')->label('Customer')
                    ->formatStateUsing(fn (User $record) => $record->customer?->displayName())->placeholder('—'),
                TextColumn::make('tokens_count')->label('API tokens')->alignEnd()->color('gray'),
                TextColumn::make('created_at')->label('Since')->date('d.m.Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->options(Role::options()),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}

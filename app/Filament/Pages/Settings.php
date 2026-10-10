<?php

namespace App\Filament\Pages;

use App\Support\Settings as AppSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use UnitEnum;

/** Company details for invoices and the thresholds the AI agent works with. */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mount(): void
    {
        $this->form->fill(Arr::undot(AppSettings::all()));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(2)
            ->components([
                Section::make('Company')
                    ->description('Printed on every invoice.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('company.name')->label('Legal name')->required()->maxLength(120),
                        TextInput::make('company.address')->label('Address')->required()->maxLength(200),
                        TextInput::make('company.tax_id')->label('Tax ID (PIB)')->required()->maxLength(20),
                        TextInput::make('company.registration_number')->label('Registration number')->maxLength(20),
                        TextInput::make('company.bank_account')->label('Bank account')->required()->maxLength(40),
                        TextInput::make('company.email')->label('Email')->email(),
                        TextInput::make('company.phone')->label('Phone'),
                    ]),
                Section::make('Invoices and orders')
                    ->columns(2)
                    ->schema([
                        TextInput::make('invoice.prefix')->label('Invoice prefix')->required()->maxLength(10)
                            ->helperText('INV gives INV-2026-00001.'),
                        TextInput::make('invoice.vat_rate')->label('VAT %')->integer()->minValue(0)->maxValue(100)->required(),
                        TextInput::make('invoice.payment_days')->label('Payment terms (days)')->integer()->minValue(0)->required(),
                        TextInput::make('orders.waiting_hours')->label('Order is overdue after (hours)')->integer()->minValue(1)->required()
                            ->helperText('Open orders in one status for longer show up under "Needs attention".'),
                        Toggle::make('notifications.customer_emails')->label('Email customers when their order is confirmed, shipped, delivered or cancelled')
                            ->columnSpanFull(),
                    ]),
                Section::make('Order monitor (AI agent)')
                    ->description('The agent reads these every time it runs.')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('monitor.backlog_threshold')->label('Email the manager above (open orders)')->integer()->minValue(1)->required(),
                        TextInput::make('monitor.urgent_threshold')->label('Draft an SMS above (open orders)')->integer()->minValue(1)->required(),
                        TextInput::make('monitor.max_age_days')->label('…or when an order is open longer than (days)')->integer()->minValue(1)->required(),
                    ]),
            ]);
    }

    public function save(): void
    {
        AppSettings::put(Arr::dot($this->form->getState()));

        Notification::make()->title('Settings saved')->success()->send();
    }
}

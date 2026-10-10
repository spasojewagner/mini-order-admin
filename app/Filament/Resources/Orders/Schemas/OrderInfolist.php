<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Order')
                            ->columns(4)
                            ->schema([
                                TextEntry::make('status')->badge()
                                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                                    ->color(fn (OrderStatus $state) => $state->color())
                                    ->icon(fn (OrderStatus $state) => $state->icon()),
                                TextEntry::make('created_at')->label('Placed')->dateTime('d.m.Y H:i')
                                    ->helperText(fn (Order $record) => $record->created_at->diffForHumans()),
                                TextEntry::make('source')->label('Channel')->badge()->color('gray')
                                    ->formatStateUsing(fn (string $state) => match ($state) {
                                        'portal' => 'Customer portal',
                                        'mcp' => 'AI client (MCP)',
                                        default => 'Back office',
                                    }),
                                TextEntry::make('invoice_number')->label('Invoice')->placeholder('after shipping')
                                    ->url(fn (Order $record) => $record->invoice_number ? route('invoices.show', $record) : null, shouldOpenInNewTab: true)
                                    ->color('primary')->icon(fn (Order $record) => $record->invoice_number ? 'heroicon-m-document-text' : null),
                                TextEntry::make('note')->placeholder('No note')->columnSpanFull(),
                            ]),
                        Section::make('Amount')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('net')->label('Net')
                                    ->state(fn (Order $record) => Money::format(Money::totals($record->total_amount)['net'])),
                                TextEntry::make('vat')->label(fn () => 'VAT '.Money::totals(0)['vat_rate'].'%')
                                    ->state(fn (Order $record) => Money::format(Money::totals($record->total_amount)['vat'])),
                                TextEntry::make('gross')->label('Total')
                                    ->state(fn (Order $record) => Money::format(Money::totals($record->total_amount)['gross']))
                                    ->weight(FontWeight::Bold)->size('lg'),
                            ]),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Customer')
                            ->schema([
                                TextEntry::make('customer.company_name')->hiddenLabel()
                                    ->state(fn (Order $record) => $record->customer?->displayName())
                                    ->weight(FontWeight::SemiBold)
                                    ->url(fn (Order $record) => $record->customer ? CustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                                TextEntry::make('customer.name')->label('Contact')
                                    ->visible(fn (Order $record) => (bool) $record->customer?->company_name),
                                TextEntry::make('customer.address')->label('Delivery address')->placeholder('—'),
                                TextEntry::make('customer.phone')->label('Phone')->placeholder('—'),
                                TextEntry::make('customer.email')->label('Email')->placeholder('—')->copyable(),
                                TextEntry::make('customer.tax_id')->label('Tax ID (PIB)')->placeholder('—'),
                            ]),
                        Section::make('Timeline')
                            ->collapsible()
                            ->schema([
                                RepeatableEntry::make('events')
                                    ->hiddenLabel()
                                    ->contained(false)
                                    ->schema([
                                        TextEntry::make('summary')->hiddenLabel()
                                            ->state(fn (OrderEvent $record) => $record->summary())
                                            ->weight(FontWeight::Medium)
                                            ->color(fn (OrderEvent $record) => $record->type === 'email_failed' ? 'danger' : null)
                                            ->helperText(fn (OrderEvent $record) => trim($record->created_at->format('d.m.Y H:i').' '.($record->description ? '· '.$record->description : ''))),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}

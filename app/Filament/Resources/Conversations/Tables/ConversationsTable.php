<?php

namespace App\Filament\Resources\Conversations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Kupac')
                    ->searchable()
                    ->placeholder('Nepoznat'),

                TextColumn::make('subject')
                    ->label('Tema')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'waiting' => 'warning',
                        'closed' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Kreirano')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Open',
                        'waiting' => 'Waiting',
                        'closed' => 'Closed',
                    ]),

                SelectFilter::make('channel')
                    ->label('Kanal')
                    ->options([
                        'web' => 'Web',
                        'viber' => 'Viber',
                        'whatsapp' => 'WhatsApp',
                        'instagram' => 'Instagram',
                        'phone' => 'Telefon',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
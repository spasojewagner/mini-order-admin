<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tip')
                    ->formatStateUsing(fn ($state) => $state === 'company' ? 'Firma' : 'Fizičko lice')
                    ->badge(),

                TextColumn::make('name')
                    ->label('Ime')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Kreiran')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tip')
                    ->options([
                        'individual' => 'Fizičko lice',
                        'company' => 'Firma',
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
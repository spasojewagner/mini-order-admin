<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use App\Services\OrderConfirmationService;
use App\Models\Order;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Kupac')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'new' => 'info',
                        'confirmed' => 'success',
                        'in_progress' => 'warning',
                        'shipped' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_amount')
                    ->label('Ukupno')
                    ->money('RSD')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'new' => 'New',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In progress',
                        'shipped' => 'Shipped',
                        'cancelled' => 'Cancelled',
                    ]),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Od'),
                        DatePicker::make('until')->label('Do'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Potvrdi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Potvrdi porudžbinu')
                    ->modalDescription('Ovo će skinuti lager i promeniti status u confirmed.')
                    ->visible(fn(Order $record): bool => in_array($record->status, ['draft', 'new']))
                    ->action(function (Order $record) {
                        try {
                            app(OrderConfirmationService::class)->confirm($record);

                            Notification::make()
                                ->title('Porudžbina je potvrđena i lager je ažuriran.')
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Greška pri potvrdi')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
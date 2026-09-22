<?php

namespace App\Filament\Resources\SmsMessages\Tables;

use App\Models\SmsMessage;
use App\Services\SmsApprovalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Throwable;

class SmsMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'sent' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Primalac')
                    ->searchable(),

                TextColumn::make('body')
                    ->label('Poruka')
                    ->limit(60)
                    ->tooltip(fn (SmsMessage $record): string => $record->body)
                    ->searchable(),

                TextColumn::make('approvedBy.name')
                    ->label('Odlučio')
                    ->placeholder('—'),

                TextColumn::make('approved_at')
                    ->label('Odlučeno')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('sent_at')
                    ->label('Poslato')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kreirano')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Čeka odobrenje',
                        'sent' => 'Poslato',
                        'rejected' => 'Odbijeno',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Odobri i pošalji')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Odobri poruku')
                    ->modalDescription('Poruka će biti stvarno poslata primaocu.')
                    ->visible(fn (SmsMessage $record): bool => $record->status === 'pending')
                    ->action(function (SmsMessage $record) {
                        try {
                            $message = app(SmsApprovalService::class)
                                ->approve($record, auth()->user());

                            Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Greška pri odobravanju')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();
                        } catch (Throwable $e) {
                            // Kanal je pukao - poruka ostaje pending pa moze ponovo.
                            Notification::make()
                                ->title('Slanje nije uspelo')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Odbij')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Odbij poruku')
                    ->modalDescription('Poruka neće biti poslata.')
                    ->visible(fn (SmsMessage $record): bool => $record->status === 'pending')
                    ->action(function (SmsMessage $record) {
                        try {
                            $message = app(SmsApprovalService::class)
                                ->reject($record, auth()->user());

                            Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Greška')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();
                        }
                    }),

                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\SmsMessages\Pages;

use App\Filament\Resources\SmsMessages\SmsMessageResource;
use App\Models\SmsMessage;
use App\Services\SmsApprovalService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

class ViewSmsMessage extends ViewRecord
{
    protected static string $resource = SmsMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
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

                        Notification::make()->title($message)->success()->send();
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Greška pri odobravanju')
                            ->body(collect($e->errors())->flatten()->implode(' '))
                            ->danger()
                            ->send();
                    } catch (Throwable $e) {
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
                ->visible(fn (SmsMessage $record): bool => $record->status === 'pending')
                ->action(function (SmsMessage $record) {
                    try {
                        $message = app(SmsApprovalService::class)
                            ->reject($record, auth()->user());

                        Notification::make()->title($message)->success()->send();
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title('Greška')
                            ->body(collect($e->errors())->flatten()->implode(' '))
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}

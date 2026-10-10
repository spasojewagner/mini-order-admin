<?php

namespace App\Filament\Resources\SmsMessages;

use App\Models\SmsMessage;
use App\Services\SmsApprovalService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Approve or reject an SMS the AI agent drafted. Nothing is sent without approval. */
class SmsApprovalActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve and send')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Approve this SMS?')
            ->modalDescription(fn (SmsMessage $record) => "It will really be sent to {$record->phone}.")
            ->visible(fn (SmsMessage $record) => $record->status === 'pending' && auth()->user()?->isAdmin())
            ->action(function (SmsMessage $record) {
                try {
                    $message = app(SmsApprovalService::class)->approve($record, auth()->user());
                    Notification::make()->title($message)->success()->send();
                } catch (ValidationException $e) {
                    Notification::make()->title('Not possible')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                } catch (Throwable $e) {
                    // The channel failed: the message stays pending, so it can be tried again.
                    Notification::make()->title('Sending failed')->body($e->getMessage())->danger()->send();
                }
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Reject this SMS?')
            ->modalDescription('It will not be sent.')
            ->visible(fn (SmsMessage $record) => $record->status === 'pending' && auth()->user()?->isAdmin())
            ->action(function (SmsMessage $record) {
                try {
                    Notification::make()->title(app(SmsApprovalService::class)->reject($record, auth()->user()))->success()->send();
                } catch (ValidationException $e) {
                    Notification::make()->title('Not possible')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                }
            });
    }
}

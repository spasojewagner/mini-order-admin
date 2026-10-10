<?php

namespace App\Filament\Resources\AgentRuns\Pages;

use App\Filament\Resources\AgentRuns\AgentRunResource;
use App\Models\AgentRun;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListAgentRuns extends ListRecords
{
    protected static string $resource = AgentRunResource::class;

    public function getSubheading(): ?string
    {
        return 'The order monitor runs every hour. It emails the manager when the backlog is too big and drafts an SMS for approval in an emergency.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runNow')
                ->label('Run order monitor now')
                ->icon('heroicon-o-play')
                ->visible(fn () => auth()->user()?->isAdmin())
                ->requiresConfirmation()
                ->modalDescription('The agent reads the open orders and may send the alert email. It takes a few seconds.')
                ->action(function () {
                    $exit = Artisan::call('app:monitor-orders', ['--manual' => true, '--user' => auth()->id()]);
                    $run = AgentRun::query()->latest('id')->first();

                    Notification::make()
                        ->title($exit === 0 ? 'Agent finished' : 'Agent failed')
                        ->body(str($run?->response ?? $run?->error ?? '')->limit(300))
                        ->color($exit === 0 ? 'success' : 'danger')
                        ->send();
                }),
        ];
    }
}

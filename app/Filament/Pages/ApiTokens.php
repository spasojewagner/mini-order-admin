<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Laravel\Sanctum\PersonalAccessToken;

/** Personal tokens for AI clients that connect to the MCP server as this user. */
class ApiTokens extends Page
{
    protected string $view = 'filament.pages.api-tokens';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'API tokens for AI clients';

    /** Shown once, right after it is created. */
    public ?string $newToken = null;

    public function getTokens()
    {
        return auth()->user()->tokens()->latest()->get();
    }

    public function endpoint(): string
    {
        return rtrim(config('app.url'), '/').'/mcp/order-admin';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Create token')
                ->icon('heroicon-o-plus')
                ->schema([
                    TextInput::make('name')->label('What is it for?')->placeholder('Claude Desktop on my laptop')->required()->maxLength(100),
                ])
                ->action(function (array $data) {
                    $this->newToken = auth()->user()->createToken($data['name'])->plainTextToken;
                }),
        ];
    }

    public function revoke(int $id): void
    {
        $token = PersonalAccessToken::query()
            ->where('tokenable_type', auth()->user()::class)
            ->where('tokenable_id', auth()->id())
            ->findOrFail($id);
        $token->delete();

        Notification::make()->title("Token \"{$token->name}\" revoked")->success()->send();
    }
}

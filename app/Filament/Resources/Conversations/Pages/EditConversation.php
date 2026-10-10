<?php

namespace App\Filament\Resources\Conversations\Pages;

use App\Filament\Resources\Conversations\ConversationResource;
use App\Models\Conversation;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditConversation extends EditRecord
{
    protected static string $resource = ConversationResource::class;

    public function getTitle(): string|Htmlable
    {
        /** @var Conversation $conversation */
        $conversation = $this->getRecord();

        return ($conversation->customer?->displayName() ?? 'Unknown customer').($conversation->subject ? " · {$conversation->subject}" : '');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

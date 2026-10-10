<?php

namespace App\Filament\Resources\Conversations\RelationManagers;

use App\Ai\Agents\ReplyDraftAgent;
use App\Models\Conversation;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            ToggleButtons::make('sender')
                ->options(['admin' => 'Us', 'customer' => 'Customer'])
                ->default('admin')->inline()->required(),
            Textarea::make('body')->label('Message')->required()->rows(5)->maxLength(2000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('sender')->label('From')->badge()
                    ->formatStateUsing(fn (string $state, Message $record) => $state === 'customer' ? 'Customer' : ($record->user?->name ?? 'Us'))
                    ->color(fn (string $state) => $state === 'admin' ? 'primary' : 'gray'),
                TextColumn::make('body')->label('Message')->wrap(),
                IconColumn::make('ai_generated')->label('AI draft')->boolean()
                    ->trueIcon('heroicon-o-sparkles')->falseIcon('')->trueColor('warning')
                    ->tooltip(fn (Message $record) => $record->ai_generated ? 'Written by AI: check it before sending' : null),
                TextColumn::make('created_at')->label('Time')->dateTime('d.m.Y H:i'),
            ])
            ->headerActions([
                Action::make('draftReply')
                    ->label('Draft reply with AI')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->action(function () {
                        /** @var Conversation $conversation */
                        $conversation = $this->getOwnerRecord();

                        try {
                            $draft = trim((string) (new ReplyDraftAgent)->prompt(ReplyDraftAgent::promptFor($conversation)));
                        } catch (Throwable $e) {
                            report($e);
                            Notification::make()->title('The AI draft failed')
                                ->body(blank(config('ai.providers.groq.key')) ? 'GROQ_API_KEY is not set in .env.' : $e->getMessage())
                                ->danger()->send();

                            return;
                        }

                        $conversation->messages()->create([
                            'sender' => 'admin',
                            'user_id' => auth()->id(),
                            'body' => $draft,
                            'ai_generated' => true,
                        ]);
                        $conversation->touch();

                        Notification::make()->title('Draft added')
                            ->body('It uses this customer\'s orders. Read and edit it before you send it.')
                            ->success()->send();
                    }),
                CreateAction::make()->label('Add message')
                    ->mutateDataUsing(fn (array $data) => [...$data, 'user_id' => $data['sender'] === 'admin' ? auth()->id() : null])
                    ->after(fn () => $this->getOwnerRecord()->touch()),
            ])
            ->recordActions([
                EditAction::make()->mutateDataUsing(fn (array $data) => [...$data, 'ai_generated' => false]),
                DeleteAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Conversations\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sender')
                    ->label('Pošiljalac')
                    ->options([
                        'customer' => 'Kupac',
                        'admin' => 'Admin',
                    ])
                    ->default('admin')
                    ->required(),

                Textarea::make('body')
                    ->label('Poruka')
                    ->required()
                    ->rows(3)
                    ->maxLength(2000),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('sender')
                    ->label('Pošiljalac')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'admin' ? 'info' : 'gray'),

                TextColumn::make('body')
                    ->label('Poruka')
                    ->wrap()
                    ->searchable(),

                IconColumn::make('ai_generated')
                    ->label('AI draft')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Vreme')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                // Dugme: generiši AI draft (statički za sad)
                Action::make('generateDraft')
                    ->label('Generate draft reply')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->action(function () {
                        // Statički draft odgovor (kasnije se ovde poziva pravi AI API)
                        $draft = 'Hvala na poruci. Proverićemo i javiti Vam uskoro.';

                        $this->getOwnerRecord()->messages()->create([
                            'sender' => 'admin',
                            'body' => $draft,
                            'ai_generated' => true,
                        ]);

                        Notification::make()
                            ->title('Draft odgovor je generisan')
                            ->body('Predlog je dodat kao poruka. Pregledajte i izmenite pre slanja.')
                            ->success()
                            ->send();
                    }),

                CreateAction::make()
                    ->label('Nova poruka'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
<?php

namespace App\Filament\Resources\Conversations\Tables;

use App\Filament\Resources\Conversations\Schemas\ConversationForm;
use App\Models\Conversation;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'latestMessage'])->withCount('messages'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('customer.company_name')->label('Customer')
                    ->formatStateUsing(fn (Conversation $record) => $record->customer?->displayName() ?? 'Unknown')
                    ->default('Unknown')
                    ->searchable(['company_name', 'name'])
                    ->weight('medium'),
                TextColumn::make('subject')->searchable()->placeholder('—')
                    ->description(fn (Conversation $record) => $record->latestMessage
                        ? ($record->latestMessage->sender === 'customer' ? 'Customer: ' : 'Us: ').Str::limit($record->latestMessage->body, 70)
                        : null),
                TextColumn::make('channel')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => ConversationForm::CHANNELS[$state] ?? ucfirst($state)),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state) => ['open' => 'Open', 'waiting' => 'Waiting', 'closed' => 'Closed'][$state] ?? $state)
                    ->color(fn (string $state) => ['open' => 'success', 'waiting' => 'warning'][$state] ?? 'gray'),
                TextColumn::make('messages_count')->label('Messages')->alignEnd()->color('gray'),
                TextColumn::make('updated_at')->label('Last activity')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['open' => 'Open', 'waiting' => 'Waiting', 'closed' => 'Closed'])->default('open'),
                SelectFilter::make('channel')->options(ConversationForm::CHANNELS),
            ])
            ->recordActions([
                EditAction::make()->label('Open'),
            ]);
    }
}

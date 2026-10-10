<?php

namespace App\Filament\Resources\SmsMessages\Tables;

use App\Filament\Resources\SmsMessages\SmsApprovalActions;
use App\Models\SmsMessage;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SmsMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('status')->badge()->sortable()
                    ->formatStateUsing(fn (string $state) => ['pending' => 'Waiting for approval', 'sent' => 'Sent', 'rejected' => 'Rejected'][$state] ?? $state)
                    ->color(fn (string $state) => ['pending' => 'warning', 'sent' => 'success', 'rejected' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('body')->label('Text')->limit(70)->tooltip(fn (SmsMessage $record) => $record->body)->searchable()
                    ->description(fn (SmsMessage $record) => $record->reason ? 'Reason: '.str($record->reason)->limit(80) : null),
                TextColumn::make('phone')->label('To'),
                TextColumn::make('approvedBy.name')->label('Decided by')->placeholder('—'),
                TextColumn::make('created_at')->label('Drafted')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Waiting for approval', 'sent' => 'Sent', 'rejected' => 'Rejected']),
            ])
            ->recordActions([
                SmsApprovalActions::approve(),
                SmsApprovalActions::reject(),
                ViewAction::make()->iconButton(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\AgentRuns;

use App\Filament\Resources\AgentRuns\Pages\ListAgentRuns;
use App\Filament\Resources\AgentRuns\Pages\ViewAgentRun;
use App\Models\AgentRun;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** What the AI agent did on each run: trigger, tool calls with their results, and its answer. */
class AgentRunResource extends Resource
{
    protected static ?string $model = AgentRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'AI assistant';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Agent runs';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('agent'),
                TextEntry::make('trigger')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state, AgentRun $record) => $state === 'manual' ? 'Manual'.($record->user ? " · {$record->user->name}" : '') : 'Scheduled'),
                TextEntry::make('status')->badge()->color(fn (string $state) => ['completed' => 'success', 'failed' => 'danger'][$state] ?? 'warning'),
                TextEntry::make('duration_ms')->label('Took')->formatStateUsing(fn ($state) => $state ? number_format($state / 1000, 1).' s' : '—'),
                TextEntry::make('response')->label('Agent\'s answer')->placeholder('—')->columnSpanFull()->prose(),
                TextEntry::make('error')->color('danger')->visible(fn (AgentRun $record) => filled($record->error))->columnSpanFull(),
            ]),
            Section::make('What the tools did')->columnSpanFull()->schema([
                RepeatableEntry::make('actions')->hiddenLabel()
                    ->placeholder('No side effects: nothing was sent or drafted.')
                    ->schema([
                        TextEntry::make('tool')->badge(),
                        TextEntry::make('result')->hiddenLabel(),
                    ])->columns(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d.m.Y H:i')->description(fn (AgentRun $record) => $record->created_at->diffForHumans()),
                TextColumn::make('agent'),
                TextColumn::make('trigger')->badge()->color('gray')->formatStateUsing(fn (string $state) => $state === 'manual' ? 'Manual' : 'Scheduled'),
                TextColumn::make('status')->badge()->color(fn (string $state) => ['completed' => 'success', 'failed' => 'danger'][$state] ?? 'warning'),
                TextColumn::make('actions')->label('Actions')
                    ->state(fn (AgentRun $record) => collect($record->actions)->pluck('tool')->map(fn ($t) => str_replace('_', ' ', $t))->implode(', ') ?: 'none')
                    ->color('gray'),
                TextColumn::make('response')->label('Answer')->limit(80)->wrap()->placeholder('—'),
            ])
            ->recordActions([ViewAction::make()->iconButton()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgentRuns::route('/'),
            'view' => ViewAgentRun::route('/{record}'),
        ];
    }
}

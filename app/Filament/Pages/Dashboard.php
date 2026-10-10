<?php

namespace App\Filament\Pages;

use App\Support\Period;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Overview';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label('Period')
                ->options(Period::OPTIONS)
                ->default('30d')
                ->selectablePlaceholder(false),
        ]);
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 3];
    }
}

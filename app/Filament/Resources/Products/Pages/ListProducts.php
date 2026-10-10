<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        $low = Product::query()->active()->lowStock()->count();

        return [
            'active' => Tab::make('For sale')->modifyQueryUsing(fn (Builder $query) => $query->active()),
            'low_stock' => Tab::make('Reorder')->icon('heroicon-m-exclamation-triangle')
                ->badge($low ?: null)->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->active()->lowStock()),
            'inactive' => Tab::make('Not for sale')->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
            'all' => Tab::make('All'),
        ];
    }
}

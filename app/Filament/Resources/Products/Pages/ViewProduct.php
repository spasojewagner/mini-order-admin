<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\StockActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            StockActions::receive()->after(fn () => $this->redirect(ProductResource::getUrl('view', ['record' => $this->getRecord()]), navigate: true)),
            StockActions::correct()->after(fn () => $this->redirect(ProductResource::getUrl('view', ['record' => $this->getRecord()]), navigate: true)),
            EditAction::make()->color('gray'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\StockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $opening = (int) ($data['opening_stock'] ?? 0);
        unset($data['opening_stock']);

        $product = static::getModel()::create($data);
        app(StockService::class)->opening($product, $opening, auth()->user());

        return $product;
    }

    protected function getRedirectUrl(): string
    {
        return ProductResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}

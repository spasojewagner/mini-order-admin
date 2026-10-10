<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Support\Money;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(6)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('sku')->label('SKU')->placeholder('—'),
                    TextEntry::make('category')->placeholder('—'),
                    TextEntry::make('price')->label('Price (excl. VAT)')->formatStateUsing(fn ($state) => Money::format($state)),
                    TextEntry::make('stock_quantity')->label('In stock')->badge()->size('lg')->weight(FontWeight::Bold)
                        ->color(fn (Product $record) => $record->stock_quantity === 0 ? 'danger' : ($record->isLowStock() ? 'warning' : 'success')),
                    TextEntry::make('reorder_level')->label('Reorder at'),
                    IconEntry::make('is_active')->label('For sale')->boolean(),
                ]),
        ]);
    }
}

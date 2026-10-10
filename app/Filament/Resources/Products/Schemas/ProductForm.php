<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('sku')->label('SKU')->maxLength(100)->unique(ignoreRecord: true),
                    TextInput::make('category')->maxLength(60)
                        ->datalist(fn () => Product::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all()),
                    TextInput::make('price')->label('Price (RSD, excl. VAT)')->required()->numeric()->minValue(0)->step('0.01'),
                    TextInput::make('reorder_level')->label('Reorder at')->helperText('Shown as low stock at or below this quantity.')
                        ->required()->integer()->minValue(0)->default(10),
                    Toggle::make('is_active')->label('For sale')->default(true)
                        ->helperText('Inactive products are hidden from the portal and new orders.'),
                ]),
            Section::make('Opening stock')
                ->description('Later changes go through "Receive goods" and "Correct stock", so every change has a reason.')
                ->visibleOn('create')
                ->schema([
                    TextInput::make('opening_stock')->label('Quantity on the shelf now')->integer()->minValue(0)->default(0),
                ]),
        ]);
    }
}

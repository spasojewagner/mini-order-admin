<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Proizvod')
                    ->options(Product::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('quantity')
                    ->label('Količina')
                    ->integer()
                    ->required()
                    ->minValue(1)
                    ->default(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Proizvod')
                    ->searchable(),

                TextColumn::make('unit_price')
                    ->label('Cena')
                    ->money('RSD'),

                TextColumn::make('quantity')
                    ->label('Količina'),

                TextColumn::make('subtotal')
                    ->label('Ukupno')
                    ->money('RSD'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $product = Product::find($data['product_id']);
                        $data['product_name'] = $product->name;
                        $data['unit_price'] = $product->price;
                        $data['subtotal'] = $product->price * (int) $data['quantity'];
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $product = Product::find($data['product_id']);
                        $data['product_name'] = $product->name;
                        $data['unit_price'] = $product->price;
                        $data['subtotal'] = $product->price * (int) $data['quantity'];
                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
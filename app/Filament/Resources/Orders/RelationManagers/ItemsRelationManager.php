<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Order lines. Read-only once the order is confirmed, because stock was taken for them. */
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Lines';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        /** @var Order $order */
        $order = $this->getOwnerRecord();

        return ! $order->isEditable() || ! auth()->user()?->can('update', $order);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->label('Product')
                ->options(fn () => Product::query()->active()->orderBy('name')->get()
                    ->mapWithKeys(fn (Product $p) => [$p->id => "{$p->name} · {$p->sku} · ".Money::format($p->price)." · {$p->stock_quantity} in stock"]))
                ->searchable()
                ->required(),
            TextInput::make('quantity')
                ->label('Quantity')
                ->integer()
                ->required()
                ->minValue(1)
                ->maxValue(100000)
                ->default(1),
        ]);
    }

    public function table(Table $table): Table
    {
        $priced = function (array $data): array {
            $product = Product::findOrFail($data['product_id']);
            $data['product_name'] = $product->name;
            $data['unit_price'] = $product->price;
            $data['subtotal'] = round((float) $product->price * (int) $data['quantity'], 2);

            return $data;
        };

        return $table
            ->recordTitleAttribute('product_name')
            ->modifyQueryUsing(fn ($query) => $query->with('product'))
            ->columns([
                TextColumn::make('product_name')->label('Product')
                    ->description(fn (OrderItem $record) => $record->product?->sku),
                TextColumn::make('unit_price')->label('Unit price')->money('RSD')->alignEnd(),
                TextColumn::make('quantity')->label('Qty')->alignEnd(),
                TextColumn::make('product.stock_quantity')->label('In stock now')->alignEnd()->color('gray')
                    ->visible(fn () => $this->getOwnerRecord()->isEditable()),
                TextColumn::make('subtotal')->label('Amount')->money('RSD')->alignEnd()->weight('semibold')
                    ->summarize(Sum::make()->money('RSD')->label('Net total')),
            ])
            ->emptyStateHeading('No lines yet')
            ->emptyStateDescription('Add the products the customer ordered.')
            ->headerActions([
                CreateAction::make()->label('Add line')->mutateDataUsing($priced),
            ])
            ->recordActions([
                EditAction::make()->mutateDataUsing($priced),
                DeleteAction::make(),
            ]);
    }
}

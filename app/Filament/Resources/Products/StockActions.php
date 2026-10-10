<?php

namespace App\Filament\Resources\Products;

use App\Models\Product;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/** Stock changes from the panel. Each one is written to the product's stock history. */
class StockActions
{
    public static function receive(): Action
    {
        return Action::make('receive')
            ->label('Receive goods')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->visible(fn (Product $record) => auth()->user()?->can('adjustStock', $record))
            ->modalHeading(fn (Product $record) => "Receive {$record->name}")
            ->modalDescription(fn (Product $record) => "Now in stock: {$record->stock_quantity}.")
            ->schema([
                TextInput::make('quantity')->label('Quantity received')->integer()->minValue(1)->maxValue(1000000)->required(),
                TextInput::make('note')->label('Delivery note / supplier')->maxLength(255),
            ])
            ->action(function (Product $record, array $data) {
                app(StockService::class)->receive($record, (int) $data['quantity'], auth()->user(), $data['note'] ?? null);
                Notification::make()->title("Received {$data['quantity']} × {$record->name}")
                    ->body("Now in stock: {$record->fresh()->stock_quantity}.")->success()->send();
            });
    }

    public static function correct(): Action
    {
        return Action::make('correct')
            ->label('Correct stock')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('gray')
            ->visible(fn (Product $record) => auth()->user()?->can('adjustStock', $record))
            ->modalHeading(fn (Product $record) => "Stock count: {$record->name}")
            ->modalDescription('Enter what is actually on the shelf. The difference is recorded with your reason.')
            ->fillForm(fn (Product $record) => ['counted' => $record->stock_quantity])
            ->schema([
                TextInput::make('counted')->label('Counted quantity')->integer()->minValue(0)->required(),
                TextInput::make('reason')->label('Reason')->placeholder('Stocktake, damaged goods, …')->required()->maxLength(255),
            ])
            ->action(function (Product $record, array $data, Action $action) {
                try {
                    $movement = app(StockService::class)->correctTo($record, (int) $data['counted'], auth()->user(), $data['reason']);
                } catch (ValidationException $e) {
                    Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
                    $action->halt();

                    return;
                }

                Notification::make()
                    ->title($movement ? sprintf('Stock corrected by %+d', $movement->quantity) : 'Stock already matched the count')
                    ->success()->send();
            });
    }
}

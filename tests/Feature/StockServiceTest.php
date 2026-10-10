<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_receiving_goods_adds_stock_and_records_who_and_why(): void
    {
        $product = $this->product(stock: 5);
        $user = $this->staff('warehouse');

        app(StockService::class)->receive($product, 40, $user, 'Delivery note 1187');

        $this->assertSame(45, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id, 'type' => 'receipt', 'quantity' => 40, 'balance_after' => 45,
            'user_id' => $user->id, 'note' => 'Delivery note 1187',
        ]);
    }

    public function test_a_stock_count_records_the_difference_not_an_overwrite(): void
    {
        $product = $this->product(stock: 30);

        $movement = app(StockService::class)->correctTo($product, 27, $this->staff('warehouse'), 'Stocktake');

        $this->assertSame(-3, $movement->quantity);
        $this->assertSame(StockMovementType::Adjustment, $movement->type);
        $this->assertSame(27, $product->fresh()->stock_quantity);
        $this->assertNull(app(StockService::class)->correctTo($product, 27, null, 'Same again'));
    }

    public function test_stock_never_goes_below_zero(): void
    {
        $product = $this->product(stock: 3);

        $this->expectException(ValidationException::class);
        app(StockService::class)->move($product, -4, StockMovementType::Adjustment);
    }

    public function test_stock_cannot_be_changed_through_mass_assignment(): void
    {
        $product = $this->product(stock: 10);
        $product->update(['stock_quantity' => 999, 'name' => 'Renamed']);

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertSame('Renamed', $product->fresh()->name);
    }

    public function test_the_erp_sync_records_differences_as_movements(): void
    {
        $product = $this->product(stock: 10);
        $product->forceFill(['external_id' => 'ERP-1'])->save();
        $file = tempnam(sys_get_temp_dir(), 'erp');
        file_put_contents($file, json_encode([
            ['external_id' => 'ERP-1', 'name' => 'Synced name', 'price' => 99.5, 'stock_quantity' => 14],
            ['external_id' => 'ERP-2', 'name' => 'New from ERP', 'price' => 10, 'stock_quantity' => 3],
            ['name' => 'No id'],
        ]));

        $this->artisan('app:sync-products', ['--path' => $file])->assertSuccessful();

        $this->assertSame(14, $product->fresh()->stock_quantity);
        $this->assertSame('Synced name', $product->fresh()->name);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sync', 'quantity' => 4]);
        $this->assertDatabaseHas('products', ['external_id' => 'ERP-2', 'stock_quantity' => 3]);
    }
}

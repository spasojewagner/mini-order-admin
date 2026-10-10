<?php

namespace App\Console\Commands;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Console\Command;

/**
 * Pulls products from the ERP export (storage/app/erp-products.json). The ERP
 * is the source of truth for names, prices and stock; a stock difference is
 * written as an "ERP sync" movement, so the history still adds up.
 */
class SyncProducts extends Command
{
    protected $signature = 'app:sync-products {--path= : JSON file to read instead of storage/app/erp-products.json}';

    protected $description = 'Sync products from the ERP export';

    public function handle(StockService $stock): int
    {
        $path = $this->option('path') ?: storage_path('app/erp-products.json');

        if (! file_exists($path)) {
            $this->error("ERP export not found: {$path}");

            return self::FAILURE;
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data)) {
            $this->error('The ERP export is not valid JSON.');

            return self::FAILURE;
        }

        $created = $updated = $skipped = 0;

        foreach ($data as $row) {
            // Rows without external_id cannot be matched reliably.
            if (empty($row['external_id'])) {
                $skipped++;
                $this->warn('Skipped a row without external_id.');

                continue;
            }

            $product = Product::firstWhere('external_id', $row['external_id']);
            $attributes = array_filter([
                'sku' => $row['sku'] ?? null,
                'name' => $row['name'] ?? null,
                'category' => $row['category'] ?? null,
                'price' => $row['price'] ?? null,
            ], fn ($v) => $v !== null);

            if ($product) {
                $product->update($attributes);
                $updated++;
            } else {
                $product = Product::create(['external_id' => $row['external_id'], 'name' => 'Unnamed', 'is_active' => true, ...$attributes]);
                $created++;
            }

            if (isset($row['stock_quantity'])) {
                $stock->correctTo($product, (int) $row['stock_quantity'], null, 'ERP sync', StockMovementType::Sync);
            }
        }

        $this->info("Sync finished. Created: {$created}, updated: {$updated}, skipped: {$skipped}.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class SyncProducts extends Command
{
    protected $signature = 'app:sync-products';

    protected $description = 'Sinhronizuje proizvode iz spoljnog ERP sistema (JSON fajl)';

    public function handle(): int
    {
        $path = storage_path('app/erp-products.json');

        // Provera da fajl postoji (šta ako API/izvor ne radi)
        if (! file_exists($path)) {
            $this->error('ERP izvor nije dostupan (fajl ne postoji): ' . $path);
            return self::FAILURE;
        }

        $json = file_get_contents($path);
        $data = json_decode($json, true);

        // Provera da je JSON validan
        if (! is_array($data)) {
            $this->error('ERP podaci nisu validni (neispravan JSON).');
            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($data as $row) {
            // Preskoči redove bez external_id (ne možemo ih pouzdano mapirati)
            if (empty($row['external_id'])) {
                $skipped++;
                $this->warn('Preskočen red bez external_id.');
                continue;
            }

            $product = Product::where('external_id', $row['external_id'])->first();

            if ($product) {
                // Postoji — ažuriraj (ERP je izvor istine, prepiši polja)
                $product->update([
                    'sku' => $row['sku'] ?? $product->sku,
                    'name' => $row['name'] ?? $product->name,
                    'price' => $row['price'] ?? $product->price,
                    'stock_quantity' => $row['stock_quantity'] ?? $product->stock_quantity,
                ]);
                $updated++;
            } else {
                // Ne postoji — kreiraj novi
                Product::create([
                    'external_id' => $row['external_id'],
                    'sku' => $row['sku'] ?? null,
                    'name' => $row['name'] ?? 'Bez naziva',
                    'price' => $row['price'] ?? 0,
                    'stock_quantity' => $row['stock_quantity'] ?? 0,
                    'is_active' => true,
                ]);
                $created++;
            }
        }

        // Log rezultata
        $this->info("Sinhronizacija završena.");
        $this->info("Kreirano: {$created}");
        $this->info("Ažurirano: {$updated}");
        $this->info("Preskočeno: {$skipped}");

        return self::SUCCESS;
    }
}
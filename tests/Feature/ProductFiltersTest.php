<?php

namespace Tests\Feature;

use App\Livewire\ProductFilters;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_pretraga_postuje_filter_statusa(): void
    {
        Product::factory()->create([
            'name' => 'Laptop Novi',
            'sku' => 'LAP001',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Laptop Stari',
            'sku' => 'LAP002',
            'is_active' => false,
        ]);

        Livewire::test(ProductFilters::class)
            ->set('search', 'Laptop')
            ->set('status', 'inactive')
            ->assertViewHas('products', function ($products) {
                // Pre popravke je vracao oba, jer je OR "pobegao" iz filtera.
                return $products->count() === 1
                    && $products->first()->name === 'Laptop Stari';
            });
    }

    public function test_pretraga_postuje_filter_lagera(): void
    {
        Product::factory()->create([
            'name' => 'Miš Bezicni',
            'sku' => 'MIS001',
            'is_active' => true,
            'stock_quantity' => 0,
        ]);

        Product::factory()->create([
            'name' => 'Miš Zicni',
            'sku' => 'MIS002',
            'is_active' => true,
            'stock_quantity' => 5,
        ]);

        Livewire::test(ProductFilters::class)
            ->set('search', 'Miš')
            ->set('inStock', true)
            ->assertViewHas('products', function ($products) {
                return $products->count() === 1
                    && $products->first()->name === 'Miš Zicni';
            });
    }
}

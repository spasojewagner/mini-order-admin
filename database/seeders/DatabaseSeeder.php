<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Test nalozi (admin, sales, warehouse) — prvo, da postoje pre ostalog
        $this->call(UserSeeder::class);

        // Demo podaci
        Customer::factory(30)->create();
        Product::factory(20)->create();

        $this->call(OrderSeeder::class);
        $this->call(ConversationSeeder::class);
    }
}
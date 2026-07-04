<?php

namespace Database\Seeders;

use App\Models\User;
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
        \App\Models\Customer::factory(30)->create();
        \App\Models\Product::factory(20)->create();

        $this->call(OrderSeeder::class);
        $this->call(ConversationSeeder::class);
    }
}

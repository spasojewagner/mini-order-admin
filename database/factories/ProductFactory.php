<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(2, true)),
            'sku' => strtoupper(fake()->unique()->bothify('???-###')),
            'category' => fake()->randomElement(['Beverages', 'Snacks', 'Dairy', 'Household']),
            'price' => fake()->randomFloat(2, 100, 5000),
            'stock_quantity' => fake()->numberBetween(20, 100),
            'reorder_level' => 10,
            'is_active' => true,
        ];
    }
}

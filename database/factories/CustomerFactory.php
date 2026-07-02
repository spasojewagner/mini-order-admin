<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
   public function definition(): array
{
    $isCompany = fake()->boolean(30);

    return [
        'type' => $isCompany ? 'company' : 'individual',
        'name' => fake()->name(),
        'company_name' => $isCompany ? fake()->company() : null,
        'tax_id' => $isCompany ? fake()->numerify('#########') : null,
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->phoneNumber(),
        'address' => fake()->address(),
    ];
}
}

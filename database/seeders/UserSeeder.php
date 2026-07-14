<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Test nalozi za sve tri role (koriste se za prijavu na /admin)
        User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'sales@test.com'],
            [
                'name' => 'Sales',
                'password' => Hash::make('sales123'),
                'role' => 'sales',
            ]
        );

        User::updateOrCreate(
            ['email' => 'warehouse@test.com'],
            [
                'name' => 'Warehouse',
                'password' => Hash::make('warehouse123'),
                'role' => 'warehouse',
            ]
        );
    }
}
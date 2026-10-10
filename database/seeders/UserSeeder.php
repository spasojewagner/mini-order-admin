<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** Staff demo logins, one per role. Password for all: "password". The portal login is made by DemoDataSeeder. */
class UserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $staff = [
            ['email' => 'admin@demo.test', 'name' => 'Ana Petrović', 'role' => 'admin'],
            ['email' => 'sales@demo.test', 'name' => 'Marko Jovanović', 'role' => 'sales'],
            ['email' => 'warehouse@demo.test', 'name' => 'Nikola Ilić', 'role' => 'warehouse'],
        ];

        foreach ($staff as $user) {
            User::updateOrCreate(['email' => $user['email']], [
                'name' => $user['name'],
                'role' => $user['role'],
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
            ]);
        }
    }
}

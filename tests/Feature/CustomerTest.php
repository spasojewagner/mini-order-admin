<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_moze_da_se_kreira_kupac(): void
    {
        $response = $this->post('/customers', [
            'type' => 'individual',
            'name' => 'Pera Perić',
            'email' => 'pera@example.com',
        ]);

        // Proveri da je kupac stvarno u bazi
        $this->assertDatabaseHas('customers', [
            'name' => 'Pera Perić',
            'email' => 'pera@example.com',
        ]);
    }

    public function test_kupac_ne_moze_bez_imena(): void
    {
        $response = $this->post('/customers', [
            'type' => 'individual',
            'name' => '', // prazno ime
            'email' => 'test@example.com',
        ]);

        // Treba da vrati grešku validacije za polje name
        $response->assertSessionHasErrors('name');

        // I da NIŠTA nije upisano u bazu
        $this->assertDatabaseCount('customers', 0);
    }
}
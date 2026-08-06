<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registracija_pravi_i_kupca_i_povezuje_ga_sa_nalogom(): void
    {
        $this->post('/register', [
            'name' => 'Test Kupac',
            'email' => 'test.kupac@example.com',
            'password' => 'lozinka123',
            'password_confirmation' => 'lozinka123',
        ]);

        // Napravljen je Customer zapis
        $this->assertDatabaseHas('customers', [
            'email' => 'test.kupac@example.com',
            'type' => 'individual',
        ]);

        // Nalog je povezan sa tim kupcem i ima rolu customer
        $user = User::where('email', 'test.kupac@example.com')->first();
        $this->assertNotNull($user->customer_id);
        $this->assertEquals('customer', $user->role);
        $this->assertEquals('Test Kupac', $user->customer->name);
    }

    public function test_kupac_ne_moze_da_pristupi_admin_panelu(): void
    {
        $customer = Customer::factory()->create();
        $user = User::factory()->create([
            'role' => 'customer',
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        // Kupac nema pristup panelu (canAccessPanel ga odbija)
        $response->assertForbidden();
    }

    public function test_admin_moze_da_pristupi_admin_panelu(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertSuccessful();
    }
}
<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_signing_up_creates_a_customer_linked_to_the_account(): void
    {
        $this->post('/register', [
            'name' => 'Jelena Marković',
            'company_name' => 'Kafe Sunce',
            'tax_id' => '101234567',
            'email' => 'jelena@kafe-sunce.example',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'jelena@kafe-sunce.example',
            'type' => 'company',
            'company_name' => 'Kafe Sunce',
            'tax_id' => '101234567',
        ]);

        $user = User::firstWhere('email', 'jelena@kafe-sunce.example');
        $this->assertSame('customer', $user->role);
        $this->assertSame('Kafe Sunce', $user->customer->company_name);
    }

    public function test_signing_up_without_a_company_creates_an_individual(): void
    {
        $this->post('/register', [
            'name' => 'Petar Savić',
            'email' => 'petar@mail.example',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('customers', ['email' => 'petar@mail.example', 'type' => 'individual']);
    }

    public function test_customers_cannot_open_the_back_office(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'customer_id' => Customer::factory()->create()->id]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_staff_can_open_the_back_office(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertSuccessful();
    }
}

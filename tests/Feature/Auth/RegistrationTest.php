<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '05551234567',
            'company_title' => 'Test Agency',
            'tourism_title' => 'Test Tourism',
            'tursab_no' => '12345',
            'tax_number' => '1234567890',
            'tax_office' => 'Sisli',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('acente.dashboard', absolute: false));
    }
}

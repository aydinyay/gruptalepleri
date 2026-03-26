<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleGuardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_acente_cannot_access_superadmin_routes(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'roleguard-acente-superadmin@example.com',
        ]);

        $this->actingAs($acente)->get(route('superadmin.dashboard'))->assertForbidden();
        $this->actingAs($acente)->get(route('superadmin.charter.hub'))->assertForbidden();
        $this->actingAs($acente)->get(route('superadmin.leisure.settings.index'))->assertForbidden();
    }

    public function test_acente_cannot_access_admin_routes(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'roleguard-acente-admin@example.com',
        ]);

        $this->actingAs($acente)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($acente)->get(route('admin.charter.hub'))->assertForbidden();
    }

    public function test_admin_can_access_admin_but_not_superadmin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'roleguard-admin@example.com',
        ]);

        $this->actingAs($admin)->get(route('admin.charter.hub'))->assertOk();

        $this->actingAs($admin)->get(route('superadmin.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('superadmin.charter.hub'))->assertForbidden();
    }

    public function test_superadmin_can_access_superadmin_and_admin_routes(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'roleguard-superadmin@example.com',
        ]);

        $this->actingAs($superadmin)->get(route('superadmin.charter.hub'))->assertOk();

        $this->actingAs($superadmin)->get(route('admin.charter.hub'))->assertOk();
    }
}

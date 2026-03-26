<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_acente_air_charter_hub_renders_vitrin_and_shortcuts(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-charter-hub@example.com',
        ]);

        $response = $this->actingAs($acente)->get(route('acente.charter.hub'));

        $response->assertOk();
        $response->assertSee('Air Charter Merkezi', false);
        $response->assertSee(route('acente.charter.index'), false);
        $response->assertSee(route('acente.charter.create'), false);
        $response->assertSee('Hazir Charter Paketleri', false);
        $response->assertSee('Premium Hazir Paketler', false);
        $response->assertSee('Rezervasyon Yap', false);
    }

    public function test_admin_air_charter_hub_renders(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-charter-hub@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.charter.hub'));

        $response->assertOk();
        $response->assertSee('Air Charter Merkezi', false);
        $response->assertSee(route('admin.charter.index'), false);
    }

    public function test_superadmin_air_charter_hub_renders(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-charter-hub@example.com',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.charter.hub'));

        $response->assertOk();
        $response->assertSee('Air Charter Merkezi', false);
        $response->assertSee(route('superadmin.charter.index'), false);
        $response->assertSee(route('superadmin.charter.packages.index'), false);
        $response->assertSee('Premium Hazir Paketler', false);
        $response->assertSee('Paket Yonetimine Git', false);
        $response->assertDontSee('Rezervasyon Yap', false);
    }

    public function test_charter_list_route_is_preserved_while_hub_route_exists(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-charter-list@example.com',
        ]);

        $response = $this->actingAs($acente)->get(route('acente.charter.index'));

        $response->assertOk();
        $response->assertSee('Air Charter Taleplerim', false);
    }

    public function test_acente_charter_hub_shows_empty_premium_state_when_no_active_packages(): void
    {
        DB::table('charter_preset_packages')->update(['is_active' => false]);

        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-charter-hub-empty@example.com',
        ]);

        $response = $this->actingAs($acente)->get(route('acente.charter.hub'));

        $response->assertOk();
        $response->assertSee('Premium Hazir Paketler', false);
        $response->assertSee('Su an aktif hazir paket bulunmuyor.', false);
    }

    public function test_superadmin_charter_hub_shows_empty_premium_state_when_no_active_packages(): void
    {
        DB::table('charter_preset_packages')->update(['is_active' => false]);

        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-charter-hub-empty@example.com',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.charter.hub'));

        $response->assertOk();
        $response->assertSee('Premium Hazir Paketler', false);
        $response->assertSee('Su an aktif hazir paket bulunmuyor.', false);
    }

    public function test_acente_navbar_air_charter_group_header_points_to_hub_route(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-navbar-hub-link@example.com',
        ]);

        $response = $this->actingAs($acente)->get(route('acente.charter.index'));

        $response->assertOk();
        $response->assertSee(route('acente.charter.hub'), false);
    }
}

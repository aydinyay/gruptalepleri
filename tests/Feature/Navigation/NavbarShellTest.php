<?php

namespace Tests\Feature\Navigation;

use App\Models\AiCelebrationCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_uses_role_based_acente_navbar_shell(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'navbar-acente@example.com',
        ]);

        $response = $this->actingAs($acente)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('data-gt-nav-shell="1"', false);
        $response->assertSee('data-gt-role="acente"', false);
        $response->assertSee('data-gt-active-group="hesap"', false);
    }

    public function test_admin_navbar_respects_broadcast_permission_visibility(): void
    {
        $adminNoBroadcast = User::factory()->create([
            'role' => 'admin',
            'can_send_broadcast' => false,
            'email' => 'navbar-admin-no-broadcast@example.com',
        ]);

        $responseNoBroadcast = $this->actingAs($adminNoBroadcast)->get(route('admin.charter.index'));
        $responseNoBroadcast->assertOk();
        $responseNoBroadcast->assertSee('data-gt-role="admin"', false);
        $responseNoBroadcast->assertDontSee(route('admin.broadcast.create'), false);

        $adminWithBroadcast = User::factory()->create([
            'role' => 'admin',
            'can_send_broadcast' => true,
            'email' => 'navbar-admin-with-broadcast@example.com',
        ]);

        $responseWithBroadcast = $this->actingAs($adminWithBroadcast)->get(route('admin.charter.index'));
        $responseWithBroadcast->assertOk();
        $responseWithBroadcast->assertSee(route('admin.broadcast.create'), false);
    }

    public function test_superadmin_charter_packages_page_marks_air_charter_group_active(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'navbar-superadmin-charter@example.com',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.charter.packages.index'));

        $response->assertOk();
        $response->assertSee('data-gt-role="superadmin"', false);
        $response->assertSee('data-gt-active-group="air-charter"', false);
    }

    public function test_superadmin_yonetim_group_page_is_highlighted_but_closed_by_default_on_desktop(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'navbar-superadmin-acenteler@example.com',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.acenteler'));

        $response->assertOk();
        $response->assertSee('data-gt-active-group="yonetim"', false);

        $content = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<div[^>]*class="gt-nav-group"[^>]*data-gt-nav-group="yonetim"[^>]*>/',
            $content
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<details[^>]*class="gt-nav-group"[^>]*data-gt-nav-group="yonetim"[^>]*>/',
            $content
        );
    }

    public function test_desktop_navbar_css_includes_hover_bridge_for_dropdown_gap(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'navbar-acente-hover-bridge@example.com',
        ]);

        $response = $this->actingAs($acente)->get(route('acente.charter.index'));

        $response->assertOk();
        $response->assertSee('.gt-nav-group::after', false);
        $response->assertSee('width: max(100%, 16rem);', false);
        $response->assertSee('height: .4rem;', false);
    }

    public function test_superadmin_ai_preview_page_uses_shared_navbar_shell(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'navbar-superadmin-ai@example.com',
        ]);

        $campaign = AiCelebrationCampaign::query()->create([
            'event_name' => 'Test Kutlama',
            'status' => AiCelebrationCampaign::STATUS_DRAFT,
            'display_mode' => AiCelebrationCampaign::DISPLAY_BANNER,
            'show_on_authenticated' => true,
            'priority' => 100,
            'frequency_cap' => 1,
            'created_by' => $superadmin->id,
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.ai-kutlama.onizleme', $campaign));

        $response->assertOk();
        $response->assertSee('data-gt-role="superadmin"', false);
        $response->assertSee('data-gt-active-group="sistem"', false);
        $response->assertSee(route('superadmin.site.ayarlar', ['sekme' => 'ai']), false);
    }

    public function test_acente_charter_page_renders_product_grouped_navbar(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'navbar-acente-charter@example.com',
        ]);

        $response = $this->actingAs($acente)->get(route('acente.charter.index'));

        $response->assertOk();
        $response->assertSee('data-gt-role="acente"', false);
        $response->assertSee('data-gt-nav-group="talepler"', false);
        $response->assertSee('data-gt-nav-group="air-charter"', false);
        $response->assertSee('data-gt-nav-group="transfer"', false);
        $response->assertSee('data-gt-nav-group="leisure"', false);
    }
}

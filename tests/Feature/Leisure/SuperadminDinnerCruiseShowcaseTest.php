<?php

namespace Tests\Feature\Leisure;

use App\Models\LeisureExtraOption;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminDinnerCruiseShowcaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_view_dinner_cruise_showcase_page(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-dinner-showcase@example.com',
        ]);

        LeisurePackageTemplate::query()->create([
            'product_type' => 'dinner_cruise',
            'code' => 'bosphorus_premium_test',
            'level' => 'premium',
            'name_tr' => 'Bosphorus Premium',
            'name_en' => 'Bosphorus Premium',
            'summary_tr' => 'Premium dinner cruise deneyimi.',
            'summary_en' => 'Premium dinner cruise experience.',
            'hero_image_url' => '/uploads/leisure-media/seed-bosphorus/bosphorus-exterior-night-blue.jpg',
            'includes_tr' => ['Shuttle transfer', 'Premium menu'],
            'includes_en' => ['Shuttle transfer', 'Premium menu'],
            'excludes_tr' => ['Private yacht'],
            'excludes_en' => ['Private yacht'],
            'is_active' => true,
            'sort_order' => 5,
        ]);

        LeisureMediaAsset::query()->create([
            'product_type' => 'dinner_cruise',
            'category' => 'show',
            'media_type' => 'photo',
            'source_type' => 'upload',
            'title_tr' => 'Semazen gosterisi',
            'title_en' => 'Whirling dervish show',
            'file_path' => '/uploads/leisure-media/seed-bosphorus/bosphorus-show-dervish.jpg',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        LeisureExtraOption::query()->create([
            'product_type' => 'dinner_cruise',
            'category' => 'transfer',
            'code' => 'shuttle_transfer_showcase',
            'title_tr' => 'Shuttle Transfer',
            'title_en' => 'Shuttle Transfer',
            'default_included' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.dinner-cruise.showcase'));

        $response->assertOk();
        $response->assertSee('Dinner Cruise Teklif Vitrini');
        $response->assertSee('Bosphorus Premium');
        $response->assertSee('Semazen gosterisi');
        $response->assertSee('Shuttle Transfer');
        $response->assertSee('/uploads/leisure-media/seed-bosphorus/bosphorus-exterior-night-blue.jpg');
    }

    public function test_non_superadmin_users_cannot_access_dinner_cruise_showcase_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-dinner-showcase@example.com',
        ]);

        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-dinner-showcase@example.com',
        ]);

        $this->actingAs($admin)->get(route('superadmin.dinner-cruise.showcase'))->assertForbidden();
        $this->actingAs($acente)->get(route('superadmin.dinner-cruise.showcase'))->assertForbidden();
    }
}

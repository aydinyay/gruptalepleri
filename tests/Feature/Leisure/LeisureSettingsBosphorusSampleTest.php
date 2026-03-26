<?php

namespace Tests\Feature\Leisure;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LeisureSettingsBosphorusSampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_inserts_bosphorus_dinner_cruise_template(): void
    {
        $this->assertDatabaseHas('leisure_package_templates', [
            'product_type' => 'dinner_cruise',
            'code' => 'bosphorus_dinner_cruise',
            'level' => 'premium',
            'name_tr' => 'Bosphorus Dinner Cruise',
            'name_en' => 'Bosphorus Dinner Cruise',
            'sort_order' => 15,
            'is_active' => 1,
        ]);
    }

    public function test_bosphorus_template_migration_is_idempotent_when_run_again(): void
    {
        $migration = require database_path('migrations/2026_03_24_210500_add_bosphorus_dinner_cruise_leisure_template.php');
        $migration->up();

        $count = DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'bosphorus_dinner_cruise')
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_superadmin_leisure_settings_view_renders_bosphorus_prefill_button_and_payload(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-leisure-sample-view@example.com',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.leisure.settings.index'));

        $response->assertOk();
        $response->assertSee('Bosphorus Ornegini Doldur');
        $response->assertSee('id="leisurePackageCreateForm"', false);
        $response->assertSee('data-bosphorus-sample=', false);
        $response->assertSee('bosphorus_dinner_cruise');
        $response->assertSee('name="hero_image_url"', false);
        $response->assertSee('name="hero_image_file"', false);
        $response->assertSee('bootstrap.bundle.min.js');
        $response->assertSee('data-bs-toggle="collapse"', false);
    }

    public function test_superadmin_can_still_store_another_package_template_after_sample_changes(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-leisure-sample-store@example.com',
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.leisure.settings.packages.store'), [
            'product_type' => 'dinner_cruise',
            'code' => 'bosphorus_dinner_cruise_plus',
            'level' => 'vip',
            'name_tr' => 'Bosphorus Dinner Cruise Plus',
            'name_en' => 'Bosphorus Dinner Cruise Plus',
            'summary_tr' => 'Ornek paket testi',
            'summary_en' => 'Sample package test',
            'hero_image_url' => '/uploads/leisure-media/seed-bosphorus/bosphorus-exterior-night-blue.jpg',
            'includes_tr_text' => "Transfer\nMenu",
            'includes_en_text' => "Transfer\nMenu",
            'excludes_tr_text' => "Private yacht",
            'excludes_en_text' => "Private yacht",
            'is_active' => '1',
            'sort_order' => 25,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leisure_package_templates', [
            'product_type' => 'dinner_cruise',
            'code' => 'bosphorus_dinner_cruise_plus',
            'name_tr' => 'Bosphorus Dinner Cruise Plus',
            'hero_image_url' => '/uploads/leisure-media/seed-bosphorus/bosphorus-exterior-night-blue.jpg',
        ]);
    }

    public function test_superadmin_can_store_package_template_with_uploaded_hero_image(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-leisure-upload-store@example.com',
        ]);

        $upload = UploadedFile::fake()->image('hero.jpg', 1280, 720);

        $response = $this->actingAs($superadmin)->post(route('superadmin.leisure.settings.packages.store'), [
            'product_type' => 'dinner_cruise',
            'code' => 'upload_hero_case',
            'level' => 'standard',
            'name_tr' => 'Upload Hero Paket',
            'name_en' => 'Upload Hero Package',
            'hero_image_file' => $upload,
            'includes_tr_text' => "Transfer\nMenu",
            'includes_en_text' => "Transfer\nMenu",
            'is_active' => '1',
            'sort_order' => 12,
        ]);

        $response->assertRedirect();
        $template = DB::table('leisure_package_templates')
            ->where('product_type', 'dinner_cruise')
            ->where('code', 'upload_hero_case')
            ->first();

        $this->assertNotNull($template);
        $this->assertStringStartsWith('/uploads/leisure-package-heroes/', (string) $template->hero_image_url);
        $this->assertFileExists(public_path(ltrim((string) $template->hero_image_url, '/')));

        File::delete(public_path(ltrim((string) $template->hero_image_url, '/')));
    }

    public function test_superadmin_can_clear_existing_uploaded_hero_image_on_update(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-leisure-upload-clear@example.com',
        ]);

        $directory = '/uploads/leisure-package-heroes/tests';
        File::ensureDirectoryExists(public_path($directory));
        $absolutePath = public_path(ltrim($directory . '/old-hero.jpg', '/'));
        File::put($absolutePath, 'old-hero');

        $templateId = DB::table('leisure_package_templates')->insertGetId([
            'product_type' => 'dinner_cruise',
            'code' => 'clear_hero_case',
            'level' => 'vip',
            'name_tr' => 'Clear Hero Paket',
            'name_en' => 'Clear Hero Package',
            'hero_image_url' => $directory . '/old-hero.jpg',
            'includes_tr' => json_encode(['Transfer']),
            'includes_en' => json_encode(['Transfer']),
            'excludes_tr' => json_encode([]),
            'excludes_en' => json_encode([]),
            'is_active' => 1,
            'sort_order' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->patch(route('superadmin.leisure.settings.packages.update', $templateId), [
            'product_type' => 'dinner_cruise',
            'code' => 'clear_hero_case',
            'level' => 'vip',
            'name_tr' => 'Clear Hero Paket',
            'name_en' => 'Clear Hero Package',
            'hero_image_url' => $directory . '/old-hero.jpg',
            'clear_hero_image' => '1',
            'includes_tr_text' => "Transfer",
            'includes_en_text' => "Transfer",
            'is_active' => '1',
            'sort_order' => 22,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leisure_package_templates', [
            'id' => $templateId,
            'hero_image_url' => null,
        ]);
        $this->assertFileDoesNotExist($absolutePath);
    }
}

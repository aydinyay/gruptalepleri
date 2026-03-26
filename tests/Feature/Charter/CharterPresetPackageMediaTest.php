<?php

namespace Tests\Feature\Charter;

use App\Models\CharterPresetPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CharterPresetPackageMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_store_package_with_hero_image_url(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-package-media-store@example.com',
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.charter.packages.store'), [
            'code' => 'test-media-url-001',
            'title' => 'Test Media URL Package',
            'summary' => 'Hero image url should be persisted.',
            'transport_type' => 'jet',
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'from_label' => 'Istanbul Airport',
            'to_label' => 'Antalya Airport',
            'aircraft_label' => 'Citation CJ2',
            'suggested_pax' => 6,
            'trip_type' => 'Tek Yon',
            'group_type' => 'VIP',
            'cabin_preference' => 'vip_jet',
            'price' => 12345,
            'currency' => 'EUR',
            'hero_image_url' => 'https://example.com/jet.jpg',
            'highlights_text' => "Hizli check-in\nPremium ikram",
            'is_active' => '1',
            'sort_order' => 10,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('charter_preset_packages', [
            'code' => 'test-media-url-001',
            'hero_image_url' => 'https://example.com/jet.jpg',
        ]);
    }

    public function test_upload_overrides_manual_hero_image_and_replaces_old_file(): void
    {
        $oldFileAbsolutePath = $this->createManagedPublicStorageFile('old-image.jpg', 'old');

        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-package-media-update@example.com',
        ]);

        $package = CharterPresetPackage::query()->create([
            'code' => 'test-media-upload-001',
            'title' => 'Upload Package',
            'summary' => 'Upload should replace old image.',
            'transport_type' => 'jet',
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'from_label' => 'Istanbul Airport',
            'to_label' => 'Antalya Airport',
            'aircraft_label' => 'Citation CJ2',
            'suggested_pax' => 6,
            'trip_type' => 'Tek Yon',
            'group_type' => 'VIP',
            'cabin_preference' => 'vip_jet',
            'price' => 14500,
            'currency' => 'EUR',
            'hero_image_url' => '/storage/charter/preset-packages/old-image.jpg',
            'highlights_json' => ['Hizli check-in'],
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $response = $this->actingAs($superadmin)->post(
            route('superadmin.charter.packages.update', ['packageCode' => $package->code]),
            [
                '_method' => 'PATCH',
                'code' => $package->code,
                'title' => 'Upload Package Updated',
                'summary' => 'Upload should replace old image.',
                'transport_type' => 'jet',
                'from_iata' => 'IST',
                'to_iata' => 'AYT',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Antalya Airport',
                'aircraft_label' => 'Citation CJ2',
                'suggested_pax' => 6,
                'trip_type' => 'Tek Yon',
                'group_type' => 'VIP',
                'cabin_preference' => 'vip_jet',
                'price' => 14500,
                'currency' => 'EUR',
                'hero_image_url' => 'https://example.com/ignored-because-file-exists.jpg',
                'hero_image_file' => UploadedFile::fake()->image('new-jet.jpg', 1280, 720),
                'highlights_text' => "Hizli check-in\nPremium ikram",
                'is_active' => '1',
                'sort_order' => 20,
            ]
        );

        $response->assertRedirect();

        $package->refresh();

        $this->assertStringStartsWith('/storage/charter/preset-packages/', (string) $package->hero_image_url);
        $this->assertNotEquals('/storage/charter/preset-packages/old-image.jpg', $package->hero_image_url);

        $this->assertFileDoesNotExist($oldFileAbsolutePath);

        $newAbsolutePath = $this->absolutePathFromPublicUrl((string) $package->hero_image_url);
        $this->assertFileExists($newAbsolutePath);
    }

    public function test_destroy_cleans_up_managed_hero_image_file(): void
    {
        $deleteFileAbsolutePath = $this->createManagedPublicStorageFile('delete-image.jpg', 'delete');

        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-package-media-delete@example.com',
        ]);

        $package = CharterPresetPackage::query()->create([
            'code' => 'test-media-delete-001',
            'title' => 'Delete Package',
            'summary' => 'Delete should clean managed file.',
            'transport_type' => 'jet',
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'from_label' => 'Istanbul Airport',
            'to_label' => 'Antalya Airport',
            'aircraft_label' => 'Citation CJ2',
            'suggested_pax' => 6,
            'trip_type' => 'Tek Yon',
            'group_type' => 'VIP',
            'cabin_preference' => 'vip_jet',
            'price' => 16800,
            'currency' => 'EUR',
            'hero_image_url' => '/storage/charter/preset-packages/delete-image.jpg',
            'highlights_json' => ['Hizli check-in'],
            'is_active' => true,
            'sort_order' => 30,
        ]);

        $response = $this->actingAs($superadmin)->delete(
            route('superadmin.charter.packages.destroy', ['packageCode' => $package->code])
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('charter_preset_packages', [
            'code' => 'test-media-delete-001',
        ]);
        $this->assertFileDoesNotExist($deleteFileAbsolutePath);
    }

    public function test_store_auto_creates_hero_column_when_missing_and_persists_hero_url(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-package-media-missing-col-store@example.com',
        ]);

        $this->dropHeroImageColumnIfExists();

        $response = $this->actingAs($superadmin)->post(route('superadmin.charter.packages.store'), [
            'code' => 'test-media-guard-store-001',
            'title' => 'Guard Store Package',
            'summary' => 'Should auto-heal missing hero column.',
            'transport_type' => 'jet',
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'from_label' => 'Istanbul Airport',
            'to_label' => 'Antalya Airport',
            'aircraft_label' => 'Citation CJ2',
            'suggested_pax' => 6,
            'trip_type' => 'Tek Yon',
            'group_type' => 'VIP',
            'cabin_preference' => 'vip_jet',
            'price' => 12345,
            'currency' => 'EUR',
            'hero_image_url' => 'https://example.com/guard-store.jpg',
            'highlights_text' => "Hizli check-in\nPremium ikram",
            'is_active' => '1',
            'sort_order' => 10,
        ]);

        $response->assertRedirect();
        $this->assertTrue(Schema::hasColumn('charter_preset_packages', 'hero_image_url'));
        $this->assertDatabaseHas('charter_preset_packages', [
            'code' => 'test-media-guard-store-001',
            'hero_image_url' => 'https://example.com/guard-store.jpg',
        ]);
    }

    public function test_update_auto_creates_hero_column_when_missing_and_persists_uploaded_file(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-package-media-missing-col-update@example.com',
        ]);

        $package = CharterPresetPackage::query()->create([
            'code' => 'test-media-guard-update-001',
            'title' => 'Guard Update Package',
            'summary' => 'Should stay unchanged.',
            'transport_type' => 'jet',
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'from_label' => 'Istanbul Airport',
            'to_label' => 'Antalya Airport',
            'aircraft_label' => 'Citation CJ2',
            'suggested_pax' => 6,
            'trip_type' => 'Tek Yon',
            'group_type' => 'VIP',
            'cabin_preference' => 'vip_jet',
            'price' => 14500,
            'currency' => 'EUR',
            'hero_image_url' => 'https://example.com/old-hero.jpg',
            'highlights_json' => ['Hizli check-in'],
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $this->dropHeroImageColumnIfExists();

        $response = $this->actingAs($superadmin)->post(
            route('superadmin.charter.packages.update', ['packageCode' => $package->code]),
            [
                '_method' => 'PATCH',
                'code' => $package->code,
                'title' => 'Guard Update Package Updated',
                'summary' => 'Should auto-heal missing hero column.',
                'transport_type' => 'jet',
                'from_iata' => 'IST',
                'to_iata' => 'AYT',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Antalya Airport',
                'aircraft_label' => 'Citation CJ2',
                'suggested_pax' => 6,
                'trip_type' => 'Tek Yon',
                'group_type' => 'VIP',
                'cabin_preference' => 'vip_jet',
                'price' => 14500,
                'currency' => 'EUR',
                'hero_image_file' => UploadedFile::fake()->image('guard-new-jet.jpg', 1280, 720),
                'highlights_text' => "Hizli check-in\nPremium ikram",
                'is_active' => '1',
                'sort_order' => 20,
            ]
        );

        $response->assertRedirect();
        $this->assertTrue(Schema::hasColumn('charter_preset_packages', 'hero_image_url'));

        $package->refresh();
        $this->assertSame('Guard Update Package Updated', (string) $package->title);
        $this->assertStringStartsWith('/storage/charter/preset-packages/', (string) $package->hero_image_url);

        $newAbsolutePath = $this->absolutePathFromPublicUrl((string) $package->hero_image_url);
        $this->assertFileExists($newAbsolutePath);
    }

    public function test_update_can_clear_existing_hero_image_url(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-package-media-clear-url@example.com',
        ]);

        $package = CharterPresetPackage::query()->create([
            'code' => 'test-media-clear-hero-001',
            'title' => 'Clear Hero Package',
            'summary' => 'Hero URL should be removable.',
            'transport_type' => 'jet',
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'from_label' => 'Istanbul Airport',
            'to_label' => 'Antalya Airport',
            'aircraft_label' => 'Citation CJ2',
            'suggested_pax' => 6,
            'trip_type' => 'Tek Yon',
            'group_type' => 'VIP',
            'cabin_preference' => 'vip_jet',
            'price' => 14500,
            'currency' => 'EUR',
            'hero_image_url' => 'https://example.com/will-be-cleared.jpg',
            'highlights_json' => ['Hizli check-in'],
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $response = $this->actingAs($superadmin)->post(
            route('superadmin.charter.packages.update', ['packageCode' => $package->code]),
            [
                '_method' => 'PATCH',
                'code' => $package->code,
                'title' => $package->title,
                'summary' => $package->summary,
                'transport_type' => $package->transport_type,
                'from_iata' => $package->from_iata,
                'to_iata' => $package->to_iata,
                'from_label' => $package->from_label,
                'to_label' => $package->to_label,
                'aircraft_label' => $package->aircraft_label,
                'suggested_pax' => $package->suggested_pax,
                'trip_type' => $package->trip_type,
                'group_type' => $package->group_type,
                'cabin_preference' => $package->cabin_preference,
                'price' => $package->price,
                'currency' => $package->currency,
                'hero_image_url' => '',
                'highlights_text' => "Hizli check-in\nPremium ikram",
                'is_active' => '1',
                'sort_order' => $package->sort_order,
            ]
        );

        $response->assertRedirect();

        $package->refresh();
        $this->assertNull($package->hero_image_url);
    }

    public function test_update_can_force_remove_existing_hero_image_with_checkbox(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-package-media-clear-url-checkbox@example.com',
        ]);

        $package = CharterPresetPackage::query()->create([
            'code' => 'test-media-clear-hero-checkbox-001',
            'title' => 'Clear Hero Package Checkbox',
            'summary' => 'Hero URL should be removable via checkbox.',
            'transport_type' => 'jet',
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'from_label' => 'Istanbul Airport',
            'to_label' => 'Antalya Airport',
            'aircraft_label' => 'Citation CJ2',
            'suggested_pax' => 6,
            'trip_type' => 'Tek Yon',
            'group_type' => 'VIP',
            'cabin_preference' => 'vip_jet',
            'price' => 14500,
            'currency' => 'EUR',
            'hero_image_url' => 'https://example.com/will-be-cleared-checkbox.jpg',
            'highlights_json' => ['Hizli check-in'],
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $response = $this->actingAs($superadmin)->post(
            route('superadmin.charter.packages.update', ['packageCode' => $package->code]),
            [
                '_method' => 'PATCH',
                'code' => $package->code,
                'title' => $package->title,
                'summary' => $package->summary,
                'transport_type' => $package->transport_type,
                'from_iata' => $package->from_iata,
                'to_iata' => $package->to_iata,
                'from_label' => $package->from_label,
                'to_label' => $package->to_label,
                'aircraft_label' => $package->aircraft_label,
                'suggested_pax' => $package->suggested_pax,
                'trip_type' => $package->trip_type,
                'group_type' => $package->group_type,
                'cabin_preference' => $package->cabin_preference,
                'price' => $package->price,
                'currency' => $package->currency,
                'hero_image_url' => $package->hero_image_url,
                'hero_image_remove' => '1',
                'highlights_text' => "Hizli check-in\nPremium ikram",
                'is_active' => '1',
                'sort_order' => $package->sort_order,
            ]
        );

        $response->assertRedirect();

        $package->refresh();
        $this->assertNull($package->hero_image_url);
    }

    public function test_index_shows_warning_when_hero_column_is_missing(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-package-media-missing-col-index@example.com',
        ]);

        $this->dropHeroImageColumnIfExists();

        $response = $this->actingAs($superadmin)->get(route('superadmin.charter.packages.index'));

        $response->assertOk();
        $response->assertSee('data-hero-image-feature-warning="1"', false);
        $response->assertSee('Kayit aninda otomatik duzeltme denenecek; yine de kalici cozum icin');
    }

    private function dropHeroImageColumnIfExists(): void
    {
        if (! Schema::hasTable('charter_preset_packages') || ! Schema::hasColumn('charter_preset_packages', 'hero_image_url')) {
            return;
        }

        Schema::table('charter_preset_packages', function ($table): void {
            $table->dropColumn('hero_image_url');
        });
    }

    private function createManagedPublicStorageFile(string $fileName, string $content): string
    {
        $absolutePath = public_path(str_replace('/', DIRECTORY_SEPARATOR, 'storage/charter/preset-packages/' . $fileName));
        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        file_put_contents($absolutePath, $content);

        return $absolutePath;
    }

    private function absolutePathFromPublicUrl(string $publicUrl): string
    {
        $relativePath = ltrim(parse_url($publicUrl, PHP_URL_PATH) ?: $publicUrl, '/');

        return public_path(str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
    }
}

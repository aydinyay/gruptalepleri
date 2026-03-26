<?php

namespace Tests\Feature\Leisure;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeisureMediaLibrarySeedTest extends TestCase
{
    use RefreshDatabase;

    private const FILE_PREFIX = '/uploads/leisure-media/seed-bosphorus/';

    public function test_migration_seeds_bosphorus_dinner_cruise_media_assets(): void
    {
        $count = DB::table('leisure_media_assets')
            ->where('product_type', 'dinner_cruise')
            ->where('file_path', 'like', self::FILE_PREFIX . '%')
            ->count();

        $this->assertSame(10, $count);
        $this->assertDatabaseHas('leisure_media_assets', [
            'product_type' => 'dinner_cruise',
            'file_path' => self::FILE_PREFIX . 'bosphorus-show-dervish.jpg',
            'title_tr' => 'Semazen gosterisi',
            'source_type' => 'upload',
            'media_type' => 'photo',
            'is_active' => 1,
        ]);
    }

    public function test_media_seed_migration_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_03_24_211000_seed_bosphorus_dinner_cruise_media_assets.php');
        $migration->up();

        $count = DB::table('leisure_media_assets')
            ->where('product_type', 'dinner_cruise')
            ->where('file_path', 'like', self::FILE_PREFIX . '%')
            ->count();

        $this->assertSame(10, $count);
    }

    public function test_superadmin_leisure_settings_lists_seeded_media_entries(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-leisure-media-seed@example.com',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.leisure.settings.index'));

        $response->assertOk();
        $response->assertSee('Semazen gosterisi');
        $response->assertSee('Gece bogaz tekne gorunumu');
        $response->assertSee(self::FILE_PREFIX . 'bosphorus-show-dervish.jpg');
    }
}


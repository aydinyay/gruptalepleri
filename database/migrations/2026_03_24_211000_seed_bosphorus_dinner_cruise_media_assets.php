<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const FILE_PREFIX = '/uploads/leisure-media/seed-bosphorus/';

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'file' => 'bosphorus-table-banquet.jpg',
                'category' => 'masa_duzeni',
                'title_tr' => 'Uzun banquet masa duzeni',
                'title_en' => 'Long banquet table setup',
                'tags' => ['dinner', 'table', 'banquet', 'interior'],
                'sort_order' => 10,
            ],
            [
                'file' => 'bosphorus-show-dervish.jpg',
                'category' => 'show',
                'title_tr' => 'Semazen gosterisi',
                'title_en' => 'Whirling dervish performance',
                'tags' => ['show', 'dervish', 'culture'],
                'sort_order' => 20,
            ],
            [
                'file' => 'bosphorus-show-oriental.jpg',
                'category' => 'show',
                'title_tr' => 'Oryantal dans show',
                'title_en' => 'Oriental dance show',
                'tags' => ['show', 'dance', 'entertainment'],
                'sort_order' => 30,
            ],
            [
                'file' => 'bosphorus-show-caucasian-red.jpg',
                'category' => 'show',
                'title_tr' => 'Kafkas dans gosterisi',
                'title_en' => 'Caucasian dance performance',
                'tags' => ['show', 'caucasian', 'live'],
                'sort_order' => 40,
            ],
            [
                'file' => 'bosphorus-interior-window.jpg',
                'category' => 'ambiyans',
                'title_tr' => 'Bogaz manzarali salon masalari',
                'title_en' => 'Bosphorus view dining hall',
                'tags' => ['interior', 'view', 'dining'],
                'sort_order' => 50,
            ],
            [
                'file' => 'bosphorus-show-folk-black.jpg',
                'category' => 'show',
                'title_tr' => 'Halk dansi performansi',
                'title_en' => 'Folk dance performance',
                'tags' => ['show', 'folk', 'live'],
                'sort_order' => 60,
            ],
            [
                'file' => 'bosphorus-exterior-night-blue.jpg',
                'category' => 'tekne_dis',
                'title_tr' => 'Gece bogaz tekne gorunumu',
                'title_en' => 'Night cruise exterior view',
                'tags' => ['exterior', 'night', 'boat'],
                'sort_order' => 70,
            ],
            [
                'file' => 'bosphorus-exterior-bridge-night.jpg',
                'category' => 'tekne_dis',
                'title_tr' => 'Kopru altinda gece rotasi',
                'title_en' => 'Night route under bridge',
                'tags' => ['exterior', 'bridge', 'night'],
                'sort_order' => 80,
            ],
            [
                'file' => 'bosphorus-show-folk-group.jpg',
                'category' => 'show',
                'title_tr' => 'Canli ekip dans gosterisi',
                'title_en' => 'Live group dance show',
                'tags' => ['show', 'group', 'entertainment'],
                'sort_order' => 90,
            ],
            [
                'file' => 'bosphorus-interior-dance-floor.jpg',
                'category' => 'ambiyans',
                'title_tr' => 'Dans pisti ve masa yerlesimi',
                'title_en' => 'Dance floor and seating layout',
                'tags' => ['interior', 'dance-floor', 'layout'],
                'sort_order' => 100,
            ],
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('leisure_media_assets')) {
            return;
        }

        $now = now();
        foreach ($this->rows() as $row) {
            $filePath = self::FILE_PREFIX . $row['file'];

            $exists = DB::table('leisure_media_assets')
                ->where('product_type', 'dinner_cruise')
                ->where('file_path', $filePath)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('leisure_media_assets')->insert([
                'product_type' => 'dinner_cruise',
                'category' => $row['category'],
                'media_type' => 'photo',
                'source_type' => 'upload',
                'title_tr' => $row['title_tr'],
                'title_en' => $row['title_en'],
                'file_path' => $filePath,
                'external_url' => null,
                'tags_json' => json_encode($row['tags'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'capacity_min' => null,
                'capacity_max' => null,
                'luxury_level' => 'premium',
                'usage_type' => 'shared',
                'is_active' => true,
                'sort_order' => $row['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('leisure_media_assets')) {
            return;
        }

        $paths = collect($this->rows())
            ->map(fn (array $row) => self::FILE_PREFIX . $row['file'])
            ->all();

        DB::table('leisure_media_assets')
            ->where('product_type', 'dinner_cruise')
            ->whereIn('file_path', $paths)
            ->delete();
    }
};


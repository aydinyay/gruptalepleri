<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        Schema::table('leisure_package_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('leisure_package_templates', 'base_price_per_person')) {
                $table->decimal('base_price_per_person', 10, 2)->nullable()->after('sort_order')
                    ->comment('B2B kişi başı net fiyat (TRY)');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'original_price_per_person')) {
                $table->decimal('original_price_per_person', 10, 2)->nullable()->after('base_price_per_person')
                    ->comment('Üstü çizili eski fiyat (TRY)');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'currency')) {
                $table->string('currency', 8)->default('TRY')->after('original_price_per_person');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'duration_hours')) {
                $table->decimal('duration_hours', 4, 1)->nullable()->after('currency')
                    ->comment('Süre (saat)');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'departure_times')) {
                $table->json('departure_times')->nullable()->after('duration_hours')
                    ->comment('Kalkış saatleri — ["19:30 Biniş / 20:00 Kalkış"]');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'pier_name')) {
                $table->string('pier_name', 120)->nullable()->after('departure_times')
                    ->comment('Ana kalkış iskelesi');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'meeting_point')) {
                $table->string('meeting_point', 255)->nullable()->after('pier_name')
                    ->comment('Buluşma noktası açıklaması');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'max_pax')) {
                $table->unsignedSmallInteger('max_pax')->nullable()->after('meeting_point');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'badge_text')) {
                $table->string('badge_text', 60)->nullable()->after('max_pax')
                    ->comment('Kart üzeri rozet — "En Popüler"');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'rating')) {
                $table->decimal('rating', 3, 1)->nullable()->after('badge_text');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'review_count')) {
                $table->unsignedInteger('review_count')->default(0)->after('rating');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'long_description_tr')) {
                $table->text('long_description_tr')->nullable()->after('review_count');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'long_description_en')) {
                $table->text('long_description_en')->nullable()->after('long_description_tr');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'timeline_tr')) {
                $table->text('timeline_tr')->nullable()->after('long_description_en')
                    ->comment('Program akışı — JSON ya da metin');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'cancellation_policy_tr')) {
                $table->string('cancellation_policy_tr', 255)->nullable()->after('timeline_tr');
            }
            if (! Schema::hasColumn('leisure_package_templates', 'important_notes_tr')) {
                $table->json('important_notes_tr')->nullable()->after('cancellation_policy_tr');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leisure_package_templates')) {
            return;
        }

        $cols = [
            'base_price_per_person', 'original_price_per_person', 'currency',
            'duration_hours', 'departure_times', 'pier_name', 'meeting_point',
            'max_pax', 'badge_text', 'rating', 'review_count',
            'long_description_tr', 'long_description_en', 'timeline_tr',
            'cancellation_policy_tr', 'important_notes_tr',
        ];

        Schema::table('leisure_package_templates', function (Blueprint $table) use ($cols): void {
            foreach ($cols as $col) {
                if (Schema::hasColumn('leisure_package_templates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

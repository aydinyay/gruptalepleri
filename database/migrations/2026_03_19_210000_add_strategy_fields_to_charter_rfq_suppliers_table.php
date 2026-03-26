<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charter_rfq_suppliers', function (Blueprint $table): void {
            $table->string('supplier_kind', 30)->default('operator')->after('service_types');
            $table->json('charter_models')->nullable()->after('supplier_kind');
            $table->unsignedSmallInteger('min_pax')->nullable()->after('charter_models');
            $table->unsignedSmallInteger('max_pax')->nullable()->after('min_pax');
            $table->unsignedSmallInteger('priority')->default(100)->after('max_pax');
            $table->boolean('is_premium_only')->default(false)->after('priority');
            $table->boolean('is_cargo_operator')->default(false)->after('is_premium_only');
            $table->unsignedSmallInteger('min_notice_hours')->nullable()->after('is_cargo_operator');

            $table->index(['is_active', 'priority']);
            $table->index(['supplier_kind', 'is_cargo_operator']);
            $table->index(['min_pax', 'max_pax']);
        });

        $seedRows = [
            [
                'name' => 'Redstar Havacilik',
                'email' => 'info@redstar.com.tr',
                'phone' => '+90 216 585 50 00',
                'service_types' => ['jet'],
                'supplier_kind' => 'operator',
                'charter_models' => ['full_charter'],
                'min_pax' => 4,
                'max_pax' => 12,
                'priority' => 30,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 6,
                'is_active' => true,
                'notes' => 'Base SAW | Learjet45/Challenger | 7-8 pax medevac guclu.',
            ],
            [
                'name' => 'Bonair Havacilik',
                'email' => 'info@bonair.com.tr',
                'phone' => '+90 216 465 75 00',
                'service_types' => ['jet'],
                'supplier_kind' => 'operator',
                'charter_models' => ['full_charter'],
                'min_pax' => 8,
                'max_pax' => 18,
                'priority' => 35,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 12,
                'is_active' => true,
                'notes' => 'Challenger 605 | ust segment.',
            ],
            [
                'name' => 'General Aviation Services (GAS)',
                'email' => 'operations@gasaviation.com',
                'phone' => '+90 212 465 27 00',
                'service_types' => ['jet', 'helicopter', 'airliner'],
                'supplier_kind' => 'hybrid',
                'charter_models' => ['full_charter', 'acmi', 'block_seat'],
                'min_pax' => 4,
                'max_pax' => 220,
                'priority' => 40,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 8,
                'is_active' => true,
                'notes' => 'Hybrid operator+handling+broker | network guclu.',
            ],
            [
                'name' => 'Talon Air Turkey',
                'email' => 'charter@talonair.com',
                'phone' => '+1 631 589 2222',
                'service_types' => ['jet'],
                'supplier_kind' => 'operator',
                'charter_models' => ['full_charter'],
                'min_pax' => 6,
                'max_pax' => 18,
                'priority' => 55,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 24,
                'is_active' => true,
                'notes' => 'Long-range/yabanci pax | global hat.',
            ],
            [
                'name' => 'JetStory Aviation',
                'email' => 'sales@jetstory.com',
                'phone' => '+90 212 000 00 00',
                'service_types' => ['jet', 'helicopter', 'airliner'],
                'supplier_kind' => 'broker',
                'charter_models' => ['full_charter', 'acmi', 'block_seat'],
                'min_pax' => 2,
                'max_pax' => 220,
                'priority' => 60,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 6,
                'is_active' => true,
                'notes' => 'Broker agirlikli | genel hat degisebilir.',
            ],
            [
                'name' => 'THY Charter',
                'email' => 'charter@thy.com',
                'phone' => null,
                'service_types' => ['airliner'],
                'supplier_kind' => 'carrier',
                'charter_models' => ['full_charter', 'block_seat'],
                'min_pax' => 120,
                'max_pax' => 400,
                'priority' => 20,
                'is_premium_only' => true,
                'is_cargo_operator' => false,
                'min_notice_hours' => 24,
                'is_active' => true,
                'notes' => 'A320/B737/A330/B777 | VIP-devlet-yuksek butce segmenti.',
            ],
            [
                'name' => 'Pegasus Charter',
                'email' => 'charter@flypgs.com',
                'phone' => null,
                'service_types' => ['airliner'],
                'supplier_kind' => 'carrier',
                'charter_models' => ['full_charter', 'block_seat'],
                'min_pax' => 60,
                'max_pax' => 210,
                'priority' => 22,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 18,
                'is_active' => true,
                'notes' => 'B737 | grup charter fiyat/performans.',
            ],
            [
                'name' => 'SunExpress Charter',
                'email' => 'charter@sunexpress.com',
                'phone' => null,
                'service_types' => ['airliner'],
                'supplier_kind' => 'carrier',
                'charter_models' => ['full_charter', 'block_seat'],
                'min_pax' => 60,
                'max_pax' => 210,
                'priority' => 24,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 18,
                'is_active' => true,
                'notes' => 'B737 | tur operatoru charter, DE hatti guclu.',
            ],
            [
                'name' => 'Freebird Airlines',
                'email' => 'sales@freebirdairlines.com',
                'phone' => null,
                'service_types' => ['airliner'],
                'supplier_kind' => 'carrier',
                'charter_models' => ['full_charter', 'acmi'],
                'min_pax' => 50,
                'max_pax' => 220,
                'priority' => 28,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 18,
                'is_active' => true,
                'notes' => 'A320/A321 | charter + ACMI.',
            ],
            [
                'name' => 'Tailwind Airlines',
                'email' => 'info@tailwind.com.tr',
                'phone' => null,
                'service_types' => ['airliner'],
                'supplier_kind' => 'carrier',
                'charter_models' => ['full_charter', 'acmi'],
                'min_pax' => 50,
                'max_pax' => 210,
                'priority' => 30,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 18,
                'is_active' => true,
                'notes' => 'B737 | charter + wet lease.',
            ],
            [
                'name' => 'Corendon Airlines',
                'email' => 'sales@corendonairlines.com',
                'phone' => null,
                'service_types' => ['airliner'],
                'supplier_kind' => 'carrier',
                'charter_models' => ['full_charter', 'acmi', 'block_seat'],
                'min_pax' => 50,
                'max_pax' => 210,
                'priority' => 27,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 18,
                'is_active' => true,
                'notes' => 'B737 | charter/tur operatoru.',
            ],
            [
                'name' => 'Southwind Airlines',
                'email' => 'info@southwindairlines.com',
                'phone' => null,
                'service_types' => ['airliner'],
                'supplier_kind' => 'carrier',
                'charter_models' => ['full_charter'],
                'min_pax' => 80,
                'max_pax' => 330,
                'priority' => 35,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 24,
                'is_active' => true,
                'notes' => 'A321/A330 | yeni ama agresif.',
            ],
            [
                'name' => 'Turkish Cargo',
                'email' => 'cargo@thy.com',
                'phone' => null,
                'service_types' => ['airliner'],
                'supplier_kind' => 'cargo',
                'charter_models' => ['cargo'],
                'min_pax' => null,
                'max_pax' => null,
                'priority' => 25,
                'is_premium_only' => false,
                'is_cargo_operator' => true,
                'min_notice_hours' => 24,
                'is_active' => true,
                'notes' => 'Cargo only | B777F/A330F',
            ],
            [
                'name' => 'MNG Airlines',
                'email' => 'charter@mngairlines.com',
                'phone' => '+90 212 465 29 00',
                'service_types' => ['airliner'],
                'supplier_kind' => 'cargo',
                'charter_models' => ['cargo'],
                'min_pax' => null,
                'max_pax' => null,
                'priority' => 26,
                'is_premium_only' => false,
                'is_cargo_operator' => true,
                'min_notice_hours' => 24,
                'is_active' => true,
                'notes' => 'Cargo only | filo detayi teyit edilmeli.',
            ],
            [
                'name' => 'ACT Airlines',
                'email' => 'charter@mycargo.aero',
                'phone' => '+90 212 465 28 00',
                'service_types' => ['airliner'],
                'supplier_kind' => 'cargo',
                'charter_models' => ['cargo'],
                'min_pax' => null,
                'max_pax' => null,
                'priority' => 26,
                'is_premium_only' => false,
                'is_cargo_operator' => true,
                'min_notice_hours' => 24,
                'is_active' => true,
                'notes' => 'Cargo only | B747/IL-76',
            ],
            [
                'name' => 'Arkas',
                'email' => 'info@arkas.com.tr',
                'phone' => '+90 232 488 10 00',
                'service_types' => ['airliner'],
                'supplier_kind' => 'broker',
                'charter_models' => ['acmi'],
                'min_pax' => null,
                'max_pax' => null,
                'priority' => 250,
                'is_premium_only' => false,
                'is_cargo_operator' => false,
                'min_notice_hours' => 24,
                'is_active' => false,
                'notes' => 'Alan disi olabilir; pasif tutuldu, dogrulama sonrasi aktif edin.',
            ],
        ];

        foreach ($seedRows as $row) {
            $serviceTypes = $row['service_types'];
            $charterModels = $row['charter_models'];
            unset($row['service_types'], $row['charter_models']);

            $row['service_types'] = json_encode($serviceTypes, JSON_UNESCAPED_UNICODE);
            $row['charter_models'] = json_encode($charterModels, JSON_UNESCAPED_UNICODE);
            $row['updated_at'] = now();

            $exists = DB::table('charter_rfq_suppliers')->where('email', $row['email'])->exists();
            if ($exists) {
                DB::table('charter_rfq_suppliers')->where('email', $row['email'])->update($row);
            } else {
                $row['created_at'] = now();
                DB::table('charter_rfq_suppliers')->insert($row);
            }
        }
    }

    public function down(): void
    {
        Schema::table('charter_rfq_suppliers', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'priority']);
            $table->dropIndex(['supplier_kind', 'is_cargo_operator']);
            $table->dropIndex(['min_pax', 'max_pax']);

            $table->dropColumn([
                'supplier_kind',
                'charter_models',
                'min_pax',
                'max_pax',
                'priority',
                'is_premium_only',
                'is_cargo_operator',
                'min_notice_hours',
            ]);
        });
    }
};

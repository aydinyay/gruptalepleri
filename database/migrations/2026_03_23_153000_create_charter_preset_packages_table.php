<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('charter_preset_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('title');
            $table->string('summary')->nullable();
            $table->string('transport_type', 20);
            $table->string('from_iata', 10);
            $table->string('to_iata', 10);
            $table->string('from_label')->nullable();
            $table->string('to_label')->nullable();
            $table->string('aircraft_label')->nullable();
            $table->unsignedSmallInteger('suggested_pax')->default(1);
            $table->string('trip_type', 50)->default('Tek Yon');
            $table->string('group_type', 120)->nullable();
            $table->string('cabin_preference', 40)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 8)->default('EUR');
            $table->json('highlights_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['transport_type', 'is_active']);
        });

        $now = now();
        $rows = collect($this->defaultRows())
            ->map(function (array $row) use ($now): array {
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
                return $row;
            })
            ->all();

        if (! empty($rows)) {
            DB::table('charter_preset_packages')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('charter_preset_packages');
    }

    private function defaultRows(): array
    {
        return [
            [
                'code' => 'ist-ayt-economy-jet-6',
                'title' => 'Istanbul - Antalya Ekonomik Jet',
                'summary' => '6 kisiye kadar kisa/orta mesafe jet paketi.',
                'transport_type' => 'jet',
                'from_iata' => 'IST',
                'to_iata' => 'AYT',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Antalya Airport',
                'aircraft_label' => 'Cessna Citation CJ2 veya benzeri',
                'suggested_pax' => 6,
                'trip_type' => 'Tek Yon',
                'group_type' => 'VIP Tatil',
                'cabin_preference' => 'ekonomik_jet',
                'price' => 12000,
                'currency' => 'EUR',
                'highlights_json' => json_encode(['Hizli onay sureci', 'Kabinde ikram dahil', 'Kabine bagaj uygunlugu']),
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'saw-bod-economy-jet-6',
                'title' => 'Istanbul - Bodrum Ekonomik Jet',
                'summary' => 'Yaz sezonunda sik tercih edilen ekonomik jet paketi.',
                'transport_type' => 'jet',
                'from_iata' => 'SAW',
                'to_iata' => 'BJV',
                'from_label' => 'Sabiha Gokcen Airport',
                'to_label' => 'Milas-Bodrum Airport',
                'aircraft_label' => 'HondaJet / Phenom 300 sinifi',
                'suggested_pax' => 6,
                'trip_type' => 'Tek Yon',
                'group_type' => 'Tatil Grubu',
                'cabin_preference' => 'ekonomik_jet',
                'price' => 10900,
                'currency' => 'EUR',
                'highlights_json' => json_encode(['Marina transferine uygun slot', 'Esnek bagaj opsiyonu', 'VIP lounge destegi']),
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'ist-asr-mid-jet-8',
                'title' => 'Istanbul - Kayseri Orta Segment Jet',
                'summary' => 'Is seyahati ve dag rotalari icin dengeli paket.',
                'transport_type' => 'jet',
                'from_iata' => 'IST',
                'to_iata' => 'ASR',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Kayseri Airport',
                'aircraft_label' => 'Legacy 450 / Challenger 300 sinifi',
                'suggested_pax' => 8,
                'trip_type' => 'Tek Yon',
                'group_type' => 'Kurumsal',
                'cabin_preference' => 'farketmez',
                'price' => 16900,
                'currency' => 'EUR',
                'highlights_json' => json_encode(['Toplantiya uygun sessiz kabin', 'Wi-Fi hazirligi', 'Hizli boarding']),
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'code' => 'ist-dlm-vip-jet-8',
                'title' => 'Istanbul - Dalaman VIP Jet',
                'summary' => 'Premium servisli vip jet paketi.',
                'transport_type' => 'jet',
                'from_iata' => 'IST',
                'to_iata' => 'DLM',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Dalaman Airport',
                'aircraft_label' => 'Challenger 350 / Gulfstream G200',
                'suggested_pax' => 8,
                'trip_type' => 'Tek Yon',
                'group_type' => 'VIP Tatil',
                'cabin_preference' => 'vip_jet',
                'price' => 22400,
                'currency' => 'EUR',
                'highlights_json' => json_encode(['Premium catering', 'Kabinde toplanti masasi', 'Yuksek bagaj kapasitesi']),
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'code' => 'ist-izmir-economy-jet-6',
                'title' => 'Istanbul - Izmir Ekonomik Jet',
                'summary' => 'Kisa mesafede hizli operasyon icin ideal.',
                'transport_type' => 'jet',
                'from_iata' => 'IST',
                'to_iata' => 'ADB',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Izmir Adnan Menderes Airport',
                'aircraft_label' => 'Citation Mustang / CJ3',
                'suggested_pax' => 6,
                'trip_type' => 'Tek Yon',
                'group_type' => 'Kurumsal',
                'cabin_preference' => 'ekonomik_jet',
                'price' => 9800,
                'currency' => 'EUR',
                'highlights_json' => json_encode(['Ayni gun donus icin uygun', 'Hizli slot bulunurlugu', 'Dusuk operasyon maliyeti']),
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'code' => 'ist-ankara-heli-4',
                'title' => 'Istanbul - Ankara Helikopter Transfer',
                'summary' => 'Kisa ve acil nokta transferleri icin premium helikopter.',
                'transport_type' => 'helicopter',
                'from_iata' => 'IST',
                'to_iata' => 'ESB',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Ankara Esenboga Airport',
                'aircraft_label' => 'AW139 / Bell 429',
                'suggested_pax' => 4,
                'trip_type' => 'Tek Yon',
                'group_type' => 'VIP Transfer',
                'cabin_preference' => null,
                'price' => 14500,
                'currency' => 'EUR',
                'highlights_json' => json_encode(['Roof-top transfer koordinasyonu', 'Hizli kalkis penceresi', 'Esnek inis noktasi']),
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'code' => 'ist-izmir-airliner-70',
                'title' => 'Istanbul - Izmir Grup Charter Ucak',
                'summary' => 'Etkinlik ve ekip tasimalari icin tek sefer grup charter.',
                'transport_type' => 'airliner',
                'from_iata' => 'IST',
                'to_iata' => 'ADB',
                'from_label' => 'Istanbul Airport',
                'to_label' => 'Izmir Adnan Menderes Airport',
                'aircraft_label' => 'Embraer 190 / A319',
                'suggested_pax' => 70,
                'trip_type' => 'Tek Yon',
                'group_type' => 'Etkinlik Grubu',
                'cabin_preference' => null,
                'price' => 39000,
                'currency' => 'EUR',
                'highlights_json' => json_encode(['Toplu check-in planlamasi', 'Kurumsal branding opsiyonu', 'Yer hizmetleri paketi']),
                'is_active' => true,
                'sort_order' => 70,
            ],
        ];
    }
};

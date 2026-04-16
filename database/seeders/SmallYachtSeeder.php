<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SmallYachtSeeder extends Seeder
{
    public function run(): void
    {
        // LeisurePackageTemplate — small_yacht
        $existing = DB::table('leisure_package_templates')
            ->where('code', 'small_yacht')
            ->first();

        if (! $existing) {
            DB::table('leisure_package_templates')->insert([
                'product_type'              => 'yacht',
                'code'                      => 'small_yacht',
                'level'                     => 'small',
                'name_tr'                   => 'Küçük Özel Yat (1-10 Kişi)',
                'name_en'                   => 'Small Private Yacht (1-10 Pax)',
                'summary_tr'                => 'İstanbul Boğazı\'nda 1-10 kişiye özel, saatlik veya günlük yat charter. Özel kaptan, yakıt dahil.',
                'summary_en'                => 'Private yacht charter on Istanbul Bosphorus for 1-10 guests. Captain and fuel included.',
                'hero_image_url'            => null,
                'includes_tr'               => json_encode([
                    'Özel kaptan hizmeti',
                    'Yakıt ve marina ücreti dahil',
                    'Meşrubat ve su servisi',
                    'Can yeleği ve emniyet ekipmanları',
                    'Rota danışmanlığı (Boğaz, Prens Adaları, İstinye Koyu)',
                    'Ücretsiz iptal (24 saat öncesine kadar)',
                ]),
                'includes_en'               => json_encode([
                    'Private captain service',
                    'Fuel and marina fees included',
                    'Soft drinks and water',
                    'Life jackets and safety equipment',
                    'Route advisory (Bosphorus, Princes Islands, Istinye Bay)',
                    'Free cancellation up to 24 hours before',
                ]),
                'excludes_tr'               => json_encode([
                    'Alkollü içecekler (talep üzerine temin edilebilir)',
                    'Yemek servisi (ilave ücretle sunulabilir)',
                    'Transfer hizmeti (isteğe bağlı eklenebilir)',
                ]),
                'excludes_en'               => json_encode([
                    'Alcoholic beverages (available on request)',
                    'Catering service (available at extra cost)',
                    'Transfer service (optional add-on)',
                ]),
                'base_price_per_person'     => 450.00,
                'original_price_per_person' => null,
                'currency'                  => 'EUR',
                'duration_hours'            => 4.0,
                'departure_times'           => json_encode(['Esnek — talep saatine göre']),
                'pier_name'                 => 'İstanbul Boğazı (marina seçimi rezervasyonda belirlenir)',
                'meeting_point'             => 'Seçilen marinada kaptan tarafından karşılama',
                'max_pax'                   => 10,
                'badge_text'                => 'Özel & Sessiz',
                'rating'                    => 4.8,
                'review_count'              => 318,
                'long_description_tr'       => "İstanbul Boğazı'nda tamamen size ait, küçük ve özel bir yat deneyimi yaşayın.\n\nSadece sizin grubunuza özel tahsis edilen bu teknede Boğaz'ın eşsiz silüetini kalabalıktan uzak, huzurla keşfedebilirsiniz. Ortaköy Köprüsü, Rumeli Hisarı, Anadolu Kavağı ve Prens Adaları gibi ikonik noktalara uğramanız mümkün.\n\nDeneyimli kaptanımız rota konusunda size rehberlik eder. Minimum 4 saatlik charter imkânı sunulmaktadır; rezervasyona göre tam gün seçenekler de mevcuttur.",
                'long_description_en'       => null,
                'timeline_tr'               => null,
                'cancellation_policy_tr'    => 'Hizmet tarihinden 24 saat öncesine kadar ücretsiz iptal',
                'important_notes_tr'        => json_encode([
                    'Fiyatlar saatlik bazlıdır; minimum 4 saat geçerlidir.',
                    'Grup büyüklüğü ve tarih seçimine göre fiyat değişebilir.',
                    'Yemek servisi ilave ücretle sunulabilir.',
                ]),
                'is_active'                 => true,
                'sort_order'                => 10,
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }

        $templateId = DB::table('leisure_package_templates')
            ->where('code', 'small_yacht')
            ->value('id');

        // CatalogItem — kucuk-ozel-yat-1-10-kisi
        $itemExists = DB::table('catalog_items')
            ->where('slug', 'kucuk-ozel-yat-1-10-kisi')
            ->exists();

        if (! $itemExists) {
            // Ensure category 9 (Yat Kiralama) exists
            $catId = DB::table('catalog_categories')->where('id', 9)->value('id');

            DB::table('catalog_items')->insert([
                'category_id'        => $catId ?? null,
                'owner_type'         => 'platform',
                'supplier_id'        => null,
                'product_type'       => 'leisure',
                'reference_type'     => 'leisure_package_template',
                'reference_id'       => $templateId,
                'title'              => 'İstanbul Boğazı: Küçük Özel Yat Charter (1-10 Kişi)',
                'slug'               => 'kucuk-ozel-yat-1-10-kisi',
                'short_desc'         => 'Boğaz\'da tamamen size özel küçük yat deneyimi. Özel kaptan, yakıt ve meşrubat dahil. 1-10 kişi kapasiteli.',
                'full_desc'          => "İstanbul Boğazı'nda unutulmaz bir deniz deneyimi yaşamak isteyenler için özel yat charter hizmeti.\n\nTamamen size ait olan yatta, Boğaz'ın eşsiz manzarasını özgürce keşfedebilirsiniz. Ortaköy'den Anadolu Feneri'ne, Prens Adaları'ndan İstinye Koyu'na kadar istediğiniz rotada seyahat edebilirsiniz.\n\nDeneyimli kaptanımız rota planlamasında size eşlik eder. Yakıt, marina ücreti ve meşrubat servisi fiyata dahildir.\n\nFiyatlandırma kişi sayısına, süreye ve seçilen rotaya göre kişiselleştirilir. Teklif almak için hemen iletişime geçin.",
                'cover_image'        => null,
                'gallery_json'       => null,
                'pricing_type'       => 'quote',
                'base_price'         => null,
                'currency'           => 'EUR',
                'is_active'          => true,
                'is_featured'        => true,
                'is_published'       => true,
                'published_at'       => now(),
                'destination_city'   => 'İstanbul',
                'destination_country'=> 'Türkiye',
                'duration_days'      => null,
                'duration_hours'     => 4,
                'min_pax'            => 1,
                'max_pax'            => 10,
                'sort_order'         => 95,
                'rating_avg'         => 4.82,
                'review_count'       => 318,
                'meta_title'         => 'İstanbul Boğazı Küçük Özel Yat Charter (1-10 Kişi) | gruprezervasyonlari.com',
                'meta_description'   => 'İstanbul Boğazı\'nda 1-10 kişiye özel yat charter. Kaptan, yakıt ve meşrubat dahil. Boğaz, Prens Adaları ve özel rota seçenekleri.',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }
    }
}

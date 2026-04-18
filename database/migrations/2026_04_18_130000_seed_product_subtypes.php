<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Slug bazlı kesin atamalar (30 mevcut ürün)
        $slugSubtypes = [
            // Transfers
            'business-van-transfer'                         => 'airport_transfer',
            'istanbul-havalimani-taksim-transfer'           => 'airport_transfer',
            'minibus-transfer'                              => 'intercity_transfer',
            'antalya-havalimani-lara-ve-kundu-transfer'     => 'airport_transfer',
            'izmir-adnan-menderes-havalimani-transfer'      => 'airport_transfer',
            'trabzon-havalimani-uzungol-transfer'           => 'airport_transfer',

            // Charter
            'istanbul-antalya-ekonomik-jet-paketi'         => 'private_jet',
            'istanbul-izmir-ekonomik-jet-paketi'           => 'private_jet',
            'istanbul-uzeri-helikopter-deneyimi'           => 'helicopter_tour',
            'ozel-jet-kiralama-bodrum-dalaman'             => 'private_jet',
            'ozel-jet-kiralama-istanbul-antalya'           => 'private_jet',

            // Leisure
            'alkollu-dinner-cruise'                        => 'dinner_cruise',
            'alkolsuz-dinner-cruise'                       => 'dinner_cruise',
            'bodrum-cikisli-10-kisilik-motorbot-turu'      => 'yacht_charter',
            'fethiye-12-adalar-gunubirlik-tekne-turu'      => 'day_tour',
            'istanbul-turk-gecesi-gosteri-aksamyemegi'     => 'evening_show',
            'izmir-cesme-marina-8-kisilik-yat-kiralama'    => 'yacht_charter',

            // Tours
            'gunubirlik-bursa-turu'                        => 'day_tour',
            'gunubirlik-sapanca-masukiye-turu'             => 'day_tour',
            'kucuk-ozel-yat-1-10-kisi'                    => 'yacht_charter',
            'orta-boy-ozel-yat-10-20-kisi'                => 'yacht_charter',
            'antalya-rafting-jeep-safari-ve-selaleler'     => 'activity_tour',
            'istanbul-cikisli-balkanlar-turu'              => 'multi_day_tour',
            'kapadokya-3-gece-4-gun-balon-turu-paketi'     => 'multi_day_tour',
            'pamukkale-ve-efes-2-gun-1-gece'               => 'multi_day_tour',

            // Hotel
            'istanbul-sehir-oteli-grup-konaklamalari'      => 'hotel_room',
            'kapadokya-magara-oteli-balon-turu-paketi'     => 'hotel_room',

            // Visa
            'schengen-vize-danismanligi-eksiksiz-paket'    => 'visa_service',
            'turistik-vize-basvuru-destegi'                => 'visa_service',

            // Other
            'kurumsal-etkinlik-paketi-bogaz-turu'          => 'corporate_event',
        ];

        foreach ($slugSubtypes as $slug => $subtype) {
            DB::table('catalog_items')
                ->where('slug', $slug)
                ->update(['product_subtype' => $subtype]);
        }

        // Slug eşleşmeyenler için product_type bazlı akıllı fallback
        DB::table('catalog_items')
            ->whereNull('product_subtype')
            ->where('product_type', 'transfer')
            ->update(['product_subtype' => 'airport_transfer']);

        DB::table('catalog_items')
            ->whereNull('product_subtype')
            ->where('product_type', 'charter')
            ->update(['product_subtype' => 'private_jet']);

        DB::table('catalog_items')
            ->whereNull('product_subtype')
            ->where('product_type', 'leisure')
            ->update(['product_subtype' => 'dinner_cruise']);

        DB::table('catalog_items')
            ->whereNull('product_subtype')
            ->where('product_type', 'tour')
            ->update(['product_subtype' => 'day_tour']);

        DB::table('catalog_items')
            ->whereNull('product_subtype')
            ->where('product_type', 'hotel')
            ->update(['product_subtype' => 'hotel_room']);

        DB::table('catalog_items')
            ->whereNull('product_subtype')
            ->where('product_type', 'visa')
            ->update(['product_subtype' => 'visa_service']);

        DB::table('catalog_items')
            ->whereNull('product_subtype')
            ->where('product_type', 'other')
            ->update(['product_subtype' => 'corporate_event']);
    }

    public function down(): void
    {
        DB::table('catalog_items')->update(['product_subtype' => null]);
    }
};

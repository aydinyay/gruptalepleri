<?php

namespace Database\Seeders;

use App\Models\SistemOlaySablon;
use Illuminate\Database\Seeder;

class SistemOlaySeeder extends Seeder
{
    public function run(): void
    {
        $olaylar = [
            [
                'olay_kodu'   => 'teklif_eklendi',
                'olay_adi'    => 'Teklif Eklendi (Acenteye)',
                'alici'       => 'acente',
                'email_konu'  => null,
                'email_govde' => null,
                'sms_govde'   => null,
                'degiskenler' => ['gtpnr', 'havayolu', 'link'],
            ],
            [
                'olay_kodu'   => 'durum_degisti',
                'olay_adi'    => 'Durum Değişti (Acenteye)',
                'alici'       => 'acente',
                'email_konu'  => null,
                'email_govde' => null,
                'sms_govde'   => null,
                'degiskenler' => ['gtpnr', 'eski_durum', 'yeni_durum', 'link'],
            ],
            [
                'olay_kodu'   => 'hosgeldiniz',
                'olay_adi'    => 'Hoş Geldiniz (Acenteye)',
                'alici'       => 'acente',
                'email_konu'  => null,
                'email_govde' => null,
                'sms_govde'   => null,
                'degiskenler' => ['sirket_adi', 'ad_soyad', 'link'],
            ],
            [
                'olay_kodu'   => 'yeni_talep',
                'olay_adi'    => 'Yeni Talep (Admin)',
                'alici'       => 'admin',
                'email_konu'  => null,
                'email_govde' => null,
                'sms_govde'   => null,
                'degiskenler' => ['gtpnr', 'acente_adi', 'pax', 'link'],
            ],
            [
                'olay_kodu'   => 'teklif_kabul',
                'olay_adi'    => 'Teklif Kabul Edildi (Admin)',
                'alici'       => 'admin',
                'email_konu'  => null,
                'email_govde' => null,
                'sms_govde'   => null,
                'degiskenler' => ['gtpnr', 'acente_adi', 'havayolu', 'link'],
            ],
            [
                'olay_kodu'   => 'opsiyon_uyarisi',
                'olay_adi'    => 'Opsiyon Uyarısı (Admin)',
                'alici'       => 'admin',
                'email_konu'  => null,
                'email_govde' => null,
                'sms_govde'   => null,
                'degiskenler' => ['gtpnr', 'havayolu', 'saat_kaldi', 'bitis', 'link'],
            ],
            [
                'olay_kodu'   => 'yeni_acente',
                'olay_adi'    => 'Yeni Acente Kaydı (Admin)',
                'alici'       => 'admin',
                'email_konu'  => null,
                'email_govde' => null,
                'sms_govde'   => null,
                'degiskenler' => ['sirket_adi', 'ad_soyad', 'telefon', 'email', 'link'],
            ],
        ];

        foreach ($olaylar as $olay) {
            SistemOlaySablon::updateOrCreate(
                ['olay_kodu' => $olay['olay_kodu']],
                $olay
            );
        }

        $this->command->info('SistemOlaySeeder tamamlandi: ' . count($olaylar) . ' olay eklendi.');
    }
}

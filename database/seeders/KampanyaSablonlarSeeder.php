<?php

namespace Database\Seeders;

use App\Models\KampanyaSablon;
use Illuminate\Database\Seeder;

class KampanyaSablonlarSeeder extends Seeder
{
    public function run(): void
    {
        // Standart Davet
        if (!KampanyaSablon::where('ad', 'Standart Davet')->exists()) {
            KampanyaSablon::create([
                'ad'          => 'Standart Davet',
                'tip'         => 'email',
                'konu'        => 'GrupTalepleri.com — Platforma Davet',
                'html_icerik' => $this->standartDavetHtml(),
                'aktif'       => true,
            ]);
        }

        // Yeni Acente Tebrik
        if (!KampanyaSablon::where('ad', 'Yeni Acente Tebrik')->exists()) {
            KampanyaSablon::create([
                'ad'          => 'Yeni Acente Tebrik',
                'tip'         => 'email',
                'konu'        => 'Hayırlı Olsun! GrupTalepleri\'nden tebrikler 🎉',
                'html_icerik' => $this->yeniAcenteHtml(),
                'aktif'       => true,
            ]);
        }
    }

    private function standartDavetHtml(): string
    {
        // blade şablonunu render ederek alıyoruz
        return view('emails.tursab_davet', [
            'acenteUnvani' => '{{acente_adi}}',
            'belgeNo'      => '{{belge_no}}',
            'kayitUrl'     => '{{kayit_url}}',
            'aiParagraf'   => '',
        ])->render();
    }

    private function yeniAcenteHtml(): string
    {
        return view('emails.tursab_davet_yeni_acente', [
            'acenteUnvani' => '{{acente_adi}}',
            'belgeNo'      => '{{belge_no}}',
            'kayitUrl'     => '{{kayit_url}}',
            'aiParagraf'   => '',
        ])->render();
    }
}
// deploy

<?php

namespace App\Services;

class PaoNetHelper
{
    // ── Kimlik Tipi Algılama ───────────────────────────────────────────────────

    public static function detectKimlikTipi(string $deger): string
    {
        $clean = preg_replace('/\s+/', '', $deger);
        if (preg_match('/^[0-9]{11}$/', $clean)) {
            return 'tc';
        }
        return 'pasaport';
    }

    public static function urunKodu(string $kimlikTipi): string
    {
        return $kimlikTipi === 'tc' ? 'NPN302' : 'NPN220';
    }

    // ── InsurSeq Builder ──────────────────────────────────────────────────────
    // Format: "ADI;SOYADI;BABA_ADI;DOGUM_TARIHI;DOGUM_YERI;CINSIYET"

    public static function buildInsurSeq(
        string $adi,
        string $soyadi,
        string $babaAdi,
        string $dogumTarihi,  // Y-m-d
        string $dogumYeri,
        string $cinsiyet      // E / K
    ): string {
        return implode(';', [
            mb_strtoupper(trim($adi),    'UTF-8'),
            mb_strtoupper(trim($soyadi), 'UTF-8'),
            mb_strtoupper(trim($babaAdi),'UTF-8'),
            $dogumTarihi,
            mb_strtoupper(trim($dogumYeri), 'UTF-8'),
            strtoupper($cinsiyet),
        ]);
    }

    // ── AdresSeq Builder ──────────────────────────────────────────────────────

    public static function buildAdresSeq(
        string $ilKod,
        string $ilAdi,
        string $ilceKod,
        string $ilceAdi,
        string $semtAdi  = '',
        string $adres    = ''
    ): string {
        return implode(';', [
            $ilKod,
            mb_strtoupper(trim($ilAdi),  'UTF-8'),
            $ilceKod,
            mb_strtoupper(trim($ilceAdi),'UTF-8'),
            mb_strtoupper(trim($semtAdi),'UTF-8'),
            trim($adres),
        ]);
    }

    // ── NPN302 / NPN220 StrMsg Builder ────────────────────────────────────────

    public static function buildStrMsg(array $params): string
    {
        return implode('|', array_map(
            fn ($k, $v) => "{$k}={$v}",
            array_keys($params),
            array_values($params)
        ));
    }

    // ── Fiyat Hesaplama ───────────────────────────────────────────────────────
    // Satış = (Bprim × Dkuru) × (1 + (Markup + Tampon) / 100)

    public static function hesaplaSatisFiyati(
        float $bprim,
        float $dkuru,
        float $markupYuzde,
        float $tamponYuzde
    ): array {
        $maliyetTl  = $bprim * $dkuru;
        $satisFiyat = $maliyetTl * (1 + ($markupYuzde + $tamponYuzde) / 100);

        return [
            'maliyet_tl'    => round($maliyetTl, 2),
            'satis_fiyat'   => round($satisFiyat, 2),
            'net_kar'       => round($satisFiyat - $maliyetTl, 2),
        ];
    }

    // ── Cinsiyet Normalize ────────────────────────────────────────────────────

    public static function cinsiyetKodu(string $cinsiyet): string
    {
        $c = mb_strtolower(trim($cinsiyet), 'UTF-8');
        if (in_array($c, ['k', 'kadin', 'kadın', 'f', 'female', 'kız'], true)) return 'K';
        return 'E';
    }

    // ── Tarih Formatı ─────────────────────────────────────────────────────────

    public static function formatDate(string $tarih): string
    {
        // Y-m-d → d.m.Y (PAO-Net bazı endpoint'lerde bunu bekler)
        try {
            return \Carbon\Carbon::parse($tarih)->format('d.m.Y');
        } catch (\Throwable) {
            return $tarih;
        }
    }

    // ── PDF URL Normalize (backslash → forward slash) ─────────────────────────

    public static function normalizePdfUrl(string $url): string
    {
        return str_replace('\\', '/', $url);
    }
}

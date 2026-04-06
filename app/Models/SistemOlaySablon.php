<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SistemOlaySablon extends Model
{
    protected $table = 'sistem_olay_sablonlari';

    protected $fillable = [
        'olay_kodu',
        'olay_adi',
        'alici',
        'email_konu',
        'email_govde',
        'sms_govde',
        'email_aktif',
        'sms_aktif',
        'degiskenler',
    ];

    protected $casts = [
        'degiskenler'  => 'array',
        'email_aktif'  => 'boolean',
        'sms_aktif'    => 'boolean',
    ];

    /**
     * Verilen olay kodu için email şablonu varsa render edilmiş HTML + konu döndür.
     * Yoksa (govde null) null döndür → caller Blade şablonuna düşer.
     */
    public static function resolveEmail(string $olayKodu, array $data): ?array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sistem_olay_sablonlari')) {
            return null;
        }

        $sablon = static::where('olay_kodu', $olayKodu)->first();

        if (!$sablon || !$sablon->email_aktif || !$sablon->email_govde) {
            return null;
        }

        $html  = static::replaceVars($sablon->email_govde, $data);
        $konu  = $sablon->email_konu
            ? static::replaceVars($sablon->email_konu, $data)
            : null;

        return ['html' => $html, 'konu' => $konu];
    }

    /**
     * Verilen olay kodu için SMS metni varsa döndür, yoksa null.
     */
    public static function resolveSms(string $olayKodu, array $data): ?string
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('sistem_olay_sablonlari')) {
            return null;
        }

        $sablon = static::where('olay_kodu', $olayKodu)->first();

        if (!$sablon || !$sablon->sms_aktif || !$sablon->sms_govde) {
            return null;
        }

        return static::replaceVars($sablon->sms_govde, $data);
    }

    private static function replaceVars(string $metin, array $data): string
    {
        foreach ($data as $key => $val) {
            $metin = str_replace('{' . $key . '}', (string) $val, $metin);
        }
        return $metin;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShortLinkService
{
    /**
     * Verilen URL için kısa link oluşturur veya mevcutsa döner.
     * Örnek: https://gruptalepleri.com/s/aB3xZ9
     */
    public function make(string $url, string $context = '', ?int $contextId = null, ?int $expireDays = 365): string
    {
        // Aynı URL + context için mevcut kodu döndür
        $existing = DB::table('short_links')
            ->where('url', $url)
            ->where('context', $context ?: null)
            ->where('context_id', $contextId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->value('code');

        if ($existing) {
            return route('short.redirect', $existing);
        }

        // Benzersiz kod üret
        do {
            $code = Str::random(6);
        } while (DB::table('short_links')->where('code', $code)->exists());

        DB::table('short_links')->insert([
            'code'       => $code,
            'url'        => $url,
            'context'    => $context ?: null,
            'context_id' => $contextId,
            'expires_at' => $expireDays ? now()->addDays($expireDays) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return route('short.redirect', $code);
    }

    /**
     * Poliçe PDF'i için hazır short link üretir.
     */
    public function forPolice(int $policeId, string $kanal, string $tip = 'police'): string
    {
        $url = $kanal === 'b2b'
            ? route('acente.sigorta.belge', ['police' => $policeId, 'tip' => $tip])
            : route('b2c.sigorta.belge',   ['police' => $policeId, 'tip' => $tip]);

        return $this->make($url, "sigorta_{$tip}", $policeId, 365);
    }
}

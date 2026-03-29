<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\Request as GrupTalep;
use App\Models\SistemAyar;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TuraiController extends Controller
{
    public function chat(Request $request, string $gtpnr): JsonResponse
    {
        try {
            $user = auth()->user();

            $talep = GrupTalep::where('gtpnr', $gtpnr)
                ->where('user_id', $user->id)
                ->with(['segments', 'offers' => fn ($q) => $q->where(fn ($q2) => $q2->where('is_visible', true)->orWhere('is_accepted', true)), 'payments'])
                ->first();

            if (! $talep) {
                return response()->json(['hata' => 'Talep bulunamadı.'], 404);
            }

            $digerTalepler = GrupTalep::where('user_id', $user->id)
                ->where('gtpnr', '!=', $gtpnr)
                ->with(['segments', 'offers' => fn ($q) => $q->where(fn ($q2) => $q2->where('is_visible', true)->orWhere('is_accepted', true))])
                ->latest()
                ->limit(40)
                ->get();

            $gecmis = array_slice($request->input('gecmis', []), -12);
            $mesaj  = trim($request->input('mesaj', ''));

            if ($mesaj === '') {
                return response()->json(['hata' => 'Mesaj boş olamaz.'], 422);
            }

            $apiKey = (string) config('services.gemini.key');
            if ($apiKey === '') {
                return response()->json(['hata' => 'AI servisi şu an kullanılamıyor.'], 503);
            }

            $model   = (string) config('services.gemini.text_model', 'gemini-2.5-flash');
            $context = $this->buildContext($talep, $digerTalepler);
            $yanit   = $this->geminiChat($context, $gecmis, $mesaj, $apiKey, $model);

            return response()->json(['yanit' => $yanit]);

        } catch (\Exception $e) {
            return response()->json(['hata' => 'Sunucu hatası: ' . $e->getMessage()], 500);
        }
    }

    // ── Acil SMS gönder ────────────────────────────────────────────────────────
    public function acilSms(Request $request, string $gtpnr): JsonResponse
    {
        try {
            $user = auth()->user();

            $talep = GrupTalep::where('gtpnr', $gtpnr)
                ->where('user_id', $user->id)
                ->with('segments')
                ->first();

            if (! $talep) {
                return response()->json(['hata' => 'Talep bulunamadı.'], 404);
            }

            $rota    = $talep->segments->map(fn ($s) => "{$s->from_iata}→{$s->to_iata}")->implode(' / ');
            $tarih   = $talep->segments->first()?->departure_date ?? '-';
            $acente  = $user->name ?? 'Acente';
            $acenteTel = $user->phone ?? '-';

            $mesaj = "🆘 ACİL DESTEK\n"
                . "Acente: {$acente}\n"
                . "Talep : {$gtpnr}\n"
                . "Rota  : {$rota} | {$tarih}\n"
                . "Tel   : {$acenteTel}\n"
                . "Hemen aranmak istiyor.";

            $sms      = new \App\Services\SmsService();
            $hedefler = [];

            // 1. Superadmin User'ının phone alanı
            $superadminUser = \App\Models\User::where('role', 'superadmin')
                ->whereNotNull('phone')->first();
            if ($superadminUser?->phone) {
                $hedefler[$superadminUser->phone] = 'SuperAdmin';
            }

            // 2. SistemAyar sirket_cep (farklıysa ekle)
            $cepRaw = preg_replace('/[^0-9]/', '', (string) SistemAyar::get('sirket_cep', ''));
            if (strlen($cepRaw) === 11 && str_starts_with($cepRaw, '0')) {
                $cepRaw = '90' . substr($cepRaw, 1); // 05324262630 → 905324262630
            } elseif (strlen($cepRaw) === 10) {
                $cepRaw = '90' . $cepRaw; // 5324262630 → 905324262630
            }
            if ($cepRaw && ! isset($hedefler[$cepRaw])) {
                $hedefler[$cepRaw] = 'SuperAdmin Cep';
            }

            if (empty($hedefler)) {
                $acilNo = SistemAyar::get('sirket_cep', SistemAyar::get('sirket_telefon', ''));
                return response()->json(['mesaj' => "⚠️ SMS alıcısı tanımlı değil. Lütfen doğrudan arayın: {$acilNo}"]);
            }

            $gonderildi = false;
            foreach ($hedefler as $phone => $name) {
                if ($sms->send($talep->id, 'admin', $name, (string) $phone, $mesaj)) {
                    $gonderildi = true;
                }
            }

            if ($gonderildi) {
                return response()->json(['mesaj' => "✅ Acil SMS gönderildi. En kısa sürede sizi arayacağız."]);
            }

            $acilNo = SistemAyar::get('sirket_cep', SistemAyar::get('sirket_telefon', ''));
            return response()->json(['mesaj' => "⚠️ SMS gönderilemedi. Lütfen doğrudan arayın: {$acilNo}"]);

        } catch (\Exception $e) {
            return response()->json(['hata' => 'SMS gönderilemedi: ' . $e->getMessage()], 500);
        }
    }

    // ── Bağlam oluşturucu ──────────────────────────────────────────────────────
    private function buildContext(GrupTalep $talep, $digerTalepler): string
    {
        $user = auth()->user();
        $now  = Carbon::now('Europe/Istanbul');

        // ── Şirket/iletişim/banka bilgileri ──
        $sirketUnvan  = SistemAyar::get('sirket_unvan', 'Grup Talepleri Turizm San. ve Tic. Ltd. Şti.');
        $whatsapp     = SistemAyar::get('sirket_whatsapp', '+90 535 415 47 99');
        $eposta       = SistemAyar::get('sirket_eposta', 'destek@gruptalepleri.com');
        $telefon      = SistemAyar::get('sirket_telefon', '+90 535 415 47 99');

        // ── Admin telefon numaraları (users tablosundan) ──
        $adminUsers = \App\Models\User::whereIn('role', ['admin', 'superadmin'])
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->orderByRaw("role = 'superadmin' DESC")
            ->get(['name', 'phone', 'role']);
        $adminTelStr = '';
        foreach ($adminUsers as $au) {
            $label = $au->role === 'superadmin' ? 'Süperadmin' : 'Admin';
            $adminTelStr .= "{$label} ({$au->name}): [{$au->phone}](tel:{$au->phone})\n";
        }
        if ($adminTelStr === '') {
            $adminTelStr = "Telefon: [{$telefon}](tel:{$telefon})\n";
        }

        // Çoklu banka hesapları
        $bankaHesaplari = [];
        for ($i = 1; $i <= 4; $i++) {
            $iban = (string) SistemAyar::get("banka_iban_{$i}", $i === 1 ? SistemAyar::get('banka_iban', '') : '');
            if (empty(trim($iban))) continue;
            $bankaHesaplari[] = [
                'doviz'   => SistemAyar::get("banka_doviz_{$i}", 'TRY'),
                'adi'     => SistemAyar::get("banka_adi_{$i}", SistemAyar::get('banka_adi', '')),
                'sube'    => SistemAyar::get("banka_sube_{$i}", SistemAyar::get('banka_sube', '')),
                'sahip'   => SistemAyar::get("banka_hesap_sahibi_{$i}", SistemAyar::get('banka_hesap_sahibi', $sirketUnvan)),
                'iban'    => "TR{$iban}",
                'not'     => SistemAyar::get("banka_aciklama_{$i}", SistemAyar::get('banka_aciklama', 'Açıklama kısmına GTPNR numaranızı yazınız.')),
            ];
        }

        // ── Mevcut talep segmentleri ──
        $segmentStr = '';
        foreach ($talep->segments as $i => $seg) {
            $segmentStr .= ($i + 1) . ". {$seg->from_iata}({$seg->from_city}) → {$seg->to_iata}({$seg->to_city}) | {$seg->departure_date}" . ($seg->departure_time ? " {$seg->departure_time}" : '') . "\n";
        }

        // ── Teklifler — kabul edilmiş varsa sadece onu göster ──
        $kabulTeklif  = $talep->offers->where('is_accepted', true)->first();
        $gosterOffers = $kabulTeklif
            ? $talep->offers->where('is_accepted', true)          // sadece kabul edilen
            : $talep->offers->where('is_visible', true);          // henüz kabul yok → görünenler

        $teklifStr = '';
        foreach ($gosterOffers as $i => $o) {
            $durum = $o->is_accepted ? '✅ Kabul Edildi' : 'Bekliyor';
            $opsiyon = ($o->option_date ? $o->option_date : '-') . ($o->option_time ? " {$o->option_time}" : '');

            // Opsiyon geri sayım
            $geriSayim = '';
            if ($o->option_date) {
                try {
                    $opsDate = Carbon::parse($o->option_date . ($o->option_time ? " {$o->option_time}" : ' 23:59'), 'Europe/Istanbul');
                    $diff = $now->diffInMinutes($opsDate, false);
                    if ($diff < 0) {
                        $geriSayim = ' ⚠️ SÜRESI DOLDU';
                    } elseif ($diff < 60) {
                        $geriSayim = " ⚠️ {$diff} DAKİKA KALDI";
                    } elseif ($diff < 1440) {
                        $geriSayim = ' ⚠️ ' . round($diff / 60) . ' SAAT KALDI';
                    } else {
                        $geriSayim = ' (' . round($diff / 1440) . ' gün kaldı)';
                    }
                } catch (\Exception $e) {}
            }

            $teklifStr .= ($i + 1) . ". {$o->airline} | {$o->price_per_pax} {$o->currency}/kişi | Toplam: {$o->total_price} {$o->currency}";
            if ($o->deposit_amount) {
                $teklifStr .= " | Depozito: {$o->deposit_amount} {$o->currency} (%{$o->deposit_rate})";
            }
            $teklifStr .= " | Opsiyon: {$opsiyon}{$geriSayim} | {$durum}\n";
            if ($o->offer_text) {
                $teklifStr .= "   Not: " . mb_substr(strip_tags($o->offer_text), 0, 200) . "\n";
            }
        }
        if ($teklifStr === '') $teklifStr = "Henüz teklif girilmemiş.\n";

        // ── Ödeme planı ──
        $odemePlanStr = '';
        $toplamTutar  = 0;
        $odenenTutar  = 0;
        $currency     = $kabulTeklif?->currency ?? 'TRY';

        if ($kabulTeklif) {
            $toplamTutar = (float) $kabulTeklif->total_price;
        }

        foreach ($talep->payments as $i => $p) {
            $durum = match ($p->status) {
                'alindi'    => '✅ Alındı',
                'bekleniyor'=> '⏳ Bekleniyor',
                'iade'      => '↩️ İade',
                default     => $p->status,
            };

            if ($p->status === 'alindi') $odenenTutar += (float) $p->amount;

            $satirlar = ($i + 1) . ". ";
            $satirlar .= match ($p->payment_type ?? '') {
                'depozito' => 'Depozito',
                'bakiye'   => 'Bakiye',
                'full'     => 'Tam Ödeme',
                default    => ($p->payment_type ?? 'Ödeme'),
            };
            $satirlar .= " | {$p->amount} {$p->currency} | {$durum}";
            if ($p->due_date) {
                $dueDate = Carbon::parse($p->due_date, 'Europe/Istanbul');
                $gecti   = $dueDate->isPast() && $p->status === 'bekleniyor' ? ' ⚠️ TARİH GEÇTİ!' : '';
                $satirlar .= " | Son: {$p->due_date}{$gecti}";
            }
            if ($p->payment_date) $satirlar .= " | Ödeme: {$p->payment_date}";
            $odemePlanStr .= $satirlar . "\n";
        }
        if ($odemePlanStr === '') $odemePlanStr = "Ödeme planı henüz oluşturulmamış.\n";

        $kalanTutar = max(0, $toplamTutar - $odenenTutar);
        $tahsilatYuzde = $toplamTutar > 0 ? round(($odenenTutar / $toplamTutar) * 100) : 0;

        // ── Diğer talepler ──
        $digerStr = '';
        foreach ($digerTalepler as $dt) {
            $rota = $dt->segments->map(fn ($s) => "{$s->from_iata}→{$s->to_iata}")->implode(' / ');
            $tarih = $dt->segments->first()?->departure_date ?? '-';
            $teklifSayisi = $dt->offers->count();
            $enIyiTeklif = $dt->offers->where('is_accepted', true)->first()
                ?? $dt->offers->sortBy('total_price')->first();
            $fiyat = $enIyiTeklif ? " | {$enIyiTeklif->total_price} {$enIyiTeklif->currency}" : '';

            // Opsiyon
            $opsiyonStr = '';
            $kabulOffer = $dt->offers->where('is_accepted', true)->first();
            if ($kabulOffer?->option_date) {
                try {
                    $opsDate = Carbon::parse($kabulOffer->option_date . ($kabulOffer->option_time ? " {$kabulOffer->option_time}" : ' 23:59'), 'Europe/Istanbul');
                    $diff = $now->diffInMinutes($opsDate, false);
                    if ($diff < 0) $opsiyonStr = ' | ⚠️ Opsiyon doldu';
                    elseif ($diff < 1440) $opsiyonStr = ' | ⚠️ Opsiyon: ' . round($diff / 60) . ' saat';
                    else $opsiyonStr = ' | Opsiyon: ' . $kabulOffer->option_date;
                } catch (\Exception $e) {}
            }

            $statusLabel = match ($dt->status) {
                'beklemede'       => '⏳ Beklemede',
                'islemde'         => '🔄 İşlemde',
                'fiyatlandirildi' => '💰 Fiyatlandırıldı',
                'onaylandi'       => '✅ Onaylandı',
                'depozitoda'      => '💳 Depozitoda',
                'biletlendi'      => '🎫 Biletlendi',
                'iptal'           => '❌ İptal',
                'olumsuz'         => '🚫 Olumsuz',
                default           => $dt->status,
            };

            $digerStr .= "• {$dt->gtpnr} | {$rota} | {$tarih} | {$statusLabel} | {$teklifSayisi} teklif{$fiyat}{$opsiyonStr}\n";
        }
        if ($digerStr === '') $digerStr = "Bu hesaba ait başka talep bulunmuyor.\n";

        // ── WhatsApp linki ──
        $waNumara = preg_replace('/[^0-9]/', '', $whatsapp);
        $waLink   = "https://wa.me/{$waNumara}?text=" . rawurlencode("{$talep->gtpnr} numaralı talebim hakkında görüşmek istiyorum.");

        // ── Banka hesapları formatı ──
        $bankaStr = '';
        if (empty($bankaHesaplari)) {
            $bankaStr = "Henüz banka hesabı girilmemiş. Lütfen operasyon ekibiyle iletişime geçin.\n";
        } else {
            foreach ($bankaHesaplari as $idx => $h) {
                $bankaStr .= ($idx + 1) . ". Hesap ({$h['doviz']}):\n";
                $bankaStr .= "   Banka    : {$h['adi']}" . ($h['sube'] ? " / {$h['sube']}" : '') . "\n";
                $bankaStr .= "   Hesap Sah: {$h['sahip']}\n";
                $bankaStr .= "   IBAN     : {$h['iban']}\n";
                $bankaStr .= "   Not      : {$h['not']}\n\n";
            }
        }

        // ── System prompt ──
        return <<<PROMPT
Sen TURAi'sin. GrupTalepleri.com'un acente portalı yapay zeka asistanısın.
Şu an {$user->name} kullanıcısının hesabına hizmet veriyorsun.
Bugünün tarihi ve saati: {$now->format('d.m.Y H:i')} (Türkiye saati)

━━━ MEVCUT TALEP — {$talep->gtpnr} ━━━
GTPNR       : {$talep->gtpnr}
Durum       : {$talep->status}
Oluşturulma : {$talep->created_at->format('d.m.Y H:i')}
Yolcu Sayısı: {$talep->pax_total} kişi ({$talep->pax_adult}Y + {$talep->pax_child}Ç + {$talep->pax_infant}B)
Seyahat Amacı: {$talep->flight_purpose}
Tercih Edilen Havayolu: {$talep->preferred_airline}
Acente Notu : {$talep->notes}

ROTA:
{$segmentStr}
TEKLİFLER:
{$teklifStr}
ÖDEME PLANI:
{$odemePlanStr}
FİNANSAL ÖZET:
• Toplam Tutar  : {$toplamTutar} {$currency}
• Ödenen        : {$odenenTutar} {$currency}
• Kalan Borç    : {$kalanTutar} {$currency}
• Tahsilat      : %{$tahsilatYuzde}

━━━ HESABINIZIN DİĞER TALEPLERİ (Son 40) ━━━
{$digerStr}
━━━ BANKA / HAVALE BİLGİLERİ ━━━
{$bankaStr}

━━━ İLETİŞİM VE ACİL (7/24) ━━━
{$adminTelStr}WhatsApp: [WhatsApp ile Yaz →]({$waLink})
E-posta : {$eposta}

━━━ DAVRANIŞ KURALLARI ━━━
1. TALEPLERİ SORARKEN: Yalnızca yukarıdaki verileri kullan. Veritabanında olmayan hiçbir tarih, tutar veya bilgi söyleme. "Sistemde bu bilgi yok." de.
2. ROTA ARAMALARI: "TRZ talebi", "JFK uçuşum" gibi IATA kodu içeren soruları yukarıdaki talep listesinden eşleştir.
3. DESTİNASYON BİLGİSİ: Havalimanı, şehir, gezilecek yer, ulaşım, tur programı gibi genel sorular için Gemini genel bilgini kullanabilirsin. Cevabın başına "(Genel bilgi)" ekle.
4. HAVALE SORUSU: Banka bölümündeki bilgileri eksiksiz ver — IBAN, hesap sahibi, açıklama notunu mutlaka belirt.
5. KISA ve NET cevapla. Gereksiz giriş cümlesi kurma.
6. Türkçe cevapla.
7. Emoji kullanabilirsin, ama abartma.
8. İLETİŞİM KURALI: Telefon/WhatsApp bilgisi verirken URL'yi ham metin yazma — mutlaka [tıklanabilir metin](url) formatında yaz. Çalışma saatinden bahsetme — 7/24 ulaşılabilirler.
9. ACİL DURUM KURALI: Acil sorusunda yukarıdaki İLETİŞİM VE ACİL bölümündeki numaraları ver. Hepsini listele. Çalışma saatinden ASLA bahsetme.
PROMPT;
    }

    // ── Gemini çağrısı ─────────────────────────────────────────────────────────
    private function geminiChat(string $context, array $gecmis, string $mesaj, string $apiKey, string $model): string
    {
        $gecmisBolum = '';
        foreach ($gecmis as $msg) {
            $rol = ($msg['rol'] ?? '') === 'kullanici' ? 'Kullanıcı' : 'TURAi';
            $gecmisBolum .= "{$rol}: " . mb_substr($msg['icerik'] ?? '', 0, 500) . "\n";
        }

        $prompt = $context
            . ($gecmisBolum ? "\n━━━ KONUŞMA GEÇMİŞİ ━━━\n{$gecmisBolum}" : '')
            . "\n━━━ KULLANICININ SORUSU ━━━\n{$mesaj}\n\nTURAi:";

        $response = Http::timeout(45)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents'         => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]],
            ]
        );

        if (! $response->successful()) {
            throw new \Exception('AI servisi geçici olarak kullanılamıyor.');
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if (! $text) {
            throw new \Exception('AI boş yanıt döndürdü.');
        }

        return $text;
    }
}

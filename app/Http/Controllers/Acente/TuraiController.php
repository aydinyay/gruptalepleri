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
    use \App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;

    public function chat(Request $request, string $gtpnr): JsonResponse
    {
        try {
            $user = $this->acenteActor();

            $talepQuery = GrupTalep::where('gtpnr', $gtpnr);
            if ($this->isAcentePreviewMode()) {
                $talepQuery->where('user_id', $user->id);
            } elseif (! in_array(auth()->user()->role, ['admin', 'superadmin'], true)) {
                $talepQuery->where('user_id', $user->id);
            }
            $talep = $talepQuery->with(['segments', 'offers' => fn ($q) => $q->whereIn('durum', ['beklemede', 'kabul_edildi']), 'payments'])
                ->first();

            if (! $talep) {
                return response()->json(['hata' => 'Talep bulunamadı.'], 404);
            }

            $digerTalepler = GrupTalep::where('user_id', $talep->user_id)
                ->where('gtpnr', '!=', $gtpnr)
                ->with(['segments', 'offers' => fn ($q) => $q->whereIn('durum', ['beklemede', 'kabul_edildi'])])
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
            $konum   = trim((string) $request->input('konum', ''));
            $hava    = trim((string) $request->input('hava', ''));
            $context = $this->buildContext($talep, $digerTalepler, $konum, $hava);
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
            $user = $this->acenteActor();

            $acilQuery = GrupTalep::where('gtpnr', $gtpnr);
            if ($this->isAcentePreviewMode()) {
                $acilQuery->where('user_id', $user->id);
            } elseif (! in_array(auth()->user()->role, ['admin', 'superadmin'], true)) {
                $acilQuery->where('user_id', $user->id);
            }
            $talep = $acilQuery->with('segments')->first();

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

    // ── Talebi kendi telefonuma SMS olarak gönder ──────────────────────────────
    public function selfSms(Request $request, string $gtpnr): JsonResponse
    {
        try {
            $user = $this->acenteActor();

            $phone = preg_replace('/[^0-9]/', '', (string) ($user->phone ?? ''));
            if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
                $phone = '90' . substr($phone, 1);
            } elseif (strlen($phone) === 10 && str_starts_with($phone, '5')) {
                $phone = '90' . $phone;
            }

            if (strlen($phone) < 11) {
                return response()->json(['mesaj' => '⚠️ Hesabınızda kayıtlı cep numarası yok. Profilinizden telefon ekleyin.']);
            }

            $smsQuery = GrupTalep::where('gtpnr', $gtpnr);
            if ($this->isAcentePreviewMode()) {
                $smsQuery->where('user_id', $user->id);
            } elseif (! in_array(auth()->user()->role, ['admin', 'superadmin'], true)) {
                $smsQuery->where('user_id', $user->id);
            }
            $talep = $smsQuery->with(['segments', 'payments'])->first();

            if (! $talep) {
                return response()->json(['hata' => 'Talep bulunamadı.'], 404);
            }

            $rota  = $talep->segments->map(fn ($s) => "{$s->from_iata}→{$s->to_iata}")->implode(' / ');
            $tarih = $talep->segments->first()?->departure_date ?? '-';
            $pax   = $talep->pax_total ?? '-';

            // Aktif ödeme
            $aktifOdeme = $talep->payments->firstWhere('is_active', true);
            $odemeStr   = '';
            if ($aktifOdeme) {
                $odemeStr = "\nOdeme  : {$aktifOdeme->amount} {$aktifOdeme->currency}";
                if ($aktifOdeme->due_date) {
                    $odemeStr .= " | Son: {$aktifOdeme->due_date}";
                }
            }

            $mesaj = "GrupTalepleri.com\n"
                . "Talep: {$gtpnr}\n"
                . "Rota : {$rota}\n"
                . "Tarih: {$tarih} | {$pax} kisi\n"
                . "Durum: {$talep->status}"
                . $odemeStr
                . "\ngruptalepleri.com/login";

            $sms = new \App\Services\SmsService();
            if ($sms->send($talep->id, 'acente', $user->name, $phone, $mesaj)) {
                return response()->json(['mesaj' => '✅ Talep bilgileri telefonunuza gönderildi.']);
            }

            return response()->json(['mesaj' => '⚠️ SMS gönderilemedi, lütfen tekrar deneyin.']);

        } catch (\Exception $e) {
            return response()->json(['hata' => 'SMS gönderilemedi: ' . $e->getMessage()], 500);
        }
    }

    // ── Dashboard genel sohbet (gtpnr'sız) ────────────────────────────────────
    public function dashboardChat(Request $request): JsonResponse
    {
        try {
            $user = $this->acenteActor();

            $talepler = GrupTalep::where('user_id', $user->id)
                ->with([
                    'segments',
                    'offers'   => fn($q) => $q->whereIn('durum', ['beklemede', 'kabul_edildi']),
                    'payments' => fn($q) => $q->where('is_active', true),
                ])
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
            $konum   = trim((string) $request->input('konum', ''));
            $hava    = trim((string) $request->input('hava', ''));
            $context = $this->buildDashboardContext($user, $talepler, $konum, $hava);
            $yanit   = $this->geminiChat($context, $gecmis, $mesaj, $apiKey, $model);

            return response()->json(['yanit' => $yanit]);

        } catch (\Exception $e) {
            return response()->json(['hata' => 'Sunucu hatası: ' . $e->getMessage()], 500);
        }
    }

    // ── Dashboard genel bağlam (belirli bir talep yok) ─────────────────────────
    private function buildDashboardContext($user, $talepler, string $konum = '', string $hava = ''): string
    {
        $now = Carbon::now('Europe/Istanbul');

        $sirketUnvan    = SistemAyar::get('sirket_unvan',          'Grup Talepleri Turizm San. ve Tic. Ltd. Şti.');
        $sirketVkn      = SistemAyar::get('sirket_vkn',            '4110477529');
        $sirketVD       = SistemAyar::get('sirket_vergi_dairesi',  'Beyoğlu VD');
        $sirketMersis   = SistemAyar::get('sirket_mersis_no',      '0411047752900001');
        $sirketAdres    = SistemAyar::get('sirket_adres',          'İnönü Mah. Cumhuriyet Cad. No:93/12 Şişli / İstanbul');
        $sirketTursabNo = SistemAyar::get('sirket_tursab_no',      '12572');
        $sirketTursabGrp= SistemAyar::get('sirket_tursab_grup',    'A');
        $whatsapp       = SistemAyar::get('sirket_whatsapp',       '+90 535 415 47 99');
        $eposta         = SistemAyar::get('sirket_eposta',         'destek@gruptalepleri.com');
        $telefon        = SistemAyar::get('sirket_telefon',        '+90 535 415 47 99');

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

        $bankaHesaplari = [];
        for ($i = 1; $i <= 4; $i++) {
            $iban = (string) SistemAyar::get("banka_iban_{$i}", $i === 1 ? SistemAyar::get('banka_iban', '') : '');
            if (empty(trim($iban))) continue;
            $bankaHesaplari[] = [
                'doviz' => SistemAyar::get("banka_doviz_{$i}", 'TRY'),
                'adi'   => SistemAyar::get("banka_adi_{$i}", SistemAyar::get('banka_adi', '')),
                'sube'  => SistemAyar::get("banka_sube_{$i}", SistemAyar::get('banka_sube', '')),
                'sahip' => SistemAyar::get("banka_hesap_sahibi_{$i}", SistemAyar::get('banka_hesap_sahibi', $sirketUnvan)),
                'iban'  => "TR{$iban}",
                'not'   => SistemAyar::get("banka_aciklama_{$i}", SistemAyar::get('banka_aciklama', 'Açıklama kısmına GTPNR numaranızı yazınız.')),
            ];
        }
        $bankaStr = '';
        if (empty($bankaHesaplari)) {
            $bankaStr = "Henüz banka hesabı girilmemiş.\n";
        } else {
            foreach ($bankaHesaplari as $idx => $h) {
                $bankaStr .= ($idx + 1) . ". Hesap ({$h['doviz']}):\n";
                $bankaStr .= "   Banka    : {$h['adi']}" . ($h['sube'] ? " / {$h['sube']}" : '') . "\n";
                $bankaStr .= "   Hesap Sah: {$h['sahip']}\n";
                $bankaStr .= "   IBAN     : {$h['iban']}\n";
                $bankaStr .= "   Not      : {$h['not']}\n\n";
            }
        }

        $waNumara = preg_replace('/[^0-9]/', '', $whatsapp);
        $waLink   = "https://wa.me/{$waNumara}?text=" . rawurlencode("Taleplerim hakkında görüşmek istiyorum.");

        // Tüm talepler listesi
        $talepStr   = '';
        $acilOzetler = [];
        foreach ($talepler as $t) {
            $ilkSeg = $t->segments->first();
            $sonSeg = $t->segments->last();
            $isRT   = ($t->trip_type ?? '') === 'round_trip';

            if ($isRT && $ilkSeg && $sonSeg && $ilkSeg->id !== $sonSeg->id) {
                $t1 = $ilkSeg->departure_date ? Carbon::parse($ilkSeg->departure_date)->format('d M Y') : '';
                $t2 = $sonSeg->departure_date ? Carbon::parse($sonSeg->departure_date)->format('d M Y') : '';
                $rota = "🛫 {$ilkSeg->from_iata} → {$ilkSeg->to_iata} · {$t1} / 🛬 {$sonSeg->from_iata} ← {$sonSeg->to_iata} · {$t2}";
            } else {
                $rota = $t->segments->map(fn($s) => "🛫 {$s->from_iata} → {$s->to_iata}")->implode(' / ');
                $rota .= $ilkSeg?->departure_date ? ' · ' . Carbon::parse($ilkSeg->departure_date)->format('d M Y') : '';
            }

            $kabulTeklif = $t->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL);
            $fiyat = $kabulTeklif ? " | {$kabulTeklif->total_price} {$kabulTeklif->currency}" : '';

            $aktifPayment = $t->payments->firstWhere('is_active', true);
            $odemeStr = '';
            if ($aktifPayment) {
                $odemeStr = " | Aktif Ödeme: {$aktifPayment->amount} {$aktifPayment->currency}";
                if ($aktifPayment->due_date) {
                    $gecti = Carbon::parse($aktifPayment->due_date)->isPast() ? ' ⚠️GEÇTİ' : '';
                    $odemeStr .= " (Son: {$aktifPayment->due_date}{$gecti})";
                }
            }

            $statusLabel = match ($t->status) {
                'beklemede'       => '⏳ Beklemede',
                'islemde'         => '🔄 İşlemde',
                'fiyatlandirildi' => '💰 Fiyatlandırıldı',
                'depozitoda'      => '💳 Depozitoda',
                'biletlendi'      => '🎫 Biletlendi',
                'iptal'           => '❌ İptal',
                'olumsuz'         => '🚫 Olumsuz',
                default           => $t->status,
            };

            $talepStr .= "• {$t->gtpnr} | {$rota} | {$statusLabel}{$fiyat}{$odemeStr}\n";

            // Acil özetler (opsiyonlu, gecikmiş ödeme)
            if ($t->aktif_adim === 'odeme_gecikti') {
                $acilOzetler[] = "⚠️ {$t->gtpnr} gecikmiş ödeme";
            } elseif ($aktifPayment?->due_date && Carbon::parse($aktifPayment->due_date)->diffInHours(now(), false) < 0
                && Carbon::parse($aktifPayment->due_date)->diffInHours(now(), false) > -48) {
                $acilOzetler[] = "⏰ {$t->gtpnr} 48 saat içinde ödeme vadesi";
            }
        }

        $acilStr = empty($acilOzetler) ? '' : "━━━ ACİL UYARILAR ━━━\n" . implode("\n", $acilOzetler) . "\n\n";

        $konumStr = $konum ? "Kullanıcının fiziksel konumu: {$konum}" . ($hava ? " | Hava: {$hava}" : '') . "\n" : '';

        return <<<PROMPT
Sen TURAi'sin. GrupTalepleri.com'un acente portalı yapay zeka asistanısın.
Şu an {$user->name} kullanıcısının hesabına hizmet veriyorsun.
Bugünün tarihi ve saati: {$now->format('d.m.Y H:i')} (Türkiye saati)
{$konumStr}
Kullanıcı şu an dashboard (genel panel) sayfasında — belirli bir talep görüntülenmiyor.
Tüm talepler aşağıda listelenmiştir.

━━━ TÜM TALEPLERİ (Toplam: {$talepler->count()} adet) ━━━
{$talepStr}
{$acilStr}━━━ BANKA / HAVALE BİLGİLERİ ━━━
{$bankaStr}
━━━ ŞİRKET / FATURA BİLGİLERİ ━━━
Unvan          : {$sirketUnvan}
Vergi No (VKN) : {$sirketVkn}
Vergi Dairesi  : {$sirketVD}
MERSİS No      : {$sirketMersis}
Adres          : {$sirketAdres}
TÜRSAB         : {$sirketTursabGrp} Grubu Belge No: {$sirketTursabNo}
E-posta        : {$eposta}

━━━ İLETİŞİM VE ACİL (7/24) ━━━
{$adminTelStr}WhatsApp: [WhatsApp ile Yaz →]({$waLink})
E-posta : {$eposta}

━━━ DAVRANIŞ KURALLARI ━━━
1. Yalnızca yukarıdaki verileri kullan. Veritabanında olmayan bilgi söyleme.
2. ROTA ARAMALARI: IATA kodu içeren soruları talep listesinden eşleştir.
3. DESTİNASYON BİLGİSİ: Genel sorular için genel bilgini kullanabilirsin. Başına "(Genel bilgi)" ekle.
4. HAVALE SORUSU: IBAN, hesap sahibi, açıklama notunu mutlaka belirt.
5. KISA ve NET cevapla. Türkçe. Emoji kullanabilirsin ama abartma.
6. İLETİŞİM KURALI: Telefon/WhatsApp verirken [tıklanabilir metin](url) formatında yaz.
7. ACİL DURUM KURALI: İletişim ve Acil bölümündeki numaraları ver. Çalışma saatinden bahsetme.
8. GÖRSEL FORMAT KURALI: Talep listelerken 🎫 **GTPNR** formatını kullan.
PROMPT;
    }

    // ── Bağlam oluşturucu ──────────────────────────────────────────────────────
    private function buildContext(GrupTalep $talep, $digerTalepler, string $konum = '', string $hava = ''): string
    {
        $user = $this->acenteActor();
        $now  = Carbon::now('Europe/Istanbul');

        // ── Şirket/iletişim/banka bilgileri ──
        $sirketUnvan     = SistemAyar::get('sirket_unvan',          'Grup Talepleri Turizm San. ve Tic. Ltd. Şti.');
        $sirketVkn       = SistemAyar::get('sirket_vkn',            '4110477529');
        $sirketVD        = SistemAyar::get('sirket_vergi_dairesi',  'Beyoğlu VD');
        $sirketMersis    = SistemAyar::get('sirket_mersis_no',      '0411047752900001');
        $sirketAdres     = SistemAyar::get('sirket_adres',          'İnönü Mah. Cumhuriyet Cad. No:93/12 Şişli / İstanbul');
        $sirketTursabNo  = SistemAyar::get('sirket_tursab_no',      '12572');
        $sirketTursabGrp = SistemAyar::get('sirket_tursab_grup',    'A');
        $whatsapp        = SistemAyar::get('sirket_whatsapp',       '+90 535 415 47 99');
        $eposta          = SistemAyar::get('sirket_eposta',         'destek@gruptalepleri.com');
        $telefon         = SistemAyar::get('sirket_telefon',        '+90 535 415 47 99');

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
        $isRoundTrip   = ($talep->trip_type ?? '') === 'round_trip';
        $tripTypeLabel = $isRoundTrip ? 'Gidiş-Dönüş' : 'Tek Yön';
        $segmentStr    = '';
        foreach ($talep->segments as $i => $seg) {
            $segmentStr .= ($i + 1) . ". {$seg->from_iata}({$seg->from_city}) → {$seg->to_iata}({$seg->to_city}) | {$seg->departure_date}" . ($seg->departure_time ? " {$seg->departure_time}" : '') . "\n";
        }

        // Mevcut talep için hazır badge formatı (AI bunu kopyalasın, tahmin etmesin)
        $ilkSeg = $talep->segments->first();
        $sonSeg = $talep->segments->last();
        if ($isRoundTrip && $ilkSeg && $sonSeg && $ilkSeg->id !== $sonSeg->id) {
            $t1 = $ilkSeg->departure_date ? \Carbon\Carbon::parse($ilkSeg->departure_date)->format('d M Y') : '';
            $t2 = $sonSeg->departure_date ? \Carbon\Carbon::parse($sonSeg->departure_date)->format('d M Y') : '';
            $rotaBadge = "🎫 **{$talep->gtpnr}** 🛫 {$ilkSeg->from_iata} → {$ilkSeg->to_iata} · {$t1} / 🛬 {$sonSeg->from_iata} ← {$sonSeg->to_iata} · {$t2}";
        } else {
            $from = $ilkSeg?->from_iata ?? '';
            $to   = $ilkSeg?->to_iata ?? '';
            $t1   = $ilkSeg?->departure_date ? \Carbon\Carbon::parse($ilkSeg->departure_date)->format('d M Y') : '';
            $rotaBadge = "🎫 **{$talep->gtpnr}** 🛫 {$from} → {$to} · {$t1}";
        }

        // ── Teklifler — kabul edilmiş varsa sadece onu göster ──
        $kabulTeklif  = $talep->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL);
        $gosterOffers = $kabulTeklif
            ? $talep->offers->where('durum', \App\Models\Offer::DURUM_KABUL)
            : $talep->offers->where('durum', \App\Models\Offer::DURUM_BEKLEMEDE);

        $teklifStr = '';
        foreach ($gosterOffers as $i => $o) {
            $durumLabel = match ($o->durum) {
                \App\Models\Offer::DURUM_KABUL      => '✅ Kabul Edildi',
                \App\Models\Offer::DURUM_REDDEDILDI => '❌ Reddedildi',
                \App\Models\Offer::DURUM_GIZLENDI   => '🙈 Gizlendi',
                default                              => '⏳ Bekliyor',
            };

            $teklifStr .= ($i + 1) . ". {$o->airline} | {$o->price_per_pax} {$o->currency}/kişi | Toplam: {$o->total_price} {$o->currency}";
            if ($o->deposit_amount) {
                $teklifStr .= " | Depozito: {$o->deposit_amount} {$o->currency} (%{$o->deposit_rate})";
            }
            $teklifStr .= " | {$durumLabel}\n";
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
            $durumLabel = match ($p->status) {
                \App\Models\RequestPayment::STATUS_ALINDI  => '✅ Alındı',
                \App\Models\RequestPayment::STATUS_AKTIF   => '⏳ Bekliyor',
                \App\Models\RequestPayment::STATUS_TASLAK  => '📋 Taslak',
                \App\Models\RequestPayment::STATUS_GECIKTI => '⚠️ GECİKTİ',
                \App\Models\RequestPayment::STATUS_IADE    => '↩️ İade',
                default                                    => $p->status,
            };

            if ($p->status === \App\Models\RequestPayment::STATUS_ALINDI) $odenenTutar += (float) $p->amount;

            $aktifIsareti = $p->is_active ? ' ← AKTİF' : '';
            $satirlar = ($i + 1) . ". ";
            $satirlar .= match ($p->payment_type ?? '') {
                'depozito' => 'Depozito',
                'bakiye'   => 'Bakiye',
                'full'     => 'Tam Ödeme',
                default    => ($p->payment_type ?? 'Ödeme'),
            };
            $satirlar .= " | {$p->amount} {$p->currency} | {$durumLabel}{$aktifIsareti}";
            if ($p->due_date) {
                $dueDate = Carbon::parse($p->due_date, 'Europe/Istanbul');
                $gecti   = $dueDate->isPast() && in_array($p->status, [\App\Models\RequestPayment::STATUS_AKTIF, \App\Models\RequestPayment::STATUS_GECIKTI]) ? ' ⚠️ TARİH GEÇTİ!' : '';
                $satirlar .= " | Son: {$p->due_date}{$gecti}";
            }
            if ($p->payment_date) $satirlar .= " | Ödeme: {$p->payment_date}";
            $odemePlanStr .= $satirlar . "\n";
        }
        if ($odemePlanStr === '') $odemePlanStr = "Ödeme planı henüz oluşturulmamış.\n";

        $kalanTutar = max(0, $toplamTutar - $odenenTutar);
        $tahsilatYuzde = $toplamTutar > 0 ? round(($odenenTutar / $toplamTutar) * 100) : 0;

        // ── Aktif adım ve ödeme durumu ──
        $aktifAdim    = $talep->aktif_adim ?? 'teklif_bekleniyor';
        $odemeDurumu  = $talep->odeme_durumu ?? 'yok';
        $aktifPayment = $talep->payments->firstWhere('is_active', true);

        $aktifAdimLabel = match ($aktifAdim) {
            'teklif_bekleniyor'      => 'Teklif bekleniyor',
            'karar_bekleniyor'       => 'Opsiyonda (teklif sunuldu)',
            'odeme_plani_bekleniyor' => 'Ödeme planı bekleniyor (teklif kabul edildi)',
            'odeme_bekleniyor'       => 'Ödeme bekleniyor',
            'odeme_gecikti'          => '⚠️ ÖDEME GECİKTİ',
            'odeme_alindi_devam'     => 'Kısmi ödeme alındı, devam ediyor',
            'biletleme_bekleniyor'   => 'Tüm ödemeler alındı — biletleme bekleniyor',
            'tamamlandi'             => 'Tamamlandı',
            default                  => $aktifAdim,
        };

        $aktifVade = "Aktif Adım: {$aktifAdimLabel}\nÖdeme Durumu: {$odemeDurumu}";
        if ($aktifPayment) {
            $aktifVade .= "\nAktif Ödeme: {$aktifPayment->amount} {$aktifPayment->currency}";
            if ($aktifPayment->due_date) {
                $dueDt = Carbon::parse($aktifPayment->due_date)->endOfDay('Europe/Istanbul');
                $gunKaldi = (int) $now->diffInDays($dueDt, false);
                $gectiMi  = $dueDt->isPast() ? ' ⚠️ GEÇTİ' : " ({$gunKaldi} gün kaldı)";
                $aktifVade .= " | Son Tarih: {$aktifPayment->due_date}{$gectiMi}";
            }
        }

        // ── Diğer talepler ──
        $digerStr = '';
        foreach ($digerTalepler as $dt) {
            $isRT  = ($dt->trip_type ?? '') === 'round_trip';
            $ilkSeg = $dt->segments->first();
            $sonSeg = $dt->segments->last();
            $tarih = $ilkSeg?->departure_date ?? '-';
            if ($isRT && $ilkSeg && $sonSeg && $ilkSeg->id !== $sonSeg->id) {
                $t1 = $ilkSeg->departure_date ? \Carbon\Carbon::parse($ilkSeg->departure_date)->format('d M Y') : '';
                $t2 = $sonSeg->departure_date ? \Carbon\Carbon::parse($sonSeg->departure_date)->format('d M Y') : '';
                $rota = "🛫 {$ilkSeg->from_iata} → {$ilkSeg->to_iata} · {$t1} / 🛬 {$sonSeg->from_iata} ← {$sonSeg->to_iata} · {$t2}";
            } else {
                $rota = $dt->segments->map(fn ($s) => "🛫 {$s->from_iata} → {$s->to_iata}")->implode(' / ');
            }
            $teklifSayisi = $dt->offers->count();
            $enIyiTeklif = $dt->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL)
                ?? $dt->offers->sortBy('total_price')->first();
            $fiyat = $enIyiTeklif ? " | {$enIyiTeklif->total_price} {$enIyiTeklif->currency}" : '';

            // Aktif adım
            $opsiyonStr = $dt->aktif_adim ? ' | ' . match ($dt->aktif_adim) {
                'odeme_bekleniyor'       => '💳 Ödeme bekliyor',
                'odeme_gecikti'          => '⚠️ Ödeme gecikti',
                'odeme_alindi_devam'     => '✅ Kısmi ödeme',
                'biletleme_bekleniyor'   => '✅ Biletleme bekliyor',
                'tamamlandi'             => '✅ Tamamlandı',
                'karar_bekleniyor'       => '⏳ Opsiyonda',
                'odeme_plani_bekleniyor' => '📋 Plan bekliyor',
                default                  => '',
            } : '';

            $statusLabel = match ($dt->status) {
                'beklemede'       => '⏳ Beklemede',
                'islemde'         => '🔄 İşlemde',
                'fiyatlandirildi' => '💰 Fiyatlandırıldı',
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
        $konumStr = $konum ? "Kullanıcının fiziksel konumu: {$konum}" . ($hava ? " | Hava: {$hava}" : '') . "\n" : '';

        return <<<PROMPT
Sen TURAi'sin. GrupTalepleri.com'un acente portalı yapay zeka asistanısın.
Şu an {$user->name} kullanıcısının hesabına hizmet veriyorsun.
Bugünün tarihi ve saati: {$now->format('d.m.Y H:i')} (Türkiye saati)
{$konumStr}
━━━ MEVCUT TALEP — {$talep->gtpnr} ━━━
GTPNR       : {$talep->gtpnr}
Durum       : {$talep->status}
Oluşturulma : {$talep->created_at->format('d.m.Y H:i')}
Uçuş Türü   : {$tripTypeLabel}
Yolcu Sayısı: {$talep->pax_total} kişi ({$talep->pax_adult}Y + {$talep->pax_child}Ç + {$talep->pax_infant}B)
Seyahat Amacı: {$talep->flight_purpose}
Tercih Edilen Havayolu: {$talep->preferred_airline}
Acente Notu : {$talep->notes}

ROTA:
{$segmentStr}
MEVCUT TALEBİN BADGE FORMATI (liste yaparken sadece bunu kopyala, tahmin etme):
{$rotaBadge}

TEKLİFLER:
{$teklifStr}
ÖDEME PLANI:
{$odemePlanStr}
AKTİF ADIM / ÖDEME DURUMU:
{$aktifVade}

FİNANSAL ÖZET:
• Toplam Tutar  : {$toplamTutar} {$currency}
• Ödenen        : {$odenenTutar} {$currency}
• Kalan Borç    : {$kalanTutar} {$currency}
• Tahsilat      : %{$tahsilatYuzde}

━━━ HESABINIZIN DİĞER TALEPLERİ (Toplam: {$digerTalepler->count()} adet) ━━━
{$digerStr}
━━━ BANKA / HAVALE BİLGİLERİ ━━━
{$bankaStr}

━━━ ŞİRKET / FATURA BİLGİLERİ ━━━
Unvan          : {$sirketUnvan}
Vergi No (VKN) : {$sirketVkn}
Vergi Dairesi  : {$sirketVD}
MERSİS No      : {$sirketMersis}
Adres          : {$sirketAdres}
TÜRSAB         : {$sirketTursabGrp} Grubu Belge No: {$sirketTursabNo}
E-posta        : {$eposta}

━━━ İLETİŞİM VE ACİL (7/24) ━━━
{$adminTelStr}WhatsApp: [WhatsApp ile Yaz →]({$waLink})
E-posta : {$eposta}

━━━ DAVRANIŞ KURALLARI ━━━
1. TALEPLERİ SORARKEN: Yalnızca yukarıdaki verileri kullan. Veritabanında olmayan hiçbir tarih, tutar veya bilgi söyleme. "Sistemde bu bilgi yok." de. Talep sayısını söylerken yukarıdaki "Toplam: X adet" değerini kullan — asla 40 gibi sabit bir rakam söyleme.
2. ROTA ARAMALARI: "TRZ talebi", "JFK uçuşum" gibi IATA kodu içeren soruları yukarıdaki talep listesinden eşleştir.
3. DESTİNASYON BİLGİSİ: Havalimanı, şehir, gezilecek yer, ulaşım, tur programı gibi genel sorular için Gemini genel bilgini kullanabilirsin. Cevabın başına "(Genel bilgi)" ekle.
4. HAVALE SORUSU: Banka bölümündeki bilgileri eksiksiz ver — IBAN, hesap sahibi, açıklama notunu mutlaka belirt.
5. KISA ve NET cevapla. Gereksiz giriş cümlesi kurma.
6. Türkçe cevapla.
7. Emoji kullanabilirsin, ama abartma.
8. İLETİŞİM KURALI: Telefon/WhatsApp bilgisi verirken URL'yi ham metin yazma — mutlaka [tıklanabilir metin](url) formatında yaz. Çalışma saatinden bahsetme — 7/24 ulaşılabilirler.
9. ACİL DURUM KURALI: Acil sorusunda yukarıdaki İLETİŞİM VE ACİL bölümündeki numaraları ver. Hepsini listele. Çalışma saatinden ASLA bahsetme.
12. SMS KURALI: Kullanıcı "SMS at", "SMS gönder", "telefonuma yaz", "opsiyonumu sms ile al" gibi bir şey istediğinde, sohbet arayüzündeki "📱 Bana SMS at" chipini kullanmasını söyle. Bu chip tıklandığında talep bilgileri otomatik olarak kayıtlı telefonuna gönderilir. "SMS gönderme yeteneğim yok" asla DEME.
11. VADE / DURUM SORUSU: "Ne zaman ödemeliyim", "sürem", "son tarih", "adım ne" gibi sorularda AKTİF ADIM ve AKTİF ÖDEME bölümünü kullan. Teklif option_date'ini asla deadline olarak sunma — sistem artık aktif_adim ve is_active payment üzerinden çalışıyor. Aktif ödeme varsa tutarı ve son tarihi ver; yoksa aktif adımı açıkla.
10. GÖRSEL FORMAT KURALI (ÇOK ÖNEMLİ): Talep veya rota listelerken her zaman şu formatı kullan:
    - Her talep ayrı satırda, başında 🎫
    - Gidiş-dönüş: `🎫 **GTPNR** 🛫 KAL → VAR · Tarih / 🛬 VAR ← KAL · DönüşTarihi | Durum`
    - Tek yön:      `🎫 **GTPNR** 🛫 KAL → VAR · Tarih | Durum`
    - MEVCUT TALEBİN BADGE FORMATI YUKARIDA HAZIR — sadece kopyala, yeniden üretme!
    Şehir adlarını IATA kodunun yanına koyma — sadece IATA kodu yeterli.
    Düz paragraf veya `*` bullet ile talep listesi YAZMA.
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

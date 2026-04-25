<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\SigortaOdeme;
use App\Models\SigortaPolice;
use App\Services\PaoNetService;
use App\Services\PaoNetHelper;
use App\Services\Payments\PaynkolayGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SigortaController extends Controller
{
    private function aktifMi(): bool
    {
        return (bool) DB::table('sigorta_ayarlar')->where('anahtar', 'aktif')->value('deger');
    }

    private function ayar(string $anahtar, mixed $default = null): mixed
    {
        $val = DB::table('sigorta_ayarlar')->where('anahtar', $anahtar)->value('deger');
        return $val !== null ? $val : $default;
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public function create()
    {
        if (!$this->aktifMi()) {
            return view('b2c.sigorta.kapali');
        }
        return view('b2c.sigorta.create');
    }

    // ── Teklif AJAX ───────────────────────────────────────────────────────────

    public function teklifAl(Request $request)
    {
        if ($request->filled('website')) {
            return response()->json(['error' => 'Geçersiz istek.'], 422);
        }

        $request->validate([
            'kimlik'           => 'required|string|max:20',
            'adi'              => 'required|string|max:80',
            'soyadi'           => 'required|string|max:80',
            'dogum_tarihi'     => 'required|date',
            'baslangic_tarihi' => 'required|date|after_or_equal:today',
            'bitis_tarihi'     => 'required|date|after:baslangic_tarihi',
            'ulke'             => 'required|string|max:80',
            'dogum_yeri'       => 'nullable|string|max:100',
            'uyruk'            => 'nullable|string|max:80',
            'baba_adi'         => 'nullable|string|max:80',
            'cinsiyet'         => 'nullable|string|in:E,K',
            'boy'              => 'nullable|integer|min:50|max:250',
            'kilo'             => 'nullable|integer|min:10|max:300',
            'il_adi'           => 'nullable|string|max:60',
            'ilce_adi'         => 'nullable|string|max:60',
            'adres'            => 'nullable|string|max:255',
        ]);

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta hizmeti şu an kullanılamıyor.'], 503);
        }

        try {
            $svc      = app(PaoNetService::class);
            $kimlikT  = PaoNetHelper::detectKimlikTipi($request->kimlik);
            $urunKodu = PaoNetHelper::urunKodu($kimlikT);

            $msgParams = [
                'Kimlik'          => $request->kimlik,
                'Adi'             => $request->adi,
                'Soyadi'          => $request->soyadi,
                'DogumTarihi'     => $request->dogum_tarihi,
                'BaslangicTarihi' => $request->baslangic_tarihi,
                'BitisTarihi'     => $request->bitis_tarihi,
                'GidilecekUlke'   => $request->ulke,
            ];

            if ($kimlikT === 'pasaport') {
                $msgParams['BabaAdi']   = $request->baba_adi ?? '';
                $msgParams['DogumYeri'] = $request->dogum_yeri ?? '';
                $msgParams['Cinsiyet']  = PaoNetHelper::cinsiyetKodu($request->cinsiyet ?? 'E');
                $msgParams['Uyruk']     = $request->uyruk ?? '';
                $msgParams['Boy']       = $request->boy ?? '';
                $msgParams['Kilo']      = $request->kilo ?? '';
                $msgParams['IlAdi']     = $request->il_adi ?? '';
                $msgParams['IlceAdi']   = $request->ilce_adi ?? '';
                $msgParams['Adres']     = $request->adres ?? '';
            }

            $strMsg = PaoNetHelper::buildStrMsg($msgParams);
            $teklif  = $svc->teklifAl($urunKodu, $strMsg);
            $bprim   = (float) ($teklif['Bprim'] ?? $teklif['bprim'] ?? 0);
            $dkuru   = (float) ($teklif['Dkuru'] ?? $teklif['dkuru'] ?? 1);
            $doviz   = $teklif['DovizTuru'] ?? 'USD';

            $markup  = (float) $this->ayar('b2c_markup_yuzde', 50);
            $tampon  = (float) $this->ayar('kur_tamponu_yuzde', 5);
            $fiyat   = PaoNetHelper::hesaplaSatisFiyati($bprim, $dkuru, $markup, $tampon);

            return response()->json([
                'ok'         => true,
                'teklif_id'  => $teklif['TeklifId'] ?? '',
                'urun_kodu'  => $urunKodu,
                'doviz_turu' => $doviz,
                'bprim'      => $bprim,
                'dkuru'      => $dkuru,
                'satis_tl'   => $fiyat['satis_fiyat'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Poliçe Başlat → Ödeme Sayfasına Yönlendir ────────────────────────────

    public function policeUret(Request $request)
    {
        $request->validate([
            'teklif_id'        => 'required|string',
            'urun_kodu'        => 'required|string',
            'kimlik'           => 'required|string|max:20',
            'adi'              => 'required|string|max:80',
            'soyadi'           => 'required|string|max:80',
            'dogum_tarihi'     => 'required|date',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi'     => 'required|date',
            'ulke'             => 'required|string|max:80',
            'bprim'            => 'required|numeric',
            'dkuru'            => 'required|numeric',
            'doviz_turu'       => 'required|string|max:5',
        ]);

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta hizmeti şu an kullanılamıyor.'], 503);
        }

        $markup  = (float) $this->ayar('b2c_markup_yuzde', 50);
        $tampon  = (float) $this->ayar('kur_tamponu_yuzde', 5);
        $fiyat   = PaoNetHelper::hesaplaSatisFiyati(
            (float) $request->bprim,
            (float) $request->dkuru,
            $markup,
            $tampon
        );

        $b2cUser = auth('b2c')->user();

        // ── Poliçe kaydı oluştur — PAO-Net'e HENÜz gitme ────────────────────
        $police = SigortaPolice::create([
            'b2c_user_id'       => $b2cUser?->id,
            'kanal'             => 'b2c',
            'paonet_teklif_id'  => $request->teklif_id,
            'paonet_urun_kodu'  => $request->urun_kodu,
            'sigortali_kimlik'  => $request->kimlik,
            'kimlik_tipi'       => PaoNetHelper::detectKimlikTipi($request->kimlik),
            'sigortali_adi'     => $request->adi,
            'sigortali_soyadi'  => $request->soyadi,
            'sigortali_dogum'   => $request->dogum_tarihi,
            'baslangic_tarihi'  => $request->baslangic_tarihi,
            'bitis_tarihi'      => $request->bitis_tarihi,
            'gidilecek_ulke'    => $request->ulke,
            'api_doviz_turu'    => $request->doviz_turu,
            'api_doviz_tutar'   => $request->bprim,
            'api_kur'           => $request->dkuru,
            'maliyet_tl'        => $fiyat['maliyet_tl'],
            'b2c_fiyat_tl'      => $fiyat['satis_fiyat'],
            'satilan_fiyat_tl'  => $fiyat['satis_fiyat'],
            'net_kar_tl'        => $fiyat['net_kar'],
            'markup_yuzde'      => $markup,
            'kur_tamponu_yuzde' => $tampon,
            'durum'             => 'odeme_bekleniyor',  // ← ödeme olmadan poliçe yok
        ]);

        // ── Ödeme kaydı oluştur ───────────────────────────────────────────────
        $internalRef = 'SPY-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(6));

        $odeme = SigortaOdeme::create([
            'sigorta_police_id'    => $police->id,
            'kanal'                => 'b2c',
            'internal_reference'   => $internalRef,
            'amount_try'           => $fiyat['satis_fiyat'],
            'status'               => 'pending',
            'request_payload_json' => [
                'police_id' => $police->id,
                'teklif_id' => $request->teklif_id,
                'urun_kodu' => $request->urun_kodu,
                'kimlik'    => $request->kimlik,
                'adi'       => $request->adi,
                'soyadi'    => $request->soyadi,
            ],
        ]);

        // ── Paynkolay ödeme başlat → redirect URL al ─────────────────────────
        try {
            $gateway     = app(PaynkolayGatewayService::class);
            $initialized = $gateway->initializePayment(
                clientReference: $internalRef,
                amountTry:       (float) $fiyat['satis_fiyat'],
                successUrl:      route('b2c.sigorta.odeme.basarili'),
                failUrl:         route('b2c.sigorta.odeme.basarisiz'),
                cardHolderIp:    (string) $request->ip(),
            );

            $odeme->update(['provider_reference' => $initialized['provider_reference']]);

            return response()->json([
                'ok'          => true,
                'payment_url' => $initialized['redirect_url'],
            ]);
        } catch (\Throwable $e) {
            $police->update(['durum' => 'hata', 'hata_mesaji' => $e->getMessage()]);
            $odeme->update(['status' => 'rejected', 'failure_reason' => $e->getMessage(), 'failed_at' => now()]);
            Log::error('sigorta.b2c.odeme_baslat', ['police_id' => $police->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Ödeme başlatılamadı: ' . Str::limit($e->getMessage(), 200)], 422);
        }
    }

    // ── Paynkolay Callback — Başarılı ────────────────────────────────────────

    public function odemeBasarili(Request $request)
    {
        return $this->handleOdemeCallback($request, forceFailed: false);
    }

    // ── Paynkolay Callback — Başarısız ───────────────────────────────────────

    public function odemeBasarisiz(Request $request)
    {
        return $this->handleOdemeCallback($request, forceFailed: true);
    }

    private function handleOdemeCallback(Request $request, bool $forceFailed): \Illuminate\Http\RedirectResponse
    {
        $payload = $request->all();
        $gateway = app(PaynkolayGatewayService::class);

        if (!$gateway->isValidResponseHash($payload)) {
            return redirect()->route('b2c.sigorta.create')
                ->with('error', 'Ödeme doğrulaması başarısız. Lütfen tekrar deneyin.');
        }

        if ($forceFailed) {
            $payload['response_code']   = '0';
            $payload['auth_code']       = '0';
            $payload['payment_status']  = 'failed';
        }

        $internalRef = trim((string) (
            $payload['clientRefCode']
            ?? $payload['CLIENT_REFERENCE_CODE']
            ?? $payload['client_reference_code']
            ?? ''
        ));

        $odeme = SigortaOdeme::where('internal_reference', $internalRef)->first();

        if (!$odeme || !$odeme->sigorta_police_id) {
            return redirect()->route('b2c.sigorta.create')
                ->with('error', 'Ödeme kaydı bulunamadı.');
        }

        // Mükerrer callback koruması
        if ($odeme->paid_at || $odeme->failed_at) {
            $police = SigortaPolice::find($odeme->sigorta_police_id);
            if ($police && $police->durum === 'tamamlandi') {
                return redirect()->route('b2c.sigorta.durum', $police->id);
            }
            return redirect()->route('b2c.sigorta.create');
        }

        $mappedStatus = $gateway->mapCallbackStatus($payload);

        if ($mappedStatus !== 'paid') {
            $reason = Str::limit((string) ($payload['RESPONSE_DATA'] ?? $payload['message'] ?? 'Ödeme reddedildi.'), 350);
            $odeme->update([
                'status'                => 'rejected',
                'callback_payload_json' => $payload,
                'failure_reason'        => $reason,
                'failed_at'             => now(),
            ]);
            SigortaPolice::where('id', $odeme->sigorta_police_id)
                ->update(['durum' => 'odeme_basarisiz']);

            return redirect()->route('b2c.sigorta.create')
                ->with('error', 'Ödeme başarısız oldu. Poliçe düzenlenmedi. ' . $reason);
        }

        // ── Ödeme BAŞARILI → PAO-Net'e poliçe üret ───────────────────────────
        $providerRef = trim((string) ($payload['reference_code'] ?? $payload['REFERENCE_CODE'] ?? ''));
        $odeme->update([
            'status'                => 'approved',
            'provider_reference'    => $providerRef ?: $odeme->provider_reference,
            'callback_payload_json' => $payload,
            'paid_at'               => now(),
        ]);

        $police = SigortaPolice::find($odeme->sigorta_police_id);

        if (!$police) {
            Log::critical('sigorta.b2c.police_kaydi_bulunamadi', ['odeme_id' => $odeme->id]);
            return redirect()->route('b2c.sigorta.create')
                ->with('error', 'Ödeme alındı ancak poliçe kaydı bulunamadı. Lütfen destek ile iletişime geçin.');
        }

        $police->update(['durum' => 'police_isleniyor']);

        try {
            $svc      = app(PaoNetService::class);
            $sonuc    = $svc->policeUret($police->paonet_teklif_id);
            $referans = $sonuc['Referans'] ?? $sonuc['referans'] ?? '';
            $police->update(['paonet_referans' => $referans]);
        } catch (\RuntimeException $e) {
            Log::critical('sigorta.b2c.paonet_basarisiz_odeme_alindi', [
                'police_id' => $police->id,
                'error'     => $e->getMessage(),
            ]);
            $police->update(['durum' => 'hata', 'hata_mesaji' => $e->getMessage()]);
        }

        return redirect()->route('b2c.sigorta.durum', $police->id);
    }

    // ── Poliçe Durum Sayfası (ödeme sonrası polling) ─────────────────────────

    public function policeDurum(Request $request, SigortaPolice $police)
    {
        // Sadece sahibi veya anonim (aynı browser session üzerinden erişim)
        $b2cUser = auth('b2c')->user();
        if ($b2cUser && $police->b2c_user_id && $police->b2c_user_id !== $b2cUser->id) {
            abort(403);
        }
        if (!$b2cUser && $police->b2c_user_id) {
            abort(403);
        }

        return view('b2c.sigorta.durum', compact('police'));
    }

    // ── Poliçe Durum Poll (AJAX) ──────────────────────────────────────────────

    public function policeUretimDurum(Request $request, SigortaPolice $police)
    {
        // Auth kontrolü — login varsa sahipliği, yoksa sadece b2c_user_id=null olan poliçeler
        $b2cUser = auth('b2c')->user();
        if ($b2cUser) {
            abort_unless($police->b2c_user_id === $b2cUser->id, 403);
        } elseif ($police->b2c_user_id !== null) {
            abort(403);
        }

        if ($police->durum === 'tamamlandi') {
            return response()->json(['durum' => 'tamamlandi', 'police_no' => $police->police_no]);
        }

        if (in_array($police->durum, ['hata', 'odeme_basarisiz', 'odeme_bekleniyor'])) {
            return response()->json(['durum' => $police->durum]);
        }

        if (!$this->aktifMi() || empty($police->paonet_referans)) {
            return response()->json(['durum' => $police->durum]);
        }

        try {
            $svc      = app(PaoNetService::class);
            $sonuc    = $svc->uretimDurumu($police->paonet_referans);
            $policeNo = $sonuc['PoliceNo'] ?? '';

            if ($policeNo) {
                $pdfData = $svc->pdfGetir($policeNo);
                $police->update([
                    'police_no'          => $policeNo,
                    'durum'              => 'tamamlandi',
                    'pdf_link'           => $pdfData['PdfLink'] ?? '',
                    'makbuz_link'        => $pdfData['MakbuzLink'] ?? '',
                    'sertifika_link'     => $pdfData['SertifikaLink'] ?? '',
                    'ing_sertifika_link' => $pdfData['IngSertifikaLink'] ?? '',
                ]);
                $policeF = $police->fresh();
                try { (new \App\Services\EmailService())->policeHazir($policeF); } catch (\Throwable) {}

                // SMS müşteriye (B2C kullanıcısı)
                try {
                    $b2cUser  = auth('b2c')->user();
                    if ($b2cUser && $b2cUser->phone) {
                        $shortUrl = (new \App\Services\ShortLinkService())->forPolice($policeF->id, 'b2c', 'police');
                        $smsMsg   = "Sigorta policeniz hazir! Police No: {$policeNo} | PDF: {$shortUrl}";
                        (new \App\Services\SmsService())->send(null, 'b2c_musteri', $b2cUser->name, $b2cUser->phone, $smsMsg);
                    }
                } catch (\Throwable) {}

                // Admin push + SMS
                try {
                    $adminUrl = route('admin.sigorta.index');
                    (new \App\Services\NotificationService())->yeniPolice('b2c', $policeNo, $policeF->sigortali_adi . ' ' . $policeF->sigortali_soyadi, (float)$policeF->satilan_fiyat_tl, $adminUrl);
                    $adminSms = "B2C Police: {$policeNo} | {$policeF->sigortali_adi} {$policeF->sigortali_soyadi} | " . number_format($policeF->satilan_fiyat_tl, 0, ',', '.') . " TL";
                    (new \App\Services\SmsService())->sendToAdmin(null, $adminSms);
                } catch (\Throwable) {}

                return response()->json(['durum' => 'tamamlandi', 'police_no' => $policeNo]);
            }

            return response()->json(['durum' => 'police_isleniyor']);
        } catch (\RuntimeException $e) {
            return response()->json(['durum' => $police->durum]);
        }
    }

    // ── PDF Proxy ─────────────────────────────────────────────────────────────

    public function belge(SigortaPolice $police, string $tip)
    {
        abort_unless(in_array($tip, ['police', 'makbuz', 'sertifika', 'ing-sertifika']), 404);

        $b2cUser = auth('b2c')->user();
        if ($b2cUser) {
            abort_unless($police->b2c_user_id === $b2cUser->id, 403);
        } elseif ($police->b2c_user_id !== null) {
            abort(403);
        }

        $urlMap = [
            'police'    => $police->pdf_link,
            'makbuz'    => $police->makbuz_link,
            'sertifika' => $police->sertifika_link,
        ];

        $rawUrl = $urlMap[$tip] ?? null;
        abort_if(empty($rawUrl), 404);

        return app(PaoNetService::class)->pdfStream(
            PaoNetHelper::normalizePdfUrl($rawUrl)
        );
    }

    // ── Hesabım → Poliçelerim ─────────────────────────────────────────────────

    public function policelerim(Request $request)
    {
        $b2cUser  = auth('b2c')->user();
        $policeler = SigortaPolice::where('b2c_user_id', $b2cUser->id)
            ->where('kanal', 'b2c')
            ->latest()
            ->paginate(20);

        return view('b2c.sigorta.policelerim', compact('policeler'));
    }
}

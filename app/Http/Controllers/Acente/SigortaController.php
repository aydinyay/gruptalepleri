<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;
use App\Http\Controllers\Controller;
use App\Models\SigortaOdeme;
use App\Models\SigortaPolice;
use App\Models\SigortaBatchJob;
use App\Services\PaoNetService;
use App\Services\PaoNetHelper;
use App\Services\Payments\PaynkolayGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SigortaController extends Controller
{
    use ResolvesPreviewUser;
    private function aktifMi(): bool
    {
        return (bool) DB::table('sigorta_ayarlar')->where('anahtar', 'aktif')->value('deger');
    }

    private function ayar(string $anahtar, mixed $default = null): mixed
    {
        $val = DB::table('sigorta_ayarlar')->where('anahtar', $anahtar)->value('deger');
        return $val !== null ? $val : $default;
    }

    // ── Liste ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $acenteId = $this->acenteActor()->id;

        $q = SigortaPolice::where('acente_id', $acenteId)
            ->where('kanal', 'b2b')
            ->latest();

        if ($request->filled('kimlik'))   $q->where('sigortali_kimlik', 'like', '%' . $request->kimlik . '%');
        if ($request->filled('police_no'))$q->where('police_no', 'like', '%' . $request->police_no . '%');
        if ($request->filled('durum'))    $q->where('durum', $request->durum);
        if ($request->filled('tarih_bas'))$q->whereDate('baslangic_tarihi', '>=', $request->tarih_bas);
        if ($request->filled('tarih_bit'))$q->whereDate('baslangic_tarihi', '<=', $request->tarih_bit);

        $policeler = $q->paginate(25)->withQueryString();

        return view('acente.sigorta.index', compact('policeler'));
    }

    // ── Tekil Yeni Form ───────────────────────────────────────────────────────

    public function create()
    {
        return view('acente.sigorta.create', [
            'aktif' => $this->aktifMi(),
        ]);
    }

    // ── Müşteri Kontrol AJAX (CHK2CST) ────────────────────────────────────────

    public function musteriKontrol(Request $request)
    {
        $request->validate([
            'kimlik'       => 'required|string|max:20',
            'dogum_tarihi' => 'required|date',
        ]);

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
        }

        try {
            $svc  = app(PaoNetService::class);
            $data = $svc->musteriKontrol($request->kimlik, $request->dogum_tarihi);
            return response()->json(['ok' => true, 'data' => $data]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Teklif Al AJAX ────────────────────────────────────────────────────────

    public function teklifAl(Request $request)
    {
        $request->validate([
            'kimlik'           => 'required|string|max:20',
            'adi'              => 'required|string|max:80',
            'soyadi'           => 'required|string|max:80',
            'dogum_tarihi'     => 'required|date',
            'baslangic_tarihi' => 'required|date|after_or_equal:today',
            'bitis_tarihi'     => 'required|date|after:baslangic_tarihi',
            'ulke'             => 'required|string|max:80',
        ]);

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
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

            // NPN220 (pasaport) için zorunlu ek alanlar
            if ($kimlikT === 'pasaport') {
                $msgParams['DogumYeri'] = $request->dogum_yeri ?? '';
                $msgParams['Uyruk']     = $request->uyruk ?? '';
                $msgParams['Boy']       = $request->boy ?? '';
                $msgParams['Kilo']      = $request->kilo ?? '';
                $msgParams['IlAdi']     = $request->il_adi ?? '';
                $msgParams['IlceAdi']   = $request->ilce_adi ?? '';
                $msgParams['Adres']     = $request->adres ?? '';
            }

            $strMsg = PaoNetHelper::buildStrMsg($msgParams);
            $teklif = $svc->teklifAl($urunKodu, $strMsg);

            $bprim = (float) ($teklif['Bprim'] ?? $teklif['bprim'] ?? 0);
            $dkuru = (float) ($teklif['Dkuru'] ?? $teklif['dkuru'] ?? 1);
            $doviz = $teklif['DovizTuru'] ?? $teklif['dovizTuru'] ?? 'USD';

            $markup  = (float) $this->ayar('b2b_markup_yuzde', 20);
            $tampon  = (float) $this->ayar('kur_tamponu_yuzde', 5);
            $fiyatlar = PaoNetHelper::hesaplaSatisFiyati($bprim, $dkuru, $markup, $tampon);

            return response()->json([
                'ok'         => true,
                'teklif_id'  => $teklif['TeklifId'] ?? $teklif['teklifId'] ?? '',
                'urun_kodu'  => $urunKodu,
                'doviz_turu' => $doviz,
                'bprim'      => $bprim,
                'dkuru'      => $dkuru,
                'maliyet_tl' => $fiyatlar['maliyet_tl'],
                'satis_tl'   => $fiyatlar['satis_fiyat'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── Poliçe Başlat → Ödeme Sayfasına Yönlendir ───────────────────────────

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
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
        }

        $markup   = (float) $this->ayar('b2b_markup_yuzde', 20);
        $tampon   = (float) $this->ayar('kur_tamponu_yuzde', 5);
        $fiyatlar = PaoNetHelper::hesaplaSatisFiyati(
            (float) $request->bprim,
            (float) $request->dkuru,
            $markup,
            $tampon
        );

        // ── Poliçe kaydı — PAO-Net'e HENÜz gitme ────────────────────────────
        $police = SigortaPolice::create([
            'acente_id'         => $this->acenteActor()->id,
            'kanal'             => 'b2b',
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
            'maliyet_tl'        => $fiyatlar['maliyet_tl'],
            'b2b_fiyat_tl'      => $fiyatlar['satis_fiyat'],
            'satilan_fiyat_tl'  => $fiyatlar['satis_fiyat'],
            'net_kar_tl'        => $fiyatlar['net_kar'],
            'markup_yuzde'      => $markup,
            'kur_tamponu_yuzde' => $tampon,
            'durum'             => 'odeme_bekleniyor',
        ]);

        $internalRef = 'APY-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(6));

        $odeme = SigortaOdeme::create([
            'sigorta_police_id'    => $police->id,
            'kanal'                => 'b2b',
            'internal_reference'   => $internalRef,
            'amount_try'           => $fiyatlar['satis_fiyat'],
            'status'               => 'pending',
            'request_payload_json' => ['police_id' => $police->id, 'acente_id' => $this->acenteActor()->id],
        ]);

        try {
            $gateway     = app(PaynkolayGatewayService::class);
            $initialized = $gateway->initializePayment(
                clientReference: $internalRef,
                amountTry:       (float) $fiyatlar['satis_fiyat'],
                successUrl:      route('acente.sigorta.odeme.basarili'),
                failUrl:         route('acente.sigorta.odeme.basarisiz'),
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
            return response()->json(['error' => 'Ödeme başlatılamadı: ' . Str::limit($e->getMessage(), 200)], 422);
        }
    }

    // ── Paynkolay Callback — Başarılı (Acente) ───────────────────────────────

    public function odemeBasarili(Request $request)
    {
        return $this->handleOdemeCallback($request, forceFailed: false);
    }

    public function odemeBasarisiz(Request $request)
    {
        return $this->handleOdemeCallback($request, forceFailed: true);
    }

    private function handleOdemeCallback(Request $request, bool $forceFailed): \Illuminate\Http\RedirectResponse
    {
        $payload = $request->all();
        $gateway = app(PaynkolayGatewayService::class);

        if (!$gateway->isValidResponseHash($payload)) {
            return redirect()->route('acente.sigorta.create')
                ->with('error', 'Ödeme doğrulaması başarısız.');
        }

        if ($forceFailed) {
            $payload['response_code']  = '0';
            $payload['auth_code']      = '0';
            $payload['payment_status'] = 'failed';
        }

        $internalRef = trim((string) (
            $payload['clientRefCode']
            ?? $payload['CLIENT_REFERENCE_CODE']
            ?? $payload['client_reference_code']
            ?? ''
        ));

        $odeme = SigortaOdeme::where('internal_reference', $internalRef)->first();

        if (!$odeme || (!$odeme->sigorta_police_id && !$odeme->sigorta_batch_job_id)) {
            return redirect()->route('acente.sigorta.index')
                ->with('error', 'Ödeme kaydı bulunamadı.');
        }

        // Duplicate callback guard
        if ($odeme->paid_at || $odeme->failed_at) {
            if ($odeme->sigorta_batch_job_id) {
                return redirect()->route('acente.sigorta.toplu-sonuc', $odeme->sigorta_batch_job_id);
            }
            return redirect()->route('acente.sigorta.show', $odeme->sigorta_police_id);
        }

        $mappedStatus = $gateway->mapCallbackStatus($payload);
        $providerRef  = trim((string) ($payload['reference_code'] ?? $payload['REFERENCE_CODE'] ?? ''));

        // ── Batch ödeme ──────────────────────────────────────────────────────
        if ($odeme->sigorta_batch_job_id) {
            $batch = SigortaBatchJob::find($odeme->sigorta_batch_job_id);

            if ($mappedStatus !== 'paid') {
                $reason = Str::limit((string) ($payload['RESPONSE_DATA'] ?? $payload['message'] ?? 'Ödeme reddedildi.'), 350);
                $odeme->update([
                    'status'                => 'rejected',
                    'callback_payload_json' => $payload,
                    'failure_reason'        => $reason,
                    'failed_at'             => now(),
                ]);
                $batch?->update(['durum' => 'hata']);
                return redirect()->route('acente.sigorta.toplu')
                    ->with('error', 'Ödeme başarısız. Poliçeler düzenlenmedi.');
            }

            $odeme->update([
                'status'                => 'approved',
                'provider_reference'    => $providerRef ?: $odeme->provider_reference,
                'callback_payload_json' => $payload,
                'paid_at'               => now(),
            ]);

            $batch?->update(['durum' => 'isleniyor']);

            return redirect()->route('acente.sigorta.toplu-sonuc', $batch->id)
                ->with('success', 'Ödeme alındı, poliçeler işleniyor.');
        }

        // ── Tekil ödeme ──────────────────────────────────────────────────────
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

            return redirect()->route('acente.sigorta.create')
                ->with('error', 'Ödeme başarısız. Poliçe düzenlenmedi.');
        }

        $odeme->update([
            'status'                => 'approved',
            'provider_reference'    => $providerRef ?: $odeme->provider_reference,
            'callback_payload_json' => $payload,
            'paid_at'               => now(),
        ]);

        $police = SigortaPolice::find($odeme->sigorta_police_id);
        $police->update(['durum' => 'police_isleniyor']);

        try {
            $svc      = app(PaoNetService::class);
            $sonuc    = $svc->policeUret($police->paonet_teklif_id);
            $referans = $sonuc['Referans'] ?? $sonuc['referans'] ?? '';
            $police->update(['paonet_referans' => $referans]);
        } catch (\RuntimeException $e) {
            Log::critical('sigorta.b2b.paonet_basarisiz_odeme_alindi', [
                'police_id' => $police->id,
                'error'     => $e->getMessage(),
            ]);
            $police->update(['durum' => 'hata', 'hata_mesaji' => $e->getMessage()]);
        }

        return redirect()->route('acente.sigorta.show', $police->id)
            ->with('success', 'Ödeme alındı, poliçeniz işleniyor.');
    }

    // ── Üretim Durum Poll AJAX (CHKAUTOPOLICY) ─────────────────────────────────

    public function policeUretimDurum(Request $request, SigortaPolice $police)
    {
        abort_unless($police->acente_id === $this->acenteActor()->id, 403);

        if ($police->durum === 'tamamlandi') {
            return response()->json(['durum' => 'tamamlandi', 'police_no' => $police->police_no]);
        }

        if (!$this->aktifMi() || empty($police->paonet_referans)) {
            return response()->json(['durum' => $police->durum]);
        }

        try {
            $svc   = app(PaoNetService::class);
            $sonuc = $svc->uretimDurumu($police->paonet_referans);

            $policeNo = $sonuc['PoliceNo'] ?? $sonuc['policeNo'] ?? '';

            if ($policeNo) {
                // PDF linklerini de çek
                $pdfData = $svc->pdfGetir($policeNo);
                $police->update([
                    'police_no'          => $policeNo,
                    'durum'              => 'tamamlandi',
                    'pdf_url_base'       => $pdfData['PdfUrlBase'] ?? '',
                    'pdf_link'           => $pdfData['PdfLink'] ?? '',
                    'makbuz_link'        => $pdfData['MakbuzLink'] ?? '',
                    'sertifika_link'     => $pdfData['SertifikaLink'] ?? '',
                    'ing_sertifika_link' => $pdfData['IngSertifikaLink'] ?? '',
                ]);

                try { (new \App\Services\EmailService())->policeHazir($police->fresh()); } catch (\Throwable) {}

                return response()->json(['durum' => 'tamamlandi', 'police_no' => $policeNo]);
            }

            return response()->json(['durum' => 'police_isleniyor']);
        } catch (\RuntimeException $e) {
            return response()->json(['durum' => $police->durum, 'hata' => $e->getMessage()]);
        }
    }

    // ── Poliçe Detay ──────────────────────────────────────────────────────────

    public function show(SigortaPolice $police)
    {
        abort_unless($police->acente_id === $this->acenteActor()->id, 403);
        return view('acente.sigorta.show', compact('police'));
    }

    // ── Toplu İşlem Form ─────────────────────────────────────────────────────

    public function toplu()
    {
        return view('acente.sigorta.toplu', [
            'aktif' => $this->aktifMi(),
        ]);
    }

    // ── CSV Şablon İndir ─────────────────────────────────────────────────────

    public function topluSablon()
    {
        $bom     = "\xEF\xBB\xBF"; // Excel için UTF-8 BOM
        $header  = "kimlik,adi,soyadi,baba_adi,dogum_tarihi,dogum_yeri,cinsiyet,uyruk,boy,kilo,il,ilce,adres\n";
        $tc      = "12345678901,Ahmet,Yılmaz,,1985-05-15,,E,,,,,, \n";
        $pspr    = "AB1234567,Ivan,Petrov,Nikolai,1990-03-20,Moskova,E,Rus,180,75,İstanbul,Şişli,\"Cumhuriyet Cad. No:10\"\n";

        return response($bom . $header . $tc . $pspr, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sigorta-sablon.csv"',
        ]);
    }

    // ── Toplu Batch Başlat → sadece fiyat hesaplama aşaması açılır ──────────────

    public function topluBaslat(Request $request)
    {
        $request->validate([
            'islem_adi'        => 'nullable|string|max:120',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi'     => 'required|date|after:baslangic_tarihi',
            'ulke'             => 'required|string|max:80',
            'satirlar'         => 'required|array|min:1|max:200',
            'satirlar.*.kimlik'      => 'required|string|max:20',
            'satirlar.*.adi'         => 'required|string|max:80',
            'satirlar.*.soyadi'      => 'required|string|max:80',
            'satirlar.*.dogum_tarihi'=> 'required|date',
        ]);

        $satirlar = array_map(fn ($s) => array_merge($s, [
            'baslangic_tarihi' => $request->baslangic_tarihi,
            'bitis_tarihi'     => $request->bitis_tarihi,
            'ulke'             => $request->ulke,
        ]), $request->satirlar);

        $batch = SigortaBatchJob::create([
            'islem_adi'         => $request->islem_adi ?: 'Toplu Sigorta ' . now()->format('d.m.Y H:i'),
            'kanal'             => 'b2b',
            'acente_id'         => $this->acenteActor()->id,
            'toplam'            => count($satirlar),
            'durum'             => 'fiyat_hesaplaniyor',
            'bekleyen_satirlar' => $satirlar,
        ]);

        return response()->json(['ok' => true, 'batch_id' => $batch->id]);
    }

    // ── Fiyat Hesaplama Poll (teklifAl — 4 kişi/çağrı) ────────────────────────

    public function topluFiyatPoll(Request $request, SigortaBatchJob $batch)
    {
        abort_unless($batch->acente_id === $this->acenteActor()->id, 403);

        if (!in_array($batch->durum, ['fiyat_hesaplaniyor'])) {
            return response()->json([
                'tamamlandi'   => true,
                'fiyatlanan'   => count($batch->fiyatlanmis_satirlar ?? []),
                'toplam'       => $batch->toplam,
                'total_tl'     => (float) $batch->total_amount_try,
                'batch_durum'  => $batch->durum,
            ]);
        }

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
        }

        $bekleyen    = $batch->bekleyen_satirlar ?? [];
        $fiyatlanmis = $batch->fiyatlanmis_satirlar ?? [];
        $markup      = (float) $this->ayar('b2b_markup_yuzde', 20);
        $tampon      = (float) $this->ayar('kur_tamponu_yuzde', 5);

        if (empty($bekleyen)) {
            $total = array_sum(array_column($fiyatlanmis, 'satis_fiyat'));
            $batch->update([
                'durum'            => 'odeme_bekleniyor',
                'total_amount_try' => $total,
            ]);
            return response()->json([
                'tamamlandi'  => true,
                'fiyatlanan'  => count($fiyatlanmis),
                'toplam'      => $batch->toplam,
                'total_tl'    => $total,
                'batch_durum' => 'odeme_bekleniyor',
            ]);
        }

        $islenecekler = array_splice($bekleyen, 0, 4);
        $svc          = app(PaoNetService::class);

        foreach ($islenecekler as $satir) {
            try {
                $kimlikT  = PaoNetHelper::detectKimlikTipi($satir['kimlik']);
                $urunKodu = PaoNetHelper::urunKodu($kimlikT);

                $msgParams = [
                    'Kimlik'          => $satir['kimlik'],
                    'Adi'             => $satir['adi'],
                    'Soyadi'          => $satir['soyadi'],
                    'DogumTarihi'     => $satir['dogum_tarihi'],
                    'BaslangicTarihi' => $satir['baslangic_tarihi'],
                    'BitisTarihi'     => $satir['bitis_tarihi'],
                    'GidilecekUlke'   => $satir['ulke'],
                ];
                if ($kimlikT === 'pasaport') {
                    $msgParams['DogumYeri'] = $satir['dogum_yeri'] ?? '';
                    $msgParams['Uyruk']     = $satir['uyruk']      ?? '';
                    $msgParams['Boy']       = $satir['boy']         ?? '';
                    $msgParams['Kilo']      = $satir['kilo']        ?? '';
                    $msgParams['IlAdi']     = $satir['il_adi']      ?? '';
                    $msgParams['IlceAdi']   = $satir['ilce_adi']    ?? '';
                    $msgParams['Adres']     = $satir['adres']       ?? '';
                }

                $strMsg = PaoNetHelper::buildStrMsg($msgParams);
                $teklif = $svc->teklifAl($urunKodu, $strMsg);

                $bprim  = (float) ($teklif['Bprim'] ?? 0);
                $dkuru  = (float) ($teklif['Dkuru'] ?? 1);
                $fiyat  = PaoNetHelper::hesaplaSatisFiyati($bprim, $dkuru, $markup, $tampon);

                $fiyatlanmis[] = array_merge($satir, [
                    'teklif_id'   => $teklif['TeklifId'] ?? $teklif['teklifId'] ?? '',
                    'urun_kodu'   => $urunKodu,
                    'kimlik_tipi' => $kimlikT,
                    'doviz_turu'  => $teklif['DovizTuru'] ?? 'USD',
                    'bprim'       => $bprim,
                    'dkuru'       => $dkuru,
                    'maliyet_tl'  => $fiyat['maliyet_tl'],
                    'satis_fiyat' => $fiyat['satis_fiyat'],
                    'net_kar'     => $fiyat['net_kar'],
                ]);
            } catch (\RuntimeException $e) {
                // Fiyatlama hatası — satırı bilgiyle geri ekle, devam et
                $fiyatlanmis[] = array_merge($satir, [
                    'teklif_id'   => null,
                    'hata'        => $e->getMessage(),
                    'satis_fiyat' => 0,
                ]);
                Log::warning('Batch fiyat hatası', ['satir' => $satir, 'hata' => $e->getMessage()]);
            }
        }

        $batch->update([
            'bekleyen_satirlar'    => array_values($bekleyen),
            'fiyatlanmis_satirlar' => $fiyatlanmis,
        ]);

        return response()->json([
            'tamamlandi'  => false,
            'fiyatlanan'  => count($fiyatlanmis),
            'toplam'      => $batch->toplam,
            'kalan'       => count($bekleyen),
            'batch_durum' => 'fiyat_hesaplaniyor',
        ]);
    }

    // ── Toplu Ödeme Başlat ───────────────────────────────────────────────────

    public function topluOdemeBaslat(Request $request, SigortaBatchJob $batch)
    {
        abort_unless($batch->acente_id === $this->acenteActor()->id, 403);
        abort_unless($batch->durum === 'odeme_bekleniyor', 422, 'Batch henüz fiyatlanmadı.');

        $existingOdeme = $batch->odeme;
        if ($existingOdeme && $existingOdeme->paid_at) {
            return response()->json(['error' => 'Bu batch için ödeme zaten alındı.'], 422);
        }

        $internalRef = 'BTY-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(6));

        $odeme = SigortaOdeme::create([
            'sigorta_batch_job_id' => $batch->id,
            'kanal'                => 'b2b',
            'internal_reference'   => $internalRef,
            'amount_try'           => $batch->total_amount_try,
            'status'               => 'pending',
            'request_payload_json' => ['batch_id' => $batch->id, 'acente_id' => $this->acenteActor()->id],
        ]);

        try {
            $gateway     = app(PaynkolayGatewayService::class);
            $initialized = $gateway->initializePayment(
                clientReference: $internalRef,
                amountTry:       (float) $batch->total_amount_try,
                successUrl:      route('acente.sigorta.odeme.basarili'),
                failUrl:         route('acente.sigorta.odeme.basarisiz'),
                cardHolderIp:    (string) $request->ip(),
            );

            $odeme->update(['provider_reference' => $initialized['provider_reference']]);

            return response()->json([
                'ok'          => true,
                'payment_url' => $initialized['redirect_url'],
            ]);
        } catch (\Throwable $e) {
            $odeme->update(['status' => 'rejected', 'failure_reason' => $e->getMessage(), 'failed_at' => now()]);
            return response()->json(['error' => 'Ödeme başlatılamadı: ' . Str::limit($e->getMessage(), 200)], 422);
        }
    }

    // ── Toplu Sonuç Sayfası (ödeme sonrası) ─────────────────────────────────

    public function topluSonuc(SigortaBatchJob $batch)
    {
        abort_unless($batch->acente_id === $this->acenteActor()->id, 403);
        return view('acente.sigorta.toplu-sonuc', compact('batch'));
    }

    // ── Toplu Poliçe Üretim Poll (policeUret — 4 kişi/çağrı) ─────────────────

    public function topluUretPoll(Request $request, SigortaBatchJob $batch)
    {
        abort_unless($batch->acente_id === $this->acenteActor()->id, 403);

        if ($batch->tamamlandiMi()) {
            return response()->json([
                'tamamlandi' => true,
                'tamamlanan' => $batch->tamamlanan,
                'basarisiz'  => $batch->basarisiz,
                'toplam'     => $batch->toplam,
            ]);
        }

        if (!$this->aktifMi()) {
            return response()->json(['error' => 'Sigorta modülü henüz aktif değil.'], 503);
        }

        $fiyatlanmis = $batch->fiyatlanmis_satirlar ?? [];
        if (empty($fiyatlanmis)) {
            $batch->update(['durum' => 'tamamlandi']);
            return response()->json(['tamamlandi' => true, 'tamamlanan' => $batch->tamamlanan, 'basarisiz' => $batch->basarisiz, 'toplam' => $batch->toplam]);
        }

        $markup = (float) $this->ayar('b2b_markup_yuzde', 20);
        $tampon = (float) $this->ayar('kur_tamponu_yuzde', 5);
        $svc    = app(PaoNetService::class);

        $islenecekler = array_splice($fiyatlanmis, 0, 4);

        foreach ($islenecekler as $satir) {
            if (empty($satir['teklif_id'])) {
                // Fiyatlama hatası olan satır — skip
                $batch->increment('basarisiz');
                continue;
            }
            try {
                $sonuc    = $svc->policeUret($satir['teklif_id']);
                $referans = $sonuc['Referans'] ?? $sonuc['referans'] ?? '';

                SigortaPolice::create([
                    'batch_job_id'      => $batch->id,
                    'acente_id'         => $batch->acente_id,
                    'kanal'             => 'b2b',
                    'paonet_referans'   => $referans,
                    'paonet_teklif_id'  => $satir['teklif_id'],
                    'paonet_urun_kodu'  => $satir['urun_kodu'] ?? '',
                    'sigortali_kimlik'  => $satir['kimlik'],
                    'kimlik_tipi'       => $satir['kimlik_tipi'] ?? PaoNetHelper::detectKimlikTipi($satir['kimlik']),
                    'sigortali_adi'     => $satir['adi'],
                    'sigortali_soyadi'  => $satir['soyadi'],
                    'sigortali_dogum'   => $satir['dogum_tarihi'],
                    'baslangic_tarihi'  => $satir['baslangic_tarihi'],
                    'bitis_tarihi'      => $satir['bitis_tarihi'],
                    'gidilecek_ulke'    => $satir['ulke'],
                    'api_doviz_turu'    => $satir['doviz_turu'] ?? 'USD',
                    'api_doviz_tutar'   => $satir['bprim'] ?? 0,
                    'api_kur'           => $satir['dkuru'] ?? 1,
                    'maliyet_tl'        => $satir['maliyet_tl'] ?? 0,
                    'b2b_fiyat_tl'      => $satir['satis_fiyat'] ?? 0,
                    'satilan_fiyat_tl'  => $satir['satis_fiyat'] ?? 0,
                    'net_kar_tl'        => $satir['net_kar'] ?? 0,
                    'markup_yuzde'      => $markup,
                    'kur_tamponu_yuzde' => $tampon,
                    'durum'             => 'police_isleniyor',
                ]);

                $batch->increment('tamamlanan');
            } catch (\RuntimeException $e) {
                $batch->increment('basarisiz');
                Log::warning('Batch policeUret hatası', ['satir' => $satir, 'hata' => $e->getMessage()]);
            }
        }

        $yeniDurum = empty($fiyatlanmis) ? 'tamamlandi' : 'isleniyor';
        $batch->update([
            'fiyatlanmis_satirlar' => array_values($fiyatlanmis),
            'durum'                => $yeniDurum,
        ]);

        return response()->json([
            'tamamlandi' => $yeniDurum === 'tamamlandi',
            'tamamlanan' => $batch->fresh()->tamamlanan,
            'basarisiz'  => $batch->fresh()->basarisiz,
            'toplam'     => $batch->toplam,
            'kalan'      => count($fiyatlanmis),
        ]);
    }

    // ── Batch Retry (Hatalı Kayıtlar) ─────────────────────────────────────────

    public function topluRetry(Request $request, SigortaBatchJob $batch)
    {
        abort_unless($batch->acente_id === $this->acenteActor()->id, 403);

        // Retry only on fiyat errors (fiyatlanmis_satirlar with no teklif_id)
        $fiyatlanmis = $batch->fiyatlanmis_satirlar ?? [];
        $hataliFiyat = array_filter($fiyatlanmis, fn ($s) => empty($s['teklif_id']));
        $basarili    = array_filter($fiyatlanmis, fn ($s) => !empty($s['teklif_id']));

        if (empty($hataliFiyat)) {
            return response()->json(['error' => 'Yeniden denenecek kayıt yok.'], 422);
        }

        // Move failed back to bekleyen for re-pricing
        $yenidenBekleyen = array_map(function ($s) {
            unset($s['teklif_id'], $s['hata'], $s['urun_kodu'], $s['kimlik_tipi'],
                  $s['doviz_turu'], $s['bprim'], $s['dkuru'], $s['maliyet_tl'],
                  $s['satis_fiyat'], $s['net_kar']);
            return $s;
        }, array_values($hataliFiyat));

        $batch->update([
            'bekleyen_satirlar'    => $yenidenBekleyen,
            'fiyatlanmis_satirlar' => array_values($basarili),
            'durum'                => 'fiyat_hesaplaniyor',
        ]);

        return response()->json(['ok' => true]);
    }

    // ── MBF Form (B2B only) ───────────────────────────────────────────────────

    public function mbf()
    {
        return view('acente.sigorta.mbf', [
            'aktif' => $this->aktifMi(),
        ]);
    }

    public function mbfGonder(Request $request)
    {
        $request->validate([
            'trap_type' => 'required|in:1,2,3',
        ]);

        if (!$this->aktifMi()) {
            return back()->withErrors(['aktif' => 'Sigorta modülü henüz aktif değil.']);
        }

        try {
            $svc  = app(PaoNetService::class);
            $data = $svc->mbfOlustur($request->except('_token'));
            return back()->with('success', 'MBF başarıyla oluşturuldu. Referans: ' . ($data['Referans'] ?? ''));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['mbf' => $e->getMessage()]);
        }
    }

    // ── PDF Belge Proxy ───────────────────────────────────────────────────────

    public function belge(SigortaPolice $police, string $tip)
    {
        abort_unless($police->acente_id === $this->acenteActor()->id, 403);
        abort_unless(in_array($tip, ['police', 'makbuz', 'sertifika', 'ing-sertifika']), 404);

        $urlMap = [
            'police'       => $police->pdf_link,
            'makbuz'       => $police->makbuz_link,
            'sertifika'    => $police->sertifika_link,
            'ing-sertifika'=> $police->ing_sertifika_link,
        ];

        $rawUrl = $urlMap[$tip] ?? null;
        abort_if(empty($rawUrl), 404, 'Belge bulunamadı.');

        return app(PaoNetService::class)->pdfStream(
            PaoNetHelper::normalizePdfUrl($rawUrl)
        );
    }

    // ── Kâr Raporu (Acente) ───────────────────────────────────────────────────

    public function karRaporu(Request $request)
    {
        $acenteId = $this->acenteActor()->id;

        $buAy = SigortaPolice::where('acente_id', $acenteId)
            ->where('durum', 'tamamlandi')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('COUNT(*) as adet, SUM(satilan_fiyat_tl) as toplam_satis, SUM(net_kar_tl) as toplam_kar')
            ->first();

        $buYil = SigortaPolice::where('acente_id', $acenteId)
            ->where('durum', 'tamamlandi')
            ->whereYear('created_at', now()->year)
            ->selectRaw('COUNT(*) as adet, SUM(satilan_fiyat_tl) as toplam_satis, SUM(net_kar_tl) as toplam_kar')
            ->first();

        $gunluk = SigortaPolice::where('acente_id', $acenteId)
            ->where('durum', 'tamamlandi')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('DATE(created_at) as tarih, COUNT(*) as adet, SUM(net_kar_tl) as kar')
            ->groupBy('tarih')
            ->orderBy('tarih')
            ->get();

        return view('acente.sigorta.kar-raporu', compact('buAy', 'buYil', 'gunluk'));
    }

    // ── İptal ─────────────────────────────────────────────────────────────────

    public function iptal(Request $request, SigortaPolice $police)
    {
        abort_unless($police->acente_id === $this->acenteActor()->id, 403);
        abort_unless($police->iptalEdilebilirMi(), 422, 'Bu poliçe iptal edilemez.');

        $request->validate([
            'iptal_nedeni'    => 'required|string|max:255',
            'mukerrer_police' => 'required|string|max:80',
        ]);

        if (!$this->aktifMi()) {
            return back()->withErrors(['aktif' => 'Sigorta modülü henüz aktif değil.']);
        }

        try {
            $svc = app(PaoNetService::class);
            $svc->iptalEkle($police->police_no, $request->mukerrer_police, $request->iptal_nedeni);

            $police->update([
                'durum'              => 'iptal_bekliyor',
                'iptal_nedeni'       => $request->iptal_nedeni,
                'mukerrer_police_no' => $request->mukerrer_police,
                'iptal_tarih'        => now(),
            ]);

            return back()->with('success', 'İptal talebi PAO-Net\'e gönderildi.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['iptal' => $e->getMessage()]);
        }
    }
}

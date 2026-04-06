<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\Request as TalepModel;
use App\Models\Offer;
use App\Models\Request as RequestModel;
use App\Models\RequestLog;
use App\Models\RequestPayment;
use App\Models\User;
use App\Services\EmailService;
use App\Services\Finance\FinanceSyncService;
use App\Services\GtpnrService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RequestController extends Controller
{
    // Aktif olmayan statüler — varsayılan görünümde gizlenir
    private array $pasifStatusler = [
        RequestModel::STATUS_BILETLENDI,
        RequestModel::STATUS_OLUMSUZ,
        RequestModel::STATUS_IADE,
        RequestModel::STATUS_IPTAL,
    ];

    public function index(Request $request)
    {
        $query = TalepModel::with([
                'user', 'segments', 'offers',
                'payments' => fn($q) => $q->where('is_active', true),
            ]);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('gtpnr', 'like', "%{$q}%")
                  ->orWhere('agency_name', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($request->input('durum') === 'tumu') {
            // Arşiv dahil tümü — filtre uygulanmaz
        } elseif ($request->filled('durum')) {
            $query->where('status', $request->durum);
        } else {
            // Varsayılan: sadece aktif talepler
            $query->whereNotIn('status', $this->pasifStatusler);
        }

        if ($request->filled('tarih_baslangic')) {
            $query->whereDate('created_at', '>=', $request->tarih_baslangic);
        }

        if ($request->filled('tarih_bitis')) {
            $query->whereDate('created_at', '<=', $request->tarih_bitis);
        }

        if ((int) $request->input('teklif') === 1) {
            $query->whereHas('offers');
        }

        if ((int) $request->input('opsiyon') === 1) {
            $simdiSaat = now()->format('H:i:s');
            $query->whereHas('offers', function ($offerQuery) use ($simdiSaat) {
                $offerQuery->whereNotNull('option_date')
                    ->where(function ($activeQuery) use ($simdiSaat) {
                        $activeQuery->whereDate('option_date', '>', today())
                            ->orWhere(function ($todayQuery) use ($simdiSaat) {
                                $todayQuery->whereDate('option_date', today())
                                    ->where(function ($timeQuery) use ($simdiSaat) {
                                        $timeQuery->whereNull('option_time')
                                            ->orWhere('option_time', '>=', $simdiSaat);
                                    });
                            });
                    });
            });
        }

        if ($request->filled('adim')) {
            $adim = $request->input('adim');
            if ($adim === 'odeme_bekleniyor') {
                // Ödeme bekliyor + gecikti + kısmi alındı devam ediyor
                $query->whereIn('aktif_adim', ['odeme_bekleniyor', 'odeme_gecikti', 'odeme_alindi_devam']);
            } else {
                $query->where('aktif_adim', $adim);
            }
        }

        // Sıralama: opsiyonda filtresi aktifse yakın opsiyon tarihi, aksi hâlde yeni talep önce
        if ((int) $request->input('opsiyon') === 1) {
            $query->orderByRaw("(
                SELECT MIN(CONCAT(option_date, ' ', COALESCE(option_time, '15:59:59')))
                FROM offers
                WHERE offers.request_id = requests.id
                  AND offers.option_date IS NOT NULL
            ) ASC");
        } else {
            $query->orderBy('id', 'desc');
        }

        $talepler = $query->paginate(20)->withQueryString();

        $durumSayilari = TalepModel::selectRaw('status, count(*) as toplam')
            ->groupBy('status')
            ->pluck('toplam', 'status');

        $aktifSayisi = TalepModel::whereNotIn('status', $this->pasifStatusler)->count();

        $simdiSaat = now()->format('H:i:s');
        $opsiyonSayisi = TalepModel::whereNotIn('status', $this->pasifStatusler)
            ->whereHas('offers', fn($q) => $q->whereNotNull('option_date')
                ->where(fn($aq) => $aq
                    ->whereDate('option_date', '>', today())
                    ->orWhere(fn($tq) => $tq
                        ->whereDate('option_date', today())
                        ->where(fn($q2) => $q2->whereNull('option_time')->orWhere('option_time', '>=', $simdiSaat))
                    )
                )
            )->count();

        $adimSayilari = TalepModel::whereNotIn('status', $this->pasifStatusler)
            ->selectRaw('aktif_adim, count(*) as toplam')
            ->groupBy('aktif_adim')
            ->pluck('toplam', 'aktif_adim');

        $odemeSayisi = ($adimSayilari['odeme_bekleniyor'] ?? 0)
                     + ($adimSayilari['odeme_gecikti'] ?? 0)
                     + ($adimSayilari['odeme_alindi_devam'] ?? 0);

        return view('admin.requests.index', compact(
            'talepler', 'durumSayilari', 'aktifSayisi', 'opsiyonSayisi', 'adimSayilari', 'odemeSayisi'
        ));
    }

    public function create()
    {
        $acenteler = User::whereIn('role', ['acente', 'admin', 'superadmin'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        return view('admin.requests.create', compact('acenteler'));
    }

    public function storeOnBehalf(Request $request)
    {
        $validated = $request->validate([
            'acente_user_id'           => 'required|exists:users,id',
            'agency_name'              => 'required|string',
            'phone'                    => 'required|string',
            'email'                    => 'required|email',
            'group_company_name'       => 'nullable|string',
            'flight_purpose'           => 'nullable|string',
            'trip_type'                => 'required|string|in:one_way,round_trip,multi',
            'pax_total'                => 'required|integer|min:1',
            'pax_adult'                => 'nullable|integer',
            'pax_child'                => 'nullable|integer',
            'pax_infant'               => 'nullable|integer',
            'preferred_airline'        => 'nullable|string',
            'notes'                    => 'nullable|string',
            'segments'                 => 'required|array|min:1',
            'segments.*.from_iata'     => 'required|string',
            'segments.*.to_iata'       => 'required|string',
            'segments.*.departure_date'=> 'required|date',
            'segments.*.departure_time_slot' => 'required|in:sabah,ogle,aksam,esnek',
        ]);

        $gtpnr = (new GtpnrService())->generate('group_flight');
        $acenteUser = User::findOrFail($validated['acente_user_id']);

        $talep = TalepModel::create([
            'gtpnr'              => $gtpnr,
            'user_id'            => $acenteUser->id,
            'type'               => 'group_flight',
            'status'             => 'beklemede',
            'agency_name'        => mb_strtoupper($validated['agency_name'], 'UTF-8'),
            'phone'              => $validated['phone'],
            'email'              => $validated['email'],
            'group_company_name' => $validated['group_company_name'] ?? null,
            'flight_purpose'     => $validated['flight_purpose'] ?? null,
            'trip_type'          => $validated['trip_type'],
            'pax_total'          => $validated['pax_total'],
            'pax_adult'          => $validated['pax_adult'] ?? 0,
            'pax_child'          => $validated['pax_child'] ?? 0,
            'pax_infant'         => $validated['pax_infant'] ?? 0,
            'preferred_airline'  => $validated['preferred_airline'] ?? null,
            'hotel_needed'       => $request->boolean('hotel_needed'),
            'visa_needed'        => $request->boolean('visa_needed'),
            'notes'              => $validated['notes'] ?? null,
        ]);

        $iataCodes = collect($validated['segments'])->flatMap(fn($s) => [
            strtoupper($s['from_iata']),
            strtoupper($s['to_iata']),
        ])->unique()->values();

        $airportMap = Airport::whereIn('iata', $iataCodes)
            ->get(['iata', 'city', 'name'])
            ->keyBy('iata');

        foreach ($validated['segments'] as $index => $segment) {
            $fromIata = strtoupper($segment['from_iata']);
            $toIata   = strtoupper($segment['to_iata']);
            $fromAp   = $airportMap[$fromIata] ?? null;
            $toAp     = $airportMap[$toIata]   ?? null;

            $talep->segments()->create([
                'order'                => $index,
                'from_iata'            => $fromIata,
                'from_city'            => $fromAp ? ($fromAp->city ?: $fromAp->name) : null,
                'to_iata'              => $toIata,
                'to_city'              => $toAp ? ($toAp->city ?: $toAp->name) : null,
                'departure_date'       => $segment['departure_date'],
                'departure_time'       => $segment['departure_time'] ?? null,
                'departure_time_slot'  => $segment['departure_time_slot'],
            ]);
        }

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'talep_olusturuldu',
            'description' => 'Talep admin tarafından oluşturuldu: ' . auth()->user()->name . ' → Acenta: ' . $acenteUser->name,
            'user_id'     => auth()->id(),
        ]);

        return redirect()->route('admin.requests.show', $talep->gtpnr)
            ->with('success', 'Talep oluşturuldu: ' . $gtpnr);
    }

    public function show($gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->with(['user', 'segments', 'offers', 'logs.user', 'payments.offer', 'notifications'])
            ->firstOrFail();

        return view('admin.requests.show', compact('talep'));
    }

    public function updateStatus(Request $request, $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();

        if ($talep->status === RequestModel::STATUS_BILETLENDI) {
            return back()->with('error', 'Biletlenmis talepler degistirilemez.');
        }

        $gecerliDurumlar = [
            RequestModel::STATUS_BEKLEMEDE,
            RequestModel::STATUS_ISLEMDE,
            RequestModel::STATUS_FIYATLANDIRILDI,
            RequestModel::STATUS_DEPOZITODA,
            RequestModel::STATUS_BILETLENDI,
            RequestModel::STATUS_IADE,
            RequestModel::STATUS_OLUMSUZ,
        ];
        $yeniDurum = RequestModel::normalizeStatus($request->status);

        if (!in_array($yeniDurum, $gecerliDurumlar, true)) {
            return back()->with('error', 'Gecersiz durum.');
        }

        $eskiDurum = $talep->status;
        $talep->update(['status' => $yeniDurum]);
        $talep->refreshAktifAdim();

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'durum_degisti',
            'description' => 'Durum degisti: ' . $eskiDurum . ' -> ' . $yeniDurum,
            'user_id'     => auth()->id(),
        ]);

        // Depozitoda geçişinde kabul_edildi teklif yoksa tek fiyatlı teklifi otomatik kabul et
        if ($yeniDurum === RequestModel::STATUS_DEPOZITODA) {
            $kabulVar = $talep->offers()->where('durum', \App\Models\Offer::DURUM_KABUL)->exists();
            if (!$kabulVar) {
                $fiyatliTeklifler = $talep->offers()
                    ->where('durum', \App\Models\Offer::DURUM_BEKLEMEDE)
                    ->where('price_per_pax', '>', 0)
                    ->get();
                if ($fiyatliTeklifler->count() === 1) {
                    $fiyatliTeklifler->first()->update(['durum' => \App\Models\Offer::DURUM_KABUL]);
                    $talep->refreshAktifAdim();
                    RequestLog::create([
                        'request_id'  => $talep->id,
                        'action'      => 'teklif_kabul',
                        'description' => 'Teklif otomatik kabul edildi (depozito geçişi)',
                        'user_id'     => auth()->id(),
                    ]);
                }
            }
        }

        $acenteUrl = route('acente.requests.show', $talep->gtpnr);

        try {
            if ($request->boolean('notify_push_acente') && $talep->user_id) {
                (new NotificationService())->durumDegisti($talep->user_id, $talep->gtpnr, $eskiDurum, $yeniDurum, $acenteUrl);
            }

            if ($request->boolean('notify_sms_acente') && $talep->phone) {
                $smsMsg = $talep->gtpnr . ' numaralı talebinizin durumu güncellendi: ' . $yeniDurum . '. Detaylar için sisteme giriş yapınız.';
                (new SmsService())->send($talep->id, 'acente', (string) $talep->agency_name, (string) $talep->phone, $smsMsg);
            }

            if ($request->boolean('notify_email_acente') && $talep->user_id) {
                (new EmailService())->durumDegisti($talep->id, $talep->user_id, $talep->gtpnr, $eskiDurum, $yeniDurum, $acenteUrl);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('updateStatus bildirim hatasi: ' . $e->getMessage(), [
                'gtpnr' => $talep->gtpnr,
                'file'  => $e->getFile() . ':' . $e->getLine(),
            ]);
            return back()->with('success', 'Durum guncellendi.')->with('warning', 'Bildirim gönderilemedi: ' . $e->getMessage());
        }

        return back()->with('success', 'Durum guncellendi.');
    }

    public function storeOffer(Request $request, $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();

        // Mükerrer PNR kontrolü
        if ($request->airline_pnr) {
            $pnrExists = $talep->offers()->where('airline_pnr', $request->airline_pnr)->exists();
            if ($pnrExists) {
                return back()->with('error', 'Bu PNR ile teklif zaten mevcut: ' . $request->airline_pnr . '. Mükerrer kayıt engellendi.');
            }
        }

        $currency = $request->currency ?: 'TRY';
        $paxCount = $request->pax_confirmed ?: $talep->pax_total;

        $yeniTeklif = $talep->offers()->create([
            'airline'               => $request->airline,
            'airline_pnr'           => $request->airline_pnr,
            'flight_number'         => $request->flight_number,
            'flight_departure_time' => $request->flight_departure_time ?: null,
            'flight_arrival_time'   => $request->flight_arrival_time ?: null,
            'baggage_kg'            => $request->baggage_kg ?: null,
            'pax_confirmed'         => $request->pax_confirmed ?: null,
            'supplier_reference'    => $request->supplier_reference,
            'currency'              => $currency,
            'price_per_pax'         => $request->price_per_pax,
            'total_price'           => $request->price_per_pax * $paxCount,
            'cost_price'            => $request->cost_price ?: null,
            'profit_amount'         => $request->profit_amount ?: null,
            'profit_percent'        => $request->profit_percent ?: null,
            'deposit_rate'          => $request->deposit_rate ?: null,
            'deposit_amount'        => $request->deposit_amount ?: null,
            'kk_enabled'            => $request->boolean('kk_enabled'),
            'option_date'           => $request->option_date ?: null,
            'option_time'           => $request->option_time ?: null,
            'offer_text'            => $request->offer_text,
            'admin_raw_note'        => $request->admin_raw_note,
            'ai_raw_output'         => $request->ai_raw_output ? json_decode($request->ai_raw_output, true) : null,
            'created_by'            => auth()->user()->name,
            'durum'                 => \App\Models\Offer::DURUM_BEKLEMEDE,
        ]);

        $talep->update(['status' => RequestModel::STATUS_FIYATLANDIRILDI]);
        $talep->refreshAktifAdim();

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_eklendi',
            'description' => $request->airline . ' için teklif eklendi — ' . $request->price_per_pax . ' ' . $request->currency . '/kişi',
            'user_id'     => auth()->id(),
        ]);

        $acenteUrl = route('acente.requests.show', $talep->gtpnr);

        // Bildirimler — sadece admin tarafından seçilenler gönderilir
        try {
            if ($request->boolean('notify_push_acente') && $talep->user_id) {
                (new NotificationService())->teklifEklendi($talep->user_id, $talep->gtpnr, $request->airline, $acenteUrl);
            }

            if ($request->boolean('notify_sms_acente') && $talep->phone) {
                $smsMsg = $talep->gtpnr . ' numaralı talebiniz için yeni bir fiyat teklifi hazırlandı. Teklifinizi görüntülemek için sisteme giriş yapınız.';
                (new SmsService())->send($talep->id, 'acente', (string) $talep->agency_name, (string) $talep->phone, $smsMsg);
            }

            if ($request->boolean('notify_sms_admin')) {
                (new SmsService())->sendByEvent('offer_added', $talep->id, $talep->gtpnr . ' teklif eklendi: ' . $request->airline . ' — ' . $request->price_per_pax . ' ' . $currency . '/kişi');
            }

            if ($request->boolean('notify_email_acente') && $talep->user_id) {
                (new EmailService())->teklifEklendi($talep->id, $talep->user_id, $talep->gtpnr, $request->airline, $acenteUrl);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('storeOffer bildirim hatasi: ' . $e->getMessage(), [
                'gtpnr' => $talep->gtpnr,
                'file'  => $e->getFile() . ':' . $e->getLine(),
            ]);
        }

        $successMsg = 'Teklif eklendi.';

        // Ödeme alanları gönderilmişse otomatik kaydet (mükerrer kontrolü ile)
        $pAmount = floatval($request->p_amount);
        if ($pAmount > 0) {
            $odemeVar = $talep->payments()
                ->where('amount', $pAmount)
                ->where('payment_date', $request->p_date ?: null)
                ->where('payment_method', $request->p_method ?: null)
                ->exists();

            if (!$odemeVar) {
                // due_date varsa aktif, yoksa taslak
                $pDueDate  = $request->p_due_date ?: null;
                $pStatus   = $pDueDate ? \App\Models\RequestPayment::STATUS_AKTIF : \App\Models\RequestPayment::STATUS_TASLAK;
                // Bu request altında başka aktif payment yoksa is_active=true
                $isActive  = $pStatus === \App\Models\RequestPayment::STATUS_AKTIF
                    && ! $talep->payments()->where('is_active', true)->exists();

                $talep->payments()->create([
                    'offer_id'       => $yeniTeklif->id,
                    'sequence'       => $request->p_sequence ?? 1,
                    'payment_type'   => $request->p_type ?? 'depozito',
                    'payment_method' => $request->p_method,
                    'bank_name'      => $request->p_bank,
                    'sender_masked'  => $request->p_sender,
                    'account_masked' => $request->p_account,
                    'amount'         => $pAmount,
                    'currency'       => $request->p_currency ?? 'TRY',
                    'payment_date'   => $request->p_date ?: null,
                    'due_date'       => $pDueDate,
                    'status'         => $pStatus,
                    'is_active'      => $isActive,
                    'created_by'     => auth()->user()->name,
                ]);

                $yeniOdeme = $talep->payments()->latest('id')->first();
                if ($yeniOdeme) {
                    app(FinanceSyncService::class)->syncRequestPayment($yeniOdeme, auth()->id());
                }

                RequestLog::create([
                    'request_id'  => $talep->id,
                    'action'      => 'odeme_eklendi',
                    'description' => ($request->p_sequence ?? 1) . '. ödeme kaydedildi: ' . number_format($pAmount, 0) . ' ' . ($request->p_currency ?? 'TRY'),
                    'user_id'     => auth()->id(),
                ]);

                $successMsg = 'Teklif ve ödeme eklendi.';
            } else {
                $successMsg = 'Teklif eklendi. (Ödeme zaten kayıtlıydı, mükerrer engellendi.)';
            }
        }

        return back()->with('success', $successMsg);
    }

    public function aiParse(Request $request, $gtpnr)
    {
        $talep   = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $rawNote = $request->input('raw_note');

        $prompt = 'Aşağıdaki serbest metin operasyon notundan yapılandırılmış veri çıkar. '
            . 'Sadece JSON döndür, başka hiçbir şey yazma. '
            . 'Bulunamayan alanlar için null kullan. '
            . 'Tarih formatı: YYYY-MM-DD. Saat formatı: HH:MM. '
            . 'JSON şeması: {'
            . '"airline": string, "airline_pnr": string, "flight_number": string, '
            . '"flight_date": string, "departure_airport": string, "arrival_airport": string, '
            . '"flight_departure_time": string, "flight_arrival_time": string, '
            . '"pax_confirmed": integer, "price_per_pax": number, "currency": string, "baggage_kg": integer, '
            . '"supplier_reference": string, '
            . '"payment_method": string, "bank_name": string, "sender_masked": string, '
            . '"account_masked": string, "payment_amount": number, "payment_currency": string, '
            . '"payment_date": string, "payment_sequence": integer, '
            . '"ticketing_deadline": string, "balance_deadline": string'
            . '} '
            . 'Metin: ' . $rawNote;

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['error' => 'Gemini API anahtarı tanımlı değil (GEMINI_API_KEY)'], 500);
        }

        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]],
                ]
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'AI bağlantı hatası: ' . $e->getMessage()], 500);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'AI yanıt vermedi: ' . $response->status()], 500);
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        $text = preg_replace('/```json|```/i', '', $text);
        $data = json_decode(trim($text), true);

        if (!$data) {
            return response()->json(['error' => 'JSON parse hatası: ' . $text], 500);
        }

        // AI parse olayını logla — ham metin + AI özeti birlikte saklanır
        $logDesc  = "HAM METİN:\n" . mb_substr($rawNote, 0, 800);
        $logDesc .= "\n\nAI ÖZET: ";
        $logDesc .= implode(' | ', array_filter([
            $data['airline']      ?? null,
            $data['airline_pnr']  ?? null,
            $data['flight_number'] ?? null,
            isset($data['price_per_pax']) ? ($data['price_per_pax'] . ' ' . ($data['currency'] ?? '')) : null,
        ]));

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'ai_parse',
            'description' => $logDesc,
            'user_id'     => auth()->id(),
        ]);

        return response()->json(['data' => $data]);
    }

    public function aiFormatOffer(Request $request, $gtpnr)
    {
        $talep   = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $offerId = $request->input('offer_id');
        $rawNote = $request->input('raw_note');

        if (!$rawNote) {
            return response()->json(['error' => 'Ham not boş.'], 422);
        }

        $prompt = 'Aşağıdaki ham uçuş operasyon verisini seyahat acentasına gönderilecek şekilde sade, anlaşılır ve düzenli bir operasyon mesajına çevir.

Kurallar:
- Metni paragraf yapma, satır satır düzenle.
- Ham veride olmayan bilgi ekleme.
- Yorum yapma.
- Tüm bilgileri net başlıklar altında yaz.

Format şu sırayla olacak:

Havayolu
PNR
Request No
Yolcu sayısı (PAX)

Uçuş bilgisi
- Havayolu
- Uçuş numarası
- Uçuş tarihi
- Gün
- Parkur
- Kalkış saati
- Varış saati

Kişi başı fiyat
Bagaj hakkı

Ödeme durumu
- 1. depozito (tarih ve ödeme şekli)
- 2. depozito (tarih ve opsiyon bilgisi)

Biletleme deadline
- Son ödeme tarihi
- Saat

Sadece formatlanmış metni döndür, başka hiçbir şey yazma. Markdown kullanma.

Ham veri:
' . $rawNote;

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json(['error' => 'Gemini API anahtarı tanımlı değil'], 500);
        }

        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['thinkingConfig' => ['thinkingBudget' => 0]],
                ]
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'AI bağlantı hatası: ' . $e->getMessage()], 500);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'AI yanıt vermedi'], 500);
        }

        $formatted = trim($response->json('candidates.0.content.parts.0.text') ?? '');
        if (!$formatted) {
            return response()->json(['error' => 'AI boş yanıt döndürdü'], 500);
        }

        // Offer'a kaydet
        $offer = Offer::where('id', $offerId)->where('request_id', $talep->id)->firstOrFail();
        $offer->update(['offer_text' => $formatted]);

        return response()->json(['formatted' => $formatted]);
    }

    public function storePayment(Request $request, $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();

        // Mükerrer ödeme kontrolü
        $odemeVar = $talep->payments()
            ->where('amount', $request->amount)
            ->where('payment_date', $request->payment_date ?: null)
            ->where('payment_method', $request->payment_method ?: null)
            ->exists();

        if ($odemeVar) {
            return back()->with('error', 'Bu tutar, tarih ve yöntemde ödeme zaten kayıtlı. Mükerrer kayıt engellendi.');
        }

        // Status çözümleme: admin 'alindi' seçtiyse alindi, aksi halde due_date varsa aktif, yoksa taslak
        $adminStatus = $request->status;
        $dueDate     = $request->due_date ?: null;

        if ($adminStatus === 'alindi') {
            $odemeStatus = \App\Models\RequestPayment::STATUS_ALINDI;
            $isActive    = false;
        } elseif ($adminStatus === 'iade') {
            $odemeStatus = \App\Models\RequestPayment::STATUS_IADE;
            $isActive    = false;
        } elseif ($dueDate) {
            $odemeStatus = \App\Models\RequestPayment::STATUS_AKTIF;
            $isActive    = ! $talep->payments()->where('is_active', true)->exists();
        } else {
            $odemeStatus = \App\Models\RequestPayment::STATUS_TASLAK;
            $isActive    = false;
        }

        $yeniOdeme = $talep->payments()->create([
            'sequence'       => $talep->payments()->count() + 1,
            'payment_type'   => $request->payment_type,
            'payment_method' => $request->payment_method,
            'bank_name'      => $request->bank_name,
            'sender_masked'  => $request->sender_masked,
            'account_masked' => $request->account_masked,
            'amount'         => $request->amount,
            'currency'       => $request->currency ?? 'TRY',
            'payment_date'   => $request->payment_date,
            'due_date'       => $dueDate,
            'status'         => $odemeStatus,
            'is_active'      => $isActive,
            'created_by'     => auth()->user()->name,
        ]);

        app(FinanceSyncService::class)->syncRequestPayment($yeniOdeme, auth()->id());

        $seq        = $request->sequence ?? 1;
        $odemeLabel = $seq == 1 ? '1. Ödeme' : $seq . '. Ödeme';
        $logDesc    = in_array($odemeStatus, [\App\Models\RequestPayment::STATUS_TASLAK, \App\Models\RequestPayment::STATUS_AKTIF])
            ? $odemeLabel . ' planlandı: ' . number_format($request->amount, 0) . ' ' . ($request->currency ?? 'TRY') . ($dueDate ? ' — son tarih: ' . $dueDate : '')
            : $odemeLabel . ' kaydedildi: ' . number_format($request->amount, 0) . ' ' . ($request->currency ?? 'TRY');

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'odeme_eklendi',
            'description' => $logDesc,
            'user_id'     => auth()->id(),
        ]);

        $talep->refreshAktifAdim();

        return back()->with('success', 'Ödeme kaydedildi.');
    }

    public function updateOffer(Request $request, $gtpnr, $offer)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $teklif = Offer::where('request_id', $talep->id)->findOrFail($offer);

        $currency = $request->currency ?: 'TRY';
        $paxCount = $request->pax_confirmed ?: $talep->pax_total;

        $teklif->update([
            'airline'               => $request->airline,
            'airline_pnr'           => $request->airline_pnr,
            'flight_number'         => $request->flight_number,
            'flight_departure_time' => $request->flight_departure_time ?: null,
            'flight_arrival_time'   => $request->flight_arrival_time ?: null,
            'baggage_kg'            => $request->baggage_kg ?: null,
            'pax_confirmed'         => $request->pax_confirmed ?: null,
            'supplier_reference'    => $request->supplier_reference,
            'currency'              => $currency,
            'price_per_pax'         => $request->price_per_pax,
            'total_price'           => $request->price_per_pax * $paxCount,
            'cost_price'            => $request->cost_price ?: null,
            'profit_amount'         => $request->profit_amount ?: null,
            'profit_percent'        => $request->profit_percent ?: null,
            'deposit_rate'          => $request->deposit_rate ?: null,
            'deposit_amount'        => $request->deposit_amount ?: null,
            'kk_enabled'            => $request->boolean('kk_enabled'),
            'option_date'           => $request->option_date ?: null,
            'option_time'           => $request->option_time ?: null,
            'offer_text'            => $request->offer_text,
        ]);

        $talep->refreshAktifAdim();

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_guncellendi',
            'description' => ($teklif->airline ?? '—') . ' PNR:' . ($teklif->airline_pnr ?? '-') . ' teklifi güncellendi',
            'user_id'     => auth()->id(),
        ]);

        return back()->with('success', 'Teklif güncellendi.');
    }

    public function deleteOffer(Request $request, $gtpnr, $offer)
    {
        $talep  = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $teklif = Offer::where('request_id', $talep->id)->findOrFail($offer);

        if ($teklif->durum === \App\Models\Offer::DURUM_KABUL) {
            return back()->with('error', 'Kabul edilmiş teklif silinemez.');
        }

        $desc = ($teklif->airline ?? '—') . ' PNR:' . ($teklif->airline_pnr ?? '-') . ' teklifi silindi';
        $teklif->delete();

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_silindi',
            'description' => $desc,
            'user_id'     => auth()->id(),
        ]);

        $talep->refreshAktifAdim();

        return back()->with('success', 'Teklif silindi.');
    }

    public function toggleOffer(Request $request, $gtpnr, $offer)
    {
        $talep  = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $teklif = Offer::where('request_id', $talep->id)->findOrFail($offer);

        if ($teklif->durum === \App\Models\Offer::DURUM_KABUL) {
            return back()->with('error', 'Kabul edilmiş teklif gizlenemez.');
        }

        $yeniDurum = $teklif->durum === \App\Models\Offer::DURUM_GIZLENDI
            ? \App\Models\Offer::DURUM_BEKLEMEDE
            : \App\Models\Offer::DURUM_GIZLENDI;

        $teklif->update(['durum' => $yeniDurum]);

        $logDurum = $yeniDurum === \App\Models\Offer::DURUM_BEKLEMEDE ? 'acenteye gosterildi' : 'acenteden gizlendi';

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_gorunurluk',
            'description' => ($teklif->airline ?? '—') . ' PNR:' . ($teklif->airline_pnr ?? '-') . ' ' . $logDurum,
            'user_id'     => auth()->id(),
        ]);

        $talep->refreshAktifAdim();

        return back()->with('success', 'Teklif ' . $logDurum . '.');
    }

    public function deletePayment(Request $request, $gtpnr, $payment)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $odeme = RequestPayment::where('request_id', $talep->id)->findOrFail($payment);

        $wasActive = $odeme->is_active;

        app(FinanceSyncService::class)->deleteBySource('request_payment', (int) $odeme->id);
        $odeme->delete();

        // Silinen payment aktif idiyse → sıradakini aktif et
        if ($wasActive) {
            $sonraki = $talep->payments()
                ->whereIn('status', [\App\Models\RequestPayment::STATUS_TASLAK, \App\Models\RequestPayment::STATUS_AKTIF])
                ->orderBy('sequence')
                ->first();
            if ($sonraki) {
                $sonrakiStatus = $sonraki->due_date
                    ? \App\Models\RequestPayment::STATUS_AKTIF
                    : \App\Models\RequestPayment::STATUS_TASLAK;
                $sonraki->update(['is_active' => true, 'status' => $sonrakiStatus]);
            }
        }

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'odeme_silindi',
            'description' => $odeme->sequence . '. ödeme silindi: ' . number_format($odeme->amount, 0) . ' ' . $odeme->currency,
            'user_id'     => auth()->id(),
        ]);

        $talep->refreshAktifAdim();

        return back()->with('success', 'Ödeme silindi.');
    }

    public function updatePayment(Request $request, $gtpnr, $payment)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $odeme = RequestPayment::where('request_id', $talep->id)->findOrFail($payment);

        $eskiStatus = $odeme->status;
        $yeniStatus = $request->status ?? $odeme->status;

        // Status çözümleme
        $dueDate = $request->due_date ?: null;
        if ($yeniStatus === 'alindi' || $yeniStatus === 'iade') {
            $yeniIsActive = false;
        } elseif ($dueDate) {
            $yeniStatus   = \App\Models\RequestPayment::STATUS_AKTIF;
            $yeniIsActive = $odeme->is_active; // mevcut is_active korunur
        } else {
            $yeniStatus   = \App\Models\RequestPayment::STATUS_TASLAK;
            $yeniIsActive = false;
        }

        // Eğer ödeme alindi yapılıyorsa ve is_active=true idiyse → sıradakini aktif et
        if ($yeniStatus === \App\Models\RequestPayment::STATUS_ALINDI && $odeme->is_active) {
            $sonraki = $talep->payments()
                ->where('id', '!=', $odeme->id)
                ->whereIn('status', [\App\Models\RequestPayment::STATUS_TASLAK, \App\Models\RequestPayment::STATUS_AKTIF])
                ->orderBy('sequence')
                ->first();
            if ($sonraki) {
                $sonrakiStatus = $sonraki->due_date
                    ? \App\Models\RequestPayment::STATUS_AKTIF
                    : \App\Models\RequestPayment::STATUS_TASLAK;
                $sonraki->update(['is_active' => true, 'status' => $sonrakiStatus]);
            }
        }

        $odeme->update([
            'sequence'       => $request->sequence ?? $odeme->sequence,
            'payment_type'   => $request->payment_type,
            'payment_method' => $request->payment_method,
            'bank_name'      => $request->bank_name,
            'sender_masked'  => $request->sender_masked,
            'account_masked' => $request->account_masked,
            'amount'         => $request->amount,
            'currency'       => $request->currency ?? 'TRY',
            'payment_date'   => $request->payment_date,
            'due_date'       => $dueDate,
            'due_time'       => $request->due_time ?: null,
            'status'         => $yeniStatus,
            'is_active'      => $yeniIsActive,
        ]);

        app(FinanceSyncService::class)->syncRequestPayment($odeme->fresh(), auth()->id());

        $odemeSeq    = $odeme->sequence;
        $updateLabel = $odemeSeq == 1 ? '1. Ödeme' : $odemeSeq . '. Ödeme';
        $freshAmount = $odeme->fresh()->amount;
        $updateDesc  = (in_array($eskiStatus, [\App\Models\RequestPayment::STATUS_AKTIF, \App\Models\RequestPayment::STATUS_TASLAK])
                        && $yeniStatus === \App\Models\RequestPayment::STATUS_ALINDI)
            ? $updateLabel . ' ödendi: ' . number_format($freshAmount, 0) . ' ' . $odeme->currency
            : $updateLabel . ' güncellendi: ' . number_format($freshAmount, 0) . ' ' . $odeme->currency;

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'odeme_guncellendi',
            'description' => $updateDesc,
            'user_id'     => auth()->id(),
        ]);

        $talep->refreshAktifAdim();

        return back()->with('success', 'Ödeme güncellendi.');
    }

    public function updateRequest(Request $request, $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();

        $request->validate([
            'phone'               => 'nullable|string|max:30',
            'email'               => 'nullable|email|max:100',
            'pax_adult'           => 'nullable|integer|min:0',
            'pax_child'           => 'nullable|integer|min:0',
            'pax_infant'          => 'nullable|integer|min:0',
            'pax_total'           => 'nullable|integer|min:1',
            'group_company_name'  => 'nullable|string|max:200',
            'flight_purpose'      => 'nullable|string|max:200',
            'preferred_airline'   => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:2000',
        ]);

        $adult  = (int) ($request->pax_adult  ?? $talep->pax_adult);
        $child  = (int) ($request->pax_child  ?? $talep->pax_child);
        $infant = (int) ($request->pax_infant ?? $talep->pax_infant);
        $total  = $request->filled('pax_total') ? (int) $request->pax_total : ($adult + $child + $infant ?: $talep->pax_total);

        $talep->update([
            'phone'              => $request->phone,
            'email'              => $request->email,
            'pax_adult'          => $adult,
            'pax_child'          => $child,
            'pax_infant'         => $infant,
            'pax_total'          => $total,
            'group_company_name' => $request->group_company_name,
            'flight_purpose'     => $request->flight_purpose,
            'preferred_airline'  => $request->preferred_airline,
            'notes'              => $request->notes,
        ]);

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'talep_duzenlendi',
            'description' => 'Talep bilgileri güncellendi (superadmin).',
            'user_id'     => auth()->id(),
        ]);

        return back()->with('success', 'Talep bilgileri güncellendi.');
    }

    public function destroy($gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();

        // Cascade: ilişkili kayıtları temizle
        $talep->notifications()->delete();
        $talep->logs()->delete();
        $talep->payments()->delete();
        $talep->offers()->delete();
        $talep->segments()->delete();
        $talep->delete();

        return redirect()
            ->route('admin.requests.index')
            ->with('success', $gtpnr . ' numaralı talep silindi.');
    }
}

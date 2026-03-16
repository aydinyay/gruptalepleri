<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Request as TalepModel;
use App\Models\Offer;
use App\Models\Request as RequestModel;
use App\Models\RequestLog;
use App\Models\RequestPayment;
use App\Services\EmailService;
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
        $query = TalepModel::with(['user', 'segments', 'offers'])
            ->orderBy('id', 'desc');

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

        $talepler = $query->paginate(20)->withQueryString();

        $durumSayilari = TalepModel::selectRaw('status, count(*) as toplam')
            ->groupBy('status')
            ->pluck('toplam', 'status');

        $aktifSayisi = TalepModel::whereNotIn('status', $this->pasifStatusler)->count();

        return view('admin.requests.index', compact('talepler', 'durumSayilari', 'aktifSayisi'));
    }

    public function show($gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->with(['user', 'segments', 'offers', 'logs.user', 'payments', 'notifications'])
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

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'durum_degisti',
            'description' => 'Durum degisti: ' . $eskiDurum . ' -> ' . $yeniDurum,
            'user_id'     => auth()->id(),
        ]);

        if ($talep->user_id) {
            $acenteUrl = route('acente.requests.show', $talep->gtpnr);
            (new EmailService())->durumDegisti($talep->id, $talep->user_id, $talep->gtpnr, $eskiDurum, $yeniDurum, $acenteUrl);
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

        $talep->offers()->create([
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
            'deposit_rate'          => $request->deposit_rate ?: null,
            'deposit_amount'        => $request->deposit_amount ?: null,
            'option_date'           => $request->option_date ?: null,
            'option_time'           => $request->option_time ?: null,
            'offer_text'            => $request->offer_text,
            'admin_raw_note'        => $request->admin_raw_note,
            'ai_raw_output'         => $request->ai_raw_output ? json_decode($request->ai_raw_output, true) : null,
            'created_by'            => auth()->user()->name,
        ]);

        $talep->update(['status' => RequestModel::STATUS_FIYATLANDIRILDI]);

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_eklendi',
            'description' => $request->airline . ' için teklif eklendi — ' . $request->price_per_pax . ' ' . $request->currency . '/kişi',
            'user_id'     => auth()->id(),
        ]);

        $acenteUrl = route('acente.requests.show', $talep->gtpnr);

        // Acenteye push bildirimi
        if ($talep->user_id) {
            (new NotificationService())->teklifEklendi($talep->user_id, $talep->gtpnr, $request->airline, $acenteUrl);
        }

        // Acenteye SMS
        $smsMsg = $talep->gtpnr . ' numaralı talebiniz için yeni bir fiyat teklifi hazırlandı. Teklifinizi görüntülemek için sisteme giriş yapınız.';
        (new SmsService())->send($talep->id, 'acente', $talep->agency_name, $talep->phone, $smsMsg);

        // Admin/superadmin'e de offer_added event bildirimi (SMS ayarlarında kurallıysa)
        (new SmsService())->sendByEvent('offer_added', $talep->id, $talep->gtpnr . ' teklif eklendi: ' . $request->airline . ' — ' . $request->price_per_pax . ' ' . $currency . '/kişi');

        // Acenteye email
        if ($talep->user_id) {
            (new EmailService())->teklifEklendi($talep->id, $talep->user_id, $talep->gtpnr, $request->airline, $acenteUrl);
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
                $talep->payments()->create([
                    'sequence'       => $request->p_sequence ?? 1,
                    'payment_type'   => $request->p_type ?? 'depozito',
                    'payment_method' => $request->p_method,
                    'bank_name'      => $request->p_bank,
                    'sender_masked'  => $request->p_sender,
                    'account_masked' => $request->p_account,
                    'amount'         => $pAmount,
                    'currency'       => $request->p_currency ?? 'TRY',
                    'payment_date'   => $request->p_date ?: null,
                    'status'         => 'alindi',
                    'created_by'     => auth()->user()->name,
                ]);

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

        $talep->payments()->create([
            'sequence'       => $request->sequence ?? 1,
            'payment_type'   => $request->payment_type,
            'payment_method' => $request->payment_method,
            'bank_name'      => $request->bank_name,
            'sender_masked'  => $request->sender_masked,
            'account_masked' => $request->account_masked,
            'amount'         => $request->amount,
            'currency'       => $request->currency ?? 'TRY',
            'payment_date'   => $request->payment_date,
            'status'         => $request->status ?? 'alindi',
            'created_by'     => auth()->user()->name,
        ]);

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'odeme_eklendi',
            'description' => ($request->sequence ?? 1) . '. ödeme kaydedildi: ' . number_format($request->amount, 0) . ' ' . ($request->currency ?? 'TRY'),
            'user_id'     => auth()->id(),
        ]);

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
            'deposit_rate'          => $request->deposit_rate ?: null,
            'deposit_amount'        => $request->deposit_amount ?: null,
            'option_date'           => $request->option_date ?: null,
            'option_time'           => $request->option_time ?: null,
            'offer_text'            => $request->offer_text,
        ]);

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
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $teklif = Offer::where('request_id', $talep->id)->findOrFail($offer);
        $desc = ($teklif->airline ?? '—') . ' PNR:' . ($teklif->airline_pnr ?? '-') . ' teklifi silindi';
        $teklif->delete();

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_silindi',
            'description' => $desc,
            'user_id'     => auth()->id(),
        ]);

        return back()->with('success', 'Teklif silindi.');
    }

    public function toggleOffer(Request $request, $gtpnr, $offer)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $teklif = Offer::where('request_id', $talep->id)->findOrFail($offer);
        $teklif->update(['is_visible' => !$teklif->is_visible]);

        $durum = $teklif->is_visible ? 'acenteye gosterildi' : 'acenteden gizlendi';

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_gorunurluk',
            'description' => ($teklif->airline ?? '—') . ' PNR:' . ($teklif->airline_pnr ?? '-') . ' ' . $durum,
            'user_id'     => auth()->id(),
        ]);

        return back()->with('success', 'Teklif ' . $durum . '.');
    }

    public function deletePayment(Request $request, $gtpnr, $payment)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $odeme = RequestPayment::where('request_id', $talep->id)->findOrFail($payment);
        $odeme->delete();

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'odeme_silindi',
            'description' => $odeme->sequence . '. ödeme silindi: ' . number_format($odeme->amount, 0) . ' ' . $odeme->currency,
            'user_id'     => auth()->id(),
        ]);

        return back()->with('success', 'Ödeme silindi.');
    }

    public function updatePayment(Request $request, $gtpnr, $payment)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)->firstOrFail();
        $odeme = RequestPayment::where('request_id', $talep->id)->findOrFail($payment);

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
            'status'         => $request->status ?? $odeme->status,
        ]);

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'odeme_guncellendi',
            'description' => $odeme->sequence . '. ödeme güncellendi: ' . number_format($odeme->amount, 0) . ' ' . $odeme->currency,
            'user_id'     => auth()->id(),
        ]);

        return back()->with('success', 'Ödeme güncellendi.');
    }
}
<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\Request as TalepModel;
use App\Models\RequestLog;
use App\Services\GtpnrService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RequestController extends Controller
{
    public function create()
    {
        return view('acente.request.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agency_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'group_company_name' => 'nullable|string',
            'flight_purpose' => 'nullable|string',
            'trip_type' => 'required|string',
            'pax_total' => 'required|integer|min:1',
            'pax_adult' => 'nullable|integer',
            'pax_child' => 'nullable|integer',
            'pax_infant' => 'nullable|integer',
            'preferred_airline' => 'nullable|string',
            'notes' => 'nullable|string',
            'segments' => 'required|array|min:1',
            'segments.*.from_iata' => 'required|string',
            'segments.*.to_iata' => 'required|string',
            'segments.*.departure_date' => 'required|date',
        ]);

        $gtpnr = (new GtpnrService())->generate('group_flight');

        $talep = TalepModel::create([
            'gtpnr' => $gtpnr,
            'user_id' => auth()->id(),
            'type' => 'group_flight',
            'status' => 'beklemede',
            'agency_name' => $validated['agency_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'group_company_name' => $validated['group_company_name'] ?? null,
            'flight_purpose' => $validated['flight_purpose'] ?? null,
            'trip_type' => $validated['trip_type'],
            'pax_total' => $validated['pax_total'],
            'pax_adult' => $validated['pax_adult'] ?? 0,
            'pax_child' => $validated['pax_child'] ?? 0,
            'pax_infant' => $validated['pax_infant'] ?? 0,
            'preferred_airline' => $validated['preferred_airline'] ?? null,
            'hotel_needed' => $request->boolean('hotel_needed'),
            'visa_needed' => $request->boolean('visa_needed'),
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['segments'] as $index => $segment) {
            $talep->segments()->create([
                'order' => $index,
                'from_iata' => strtoupper($segment['from_iata']),
                'to_iata' => strtoupper($segment['to_iata']),
                'departure_date' => $segment['departure_date'],
                'departure_time' => $segment['departure_time'] ?? null,
            ]);
        }

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'talep_olusturuldu',
            'description' => 'Talep oluşturuldu.',
            'user_id'     => auth()->id(),
        ]);

        $url = route('admin.requests.show', $talep->gtpnr);

        // Push bildirimi
        (new NotificationService())->yeniTalep(auth()->id(), $talep->gtpnr, $talep->agency_name, $talep->pax_total, $url);

        // SMS
        $smsMsg = 'Yeni grup talebi: ' . $talep->gtpnr . ' | ' . $talep->agency_name . ' | ' . $talep->pax_total . ' PAX | ' . $talep->phone;
        (new SmsService())->sendByEvent('new_request', $talep->id, $smsMsg);

        return redirect()->route('acente.requests.show', $talep->gtpnr);
    }

    public function aiAnaliz(Request $request, $gtpnr)
    {
        TalepModel::where('gtpnr', $gtpnr)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $prompt = $request->input('prompt');

        $apiKey   = config('services.gemini.key');
        $response = Http::timeout(55)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]
        );

        if ($response->failed()) {
            return response()->json(['error' => 'AI servisi yanıt vermedi: ' . $response->body()], 500);
        }

        $html = $response->json('candidates.0.content.parts.0.text');
        if (!$html) {
            return response()->json(['error' => 'Yanıt boş geldi: ' . $response->body()], 500);
        }
        return response()->json(['html' => $html]);
    }

    public function aiKaydet(Request $request, $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $talep->update([
            'ai_analysis'            => $request->input('html'),
            'ai_analysis_hash'       => $request->input('hash'),
            'ai_analysis_updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function acceptOffer(Request $request, $gtpnr, $offer)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->where('user_id', auth()->id())
            ->with('offers')
            ->firstOrFail();

        $teklif = $talep->offers()->where('is_visible', true)->findOrFail($offer);

        // Diğer tekliflerin kabulünü kaldır
        $talep->offers()->update(['is_accepted' => false, 'accepted_at' => null]);

        // Bu teklifi kabul et
        $teklif->update(['is_accepted' => true, 'accepted_at' => now()]);

        // Talep durumunu güncelle
        $talep->update(['status' => 'depozitoda']);

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_kabul_edildi',
            'description' => ($teklif->airline ?? '—') . ' — ' . number_format($teklif->price_per_pax, 0) . ' ' . $teklif->currency . '/kişi teklifi acente tarafından kabul edildi.',
            'user_id'     => auth()->id(),
        ]);

        $url = route('admin.requests.show', $talep->gtpnr);

        // Push bildirimi
        (new NotificationService())->teklifKabulEdildi($talep->gtpnr, $talep->agency_name, $teklif->airline ?? '—', $url);

        // SMS
        $smsMsg = $talep->gtpnr . ' teklif kabul edildi: ' . ($teklif->airline ?? '—') . ' — ' . number_format($teklif->price_per_pax, 0) . ' ' . $teklif->currency . '/kişi | Acente: ' . $talep->agency_name;
        (new SmsService())->sendByEvent('offer_accepted', $talep->id, $smsMsg);

        // WhatsApp'a yönlendir
        $mesaj = $talep->gtpnr . ' numaralı talebim için ' . ($teklif->airline ?? '') . ' teklifini kabul ediyorum. Depozito ödemesi için bilgi alabilir miyim?';
        return redirect()->away('https://wa.me/905324262630?text=' . urlencode($mesaj));
    }

    public function show($gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->where('user_id', auth()->id())
            ->with(['segments', 'offers', 'logs.user', 'payments', 'notifications'])
            ->firstOrFail();

        return view('acente.request.show', compact('talep'));
    }
}
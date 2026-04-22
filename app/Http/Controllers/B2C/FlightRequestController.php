<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\Offer;
use App\Models\Request as TalepModel;
use App\Models\RequestLog;
use App\Services\EmailService;
use App\Services\GtpnrService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class FlightRequestController extends Controller
{
    // ── Talep Formu ─────────────────────────────────────────────────────────

    public function create()
    {
        return view('b2c.flight-request.create');
    }

    public function store(Request $request, GtpnrService $gtpnrService)
    {
        $validated = $request->validate([
            'contact_name'       => 'required|string|max:120',
            'phone'              => 'required|string|max:30',
            'email'              => 'required|email|max:150',
            'flight_purpose'     => 'nullable|string|max:60',
            'trip_type'          => 'required|string|in:one_way,round_trip,multi',
            'pax_total'          => 'required|integer|min:1|max:5000',
            'pax_adult'          => 'nullable|integer|min:0',
            'pax_child'          => 'nullable|integer|min:0',
            'pax_infant'         => 'nullable|integer|min:0',
            'preferred_airline'  => 'nullable|string|max:100',
            'hotel_needed'       => 'nullable|boolean',
            'notes'              => 'nullable|string|max:2000',
            'segments'           => 'required|array|min:1',
            'segments.*.from_iata'           => 'required|string|size:3',
            'segments.*.to_iata'             => 'required|string|size:3',
            'segments.*.departure_date'      => 'required|date|after_or_equal:today',
            'segments.*.departure_time_slot' => 'required|in:sabah,ogle,aksam,esnek',
        ]);

        $gtpnr = $gtpnrService->generate('group_flight');

        $talep = TalepModel::create([
            'gtpnr'          => $gtpnr,
            'user_id'        => null,
            'source_channel' => 'b2c',
            'type'           => 'group_flight',
            'status'         => 'beklemede',
            'agency_name'    => mb_strtoupper($validated['contact_name'], 'UTF-8'),
            'phone'          => $validated['phone'],
            'email'          => $validated['email'],
            'flight_purpose' => $validated['flight_purpose'] ?? null,
            'trip_type'      => $validated['trip_type'],
            'pax_total'      => $validated['pax_total'],
            'pax_adult'      => $validated['pax_adult'] ?? 0,
            'pax_child'      => $validated['pax_child'] ?? 0,
            'pax_infant'     => $validated['pax_infant'] ?? 0,
            'preferred_airline' => $validated['preferred_airline'] ?? null,
            'hotel_needed'   => $request->boolean('hotel_needed'),
            'notes'          => $validated['notes'] ?? null,
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
                'order'               => $index,
                'from_iata'           => $fromIata,
                'from_city'           => $fromAp ? ($fromAp->city ?: $fromAp->name) : null,
                'to_iata'             => $toIata,
                'to_city'             => $toAp ? ($toAp->city ?: $toAp->name) : null,
                'departure_date'      => $segment['departure_date'],
                'departure_time_slot' => $segment['departure_time_slot'],
            ]);
        }

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'talep_olusturuldu',
            'description' => 'B2C talebi oluşturuldu (GrupRezervasyonlari.com).',
            'user_id'     => null,
        ]);

        $adminUrl = route('admin.requests.show', $talep->gtpnr);
        $token    = $this->makeToken($gtpnr, $validated['phone']);
        $trackUrl = route('b2c.flight.track', ['gtpnr' => $gtpnr, 'token' => $token]);

        // Admin bildirimleri
        (new NotificationService())->yeniTalep(null, $talep->gtpnr, $talep->agency_name . ' [B2C]', $talep->pax_total, $adminUrl);
        $smsMsg = 'YENİ B2C TALEBİ: ' . $talep->gtpnr . ' | ' . $talep->agency_name . ' | ' . $talep->pax_total . ' PAX | ' . $talep->phone;
        (new SmsService())->sendByEvent('new_request', $talep->id, $smsMsg);
        (new EmailService())->yeniTalep($talep->id, $talep->gtpnr, $talep->agency_name . ' [B2C]', $talep->pax_total, $adminUrl);

        // Tüketiciye onay + takip linki emaili
        (new EmailService())->b2cTalepOnay($talep->id, $gtpnr, $validated['contact_name'], $validated['email'], $trackUrl);

        // Tüketiciye SMS
        $consumerSms = "Grup ucus talebiniz alindi. Referans: {$gtpnr}. Takip: {$trackUrl}";
        (new SmsService())->send($talep->id, 'b2c_musteri', $validated['contact_name'], $validated['phone'], $consumerSms);

        return redirect()->route('b2c.flight.confirm', $talep->gtpnr);
    }

    // ── Teşekkür sayfası (talep oluştuktan hemen sonra) ─────────────────────

    public function confirm(string $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->where('source_channel', 'b2c')
            ->with(['segments' => fn($q) => $q->orderBy('order')])
            ->firstOrFail();

        $token    = $this->makeToken($talep->gtpnr, $talep->phone);
        $trackUrl = route('b2c.flight.track', ['gtpnr' => $talep->gtpnr, 'token' => $token]);

        return view('b2c.flight-request.confirm', compact('talep', 'trackUrl'));
    }

    // ── Doğrulama sayfası ────────────────────────────────────────────────────

    public function showVerify(string $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->where('source_channel', 'b2c')
            ->firstOrFail();

        return view('b2c.flight-request.verify', compact('talep'));
    }

    public function verify(Request $request, string $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->where('source_channel', 'b2c')
            ->firstOrFail();

        $input = trim($request->input('credential', ''));

        $phoneNorm = preg_replace('/\D/', '', $talep->phone);
        $inputNorm = preg_replace('/\D/', '', $input);

        $phoneMatch = strlen($inputNorm) >= 4 && str_ends_with($phoneNorm, substr($inputNorm, -10));
        $emailMatch = strtolower($input) === strtolower($talep->email);

        if (! $phoneMatch && ! $emailMatch) {
            return back()->withErrors(['credential' => 'Girilen bilgi bu talep ile eşleşmiyor. Lütfen talep sırasında kullandığınız telefon veya e-posta adresini girin.']);
        }

        session(["b2c_talep_{$gtpnr}" => true]);

        return redirect()->route('b2c.flight.track', $gtpnr);
    }

    // ── Talep Takip Sayfası ─────────────────────────────────────────────────

    public function track(Request $request, string $gtpnr)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->where('source_channel', 'b2c')
            ->with([
                'segments'  => fn($q) => $q->orderBy('order'),
                'offers'    => fn($q) => $q->whereIn('durum', [Offer::DURUM_BEKLEMEDE, Offer::DURUM_KABUL])->orderBy('id'),
                'logs'      => fn($q) => $q->orderByDesc('id')->limit(10),
            ])
            ->firstOrFail();

        // Token ile otomatik doğrulama
        $token = $request->query('token');
        if ($token && $token === $this->makeToken($gtpnr, $talep->phone)) {
            session(["b2c_talep_{$gtpnr}" => true]);
        }

        // Session doğrulama
        if (! session("b2c_talep_{$gtpnr}")) {
            return redirect()->route('b2c.flight.verify', $gtpnr);
        }

        return view('b2c.flight-request.track', compact('talep'));
    }

    // ── Teklif Kabul ─────────────────────────────────────────────────────────

    public function acceptOffer(Request $request, string $gtpnr, int $offerId)
    {
        $talep = TalepModel::where('gtpnr', $gtpnr)
            ->where('source_channel', 'b2c')
            ->firstOrFail();

        if (! session("b2c_talep_{$gtpnr}")) {
            return redirect()->route('b2c.flight.verify', $gtpnr);
        }

        $offer = Offer::where('id', $offerId)
            ->where('request_id', $talep->id)
            ->where('durum', Offer::DURUM_BEKLEMEDE)
            ->firstOrFail();

        $offer->update(['durum' => Offer::DURUM_KABUL]);

        $talep->update(['status' => TalepModel::STATUS_DEPOZITODA]);
        $talep->refreshAktifAdim();

        RequestLog::create([
            'request_id'  => $talep->id,
            'action'      => 'teklif_kabul_edildi',
            'description' => 'Teklif B2C müşterisi tarafından kabul edildi. Teklif: ' . ($offer->airline ?? '—') . ' ' . $offer->total_price . ' ' . $offer->currency,
            'user_id'     => null,
        ]);

        // Admin'e bildir
        $adminUrl = route('admin.requests.show', $gtpnr);
        (new NotificationService())->teklifKabulEdildi($gtpnr, $talep->agency_name . ' [B2C]', $offer->airline ?? '—', $adminUrl);
        (new EmailService())->teklifKabul($talep->id, $gtpnr, $talep->agency_name . ' [B2C]', $offer->airline ?? '—', $adminUrl);

        return redirect()->route('b2c.flight.track', $gtpnr)
            ->with('offer_accepted', true);
    }

    // ── Yardımcı: Doğrulama Token'ı ─────────────────────────────────────────

    private function makeToken(string $gtpnr, string $phone): string
    {
        $normalPhone = preg_replace('/\D/', '', $phone);
        return substr(hash('sha256', $gtpnr . $normalPhone . config('app.key')), 0, 24);
    }
}

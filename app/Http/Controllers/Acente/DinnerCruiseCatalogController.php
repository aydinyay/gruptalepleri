<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\DinnerCruiseRequestDetail;
use App\Models\LeisureBooking;
use App\Models\LeisureClientOffer;
use App\Models\LeisureExtraOption;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;
use App\Models\LeisureRequest;
use App\Models\LeisureRequestExtra;
use App\Services\EmailService;
use App\Services\Finance\FinanceSyncService;
use App\Services\GtpnrService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DinnerCruiseCatalogController extends Controller
{
    // ── Listing sayfası ────────────────────────────────────────────────────
    public function catalog()
    {
        $packages = LeisurePackageTemplate::query()
            ->where('product_type', 'dinner_cruise')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $mediaAssets = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', 'dinner_cruise'))
            ->orderBy('sort_order')
            ->limit(9)
            ->get();

        return view('acente.dinner-cruise.catalog', compact('packages', 'mediaAssets'));
    }

    // ── Ürün detay sayfası ─────────────────────────────────────────────────
    public function show(string $code)
    {
        $package = LeisurePackageTemplate::query()
            ->where('product_type', 'dinner_cruise')
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $allPackages = LeisurePackageTemplate::query()
            ->where('product_type', 'dinner_cruise')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $mediaAssets = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', 'dinner_cruise'))
            ->whereNull('package_code')
            ->orderBy('sort_order')
            ->limit(9)
            ->get();

        $galleryPhotos = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where('package_code', $package->code)
            ->where('category', 'gallery')
            ->orderBy('sort_order')
            ->get();

        $extraOptions = LeisureExtraOption::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', 'dinner_cruise'))
            ->orderBy('sort_order')
            ->get();

        return view('acente.dinner-cruise.show', compact('package', 'allPackages', 'mediaAssets', 'galleryPhotos', 'extraOptions'));
    }

    // ── Direkt Rezervasyon + Ödeme ─────────────────────────────────────────
    public function book(Request $request, string $code, GtpnrService $gtpnrService, FinanceSyncService $financeSyncService): RedirectResponse
    {
        $package = LeisurePackageTemplate::query()
            ->where('product_type', 'dinner_cruise')
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'service_date'       => 'required|date|after_or_equal:today',
            'departure_time'     => 'required|string|max:80',
            'pax_adult'          => 'required|integer|min:1|max:500',
            'pax_child'          => 'nullable|integer|min:0|max:500',
            'pax_infant'         => 'nullable|integer|min:0|max:500',
            'transfer_required'  => 'nullable|boolean',
            'hotel_name'         => 'nullable|string|max:255',
            'transfer_region'    => 'nullable|string|max:80',
            'guest_name'         => 'required|string|max:120',
            'guest_phone'        => 'required|string|max:30',
            'nationality'        => 'nullable|string|max:80',
            'celebration_type'   => 'nullable|string|max:120',
            'notes'              => 'nullable|string|max:2000',
            'extra_option_codes' => 'nullable|array',
            'extra_option_codes.*' => 'nullable|string|max:50',
        ]);

        $paxAdult  = (int) $validated['pax_adult'];
        $paxChild  = (int) ($validated['pax_child'] ?? 0);
        $paxInfant = (int) ($validated['pax_infant'] ?? 0);
        $guestCount = $paxAdult + $paxChild;

        // B2B fiyat hesapla (çocuk %50 indirimli, bebek ücretsiz)
        $pricePerPerson = (float) ($package->base_price_per_person ?? 0);
        $totalAmount = round(
            ($paxAdult * $pricePerPerson) + ($paxChild * $pricePerPerson * 0.5),
            2
        );
        $currency = $package->currency ?: 'TRY';

        $leisureRequest = DB::transaction(function () use (
            $validated, $package, $gtpnrService, $financeSyncService,
            $paxAdult, $paxChild, $paxInfant, $guestCount, $totalAmount, $currency
        ) {
            // 1) LeisureRequest — direkt onaylı statüde oluştur
            $req = LeisureRequest::query()->create([
                'user_id'             => auth()->id(),
                'gtpnr'               => $gtpnrService->generate('dinner_cruise'),
                'product_type'        => 'dinner_cruise',
                'status'              => LeisureRequest::STATUS_APPROVED,
                'service_date'        => $validated['service_date'],
                'guest_count'         => $guestCount,
                'transfer_required'   => (bool) ($validated['transfer_required'] ?? false),
                'hotel_name'          => $validated['hotel_name'] ?? null,
                'transfer_region'     => $validated['transfer_region'] ?? null,
                'guest_name'          => $validated['guest_name'],
                'guest_phone'         => $validated['guest_phone'],
                'package_level'       => $package->level,
                'language_preference' => 'tr',
                'nationality'         => $validated['nationality'] ?? null,
                'notes'               => $validated['notes'] ?? null,
                'approved_at'         => now(),
            ]);

            // 2) DinnerCruiseRequestDetail
            DinnerCruiseRequestDetail::query()->create([
                'leisure_request_id' => $req->id,
                'session_time'       => $validated['departure_time'],
                'pier_name'          => $package->pier_name,
                'adult_count'        => $paxAdult,
                'child_count'        => $paxChild,
                'infant_count'       => $paxInfant,
                'celebration_type'   => $validated['celebration_type'] ?? null,
                'shared_cruise'      => true,
                'detail_json'        => ['booking_type' => 'direct', 'package_code' => $package->code],
            ]);

            // 3) LeisureClientOffer — şablondan otomatik üret
            $offer = LeisureClientOffer::query()->create([
                'leisure_request_id'  => $req->id,
                'package_template_id' => $package->id,
                'package_label'       => $package->name_tr,
                'total_price'         => $totalAmount,
                'per_person_price'    => $package->base_price_per_person,
                'currency'            => $currency,
                'includes_snapshot'   => $package->includes_tr,
                'excludes_snapshot'   => $package->excludes_tr,
                'status'              => 'accepted',
                'shared_at'           => now(),
                'accepted_at'         => now(),
            ]);

            // 4) Seçilen extra'lar
            $selectedCodes = collect($validated['extra_option_codes'] ?? [])->filter()->values();
            if ($selectedCodes->isNotEmpty()) {
                $options = LeisureExtraOption::query()->whereIn('code', $selectedCodes)->get()->keyBy('code');
                foreach ($selectedCodes as $eCode) {
                    $opt = $options->get($eCode);
                    if (! $opt) {
                        continue;
                    }
                    LeisureRequestExtra::query()->create([
                        'leisure_request_id' => $req->id,
                        'extra_option_id'    => $opt->id,
                        'title'              => $opt->title_tr,
                        'agency_note'        => $opt->description_tr,
                        'status'             => 'requested',
                    ]);
                }
            }

            // 5) LeisureBooking — doğrudan oluştur
            $booking = LeisureBooking::query()->create([
                'leisure_request_id' => $req->id,
                'client_offer_id'    => $offer->id,
                'status'             => 'pending_payment',
                'total_amount'       => $totalAmount,
                'total_paid'         => 0,
                'remaining_amount'   => $totalAmount,
                'currency'           => $currency,
                'operation_note'     => 'Direkt rezervasyon — web panel',
            ]);

            // 6) Finans kaydı
            $financeSyncService->syncLeisureBooking($booking->fresh(['request', 'clientOffer']), auth()->id());

            return $req;
        });

        // Bildirimler (push + email + SMS) — hata olursa rezervasyonu bloklamaz
        try {
            $agencyName = auth()->user()->name;
            $booking    = $leisureRequest->booking ?? $leisureRequest->load('booking')->booking;
            $amount     = (float) ($booking?->total_amount ?? 0);
            $cur        = $booking?->currency ?? 'EUR';
            $adminUrl   = route('superadmin.dinner-cruise.show', $leisureRequest);

            (new NotificationService())->yeniLeisureBooking($leisureRequest->gtpnr, $agencyName, 'dinner_cruise', $amount, $cur, $adminUrl);
            (new EmailService())->yeniLeisureBooking($leisureRequest->gtpnr, $agencyName, 'dinner_cruise', $amount, $cur, $adminUrl);

            $amtFmt = number_format($amount, 0, ',', '.') . ' ' . $cur;
            (new SmsService())->sendByEvent('new_request', null,
                "🚢 Yeni Dinner Cruise rezervasyonu: {$leisureRequest->gtpnr} / {$agencyName} / {$amtFmt}",
                ['gtpnr' => $leisureRequest->gtpnr, 'acente_adi' => $agencyName]
            );
        } catch (\Throwable $e) {
            Log::warning('Dinner Cruise booking notification error: ' . $e->getMessage());
        }

        // Ödeme sayfasına yönlendir
        return redirect()
            ->route('acente.dinner-cruise.booking-show', $leisureRequest)
            ->with('success', 'Rezervasyonunuz oluşturuldu! Ödemenizi tamamlayın.');
    }

    // ── Rezervasyon detay + ödeme tetikleme ────────────────────────────────
    public function bookingShow(LeisureRequest $leisureRequest)
    {
        abort_unless(
            $leisureRequest->user_id === auth()->id() && $leisureRequest->product_type === 'dinner_cruise',
            403
        );

        $leisureRequest->load([
            'dinnerCruiseDetail',
            'clientOffers.packageTemplate',
            'booking.payments',
        ]);

        return view('acente.dinner-cruise.booking', compact('leisureRequest'));
    }
}

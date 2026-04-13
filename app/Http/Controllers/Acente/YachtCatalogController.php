<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\LeisureBooking;
use App\Models\LeisureClientOffer;
use App\Models\LeisureExtraOption;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;
use App\Models\LeisureRequest;
use App\Models\LeisureRequestExtra;
use App\Models\YachtCharterRequestDetail;
use App\Services\Finance\FinanceSyncService;
use App\Services\GtpnrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class YachtCatalogController extends Controller
{
    // ── Katalog sayfası ────────────────────────────────────────────────────
    public function catalog()
    {
        $packages = LeisurePackageTemplate::query()
            ->where('product_type', 'yacht')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $mediaAssets = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', 'yacht'))
            ->orderBy('sort_order')
            ->limit(9)
            ->get();

        return view('acente.yacht-charter.catalog', compact('packages', 'mediaAssets'));
    }

    // ── Ürün detay sayfası ─────────────────────────────────────────────────
    public function show(string $code)
    {
        $package = LeisurePackageTemplate::query()
            ->where('product_type', 'yacht')
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $allPackages = LeisurePackageTemplate::query()
            ->where('product_type', 'yacht')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $mediaAssets = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', 'yacht'))
            ->whereNull('package_code')
            ->orderBy('sort_order')
            ->limit(9)
            ->get();

        $galleryPhotos = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where('package_code', $package->code)
            ->where('category', 'gallery')
            ->where('media_type', 'photo')
            ->orderBy('sort_order')
            ->get();

        $extraOptions = LeisureExtraOption::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', 'yacht'))
            ->orderBy('sort_order')
            ->get();

        return view('acente.yacht-charter.show', compact('package', 'allPackages', 'mediaAssets', 'galleryPhotos', 'extraOptions'));
    }

    // ── Direkt Rezervasyon ─────────────────────────────────────────────────
    public function book(Request $request, string $code, GtpnrService $gtpnrService, FinanceSyncService $financeSyncService): RedirectResponse
    {
        $package = LeisurePackageTemplate::query()
            ->where('product_type', 'yacht')
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'service_date'        => 'required|date|after_or_equal:today',
            'guest_count'         => 'required|integer|min:1|max:500',
            'duration_hours'      => 'required|integer|min:1|max:24',
            'start_time'          => 'nullable|string|max:10',
            'marina_name'         => 'nullable|string|max:120',
            'route_plan'          => 'nullable|string|max:255',
            'event_type'          => 'nullable|string|max:120',
            'vessel_style'        => 'nullable|string|max:120',
            'transfer_required'   => 'nullable|boolean',
            'hotel_name'          => 'nullable|string|max:255',
            'transfer_region'     => 'nullable|string|max:80',
            'guest_name'          => 'required|string|max:120',
            'guest_phone'         => 'required|string|max:30',
            'nationality'         => 'nullable|string|max:80',
            'notes'               => 'nullable|string|max:2000',
            'extra_option_codes'  => 'nullable|array',
            'extra_option_codes.*' => 'nullable|string|max:50',
        ]);

        $durationHours = (int) $validated['duration_hours'];
        $guestCount    = (int) $validated['guest_count'];

        // B2B fiyat: saatlik ücret × süre
        $pricePerHour = (float) ($package->base_price_per_person ?? 0);
        $totalAmount  = round($pricePerHour * $durationHours, 2);
        $currency     = $package->currency ?: 'EUR';

        $leisureRequest = DB::transaction(function () use (
            $validated, $package, $gtpnrService, $financeSyncService,
            $guestCount, $durationHours, $totalAmount, $currency
        ) {
            // 1) LeisureRequest — direkt onaylı statüde oluştur
            $req = LeisureRequest::query()->create([
                'user_id'             => auth()->id(),
                'gtpnr'               => $gtpnrService->generate('yacht'),
                'product_type'        => LeisureRequest::PRODUCT_YACHT,
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

            // 2) YachtCharterRequestDetail
            YachtCharterRequestDetail::query()->create([
                'leisure_request_id' => $req->id,
                'start_time'         => $validated['start_time'] ?? null,
                'duration_hours'     => $durationHours,
                'marina_name'        => $validated['marina_name'] ?? null,
                'route_plan'         => $validated['route_plan'] ?? null,
                'event_type'         => $validated['event_type'] ?? null,
                'vessel_style'       => $validated['vessel_style'] ?? null,
                'detail_json'        => ['booking_type' => 'direct', 'package_code' => $package->code],
            ]);

            // 3) LeisureClientOffer — otomatik üret
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

        return redirect()
            ->route('acente.yacht-charter.booking-show', $leisureRequest)
            ->with('success', 'Rezervasyonunuz oluşturuldu! Ödemenizi tamamlayın.');
    }

    // ── Rezervasyon detay + ödeme ──────────────────────────────────────────
    public function bookingShow(LeisureRequest $leisureRequest)
    {
        abort_unless(
            $leisureRequest->user_id === auth()->id() && $leisureRequest->product_type === LeisureRequest::PRODUCT_YACHT,
            403
        );

        $leisureRequest->load([
            'yachtDetail',
            'clientOffers.packageTemplate',
            'booking.payments',
        ]);

        return view('acente.yacht-charter.booking', compact('leisureRequest'));
    }
}

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
use App\Services\EmailService;
use App\Services\Finance\FinanceSyncService;
use App\Services\GtpnrService;
use App\Services\NotificationService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TourCatalogController extends Controller
{
    public function catalog()
    {
        $packages = LeisurePackageTemplate::query()
            ->where('product_type', 'tour')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $mediaAssets = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', 'tour'))
            ->orderBy('sort_order')
            ->limit(9)
            ->get();

        return view('acente.tour.catalog', compact('packages', 'mediaAssets'));
    }

    public function show(string $code)
    {
        $package = LeisurePackageTemplate::query()
            ->where('product_type', 'tour')
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $allPackages = LeisurePackageTemplate::query()
            ->where('product_type', 'tour')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $galleryPhotos = LeisureMediaAsset::query()
            ->where('is_active', true)
            ->where('package_code', $package->code)
            ->where('category', 'gallery')
            ->orderBy('sort_order')
            ->get();

        $extraOptions = LeisureExtraOption::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('product_type')->orWhere('product_type', 'tour'))
            ->orderBy('sort_order')
            ->get();

        return view('acente.tour.show', compact('package', 'allPackages', 'galleryPhotos', 'extraOptions'));
    }

    public function book(Request $request, string $code, GtpnrService $gtpnrService, FinanceSyncService $financeSyncService): RedirectResponse
    {
        $package = LeisurePackageTemplate::query()
            ->where('product_type', 'tour')
            ->where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'service_date'       => 'required|date|after_or_equal:today',
            'pax_adult'          => 'required|integer|min:1|max:500',
            'pax_child'          => 'nullable|integer|min:0|max:500',
            'pax_infant'         => 'nullable|integer|min:0|max:500',
            'pickup_location'    => 'nullable|string|max:255',
            'guest_name'         => 'required|string|max:120',
            'guest_phone'        => 'required|string|max:30',
            'nationality'        => 'nullable|string|max:80',
            'notes'              => 'nullable|string|max:2000',
            'extra_option_codes' => 'nullable|array',
            'extra_option_codes.*' => 'nullable|string|max:50',
        ]);

        $paxAdult  = (int) $validated['pax_adult'];
        $paxChild  = (int) ($validated['pax_child'] ?? 0);
        $guestCount = $paxAdult + $paxChild;

        $pricePerPerson = (float) ($package->base_price_per_person ?? 0);
        $totalAmount = round(
            ($paxAdult * $pricePerPerson) + ($paxChild * $pricePerPerson * 0.5),
            2
        );
        $currency = $package->currency ?: 'EUR';

        $leisureRequest = DB::transaction(function () use (
            $validated, $package, $gtpnrService, $financeSyncService,
            $paxAdult, $paxChild, $guestCount, $totalAmount, $currency
        ) {
            $req = LeisureRequest::query()->create([
                'user_id'           => auth()->id(),
                'gtpnr'             => $gtpnrService->generate('tour'),
                'product_type'      => 'tour',
                'status'            => LeisureRequest::STATUS_APPROVED,
                'service_date'      => $validated['service_date'],
                'guest_count'       => $guestCount,
                'transfer_required' => !empty($validated['pickup_location']),
                'hotel_name'        => $validated['pickup_location'] ?? null,
                'guest_name'        => $validated['guest_name'],
                'guest_phone'       => $validated['guest_phone'],
                'package_level'     => $package->level,
                'nationality'       => $validated['nationality'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'approved_at'       => now(),
            ]);

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

            $booking = LeisureBooking::query()->create([
                'leisure_request_id' => $req->id,
                'client_offer_id'    => $offer->id,
                'status'             => 'pending_payment',
                'total_amount'       => $totalAmount,
                'total_paid'         => 0,
                'remaining_amount'   => $totalAmount,
                'currency'           => $currency,
                'operation_note'     => 'Tur direkt rezervasyon — web panel',
            ]);

            $financeSyncService->syncLeisureBooking($booking->fresh(['request', 'clientOffer']), auth()->id());

            return $req;
        });

        try {
            $agencyName = auth()->user()->name;
            $booking    = $leisureRequest->booking ?? $leisureRequest->load('booking')->booking;
            $amount     = (float) ($booking?->total_amount ?? 0);
            $cur        = $booking?->currency ?? 'EUR';
            $adminUrl   = url('/superadmin/leisure/dinner-cruise/' . $leisureRequest->gtpnr);

            (new NotificationService())->yeniLeisureBooking($leisureRequest->gtpnr, $agencyName, 'tour', $amount, $cur, $adminUrl);
            (new EmailService())->yeniLeisureBooking($leisureRequest->gtpnr, $agencyName, 'tour', $amount, $cur, $adminUrl);

            $amtFmt = number_format($amount, 0, ',', '.') . ' ' . $cur;
            (new SmsService())->sendByEvent('new_request', null,
                "🗺️ Yeni Tur rezervasyonu: {$leisureRequest->gtpnr} / {$agencyName} / {$amtFmt}",
                ['gtpnr' => $leisureRequest->gtpnr, 'acente_adi' => $agencyName]
            );
        } catch (\Throwable $e) {
            Log::warning('Tour booking notification error: ' . $e->getMessage());
        }

        return redirect()
            ->route('acente.tour.booking-show', $leisureRequest)
            ->with('success', 'Tur rezervasyonunuz oluşturuldu! Ödemenizi tamamlayın.');
    }

    public function bookingShow(LeisureRequest $leisureRequest)
    {
        abort_unless(
            $leisureRequest->user_id === auth()->id() && $leisureRequest->product_type === 'tour',
            403
        );

        $leisureRequest->load([
            'clientOffers.packageTemplate',
            'booking.payments',
        ]);

        return view('acente.tour.booking', compact('leisureRequest'));
    }
}

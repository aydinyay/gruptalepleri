<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\LeisureBooking;
use App\Models\LeisureClientOffer;
use App\Models\LeisurePackageTemplate;
use App\Models\LeisureRequest;
use App\Models\YachtCharterRequestDetail;
use App\Services\GtpnrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeisureInquiryController extends Controller
{
    public function store(Request $request, GtpnrService $gtpnrService)
    {
        $validated = $request->validate([
            'package_code'   => 'required|string|max:60',
            'service_date'   => 'required|date|after_or_equal:today',
            'guest_count'    => 'required|integer|min:1|max:500',
            'duration_hours' => 'required|integer|min:1|max:24',
            'start_time'     => 'nullable|string|max:10',
            'event_type'     => 'nullable|string|max:120',
            'guest_name'     => 'required|string|max:120',
            'guest_phone'    => 'required|string|max:30',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $package = LeisurePackageTemplate::where('code', $validated['package_code'])
            ->where('is_active', true)
            ->first();

        $price    = (float) ($package?->original_price_per_person ?? $package?->base_price_per_person ?? 0);
        $currency = $package?->currency ?: 'EUR';
        $total    = $price * (int) $validated['duration_hours'];

        $booking = DB::transaction(function () use ($validated, $package, $price, $currency, $total, $gtpnrService): LeisureBooking {
            $req = LeisureRequest::create([
                'user_id'        => null,
                'source_channel' => 'b2c',
                'gtpnr'          => $gtpnrService->generate('yacht'),
                'product_type'   => LeisureRequest::PRODUCT_YACHT,
                'status'         => LeisureRequest::STATUS_APPROVED,
                'service_date'   => $validated['service_date'],
                'guest_count'    => (int) $validated['guest_count'],
                'guest_name'     => $validated['guest_name'],
                'guest_phone'    => $validated['guest_phone'],
                'package_level'  => $package?->level,
                'notes'          => $validated['notes'] ?? null,
                'approved_at'    => now(),
            ]);

            YachtCharterRequestDetail::create([
                'leisure_request_id' => $req->id,
                'start_time'         => $validated['start_time'] ?? null,
                'duration_hours'     => (int) $validated['duration_hours'],
                'event_type'         => $validated['event_type'] ?? null,
                'detail_json'        => ['booking_type' => 'b2c_direct', 'package_code' => $validated['package_code']],
            ]);

            $offer = LeisureClientOffer::create([
                'leisure_request_id'  => $req->id,
                'package_template_id' => $package?->id,
                'package_label'       => $package?->name_tr ?? $validated['package_code'],
                'total_price'         => $total,
                'per_person_price'    => $price,
                'currency'            => $currency,
                'includes_snapshot'   => $package?->includes_tr,
                'excludes_snapshot'   => $package?->excludes_tr,
                'status'              => 'accepted',
                'shared_at'           => now(),
                'accepted_at'         => now(),
            ]);

            return LeisureBooking::create([
                'leisure_request_id' => $req->id,
                'client_offer_id'    => $offer->id,
                'status'             => 'pending_payment',
                'total_amount'       => $total,
                'total_paid'         => 0,
                'remaining_amount'   => $total,
                'currency'           => $currency,
                'operation_note'     => 'B2C direkt rezervasyon',
            ]);
        });

        return redirect()->route('b2c.leisure.booking.show', $booking->request->gtpnr);
    }

    public function show(string $gtpnr)
    {
        $leisureRequest = LeisureRequest::where('gtpnr', $gtpnr)
            ->where('source_channel', 'b2c')
            ->with(['booking.payments', 'yachtDetail', 'clientOffers.packageTemplate'])
            ->firstOrFail();

        $booking = $leisureRequest->booking;
        $offer   = $leisureRequest->clientOffers->first();
        $detail  = $leisureRequest->yachtDetail;
        $pkg     = $offer?->packageTemplate;

        return view('b2c.product.leisure-confirm', compact('leisureRequest', 'booking', 'offer', 'detail', 'pkg'));
    }

    public function confirm()
    {
        return redirect()->route('b2c.home');
    }
}

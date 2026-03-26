<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\DinnerCruiseRequestDetail;
use App\Models\FinanceRecord;
use App\Models\LeisureBooking;
use App\Models\LeisureClientOffer;
use App\Models\LeisureExtraOption;
use App\Models\LeisurePackageTemplate;
use App\Models\LeisureRequest;
use App\Models\LeisureRequestExtra;
use App\Models\LeisureMediaAsset;
use App\Models\YachtCharterRequestDetail;
use App\Services\Finance\FinanceSyncService;
use App\Services\GtpnrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class BaseLeisureRequestController extends Controller
{
    abstract protected function productType(): string;

    abstract protected function routePrefix(): string;

    public function index()
    {
        $requests = LeisureRequest::query()
            ->where('user_id', auth()->id())
            ->where('product_type', $this->productType())
            ->with(['clientOffers', 'booking', 'dinnerCruiseDetail', 'yachtDetail'])
            ->latest()
            ->paginate(15);

        return view('acente.leisure.index', [
            'requests' => $requests,
            'productType' => $this->productType(),
            'routePrefix' => $this->routePrefix(),
            'packageTemplates' => LeisurePackageTemplate::query()
                ->where('product_type', $this->productType())
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'extraOptions' => LeisureExtraOption::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('product_type')->orWhere('product_type', $this->productType());
                })
                ->orderBy('sort_order')
                ->get(),
            'mediaAssets' => LeisureMediaAsset::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('product_type')->orWhere('product_type', $this->productType());
                })
                ->orderBy('sort_order')
                ->limit(6)
                ->get(),
        ]);
    }

    public function create()
    {
        return view('acente.leisure.create', [
            'productType' => $this->productType(),
            'routePrefix' => $this->routePrefix(),
            'packageTemplates' => LeisurePackageTemplate::query()
                ->where('product_type', $this->productType())
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'extraOptions' => LeisureExtraOption::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('product_type')->orWhere('product_type', $this->productType());
                })
                ->orderBy('sort_order')
                ->get(),
            'mediaAssets' => LeisureMediaAsset::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('product_type')->orWhere('product_type', $this->productType());
                })
                ->orderBy('sort_order')
                ->limit(6)
                ->get(),
            'regions' => ['Sultanahmet', 'Taksim', 'Sisli', 'Besiktas', 'Levent', 'Diger'],
        ]);
    }

    public function store(Request $request, GtpnrService $gtpnrService): RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'service_date' => 'required|date|after_or_equal:today',
            'guest_count' => 'required|integer|min:1|max:500',
            'transfer_required' => 'nullable|boolean',
            'hotel_name' => 'nullable|string|max:255|required_if:transfer_required,1',
            'transfer_region' => 'nullable|string|max:80|required_if:transfer_required,1',
            'guest_name' => 'nullable|string|max:120|required_if:transfer_required,1',
            'guest_phone' => 'nullable|string|max:30|required_if:transfer_required,1',
            'package_level' => 'nullable|in:standard,vip,premium',
            'alcohol_preference' => 'nullable|string|max:40',
            'menu_preference' => 'nullable|string|max:120',
            'language_preference' => 'required|in:tr,en',
            'nationality' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:4000',
            'extra_requests' => 'nullable|string|max:4000',
            'extra_option_codes' => 'nullable|array',
            'extra_option_codes.*' => 'nullable|string|max:50',
        ], $this->detailRules()));

        $leisureRequest = DB::transaction(function () use ($validated, $gtpnrService) {
            $requestModel = LeisureRequest::query()->create([
                'user_id' => auth()->id(),
                'gtpnr' => $gtpnrService->generate($this->productType()),
                'product_type' => $this->productType(),
                'status' => LeisureRequest::STATUS_NEW,
                'service_date' => $validated['service_date'],
                'guest_count' => $validated['guest_count'],
                'transfer_required' => (bool) ($validated['transfer_required'] ?? false),
                'hotel_name' => $validated['hotel_name'] ?? null,
                'transfer_region' => $validated['transfer_region'] ?? null,
                'guest_name' => $validated['guest_name'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'package_level' => $validated['package_level'] ?? 'standard',
                'alcohol_preference' => $validated['alcohol_preference'] ?? null,
                'menu_preference' => $validated['menu_preference'] ?? null,
                'language_preference' => $validated['language_preference'],
                'nationality' => $validated['nationality'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'extra_requests' => $validated['extra_requests'] ?? null,
            ]);

            if ($this->productType() === LeisureRequest::PRODUCT_DINNER_CRUISE) {
                DinnerCruiseRequestDetail::query()->create([
                    'leisure_request_id' => $requestModel->id,
                    'session_time' => $validated['dinner']['session_time'] ?? null,
                    'pier_name' => $validated['dinner']['pier_name'] ?? null,
                    'adult_count' => $validated['dinner']['adult_count'] ?? null,
                    'child_count' => $validated['dinner']['child_count'] ?? null,
                    'infant_count' => $validated['dinner']['infant_count'] ?? null,
                    'celebration_type' => $validated['dinner']['celebration_type'] ?? null,
                    'shared_cruise' => (bool) ($validated['dinner']['shared_cruise'] ?? true),
                    'detail_json' => [
                        'transfer_warning' => ($validated['transfer_region'] ?? null) === 'Diger'
                            ? 'Transfer planlamasi manuel teyit gerektirir.'
                            : null,
                    ],
                ]);
            } else {
                YachtCharterRequestDetail::query()->create([
                    'leisure_request_id' => $requestModel->id,
                    'start_time' => $validated['yacht']['start_time'] ?? null,
                    'duration_hours' => $validated['yacht']['duration_hours'] ?? null,
                    'marina_name' => $validated['yacht']['marina_name'] ?? null,
                    'route_plan' => $validated['yacht']['route_plan'] ?? null,
                    'event_type' => $validated['yacht']['event_type'] ?? null,
                    'vessel_style' => $validated['yacht']['vessel_style'] ?? null,
                ]);
            }

            $selectedCodes = collect($validated['extra_option_codes'] ?? [])->filter()->values();
            if ($requestModel->transfer_required) {
                $shuttle = LeisureExtraOption::query()->where('code', 'shuttle_transfer')->first();
                LeisureRequestExtra::query()->create([
                    'leisure_request_id' => $requestModel->id,
                    'extra_option_id' => $shuttle?->id,
                    'title' => $shuttle?->title_tr ?? 'Shuttle Transfer',
                    'agency_note' => 'Varsayilan transfer, fiyat icinde kabul edildi.',
                    'status' => 'included',
                ]);
            }

            if ($selectedCodes->isNotEmpty()) {
                $options = LeisureExtraOption::query()->whereIn('code', $selectedCodes)->get()->keyBy('code');
                foreach ($selectedCodes as $code) {
                    $option = $options->get($code);
                    if (!$option || ($requestModel->transfer_required && $code === 'shuttle_transfer')) {
                        continue;
                    }

                    LeisureRequestExtra::query()->create([
                        'leisure_request_id' => $requestModel->id,
                        'extra_option_id' => $option->id,
                        'title' => $option->title_tr,
                        'agency_note' => $option->description_tr,
                        'status' => 'requested',
                    ]);
                }
            }

            return $requestModel;
        });

        return redirect()
            ->route($this->routePrefix() . '.show', $leisureRequest)
            ->with('success', $leisureRequest->productLabel() . ' talebiniz olusturuldu.');
    }

    public function show(LeisureRequest $leisureRequest)
    {
        abort_unless(
            $leisureRequest->user_id === auth()->id() && $leisureRequest->product_type === $this->productType(),
            403
        );

        $leisureRequest->load([
            'dinnerCruiseDetail',
            'yachtDetail',
            'extras.option',
            'clientOffers.packageTemplate',
            'booking.payments',
        ]);

        $financeRecord = $leisureRequest->booking
            ? FinanceRecord::query()
                ->where('service_type', 'leisure_booking')
                ->where('service_id', $leisureRequest->booking->id)
                ->first()
            : null;

        return view('acente.leisure.show', [
            'leisureRequest' => $leisureRequest,
            'productType' => $this->productType(),
            'routePrefix' => $this->routePrefix(),
            'financeRecord' => $financeRecord,
        ]);
    }

    public function acceptOffer(LeisureRequest $leisureRequest, LeisureClientOffer $offer, FinanceSyncService $financeSyncService): RedirectResponse
    {
        abort_unless(
            $leisureRequest->user_id === auth()->id() && $leisureRequest->product_type === $this->productType(),
            403
        );
        abort_unless($offer->leisure_request_id === $leisureRequest->id, 422);

        DB::transaction(function () use ($leisureRequest, $offer, $financeSyncService) {
            $leisureRequest->clientOffers()->where('id', '!=', $offer->id)->update(['status' => 'rejected']);
            $offer->update(['status' => 'accepted', 'accepted_at' => now()]);

            $booking = LeisureBooking::query()->updateOrCreate(
                ['leisure_request_id' => $leisureRequest->id],
                [
                    'client_offer_id' => $offer->id,
                    'status' => 'pending_payment',
                    'total_amount' => $offer->total_price,
                    'total_paid' => 0,
                    'remaining_amount' => $offer->total_price,
                    'currency' => $offer->currency,
                ]
            );

            $leisureRequest->update([
                'status' => LeisureRequest::STATUS_APPROVED,
                'approved_at' => now(),
            ]);

            $financeSyncService->syncLeisureBooking($booking->fresh(['request', 'clientOffer']), auth()->id());
        });

        return back()->with('success', 'Teklif kabul edildi. Finans kaydi olusturuldu.');
    }

    protected function detailRules(): array
    {
        if ($this->productType() === LeisureRequest::PRODUCT_DINNER_CRUISE) {
            return [
                'dinner.session_time' => 'nullable|string|max:80',
                'dinner.pier_name' => 'nullable|string|max:120',
                'dinner.adult_count' => 'nullable|integer|min:0|max:500',
                'dinner.child_count' => 'nullable|integer|min:0|max:500',
                'dinner.infant_count' => 'nullable|integer|min:0|max:500',
                'dinner.celebration_type' => 'nullable|string|max:120',
                'dinner.shared_cruise' => 'nullable|boolean',
            ];
        }

        return [
            'yacht.start_time' => 'nullable|date_format:H:i',
            'yacht.duration_hours' => 'nullable|integer|min:1|max:24',
            'yacht.marina_name' => 'nullable|string|max:120',
            'yacht.route_plan' => 'nullable|string|max:255',
            'yacht.event_type' => 'nullable|string|max:120',
            'yacht.vessel_style' => 'nullable|string|max:120',
        ];
    }
}

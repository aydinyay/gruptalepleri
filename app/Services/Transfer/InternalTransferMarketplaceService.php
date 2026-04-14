<?php

namespace App\Services\Transfer;

use App\Models\SistemAyar;
use App\Models\TransferAirport;
use App\Models\TransferCancellationPolicy;
use App\Models\TransferPricingRule;
use App\Models\TransferQuoteLock;
use App\Models\TransferZone;
use App\Services\Transfer\Contracts\TransferDistanceCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InternalTransferMarketplaceService
{
    public function __construct(
        private readonly TransferDistanceCalculator $distanceCalculator
    ) {
    }

    /**
     * @return array<int, array{id:int,code:string,name:string,city:string,country:string}>
     */
    public function airports(): array
    {
        return TransferAirport::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (TransferAirport $airport): array => [
                'id' => $airport->id,
                'code' => strtoupper((string) $airport->code),
                'name' => (string) $airport->name,
                'city' => (string) $airport->city,
                'country' => (string) $airport->country,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string,city:string}>
     */
    public function zones(int $airportId): array
    {
        return TransferZone::query()
            ->where('airport_id', $airportId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (TransferZone $zone): array => [
                'id' => $zone->id,
                'name' => (string) $zone->name,
                'city' => (string) $zone->city,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function search(array $payload, int $userId, string $checkoutRouteName): array
    {
        $airport = TransferAirport::query()
            ->where('id', $payload['airport_id'])
            ->where('is_active', true)
            ->first();

        $zone = TransferZone::query()
            ->where('id', $payload['zone_id'])
            ->where('is_active', true)
            ->first();

        if (! $airport || ! $zone || $zone->airport_id !== $airport->id) {
            return [
                'options' => [],
                'noResultsReason' => 'Secilen havalimani/bolge eslesmesi bulunamadi.',
            ];
        }

        if ($airport->latitude === null || $airport->longitude === null || $zone->latitude === null || $zone->longitude === null) {
            return [
                'options' => [],
                'noResultsReason' => 'Rota koordinat bilgisi eksik oldugu icin fiyat hesaplanamadi.',
            ];
        }

        $pickupAt = Carbon::parse($payload['pickup_at']);
        $returnAt = ! empty($payload['return_at']) ? Carbon::parse($payload['return_at']) : null;

        $distance = $this->distanceCalculator->between(
            (float) $airport->latitude,
            (float) $airport->longitude,
            (float) $zone->latitude,
            (float) $zone->longitude
        );

        $currentTermsVersion = SistemAyar::transferSupplierTermsVersion();

        // Araç tipi medyasını önceden yükleyelim (N+1 önlemi)
        $vehicleTypeIds = TransferPricingRule::query()
            ->where('airport_id', $airport->id)
            ->where('zone_id', $zone->id)
            ->pluck('vehicle_type_id')
            ->unique()
            ->all();

        /** @var \Illuminate\Support\Collection<int, \App\Models\TransferVehicleMedia> $allVehicleMedia */
        $allVehicleMedia = \App\Models\TransferVehicleMedia::query()
            ->whereIn('vehicle_type_id', $vehicleTypeIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('vehicle_type_id');

        $rules = $this->queryRules(
            airportId: (int) $airport->id,
            zoneId: (int) $zone->id,
            direction: (string) $payload['direction'],
            pax: (int) $payload['pax'],
            currency: strtoupper((string) $payload['currency']),
            currentTermsVersion: $currentTermsVersion
        );

        $results = [];

        foreach ($rules as $rule) {
            $supplier = $rule->supplier;
            $policy = $supplier?->cancellationPolicy;

            if (! $supplier || ! $supplier->canOperate($currentTermsVersion)) {
                continue;
            }

            $priceBreakdown = $this->calculatePrice(
                rule: $rule,
                distanceKm: (float) $distance['distance_km'],
                durationMinutes: (int) $distance['duration_minutes'],
                pickupAt: $pickupAt,
                direction: (string) $payload['direction'],
                returnAt: $returnAt
            );

            $quoteLock = TransferQuoteLock::query()->create([
                'token' => (string) Str::uuid(),
                'supplier_id' => $supplier->id,
                'airport_id' => $airport->id,
                'zone_id' => $zone->id,
                'vehicle_type_id' => $rule->vehicle_type_id,
                'direction' => (string) $payload['direction'],
                'currency' => strtoupper((string) $payload['currency']),
                'pax' => (int) $payload['pax'],
                'pickup_at' => $pickupAt,
                'return_at' => $returnAt,
                'distance_km' => $distance['distance_km'],
                'duration_minutes' => $distance['duration_minutes'],
                'subtotal_amount' => $priceBreakdown['subtotal_amount'],
                'commission_amount' => $priceBreakdown['commission_amount'],
                'total_amount' => $priceBreakdown['total_amount'],
                'price_breakdown_json' => $priceBreakdown,
                'snapshot_json' => [
                    'supplier' => [
                        'id' => $supplier->id,
                        'company_name' => $supplier->company_name,
                        'commission_rate' => (float) $supplier->commission_rate,
                    ],
                    'rule' => [
                        'id' => $rule->id,
                        'direction' => $rule->direction,
                        'currency' => $rule->currency,
                        'base_fare' => (float) $rule->base_fare,
                        'per_km' => (float) $rule->per_km,
                        'per_minute' => (float) $rule->per_minute,
                        'minimum_fare' => (float) $rule->minimum_fare,
                        'night_start' => $rule->night_start,
                        'night_end' => $rule->night_end,
                        'night_multiplier' => (float) $rule->night_multiplier,
                        'peak_multiplier' => (float) $rule->peak_multiplier,
                    ],
                    'policy' => $policy ? [
                        'free_cancel_before_minutes' => (int) $policy->free_cancel_before_minutes,
                        'refund_percent_after_deadline' => (float) $policy->refund_percent_after_deadline,
                        'no_show_refund_percent' => (float) $policy->no_show_refund_percent,
                    ] : null,
                    'airport' => [
                        'id' => $airport->id,
                        'code' => $airport->code,
                        'name' => $airport->name,
                    ],
                    'zone' => [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'city' => $zone->city,
                    ],
                ],
                'expires_at' => now()->addMinutes(max(1, (int) config('transfer.quote_ttl_minutes', 10))),
                'created_by_user_id' => $userId,
            ]);

            $vtId = (int) $rule->vehicle_type_id;
            $vtMedia = $allVehicleMedia->get($vtId, collect());

            $results[] = [
                'quote_token'              => $quoteLock->token,
                'vehicle_type'             => (string) ($rule->vehicleType?->name ?? 'Transfer Araci'),
                'vehicle_description'      => (string) ($rule->vehicleType?->description ?? ''),
                'vehicle_max_passengers'   => (int) ($rule->vehicleType?->max_passengers ?? 0),
                'vehicle_luggage_capacity' => $rule->vehicleType?->luggage_capacity,
                'vehicle_amenities'        => $rule->vehicleType?->activeAmenities() ?? [],
                'vehicle_suggested_retail' => $rule->vehicleType?->suggested_retail_price !== null
                    ? (float) $rule->vehicleType->suggested_retail_price
                    : null,
                'vehicle_photos'           => $vtMedia
                    ->where('media_type', 'photo')
                    ->take(6)
                    ->map(fn ($m) => $m->resolvedUrl())
                    ->filter()
                    ->values()
                    ->all(),
                'vehicle_video'            => $vtMedia
                    ->where('media_type', 'video')
                    ->first()
                    ?->resolvedUrl(),
                'supplier_name'            => (string) $supplier->company_name,
                'supplier_rating'          => null,
                'duration_minutes'         => (int) $distance['duration_minutes'],
                'distance_km'              => (float) $distance['distance_km'],
                'cancellation_policy'      => $this->formatPolicy($policy),
                'total_price'              => (float) $priceBreakdown['total_amount'],
                'currency'                 => strtoupper((string) $rule->currency),
                'price_breakdown'          => $priceBreakdown,
                'booking_url'              => route($checkoutRouteName, ['quoteToken' => $quoteLock->token]),
            ];
        }

        usort($results, static fn (array $left, array $right): int => ($left['total_price'] <=> $right['total_price']));

        return [
            'options' => $results,
            'noResultsReason' => empty($results) ? 'Secilen kriterlerde aktif supplier bulunamadi.' : null,
        ];
    }

    public function findValidQuote(string $quoteToken): ?TransferQuoteLock
    {
        $quote = TransferQuoteLock::query()
            ->with(['supplier', 'airport', 'zone', 'vehicleType'])
            ->where('token', $quoteToken)
            ->first();

        if (! $quote || $quote->consumed_at !== null || $quote->isExpired()) {
            return null;
        }

        return $quote;
    }

    /**
     * @return Collection<int, TransferPricingRule>
     */
    private function queryRules(
        int $airportId,
        int $zoneId,
        string $direction,
        int $pax,
        string $currency,
        int $currentTermsVersion
    ): Collection
    {
        return TransferPricingRule::query()
            ->with(['supplier.cancellationPolicy', 'vehicleType'])
            ->where('airport_id', $airportId)
            ->where('zone_id', $zoneId)
            ->whereIn('direction', [$direction, 'BOTH'])
            ->where('currency', $currency)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->whereHas('vehicleType', function ($query) use ($pax): void {
                $query->where('is_active', true)->where('max_passengers', '>=', $pax);
            })
            ->whereHas('supplier', function ($query) use ($currentTermsVersion): void {
                $query
                    ->where('is_active', true)
                    ->where('is_approved', true)
                    ->whereNotNull('terms_accepted_at')
                    ->where('terms_version_accepted', $currentTermsVersion);
            })
            ->whereExists(function ($query) use ($airportId, $zoneId, $direction): void {
                $query
                    ->select(DB::raw('1'))
                    ->from('transfer_supplier_coverages')
                    ->whereColumn('transfer_supplier_coverages.supplier_id', 'transfer_pricing_rules.supplier_id')
                    ->where('transfer_supplier_coverages.airport_id', $airportId)
                    ->where('transfer_supplier_coverages.zone_id', $zoneId)
                    ->whereIn('transfer_supplier_coverages.direction', [$direction, 'BOTH'])
                    ->where('transfer_supplier_coverages.is_active', true);
            })
            ->get();
    }

    /**
     * @return array<string, float|int|string|null>
     */
    private function calculatePrice(
        TransferPricingRule $rule,
        float $distanceKm,
        int $durationMinutes,
        Carbon $pickupAt,
        string $direction,
        ?Carbon $returnAt
    ): array {
        $base = (float) $rule->base_fare;
        $distanceAmount = (float) $rule->per_km * $distanceKm;
        $durationAmount = (float) $rule->per_minute * $durationMinutes;

        $subtotal = $base + $distanceAmount + $durationAmount;
        $minimumFare = (float) $rule->minimum_fare;

        if ($subtotal < $minimumFare) {
            $subtotal = $minimumFare;
        }

        $nightMultiplier = $this->resolveNightMultiplier($rule, $pickupAt);
        $peakMultiplier = $this->resolvePeakMultiplier($rule, $pickupAt);
        $tripFactor = ($direction === 'BOTH' && $returnAt !== null) ? 2.0 : 1.0;

        $subtotalAmount = round($subtotal * $nightMultiplier * $peakMultiplier * $tripFactor, 2);
        $commissionRate = (float) ($rule->supplier?->commission_rate ?? 0);
        $commissionAmount = round($subtotalAmount * ($commissionRate / 100), 2);

        return [
            'base_amount' => round($base, 2),
            'distance_amount' => round($distanceAmount, 2),
            'duration_amount' => round($durationAmount, 2),
            'minimum_fare' => round($minimumFare, 2),
            'night_multiplier' => $nightMultiplier,
            'peak_multiplier' => $peakMultiplier,
            'trip_factor' => $tripFactor,
            'subtotal_amount' => $subtotalAmount,
            'commission_rate' => round($commissionRate, 2),
            'commission_amount' => $commissionAmount,
            'total_amount' => $subtotalAmount,
        ];
    }

    private function resolveNightMultiplier(TransferPricingRule $rule, Carbon $pickupAt): float
    {
        $start = trim((string) $rule->night_start);
        $end = trim((string) $rule->night_end);

        if ($start === '' || $end === '') {
            return 1.0;
        }

        $time = $pickupAt->format('H:i:s');

        if ($start <= $end) {
            $isInNightWindow = $time >= $start && $time <= $end;
        } else {
            $isInNightWindow = $time >= $start || $time <= $end;
        }

        return $isInNightWindow ? max(1.0, (float) $rule->night_multiplier) : 1.0;
    }

    private function resolvePeakMultiplier(TransferPricingRule $rule, Carbon $pickupAt): float
    {
        $hour = (int) $pickupAt->format('H');
        $isPeak = ($hour >= 7 && $hour < 10) || ($hour >= 17 && $hour < 20);

        return $isPeak ? max(1.0, (float) $rule->peak_multiplier) : 1.0;
    }

    private function formatPolicy(?TransferCancellationPolicy $policy): string
    {
        if (! $policy) {
            return 'Iptal kurali supplier politikasina gore uygulanir.';
        }

        $minutes = (int) $policy->free_cancel_before_minutes;
        $refundPercent = number_format((float) $policy->refund_percent_after_deadline, 0, ',', '.');

        return $minutes . ' dk kalana kadar ucretsiz. Sonrasinda iade orani ' . $refundPercent . '%.';
    }
}

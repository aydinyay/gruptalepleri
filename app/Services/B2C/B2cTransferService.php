<?php

namespace App\Services\B2C;

use App\Models\B2C\B2cAgencySubscription;
use App\Models\TransferAirport;
use App\Models\TransferCancellationPolicy;
use App\Models\TransferPricingRule;
use App\Models\TransferQuoteLock;
use App\Models\TransferVehicleFleet;
use App\Models\TransferZone;
use App\Services\Transfer\Contracts\TransferDistanceCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * B2C Transfer arama ve fiyatlama servisi.
 *
 * InternalTransferMarketplaceService'in B2C karşılığıdır.
 * Farklılıklar:
 * - Yalnızca B2C onaylı tedarikçiler gösterilir.
 * - Araç filosu (vehicle fleet) ile kapasite kontrolü yapılır.
 * - Komisyon oranı, B2C abonelik tablosundan alınır (yoksa config varsayılanı).
 * - created_by_user_id nullable (misafir müşteri desteklenir).
 */
class B2cTransferService
{
    public function __construct(
        private readonly TransferDistanceCalculator $distanceCalculator
    ) {}

    /**
     * Havalimanı listesi (aynı B2B ile paylaşılır).
     */
    public function airports(): array
    {
        return TransferAirport::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (TransferAirport $a): array => [
                'id'      => $a->id,
                'code'    => strtoupper((string) $a->code),
                'name'    => (string) $a->name,
                'city'    => (string) $a->city,
                'country' => (string) $a->country,
            ])
            ->all();
    }

    /**
     * Havalimanına ait bölge listesi.
     */
    public function zones(int $airportId): array
    {
        return TransferZone::query()
            ->where('airport_id', $airportId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (TransferZone $z): array => [
                'id'   => $z->id,
                'name' => (string) $z->name,
                'city' => (string) $z->city,
            ])
            ->all();
    }

    /**
     * B2C transfer arama — fiyat + müsaitlik sonuçları döner.
     *
     * @param  array<string, mixed>  $payload
     *   required: airport_id, zone_id, direction, pax, pickup_at
     *   optional: return_at (direction=BOTH ise)
     * @param  int|null  $b2cUserId  Oturum açmış B2C müşteri id'si (misafir için null)
     */
    public function search(array $payload, ?int $b2cUserId = null): array
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
            return ['options' => [], 'error' => 'Seçilen havalimanı/bölge eşleşmesi bulunamadı.'];
        }

        if ($airport->latitude === null || $airport->longitude === null
            || $zone->latitude === null || $zone->longitude === null) {
            return ['options' => [], 'error' => 'Rota koordinat bilgisi eksik, fiyat hesaplanamadı.'];
        }

        $pickupAt = Carbon::parse($payload['pickup_at']);
        $returnAt = ! empty($payload['return_at']) ? Carbon::parse($payload['return_at']) : null;

        $distance = $this->distanceCalculator->between(
            (float) $airport->latitude,
            (float) $airport->longitude,
            (float) $zone->latitude,
            (float) $zone->longitude
        );

        // Araç tipi medyasını toplu yükle
        $vehicleTypeIds = TransferPricingRule::query()
            ->where('airport_id', $airport->id)
            ->where('zone_id', $zone->id)
            ->pluck('vehicle_type_id')
            ->unique()
            ->all();

        $allVehicleMedia = \App\Models\TransferVehicleMedia::query()
            ->whereIn('vehicle_type_id', $vehicleTypeIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('vehicle_type_id');

        $rules = $this->queryRules(
            airportId:  (int) $airport->id,
            zoneId:     (int) $zone->id,
            direction:  (string) $payload['direction'],
            pax:        (int) $payload['pax'],
        );

        // Fleet kapasite verilerini toplu yükle (N+1 önlemi)
        $supplierIds    = $rules->pluck('supplier_id')->unique()->all();
        $fleetBySupplier = TransferVehicleFleet::query()
            ->whereIn('supplier_id', $supplierIds)
            ->whereIn('vehicle_type_id', $vehicleTypeIds)
            ->where('is_active', true)
            ->get()
            ->groupBy(fn ($f) => $f->supplier_id . '_' . $f->vehicle_type_id);

        $results = [];

        foreach ($rules as $rule) {
            $supplier    = $rule->supplier;
            $policy      = $supplier?->cancellationPolicy;
            $b2cSub      = $supplier?->b2cSubscription;

            if (! $supplier || ! $b2cSub?->isApproved()) {
                continue;
            }

            // Kapasite kontrolü
            $fleetKey = $supplier->id . '_' . $rule->vehicle_type_id;
            $fleet    = $fleetBySupplier->get($fleetKey)?->first();

            if ($fleet && ! $fleet->isAvailableForDate($pickupAt)) {
                continue; // Doldu — gösterme
            }

            // B2C komisyon oranı
            $b2cCommissionRate = $b2cSub->effectiveCommissionPct('transfer');

            $priceBreakdown = $this->calculatePrice(
                rule:              $rule,
                distanceKm:        (float) $distance['distance_km'],
                durationMinutes:   (int) $distance['duration_minutes'],
                pickupAt:          $pickupAt,
                direction:         (string) $payload['direction'],
                returnAt:          $returnAt,
                b2cCommissionRate: $b2cCommissionRate,
            );

            $quoteLock = TransferQuoteLock::query()->create([
                'token'               => (string) Str::uuid(),
                'supplier_id'         => $supplier->id,
                'airport_id'          => $airport->id,
                'zone_id'             => $zone->id,
                'vehicle_type_id'     => $rule->vehicle_type_id,
                'direction'           => (string) $payload['direction'],
                'currency'            => strtoupper((string) $rule->currency),
                'pax'                 => (int) $payload['pax'],
                'pickup_at'           => $pickupAt,
                'return_at'           => $returnAt,
                'distance_km'         => $distance['distance_km'],
                'duration_minutes'    => $distance['duration_minutes'],
                'subtotal_amount'     => $priceBreakdown['subtotal_amount'],
                'commission_amount'   => $priceBreakdown['commission_amount'],
                'total_amount'        => $priceBreakdown['total_amount'],
                'price_breakdown_json' => $priceBreakdown,
                'snapshot_json'       => [
                    'source'   => 'b2c',
                    'supplier' => [
                        'id'           => $supplier->id,
                        'company_name' => $supplier->company_name,
                        'b2c_commission_rate' => $b2cCommissionRate,
                    ],
                    'rule'     => [
                        'id'              => $rule->id,
                        'direction'       => $rule->direction,
                        'currency'        => $rule->currency,
                        'base_fare'       => (float) $rule->base_fare,
                        'cost_price'      => $rule->cost_price !== null ? (float) $rule->cost_price : null,
                        'b2c_price'       => $rule->b2c_price !== null ? (float) $rule->b2c_price : null,
                        'per_km'          => (float) $rule->per_km,
                        'per_minute'      => (float) $rule->per_minute,
                        'minimum_fare'    => (float) $rule->minimum_fare,
                        'night_start'     => $rule->night_start,
                        'night_end'       => $rule->night_end,
                        'night_multiplier'=> (float) $rule->night_multiplier,
                        'peak_multiplier' => (float) $rule->peak_multiplier,
                    ],
                    'policy'   => $policy ? [
                        'free_cancel_before_minutes'   => (int) $policy->free_cancel_before_minutes,
                        'refund_percent_after_deadline' => (float) $policy->refund_percent_after_deadline,
                        'no_show_refund_percent'       => (float) $policy->no_show_refund_percent,
                    ] : null,
                    'airport'  => ['id' => $airport->id, 'code' => $airport->code, 'name' => $airport->name],
                    'zone'     => ['id' => $zone->id, 'name' => $zone->name, 'city' => $zone->city],
                ],
                'expires_at'          => now()->addMinutes(max(1, (int) config('transfer.quote_ttl_minutes', 15))),
                'created_by_user_id'  => null,
            ]);

            $vtId     = (int) $rule->vehicle_type_id;
            $vtMedia  = $allVehicleMedia->get($vtId, collect());

            $results[] = [
                'quote_token'              => $quoteLock->token,
                'vehicle_type'             => (string) ($rule->vehicleType?->name ?? 'Transfer Aracı'),
                'vehicle_description'      => (string) ($rule->vehicleType?->description ?? ''),
                'vehicle_max_passengers'   => (int) ($rule->vehicleType?->max_passengers ?? 0),
                'vehicle_luggage_capacity' => $rule->vehicleType?->luggage_capacity,
                'vehicle_amenities'        => $rule->vehicleType?->activeAmenities() ?? [],
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
                'duration_minutes'         => (int) $distance['duration_minutes'],
                'distance_km'              => (float) $distance['distance_km'],
                'cancellation_policy'      => $this->formatPolicy($policy),
                'total_price'              => (float) $priceBreakdown['total_amount'],
                'currency'                 => strtoupper((string) $rule->currency),
                'price_breakdown'          => $priceBreakdown,
                'booking_url'              => route('b2c.transfer.checkout', ['quoteToken' => $quoteLock->token]),
            ];
        }

        usort($results, static fn (array $a, array $b): int => $a['total_price'] <=> $b['total_price']);

        return [
            'options' => $results,
            'error'   => empty($results) ? 'Bu kriterlerde uygun transfer bulunamadı.' : null,
        ];
    }

    /**
     * Geçerli bir B2C quote lock döner (süresi geçmemiş, tüketilmemiş).
     */
    public function findValidQuote(string $quoteToken): ?TransferQuoteLock
    {
        $quote = TransferQuoteLock::query()
            ->with(['supplier', 'airport', 'zone', 'vehicleType'])
            ->where('token', $quoteToken)
            ->first();

        if (! $quote || $quote->consumed_at !== null || $quote->isExpired()) {
            return null;
        }

        // B2C kaynağı doğrula
        if (($quote->snapshot_json['source'] ?? '') !== 'b2c') {
            return null;
        }

        return $quote;
    }

    // ── Private Yardımcılar ───────────────────────────────────────────────────

    /**
     * @return Collection<int, TransferPricingRule>
     */
    private function queryRules(int $airportId, int $zoneId, string $direction, int $pax): Collection
    {
        return TransferPricingRule::query()
            ->with(['supplier.cancellationPolicy', 'supplier.b2cSubscription', 'vehicleType'])
            ->where('airport_id', $airportId)
            ->where('zone_id', $zoneId)
            ->whereIn('direction', [$direction, 'BOTH'])
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->whereHas('vehicleType', fn ($q) => $q->where('is_active', true)->where('max_passengers', '>=', $pax))
            ->whereHas('supplier', function ($q): void {
                $q->where('is_active', true)->where('is_approved', true);
            })
            ->whereHas('supplier.b2cSubscription', function ($q): void {
                $q->where('status', B2cAgencySubscription::STATUS_APPROVED);
            })
            ->whereExists(function ($q) use ($airportId, $zoneId, $direction): void {
                $q->select(DB::raw('1'))
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
        ?Carbon $returnAt,
        float $b2cCommissionRate
    ): array {
        $tripFactor = ($direction === 'BOTH' && $returnAt !== null) ? 2.0 : 1.0;

        // ── Mod 1: b2c_price sabit fiyat ─────────────────────────────────────
        // Tedarikçi ürün sayfasında açıkça B2C perakende fiyatı belirlemiş.
        // Formül hesabı yapılmaz; tedarikçi base_fare'i alır, fark GT komisyonudur.
        if ($rule->b2c_price !== null && (float) $rule->b2c_price > 0) {
            $b2cPrice       = round((float) $rule->b2c_price * $tripFactor, 2);
            $baseFare       = round((float) $rule->base_fare * $tripFactor, 2);
            $commissionAmount = round($b2cPrice - $baseFare, 2);

            return [
                'price_mode'        => 'fixed_b2c_price',
                'base_amount'       => $baseFare,
                'distance_amount'   => 0.0,
                'duration_amount'   => 0.0,
                'minimum_fare'      => 0.0,
                'night_multiplier'  => 1.0,
                'peak_multiplier'   => 1.0,
                'trip_factor'       => $tripFactor,
                'subtotal_amount'   => $b2cPrice,
                'commission_rate'   => $b2cPrice > 0 ? round($commissionAmount / $b2cPrice * 100, 2) : 0.0,
                'commission_amount' => $commissionAmount,
                'total_amount'      => $b2cPrice,
            ];
        }

        // ── Mod 2: Formül tabanlı hesaplama (varsayılan) ──────────────────────
        $base           = (float) $rule->base_fare;
        $distanceAmount = (float) $rule->per_km * $distanceKm;
        $durationAmount = (float) $rule->per_minute * $durationMinutes;

        $subtotal    = $base + $distanceAmount + $durationAmount;
        $minimumFare = (float) $rule->minimum_fare;

        if ($subtotal < $minimumFare) {
            $subtotal = $minimumFare;
        }

        $nightMultiplier = $this->resolveNightMultiplier($rule, $pickupAt);
        $peakMultiplier  = $this->resolvePeakMultiplier($rule, $pickupAt);

        $subtotalAmount   = round($subtotal * $nightMultiplier * $peakMultiplier * $tripFactor, 2);
        $commissionAmount = round($subtotalAmount * ($b2cCommissionRate / 100), 2);

        return [
            'price_mode'        => 'formula',
            'base_amount'       => round($base, 2),
            'distance_amount'   => round($distanceAmount, 2),
            'duration_amount'   => round($durationAmount, 2),
            'minimum_fare'      => round($minimumFare, 2),
            'night_multiplier'  => $nightMultiplier,
            'peak_multiplier'   => $peakMultiplier,
            'trip_factor'       => $tripFactor,
            'subtotal_amount'   => $subtotalAmount,
            'commission_rate'   => round($b2cCommissionRate, 2),
            'commission_amount' => $commissionAmount,
            'total_amount'      => $subtotalAmount,
        ];
    }

    private function resolveNightMultiplier(TransferPricingRule $rule, Carbon $pickupAt): float
    {
        $start = trim((string) $rule->night_start);
        $end   = trim((string) $rule->night_end);

        if ($start === '' || $end === '') {
            return 1.0;
        }

        $time = $pickupAt->format('H:i:s');

        $isInNight = ($start <= $end)
            ? ($time >= $start && $time <= $end)
            : ($time >= $start || $time <= $end);

        return $isInNight ? max(1.0, (float) $rule->night_multiplier) : 1.0;
    }

    private function resolvePeakMultiplier(TransferPricingRule $rule, Carbon $pickupAt): float
    {
        $hour   = (int) $pickupAt->format('H');
        $isPeak = ($hour >= 7 && $hour < 10) || ($hour >= 17 && $hour < 20);

        return $isPeak ? max(1.0, (float) $rule->peak_multiplier) : 1.0;
    }

    private function formatPolicy(?TransferCancellationPolicy $policy): string
    {
        if (! $policy) {
            return 'İptal koşulları tedarikçi politikasına göre uygulanır.';
        }

        $minutes       = (int) $policy->free_cancel_before_minutes;
        $refundPercent = number_format((float) $policy->refund_percent_after_deadline, 0, ',', '.');

        return $minutes . ' dk. öncesine kadar ücretsiz iptal. Sonrasında iade oranı %' . $refundPercent . '.';
    }
}

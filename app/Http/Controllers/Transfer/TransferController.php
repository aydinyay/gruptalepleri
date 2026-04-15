<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Models\SistemAyar;
use App\Models\TransferSupplier;
use App\Services\Transfer\AirportTransferPortalService;
use App\Services\Transfer\InternalTransferMarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class TransferController extends Controller
{
    public function __construct(
        private readonly AirportTransferPortalService $atpService,
        private readonly InternalTransferMarketplaceService $internalService
    ) {
    }

    public function index(Request $request)
    {
        $roleContext = $this->resolveRoleContext($request);

        return view('transfer.index', [
            'roleContext' => $roleContext,
            'navbarComponent' => $this->navbarComponent($roleContext),
            'dashboardRoute' => $this->dashboardRoute($roleContext),
            'airportsEndpoint' => route($roleContext . '.transfer.airports'),
            'zonesEndpoint' => route($roleContext . '.transfer.zones'),
            'searchEndpoint' => route($roleContext . '.transfer.search'),
            'provider' => $this->provider(),
            'transferEnabled' => $this->isEnabled(),
            'acenteSupplierState' => $this->resolveAcenteSupplierState($request, $roleContext),
        ]);
    }

    public function airports(Request $request): JsonResponse
    {
        if (! $this->isEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Transfer servisi su anda devre disi.',
            ], 503);
        }

        try {
            $data = $this->isInternalProvider()
                ? $this->internalService->airports()
                : $this->atpService->airports();

            return response()->json([
                'ok' => true,
                'data' => $data,
            ]);
        } catch (RuntimeException $exception) {
            return $this->transferErrorResponse($exception, 'Havalimani listesi alinamadi.');
        }
    }

    public function zones(Request $request): JsonResponse
    {
        if (! $this->isEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Transfer servisi su anda devre disi.',
            ], 503);
        }

        $validated = $request->validate([
            'airport_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $data = $this->isInternalProvider()
                ? $this->internalService->zones((int) $validated['airport_id'])
                : $this->atpService->zones((int) $validated['airport_id']);

            return response()->json([
                'ok' => true,
                'data' => $data,
            ]);
        } catch (RuntimeException $exception) {
            return $this->transferErrorResponse($exception, 'Bolge listesi alinamadi.');
        }
    }

    public function search(Request $request): JsonResponse
    {
        if (! $this->isEnabled()) {
            return response()->json([
                'ok' => false,
                'message' => 'Transfer servisi su anda devre disi.',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'direction' => ['required', 'in:FROM_AIRPORT,TO_AIRPORT,BOTH'],
            'airport_id' => ['required', 'integer', 'min:1'],
            'zone_id' => ['required', 'integer', 'min:1'],
            'pickup_date' => ['required', 'date_format:Y-m-d'],
            'pickup_time' => ['required', 'date_format:H:i'],
            'pax' => ['required', 'integer', 'min:1', 'max:50'],
            'currency' => ['nullable', 'string', 'size:3'],
            'return_date' => ['nullable', 'date_format:Y-m-d', 'required_if:direction,BOTH'],
            'return_time' => ['nullable', 'date_format:H:i', 'required_if:direction,BOTH'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Lutfen zorunlu alanlari kontrol edin.',
                'errors' => $validator->errors(),
            ], 422);
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();

        try {
            if ($this->isInternalProvider()) {
                $roleContext = $this->resolveRoleContext($request);

                $result = $this->internalService->search(
                    payload: [
                        'direction' => (string) $validated['direction'],
                        'airport_id' => (int) $validated['airport_id'],
                        'zone_id' => (int) $validated['zone_id'],
                        'pickup_at' => $validated['pickup_date'] . ' ' . $validated['pickup_time'] . ':00',
                        'return_at' => ($validated['direction'] ?? '') === 'BOTH' && ! empty($validated['return_date']) && ! empty($validated['return_time'])
                            ? $validated['return_date'] . ' ' . $validated['return_time'] . ':00'
                            : null,
                        'pax' => (int) $validated['pax'],
                    ],
                    userId: (int) $request->user()->id,
                    checkoutRouteName: $roleContext . '.transfer.checkout.show',
                );

                return response()->json([
                    'ok' => true,
                    'data' => [
                        'options' => (array) ($result['options'] ?? []),
                        'no_results_reason' => $result['noResultsReason'] ?? null,
                    ],
                ]);
            }

            $payload = [
                'airportId' => (int) $validated['airport_id'],
                'zoneId' => (int) $validated['zone_id'],
                'direction' => (string) $validated['direction'],
                'pickupTime' => $validated['pickup_date'] . 'T' . $validated['pickup_time'] . ':00',
                'paxAdults' => (int) $validated['pax'],
                'currency' => strtoupper((string) $validated['currency']),
            ];

            if (($validated['direction'] ?? '') === 'BOTH' && ! empty($validated['return_date']) && ! empty($validated['return_time'])) {
                $payload['returnDate'] = $validated['return_date'] . 'T' . $validated['return_time'] . ':00';
            }

            $result = $this->atpService->search($payload);
            $options = $this->normalizeAtpOptions((array) ($result['options'] ?? []), $validated);

            return response()->json([
                'ok' => true,
                'data' => [
                    'options' => $options,
                    'no_results_reason' => trim((string) ($result['noResultsReason'] ?? '')) ?: null,
                ],
            ]);
        } catch (RuntimeException $exception) {
            return $this->transferErrorResponse($exception, 'Transfer arama sonucu alinamadi.');
        }
    }

    private function provider(): string
    {
        $provider = strtolower(trim((string) config('transfer.provider', 'internal')));

        return in_array($provider, ['internal', 'atp'], true) ? $provider : 'internal';
    }

    private function isInternalProvider(): bool
    {
        return $this->provider() === 'internal';
    }

    private function isEnabled(): bool
    {
        if ($this->isInternalProvider()) {
            return true;
        }

        return $this->atpService->isEnabled();
    }

    private function resolveRoleContext(Request $request): string
    {
        $roleContext = (string) $request->route('role_context', 'acente');

        return in_array($roleContext, ['acente', 'admin', 'superadmin'], true)
            ? $roleContext
            : 'acente';
    }

    private function navbarComponent(string $roleContext): string
    {
        return match ($roleContext) {
            'superadmin' => 'navbar-superadmin',
            'admin' => 'navbar-admin',
            default => 'navbar-acente',
        };
    }

    private function dashboardRoute(string $roleContext): string
    {
        return match ($roleContext) {
            'superadmin' => route('superadmin.dashboard'),
            'admin' => route('admin.dashboard'),
            default => route('acente.dashboard'),
        };
    }

    /**
     * @return array{show_panel_link:bool,show_terms_link:bool,panel_url:string|null,terms_url:string|null}
     */
    private function resolveAcenteSupplierState(Request $request, string $roleContext): array
    {
        if ($roleContext !== 'acente' || ! $request->user()) {
            return [
                'show_panel_link' => false,
                'show_terms_link' => false,
                'panel_url' => null,
                'terms_url' => null,
            ];
        }

        if (! Schema::hasTable('transfer_suppliers')) {
            return [
                'show_panel_link' => false,
                'show_terms_link' => false,
                'panel_url' => null,
                'terms_url' => null,
            ];
        }

        $supplier = TransferSupplier::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $supplier || ! $supplier->is_approved) {
            return [
                'show_panel_link' => false,
                'show_terms_link' => false,
                'panel_url' => null,
                'terms_url' => null,
            ];
        }

        $currentVersion = SistemAyar::transferSupplierTermsVersion();
        $accepted = $supplier->hasAcceptedVersion($currentVersion);

        return [
            'show_panel_link' => $accepted,
            'show_terms_link' => ! $accepted,
            'panel_url' => $accepted ? route('acente.transfer.supplier.index') : null,
            'terms_url' => route('acente.transfer.supplier.terms.show'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @param  array<string, mixed>  $searchInput
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAtpOptions(array $options, array $searchInput): array
    {
        $normalized = [];

        foreach ($options as $option) {
            if (! is_array($option)) {
                continue;
            }

            $optionCode = trim((string) ($option['optionCode'] ?? ''));
            if ($optionCode === '') {
                continue;
            }

            $supplier = is_array($option['supplier'] ?? null) ? $option['supplier'] : [];
            $supplierName = trim((string) ($supplier['name'] ?? $option['supplierName'] ?? 'Airport Transfer Portal'));
            $vehicleType = trim((string) ($option['vehicleType'] ?? 'Transfer Araci'));
            $duration = $this->resolveDurationMinutes($option);
            $cancellationPolicy = $this->resolveCancellationPolicy($option);
            $currency = strtoupper(trim((string) ($option['currency'] ?? $searchInput['currency'] ?? 'EUR')));
            $totalPrice = is_numeric($option['totalPrice'] ?? null)
                ? round((float) $option['totalPrice'], 2)
                : null;

            $bookingParams = array_filter([
                'optionCode' => $optionCode,
                'airportId' => (int) $searchInput['airport_id'],
                'zoneId' => (int) $searchInput['zone_id'],
                'pickupTime' => $searchInput['pickup_date'] . 'T' . $searchInput['pickup_time'] . ':00',
                'paxAdults' => (int) $searchInput['pax'],
                'vehicleType' => $vehicleType,
                'totalPrice' => $totalPrice !== null ? (string) $totalPrice : null,
                'currency' => $currency,
                'supplierName' => $supplierName,
                'partner' => $this->atpService->partnerCode(),
                'locale' => 'tr',
                'direction' => (string) ($searchInput['direction'] ?? 'FROM_AIRPORT'),
            ], static fn ($value) => $value !== null && $value !== '');

            $bookingUrl = $this->atpService->bookingBaseUrl() . '?' . http_build_query($bookingParams);

            $normalized[] = [
                'option_code' => $optionCode,
                'vehicle_type' => $vehicleType,
                'supplier_name' => $supplierName,
                'supplier_rating' => null,
                'duration_minutes' => $duration,
                'cancellation_policy' => $cancellationPolicy,
                'total_price' => $totalPrice,
                'currency' => $currency,
                'booking_url' => $bookingUrl,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $option
     */
    private function resolveDurationMinutes(array $option): ?int
    {
        foreach (['durationMinutes', 'estimatedDurationMinutes', 'estimatedDuration', 'duration'] as $key) {
            if (isset($option[$key]) && is_numeric($option[$key])) {
                return max(1, (int) round((float) $option[$key]));
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $option
     */
    private function resolveCancellationPolicy(array $option): string
    {
        foreach (['cancellationPolicy', 'cancelPolicy', 'freeCancellationPolicy'] as $key) {
            $value = trim((string) ($option[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        if (isset($option['freeCancellationHours']) && is_numeric($option['freeCancellationHours'])) {
            return (int) $option['freeCancellationHours'] . ' saate kadar ucretsiz iptal';
        }

        return 'Iptal politikasi tedarikci kurallarina gore degisir.';
    }

    private function transferErrorResponse(RuntimeException $exception, string $fallbackMessage): JsonResponse
    {
        $message = trim($exception->getMessage());
        if ($message === '') {
            $message = $fallbackMessage;
        }

        return response()->json([
            'ok' => false,
            'message' => Str::limit($message, 240),
        ], $this->extractStatusCode($exception));
    }

    private function extractStatusCode(RuntimeException $exception): int
    {
        $message = $exception->getMessage();
        if (preg_match('/HTTP\\s+(\\d{3})/i', $message, $matches)) {
            $status = (int) ($matches[1] ?? 0);
            if ($status >= 400 && $status < 600) {
                return $status;
            }
        }

        return 502;
    }
}

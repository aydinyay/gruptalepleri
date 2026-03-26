<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\CharterBooking;
use App\Models\CharterPayment;
use App\Models\LeisureBooking;
use App\Models\LeisurePayment;
use App\Models\LeisureRequest;
use App\Models\Request as LegacyRequest;
use App\Models\RequestPayment;
use App\Services\Finance\FinanceSyncService;
use App\Services\Payments\PaynkolayGatewayService;
use App\Services\Payments\TcmbExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModulePaymentController extends Controller
{
    public function __construct(
        private readonly PaynkolayGatewayService $paynkolayGateway,
        private readonly TcmbExchangeRateService $tcmbExchangeRateService,
        private readonly FinanceSyncService $financeSyncService,
    ) {
    }

    public function startLegacy(Request $request, string $gtpnr): RedirectResponse
    {
        $roleContext = $this->resolveRoleContext($request);
        $legacyRequest = LegacyRequest::query()
            ->where('gtpnr', $gtpnr)
            ->with(['offers', 'payments'])
            ->firstOrFail();

        if ($roleContext === 'acente' && (int) $legacyRequest->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        [$remainingAmount, $currency] = $this->legacyRemainingAmount($legacyRequest);
        if ($remainingAmount <= 0.0001) {
            return back()->with('error', 'Bu talep icin kalan odeme bulunmuyor.');
        }

        $fx = $this->tcmbExchangeRateService->convertToTry($remainingAmount, $currency);
        $internalReference = $this->generateInternalReference('LEG');

        $payment = RequestPayment::query()->create([
            'request_id' => $legacyRequest->id,
            'sequence' => ((int) $legacyRequest->payments->max('sequence')) + 1,
            'payment_type' => 'full',
            'payment_method' => 'kart',
            'bank_name' => null,
            'sender_masked' => null,
            'account_masked' => null,
            'amount' => $remainingAmount,
            'currency' => $currency,
            'payment_date' => null,
            'status' => 'bekleniyor',
            'created_by' => (string) $request->user()->name,
            'gateway_provider' => 'paynkolay',
            'gateway_internal_reference' => $internalReference,
            'gateway_status' => 'pending',
            'request_payload_json' => [
                'module' => 'legacy',
                'gtpnr' => $legacyRequest->gtpnr,
                'source_amount' => $remainingAmount,
                'source_currency' => $currency,
                'charged_try_amount' => $fx['charged_try_amount'],
                'fx_rate' => $fx['fx_rate'],
                'fx_timestamp' => optional($fx['fx_timestamp'])->toIso8601String(),
                'initiator_user_id' => (int) $request->user()->id,
            ],
            'charged_try_amount' => $fx['charged_try_amount'],
            'fx_rate' => $fx['fx_rate'],
            'fx_timestamp' => $fx['fx_timestamp'],
            'source_currency' => $fx['source_currency'],
        ]);

        $this->financeSyncService->syncRequestPayment($payment->fresh(), (int) $request->user()->id);

        try {
            $initialized = $this->paynkolayGateway->initializePayment(
                clientReference: $internalReference,
                amountTry: (float) $fx['charged_try_amount'],
                successUrl: route('payment.paynkolay.success'),
                failUrl: route('payment.paynkolay.fail'),
                cardHolderIp: (string) $request->ip(),
            );

            $payment->update([
                'gateway_provider_reference' => $initialized['provider_reference'],
                'response_payload_json' => $initialized['response'],
            ]);

            return redirect()->away($initialized['redirect_url']);
        } catch (\Throwable $exception) {
            $payment->update([
                'gateway_status' => 'failed',
                'failure_reason' => Str::limit($exception->getMessage(), 350),
                'failed_at' => now(),
                'processed_at' => now(),
            ]);

            $this->financeSyncService->syncRequestPayment($payment->fresh(), (int) $request->user()->id);

            return back()->with('error', 'Odeme baslatilamadi: ' . Str::limit($exception->getMessage(), 220));
        }
    }

    public function startCharter(Request $request, CharterBooking $booking): RedirectResponse
    {
        $roleContext = $this->resolveRoleContext($request);
        $booking->loadMissing(['request.salesQuotes', 'payments']);

        if (
            $roleContext === 'acente'
            && (int) ($booking->request?->user_id ?? 0) !== (int) $request->user()->id
        ) {
            abort(403);
        }

        $remainingAmount = round((float) $booking->remaining_amount, 2);
        if ($remainingAmount <= 0.0001) {
            return back()->with('error', 'Bu charter booking icin kalan odeme bulunmuyor.');
        }

        $currency = strtoupper((string) (
            $booking->request?->salesQuotes?->sortByDesc('id')->first()?->currency
            ?? $booking->payments->sortByDesc('id')->first()?->currency
            ?? 'EUR'
        ));

        $fx = $this->tcmbExchangeRateService->convertToTry($remainingAmount, $currency);
        $internalReference = $this->generateInternalReference('CHR');

        $payment = CharterPayment::query()->create([
            'charter_booking_id' => $booking->id,
            'method' => 'card',
            'amount' => $remainingAmount,
            'currency' => $currency,
            'status' => 'pending',
            'provider' => 'paynkolay',
            'provider_reference' => null,
            'internal_reference' => $internalReference,
            'request_payload_json' => [
                'module' => 'charter',
                'booking_id' => $booking->id,
                'charter_request_id' => $booking->charter_request_id,
                'source_amount' => $remainingAmount,
                'source_currency' => $currency,
                'charged_try_amount' => $fx['charged_try_amount'],
                'fx_rate' => $fx['fx_rate'],
                'fx_timestamp' => optional($fx['fx_timestamp'])->toIso8601String(),
                'initiator_user_id' => (int) $request->user()->id,
            ],
            'charged_try_amount' => $fx['charged_try_amount'],
            'fx_rate' => $fx['fx_rate'],
            'fx_timestamp' => $fx['fx_timestamp'],
            'source_currency' => $fx['source_currency'],
        ]);

        $this->financeSyncService->syncCharterPayment($payment->fresh(), (int) $request->user()->id);

        try {
            $initialized = $this->paynkolayGateway->initializePayment(
                clientReference: $internalReference,
                amountTry: (float) $fx['charged_try_amount'],
                successUrl: route('payment.paynkolay.success'),
                failUrl: route('payment.paynkolay.fail'),
                cardHolderIp: (string) $request->ip(),
            );

            $payment->update([
                'provider_reference' => $initialized['provider_reference'],
                'response_payload_json' => $initialized['response'],
            ]);

            return redirect()->away($initialized['redirect_url']);
        } catch (\Throwable $exception) {
            $payment->update([
                'status' => 'rejected',
                'failure_reason' => Str::limit($exception->getMessage(), 350),
                'failed_at' => now(),
                'processed_at' => now(),
            ]);

            $this->financeSyncService->syncCharterPayment($payment->fresh(), (int) $request->user()->id);
            $this->recalculateCharterBooking($booking->fresh('payments', 'request'));

            return back()->with('error', 'Odeme baslatilamadi: ' . Str::limit($exception->getMessage(), 220));
        }
    }

    public function startLeisure(Request $request, LeisureBooking $booking): RedirectResponse
    {
        $roleContext = $this->resolveRoleContext($request);
        $booking->loadMissing(['request', 'payments']);

        if ($roleContext === 'acente' && (int) ($booking->request?->user_id ?? 0) !== (int) $request->user()->id) {
            abort(403);
        }

        $remainingAmount = round((float) $booking->remaining_amount, 2);
        if ($remainingAmount <= 0.0001) {
            return back()->with('error', 'Bu leisure booking icin kalan odeme bulunmuyor.');
        }

        $currency = strtoupper((string) ($booking->currency ?: 'TRY'));
        $fx = $this->tcmbExchangeRateService->convertToTry($remainingAmount, $currency);
        $internalReference = $this->generateInternalReference('LEI');
        $paymentReference = $this->generatePaymentReference('LPY');

        $payment = LeisurePayment::query()->create([
            'leisure_booking_id' => $booking->id,
            'reference' => $paymentReference,
            'method' => 'card',
            'amount' => $remainingAmount,
            'currency' => $currency,
            'status' => 'pending',
            'provider' => 'paynkolay',
            'provider_reference' => null,
            'internal_reference' => $internalReference,
            'request_payload_json' => [
                'module' => 'leisure',
                'booking_id' => $booking->id,
                'leisure_request_id' => $booking->leisure_request_id,
                'source_amount' => $remainingAmount,
                'source_currency' => $currency,
                'charged_try_amount' => $fx['charged_try_amount'],
                'fx_rate' => $fx['fx_rate'],
                'fx_timestamp' => optional($fx['fx_timestamp'])->toIso8601String(),
                'initiator_user_id' => (int) $request->user()->id,
            ],
            'charged_try_amount' => $fx['charged_try_amount'],
            'fx_rate' => $fx['fx_rate'],
            'fx_timestamp' => $fx['fx_timestamp'],
            'source_currency' => $fx['source_currency'],
            'created_by_user_id' => (int) $request->user()->id,
        ]);

        $this->financeSyncService->syncLeisurePayment($payment->fresh(), (int) $request->user()->id);

        try {
            $initialized = $this->paynkolayGateway->initializePayment(
                clientReference: $internalReference,
                amountTry: (float) $fx['charged_try_amount'],
                successUrl: route('payment.paynkolay.success'),
                failUrl: route('payment.paynkolay.fail'),
                cardHolderIp: (string) $request->ip(),
            );

            $payment->update([
                'provider_reference' => $initialized['provider_reference'],
                'response_payload_json' => $initialized['response'],
            ]);

            return redirect()->away($initialized['redirect_url']);
        } catch (\Throwable $exception) {
            $payment->update([
                'status' => 'rejected',
                'failure_reason' => Str::limit($exception->getMessage(), 350),
                'failed_at' => now(),
                'processed_at' => now(),
            ]);

            $this->financeSyncService->syncLeisurePayment($payment->fresh(), (int) $request->user()->id);
            $this->recalculateLeisureBooking($booking->fresh('payments'));

            return back()->with('error', 'Odeme baslatilamadi: ' . Str::limit($exception->getMessage(), 220));
        }
    }

    public function paynkolaySuccess(Request $request)
    {
        return $this->handlePaynkolayReturn($request, false);
    }

    public function paynkolayFail(Request $request)
    {
        return $this->handlePaynkolayReturn($request, true);
    }

    public function paynkolaySimulate(Request $request, string $reference): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $status = strtolower((string) $request->query('status', 'paid'));
        if (! in_array($status, ['paid', 'failed'], true)) {
            $status = 'paid';
        }

        $payload = [
            'clientRefCode' => $reference,
            'reference_code' => 'SIM-' . $reference,
            'response_code' => $status === 'paid' ? '2' : '0',
            'auth_code' => $status === 'paid' ? '1' : '0',
            'status' => $status,
            'payment_status' => $status,
            'message' => 'Simulation response',
        ];

        $result = $this->processPaynkolayPayload($payload);
        if (! $result) {
            abort(404);
        }

        if (! auth()->check()) {
            return redirect()->route('login')->with('success', 'Odeme sonucu kaydedildi.');
        }

        return redirect()->route(
            $this->resolveRedirectRouteName($result['module'], $result['data'], (string) auth()->user()->role),
            $this->resolveRedirectRouteParameters($result['module'], $result['data'])
        );
    }

    private function handlePaynkolayReturn(Request $request, bool $forceFailed)
    {
        $payload = $request->all();

        if (! $this->paynkolayGateway->isValidResponseHash($payload)) {
            if ($request->isMethod('post')) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Hash dogrulamasi basarisiz.',
                ], 400);
            }

            return response('Hash dogrulamasi basarisiz.', 400);
        }

        if ($forceFailed) {
            $payload['status'] = 'failed';
            $payload['payment_status'] = 'failed';
            $payload['RESPONSE_CODE'] = '0';
            $payload['response_code'] = '0';
            $payload['AUTH_CODE'] = '0';
            $payload['auth_code'] = '0';
        }

        $result = $this->processPaynkolayPayload($payload);
        if (! $result) {
            if ($request->isMethod('post')) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Islem bulunamadi.',
                ], 404);
            }

            return response('Islem bulunamadi.', 404);
        }

        if ($request->isMethod('post')) {
            return response()->json(['ok' => true]);
        }

        if (! auth()->check()) {
            return redirect()->route('login')
                ->with('success', 'Odeme sonucu alindi. Giris yaptiktan sonra ilgili kaydi gorebilirsiniz.');
        }

        return redirect()->route(
            $this->resolveRedirectRouteName($result['module'], $result['data'], (string) auth()->user()->role),
            $this->resolveRedirectRouteParameters($result['module'], $result['data'])
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{module:string,data:array<string, mixed>}|null
     */
    private function processPaynkolayPayload(array $payload): ?array
    {
        $internalReference = trim((string) (
            $payload['clientRefCode']
            ?? $payload['CLIENT_REFERENCE_CODE']
            ?? $payload['client_reference_code']
            ?? ''
        ));
        $providerReference = trim((string) (
            $payload['reference_code']
            ?? $payload['REFERENCE_CODE']
            ?? $payload['provider_reference']
            ?? ''
        ));
        $mappedStatus = $this->paynkolayGateway->mapCallbackStatus($payload);
        $failureReason = $this->extractFailureReason($payload);

        if ($internalReference === '' && $providerReference === '') {
            return null;
        }

        if (str_starts_with($internalReference, 'LEG-')) {
            $payment = $this->findLegacyPayment($internalReference, $providerReference);

            return $payment
                ? ['module' => 'legacy', 'data' => $this->processLegacyPayment($payment, $payload, $mappedStatus, $providerReference, $failureReason)]
                : null;
        }

        if (str_starts_with($internalReference, 'CHR-')) {
            $payment = $this->findCharterPayment($internalReference, $providerReference);

            return $payment
                ? ['module' => 'charter', 'data' => $this->processCharterPayment($payment, $payload, $mappedStatus, $providerReference, $failureReason)]
                : null;
        }

        if (str_starts_with($internalReference, 'LEI-')) {
            $payment = $this->findLeisurePayment($internalReference, $providerReference);

            return $payment
                ? ['module' => 'leisure', 'data' => $this->processLeisurePayment($payment, $payload, $mappedStatus, $providerReference, $failureReason)]
                : null;
        }

        $legacy = $this->findLegacyPayment($internalReference, $providerReference);
        if ($legacy) {
            return [
                'module' => 'legacy',
                'data' => $this->processLegacyPayment($legacy, $payload, $mappedStatus, $providerReference, $failureReason),
            ];
        }

        $charter = $this->findCharterPayment($internalReference, $providerReference);
        if ($charter) {
            return [
                'module' => 'charter',
                'data' => $this->processCharterPayment($charter, $payload, $mappedStatus, $providerReference, $failureReason),
            ];
        }

        $leisure = $this->findLeisurePayment($internalReference, $providerReference);
        if ($leisure) {
            return [
                'module' => 'leisure',
                'data' => $this->processLeisurePayment($leisure, $payload, $mappedStatus, $providerReference, $failureReason),
            ];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function processLegacyPayment(
        RequestPayment $payment,
        array $payload,
        string $mappedStatus,
        string $providerReference,
        ?string $failureReason
    ): array {
        return DB::transaction(function () use ($payment, $payload, $mappedStatus, $providerReference, $failureReason): array {
            $payment->refresh();

            if (
                $payment->processed_at !== null
                && in_array((string) $payment->gateway_status, ['paid', 'failed', 'refunded'], true)
            ) {
                return [
                    'request_gtpnr' => $payment->request()->value('gtpnr'),
                ];
            }

            $payment->gateway_provider_reference = $providerReference !== '' ? $providerReference : $payment->gateway_provider_reference;
            $payment->callback_payload_json = $payload;
            $payment->processed_at = now();

            if ($mappedStatus === 'paid') {
                $payment->gateway_status = 'paid';
                $payment->status = 'alindi';
                $payment->payment_date = now()->toDateString();
                $payment->paid_at = now();
                $payment->failed_at = null;
                $payment->failure_reason = null;
            } elseif ($mappedStatus === 'refunded') {
                $payment->gateway_status = 'refunded';
                $payment->status = 'iade';
                $payment->failed_at = now();
                $payment->failure_reason = $failureReason;
            } elseif ($mappedStatus === 'failed') {
                $payment->gateway_status = 'failed';
                $payment->failed_at = now();
                $payment->failure_reason = $failureReason;
            }

            $payment->save();

            $this->financeSyncService->syncRequestPayment($payment->fresh(), auth()->id());

            return [
                'request_gtpnr' => (string) $payment->request()->value('gtpnr'),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function processCharterPayment(
        CharterPayment $payment,
        array $payload,
        string $mappedStatus,
        string $providerReference,
        ?string $failureReason
    ): array {
        return DB::transaction(function () use ($payment, $payload, $mappedStatus, $providerReference, $failureReason): array {
            $payment->refresh();
            $payment->loadMissing('booking.request');

            if (
                $payment->processed_at !== null
                && in_array((string) $payment->status, ['approved', 'rejected'], true)
            ) {
                return [
                    'charter_request_id' => (int) ($payment->booking?->charter_request_id ?? 0),
                ];
            }

            $payment->provider_reference = $providerReference !== '' ? $providerReference : $payment->provider_reference;
            $payment->callback_payload_json = $payload;
            $payment->processed_at = now();

            if ($mappedStatus === 'paid') {
                $payment->status = 'approved';
                $payment->approved_at = now();
                $payment->paid_at = now();
                $payment->failed_at = null;
                $payment->failure_reason = null;
            } elseif ($mappedStatus === 'failed') {
                $payment->status = 'rejected';
                $payment->failed_at = now();
                $payment->failure_reason = $failureReason;
            }

            $payment->save();

            $this->financeSyncService->syncCharterPayment($payment->fresh(), auth()->id());
            $booking = $payment->booking?->fresh(['payments', 'request']);
            if ($booking) {
                $this->recalculateCharterBooking($booking);
            }

            return [
                'charter_request_id' => (int) ($payment->booking?->charter_request_id ?? 0),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function processLeisurePayment(
        LeisurePayment $payment,
        array $payload,
        string $mappedStatus,
        string $providerReference,
        ?string $failureReason
    ): array {
        return DB::transaction(function () use ($payment, $payload, $mappedStatus, $providerReference, $failureReason): array {
            $payment->refresh();
            $payment->loadMissing('booking.request');

            if (
                $payment->processed_at !== null
                && in_array((string) $payment->status, ['approved', 'rejected'], true)
            ) {
                return [
                    'leisure_request_gtpnr' => (string) ($payment->booking?->request?->gtpnr ?? ''),
                    'product_type' => (string) ($payment->booking?->request?->product_type ?? ''),
                ];
            }

            $payment->provider_reference = $providerReference !== '' ? $providerReference : $payment->provider_reference;
            $payment->callback_payload_json = $payload;
            $payment->processed_at = now();

            if ($mappedStatus === 'paid') {
                $payment->status = 'approved';
                $payment->paid_at = now();
                $payment->failed_at = null;
                $payment->failure_reason = null;
            } elseif ($mappedStatus === 'failed') {
                $payment->status = 'rejected';
                $payment->failed_at = now();
                $payment->failure_reason = $failureReason;
            }

            $payment->save();

            $this->financeSyncService->syncLeisurePayment($payment->fresh(), auth()->id());
            $booking = $payment->booking?->fresh(['payments', 'request']);
            if ($booking) {
                $this->recalculateLeisureBooking($booking);
            }

            return [
                'leisure_request_gtpnr' => (string) ($payment->booking?->request?->gtpnr ?? ''),
                'product_type' => (string) ($payment->booking?->request?->product_type ?? ''),
            ];
        });
    }

    private function recalculateCharterBooking(CharterBooking $booking): void
    {
        $approvedTotal = round((float) $booking->payments()->where('status', 'approved')->sum('amount'), 2);
        $totalAmount = round((float) $booking->total_amount, 2);
        $remaining = max(0, round($totalAmount - $approvedTotal, 2));

        $bookingStatus = 'pending_payment';
        if ($approvedTotal > 0 && $remaining > 0.0001) {
            $bookingStatus = 'partial_paid';
        } elseif ($remaining <= 0.0001) {
            $bookingStatus = 'paid';
        }

        $booking->update([
            'status' => $bookingStatus,
            'total_paid' => $approvedTotal,
            'remaining_amount' => $remaining,
        ]);

        $requestStatus = match ($bookingStatus) {
            'paid' => \App\Models\CharterRequest::STATUS_PAID,
            'partial_paid' => \App\Models\CharterRequest::STATUS_PARTIAL_PAID,
            default => \App\Models\CharterRequest::STATUS_PENDING_PAYMENT,
        };

        if ($booking->request) {
            $booking->request->update(['status' => $requestStatus]);
        }
    }

    private function recalculateLeisureBooking(LeisureBooking $booking): void
    {
        $approvedTotal = round((float) $booking->payments()->where('status', 'approved')->sum('amount'), 2);
        $totalAmount = round((float) $booking->total_amount, 2);
        $remaining = max(0, round($totalAmount - $approvedTotal, 2));

        $bookingStatus = 'pending_payment';
        if ($approvedTotal > 0 && $remaining > 0.0001) {
            $bookingStatus = 'partial_paid';
        } elseif ($remaining <= 0.0001) {
            $bookingStatus = 'paid';
        }

        $booking->update([
            'status' => $bookingStatus,
            'total_paid' => $approvedTotal,
            'remaining_amount' => $remaining,
        ]);

        if ($booking->request && $bookingStatus === 'paid' && $booking->request->status === LeisureRequest::STATUS_APPROVED) {
            $booking->request->update([
                'status' => LeisureRequest::STATUS_APPROVED,
            ]);
        }
    }

    private function resolveRoleContext(Request $request): string
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        if (str_starts_with($routeName, 'superadmin.')) {
            return 'superadmin';
        }

        if (str_starts_with($routeName, 'admin.')) {
            return 'admin';
        }

        return 'acente';
    }

    /**
     * @return array{0:float,1:string}
     */
    private function legacyRemainingAmount(LegacyRequest $request): array
    {
        $selectedOffer = $request->offers->firstWhere('is_accepted', true)
            ?: $request->offers->sortByDesc('id')->first();
        $total = (float) ($selectedOffer?->total_price ?? 0);
        $currency = strtoupper((string) ($selectedOffer?->currency ?: 'TRY'));
        $paid = (float) $request->payments()->where('status', 'alindi')->sum('amount');

        return [max(0, round($total - $paid, 2)), $currency];
    }

    private function findLegacyPayment(string $internalReference, string $providerReference): ?RequestPayment
    {
        $query = RequestPayment::query();

        if ($internalReference !== '') {
            $query->where('gateway_internal_reference', $internalReference);
        } elseif ($providerReference !== '') {
            $query->where('gateway_provider_reference', $providerReference);
        } else {
            return null;
        }

        return $query->first();
    }

    private function findCharterPayment(string $internalReference, string $providerReference): ?CharterPayment
    {
        $query = CharterPayment::query();

        if ($internalReference !== '') {
            $query->where('internal_reference', $internalReference);
        } elseif ($providerReference !== '') {
            $query->where('provider_reference', $providerReference);
        } else {
            return null;
        }

        return $query->first();
    }

    private function findLeisurePayment(string $internalReference, string $providerReference): ?LeisurePayment
    {
        $query = LeisurePayment::query();

        if ($internalReference !== '') {
            $query->where('internal_reference', $internalReference);
        } elseif ($providerReference !== '') {
            $query->where('provider_reference', $providerReference);
        } else {
            return null;
        }

        return $query->first();
    }

    private function resolveRedirectRouteName(string $module, array $data, string $role): string
    {
        $normalizedRole = in_array($role, ['acente', 'admin', 'superadmin'], true) ? $role : 'acente';

        if ($module === 'legacy') {
            return $normalizedRole === 'acente'
                ? 'acente.requests.show'
                : 'admin.requests.show';
        }

        if ($module === 'charter') {
            return $normalizedRole . '.charter.show';
        }

        if ($module === 'leisure') {
            return ($data['product_type'] ?? '') === LeisureRequest::PRODUCT_DINNER_CRUISE
                ? $normalizedRole . '.dinner-cruise.show'
                : $normalizedRole . '.yacht-charter.show';
        }

        return 'dashboard';
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRedirectRouteParameters(string $module, array $data): array
    {
        if ($module === 'legacy') {
            return ['gtpnr' => $data['request_gtpnr'] ?? ''];
        }

        if ($module === 'charter') {
            return ['charterRequest' => (int) ($data['charter_request_id'] ?? 0)];
        }

        if ($module === 'leisure') {
            return ['leisureRequest' => $data['leisure_request_gtpnr'] ?? ''];
        }

        return [];
    }

    private function extractFailureReason(array $payload): ?string
    {
        $reason = trim((string) (
            $payload['RESPONSE_DATA']
            ?? $payload['response_data']
            ?? $payload['message']
            ?? ''
        ));

        return $reason !== '' ? Str::limit($reason, 350) : null;
    }

    private function generateInternalReference(string $prefix): string
    {
        return strtoupper($prefix) . '-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(8));
    }

    private function generatePaymentReference(string $prefix): string
    {
        return strtoupper($prefix) . '-' . strtoupper(Str::random(12));
    }
}

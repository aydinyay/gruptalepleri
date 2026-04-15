<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Models\TransferBooking;
use App\Models\TransferPaymentTransaction;
use App\Models\TransferQuoteLock;
use App\Models\TransferSettlementEntry;
use App\Models\TransferSupplier;
use App\Services\Finance\FinanceSyncService;
use App\Services\Transfer\InternalTransferMarketplaceService;
use App\Services\Transfer\PaynkolayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransferCheckoutController extends Controller
{
    public function __construct(
        private readonly InternalTransferMarketplaceService $internalService,
        private readonly PaynkolayService $paynkolayService,
        private readonly FinanceSyncService $financeSyncService,
    ) {
    }

    public function show(Request $request, string $quoteToken)
    {
        $this->abortIfExternalProvider();

        $quote = $this->internalService->findValidQuote($quoteToken);
        if (! $quote) {
            $roleContext = $this->resolveRoleContext($request);

            return redirect()
                ->route($roleContext . '.transfer.index')
                ->with('error', 'Teklif suresi doldu veya kullanildi. Lutfen yeniden transfer aramasi yapin.');
        }

        $roleContext = $this->resolveRoleContext($request);

        return view('transfer.checkout', [
            'quote' => $quote,
            'roleContext' => $roleContext,
            'navbarComponent' => $this->navbarComponent($roleContext),
            'bookEndpoint' => route($roleContext . '.transfer.checkout.book', ['quoteToken' => $quoteToken]),
            'searchRoute' => route($roleContext . '.transfer.index'),
            'ttlSeconds' => max(0, now()->diffInSeconds($quote->expires_at, false)),
        ]);
    }

    public function book(Request $request, string $quoteToken): RedirectResponse
    {
        $this->abortIfExternalProvider();

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'passenger_names' => ['nullable', 'string', 'max:2000'],
            'flight_number' => ['nullable', 'string', 'max:40'],
            'terminal' => ['nullable', 'string', 'max:40'],
            'pickup_sign_name' => ['nullable', 'string', 'max:120'],
            'exact_pickup_address' => ['nullable', 'string', 'max:500'],
            'luggage_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'child_seat_count' => ['nullable', 'integer', 'min:0', 'max:10'],
        ]);

        /** @var TransferBooking $booking */
        /** @var TransferPaymentTransaction $payment */
        [$booking, $payment] = DB::transaction(function () use ($request, $quoteToken, $validated): array {
            $quote = TransferQuoteLock::query()
                ->where('token', $quoteToken)
                ->lockForUpdate()
                ->first();

            if (! $quote || $quote->consumed_at !== null || $quote->isExpired()) {
                throw ValidationException::withMessages([
                    'quote' => 'Teklif suresi doldugu icin rezervasyon olusturulamadi. Lutfen yeniden arama yapin.',
                ]);
            }

            $booking = TransferBooking::query()->create([
                'booking_ref' => $this->generateBookingRef(),
                'quote_lock_id' => $quote->id,
                'supplier_id' => $quote->supplier_id,
                'agency_user_id' => (int) $request->user()->id,
                'created_by_user_id' => (int) $request->user()->id,
                'airport_id' => $quote->airport_id,
                'zone_id' => $quote->zone_id,
                'vehicle_type_id' => $quote->vehicle_type_id,
                'direction' => $quote->direction,
                'pax' => $quote->pax,
                'pickup_at' => $quote->pickup_at,
                'return_at' => $quote->return_at,
                'status' => TransferBooking::STATUS_PAYMENT_PENDING,
                'currency' => $quote->currency,
                'subtotal_amount' => $quote->subtotal_amount,
                'commission_amount' => $quote->commission_amount,
                'total_amount' => $quote->total_amount,
                'price_snapshot_json' => [
                    'quote_token' => $quote->token,
                    'price_breakdown' => $quote->price_breakdown_json,
                    'snapshot' => $quote->snapshot_json,
                    'contact' => [
                        'name' => $validated['contact_name'],
                        'phone' => $validated['contact_phone'],
                    ],
                    'operation_details' => [
                        'passenger_names' => trim((string) ($validated['passenger_names'] ?? '')) ?: null,
                        'flight_number' => trim((string) ($validated['flight_number'] ?? '')) ?: null,
                        'terminal' => trim((string) ($validated['terminal'] ?? '')) ?: null,
                        'pickup_sign_name' => trim((string) ($validated['pickup_sign_name'] ?? '')) ?: null,
                        'exact_pickup_address' => trim((string) ($validated['exact_pickup_address'] ?? '')) ?: null,
                        'luggage_count' => array_key_exists('luggage_count', $validated) ? (int) $validated['luggage_count'] : null,
                        'child_seat_count' => array_key_exists('child_seat_count', $validated) ? (int) $validated['child_seat_count'] : null,
                    ],
                ],
                'supplier_policy_snapshot_json' => data_get($quote->snapshot_json, 'policy'),
                'notes' => trim((string) ($validated['notes'] ?? '')),
            ]);

            $payment = TransferPaymentTransaction::query()->create([
                'transfer_booking_id' => $booking->id,
                'reference' => $this->generatePaymentReference(),
                'provider' => 'paynkolay',
                'status' => 'pending',
                'amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'request_payload_json' => [
                    'contact_name' => $validated['contact_name'],
                    'contact_phone' => $validated['contact_phone'],
                ],
            ]);

            $quote->update([
                'consumed_at' => now(),
            ]);

            return [$booking, $payment];
        });

        $successUrl = route('transfer.payment.paynkolay.success');
        $failUrl = route('transfer.payment.paynkolay.fail');

        try {
            $paymentInit = $this->paynkolayService->initializePayment(
                booking: $booking,
                transaction: $payment,
                successUrl: $successUrl,
                failUrl: $failUrl,
                cardHolderIp: (string) $request->ip(),
            );

            $payment->update([
                'provider_transaction_id' => $paymentInit['provider_transaction_id'],
                'response_payload_json' => $paymentInit['response'],
            ]);

            return redirect()->away($paymentInit['redirect_url']);
        } catch (\Throwable $exception) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => Str::limit($exception->getMessage(), 240),
                'failed_at' => now(),
                'processed_at' => now(),
            ]);

            $booking->update([
                'status' => TransferBooking::STATUS_FAILED,
                'failed_at' => now(),
            ]);

            // Payment init basarisizsa tokeni tekrar kullanilabilir yapalim.
            $quote = TransferQuoteLock::query()->find($booking->quote_lock_id);
            if ($quote && ! $quote->isExpired()) {
                $quote->update([
                    'consumed_at' => null,
                ]);
            }

            return back()->withErrors([
                'payment' => 'Odeme baslatilamadi: ' . Str::limit($exception->getMessage(), 180),
            ]);
        }
    }

    public function paynkolaySuccess(Request $request)
    {
        return $this->handlePaynkolayReturn($request, forceFailed: false);
    }

    public function paynkolayFail(Request $request)
    {
        return $this->handlePaynkolayReturn($request, forceFailed: true);
    }

    public function showBooking(Request $request, TransferBooking $booking)
    {
        $this->abortIfExternalProvider();

        $booking->loadMissing(['supplier', 'airport', 'zone', 'vehicleType', 'paymentTransactions']);

        $roleContext = $this->resolveRoleContext($request);
        $this->authorizeBookingAccess($request, $booking, $roleContext);
        $supplierContext = $this->isSupplierContext($request);

        $canCancel = ! $supplierContext
            && in_array($roleContext, ['acente', 'admin', 'superadmin'], true)
            && in_array($booking->status, [
                TransferBooking::STATUS_PAYMENT_PENDING,
                TransferBooking::STATUS_CONFIRMED,
            ], true);

        $searchRoute = $supplierContext
            ? route('acente.transfer.supplier.index')
            : route($roleContext . '.transfer.index');

        return view('transfer.booking', [
            'booking' => $booking,
            'roleContext' => $roleContext,
            'navbarComponent' => $this->navbarComponent($roleContext),
            'searchRoute' => $searchRoute,
            'canCancel' => $canCancel,
            'cancelEndpoint' => $canCancel
                ? route($roleContext . '.transfer.booking.cancel', ['booking' => $booking->id])
                : null,
            'rezervasyonlarimRoute' => $roleContext === 'acente'
                ? route('acente.rezervasyonlarim.index')
                : null,
        ]);
    }

    public function cancelBooking(Request $request, TransferBooking $booking): RedirectResponse
    {
        $this->abortIfExternalProvider();

        $roleContext = $this->resolveRoleContext($request);
        $this->authorizeBookingAccess($request, $booking, $roleContext);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:400'],
        ]);

        if (! in_array($booking->status, [TransferBooking::STATUS_PAYMENT_PENDING, TransferBooking::STATUS_CONFIRMED], true)) {
            return back()->withErrors(['booking' => 'Bu rezervasyon bu durumda iptal edilemez.']);
        }

        $refundInfo = $this->calculateRefund($booking);

        $booking->update([
            'status' => $refundInfo['status'],
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $request->user()->id,
            'cancellation_reason' => trim((string) ($validated['reason'] ?? '')),
            'refundable_amount' => $refundInfo['refundable_amount'],
        ]);

        $payment = $booking->paymentTransactions()->latest('id')->first();
        if ($payment) {
            $payment->update([
                'status' => $refundInfo['status'] === TransferBooking::STATUS_REFUNDED ? 'refunded' : 'failed',
                'refunded_at' => $refundInfo['status'] === TransferBooking::STATUS_REFUNDED ? now() : null,
                'failed_at' => $refundInfo['status'] !== TransferBooking::STATUS_REFUNDED ? now() : null,
                'processed_at' => now(),
                'failure_reason' => $refundInfo['status'] !== TransferBooking::STATUS_REFUNDED
                    ? 'Rezervasyon iptal edildi.'
                    : null,
            ]);
        }

        TransferSettlementEntry::query()
            ->where('transfer_booking_id', $booking->id)
            ->update([
                'status' => 'cancelled',
                'notes' => 'Booking cancelled by ' . $request->user()->id,
            ]);

        $this->financeSyncService->syncTransferBooking($booking->fresh('paymentTransactions'), $request->user()->id);

        return back()->with('success', 'Transfer rezervasyonu iptal edildi.');
    }

    public function paymentStatus(Request $request, TransferBooking $booking): JsonResponse
    {
        $this->abortIfExternalProvider();

        $roleContext = $this->resolveRoleContext($request);
        $this->authorizeBookingAccess($request, $booking, $roleContext);

        $transaction = $booking->paymentTransactions()->latest('id')->first();

        return response()->json([
            'ok' => true,
            'data' => [
                'booking_status' => $booking->status,
                'payment_status' => $transaction?->status,
                'booking_ref' => $booking->booking_ref,
            ],
        ]);
    }

    public function paymentCallback(Request $request)
    {
        return $this->handlePaynkolayReturn($request, forceFailed: false);
    }

    public function paymentSimulate(Request $request, string $reference): RedirectResponse
    {
        $this->abortIfExternalProvider();

        $status = strtolower(trim((string) $request->query('status', 'paid')));
        if (! in_array($status, ['paid', 'failed'], true)) {
            $status = 'paid';
        }

        $transaction = $this->processPaymentPayload([
            'reference' => $reference,
            'status' => $status,
            'payment_status' => $status,
            'provider' => 'simulation',
        ]);

        if (! $transaction) {
            abort(404);
        }

        $roleContext = $this->resolveRoleContext($request);

        return redirect()->route($roleContext . '.transfer.booking.show', ['booking' => $transaction->transfer_booking_id]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function processPaymentPayload(array $payload): ?TransferPaymentTransaction
    {
        $reference = trim((string) (
            $payload['reference']
            ?? $payload['merchant_reference']
            ?? $payload['client_reference_code']
            ?? $payload['CLIENT_REFERENCE_CODE']
            ?? $payload['clientRefCode']
            ?? ''
        ));
        $providerTransactionId = trim((string) (
            $payload['transaction_id']
            ?? $payload['provider_transaction_id']
            ?? $payload['reference_code']
            ?? $payload['REFERENCE_CODE']
            ?? ''
        ));

        $transactionQuery = TransferPaymentTransaction::query()->with('booking');

        if ($reference !== '') {
            $transactionQuery->where('reference', $reference);
        } elseif ($providerTransactionId !== '') {
            $transactionQuery->where('provider_transaction_id', $providerTransactionId);
        } else {
            return null;
        }

        $transaction = $transactionQuery->first();
        if (! $transaction || ! $transaction->booking) {
            return null;
        }

        $mappedStatus = $this->paynkolayService->mapCallbackStatus($payload);

        if ($transaction->processed_at !== null && $transaction->status === $mappedStatus) {
            return $transaction;
        }

        $transaction->status = $mappedStatus;
        $transaction->provider_transaction_id = $providerTransactionId !== ''
            ? $providerTransactionId
            : $transaction->provider_transaction_id;
        $transaction->callback_payload_json = $payload;
        $transaction->processed_at = now();
        $transaction->paid_at = $mappedStatus === 'paid' ? now() : $transaction->paid_at;
        $transaction->failed_at = $mappedStatus === 'failed' ? now() : $transaction->failed_at;
        $transaction->refunded_at = $mappedStatus === 'refunded' ? now() : $transaction->refunded_at;
        $transaction->save();

        $booking = $transaction->booking;

        if ($mappedStatus === 'paid') {
            $booking->status = TransferBooking::STATUS_CONFIRMED;
            $booking->confirmed_at = now();
            $booking->failed_at = null;
            $booking->save();

            TransferSettlementEntry::query()->updateOrCreate(
                ['transfer_booking_id' => $booking->id],
                [
                    'supplier_id' => $booking->supplier_id,
                    'status' => 'pending',
                    'gross_amount' => $booking->total_amount,
                    'commission_amount' => $booking->commission_amount,
                    'net_amount' => round((float) $booking->total_amount - (float) $booking->commission_amount, 2),
                    'currency' => $booking->currency,
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );
        } elseif ($mappedStatus === 'failed') {
            $booking->status = TransferBooking::STATUS_FAILED;
            $booking->failed_at = now();
            $booking->save();
        } elseif ($mappedStatus === 'refunded') {
            $booking->status = TransferBooking::STATUS_REFUNDED;
            $booking->cancelled_at = $booking->cancelled_at ?: now();
            $booking->save();

            TransferSettlementEntry::query()
                ->where('transfer_booking_id', $booking->id)
                ->update([
                    'status' => 'cancelled',
                    'notes' => 'Payment refunded.',
                ]);
        }

        $this->financeSyncService->syncTransferBooking($booking->fresh('paymentTransactions'), null);

        return $transaction;
    }

    private function handlePaynkolayReturn(Request $request, bool $forceFailed)
    {
        $this->abortIfExternalProvider();

        $payload = $request->all();

        if (! $this->paynkolayService->isValidResponseHash($payload)) {
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

        $transaction = $this->processPaymentPayload($payload);
        if (! $transaction) {
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
                ->with('success', 'Odeme sonucu alindi. Giris yaptiktan sonra transfer detayini gorebilirsiniz.');
        }

        $roleContext = $this->resolveRoleContextFromUser($request);

        return redirect()->route($roleContext . '.transfer.booking.show', ['booking' => $transaction->transfer_booking_id]);
    }

    /**
     * @return array{status:string,refundable_amount:float}
     */
    private function calculateRefund(TransferBooking $booking): array
    {
        $policy = is_array($booking->supplier_policy_snapshot_json)
            ? $booking->supplier_policy_snapshot_json
            : [];

        $freeCancelBeforeMinutes = max(0, (int) ($policy['free_cancel_before_minutes'] ?? 0));
        $refundAfterDeadline = max(0, min(100, (float) ($policy['refund_percent_after_deadline'] ?? 0)));
        $noShowRefund = max(0, min(100, (float) ($policy['no_show_refund_percent'] ?? 0)));

        $minutesUntilPickup = now()->diffInMinutes($booking->pickup_at, false);

        if ($minutesUntilPickup < 0) {
            $refundRate = $noShowRefund;
        } elseif ($minutesUntilPickup >= $freeCancelBeforeMinutes) {
            $refundRate = 100;
        } else {
            $refundRate = $refundAfterDeadline;
        }

        $refundableAmount = round(((float) $booking->total_amount) * ($refundRate / 100), 2);
        $status = $refundableAmount > 0
            ? TransferBooking::STATUS_REFUNDED
            : TransferBooking::STATUS_CANCELLED;

        return [
            'status' => $status,
            'refundable_amount' => $refundableAmount,
        ];
    }

    private function authorizeBookingAccess(Request $request, TransferBooking $booking, string $roleContext): void
    {
        if ($this->isSupplierContext($request)) {
            $supplierFromMiddleware = $request->attributes->get('transfer_supplier');
            if ($supplierFromMiddleware instanceof TransferSupplier) {
                $supplierId = (int) $supplierFromMiddleware->id;
            } else {
                if (! Schema::hasTable('transfer_suppliers')) {
                    abort(403);
                }

                $supplierId = (int) TransferSupplier::query()
                    ->where('user_id', $request->user()->id)
                    ->where('is_approved', true)
                    ->value('id');
            }

            if (! $supplierId || (int) $booking->supplier_id !== (int) $supplierId) {
                abort(403);
            }

            return;
        }

        if ($roleContext === 'acente' && (int) $booking->agency_user_id !== (int) $request->user()->id) {
            abort(403);
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

    private function resolveRoleContextFromUser(Request $request): string
    {
        $role = (string) ($request->user()?->role ?? '');

        if ($role === 'superadmin') {
            return 'superadmin';
        }

        if ($role === 'admin') {
            return 'admin';
        }

        return 'acente';
    }

    private function navbarComponent(string $roleContext): string
    {
        return match ($roleContext) {
            'superadmin' => 'navbar-superadmin',
            'admin' => 'navbar-admin',
            default => 'navbar-acente',
        };
    }

    private function isSupplierContext(Request $request): bool
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        return str_starts_with($routeName, 'acente.transfer.supplier.');
    }

    private function abortIfExternalProvider(): void
    {
        $provider = strtolower(trim((string) config('transfer.provider', 'internal')));

        abort_unless($provider === 'internal', 404);
    }

    private function generateBookingRef(): string
    {
        do {
            $ref = 'TRF' . now()->format('ymd') . strtoupper(Str::random(5));
        } while (TransferBooking::query()->where('booking_ref', $ref)->exists());

        return $ref;
    }

    private function generatePaymentReference(): string
    {
        do {
            $reference = 'TPY-' . strtoupper(Str::random(12));
        } while (TransferPaymentTransaction::query()->where('reference', $reference)->exists());

        return $reference;
    }
}

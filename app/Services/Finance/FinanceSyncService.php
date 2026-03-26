<?php

namespace App\Services\Finance;

use App\Models\Agency;
use App\Models\CharterBooking;
use App\Models\CharterPayment;
use App\Models\LeisureBooking;
use App\Models\LeisurePayment;
use App\Models\TransferBooking;
use App\Models\FinanceAllocation;
use App\Models\FinanceAuditLog;
use App\Models\FinanceRecord;
use App\Models\FinanceTransaction;
use App\Models\Request as LegacyRequest;
use App\Models\RequestPayment;
use App\Services\Finance\FinancePaymentPlanService;
use Illuminate\Support\Facades\Schema;

class FinanceSyncService
{
    private const REALIZED_STATUSES = ['approved', 'refunded'];

    public function syncRequestPayment(RequestPayment $payment, ?int $actorUserId = null): void
    {
        if (!$this->isCoreReady()) {
            return;
        }

        $request = $payment->request()->with(['user', 'offers'])->first();
        if (!$request) {
            return;
        }

        $record = $this->findOrCreateRecordForRequest($request, (string) $payment->currency, $actorUserId);

        [$status, $direction] = $this->mapLegacyRequestPaymentStatus((string) $payment->status);
        $grossAmount = round((float) $payment->amount, 2);
        $sourceKey = 'request_payment:' . $payment->id;

        $transaction = FinanceTransaction::query()->updateOrCreate(
            ['source_key' => $sourceKey],
            [
                'finance_record_id' => $record->id,
                'source_type' => 'request_payment',
                'source_id' => $payment->id,
                'payer_user_id' => $request->user_id,
                'method' => $this->mapLegacyPaymentMethod((string) $payment->payment_method),
                'direction' => $direction,
                'gross_amount' => $grossAmount,
                'fee_amount' => 0,
                'commission_amount' => 0,
                'net_amount' => $grossAmount,
                'currency' => strtoupper((string) ($payment->currency ?: $record->currency)),
                'status' => $status,
                'payment_date' => $payment->payment_date,
                'provider' => 'legacy_request',
                'provider_reference' => null,
                'bank_name' => $payment->bank_name,
                'sender_name' => $payment->sender_masked,
                'sender_reference' => $payment->account_masked,
                'receipt_path' => null,
                'notes' => $payment->payment_type ?: null,
                'meta' => [
                    'sequence' => $payment->sequence,
                    'payment_type' => $payment->payment_type,
                    'created_by_legacy' => $payment->created_by,
                    'request_gtpnr' => $request->gtpnr,
                ],
                'created_by_user_id' => $actorUserId,
                'approved_by_user_id' => $actorUserId,
                'approved_at' => in_array($status, self::REALIZED_STATUSES, true) ? now() : null,
            ]
        );

        $this->syncAllocation($record, $transaction);
        $this->recalculateRecord($record);
    }

    public function syncCharterPayment(CharterPayment $payment, ?int $actorUserId = null): void
    {
        if (!$this->isCoreReady()) {
            return;
        }

        $booking = $payment->booking()->with(['request.user', 'request.salesQuotes'])->first();
        if (!$booking) {
            return;
        }

        $currency = (string) ($payment->currency ?: 'EUR');
        $record = $this->findOrCreateRecordForCharterBooking($booking, $currency, $actorUserId);

        [$status, $direction] = $this->mapCharterPaymentStatus((string) $payment->status);
        $grossAmount = round((float) $payment->amount, 2);
        $sourceKey = 'charter_payment:' . $payment->id;

        $transaction = FinanceTransaction::query()->updateOrCreate(
            ['source_key' => $sourceKey],
            [
                'finance_record_id' => $record->id,
                'source_type' => 'charter_payment',
                'source_id' => $payment->id,
                'payer_user_id' => $booking->request?->user_id,
                'method' => $this->mapCharterPaymentMethod((string) $payment->method),
                'direction' => $direction,
                'gross_amount' => $grossAmount,
                'fee_amount' => 0,
                'commission_amount' => 0,
                'net_amount' => $grossAmount,
                'currency' => strtoupper($currency),
                'status' => $status,
                'payment_date' => $payment->created_at?->toDateString(),
                'provider' => $payment->provider ?: 'charter_manual',
                'provider_reference' => $payment->provider_reference,
                'bank_name' => null,
                'sender_name' => null,
                'sender_reference' => null,
                'receipt_path' => $payment->receipt_path,
                'notes' => $payment->admin_note,
                'meta' => [
                    'charter_request_id' => $booking->charter_request_id,
                    'booking_id' => $booking->id,
                ],
                'created_by_user_id' => $actorUserId,
                'approved_by_user_id' => $payment->approved_by_user_id ?: $actorUserId,
                'approved_at' => $payment->approved_at,
            ]
        );

        $this->syncAllocation($record, $transaction);
        $this->recalculateRecord($record);
    }

    public function syncLeisureBooking(LeisureBooking $booking, ?int $actorUserId = null): void
    {
        if (!$this->isCoreReady()) {
            return;
        }

        $booking->loadMissing(['request', 'clientOffer']);
        $request = $booking->request;
        if (!$request) {
            return;
        }

        $currency = (string) ($booking->currency ?: $booking->clientOffer?->currency ?: 'EUR');
        $grossAmount = (float) $booking->total_amount;

        /** @var FinanceRecord $record */
        $record = FinanceRecord::query()->firstOrCreate(
            [
                'service_type' => 'leisure_booking',
                'service_id' => $booking->id,
            ],
            [
                'scope_type' => 'service',
                'agency_user_id' => $request->user_id,
                'agency_id' => $this->agencyIdForUser($request->user_id),
                'document_type' => $request->product_type,
                'document_ref' => $request->gtpnr,
                'title' => $request->productLabel() . ' #' . $request->gtpnr,
                'currency' => strtoupper($currency ?: 'EUR'),
                'gross_amount' => $grossAmount,
                'paid_amount' => (float) $booking->total_paid,
                'remaining_amount' => (float) $booking->remaining_amount,
                'due_date' => null,
                'status' => $grossAmount > 0 ? 'open' : 'draft',
                'notes' => null,
                'meta' => ['source' => 'leisure_booking', 'product_type' => $request->product_type],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]
        );

        $record->fill([
            'agency_user_id' => $request->user_id,
            'agency_id' => $this->agencyIdForUser($request->user_id),
            'document_type' => $request->product_type,
            'document_ref' => $request->gtpnr,
            'title' => $request->productLabel() . ' #' . $request->gtpnr,
            'currency' => strtoupper($currency ?: $record->currency),
            'gross_amount' => $grossAmount,
            'paid_amount' => (float) $booking->total_paid,
            'remaining_amount' => (float) $booking->remaining_amount,
            'status' => ((float) $booking->remaining_amount) <= 0.0001
                ? 'paid'
                : (((float) $booking->total_paid) > 0 ? 'partial' : 'open'),
            'updated_by_user_id' => $actorUserId,
            'meta' => [
                'source' => 'leisure_booking',
                'product_type' => $request->product_type,
                'client_offer_id' => $booking->client_offer_id,
            ],
        ]);
        $record->save();

        if (Schema::hasTable('finance_payment_plans')) {
            app(FinancePaymentPlanService::class)->syncForRecord($record->fresh());
        }
    }

    public function syncLeisurePayment(LeisurePayment $payment, ?int $actorUserId = null): void
    {
        if (! $this->isCoreReady()) {
            return;
        }

        $booking = $payment->booking()->with(['request', 'clientOffer'])->first();
        if (! $booking || ! $booking->request) {
            return;
        }

        $this->syncLeisureBooking($booking, $actorUserId);

        $record = FinanceRecord::query()
            ->where('service_type', 'leisure_booking')
            ->where('service_id', $booking->id)
            ->first();
        if (! $record) {
            return;
        }

        [$status, $direction] = $this->mapLeisurePaymentStatus((string) $payment->status);
        $grossAmount = round((float) $payment->amount, 2);
        $sourceKey = 'leisure_payment:' . $payment->id;

        $transaction = FinanceTransaction::query()->updateOrCreate(
            ['source_key' => $sourceKey],
            [
                'finance_record_id' => $record->id,
                'source_type' => 'leisure_payment',
                'source_id' => $payment->id,
                'payer_user_id' => $booking->request->user_id,
                'method' => $this->mapCharterPaymentMethod((string) $payment->method),
                'direction' => $direction,
                'gross_amount' => $grossAmount,
                'fee_amount' => 0,
                'commission_amount' => 0,
                'net_amount' => $grossAmount,
                'currency' => strtoupper((string) ($payment->currency ?: $record->currency)),
                'status' => $status,
                'payment_date' => optional($payment->paid_at ?: $payment->created_at)->toDateString(),
                'provider' => $payment->provider ?: 'paynkolay',
                'provider_reference' => $payment->provider_reference,
                'bank_name' => null,
                'sender_name' => null,
                'sender_reference' => null,
                'receipt_path' => null,
                'notes' => $payment->failure_reason,
                'meta' => [
                    'leisure_booking_id' => $booking->id,
                    'leisure_request_id' => $booking->leisure_request_id,
                    'payment_reference' => $payment->reference,
                    'charged_try_amount' => $payment->charged_try_amount,
                    'fx_rate' => $payment->fx_rate,
                    'fx_timestamp' => optional($payment->fx_timestamp)->toIso8601String(),
                    'source_currency' => $payment->source_currency,
                ],
                'created_by_user_id' => $payment->created_by_user_id ?: $actorUserId,
                'approved_by_user_id' => $status === 'approved' ? ($actorUserId ?: $payment->created_by_user_id) : null,
                'approved_at' => $status === 'approved' ? ($payment->paid_at ?: now()) : null,
            ]
        );

        $this->syncAllocation($record, $transaction);
        $this->recalculateRecord($record);
    }

    public function syncTransferBooking(TransferBooking $booking, ?int $actorUserId = null): void
    {
        if (! $this->isCoreReady()) {
            return;
        }

        $booking->loadMissing(['agencyUser', 'paymentTransactions']);

        $currency = strtoupper((string) ($booking->currency ?: 'TRY'));
        $grossAmount = (float) $booking->total_amount;

        /** @var FinanceRecord $record */
        $record = FinanceRecord::query()->firstOrCreate(
            [
                'service_type' => 'transfer_booking',
                'service_id' => $booking->id,
            ],
            [
                'scope_type' => 'service',
                'agency_user_id' => $booking->agency_user_id,
                'agency_id' => $this->agencyIdForUser($booking->agency_user_id),
                'document_type' => 'transfer',
                'document_ref' => $booking->booking_ref,
                'title' => 'Transfer Rezervasyon #' . $booking->booking_ref,
                'currency' => $currency,
                'gross_amount' => $grossAmount,
                'paid_amount' => 0,
                'remaining_amount' => $grossAmount,
                'due_date' => null,
                'status' => $grossAmount > 0 ? 'open' : 'draft',
                'meta' => ['source' => 'transfer_booking'],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]
        );

        $record->fill([
            'agency_user_id' => $booking->agency_user_id,
            'agency_id' => $this->agencyIdForUser($booking->agency_user_id),
            'document_ref' => $booking->booking_ref,
            'title' => 'Transfer Rezervasyon #' . $booking->booking_ref,
            'currency' => $currency,
            'gross_amount' => $grossAmount,
            'updated_by_user_id' => $actorUserId,
            'meta' => [
                'source' => 'transfer_booking',
                'supplier_id' => $booking->supplier_id,
                'booking_status' => $booking->status,
            ],
        ]);
        $record->save();

        $latestPayment = $booking->paymentTransactions->sortByDesc('id')->first();
        if ($latestPayment) {
            [$transactionStatus, $direction] = $this->mapTransferPaymentStatus((string) $latestPayment->status);
            $amount = round((float) $latestPayment->amount, 2);

            $transaction = FinanceTransaction::query()->updateOrCreate(
                ['source_key' => 'transfer_payment:' . $latestPayment->id],
                [
                    'finance_record_id' => $record->id,
                    'source_type' => 'transfer_payment',
                    'source_id' => $latestPayment->id,
                    'payer_user_id' => $booking->agency_user_id,
                    'method' => 'card',
                    'direction' => $direction,
                    'gross_amount' => $amount,
                    'fee_amount' => 0,
                    'commission_amount' => 0,
                    'net_amount' => $amount,
                    'currency' => strtoupper((string) ($latestPayment->currency ?: $currency)),
                    'status' => $transactionStatus,
                    'payment_date' => optional($latestPayment->paid_at ?: $latestPayment->created_at)->toDateString(),
                    'provider' => $latestPayment->provider ?: 'paynkolay',
                    'provider_reference' => $latestPayment->provider_transaction_id,
                    'bank_name' => null,
                    'sender_name' => null,
                    'sender_reference' => null,
                    'receipt_path' => null,
                    'notes' => $latestPayment->failure_reason,
                    'meta' => [
                        'transfer_booking_id' => $booking->id,
                        'booking_ref' => $booking->booking_ref,
                    ],
                    'created_by_user_id' => $actorUserId,
                    'approved_by_user_id' => $transactionStatus === 'approved' ? $actorUserId : null,
                    'approved_at' => $transactionStatus === 'approved' ? now() : null,
                ]
            );

            $this->syncAllocation($record, $transaction);
        }

        $this->recalculateRecord($record);
    }

    public function deleteBySource(string $sourceType, int $sourceId): void
    {
        if (!$this->isCoreReady()) {
            return;
        }

        $sourceKey = $sourceType . ':' . $sourceId;
        $transaction = FinanceTransaction::query()->where('source_key', $sourceKey)->first();
        if (!$transaction) {
            return;
        }

        $record = $transaction->record;
        FinanceAllocation::query()->where('finance_transaction_id', $transaction->id)->delete();
        $transaction->delete();

        if ($record) {
            $this->recalculateRecord($record);
        }
    }

    public function syncFinanceTransaction(FinanceTransaction $transaction): void
    {
        if (!$this->isCoreReady()) {
            return;
        }

        $record = $transaction->record;
        if (!$record) {
            return;
        }

        $this->syncAllocation($record, $transaction);
        $this->recalculateRecord($record);
    }

    private function findOrCreateRecordForRequest(LegacyRequest $request, string $currency, ?int $actorUserId = null): FinanceRecord
    {
        $grossAmount = $this->estimateRequestGrossAmount($request);

        /** @var FinanceRecord $record */
        $record = FinanceRecord::query()->firstOrCreate(
            [
                'service_type' => 'request',
                'service_id' => $request->id,
            ],
            [
                'scope_type' => 'service',
                'agency_user_id' => $request->user_id,
                'agency_id' => $this->agencyIdForUser($request->user_id),
                'document_type' => 'request',
                'document_ref' => $request->gtpnr,
                'title' => 'Grup Talep #' . ($request->gtpnr ?: $request->id),
                'currency' => strtoupper($currency ?: 'TRY'),
                'gross_amount' => $grossAmount,
                'paid_amount' => 0,
                'remaining_amount' => $grossAmount,
                'due_date' => null,
                'status' => $grossAmount > 0 ? 'open' : 'draft',
                'notes' => null,
                'meta' => ['source' => 'legacy_request'],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]
        );

        $newGross = max((float) $record->gross_amount, $grossAmount);
        $record->fill([
            'agency_user_id' => $request->user_id,
            'agency_id' => $this->agencyIdForUser($request->user_id),
            'document_ref' => $request->gtpnr,
            'currency' => strtoupper($currency ?: $record->currency),
            'gross_amount' => $newGross,
            'remaining_amount' => max(0, round($newGross - (float) $record->paid_amount, 2)),
            'updated_by_user_id' => $actorUserId,
        ]);
        $record->save();

        return $record->fresh();
    }

    private function findOrCreateRecordForCharterBooking(CharterBooking $booking, string $currency, ?int $actorUserId = null): FinanceRecord
    {
        $request = $booking->request;
        $grossAmount = (float) $booking->total_amount;
        if ($grossAmount <= 0 && $request) {
            $grossAmount = (float) ($request->salesQuotes()->latest('id')->value('sale_price') ?? 0);
        }

        /** @var FinanceRecord $record */
        $record = FinanceRecord::query()->firstOrCreate(
            [
                'service_type' => 'charter_booking',
                'service_id' => $booking->id,
            ],
            [
                'scope_type' => 'service',
                'agency_user_id' => $request?->user_id,
                'agency_id' => $this->agencyIdForUser($request?->user_id),
                'document_type' => 'charter',
                'document_ref' => $request ? ('CHAR-' . $request->id) : ('BOOK-' . $booking->id),
                'title' => 'Air Charter Talep #' . ($request?->id ?: $booking->id),
                'currency' => strtoupper($currency ?: 'EUR'),
                'gross_amount' => $grossAmount,
                'paid_amount' => 0,
                'remaining_amount' => $grossAmount,
                'due_date' => null,
                'status' => $grossAmount > 0 ? 'open' : 'draft',
                'meta' => ['source' => 'charter_booking', 'charter_request_id' => $booking->charter_request_id],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]
        );

        $newGross = max((float) $record->gross_amount, $grossAmount);
        $record->fill([
            'agency_user_id' => $request?->user_id,
            'agency_id' => $this->agencyIdForUser($request?->user_id),
            'currency' => strtoupper($currency ?: $record->currency),
            'gross_amount' => $newGross,
            'remaining_amount' => max(0, round($newGross - (float) $record->paid_amount, 2)),
            'updated_by_user_id' => $actorUserId,
        ]);
        $record->save();

        return $record->fresh();
    }

    private function syncAllocation(FinanceRecord $record, FinanceTransaction $transaction): void
    {
        if (!in_array((string) $transaction->status, self::REALIZED_STATUSES, true)) {
            FinanceAllocation::query()->where('finance_transaction_id', $transaction->id)->delete();
            return;
        }

        $baseAmount = round((float) $transaction->gross_amount, 2);
        $allocationAmount = $transaction->direction === 'out' ? (0 - $baseAmount) : $baseAmount;
        $allocationType = $transaction->direction === 'out' ? 'refund' : 'payment';

        FinanceAllocation::query()->updateOrCreate(
            [
                'finance_record_id' => $record->id,
                'finance_transaction_id' => $transaction->id,
            ],
            [
                'allocation_type' => $allocationType,
                'amount' => $allocationAmount,
                'currency' => $transaction->currency,
                'notes' => null,
            ]
        );
    }

    private function recalculateRecord(FinanceRecord $record): void
    {
        $totalAllocated = (float) $record->allocations()->sum('amount');
        $gross = (float) $record->gross_amount;
        $remaining = max(0, round($gross - $totalAllocated, 2));

        $hasOutAllocations = $record->allocations()->where('amount', '<', 0)->exists();
        $status = 'open';
        if ($gross <= 0.0001 && abs($totalAllocated) <= 0.0001) {
            $status = 'draft';
        } elseif ($remaining <= 0.0001 && $totalAllocated > 0) {
            $status = 'paid';
        } elseif ($totalAllocated > 0 && $remaining > 0.0001) {
            $status = 'partial';
        } elseif ($hasOutAllocations && $totalAllocated <= 0.0001) {
            $status = 'refunded';
        }

        $before = [
            'paid_amount' => (float) $record->paid_amount,
            'remaining_amount' => (float) $record->remaining_amount,
            'status' => (string) $record->status,
        ];

        $record->update([
            'paid_amount' => round($totalAllocated, 2),
            'remaining_amount' => $remaining,
            'status' => $status,
        ]);

        $this->audit(
            action: 'recalculate',
            entityType: 'finance_record',
            entityId: $record->id,
            before: $before,
            after: [
                'paid_amount' => (float) $record->fresh()->paid_amount,
                'remaining_amount' => (float) $record->fresh()->remaining_amount,
                'status' => (string) $record->fresh()->status,
            ],
            note: 'Payment allocation totals recalculated'
        );

        if (Schema::hasTable('finance_payment_plans')) {
            app(FinancePaymentPlanService::class)->syncForRecord($record->fresh());
        }
    }

    private function estimateRequestGrossAmount(LegacyRequest $request): float
    {
        $acceptedOffer = $request->offers->firstWhere('is_accepted', true);
        $latestOffer = $acceptedOffer ?: $request->offers->sortByDesc('id')->first();
        $offerTotal = (float) ($latestOffer?->total_price ?? 0);
        if ($offerTotal > 0) {
            return round($offerTotal, 2);
        }

        $paidOrPending = (float) $request->payments()
            ->whereIn('status', ['bekleniyor', 'alindi'])
            ->sum('amount');

        return round(max(0, $paidOrPending), 2);
    }

    private function mapLegacyPaymentMethod(string $method): string
    {
        $normalized = mb_strtolower(trim($method), 'UTF-8');

        return match ($normalized) {
            'kart' => 'card',
            'havale', 'fast' => 'bank_transfer',
            'eft' => 'eft',
            'nakit' => 'cash',
            default => 'other',
        };
    }

    private function mapCharterPaymentMethod(string $method): string
    {
        $normalized = mb_strtolower(trim($method), 'UTF-8');

        return match ($normalized) {
            'card' => 'card',
            'bank_transfer' => 'bank_transfer',
            default => 'other',
        };
    }

    private function mapLegacyRequestPaymentStatus(string $status): array
    {
        $normalized = mb_strtolower(trim($status), 'UTF-8');

        return match ($normalized) {
            'alindi' => ['approved', 'in'],
            'bekleniyor' => ['awaiting_validation', 'in'],
            'iade' => ['refunded', 'out'],
            default => ['pending', 'in'],
        };
    }

    private function mapCharterPaymentStatus(string $status): array
    {
        $normalized = mb_strtolower(trim($status), 'UTF-8');

        return match ($normalized) {
            'approved' => ['approved', 'in'],
            'rejected' => ['rejected', 'in'],
            'pending' => ['awaiting_validation', 'in'],
            default => ['pending', 'in'],
        };
    }

    private function mapTransferPaymentStatus(string $status): array
    {
        $normalized = mb_strtolower(trim($status), 'UTF-8');

        return match ($normalized) {
            'paid', 'approved' => ['approved', 'in'],
            'refunded' => ['refunded', 'out'],
            'failed', 'declined' => ['rejected', 'in'],
            default => ['pending', 'in'],
        };
    }

    private function mapLeisurePaymentStatus(string $status): array
    {
        $normalized = mb_strtolower(trim($status), 'UTF-8');

        return match ($normalized) {
            'approved', 'paid' => ['approved', 'in'],
            'refunded' => ['refunded', 'out'],
            'rejected', 'failed', 'declined' => ['rejected', 'in'],
            default => ['pending', 'in'],
        };
    }

    private function agencyIdForUser(?int $userId): ?int
    {
        if (!$userId) {
            return null;
        }

        return Agency::query()->where('user_id', $userId)->value('id');
    }

    private function isCoreReady(): bool
    {
        return Schema::hasTable('finance_records')
            && Schema::hasTable('finance_transactions')
            && Schema::hasTable('finance_allocations');
    }

    private function audit(
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $before = null,
        ?array $after = null,
        ?string $note = null,
        ?int $actorUserId = null
    ): void {
        if (!Schema::hasTable('finance_audit_logs')) {
            return;
        }

        FinanceAuditLog::query()->create([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before_json' => $before,
            'after_json' => $after,
            'note' => $note,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}

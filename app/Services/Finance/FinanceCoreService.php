<?php

namespace App\Services\Finance;

use App\Models\Agency;
use App\Models\FinanceAuditLog;
use App\Models\FinanceRecord;
use App\Models\FinanceRefund;
use App\Models\FinanceTransaction;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FinanceCoreService
{
    public function __construct(
        private readonly FinanceSyncService $financeSyncService
    ) {
    }

    public function createManualRecord(array $payload, int $actorUserId): FinanceRecord
    {
        $agencyUserId = isset($payload['agency_user_id']) ? (int) $payload['agency_user_id'] : null;
        $currency = strtoupper((string) ($payload['currency'] ?? 'TRY'));
        $grossAmount = round((float) ($payload['gross_amount'] ?? 0), 2);

        $record = FinanceRecord::query()->create([
            'agency_user_id' => $agencyUserId,
            'agency_id' => $this->agencyIdForUser($agencyUserId),
            'scope_type' => 'manual',
            'service_type' => null,
            'service_id' => null,
            'document_type' => 'manual_collection',
            'document_ref' => null,
            'title' => (string) $payload['title'],
            'currency' => $currency,
            'gross_amount' => $grossAmount,
            'paid_amount' => 0,
            'remaining_amount' => $grossAmount,
            'due_date' => $payload['due_date'] ?? null,
            'status' => $grossAmount > 0 ? 'open' : 'draft',
            'notes' => $payload['notes'] ?? null,
            'meta' => [
                'created_by' => 'manual',
            ],
            'created_by_user_id' => $actorUserId,
            'updated_by_user_id' => $actorUserId,
        ]);

        $record->update([
            'document_ref' => 'MAN-' . $record->id,
        ]);

        $this->audit(
            actorUserId: $actorUserId,
            action: 'manual_record_created',
            entityType: 'finance_record',
            entityId: $record->id,
            before: null,
            after: $record->fresh()->toArray(),
            note: 'Manual finance record created'
        );

        return $record->fresh();
    }

    public function addManualTransaction(FinanceRecord $record, array $payload, int $actorUserId): FinanceTransaction
    {
        $grossAmount = round((float) ($payload['gross_amount'] ?? 0), 2);
        $feeAmount = round((float) ($payload['fee_amount'] ?? 0), 2);
        $commissionAmount = round((float) ($payload['commission_amount'] ?? 0), 2);
        $netAmount = round($grossAmount - $feeAmount - $commissionAmount, 2);
        $status = (string) ($payload['status'] ?? 'approved');

        $transaction = FinanceTransaction::query()->create([
            'finance_record_id' => $record->id,
            'source_key' => 'manual_tx:' . Str::uuid()->toString(),
            'source_type' => 'manual',
            'source_id' => null,
            'payer_user_id' => $record->agency_user_id,
            'method' => (string) ($payload['method'] ?? 'manual'),
            'direction' => (string) ($payload['direction'] ?? 'in'),
            'gross_amount' => $grossAmount,
            'fee_amount' => $feeAmount,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
            'currency' => strtoupper((string) ($payload['currency'] ?? $record->currency)),
            'status' => $status,
            'payment_date' => $payload['payment_date'] ?? null,
            'provider' => 'manual_entry',
            'provider_reference' => $payload['provider_reference'] ?? null,
            'bank_name' => $payload['bank_name'] ?? null,
            'sender_name' => $payload['sender_name'] ?? null,
            'sender_reference' => $payload['sender_reference'] ?? null,
            'receipt_path' => $payload['receipt_path'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'meta' => [
                'manual' => true,
            ],
            'created_by_user_id' => $actorUserId,
            'approved_by_user_id' => $status === 'approved' ? $actorUserId : null,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);

        $this->financeSyncService->syncFinanceTransaction($transaction->fresh());

        $this->audit(
            actorUserId: $actorUserId,
            action: 'manual_transaction_created',
            entityType: 'finance_transaction',
            entityId: $transaction->id,
            before: null,
            after: $transaction->fresh()->toArray(),
            note: 'Manual finance transaction created'
        );

        return $transaction->fresh();
    }

    public function createRefund(FinanceRecord $record, array $payload, int $actorUserId): FinanceRefund
    {
        $amount = round((float) ($payload['amount'] ?? 0), 2);
        $processNow = (bool) ($payload['process_now'] ?? false);

        $refund = FinanceRefund::query()->create([
            'finance_record_id' => $record->id,
            'finance_transaction_id' => isset($payload['finance_transaction_id']) && $payload['finance_transaction_id']
                ? (int) $payload['finance_transaction_id']
                : null,
            'refund_transaction_id' => null,
            'amount' => $amount,
            'currency' => strtoupper((string) ($payload['currency'] ?? $record->currency)),
            'method' => (string) ($payload['method'] ?? 'manual'),
            'status' => $processNow ? 'processed' : 'requested',
            'reason' => $payload['reason'] ?? null,
            'initiated_by_user_id' => $actorUserId,
            'approved_by_user_id' => $processNow ? $actorUserId : null,
            'processed_by_user_id' => $processNow ? $actorUserId : null,
            'approved_at' => $processNow ? now() : null,
            'processed_at' => $processNow ? now() : null,
            'meta' => [
                'manual' => true,
            ],
        ]);

        if ($processNow) {
            $transaction = FinanceTransaction::query()->create([
                'finance_record_id' => $record->id,
                'source_key' => 'manual_refund:' . $refund->id,
                'source_type' => 'finance_refund',
                'source_id' => $refund->id,
                'payer_user_id' => $record->agency_user_id,
                'method' => (string) ($payload['method'] ?? 'manual'),
                'direction' => 'out',
                'gross_amount' => $amount,
                'fee_amount' => 0,
                'commission_amount' => 0,
                'net_amount' => $amount,
                'currency' => strtoupper((string) ($payload['currency'] ?? $record->currency)),
                'status' => 'approved',
                'payment_date' => $payload['payment_date'] ?? now()->toDateString(),
                'provider' => 'manual_refund',
                'provider_reference' => null,
                'bank_name' => null,
                'sender_name' => null,
                'sender_reference' => null,
                'receipt_path' => null,
                'notes' => $payload['reason'] ?? null,
                'meta' => [
                    'manual' => true,
                ],
                'created_by_user_id' => $actorUserId,
                'approved_by_user_id' => $actorUserId,
                'approved_at' => now(),
            ]);

            $refund->update([
                'refund_transaction_id' => $transaction->id,
            ]);

            $this->financeSyncService->syncFinanceTransaction($transaction->fresh());
        }

        $this->audit(
            actorUserId: $actorUserId,
            action: 'refund_created',
            entityType: 'finance_refund',
            entityId: $refund->id,
            before: null,
            after: $refund->fresh()->toArray(),
            note: $processNow ? 'Refund created and processed' : 'Refund request created'
        );

        return $refund->fresh();
    }

    private function agencyIdForUser(?int $userId): ?int
    {
        if (!$userId) {
            return null;
        }

        return Agency::query()->where('user_id', $userId)->value('id');
    }

    private function audit(
        ?int $actorUserId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $before = null,
        ?array $after = null,
        ?string $note = null
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

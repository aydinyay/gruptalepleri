<?php

namespace App\Services\Finance;

use App\Models\FinanceAuditLog;
use App\Models\FinancePaymentPlan;
use App\Models\FinanceRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FinancePaymentPlanService
{
    public function createInstallmentPlan(FinanceRecord $record, array $payload, int $actorUserId): void
    {
        if (!Schema::hasTable('finance_payment_plans')) {
            return;
        }

        $count = max(1, min(24, (int) ($payload['installment_count'] ?? 1)));
        $intervalDays = max(1, min(365, (int) ($payload['interval_days'] ?? 30)));
        $currency = strtoupper((string) ($payload['currency'] ?? $record->currency));
        $firstDueDate = Carbon::parse((string) $payload['first_due_date'])->startOfDay();

        $requestedTotal = isset($payload['total_amount']) && $payload['total_amount'] !== null
            ? round((float) $payload['total_amount'], 2)
            : round((float) $record->remaining_amount, 2);
        $totalAmount = max(0.01, $requestedTotal);

        $existingMaxSequence = (int) $record->paymentPlans()->max('sequence');
        $baseAmount = floor(($totalAmount / $count) * 100) / 100;
        $residual = round($totalAmount - ($baseAmount * $count), 2);
        $planGroup = (string) Str::uuid();

        for ($i = 1; $i <= $count; $i++) {
            $installmentAmount = $baseAmount;
            if ($i === $count) {
                $installmentAmount = round($baseAmount + $residual, 2);
            }

            FinancePaymentPlan::query()->create([
                'finance_record_id' => $record->id,
                'sequence' => $existingMaxSequence + $i,
                'title' => $count > 1 ? ('Taksit ' . $i . '/' . $count) : 'Odeme Plani',
                'due_date' => $firstDueDate->copy()->addDays(($i - 1) * $intervalDays)->toDateString(),
                'amount' => $installmentAmount,
                'paid_amount' => 0,
                'currency' => $currency,
                'status' => 'planned',
                'paid_at' => null,
                'note' => $payload['note'] ?? null,
                'meta' => [
                    'group' => $planGroup,
                    'count' => $count,
                    'interval_days' => $intervalDays,
                ],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
        }

        $this->syncForRecord($record->fresh(), $actorUserId);
    }

    public function syncForRecord(FinanceRecord $record, ?int $actorUserId = null): void
    {
        if (!Schema::hasTable('finance_payment_plans')) {
            return;
        }

        $plans = FinancePaymentPlan::query()
            ->where('finance_record_id', $record->id)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        if ($plans->isEmpty()) {
            return;
        }

        $remainingPaidBudget = max(0.0, round((float) $record->paid_amount, 2));

        foreach ($plans as $plan) {
            if ($plan->status === 'cancelled') {
                continue;
            }

            $before = [
                'status' => (string) $plan->status,
                'paid_amount' => (float) $plan->paid_amount,
                'paid_at' => optional($plan->paid_at)?->toDateTimeString(),
            ];

            $amount = round((float) $plan->amount, 2);
            $allocated = min($amount, $remainingPaidBudget);

            $status = 'planned';
            if ($allocated >= $amount && $amount > 0) {
                $status = 'paid';
            } elseif ($allocated > 0) {
                $status = 'partial';
            }

            $plan->update([
                'status' => $status,
                'paid_amount' => $allocated,
                'paid_at' => $status === 'paid'
                    ? ($plan->paid_at ?: now())
                    : null,
                'updated_by_user_id' => $actorUserId,
            ]);

            $after = [
                'status' => (string) $plan->fresh()->status,
                'paid_amount' => (float) $plan->fresh()->paid_amount,
                'paid_at' => optional($plan->fresh()->paid_at)?->toDateTimeString(),
            ];

            if ($before !== $after) {
                $this->audit(
                    actorUserId: $actorUserId,
                    action: 'payment_plan_synced',
                    entityType: 'finance_payment_plan',
                    entityId: $plan->id,
                    before: $before,
                    after: $after,
                    note: 'Payment plan status synced by record paid amount'
                );
            }

            $remainingPaidBudget = round(max(0, $remainingPaidBudget - $allocated), 2);
        }
    }

    public function updateStatus(FinancePaymentPlan $plan, string $status, int $actorUserId): void
    {
        $allowed = ['planned', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return;
        }

        $before = [
            'status' => (string) $plan->status,
            'paid_amount' => (float) $plan->paid_amount,
        ];

        $plan->update([
            'status' => $status,
            'paid_amount' => $status === 'cancelled' ? 0 : $plan->paid_amount,
            'paid_at' => $status === 'cancelled' ? null : $plan->paid_at,
            'updated_by_user_id' => $actorUserId,
        ]);

        $this->syncForRecord($plan->record->fresh(), $actorUserId);

        $this->audit(
            actorUserId: $actorUserId,
            action: 'payment_plan_status_changed',
            entityType: 'finance_payment_plan',
            entityId: $plan->id,
            before: $before,
            after: [
                'status' => (string) $plan->fresh()->status,
                'paid_amount' => (float) $plan->fresh()->paid_amount,
            ],
            note: 'Payment plan status manually changed'
        );
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

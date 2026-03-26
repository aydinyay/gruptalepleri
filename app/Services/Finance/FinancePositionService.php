<?php

namespace App\Services\Finance;

use App\Models\FinanceRecord;
use App\Models\FinanceRefund;
use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FinancePositionService
{
    public function buildAgencyBalances(int $limit = 300): array
    {
        if (
            !Schema::hasTable('finance_records')
            || !Schema::hasTable('finance_transactions')
            || !Schema::hasTable('users')
        ) {
            return [
                'rows' => collect(),
                'summary' => [
                    'receivable_total' => 0.0,
                    'payable_total' => 0.0,
                    'pending_refund_total' => 0.0,
                    'net_total' => 0.0,
                ],
            ];
        }

        $recordSums = FinanceRecord::query()
            ->selectRaw('agency_user_id, SUM(gross_amount) as gross_total, SUM(paid_amount) as paid_total, SUM(remaining_amount) as receivable_total, COUNT(*) as record_count')
            ->whereNotNull('agency_user_id')
            ->groupBy('agency_user_id')
            ->get()
            ->keyBy('agency_user_id');

        $approvedOutSums = FinanceTransaction::query()
            ->selectRaw('payer_user_id as agency_user_id, SUM(gross_amount) as out_total')
            ->whereNotNull('payer_user_id')
            ->where('direction', 'out')
            ->whereIn('status', ['approved', 'refunded'])
            ->groupBy('payer_user_id')
            ->get()
            ->keyBy('agency_user_id');

        $pendingRefundSums = collect();
        if (Schema::hasTable('finance_refunds')) {
            $pendingRefundSums = FinanceRefund::query()
                ->join('finance_records', 'finance_records.id', '=', 'finance_refunds.finance_record_id')
                ->selectRaw('finance_records.agency_user_id as agency_user_id, SUM(finance_refunds.amount) as pending_refund_total')
                ->whereNotNull('finance_records.agency_user_id')
                ->where('finance_refunds.status', 'requested')
                ->groupBy('finance_records.agency_user_id')
                ->get()
                ->keyBy('agency_user_id');
        }

        $agencyIds = $recordSums->keys()
            ->merge($approvedOutSums->keys())
            ->merge($pendingRefundSums->keys())
            ->unique()
            ->values();

        $users = User::query()
            ->whereIn('id', $agencyIds)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        $rows = $agencyIds->map(function ($agencyUserId) use ($recordSums, $approvedOutSums, $pendingRefundSums, $users) {
            $recordRow = $recordSums->get($agencyUserId);
            $outRow = $approvedOutSums->get($agencyUserId);
            $refundRow = $pendingRefundSums->get($agencyUserId);
            $user = $users->get($agencyUserId);

            $grossTotal = round((float) ($recordRow->gross_total ?? 0), 2);
            $paidTotal = round((float) ($recordRow->paid_total ?? 0), 2);
            $receivableTotal = round((float) ($recordRow->receivable_total ?? 0), 2);
            $payableTotal = round((float) ($outRow->out_total ?? 0), 2);
            $pendingRefundTotal = round((float) ($refundRow->pending_refund_total ?? 0), 2);

            // Net > 0 => acenteden alacakliyiz, Net < 0 => acenteye borcluyuz.
            $net = round($receivableTotal - $payableTotal - $pendingRefundTotal, 2);

            return [
                'agency_user_id' => (int) $agencyUserId,
                'agency_name' => $user?->name ?: ('Acenta #' . $agencyUserId),
                'agency_email' => $user?->email,
                'record_count' => (int) ($recordRow->record_count ?? 0),
                'gross_total' => $grossTotal,
                'paid_total' => $paidTotal,
                'receivable_total' => $receivableTotal,
                'payable_total' => $payableTotal,
                'pending_refund_total' => $pendingRefundTotal,
                'net_total' => $net,
                'position_label' => $net > 0 ? 'alacakli' : ($net < 0 ? 'borclu' : 'denge'),
            ];
        })
            ->sortByDesc(fn (array $row) => abs($row['net_total']))
            ->values();

        if ($limit > 0) {
            $rows = $rows->take($limit)->values();
        }

        return [
            'rows' => $rows,
            'summary' => $this->calculateSummary($rows),
        ];
    }

    private function calculateSummary(Collection $rows): array
    {
        $receivableTotal = round((float) $rows->sum('receivable_total'), 2);
        $payableTotal = round((float) $rows->sum('payable_total'), 2);
        $pendingRefundTotal = round((float) $rows->sum('pending_refund_total'), 2);
        $netTotal = round($receivableTotal - $payableTotal - $pendingRefundTotal, 2);

        return [
            'receivable_total' => $receivableTotal,
            'payable_total' => $payableTotal,
            'pending_refund_total' => $pendingRefundTotal,
            'net_total' => $netTotal,
        ];
    }
}

<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\FinancePaymentPlan;
use App\Models\FinanceReceiptSubmission;
use App\Models\FinanceRecord;
use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceController extends Controller
{
    use \App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;

    public function index(Request $request)
    {
        $actor = $this->acenteActor();
        if ($actor->parent_agency_id) {
            abort_unless($actor->canDo('finans'), 403, 'Finans sayfasına erişim yetkiniz yok.');
        }
        $coreReady = Schema::hasTable('finance_records') && Schema::hasTable('finance_transactions');

        if (!$coreReady) {
            return view('acente.finance.index', [
                'coreReady' => false,
                'records' => collect(),
                'submissions' => collect(),
                'summary' => [
                    'open_total' => 0,
                    'paid_total' => 0,
                    'remaining_total' => 0,
                    'pending_transactions' => 0,
                    'due_in_7_days' => 0,
                    'overdue_installments' => 0,
                ],
                'plans' => collect(),
            ]);
        }

        $records = FinanceRecord::query()
            ->where('agency_user_id', auth()->id())
            ->with([
                'transactions' => fn ($q) => $q->latest()->limit(5),
                'paymentPlans' => fn ($q) => $q->orderBy('sequence')->limit(12),
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $submissions = Schema::hasTable('finance_receipt_submissions')
            ? FinanceReceiptSubmission::query()
                ->where('agency_user_id', auth()->id())
                ->with('record')
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $summary = [
            'open_total' => (float) FinanceRecord::query()
                ->where('agency_user_id', auth()->id())
                ->whereIn('status', ['open', 'partial'])
                ->sum('gross_amount'),
            'paid_total' => (float) FinanceRecord::query()
                ->where('agency_user_id', auth()->id())
                ->sum('paid_amount'),
            'remaining_total' => (float) FinanceRecord::query()
                ->where('agency_user_id', auth()->id())
                ->sum('remaining_amount'),
            'pending_transactions' => FinanceTransaction::query()
                ->where('payer_user_id', auth()->id())
                ->whereIn('status', ['pending', 'awaiting_validation'])
                ->count(),
            'due_in_7_days' => Schema::hasTable('finance_payment_plans')
                ? FinancePaymentPlan::query()
                    ->whereHas('record', fn ($q) => $q->where('agency_user_id', auth()->id()))
                    ->whereIn('status', ['planned', 'partial'])
                    ->whereDate('due_date', '>=', now()->toDateString())
                    ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                    ->count()
                : 0,
            'overdue_installments' => Schema::hasTable('finance_payment_plans')
                ? FinancePaymentPlan::query()
                    ->whereHas('record', fn ($q) => $q->where('agency_user_id', auth()->id()))
                    ->whereIn('status', ['planned', 'partial'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count()
                : 0,
        ];

        $plans = Schema::hasTable('finance_payment_plans')
            ? FinancePaymentPlan::query()
                ->whereHas('record', fn ($q) => $q->where('agency_user_id', auth()->id()))
                ->with('record')
                ->orderBy('due_date')
                ->limit(30)
                ->get()
            : collect();

        return view('acente.finance.index', compact('coreReady', 'records', 'summary', 'submissions', 'plans'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancePaymentPlan;
use App\Models\FinanceReceiptSubmission;
use App\Models\FinanceRecord;
use App\Models\FinanceRefund;
use App\Models\FinanceTransaction;
use App\Models\User;
use App\Services\Finance\FinanceCoreService;
use App\Services\Finance\FinancePaymentPlanService;
use App\Services\Finance\FinancePositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceController extends Controller
{
    private function assertAuthorized(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);
    }

    public function index(Request $request)
    {
        $this->assertAuthorized();

        $coreReady = Schema::hasTable('finance_records') && Schema::hasTable('finance_transactions');
        if (!$coreReady) {
            return view('admin.finance.index', [
                'coreReady' => false,
                'records' => collect(),
                'summary' => [
                    'open_total' => 0,
                    'paid_total' => 0,
                    'remaining_total' => 0,
                    'pending_transactions' => 0,
                    'pending_receipts' => 0,
                    'requested_refunds' => 0,
                    'due_in_7_days' => 0,
                    'overdue_installments' => 0,
                ],
                'agencyUsers' => collect(),
                'openRecords' => collect(),
                'plans' => collect(),
                'agencyBalances' => collect(),
                'balanceSummary' => [
                    'receivable_total' => 0.0,
                    'payable_total' => 0.0,
                    'pending_refund_total' => 0.0,
                    'net_total' => 0.0,
                ],
            ]);
        }

        $status = (string) $request->query('status', '');
        $serviceType = (string) $request->query('service_type', '');

        $recordsQuery = FinanceRecord::query()->with(['agencyUser', 'transactions' => fn ($q) => $q->latest()->limit(5)])->latest();
        if ($status !== '') {
            $recordsQuery->where('status', $status);
        }
        if ($serviceType !== '') {
            $recordsQuery->where('service_type', $serviceType);
        }

        $records = $recordsQuery->paginate(30)->withQueryString();

        $summary = [
            'open_total' => (float) FinanceRecord::query()->whereIn('status', ['open', 'partial'])->sum('gross_amount'),
            'paid_total' => (float) FinanceRecord::query()->sum('paid_amount'),
            'remaining_total' => (float) FinanceRecord::query()->sum('remaining_amount'),
            'pending_transactions' => FinanceTransaction::query()->whereIn('status', ['pending', 'awaiting_validation'])->count(),
            'pending_receipts' => Schema::hasTable('finance_receipt_submissions')
                ? FinanceReceiptSubmission::query()->whereIn('status', ['pending', 'needs_review', 'insufficient_data'])->count()
                : 0,
            'requested_refunds' => Schema::hasTable('finance_refunds')
                ? FinanceRefund::query()->where('status', 'requested')->count()
                : 0,
            'due_in_7_days' => Schema::hasTable('finance_payment_plans')
                ? FinancePaymentPlan::query()
                    ->whereIn('status', ['planned', 'partial'])
                    ->whereDate('due_date', '>=', now()->toDateString())
                    ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                    ->count()
                : 0,
            'overdue_installments' => Schema::hasTable('finance_payment_plans')
                ? FinancePaymentPlan::query()
                    ->whereIn('status', ['planned', 'partial'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count()
                : 0,
        ];

        $agencyUsers = User::query()
            ->where('role', 'acente')
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'email']);

        $openRecords = FinanceRecord::query()
            ->with('agencyUser')
            ->whereIn('status', ['open', 'partial', 'paid'])
            ->latest('id')
            ->limit(500)
            ->get(['id', 'document_ref', 'title', 'currency', 'status', 'agency_user_id', 'remaining_amount', 'paid_amount']);

        $plans = Schema::hasTable('finance_payment_plans')
            ? FinancePaymentPlan::query()
                ->with(['record.agencyUser'])
                ->orderBy('due_date')
                ->limit(50)
                ->get()
            : collect();

        $positionData = app(FinancePositionService::class)->buildAgencyBalances(300);
        $agencyBalances = $positionData['rows'];
        $balanceSummary = $positionData['summary'];

        return view('admin.finance.index', compact(
            'coreReady',
            'records',
            'summary',
            'status',
            'serviceType',
            'agencyUsers',
            'openRecords',
            'plans',
            'agencyBalances',
            'balanceSummary'
        ));
    }

    public function storeManualRecord(Request $request, FinanceCoreService $financeCoreService): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'agency_user_id' => 'nullable|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'gross_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:8',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $financeCoreService->createManualRecord($validated, (int) auth()->id());

        return back()->with('success', 'Serbest tahsilat kaydi olusturuldu.');
    }

    public function storeManualTransaction(Request $request, FinanceCoreService $financeCoreService): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'finance_record_id' => 'required|integer|exists:finance_records,id',
            'gross_amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:8',
            'status' => 'required|in:pending,awaiting_validation,approved,rejected,cancelled',
            'method' => 'required|in:card,bank_transfer,eft,cash,manual,other',
            'direction' => 'required|in:in,out',
            'payment_date' => 'nullable|date',
            'fee_amount' => 'nullable|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $record = FinanceRecord::query()->findOrFail((int) $validated['finance_record_id']);
        $financeCoreService->addManualTransaction($record, $validated, (int) auth()->id());

        return back()->with('success', 'Manuel finans hareketi kaydedildi.');
    }

    public function storeRefund(Request $request, FinanceCoreService $financeCoreService): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'finance_record_id' => 'required|integer|exists:finance_records,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:8',
            'method' => 'required|in:card,bank_transfer,eft,cash,manual,other',
            'reason' => 'nullable|string|max:2000',
            'process_now' => 'nullable|boolean',
            'payment_date' => 'nullable|date',
        ]);

        $record = FinanceRecord::query()->findOrFail((int) $validated['finance_record_id']);
        $maxRefundable = max(0.0, (float) $record->paid_amount);

        if ((float) $validated['amount'] > $maxRefundable) {
            return back()->withErrors([
                'amount' => 'Iade tutari, kayitli odenen tutardan buyuk olamaz.',
            ])->withInput();
        }

        $financeCoreService->createRefund($record, $validated, (int) auth()->id());

        return back()->with('success', 'Iade kaydi olusturuldu.');
    }

    public function storePaymentPlan(Request $request, FinancePaymentPlanService $paymentPlanService): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'finance_record_id' => 'required|integer|exists:finance_records,id',
            'first_due_date' => 'required|date',
            'installment_count' => 'required|integer|min:1|max:24',
            'interval_days' => 'nullable|integer|min:1|max:365',
            'total_amount' => 'nullable|numeric|min:0.01',
            'currency' => 'required|string|max:8',
            'note' => 'nullable|string|max:2000',
        ]);

        $record = FinanceRecord::query()->findOrFail((int) $validated['finance_record_id']);
        $paymentPlanService->createInstallmentPlan($record, $validated, (int) auth()->id());

        return back()->with('success', 'Odeme plani olusturuldu.');
    }

    public function updatePaymentPlan(Request $request, FinancePaymentPlan $plan, FinancePaymentPlanService $paymentPlanService): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'status' => 'required|in:planned,cancelled',
        ]);

        $paymentPlanService->updateStatus($plan, (string) $validated['status'], (int) auth()->id());

        return back()->with('success', 'Odeme plani durumu guncellendi.');
    }
}

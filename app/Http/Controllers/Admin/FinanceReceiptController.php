<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceAuditLog;
use App\Models\FinanceReceiptSubmission;
use App\Models\FinanceTransaction;
use App\Services\Finance\FinanceSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FinanceReceiptController extends Controller
{
    private function assertAuthorized(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);
    }

    public function index(Request $request)
    {
        $this->assertAuthorized();

        $coreReady = Schema::hasTable('finance_receipt_submissions');
        if (!$coreReady) {
            return view('admin.finance.receipts', [
                'coreReady' => false,
                'submissions' => collect(),
                'status' => '',
            ]);
        }

        $status = (string) $request->query('status', '');
        $query = FinanceReceiptSubmission::query()
            ->with(['record', 'transaction', 'agencyUser', 'reviewer'])
            ->latest();

        if ($status !== '') {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(30)->withQueryString();

        return view('admin.finance.receipts', compact('coreReady', 'submissions', 'status'));
    }

    public function update(Request $request, FinanceReceiptSubmission $submission, FinanceSyncService $financeSyncService): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'status' => 'required|in:matched,needs_review,rejected,insufficient_data',
            'review_note' => 'nullable|string|max:1000',
        ]);

        $newStatus = (string) $validated['status'];
        $reviewNote = $validated['review_note'] ?? null;
        $beforeStatus = (string) $submission->status;
        $beforeNote = $submission->review_note;

        $submission->update([
            'status' => $newStatus,
            'review_note' => $reviewNote,
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $transaction = $submission->transaction;
        if ($transaction) {
            $this->syncTransactionStatusFromSubmission($transaction, $newStatus, $reviewNote);
            $financeSyncService->syncFinanceTransaction($transaction->fresh());
        }

        $this->audit(
            action: 'receipt_submission_reviewed',
            entityType: 'finance_receipt_submission',
            entityId: $submission->id,
            before: [
                'status' => $beforeStatus,
                'review_note' => $beforeNote,
            ],
            after: [
                'status' => $newStatus,
                'review_note' => $reviewNote,
            ],
            note: 'Receipt review status updated'
        );

        return back()->with('success', 'Dekont inceleme durumu guncellendi.');
    }

    private function syncTransactionStatusFromSubmission(FinanceTransaction $transaction, string $submissionStatus, ?string $note = null): void
    {
        $targetStatus = match ($submissionStatus) {
            'matched' => 'approved',
            'rejected' => 'rejected',
            'needs_review', 'insufficient_data' => 'awaiting_validation',
            default => 'pending',
        };

        $transaction->update([
            'status' => $targetStatus,
            'approved_by_user_id' => $targetStatus === 'approved' ? auth()->id() : null,
            'approved_at' => $targetStatus === 'approved' ? now() : null,
            'notes' => $note ?: $transaction->notes,
        ]);
    }

    private function audit(
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
            'actor_user_id' => auth()->id(),
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

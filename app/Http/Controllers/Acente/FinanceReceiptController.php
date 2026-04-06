<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\FinanceReceiptSubmission;
use App\Models\FinanceRecord;
use App\Models\FinanceTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FinanceReceiptController extends Controller
{
    use \App\Http\Controllers\Acente\Concerns\ResolvesPreviewUser;

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->acenteActor();
        if ($actor->parent_agency_id) {
            abort_unless($actor->canDo('odeme'), 403, 'Dekont yükleme yetkiniz yok.');
        }
        if (
            !Schema::hasTable('finance_records')
            || !Schema::hasTable('finance_transactions')
            || !Schema::hasTable('finance_receipt_submissions')
        ) {
            return back()->with('error', 'Finans cekirdek tablolari henuz hazir degil.');
        }

        $validated = $request->validate([
            'finance_record_id' => 'required|integer|exists:finance_records,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:8',
            'payment_date' => 'required|date',
            'bank_name' => 'nullable|string|max:100',
            'sender_name' => 'nullable|string|max:120',
            'sender_reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:1000',
            'receipt_file' => 'nullable|file|max:8192|mimes:pdf,jpg,jpeg,png,webp',
        ]);

        $record = FinanceRecord::query()
            ->where('id', (int) $validated['finance_record_id'])
            ->where('agency_user_id', auth()->id())
            ->firstOrFail();

        $receiptPath = null;
        if ($request->hasFile('receipt_file')) {
            $receiptPath = $this->storeReceipt($request->file('receipt_file'));
        }

        $submission = FinanceReceiptSubmission::query()->create([
            'finance_record_id' => $record->id,
            'agency_user_id' => auth()->id(),
            'amount' => $validated['amount'],
            'currency' => strtoupper((string) $validated['currency']),
            'payment_date' => $validated['payment_date'],
            'bank_name' => $validated['bank_name'] ?? null,
            'sender_name' => $validated['sender_name'] ?? null,
            'sender_reference' => $validated['sender_reference'] ?? null,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
            'meta' => [
                'note' => $validated['note'] ?? null,
            ],
        ]);

        $transaction = FinanceTransaction::query()->create([
            'finance_record_id' => $record->id,
            'source_key' => 'receipt_submission:' . $submission->id,
            'source_type' => 'finance_receipt_submission',
            'source_id' => $submission->id,
            'payer_user_id' => auth()->id(),
            'method' => 'bank_transfer',
            'direction' => 'in',
            'gross_amount' => $validated['amount'],
            'fee_amount' => 0,
            'commission_amount' => 0,
            'net_amount' => $validated['amount'],
            'currency' => strtoupper((string) $validated['currency']),
            'status' => 'awaiting_validation',
            'payment_date' => $validated['payment_date'],
            'provider' => 'manual_receipt',
            'provider_reference' => null,
            'bank_name' => $validated['bank_name'] ?? null,
            'sender_name' => $validated['sender_name'] ?? null,
            'sender_reference' => $validated['sender_reference'] ?? null,
            'receipt_path' => $receiptPath,
            'notes' => $validated['note'] ?? null,
            'meta' => [
                'submitted_by_agency' => true,
                'submission_id' => $submission->id,
            ],
            'created_by_user_id' => auth()->id(),
        ]);

        $submission->update([
            'finance_transaction_id' => $transaction->id,
        ]);

        return back()->with('success', 'Dekont bildirimi kaydedildi. Onay surecine alindi.');
    }

    private function storeReceipt($file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $safeExt = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'bin';

        $directory = public_path('uploads/finance-receipts/' . now()->format('Y/m'));
        if (!is_dir($directory)) {
            File::ensureDirectoryExists($directory);
        }

        $filename = now()->format('YmdHis') . '_' . Str::random(10) . '.' . $safeExt;
        $file->move($directory, $filename);

        return '/uploads/finance-receipts/' . now()->format('Y/m') . '/' . $filename;
    }
}

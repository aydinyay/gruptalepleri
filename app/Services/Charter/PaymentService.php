<?php

namespace App\Services\Charter;

use App\Models\CharterBooking;
use App\Models\CharterPayment;
use App\Services\Finance\FinanceSyncService;

class PaymentService
{
    public function __construct(
        private readonly FinanceSyncService $financeSyncService
    ) {
    }

    public function createPayment(CharterBooking $booking, array $payload): CharterPayment
    {
        $payment = CharterPayment::query()->create([
            'charter_booking_id' => $booking->id,
            'method' => (string) ($payload['method'] ?? 'bank_transfer'),
            'amount' => (float) ($payload['amount'] ?? 0),
            'currency' => (string) ($payload['currency'] ?? 'EUR'),
            'status' => 'pending',
            'provider' => $payload['provider'] ?? null,
            'provider_reference' => $payload['provider_reference'] ?? null,
            'receipt_path' => $payload['receipt_path'] ?? null,
            'admin_note' => $payload['admin_note'] ?? null,
        ]);

        $this->financeSyncService->syncCharterPayment($payment, auth()->id());

        return $payment;
    }

    public function approve(CharterPayment $payment, int $approvedByUserId, ?string $note = null): CharterPayment
    {
        $payment->update([
            'status' => 'approved',
            'approved_by_user_id' => $approvedByUserId,
            'approved_at' => now(),
            'admin_note' => $note ?: $payment->admin_note,
        ]);

        $this->financeSyncService->syncCharterPayment($payment->fresh(), $approvedByUserId);
        $this->recalculateBooking($payment->booking);

        return $payment->fresh();
    }

    public function reject(CharterPayment $payment, int $approvedByUserId, ?string $note = null): CharterPayment
    {
        $payment->update([
            'status' => 'rejected',
            'approved_by_user_id' => $approvedByUserId,
            'approved_at' => now(),
            'admin_note' => $note ?: $payment->admin_note,
        ]);

        $this->financeSyncService->syncCharterPayment($payment->fresh(), $approvedByUserId);
        $this->recalculateBooking($payment->booking);

        return $payment->fresh();
    }

    public function recalculateBooking(CharterBooking $booking): CharterBooking
    {
        $approvedTotal = (float) $booking->payments()->where('status', 'approved')->sum('amount');
        $remaining = max(0, (float) $booking->total_amount - $approvedTotal);

        $status = 'pending_payment';
        if ($remaining <= 0.0001) {
            $status = 'paid';
        } elseif ($approvedTotal > 0) {
            $status = 'partial_paid';
        }

        $booking->update([
            'total_paid' => round($approvedTotal, 2),
            'remaining_amount' => round($remaining, 2),
            'status' => $status,
        ]);

        $requestStatus = match ($status) {
            'paid' => 'paid',
            'partial_paid' => 'partial_paid',
            default => 'pending_payment',
        };
        $booking->request()->update(['status' => $requestStatus]);

        return $booking->fresh();
    }
}

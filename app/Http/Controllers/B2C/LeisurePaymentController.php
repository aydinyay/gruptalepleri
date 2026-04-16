<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\LeisureBooking;
use App\Models\LeisurePayment;
use App\Models\LeisureRequest;
use App\Services\Payments\PaynkolayGatewayService;
use App\Services\Payments\TcmbExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeisurePaymentController extends Controller
{
    public function __construct(
        private readonly PaynkolayGatewayService $paynkolayGateway,
        private readonly TcmbExchangeRateService $tcmbExchangeRateService,
    ) {}

    public function start(Request $request, LeisureBooking $booking): RedirectResponse
    {
        abort_unless($booking->request?->source_channel === 'b2c', 403);

        $remainingAmount = round((float) $booking->remaining_amount, 2);
        if ($remainingAmount <= 0.0001) {
            return back()->with('error', 'Ödeme yapılacak tutar bulunamadı.');
        }

        $currency          = strtoupper((string) ($booking->currency ?: 'EUR'));
        $fx                = $this->tcmbExchangeRateService->convertToTry($remainingAmount, $currency);
        $internalReference = 'LEI-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(8));
        $paymentReference  = 'LPY-' . strtoupper(Str::random(12));

        $payment = LeisurePayment::create([
            'leisure_booking_id'   => $booking->id,
            'reference'            => $paymentReference,
            'method'               => 'card',
            'amount'               => $remainingAmount,
            'currency'             => $currency,
            'status'               => 'pending',
            'provider'             => 'paynkolay',
            'internal_reference'   => $internalReference,
            'request_payload_json' => [
                'module'               => 'leisure',
                'b2c'                  => true,
                'booking_id'           => $booking->id,
                'leisure_request_id'   => $booking->leisure_request_id,
                'source_amount'        => $remainingAmount,
                'source_currency'      => $currency,
                'charged_try_amount'   => $fx['charged_try_amount'],
                'fx_rate'              => $fx['fx_rate'],
                'fx_timestamp'         => optional($fx['fx_timestamp'])->toIso8601String(),
            ],
            'charged_try_amount'   => $fx['charged_try_amount'],
            'fx_rate'              => $fx['fx_rate'],
            'fx_timestamp'         => $fx['fx_timestamp'],
            'source_currency'      => $fx['source_currency'],
            'created_by_user_id'   => null,
        ]);

        try {
            $initialized = $this->paynkolayGateway->initializePayment(
                clientReference: $internalReference,
                amountTry: (float) $fx['charged_try_amount'],
                successUrl: route('b2c.leisure.payment.success'),
                failUrl: route('b2c.leisure.payment.fail'),
                cardHolderIp: (string) $request->ip(),
            );

            $payment->update([
                'provider_reference'   => $initialized['provider_reference'],
                'response_payload_json' => $initialized['response'],
            ]);

            return redirect()->away($initialized['redirect_url']);
        } catch (\Throwable $e) {
            $payment->update([
                'status'         => 'rejected',
                'failure_reason' => Str::limit($e->getMessage(), 350),
                'failed_at'      => now(),
                'processed_at'   => now(),
            ]);

            return back()->with('error', 'Ödeme başlatılamadı: ' . Str::limit($e->getMessage(), 200));
        }
    }

    public function success(Request $request)
    {
        return $this->handleCallback($request, false);
    }

    public function fail(Request $request)
    {
        return $this->handleCallback($request, true);
    }

    private function handleCallback(Request $request, bool $forceFailed): RedirectResponse
    {
        $payload = $request->all();

        if (! $this->paynkolayGateway->isValidResponseHash($payload)) {
            return redirect()->route('b2c.home')->with('error', 'Ödeme doğrulaması başarısız.');
        }

        if ($forceFailed) {
            $payload['status']        = 'failed';
            $payload['payment_status'] = 'failed';
            $payload['response_code'] = '0';
            $payload['auth_code']     = '0';
        }

        $internalReference = trim((string) (
            $payload['clientRefCode']
            ?? $payload['CLIENT_REFERENCE_CODE']
            ?? $payload['client_reference_code']
            ?? ''
        ));

        $payment = LeisurePayment::where('internal_reference', $internalReference)->first();

        if (! $payment) {
            return redirect()->route('b2c.home')->with('error', 'Ödeme kaydı bulunamadı.');
        }

        $mappedStatus = $this->paynkolayGateway->mapCallbackStatus($payload);

        DB::transaction(function () use ($payment, $payload, $mappedStatus): void {
            if ($payment->processed_at !== null && in_array((string) $payment->status, ['approved', 'rejected'], true)) {
                return;
            }

            $providerReference = trim((string) ($payload['reference_code'] ?? $payload['REFERENCE_CODE'] ?? $payload['provider_reference'] ?? ''));

            $payment->provider_reference    = $providerReference ?: $payment->provider_reference;
            $payment->callback_payload_json = $payload;
            $payment->processed_at          = now();

            if ($mappedStatus === 'paid') {
                $payment->status   = 'approved';
                $payment->paid_at  = now();
                $payment->failed_at = null;
                $payment->failure_reason = null;
            } else {
                $payment->status       = 'rejected';
                $payment->failed_at    = now();
                $payment->failure_reason = Str::limit((string) ($payload['RESPONSE_DATA'] ?? $payload['message'] ?? 'Ödeme reddedildi'), 350);
            }

            $payment->save();

            $booking = $payment->booking?->fresh(['payments']);
            if ($booking) {
                $approved     = round((float) $booking->payments()->where('status', 'approved')->sum('amount'), 2);
                $total        = round((float) $booking->total_amount, 2);
                $remaining    = max(0, round($total - $approved, 2));
                $bookingStatus = $remaining <= 0.0001 ? 'paid' : ($approved > 0 ? 'partial_paid' : 'pending_payment');
                $booking->update([
                    'status'           => $bookingStatus,
                    'total_paid'       => $approved,
                    'remaining_amount' => $remaining,
                ]);
            }
        });

        $payment->refresh();
        $gtpnr = $payment->booking?->request?->gtpnr;

        if (! $gtpnr) {
            return redirect()->route('b2c.home');
        }

        if ($mappedStatus === 'paid') {
            return redirect()->route('b2c.leisure.booking.show', $gtpnr)->with('payment_success', true);
        }

        return redirect()->route('b2c.leisure.booking.show', $gtpnr)->with('payment_failed', true);
    }
}

<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cOrder;
use App\Models\B2C\B2cPayment;
use App\Services\Payments\PaynkolayGatewayService;
use App\Services\Payments\TcmbExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuestPaymentController extends Controller
{
    public function __construct(
        private readonly PaynkolayGatewayService $paynkolayGateway,
        private readonly TcmbExchangeRateService $tcmbExchangeRateService,
    ) {}

    public function start(Request $request, B2cOrder $order): RedirectResponse
    {
        abort_unless(is_null($order->b2c_user_id), 403);
        abort_unless($order->status === 'pending' && (float) $order->total_price > 0, 400);

        $amount   = round((float) $order->total_price, 2);
        $currency = strtoupper((string) ($order->currency ?: 'TRY'));
        $fx       = $this->tcmbExchangeRateService->convertToTry($amount, $currency);

        $internalRef = 'GBO-' . now()->format('ymdHis') . '-' . strtoupper(Str::random(8));
        $ref         = 'GPY-' . strtoupper(Str::random(12));

        $payment = B2cPayment::create([
            'b2c_order_id'        => $order->id,
            'reference'           => $ref,
            'internal_reference'  => $internalRef,
            'provider'            => 'paynkolay',
            'status'              => 'pending',
            'amount'              => $amount,
            'currency'            => $currency,
            'charged_try_amount'  => $fx['charged_try_amount'],
            'fx_rate'             => $fx['fx_rate'],
            'fx_timestamp'        => $fx['fx_timestamp'],
            'source_currency'     => $fx['source_currency'],
            'request_payload_json' => [
                'b2c_order_id'       => $order->id,
                'order_ref'          => $order->order_ref,
                'source_amount'      => $amount,
                'source_currency'    => $currency,
                'charged_try_amount' => $fx['charged_try_amount'],
            ],
        ]);

        try {
            $initialized = $this->paynkolayGateway->initializePayment(
                clientReference: $internalRef,
                amountTry: (float) $fx['charged_try_amount'],
                successUrl: route('b2c.guest.payment.success'),
                failUrl: route('b2c.guest.payment.fail'),
                cardHolderIp: (string) $request->ip(),
            );

            $payment->update([
                'provider_transaction_id' => $initialized['provider_reference'],
                'response_payload_json'   => $initialized['response'],
            ]);

            return redirect()->away($initialized['redirect_url']);
        } catch (\Throwable $e) {
            $payment->update([
                'status'        => 'failed',
                'failure_reason' => Str::limit($e->getMessage(), 350),
                'failed_at'     => now(),
                'processed_at'  => now(),
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

        $internalRef = trim((string) (
            $payload['clientRefCode']
            ?? $payload['CLIENT_REFERENCE_CODE']
            ?? $payload['client_reference_code']
            ?? ''
        ));

        $payment = B2cPayment::where('internal_reference', $internalRef)->first();

        if (! $payment) {
            return redirect()->route('b2c.home')->with('error', 'Ödeme kaydı bulunamadı.');
        }

        $mappedStatus = $this->paynkolayGateway->mapCallbackStatus($payload);

        DB::transaction(function () use ($payment, $payload, $mappedStatus): void {
            if ($payment->processed_at !== null && in_array((string) $payment->status, ['paid', 'failed'], true)) {
                return;
            }

            $providerRef = trim((string) ($payload['reference_code'] ?? $payload['REFERENCE_CODE'] ?? $payload['provider_reference'] ?? ''));

            $payment->provider_transaction_id = $providerRef ?: $payment->provider_transaction_id;
            $payment->callback_payload_json   = $payload;
            $payment->processed_at            = now();

            if ($mappedStatus === 'paid') {
                $payment->status  = 'paid';
                $payment->paid_at = now();
                $payment->failed_at = null;
                $payment->failure_reason = null;
                $payment->save();

                $payment->order?->update([
                    'payment_status' => 'paid',
                    'status'         => 'confirmed',
                    'paid_at'        => now(),
                    'confirmed_at'   => now(),
                ]);
            } else {
                $payment->status       = 'failed';
                $payment->failed_at    = now();
                $payment->failure_reason = Str::limit((string) ($payload['RESPONSE_DATA'] ?? $payload['message'] ?? 'Ödeme reddedildi'), 350);
                $payment->save();
            }
        });

        $ref = $payment->fresh()->order?->order_ref;

        if (! $ref) {
            return redirect()->route('b2c.home');
        }

        if ($mappedStatus === 'paid') {
            return redirect()->route('b2c.guest.booking.show', $ref)->with('payment_success', true);
        }

        return redirect()->route('b2c.guest.booking.show', $ref)->with('payment_failed', true);
    }
}

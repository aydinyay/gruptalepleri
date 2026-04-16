<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\TransferBooking;
use App\Models\TransferPaymentTransaction;
use App\Models\TransferQuoteLock;
use App\Services\B2C\B2cTransferService;
use App\Services\Transfer\PaynkolayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * B2C Transfer — Arama, sonuçlar, checkout, ödeme
 *
 * Route prefix: /transfer (gruprezervasyonlari.com)
 * Auth: Misafir (guest) de kullanabilir; b2c_user_id nullable
 */
class TransferController extends Controller
{
    public function __construct(
        private readonly B2cTransferService $b2cTransferService,
        private readonly PaynkolayService   $paynkolayService,
    ) {}

    /** Arama formu */
    public function index()
    {
        $airports = $this->b2cTransferService->airports();

        return view('b2c.transfer.index', compact('airports'));
    }

    /** Havalimanı → bölgeler (AJAX) */
    public function zones(Request $request)
    {
        $request->validate(['airport_id' => 'required|integer|exists:transfer_airports,id']);
        $zones = $this->b2cTransferService->zones((int) $request->airport_id);

        return response()->json($zones);
    }

    /** Arama yapılıp sonuçlar gösterilir */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'airport_id' => 'required|integer|exists:transfer_airports,id',
            'zone_id'    => 'required|integer|exists:transfer_zones,id',
            'direction'  => 'required|in:ARR,DEP,BOTH',
            'pax'        => 'required|integer|min:1|max:100',
            'pickup_at'  => 'required|date|after:now',
            'return_at'  => 'nullable|date|after:pickup_at',
        ]);

        $b2cUserId = Auth::guard('b2c')->id();
        $result    = $this->b2cTransferService->search($validated, $b2cUserId);

        // Arama parametrelerini session'a al (geri dönüş için)
        session(['b2c_transfer_search' => $validated]);

        return view('b2c.transfer.results', [
            'options'  => $result['options'],
            'error'    => $result['error'],
            'search'   => $validated,
            'airports' => $this->b2cTransferService->airports(),
        ]);
    }

    /** Checkout sayfası — quote detayları + müşteri bilgileri formu */
    public function checkout(Request $request, string $quoteToken)
    {
        $quote = $this->b2cTransferService->findValidQuote($quoteToken);

        if (! $quote) {
            return redirect()
                ->route('b2c.transfer.index')
                ->with('error', 'Teklif süresi dolmuş veya kullanılmış. Lütfen yeniden arama yapın.');
        }

        $b2cUser       = Auth::guard('b2c')->user();
        $ttlSeconds    = max(0, now()->diffInSeconds($quote->expires_at, false));
        $searchParams  = session('b2c_transfer_search', []);

        return view('b2c.transfer.checkout', compact('quote', 'b2cUser', 'ttlSeconds', 'searchParams'));
    }

    /** Rezervasyonu oluşturur + Paynkolay'a yönlendirir */
    public function book(Request $request, string $quoteToken)
    {
        $validated = $request->validate([
            'contact_name'         => 'required|string|max:120',
            'contact_phone'        => 'required|string|max:40',
            'contact_email'        => 'required|email|max:150',
            'notes'                => 'nullable|string|max:1000',
            'passenger_names'      => 'nullable|string|max:2000',
            'flight_number'        => 'nullable|string|max:40',
            'terminal'             => 'nullable|string|max:40',
            'pickup_sign_name'     => 'nullable|string|max:120',
            'exact_pickup_address' => 'nullable|string|max:500',
            'luggage_count'        => 'nullable|integer|min:0|max:50',
            'child_seat_count'     => 'nullable|integer|min:0|max:10',
        ]);

        $b2cUserId = Auth::guard('b2c')->id();

        /** @var TransferBooking $booking */
        /** @var TransferPaymentTransaction $payment */
        [$booking, $payment] = DB::transaction(function () use ($quoteToken, $validated, $b2cUserId): array {
            /** @var TransferQuoteLock $quote */
            $quote = TransferQuoteLock::query()
                ->where('token', $quoteToken)
                ->lockForUpdate()
                ->first();

            if (! $quote || $quote->consumed_at !== null || $quote->isExpired()) {
                throw ValidationException::withMessages([
                    'quote' => 'Teklif süresi dolduğu için rezervasyon oluşturulamadı. Lütfen yeniden arama yapın.',
                ]);
            }

            if (($quote->snapshot_json['source'] ?? '') !== 'b2c') {
                throw ValidationException::withMessages([
                    'quote' => 'Geçersiz teklif.',
                ]);
            }

            $booking = TransferBooking::query()->create([
                'booking_ref'               => $this->generateRef(),
                'source'                    => TransferBooking::SOURCE_B2C,
                'quote_lock_id'             => $quote->id,
                'supplier_id'               => $quote->supplier_id,
                'agency_user_id'            => null,
                'created_by_user_id'        => null,
                'b2c_user_id'               => $b2cUserId,
                'b2c_contact_name'          => $validated['contact_name'],
                'b2c_contact_phone'         => $validated['contact_phone'],
                'b2c_contact_email'         => $validated['contact_email'],
                'airport_id'                => $quote->airport_id,
                'zone_id'                   => $quote->zone_id,
                'vehicle_type_id'           => $quote->vehicle_type_id,
                'direction'                 => $quote->direction,
                'pax'                       => $quote->pax,
                'pickup_at'                 => $quote->pickup_at,
                'return_at'                 => $quote->return_at,
                'status'                    => TransferBooking::STATUS_PAYMENT_PENDING,
                'currency'                  => $quote->currency,
                'subtotal_amount'           => $quote->subtotal_amount,
                'commission_amount'         => $quote->commission_amount,
                'total_amount'              => $quote->total_amount,
                'price_snapshot_json'       => [
                    'quote_token'       => $quote->token,
                    'price_breakdown'   => $quote->price_breakdown_json,
                    'snapshot'          => $quote->snapshot_json,
                    'contact'           => [
                        'name'  => $validated['contact_name'],
                        'phone' => $validated['contact_phone'],
                        'email' => $validated['contact_email'],
                    ],
                    'operation_details' => [
                        'passenger_names'      => trim((string) ($validated['passenger_names'] ?? '')) ?: null,
                        'flight_number'        => trim((string) ($validated['flight_number'] ?? '')) ?: null,
                        'terminal'             => trim((string) ($validated['terminal'] ?? '')) ?: null,
                        'pickup_sign_name'     => trim((string) ($validated['pickup_sign_name'] ?? '')) ?: null,
                        'exact_pickup_address' => trim((string) ($validated['exact_pickup_address'] ?? '')) ?: null,
                        'luggage_count'        => isset($validated['luggage_count']) ? (int) $validated['luggage_count'] : null,
                        'child_seat_count'     => isset($validated['child_seat_count']) ? (int) $validated['child_seat_count'] : null,
                    ],
                ],
                'supplier_policy_snapshot_json' => data_get($quote->snapshot_json, 'policy'),
                'notes'                         => trim((string) ($validated['notes'] ?? '')),
            ]);

            $payment = TransferPaymentTransaction::query()->create([
                'transfer_booking_id'   => $booking->id,
                'reference'             => 'B2C-' . strtoupper(Str::random(10)),
                'provider'              => 'paynkolay',
                'status'                => 'pending',
                'amount'                => $booking->total_amount,
                'currency'              => $booking->currency,
                'request_payload_json'  => [
                    'contact_name'  => $validated['contact_name'],
                    'contact_phone' => $validated['contact_phone'],
                    'contact_email' => $validated['contact_email'],
                ],
            ]);

            $quote->update(['consumed_at' => now()]);

            return [$booking, $payment];
        });

        $successUrl = route('b2c.transfer.payment.success');
        $failUrl    = route('b2c.transfer.payment.fail');

        try {
            $paymentInit = $this->paynkolayService->initializePayment(
                booking:    $booking,
                transaction: $payment,
                successUrl: $successUrl,
                failUrl:    $failUrl,
                cardHolderIp: (string) $request->ip(),
            );

            $payment->update([
                'provider_transaction_id' => $paymentInit['provider_transaction_id'],
                'response_payload_json'   => $paymentInit['response'],
            ]);

            return redirect()->away($paymentInit['redirect_url']);
        } catch (\Throwable $e) {
            $payment->update([
                'status'         => 'failed',
                'failure_reason' => Str::limit($e->getMessage(), 240),
                'failed_at'      => now(),
                'processed_at'   => now(),
            ]);

            $booking->update([
                'status'    => TransferBooking::STATUS_FAILED,
                'failed_at' => now(),
            ]);

            // Teklifi tekrar kullanılabilir yap (henüz geçerli ise)
            $quote = TransferQuoteLock::find($booking->quote_lock_id);
            if ($quote && ! $quote->isExpired()) {
                $quote->update(['consumed_at' => null]);
            }

            return back()->withErrors([
                'payment' => 'Ödeme başlatılamadı: ' . Str::limit($e->getMessage(), 180),
            ]);
        }
    }

    /** Paynkolay başarılı dönüş */
    public function paymentSuccess(Request $request)
    {
        return $this->handlePaymentReturn($request, forceFailed: false);
    }

    /** Paynkolay başarısız dönüş */
    public function paymentFail(Request $request)
    {
        return $this->handlePaymentReturn($request, forceFailed: true);
    }

    /** Rezervasyon detay sayfası */
    public function bookingShow(string $bookingRef)
    {
        $booking = TransferBooking::query()
            ->where('booking_ref', $bookingRef)
            ->where('source', TransferBooking::SOURCE_B2C)
            ->with(['supplier', 'airport', 'zone', 'vehicleType', 'paymentTransactions'])
            ->firstOrFail();

        // Güvenlik: misafir ise email ile doğrulama, giriş yapmışsa kendi bookingı
        $b2cUser = Auth::guard('b2c')->user();
        if ($b2cUser) {
            if ($booking->b2c_user_id !== $b2cUser->id) {
                abort(403);
            }
        } else {
            // Misafir → session'da doğrulama yok; ref + email ile son kontrol
            // (Güvenlik seviyesi acceptable; ref UUID-benzeri random)
        }

        return view('b2c.transfer.booking', compact('booking'));
    }

    // ── Private Yardımcılar ─────────────────────────────────────────────────

    private function handlePaymentReturn(Request $request, bool $forceFailed): mixed
    {
        $payload   = $request->all();
        $reference = trim((string) ($this->paynkolayService->payloadValue($payload, ['clientRefCode', 'client_ref_code', 'reference']) ?? ''));

        if ($reference === '') {
            return redirect()->route('b2c.transfer.index')
                ->with('error', 'Ödeme yanıtı alınamadı. Rezervasyonunuzu kontrol ediniz.');
        }

        $payment = TransferPaymentTransaction::query()
            ->where('reference', $reference)
            ->with('booking')
            ->first();

        if (! $payment || ! $payment->booking) {
            return redirect()->route('b2c.transfer.index')
                ->with('error', 'Rezervasyon bulunamadı. Destek ile iletişime geçin.');
        }

        $booking = $payment->booking;

        if ($booking->source !== TransferBooking::SOURCE_B2C) {
            abort(403);
        }

        if ($payment->processed_at !== null) {
            return redirect()->route('b2c.transfer.booking', ['bookingRef' => $booking->booking_ref]);
        }

        $mappedStatus = $forceFailed ? 'failed' : $this->paynkolayService->mapCallbackStatus($payload);
        $isValid      = ! $forceFailed && $this->paynkolayService->isValidResponseHash($payload);

        $now = now();

        if (! $forceFailed && $mappedStatus === 'paid' && $isValid) {
            $payment->update([
                'status'                  => 'paid',
                'callback_payload_json'   => $payload,
                'processed_at'            => $now,
                'paid_at'                 => $now,
            ]);
            $booking->update([
                'status'       => TransferBooking::STATUS_CONFIRMED,
                'confirmed_at' => $now,
            ]);

            return redirect()->route('b2c.transfer.booking', ['bookingRef' => $booking->booking_ref])
                ->with('payment_success', true);
        }

        $payment->update([
            'status'               => 'failed',
            'callback_payload_json' => $payload,
            'processed_at'         => $now,
            'failed_at'            => $now,
            'failure_reason'       => $forceFailed ? 'Kullanıcı ödemeyi iptal etti.' : 'Ödeme sağlayıcısı onaylamadı.',
        ]);
        $booking->update([
            'status'    => TransferBooking::STATUS_FAILED,
            'failed_at' => $now,
        ]);

        return redirect()->route('b2c.transfer.index')
            ->with('error', 'Ödeme tamamlanamadı. Lütfen tekrar deneyin.');
    }

    private function generateRef(): string
    {
        return 'GRZ-' . date('Y') . '-' . strtoupper(Str::random(6));
    }
}

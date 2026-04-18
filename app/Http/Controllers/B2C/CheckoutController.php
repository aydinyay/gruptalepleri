<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cOrder;
use App\Models\B2C\B2cPayment;
use App\Models\B2C\CatalogSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Ödeme akışı — Faz 6'da B2cPaynkolayService entegre edilecek.
 * Şu an sipariş oluşturma ve callback iskelet kodu.
 */
class CheckoutController extends Controller
{
    public function show()
    {
        $cart = session('b2c_cart', []);
        if (empty($cart)) {
            return redirect()->route('b2c.cart.index')->with('error', 'Sepetiniz boş.');
        }

        return view('b2c.checkout.show', compact('cart'));
    }

    public function create(Request $request)
    {
        $cart = session('b2c_cart', []);
        if (empty($cart)) {
            return redirect()->route('b2c.cart.index');
        }

        $user = Auth::guard('b2c')->user();

        // Şimdilik ilk sepet öğesinden tek sipariş oluşturuyoruz
        // Faz 5'te çok ürünlü sipariş mantığı eklenecek
        $row  = array_values($cart)[0];

        $session      = null;
        $sessionLabel = null;
        if (! empty($row['session_id'])) {
            $session = CatalogSession::find($row['session_id']);
            if ($session) {
                $sessionLabel = $session->label
                    ?? ($session->session_date->format('d.m.Y') . ($session->session_time ? ' ' . substr($session->session_time, 0, 5) : ''));
            }
        }

        $order = B2cOrder::create([
            'order_ref'       => 'GRZ-' . date('Y') . '-' . strtoupper(Str::random(6)),
            'b2c_user_id'     => $user->id,
            'catalog_item_id' => $row['catalog_item_id'],
            'session_id'      => $session?->id,
            'session_label'   => $sessionLabel,
            'pax_count'       => $row['pax_count'],
            'service_date'    => $row['service_date'] ?? null,
            'unit_price'      => $row['base_price'],
            'total_price'     => ($row['base_price'] ?? 0) * $row['pax_count'],
            'currency'        => $row['currency'] ?? 'TRY',
            'status'          => $row['pricing_type'] === 'fixed' ? 'pending' : 'pending_quote',
            'payment_status'  => 'unpaid',
        ]);

        if ($session) {
            $session->increment('booked_count', (int) $row['pax_count']);
        }

        // Sepeti temizle
        session()->forget('b2c_cart');

        // Faz 6: Paynkolay ödeme başlatma buraya eklenecek
        // Şimdilik sipariş detay sayfasına yönlendir
        return redirect()->route('b2c.account.orders.show', $order->order_ref)
            ->with('success', 'Siparişiniz oluşturuldu. Referans: ' . $order->order_ref);
    }

    public function success(Request $request)
    {
        // Faz 6: Paynkolay hash doğrulama + sipariş durum güncelleme
        return view('b2c.checkout.success');
    }

    public function fail(Request $request)
    {
        return view('b2c.checkout.fail');
    }

    public function paynkolaySuccess(Request $request)
    {
        // Faz 6: PaynkolayGatewayService->isValidResponseHash() kontrolü yapılacak
        // + B2cOrder status → confirmed güncelleme
        // + B2cPayment status → paid güncelleme
        // + FinanceSyncService entegrasyon
        return redirect()->route('b2c.checkout.success');
    }

    public function paynkolayFail(Request $request)
    {
        return redirect()->route('b2c.checkout.fail');
    }
}

<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cOrder;
use App\Models\B2C\CatalogItem;
use App\Models\B2C\CatalogSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestBookingController extends Controller
{
    public function book(Request $request, string $slug)
    {
        $item = CatalogItem::published()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'guest_name'   => 'required|string|max:120',
            'guest_phone'  => 'required|string|max:30',
            'guest_email'  => 'nullable|email|max:180',
            'session_id'   => 'nullable|integer|exists:catalog_sessions,id',
            'service_date' => 'nullable|date|after_or_equal:today',
            'pax_count'    => 'required|integer|min:1|max:500',
            'event_type'   => 'nullable|string|max:120',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $session      = null;
        $sessionLabel = null;
        $unitPrice    = (float) ($item->base_price ?? 0);

        if (! empty($validated['session_id'])) {
            $session = CatalogSession::find($validated['session_id']);

            if ($session && $session->isFull()) {
                return back()->withErrors(['session_id' => 'Seçtiğiniz seans dolu, lütfen başka bir seans seçin.']);
            }

            if ($session) {
                $sessionLabel = $session->label
                    ?? ($session->session_date->format('d.m.Y') . ($session->session_time ? ' ' . substr($session->session_time, 0, 5) : ''));

                if ($session->price_override !== null) {
                    $unitPrice = (float) $session->price_override;
                }

                if (! $validated['service_date']) {
                    $validated['service_date'] = $session->session_date->format('Y-m-d');
                }
            }
        }

        $isFixed     = $item->pricing_type === 'fixed' && $unitPrice > 0;
        $isPerFlight = $item->product_type === 'charter';
        $total       = $isFixed
            ? ($isPerFlight
                ? round($unitPrice, 2)
                : round($unitPrice * (int) $validated['pax_count'], 2))
            : null;
        $status    = $isFixed ? 'pending' : 'pending_quote';

        $ref = 'GBO-' . strtoupper(Str::random(8));

        $order = B2cOrder::create([
            'order_ref'       => $ref,
            'b2c_user_id'     => null,
            'catalog_item_id' => $item->id,
            'session_id'      => $session?->id,
            'session_label'   => $sessionLabel,
            'item_title'      => $item->title,
            'product_type'    => $item->product_type,
            'guest_name'      => $validated['guest_name'],
            'guest_phone'     => $validated['guest_phone'],
            'guest_email'     => $validated['guest_email'] ?? null,
            'status'          => $status,
            'pax_count'       => (int) $validated['pax_count'],
            'service_date'    => $validated['service_date'] ?? null,
            'event_type'      => $validated['event_type'] ?? null,
            'notes'           => $validated['notes'] ?? null,
            'unit_price'      => $isFixed ? $unitPrice : null,
            'total_price'     => $total,
            'currency'        => $item->currency ?: 'TRY',
            'payment_status'  => 'unpaid',
        ]);

        if ($session) {
            $session->increment('booked_count', (int) $validated['pax_count']);
        }

        return redirect()->route('b2c.guest.booking.show', $order->order_ref);
    }

    public function show(string $ref)
    {
        $order = B2cOrder::where('order_ref', $ref)
            ->whereNull('b2c_user_id')
            ->with(['item.category', 'payments'])
            ->firstOrFail();

        return view('b2c.product.order-confirm', compact('order'));
    }
}

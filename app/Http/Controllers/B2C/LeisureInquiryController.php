<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\LeisurePackageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeisureInquiryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_code'   => 'required|string|max:60',
            'service_date'   => 'required|date|after_or_equal:today',
            'guest_count'    => 'required|integer|min:1|max:500',
            'duration_hours' => 'required|integer|min:1|max:24',
            'start_time'     => 'nullable|string|max:10',
            'event_type'     => 'nullable|string|max:120',
            'guest_name'     => 'required|string|max:120',
            'guest_phone'    => 'required|string|max:30',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $package = LeisurePackageTemplate::where('code', $validated['package_code'])
            ->where('is_active', true)
            ->first();

        $price    = (float) ($package?->original_price_per_person ?? $package?->base_price_per_person ?? 0);
        $currency = $package?->currency ?: 'EUR';
        $total    = $price * (int) $validated['duration_hours'];

        $notes = implode(' | ', array_filter([
            'Yat: ' . ($package?->name_tr ?? $validated['package_code']),
            'Tarih: ' . \Carbon\Carbon::parse($validated['service_date'])->format('d.m.Y'),
            'Kişi: ' . $validated['guest_count'],
            'Süre: ' . $validated['duration_hours'] . ' saat',
            $validated['start_time'] ? 'Kalkış: ' . $validated['start_time'] : null,
            $validated['event_type'] ? 'Etkinlik: ' . $validated['event_type'] : null,
            $validated['notes'] ? 'Not: ' . $validated['notes'] : null,
            $total > 0 ? 'Tahmini: ' . number_format($total, 0, ',', '.') . ' ' . $currency : null,
        ]));

        $leadId = DB::table('b2c_quick_leads')->insertGetId([
            'name'         => $validated['guest_name'],
            'phone'        => $validated['guest_phone'],
            'service_type' => 'yacht_charter',
            'notes'        => $notes,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Onay sayfası için session'a detayları koy
        session()->flash('leisure_inquiry', [
            'ref'            => 'YC-B2C-' . str_pad($leadId, 5, '0', STR_PAD_LEFT),
            'package_name'   => $package?->name_tr ?? $validated['package_code'],
            'service_date'   => \Carbon\Carbon::parse($validated['service_date'])->format('d.m.Y'),
            'guest_count'    => $validated['guest_count'],
            'duration_hours' => $validated['duration_hours'],
            'start_time'     => $validated['start_time'] ?? null,
            'event_type'     => $validated['event_type'] ?? null,
            'guest_name'     => $validated['guest_name'],
            'guest_phone'    => $validated['guest_phone'],
            'total'          => $total > 0 ? number_format($total, 0, ',', '.') . ' ' . $currency : null,
            'includes'       => $package?->includes_tr ?? [],
            'pier_name'      => $package?->pier_name ?? null,
        ]);

        return redirect()->route('b2c.leisure.inquiry.confirm');
    }

    public function confirm()
    {
        $inquiry = session('leisure_inquiry');

        if (! $inquiry) {
            return redirect()->route('b2c.home');
        }

        return view('b2c.product.leisure-confirm', compact('inquiry'));
    }
}

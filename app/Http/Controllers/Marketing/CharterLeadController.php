<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\CharterRequest;
use App\Services\Charter\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CharterLeadController extends Controller
{
    public function jet()
    {
        return view('charter.public-form', [
            'transportType' => CharterRequest::TYPE_JET,
            'pageTitle' => 'Ozel Jet Kiralama Talebi',
        ]);
    }

    public function helicopter()
    {
        return view('charter.public-form', [
            'transportType' => CharterRequest::TYPE_HELICOPTER,
            'pageTitle' => 'Helikopter Kiralama Talebi',
        ]);
    }

    public function airliner()
    {
        return view('charter.public-form', [
            'transportType' => CharterRequest::TYPE_AIRLINER,
            'pageTitle' => 'Charter Ucak Talebi',
        ]);
    }

    public function store(Request $request, AiService $aiService): RedirectResponse
    {
        $validated = $request->validate([
            'transport_type' => 'required|in:jet,helicopter,airliner',
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'from_iata' => 'required|string|max:10',
            'to_iata' => 'required|string|max:10',
            'departure_date' => 'required|date|after_or_equal:today',
            'pax' => 'required|integer|min:1|max:400',
            'notes' => 'nullable|string|max:2000',
        ]);

        $charterRequest = CharterRequest::query()->create([
            'requester_type' => 'public',
            'transport_type' => $validated['transport_type'],
            'status' => CharterRequest::STATUS_LEAD,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'from_iata' => strtoupper(trim($validated['from_iata'])),
            'to_iata' => strtoupper(trim($validated['to_iata'])),
            'departure_date' => $validated['departure_date'],
            'pax' => $validated['pax'],
            'notes' => $validated['notes'] ?? null,
        ]);

        try {
            // onRequestCreated() => AI pre quote
            $charterRequest->loadMissing(['jetDetail', 'helicopterDetail', 'airlinerDetail']);
            $aiService->buildPreQuote($charterRequest);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Talebiniz alindi. Ekibimiz kisa surede sizinle iletisime gececek.');
    }
}

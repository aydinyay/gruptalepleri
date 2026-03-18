<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\CharterAirlinerRequest;
use App\Models\CharterBooking;
use App\Models\CharterExtra;
use App\Models\CharterHelicopterRequest;
use App\Models\CharterJetRequest;
use App\Models\CharterRequest;
use App\Models\CharterSalesQuote;
use App\Services\Charter\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CharterRequestController extends Controller
{
    public function create()
    {
        return view('acente.charter.create');
    }

    public function store(Request $request, AiService $aiService): RedirectResponse
    {
        $validated = $request->validate([
            'transport_type' => 'required|in:jet,helicopter,airliner',
            'from_iata' => 'required|string|max:10',
            'to_iata' => 'required|string|max:10',
            'departure_date' => 'required|date|after_or_equal:today',
            'pax' => 'required|integer|min:1|max:400',
            'is_flexible' => 'nullable|boolean',
            'group_type' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:4000',

            'jet.flight_hours_estimate' => 'nullable|integer|min:0|max:1000',
            'jet.round_trip' => 'nullable|boolean',
            'jet.return_date' => 'nullable|date|after_or_equal:departure_date',
            'jet.pet_onboard' => 'nullable|boolean',
            'jet.vip_catering' => 'nullable|boolean',
            'jet.wifi_required' => 'nullable|boolean',
            'jet.special_luggage' => 'nullable|boolean',
            'jet.luggage_count' => 'nullable|integer|min:0|max:100',
            'jet.cabin_preference' => 'nullable|string|max:120',
            'jet.airport_slot_note' => 'nullable|string|max:255',
            'jet.specs_json' => 'nullable|string|max:5000',

            'helicopter.pickup' => 'nullable|string|max:255',
            'helicopter.dropoff' => 'nullable|string|max:255',
            'helicopter.landing_details' => 'nullable|string|max:2000',

            'airliner.date_flexible' => 'nullable|boolean',
            'airliner.group_type' => 'nullable|string|max:120',
            'airliner.route_notes' => 'nullable|string|max:2000',

            'extras' => 'nullable|array',
            'extras.*.title' => 'nullable|string|max:120',
            'extras.*.agency_note' => 'nullable|string|max:1000',
        ]);

        if (
            ($validated['transport_type'] ?? '') === CharterRequest::TYPE_JET
            && (bool) ($validated['jet']['round_trip'] ?? false)
            && empty($validated['jet']['return_date'])
        ) {
            return back()
                ->withErrors(['jet.return_date' => 'Gidiş - dönüş seçiminde dönüş tarihi zorunludur.'])
                ->withInput();
        }

        $charterRequest = DB::transaction(function () use ($validated) {
            $charterRequest = CharterRequest::query()->create([
                'user_id' => auth()->id(),
                'requester_type' => 'agency',
                'transport_type' => $validated['transport_type'],
                'status' => CharterRequest::STATUS_LEAD,
                'name' => auth()->user()->name,
                'phone' => auth()->user()->phone,
                'email' => auth()->user()->email,
                'from_iata' => strtoupper(trim($validated['from_iata'])),
                'to_iata' => strtoupper(trim($validated['to_iata'])),
                'departure_date' => $validated['departure_date'],
                'pax' => $validated['pax'],
                'is_flexible' => (bool) ($validated['is_flexible'] ?? false),
                'group_type' => $validated['group_type'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($validated['transport_type'] === CharterRequest::TYPE_JET) {
                $specsJson = [];
                $rawSpecsJson = $validated['jet']['specs_json'] ?? null;
                if (is_string($rawSpecsJson) && trim($rawSpecsJson) !== '') {
                    $decodedSpecs = json_decode($rawSpecsJson, true);
                    if (is_array($decodedSpecs)) {
                        $specsJson = $decodedSpecs;
                    }
                }

                if (! empty($validated['jet']['return_date'])) {
                    $specsJson['return_date'] = $validated['jet']['return_date'];
                }

                CharterJetRequest::query()->create([
                    'charter_request_id' => $charterRequest->id,
                    'flight_hours_estimate' => $validated['jet']['flight_hours_estimate'] ?? null,
                    'round_trip' => (bool) ($validated['jet']['round_trip'] ?? false),
                    'pet_onboard' => (bool) ($validated['jet']['pet_onboard'] ?? false),
                    'vip_catering' => (bool) ($validated['jet']['vip_catering'] ?? false),
                    'wifi_required' => (bool) ($validated['jet']['wifi_required'] ?? false),
                    'special_luggage' => (bool) ($validated['jet']['special_luggage'] ?? false),
                    'luggage_count' => $validated['jet']['luggage_count'] ?? null,
                    'cabin_preference' => $validated['jet']['cabin_preference'] ?? null,
                    'airport_slot_note' => $validated['jet']['airport_slot_note'] ?? null,
                    'specs_json' => ! empty($specsJson) ? $specsJson : null,
                ]);
            } elseif ($validated['transport_type'] === CharterRequest::TYPE_HELICOPTER) {
                CharterHelicopterRequest::query()->create([
                    'charter_request_id' => $charterRequest->id,
                    'pickup' => $validated['helicopter']['pickup'] ?? null,
                    'dropoff' => $validated['helicopter']['dropoff'] ?? null,
                    'landing_details' => $validated['helicopter']['landing_details'] ?? null,
                ]);
            } else {
                CharterAirlinerRequest::query()->create([
                    'charter_request_id' => $charterRequest->id,
                    'date_flexible' => (bool) ($validated['airliner']['date_flexible'] ?? false),
                    'group_type' => $validated['airliner']['group_type'] ?? null,
                    'route_notes' => $validated['airliner']['route_notes'] ?? null,
                ]);
            }

            foreach (($validated['extras'] ?? []) as $extra) {
                $title = trim((string) ($extra['title'] ?? ''));
                $note = trim((string) ($extra['agency_note'] ?? ''));
                if ($title === '' && $note === '') {
                    continue;
                }

                CharterExtra::query()->create([
                    'charter_request_id' => $charterRequest->id,
                    'title' => $title !== '' ? $title : 'Ek Hizmet',
                    'agency_note' => $note !== '' ? $note : null,
                    'status' => 'pending_pricing',
                ]);
            }

            return $charterRequest;
        });

        try {
            $charterRequest->load(['jetDetail', 'helicopterDetail', 'airlinerDetail']);
            $aiService->buildPreQuote($charterRequest);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('acente.charter.show', $charterRequest)
            ->with('success', 'Air Charter talebiniz olusturuldu. On teklif hesaplandi.');
    }

    public function show(CharterRequest $charterRequest)
    {
        abort_unless($charterRequest->user_id === auth()->id(), 403);

        $charterRequest->load([
            'jetDetail',
            'helicopterDetail',
            'airlinerDetail',
            'extras',
            'salesQuotes.supplierQuote',
            'booking.payments',
        ]);

        return view('acente.charter.show', compact('charterRequest'));
    }

    public function acceptSalesQuote(CharterRequest $charterRequest, CharterSalesQuote $salesQuote): RedirectResponse
    {
        abort_unless($charterRequest->user_id === auth()->id(), 403);
        abort_unless($salesQuote->charter_request_id === $charterRequest->id, 422);

        DB::transaction(function () use ($charterRequest, $salesQuote) {
            $charterRequest->salesQuotes()->where('id', '!=', $salesQuote->id)->update(['status' => 'rejected']);
            $salesQuote->update(['status' => 'accepted']);

            CharterBooking::query()->updateOrCreate(
                ['charter_request_id' => $charterRequest->id],
                [
                    'sales_quote_id' => $salesQuote->id,
                    'status' => 'pending_payment',
                    'total_amount' => $salesQuote->sale_price,
                    'total_paid' => 0,
                    'remaining_amount' => $salesQuote->sale_price,
                ]
            );

            $charterRequest->update(['status' => CharterRequest::STATUS_PENDING_PAYMENT]);
        });

        return back()->with('success', 'Teklif kabul edildi. Odeme surecine gecildi.');
    }
}

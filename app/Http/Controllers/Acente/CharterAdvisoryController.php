<?php

namespace App\Http\Controllers\Acente;

use App\Http\Controllers\Controller;
use App\Models\CharterRequest;
use App\Services\Charter\AdvisoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharterAdvisoryController extends Controller
{
    public function __invoke(Request $request, AdvisoryService $advisoryService): JsonResponse
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'transport_type' => 'required|in:jet,helicopter,airliner',
            'from_iata' => 'nullable|string|max:10',
            'to_iata' => 'nullable|string|max:10',
            'departure_date' => 'nullable|date',
            'pax' => 'nullable|integer|min:0|max:400',
            'is_flexible' => 'nullable|boolean',

            'jet' => 'nullable|array',
            'jet.flight_hours_estimate' => 'nullable|integer|min:0|max:1000',
            'jet.round_trip' => 'nullable|boolean',
            'jet.return_date' => 'nullable|date',
            'jet.different_return_route' => 'nullable|boolean',
            'jet.return_from_iata' => 'nullable|string|max:10',
            'jet.return_to_iata' => 'nullable|string|max:10',
            'jet.multi_leg' => 'nullable|boolean',
            'jet.segments_count' => 'nullable|integer|min:0|max:20',
            'jet.luggage_count' => 'nullable|integer|min:0|max:100',
            'jet.cabin_preference' => 'nullable|string|max:120',

            'helicopter' => 'nullable|array',
            'helicopter.pickup' => 'nullable|string|max:255',
            'helicopter.dropoff' => 'nullable|string|max:255',
            'helicopter.landing_details' => 'nullable|string|max:2000',

            'airliner' => 'nullable|array',
            'airliner.group_type' => 'nullable|string|max:120',
            'airliner.route_notes' => 'nullable|string|max:2000',
        ]);

        if (($validated['transport_type'] ?? '') === '') {
            $validated['transport_type'] = CharterRequest::TYPE_JET;
        }

        return response()->json($advisoryService->build($validated));
    }
}

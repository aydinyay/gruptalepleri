<?php

namespace App\Http\Controllers;

use App\Models\Airline;
use App\Models\Airport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    /**
     * Havalimanı arama — GET /airports/search?q=IST
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $airports = Airport::search($q, 12);

        return response()->json(
            $airports->map(fn($a) => [
                'iata'    => $a->iata,
                'label'   => $a->displayLabel(),
                'city'    => $a->city,
                'country' => $a->country_tr ?: $a->country,
                'name'    => $a->name,
            ])
        );
    }

    /**
     * Havayolu arama — GET /airlines/search?q=TK
     */
    public function airlineSearch(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $airlines = Airline::search($q, 10);

        return response()->json(
            $airlines->map(fn($a) => [
                'iata'    => $a->iata,
                'icao'    => $a->icao,
                'name'    => $a->name,
                'country' => $a->country_tr ?: $a->country,
                'label'   => $a->displayLabel(),
            ])
        );
    }
}

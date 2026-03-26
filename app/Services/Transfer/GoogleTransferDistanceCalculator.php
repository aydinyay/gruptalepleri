<?php

namespace App\Services\Transfer;

use App\Services\Transfer\Contracts\TransferDistanceCalculator;
use Illuminate\Support\Facades\Http;

class GoogleTransferDistanceCalculator implements TransferDistanceCalculator
{
    public function between(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        $apiKey = trim((string) config('transfer.google_maps.api_key'));
        $distanceMatrixUrl = trim((string) config('transfer.google_maps.distance_matrix_url'));
        $timeout = max(5, (int) config('transfer.google_maps.timeout', 20));

        if ($apiKey !== '' && $distanceMatrixUrl !== '') {
            $response = Http::timeout($timeout)->get($distanceMatrixUrl, [
                'origins' => $originLat . ',' . $originLng,
                'destinations' => $destLat . ',' . $destLng,
                'mode' => 'driving',
                'language' => 'tr',
                'key' => $apiKey,
            ]);

            if ($response->ok()) {
                $json = $response->json();
                $element = data_get($json, 'rows.0.elements.0', []);
                if (is_array($element) && (string) ($element['status'] ?? '') === 'OK') {
                    $meters = (float) data_get($element, 'distance.value', 0);
                    $seconds = (int) data_get($element, 'duration.value', 0);
                    if ($meters > 0 && $seconds > 0) {
                        return [
                            'distance_km' => round($meters / 1000, 2),
                            'duration_minutes' => max(1, (int) ceil($seconds / 60)),
                        ];
                    }
                }
            }
        }

        return $this->fallbackHaversine($originLat, $originLng, $destLat, $destLng);
    }

    /**
     * Maps API olmadiginda dongu bozulmasin diye aproximasyon fallback.
     *
     * @return array{distance_km:float,duration_minutes:int}
     */
    private function fallbackHaversine(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        $earthRadius = 6371;

        $latFrom = deg2rad($originLat);
        $lonFrom = deg2rad($originLng);
        $latTo = deg2rad($destLat);
        $lonTo = deg2rad($destLng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2)
            + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        $airDistanceKm = $angle * $earthRadius;
        $roadFactor = 1.35;
        $distanceKm = max(1, round($airDistanceKm * $roadFactor, 2));
        $durationMinutes = max(5, (int) ceil(($distanceKm / 35) * 60));

        return [
            'distance_km' => $distanceKm,
            'duration_minutes' => $durationMinutes,
        ];
    }
}


<?php

namespace App\Services\Charter;

class HelicopterPricingService
{
    /**
     * @return array{model:string,min:float,max:float,currency:string,risk_flags:array<int,string>}
     */
    public function estimate(int $pax, string $fromIata = '', string $toIata = '', ?string $pickup = null, ?string $dropoff = null): array
    {
        $pax = max(1, $pax);
        $model = $pax <= 4 ? 'Airbus H125' : ($pax <= 6 ? 'Bell 407' : 'Airbus H145');

        $base = 6500 + ($pax * 850);
        if (! empty($pickup) && ! empty($dropoff)) {
            $base += 2500;
        }
        if ($fromIata !== '' && $toIata !== '' && strtoupper($fromIata) !== strtoupper($toIata)) {
            $base += 1800;
        }

        $min = round($base * 0.9, 2);
        $max = round($base * 1.2, 2);

        $flags = [];
        if ($pax > 6) {
            $flags[] = 'Yuksek PAX icin slot ve agirlik dogrulamasi gerekir.';
        }
        if (empty($pickup) || empty($dropoff)) {
            $flags[] = 'Pickup/Dropoff eksik, yer operasyonu teyidi gerekiyor.';
        }

        return [
            'model' => $model,
            'min' => $min,
            'max' => $max,
            'currency' => (string) config('charter.currency', 'EUR'),
            'risk_flags' => $flags,
        ];
    }
}

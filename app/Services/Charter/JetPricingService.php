<?php

namespace App\Services\Charter;

class JetPricingService
{
    /**
     * @return array{model:string,min:float,max:float,currency:string,risk_flags:array<int,string>}
     */
    public function estimate(int $pax, string $fromIata = '', string $toIata = ''): array
    {
        $pax = max(1, $pax);
        $model = $pax <= 6 ? 'Phenom 300' : ($pax <= 9 ? 'Citation Latitude' : 'Legacy 650');

        $base = 18000 + ($pax * 1400);
        if ($fromIata !== '' && $toIata !== '' && strtoupper($fromIata) !== strtoupper($toIata)) {
            $base += 6000;
        }

        $min = round($base * 0.92, 2);
        $max = round($base * 1.18, 2);

        $flags = [];
        if ($pax > 10) {
            $flags[] = 'Yolcu sayisi jet kapasite sinirina yaklasiyor.';
        }
        if ($fromIata === '' || $toIata === '') {
            $flags[] = 'Parkur eksik; fiyat araligi muhafazakar hesaplandi.';
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

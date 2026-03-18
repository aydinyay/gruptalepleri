<?php

namespace App\Services\Charter;

class AirlinerPricingService
{
    /**
     * @return array{model:string,min:float,max:float,currency:string,risk_flags:array<int,string>}
     */
    public function estimate(int $pax, string $fromIata = '', string $toIata = '', bool $isFlexible = false): array
    {
        $pax = max(1, $pax);
        $model = $pax <= 70 ? 'Embraer E190' : ($pax <= 150 ? 'Airbus A320' : 'Boeing 737-800');

        $base = 22000 + ($pax * 320);
        if ($fromIata !== '' && $toIata !== '' && strtoupper($fromIata) !== strtoupper($toIata)) {
            $base += 8500;
        }
        if (! $isFlexible) {
            $base += 2500;
        }

        $min = round($base * 0.93, 2);
        $max = round($base * 1.17, 2);

        $flags = [];
        if ($pax >= 140) {
            $flags[] = 'Yuksek PAX icin slot ve turnaround plani kritik.';
        }
        if (! $isFlexible) {
            $flags[] = 'Esnek tarih yok; fiyat volatilitesi artabilir.';
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

<?php

namespace App\Services\Charter;

use App\Models\Airport;
use App\Models\CharterRequest;

class AdvisoryService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function build(array $payload): array
    {
        $transportType = (string) ($payload['transport_type'] ?? CharterRequest::TYPE_JET);
        $fromIata = strtoupper(trim((string) ($payload['from_iata'] ?? '')));
        $toIata = strtoupper(trim((string) ($payload['to_iata'] ?? '')));
        $departureDate = (string) ($payload['departure_date'] ?? '');
        $pax = (int) ($payload['pax'] ?? 0);
        $isFlexible = filter_var($payload['is_flexible'] ?? false, FILTER_VALIDATE_BOOL);

        $category = $this->resolveCategory($transportType, $pax);
        $duration = $this->estimateDuration($transportType, $fromIata, $toIata);

        $quality = $this->resolvePreparationStatus($transportType, $payload, $fromIata, $toIata, $departureDate, $pax);
        $operational = $this->resolveOperationalStatus($transportType, $payload, $fromIata, $toIata, $departureDate, $pax, $isFlexible);
        $suggestions = $this->buildMissingSuggestions($transportType, $payload, $fromIata, $toIata, $departureDate, $pax);

        return [
            'category' => $category,
            'duration' => $duration,
            'preparation_status' => $quality,
            'operational_status' => $operational,
            'missing_suggestions' => $suggestions,
            'confidence_text' => 'Talebiniz birden fazla operatör tarafından değerlendirilir.',
            'timeline' => [
                ['key' => 'request_create', 'title' => 'Talep Oluşturma', 'is_active' => true],
                ['key' => 'operator_eval', 'title' => 'Operatör Değerlendirmesi', 'is_active' => false],
                ['key' => 'offer_collect', 'title' => 'Tekliflerin Toplanması', 'is_active' => false],
                ['key' => 'offer_present', 'title' => 'Size Sunum', 'is_active' => false],
            ],
            'disclaimer' => 'Bu panel karar destek amaçlıdır. Nihai şartlar operatör değerlendirmesi sonrası netleşir.',
        ];
    }

    private function resolveCategory(string $transportType, int $pax): string
    {
        $pax = max(1, $pax);

        return match ($transportType) {
            CharterRequest::TYPE_HELICOPTER => $pax <= 4
                ? 'Hafif Helikopter'
                : ($pax <= 6 ? 'Orta Helikopter' : 'Yüksek Kapasiteli Helikopter'),
            CharterRequest::TYPE_AIRLINER => $pax <= 70
                ? 'Bölgesel Jet'
                : ($pax <= 180 ? 'Dar Gövde Uçak' : 'Yüksek Kapasiteli Uçak'),
            default => $pax <= 6
                ? 'Light Jet'
                : ($pax <= 9 ? 'Midsize Jet' : 'Heavy Jet'),
        };
    }

    /**
     * @return array{label:string,minutes:int|null}
     */
    private function estimateDuration(string $transportType, string $fromIata, string $toIata): array
    {
        if ($fromIata === '' || $toIata === '') {
            return [
                'label' => 'Rota bilgisi girildiğinde hesaplanır',
                'minutes' => null,
            ];
        }

        if ($fromIata === $toIata) {
            return [
                'label' => 'Kalkış ve varış aynı görünüyor, lütfen rota bilgisini kontrol edin',
                'minutes' => null,
            ];
        }

        $from = Airport::query()->where('iata', $fromIata)->first(['latitude', 'longitude']);
        $to = Airport::query()->where('iata', $toIata)->first(['latitude', 'longitude']);

        if (! $from || ! $to || ! is_numeric($from->latitude) || ! is_numeric($from->longitude) || ! is_numeric($to->latitude) || ! is_numeric($to->longitude)) {
            return [
                'label' => 'Rota doğrulaması sonrası süre netleşir',
                'minutes' => null,
            ];
        }

        $distanceKm = $this->haversineDistanceKm(
            (float) $from->latitude,
            (float) $from->longitude,
            (float) $to->latitude,
            (float) $to->longitude
        );

        $speed = match ($transportType) {
            CharterRequest::TYPE_HELICOPTER => 220.0,
            CharterRequest::TYPE_AIRLINER => 820.0,
            default => 760.0,
        };

        $bufferMinutes = match ($transportType) {
            CharterRequest::TYPE_HELICOPTER => 15,
            CharterRequest::TYPE_AIRLINER => 35,
            default => 20,
        };

        $minutes = (int) round((($distanceKm / $speed) * 60) + $bufferMinutes);

        return [
            'label' => $this->formatDuration($minutes),
            'minutes' => $minutes,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{label:string,color:string}
     */
    private function resolvePreparationStatus(string $transportType, array $payload, string $fromIata, string $toIata, string $departureDate, int $pax): array
    {
        $criticalMissing = 0;
        if ($fromIata === '') {
            $criticalMissing++;
        }
        if ($toIata === '') {
            $criticalMissing++;
        }
        if ($departureDate === '') {
            $criticalMissing++;
        }
        if ($pax <= 0) {
            $criticalMissing++;
        }

        if ($criticalMissing >= 2) {
            return ['label' => 'Eksik Bilgi Var', 'color' => 'danger'];
        }

        $detailScore = 0;
        if ($transportType === CharterRequest::TYPE_JET) {
            if (! empty($payload['jet']['cabin_preference'])) {
                $detailScore++;
            }
            if (! empty($payload['jet']['luggage_count'])) {
                $detailScore++;
            }
            if (filter_var($payload['jet']['round_trip'] ?? false, FILTER_VALIDATE_BOOL) && ! empty($payload['jet']['return_date'])) {
                $detailScore++;
            }
            if (
                filter_var($payload['jet']['different_return_route'] ?? false, FILTER_VALIDATE_BOOL)
                && ! empty($payload['jet']['return_from_iata'])
                && ! empty($payload['jet']['return_to_iata'])
            ) {
                $detailScore++;
            }
            if (
                filter_var($payload['jet']['multi_leg'] ?? false, FILTER_VALIDATE_BOOL)
                && (int) ($payload['jet']['segments_count'] ?? 0) > 0
            ) {
                $detailScore++;
            }
        } elseif ($transportType === CharterRequest::TYPE_HELICOPTER) {
            if (! empty($payload['helicopter']['pickup'])) {
                $detailScore++;
            }
            if (! empty($payload['helicopter']['dropoff'])) {
                $detailScore++;
            }
            if (! empty($payload['helicopter']['landing_details'])) {
                $detailScore++;
            }
        } else {
            if (! empty($payload['airliner']['group_type'])) {
                $detailScore++;
            }
            if (! empty($payload['airliner']['route_notes'])) {
                $detailScore++;
            }
            if (! empty($payload['is_flexible'])) {
                $detailScore++;
            }
        }

        if ($criticalMissing === 0 && $detailScore >= 2) {
            return ['label' => 'Yüksek Hazırlık', 'color' => 'success'];
        }

        if ($criticalMissing > 0) {
            return ['label' => 'Eksik Bilgi Var', 'color' => 'danger'];
        }

        return ['label' => 'Geliştirilebilir', 'color' => 'warning'];
    }

    /**
     * @return array{label:string,color:string}
     */
    private function resolveOperationalStatus(string $transportType, array $payload, string $fromIata, string $toIata, string $departureDate, int $pax, bool $isFlexible): array
    {
        $score = 100;

        if ($fromIata === '' || $toIata === '') {
            $score -= 35;
        }
        if ($departureDate === '') {
            $score -= 25;
        }
        if ($pax <= 0) {
            $score -= 40;
        }

        if ($transportType === CharterRequest::TYPE_HELICOPTER && $pax > 6) {
            $score -= 20;
        }

        if ($transportType === CharterRequest::TYPE_JET && $pax > 12) {
            $score -= 20;
        }

        if (
            $transportType === CharterRequest::TYPE_JET
            && filter_var($payload['jet']['round_trip'] ?? false, FILTER_VALIDATE_BOOL)
            && empty($payload['jet']['return_date'])
        ) {
            $score -= 30;
        }
        if (
            $transportType === CharterRequest::TYPE_JET
            && filter_var($payload['jet']['different_return_route'] ?? false, FILTER_VALIDATE_BOOL)
            && (empty($payload['jet']['return_from_iata']) || empty($payload['jet']['return_to_iata']))
        ) {
            $score -= 30;
        }
        if (
            $transportType === CharterRequest::TYPE_JET
            && filter_var($payload['jet']['multi_leg'] ?? false, FILTER_VALIDATE_BOOL)
            && (int) ($payload['jet']['segments_count'] ?? 0) <= 0
        ) {
            $score -= 30;
        }

        if ($transportType === CharterRequest::TYPE_AIRLINER && ! $isFlexible) {
            $score -= 10;
        }

        if ($score >= 75) {
            return ['label' => 'Uygun', 'color' => 'success'];
        }

        if ($score >= 50) {
            return ['label' => 'Kontrol Gerekli', 'color' => 'warning'];
        }

        return ['label' => 'Ek Doğrulama Gerekli', 'color' => 'danger'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function buildMissingSuggestions(string $transportType, array $payload, string $fromIata, string $toIata, string $departureDate, int $pax): array
    {
        $messages = [];

        if ($fromIata === '') {
            $messages[] = 'Kalkış noktasını şehir, havalimanı veya IATA kodu ile netleştirin.';
        }
        if ($toIata === '') {
            $messages[] = 'Varış noktasını şehir, havalimanı veya IATA kodu ile netleştirin.';
        }
        if ($departureDate === '') {
            $messages[] = 'Planlanan uçuş tarihini ekleyin.';
        }
        if ($pax <= 0) {
            $messages[] = 'Toplam yolcu sayısını girin.';
        }

        if ($transportType === CharterRequest::TYPE_JET && empty($payload['jet']['cabin_preference'])) {
            $messages[] = 'Jet taleplerinde uçak tercihi (ekonomik/VIP/farketmez) eklemek operatör eşleşmesini hızlandırır.';
        }

        if ($transportType === CharterRequest::TYPE_HELICOPTER && empty($payload['helicopter']['landing_details'])) {
            $messages[] = 'Helikopter taleplerinde iniş alanı detayını yazmak kritik doğrulamayı hızlandırır.';
        }

        if ($transportType === CharterRequest::TYPE_AIRLINER && empty($payload['airliner']['route_notes'])) {
            $messages[] = 'Charter uçak taleplerinde rota notu eklemek teklif kalitesini artırır.';
        }

        if (
            $transportType === CharterRequest::TYPE_JET
            && filter_var($payload['jet']['round_trip'] ?? false, FILTER_VALIDATE_BOOL)
            && empty($payload['jet']['return_date'])
        ) {
            $messages[] = 'Gidiş - dönüş seçiminde dönüş tarihi girmeniz teklif kalitesini artırır.';
        }
        if (
            $transportType === CharterRequest::TYPE_JET
            && filter_var($payload['jet']['different_return_route'] ?? false, FILTER_VALIDATE_BOOL)
            && (empty($payload['jet']['return_from_iata']) || empty($payload['jet']['return_to_iata']))
        ) {
            $messages[] = 'Dönüş rotası farklı seçiminde dönüş kalkış ve varış noktalarını girin.';
        }
        if (
            $transportType === CharterRequest::TYPE_JET
            && filter_var($payload['jet']['multi_leg'] ?? false, FILTER_VALIDATE_BOOL)
            && (int) ($payload['jet']['segments_count'] ?? 0) <= 0
        ) {
            $messages[] = 'Çoklu uçuş seçtiyseniz en az bir ek parkur satırı ekleyin.';
        }

        if (empty($messages)) {
            $messages[] = 'Bilgileriniz güçlü görünüyor. Bu haliyle talep hızlıca değerlendirilebilir.';
        }

        return array_slice($messages, 0, 4);
    }

    private function haversineDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return "Yaklaşık {$minutes} dk";
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($remaining === 0) {
            return "Yaklaşık {$hours} sa";
        }

        return "Yaklaşık {$hours} sa {$remaining} dk";
    }
}

<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Request as TalepModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OfferPriceBenchmarkService
{
    public function forRequest(TalepModel $talep): array
    {
        $segments = $talep->segments->sortBy('order')->values();
        $ilkSegment = $segments->first();
        $sonSegment = $segments->last();

        if (! $ilkSegment || ! $sonSegment) {
            return $this->insufficient('Rota bilgisi eksik.');
        }

        $fromIata = strtoupper((string) $ilkSegment->from_iata);
        $toIata = strtoupper((string) $sonSegment->to_iata);
        $departureMonth = (int) Carbon::parse($ilkSegment->departure_date)->format('n');

        $guncelTeklifler = $talep->offers
            ->where('is_visible', true)
            ->where('price_per_pax', '>', 0);

        if ($guncelTeklifler->isEmpty()) {
            return $this->insufficient('Mevcut görünür fiyat teklifi yok.');
        }

        $currency = (string) ($guncelTeklifler->first()->currency ?? 'TRY');
        $guncelEnDusuk = (float) $guncelTeklifler
            ->where('currency', $currency)
            ->min('price_per_pax');

        if ($guncelEnDusuk <= 0) {
            return $this->insufficient('Kıyaslanabilir fiyat bulunamadı.');
        }

        $benzerler = $this->loadComparableOffers(
            requestId: $talep->id,
            fromIata: $fromIata,
            toIata: $toIata,
            departureMonth: $departureMonth,
            currency: $currency,
        );

        if ($benzerler->count() < 6) {
            return $this->insufficient('Benzer geçmiş örnek sayısı düşük.', $benzerler->count());
        }

        $aylikMedyanlar = $this->buildMonthlyMedianMap($currency);
        $simdikiMedyan = $this->resolveCurrentMedian($aylikMedyanlar);

        if ($simdikiMedyan <= 0) {
            return $this->insufficient('Endeks oluşturmak için yeterli veri yok.', $benzerler->count());
        }

        $normalizeEdilmis = $benzerler->map(function ($offer) use ($aylikMedyanlar, $simdikiMedyan) {
            $ay = Carbon::parse($offer->created_at)->format('Y-m');
            $ayMedyani = $this->resolveMedianForMonth($aylikMedyanlar, $ay, $simdikiMedyan);
            $katsayi = $ayMedyani > 0 ? ($simdikiMedyan / $ayMedyani) : 1.0;
            $katsayi = max(0.65, min(1.90, $katsayi));

            return (float) $offer->price_per_pax * $katsayi;
        })->filter(fn ($v) => $v > 0)->values();

        if ($normalizeEdilmis->count() < 6) {
            return $this->insufficient('Normalize edilmiş örnek yetersiz.', $normalizeEdilmis->count());
        }

        $sirali = $normalizeEdilmis->sort()->values();
        $p25 = $this->percentile($sirali, 0.25);
        $medyan = $this->percentile($sirali, 0.50);
        $p75 = $this->percentile($sirali, 0.75);

        [$sonucKodu, $sonucEtiket] = $this->classify($guncelEnDusuk, $p25, $p75);
        $guven = $this->confidence($sirali->count());

        $ozet = sprintf(
            'Endeksli benzer kıyas (son 24 ay, %s-%s, mevsim yakın): n=%d. Referans bant: %s - %s %s (medyan %s). Mevcut en düşük teklif: %s %s. Sonuç: %s. Güven: %s.',
            $fromIata,
            $toIata,
            $sirali->count(),
            $this->fmt($p25),
            $this->fmt($p75),
            $currency,
            $this->fmt($medyan),
            $this->fmt($guncelEnDusuk),
            $currency,
            $sonucEtiket,
            $guven
        );

        return [
            'has_data' => true,
            'sample_size' => $sirali->count(),
            'currency' => $currency,
            'current_min' => $guncelEnDusuk,
            'band_p25' => $p25,
            'band_p75' => $p75,
            'median' => $medyan,
            'result_code' => $sonucKodu,
            'result_label' => $sonucEtiket,
            'confidence' => $guven,
            'prompt_summary' => $ozet,
            'hash_context' => implode('|', [
                $sirali->count(),
                round($p25, 2),
                round($p75, 2),
                round($medyan, 2),
                round($guncelEnDusuk, 2),
                $guven,
                $sonucKodu,
            ]),
        ];
    }

    private function loadComparableOffers(
        int $requestId,
        string $fromIata,
        string $toIata,
        int $departureMonth,
        string $currency
    ): Collection {
        $months = $this->nearMonths($departureMonth);
        $since = now()->subYears(2)->startOfDay();

        return Offer::query()
            ->select(['offers.price_per_pax', 'offers.created_at'])
            ->where('offers.request_id', '!=', $requestId)
            ->where('offers.is_visible', true)
            ->where('offers.currency', $currency)
            ->where('offers.price_per_pax', '>', 0)
            ->whereDate('offers.created_at', '>=', $since->toDateString())
            ->whereHas('request.segments', function ($q) use ($fromIata) {
                $q->where('from_iata', $fromIata);
            })
            ->whereHas('request.segments', function ($q) use ($toIata) {
                $q->where('to_iata', $toIata);
            })
            ->whereHas('request.segments', function ($q) use ($months) {
                $q->where(function ($w) use ($months) {
                    foreach ($months as $idx => $month) {
                        if ($idx === 0) {
                            $w->whereMonth('departure_date', $month);
                        } else {
                            $w->orWhereMonth('departure_date', $month);
                        }
                    }
                });
            })
            ->orderByDesc('offers.id')
            ->limit(900)
            ->get();
    }

    private function buildMonthlyMedianMap(string $currency): array
    {
        $rows = Offer::query()
            ->select(['price_per_pax', 'created_at'])
            ->where('is_visible', true)
            ->where('currency', $currency)
            ->where('price_per_pax', '>', 0)
            ->whereDate('created_at', '>=', now()->subYears(2)->startOfDay()->toDateString())
            ->orderBy('created_at')
            ->limit(5000)
            ->get();

        $gruplar = [];
        foreach ($rows as $row) {
            $ay = Carbon::parse($row->created_at)->format('Y-m');
            $gruplar[$ay][] = (float) $row->price_per_pax;
        }

        $medyanlar = [];
        foreach ($gruplar as $ay => $fiyatlar) {
            sort($fiyatlar);
            $medyanlar[$ay] = $this->medianFromArray($fiyatlar);
        }

        ksort($medyanlar);

        return $medyanlar;
    }

    private function resolveCurrentMedian(array $medyanlar): float
    {
        if (empty($medyanlar)) {
            return 0.0;
        }

        $simdi = now()->format('Y-m');
        if (isset($medyanlar[$simdi]) && $medyanlar[$simdi] > 0) {
            return (float) $medyanlar[$simdi];
        }

        $sonUc = array_slice(array_values($medyanlar), -3);
        $sonUc = array_filter($sonUc, fn ($v) => $v > 0);
        if (count($sonUc) > 0) {
            return (float) ($this->medianFromArray(array_values($sonUc)));
        }

        return (float) max(array_values($medyanlar));
    }

    private function resolveMedianForMonth(array $medyanlar, string $month, float $fallback): float
    {
        if (isset($medyanlar[$month]) && $medyanlar[$month] > 0) {
            return (float) $medyanlar[$month];
        }

        return $fallback > 0 ? $fallback : 1.0;
    }

    private function classify(float $currentMin, float $p25, float $p75): array
    {
        if ($currentMin <= ($p25 * 0.97)) {
            return ['fiyat_avantajli', 'fiyat avantajlı'];
        }
        if ($currentMin <= ($p75 * 1.03)) {
            return ['piyasa_bandinda', 'piyasa bandında'];
        }

        return ['piyasa_ustu', 'piyasa bandı üstü'];
    }

    private function confidence(int $sampleSize): string
    {
        if ($sampleSize >= 30) {
            return 'yüksek';
        }
        if ($sampleSize >= 12) {
            return 'orta';
        }

        return 'düşük';
    }

    private function percentile(Collection $sorted, float $p): float
    {
        $n = $sorted->count();
        if ($n === 0) {
            return 0.0;
        }
        if ($n === 1) {
            return (float) $sorted->first();
        }

        $idx = ($n - 1) * $p;
        $low = (int) floor($idx);
        $high = (int) ceil($idx);
        if ($low === $high) {
            return (float) $sorted[$low];
        }

        $w = $idx - $low;

        return ((1 - $w) * (float) $sorted[$low]) + ($w * (float) $sorted[$high]);
    }

    private function medianFromArray(array $sortedValues): float
    {
        $n = count($sortedValues);
        if ($n === 0) {
            return 0.0;
        }

        $mid = intdiv($n, 2);
        if ($n % 2 === 0) {
            return ((float) $sortedValues[$mid - 1] + (float) $sortedValues[$mid]) / 2;
        }

        return (float) $sortedValues[$mid];
    }

    private function nearMonths(int $month): array
    {
        $prev = $month - 1;
        $next = $month + 1;
        if ($prev < 1) {
            $prev = 12;
        }
        if ($next > 12) {
            $next = 1;
        }

        return [$prev, $month, $next];
    }

    private function insufficient(string $reason, int $sampleSize = 0): array
    {
        return [
            'has_data' => false,
            'sample_size' => $sampleSize,
            'currency' => null,
            'current_min' => null,
            'band_p25' => null,
            'band_p75' => null,
            'median' => null,
            'result_code' => 'yetersiz_veri',
            'result_label' => 'yetersiz veri',
            'confidence' => 'düşük',
            'prompt_summary' => "Endeksli geçmiş kıyas: yetersiz veri ({$reason}). Kesin fiyat hükmü verme; yalnızca teklifler arası fark ve opsiyon süresini yorumla.",
            'hash_context' => 'insufficient:' . $sampleSize . ':' . md5($reason),
        ];
    }

    private function fmt(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}


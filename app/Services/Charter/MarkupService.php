<?php

namespace App\Services\Charter;

use App\Models\CharterRequest;
use App\Models\SistemAyar;

class MarkupService
{
    /**
     * @return array{markup_percent:float,min_profit:float,sale_price:float}
     */
    public function calculate(float $supplierPrice, string $transportType, ?float $overridePercent = null, ?float $overrideMinProfit = null): array
    {
        $supplierPrice = max(0, $supplierPrice);

        $global = $this->settingFloat('charter_markup_global_percent', (float) config('charter.markup.global_percent', 12));
        $jet = $this->settingFloat('charter_markup_jet_percent', (float) config('charter.markup.jet_percent', 15));
        $heli = $this->settingFloat('charter_markup_helicopter_percent', (float) config('charter.markup.helicopter_percent', 14));
        $airliner = $this->settingFloat('charter_markup_airliner_percent', (float) config('charter.markup.airliner_percent', 10));
        $minProfitDefault = $this->settingFloat('charter_markup_min_profit', (float) config('charter.markup.min_profit', 1500));

        $transportPercent = match ($transportType) {
            CharterRequest::TYPE_JET => $jet,
            CharterRequest::TYPE_HELICOPTER => $heli,
            CharterRequest::TYPE_AIRLINER => $airliner,
            default => $global,
        };

        $markupPercent = $overridePercent ?? $transportPercent;
        $minProfit = $overrideMinProfit ?? $minProfitDefault;

        $markupAmount = ($supplierPrice * $markupPercent) / 100;
        $salePrice = max($supplierPrice + $markupAmount, $supplierPrice + $minProfit);

        return [
            'markup_percent' => round($markupPercent, 2),
            'min_profit' => round($minProfit, 2),
            'sale_price' => round($salePrice, 2),
        ];
    }

    private function settingFloat(string $key, float $fallback): float
    {
        try {
            $raw = SistemAyar::get($key, (string) $fallback);
            return is_numeric($raw) ? (float) $raw : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }
}


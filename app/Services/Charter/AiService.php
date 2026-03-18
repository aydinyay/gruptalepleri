<?php

namespace App\Services\Charter;

use App\Models\AircraftImage;
use App\Models\CharterQuote;
use App\Models\CharterRequest;

class AiService
{
    public function __construct(
        private readonly JetPricingService $jetPricingService,
        private readonly HelicopterPricingService $helicopterPricingService,
        private readonly AirlinerPricingService $airlinerPricingService
    ) {
    }

    /**
     * AI rolu: analiz + oneri + risk.
     * Fiyat range kuralli pricing servisinden gelir (LLM uretmez).
     *
     * @return array<string, mixed>
     */
    public function buildPreQuote(CharterRequest $request): array
    {
        $estimate = match ($request->transport_type) {
            CharterRequest::TYPE_JET => $this->jetPricingService->estimate(
                (int) $request->pax,
                (string) $request->from_iata,
                (string) $request->to_iata
            ),
            CharterRequest::TYPE_HELICOPTER => $this->helicopterPricingService->estimate(
                (int) $request->pax,
                (string) $request->from_iata,
                (string) $request->to_iata,
                $request->helicopterDetail?->pickup,
                $request->helicopterDetail?->dropoff
            ),
            default => $this->airlinerPricingService->estimate(
                (int) $request->pax,
                (string) $request->from_iata,
                (string) $request->to_iata,
                (bool) $request->is_flexible
            ),
        };

        $image = AircraftImage::query()
            ->where('service_type', $request->transport_type)
            ->where('is_active', true)
            ->where(function ($q) use ($estimate): void {
                $q->where('model_name', 'like', '%' . $estimate['model'] . '%')
                    ->orWhere('model_code', 'like', '%' . $estimate['model'] . '%');
            })
            ->orderBy('priority')
            ->first();

        if (! $image) {
            $image = AircraftImage::query()
                ->where('service_type', $request->transport_type)
                ->where('is_active', true)
                ->orderBy('priority')
                ->first();
        }

        $analysisComment = $this->buildAnalysisComment($request, $estimate['risk_flags']);

        $request->update([
            'ai_suggested_model' => $estimate['model'],
            'ai_price_min' => $estimate['min'],
            'ai_price_max' => $estimate['max'],
            'ai_currency' => $estimate['currency'],
            'ai_risk_flags' => $estimate['risk_flags'],
            'ai_comment' => $analysisComment,
            'aircraft_image_url' => $image?->image_url,
            'status' => CharterRequest::STATUS_AI_QUOTED,
        ]);

        CharterQuote::query()->create([
            'charter_request_id' => $request->id,
            'quote_type' => 'ai_preview',
            'status' => 'generated',
            'title' => 'AI On Teklif',
            'description' => $analysisComment,
            'payload' => [
                'model' => $estimate['model'],
                'price_min' => $estimate['min'],
                'price_max' => $estimate['max'],
                'currency' => $estimate['currency'],
                'risk_flags' => $estimate['risk_flags'],
                'image_url' => $image?->image_url,
            ],
        ]);

        return [
            'model' => $estimate['model'],
            'price_min' => $estimate['min'],
            'price_max' => $estimate['max'],
            'currency' => $estimate['currency'],
            'risk_flags' => $estimate['risk_flags'],
            'comment' => $analysisComment,
            'image_url' => $image?->image_url,
        ];
    }

    /**
     * @param  array<int, string>  $flags
     */
    private function buildAnalysisComment(CharterRequest $request, array $flags): string
    {
        $route = trim(($request->from_iata ?: '---') . ' - ' . ($request->to_iata ?: '---'));
        $pax = $request->pax ?: 0;
        $transportLabel = match ($request->transport_type) {
            CharterRequest::TYPE_JET => 'Ozel Jet',
            CharterRequest::TYPE_HELICOPTER => 'Helikopter',
            CharterRequest::TYPE_AIRLINER => 'Charter Ucak',
            default => 'Ucus',
        };

        $base = "{$transportLabel} talebi icin {$route} parkurunda {$pax} yolcuya gore on analiz uretildi.";
        if (empty($flags)) {
            return $base . ' Kritik risk gorunmuyor, operasyonel teyit sonrasi RFQ onerilir.';
        }

        return $base . ' Riskler: ' . implode(' | ', $flags);
    }
}

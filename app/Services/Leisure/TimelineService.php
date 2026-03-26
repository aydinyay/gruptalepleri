<?php

namespace App\Services\Leisure;

use App\Models\LeisureClientOffer;
use App\Models\LeisureRequest;

class TimelineService
{
    public function build(LeisureRequest $request, ?LeisureClientOffer $offer = null): array
    {
        $times = $this->estimateTimes($request);

        if ($request->product_type === LeisureRequest::PRODUCT_DINNER_CRUISE) {
            return [
                'tr' => implode("\n", [
                    "Pickup: {$times['pickup_tr']}",
                    "Iskele varis: {$times['pier_tr']}",
                    "Boarding: {$times['boarding_tr']}",
                    "Dinner: {$times['dinner_tr']}",
                    "Show: {$times['show_tr']}",
                    "Bitis: {$times['finish_tr']}",
                    $request->transfer_required ? "Transfer donus: {$times['return_tr']}" : 'Transfer donus: Talebe gore planlanir',
                ]),
                'en' => implode("\n", [
                    "Pickup: {$times['pickup_en']}",
                    "Pier arrival: {$times['pier_en']}",
                    "Boarding: {$times['boarding_en']}",
                    "Dinner: {$times['dinner_en']}",
                    "Show: {$times['show_en']}",
                    "Finish: {$times['finish_en']}",
                    $request->transfer_required ? "Return transfer: {$times['return_en']}" : 'Return transfer: Planned according to request',
                ]),
            ];
        }

        return [
            'tr' => implode("\n", [
                "Pickup: {$times['pickup_tr']}",
                "Marina varis: {$times['pier_tr']}",
                "Boarding: {$times['boarding_tr']}",
                "Yat cikisi: {$times['dinner_tr']}",
                "Rota/Etkinlik: {$times['show_tr']}",
                "Donus: {$times['finish_tr']}",
                $request->transfer_required ? "Transfer donus: {$times['return_tr']}" : 'Transfer donus: Talebe gore planlanir',
            ]),
            'en' => implode("\n", [
                "Pickup: {$times['pickup_en']}",
                "Marina arrival: {$times['pier_en']}",
                "Boarding: {$times['boarding_en']}",
                "Departure: {$times['dinner_en']}",
                "Route/Event: {$times['show_en']}",
                "Return: {$times['finish_en']}",
                $request->transfer_required ? "Return transfer: {$times['return_en']}" : 'Return transfer: Planned according to request',
            ]),
        ];
    }

    private function estimateTimes(LeisureRequest $request): array
    {
        $base = $request->product_type === LeisureRequest::PRODUCT_YACHT
            ? ($request->yachtDetail?->start_time ?? '19:00')
            : ($request->dinnerCruiseDetail?->session_time ?? '20:30');

        [$hour, $minute] = array_pad(explode(':', $base), 2, '00');
        $startMinutes = (((int) $hour) * 60) + (int) $minute;

        $pickup = max(0, $startMinutes - 90);
        $pier = max(0, $startMinutes - 30);
        $boarding = max(0, $startMinutes - 15);
        $dinner = $startMinutes;
        $show = $startMinutes + 60;
        $finish = $startMinutes + ($request->product_type === LeisureRequest::PRODUCT_YACHT
            ? (($request->yachtDetail?->duration_hours ?? 2) * 60)
            : 180);
        $return = $finish + 20;

        return [
            'pickup_tr' => $this->formatMinutes($pickup) . ($request->transfer_region ? " - {$request->transfer_region}" : ''),
            'pier_tr' => $this->formatMinutes($pier),
            'boarding_tr' => $this->formatMinutes($boarding),
            'dinner_tr' => $this->formatMinutes($dinner),
            'show_tr' => $this->formatMinutes($show),
            'finish_tr' => $this->formatMinutes($finish),
            'return_tr' => $this->formatMinutes($return),
            'pickup_en' => $this->formatMinutes($pickup) . ($request->transfer_region ? " - {$request->transfer_region}" : ''),
            'pier_en' => $this->formatMinutes($pier),
            'boarding_en' => $this->formatMinutes($boarding),
            'dinner_en' => $this->formatMinutes($dinner),
            'show_en' => $this->formatMinutes($show),
            'finish_en' => $this->formatMinutes($finish),
            'return_en' => $this->formatMinutes($return),
        ];
    }

    private function formatMinutes(int $totalMinutes): string
    {
        $totalMinutes = max(0, $totalMinutes);
        $hour = intdiv($totalMinutes, 60) % 24;
        $minute = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }
}

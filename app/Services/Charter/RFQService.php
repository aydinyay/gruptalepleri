<?php

namespace App\Services\Charter;

use App\Models\CharterQuote;
use App\Models\CharterRequest;
use App\Models\CharterSalesQuote;
use Illuminate\Support\Facades\Mail;

class RFQService
{
    /**
     * @return array{sent:int,failed:int,targets:array<int,array<string,mixed>>}
     */
    public function dispatch(CharterRequest $request, ?CharterSalesQuote $salesQuote = null): array
    {
        $suppliers = collect(config('charter.suppliers', []))
            ->filter(static fn (array $supplier): bool => in_array($request->transport_type, $supplier['service_types'] ?? [], true))
            ->take((int) config('charter.rfq_max_suppliers', 10))
            ->values();

        $sent = 0;
        $failed = 0;
        $targets = [];

        foreach ($suppliers as $supplier) {
            $payload = [
                'supplier_name' => $supplier['name'] ?? 'Supplier',
                'email' => $supplier['email'] ?? null,
                'phone' => $supplier['phone'] ?? null,
                'service_type' => $request->transport_type,
                'request_id' => $request->id,
                'route' => trim(($request->from_iata ?: '---') . ' - ' . ($request->to_iata ?: '---')),
                'departure_date' => optional($request->departure_date)->format('Y-m-d'),
                'pax' => $request->pax,
                'sales_quote_id' => $salesQuote?->id,
            ];

            $status = 'sent';
            try {
                if (! empty($supplier['email'])) {
                    $subject = 'RFQ Talebi #' . $request->id;
                    $text = "Yeni RFQ talebi:\n"
                        . "Request: #{$request->id}\n"
                        . "Servis: {$request->transport_type}\n"
                        . "Rota: {$payload['route']}\n"
                        . "Tarih: {$payload['departure_date']}\n"
                        . "PAX: {$request->pax}\n";
                    Mail::raw($text, static function ($mail) use ($supplier, $subject): void {
                        $mail->to($supplier['email'], $supplier['name'] ?? 'Supplier')->subject($subject);
                    });
                }
                $sent++;
            } catch (\Throwable $e) {
                $status = 'failed';
                $failed++;
                $payload['error'] = $e->getMessage();
            }

            CharterQuote::query()->create([
                'charter_request_id' => $request->id,
                'quote_type' => 'rfq',
                'status' => $status,
                'title' => 'RFQ Dagitimi',
                'description' => ($supplier['name'] ?? 'Supplier') . ' kanalina RFQ gonderimi',
                'payload' => $payload,
            ]);

            $targets[] = $payload + ['status' => $status];
        }

        if ($sent > 0) {
            $request->update(['status' => CharterRequest::STATUS_RFQ_SENT]);
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'targets' => $targets,
        ];
    }
}

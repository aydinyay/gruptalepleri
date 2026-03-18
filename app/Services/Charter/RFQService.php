<?php

namespace App\Services\Charter;

use App\Models\CharterRfqSupplier;
use App\Models\CharterQuote;
use App\Models\CharterRequest;
use App\Models\CharterSalesQuote;
use App\Models\SistemAyar;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class RFQService
{
    /**
     * @return array{sent:int,failed:int,targets:array<int,array<string,mixed>>}
     */
    public function dispatch(CharterRequest $request, ?CharterSalesQuote $salesQuote = null): array
    {
        $maxSuppliers = SistemAyar::charterRfqMaxSuppliers((int) config('charter.rfq_max_suppliers', 10));

        $suppliers = $this->resolveSuppliers($request, $maxSuppliers);

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

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function resolveSuppliers(CharterRequest $request, int $maxSuppliers)
    {
        if (Schema::hasTable('charter_rfq_suppliers')) {
            $dbSuppliers = CharterRfqSupplier::query()
                ->where('is_active', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->where(function ($query) use ($request): void {
                    $query->whereJsonContains('service_types', $request->transport_type)
                        ->orWhereNull('service_types')
                        ->orWhereRaw('JSON_LENGTH(service_types) = 0');
                })
                ->orderBy('name')
                ->limit($maxSuppliers)
                ->get()
                ->map(static function (CharterRfqSupplier $supplier): array {
                    return [
                        'name' => $supplier->name,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone,
                        'service_types' => $supplier->service_types ?? [],
                    ];
                })
                ->values();

            if ($dbSuppliers->isNotEmpty()) {
                return $dbSuppliers;
            }
        }

        return collect(config('charter.suppliers', []))
            ->filter(static fn (array $supplier): bool => in_array($request->transport_type, $supplier['service_types'] ?? [], true))
            ->take($maxSuppliers)
            ->values();
    }
}

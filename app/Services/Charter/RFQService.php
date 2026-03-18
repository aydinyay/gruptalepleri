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
        $request->loadMissing(['jetDetail', 'helicopterDetail', 'airlinerDetail', 'extras', 'user']);

        $maxSuppliers = SistemAyar::charterRfqMaxSuppliers((int) config('charter.rfq_max_suppliers', 10));

        $suppliers = $this->resolveSuppliers($request, $maxSuppliers);

        $sent = 0;
        $failed = 0;
        $targets = [];
        $rfqReference = $this->buildRfqReference($request);

        foreach ($suppliers as $supplier) {
            $payload = [
                'supplier_name' => $supplier['name'] ?? 'Supplier',
                'email' => $supplier['email'] ?? null,
                'phone' => $supplier['phone'] ?? null,
                'service_type' => $request->transport_type,
                'request_id' => $request->id,
                'rfq_reference' => $rfqReference,
                'route' => trim(($request->from_iata ?: '---') . ' - ' . ($request->to_iata ?: '---')),
                'departure_date' => optional($request->departure_date)->format('Y-m-d'),
                'pax' => $request->pax,
                'sales_quote_id' => $salesQuote?->id,
            ];

            $status = 'sent';
            try {
                if (empty($supplier['email'])) {
                    $status = 'failed';
                    $failed++;
                    $payload['error'] = 'Supplier e-posta bilgisi bos.';
                } else {
                    $subject = $this->buildSubject($request);
                    $text = $this->buildMailBody($request, $rfqReference);
                    Mail::raw($text, static function ($mail) use ($supplier, $subject): void {
                        $mail->to($supplier['email'], $supplier['name'] ?? 'Supplier')->subject($subject);
                    });
                    $sent++;
                }
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
                'description' => ($supplier['name'] ?? 'Supplier') . ' kanalina RFQ gonderimi (' . $rfqReference . ')',
                'payload' => $payload + [
                    'request_snapshot' => $this->buildRequestSnapshot($request),
                ],
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

    private function buildSubject(CharterRequest $request): string
    {
        $route = strtoupper((string) $request->from_iata) . '-' . strtoupper((string) $request->to_iata);
        $date = optional($request->departure_date)->format('d.m.Y') ?: '-';
        return "RFQ #{$request->id} | {$route} | {$date} | PAX {$request->pax}";
    }

    private function buildRfqReference(CharterRequest $request): string
    {
        return 'RFQ-' . $request->id . '-' . now()->format('YmdHis');
    }

    private function buildMailBody(CharterRequest $request, string $rfqReference): string
    {
        $lines = [
            'Yeni Air Charter RFQ Talebi',
            '==========================',
            'RFQ Referansi: ' . $rfqReference,
            'Talep No: #' . $request->id,
            'Servis Tipi: ' . strtoupper((string) $request->transport_type),
            'Rota: ' . strtoupper((string) ($request->from_iata ?: '-')) . ' -> ' . strtoupper((string) ($request->to_iata ?: '-')),
            'Gidis Tarihi: ' . (optional($request->departure_date)->format('d.m.Y') ?: '-'),
            'PAX: ' . ((string) ($request->pax ?: '-')),
            'Esnek Tarih: ' . ($request->is_flexible ? 'Evet' : 'Hayir'),
        ];

        if (! empty($request->group_type)) {
            $lines[] = 'Grup Tipi: ' . $request->group_type;
        }

        $jet = $request->jetDetail;
        $jetSpecs = is_array($jet?->specs_json) ? $jet->specs_json : [];
        if ($jet?->round_trip) {
            $returnDate = $jetSpecs['return_date'] ?? null;
            $returnFrom = strtoupper((string) ($jetSpecs['return_from_iata'] ?? $request->to_iata));
            $returnTo = strtoupper((string) ($jetSpecs['return_to_iata'] ?? $request->from_iata));
            $lines[] = 'Donus: ' . $returnFrom . ' -> ' . $returnTo . ' | Tarih: ' . ($returnDate ?: '-');
        }

        $segments = collect($jetSpecs['segments'] ?? [])->filter(function ($segment): bool {
            return is_array($segment)
                && ! empty($segment['from_iata'])
                && ! empty($segment['to_iata'])
                && ! empty($segment['departure_date']);
        })->values();

        if ($segments->isNotEmpty()) {
            $lines[] = 'Coklu Ucus Parkurlari:';
            foreach ($segments as $index => $segment) {
                $lines[] = sprintf(
                    '  %d) %s -> %s | %s',
                    $index + 1,
                    strtoupper((string) $segment['from_iata']),
                    strtoupper((string) $segment['to_iata']),
                    (string) $segment['departure_date']
                );
            }
        }

        $services = collect([
            $jet?->round_trip ? 'Gidis-Donus' : null,
            $jet?->pet_onboard ? 'Evcil Hayvan' : null,
            $jet?->vip_catering ? 'VIP Catering' : null,
            $jet?->wifi_required ? 'Wi-Fi' : null,
            $jet?->special_luggage ? 'Ozel Bagaj' : null,
        ])->filter()->values();
        if ($services->isNotEmpty()) {
            $lines[] = 'Jet Hizmetleri: ' . $services->implode(', ');
        }

        if (! empty($request->notes)) {
            $lines[] = 'Talep Notu: ' . preg_replace('/\s+/', ' ', trim((string) $request->notes));
        }

        $extras = $request->extras
            ->map(function ($extra): string {
                $title = trim((string) ($extra->title ?? 'Ek Hizmet'));
                $note = trim((string) ($extra->agency_note ?? ''));
                return $note !== '' ? $title . ' (' . $note . ')' : $title;
            })
            ->filter()
            ->values();
        if ($extras->isNotEmpty()) {
            $lines[] = 'Ekstra Talepler: ' . $extras->implode(', ');
        }

        if ($request->ai_price_min !== null && $request->ai_price_max !== null) {
            $lines[] = 'AI On Aralik: '
                . number_format((float) $request->ai_price_min, 0, ',', '.')
                . ' - '
                . number_format((float) $request->ai_price_max, 0, ',', '.')
                . ' '
                . ($request->ai_currency ?: 'EUR');
        }

        $lines[] = '';
        $lines[] = 'Lutfen teklifinizi bu e-postaya yanitlayarak ya da operasyon kanalimiz uzerinden iletin.';
        $lines[] = 'Tesekkurler,';
        $lines[] = 'GrupTalepleri Operasyon';

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestSnapshot(CharterRequest $request): array
    {
        return [
            'id' => $request->id,
            'transport_type' => $request->transport_type,
            'from_iata' => $request->from_iata,
            'to_iata' => $request->to_iata,
            'departure_date' => optional($request->departure_date)->format('Y-m-d'),
            'pax' => $request->pax,
            'is_flexible' => (bool) $request->is_flexible,
            'group_type' => $request->group_type,
            'notes' => $request->notes,
            'jet_detail' => $request->jetDetail?->only([
                'round_trip',
                'pet_onboard',
                'vip_catering',
                'wifi_required',
                'special_luggage',
                'luggage_count',
                'cabin_preference',
                'airport_slot_note',
                'specs_json',
            ]),
            'extras' => $request->extras->map(function ($extra): array {
                return [
                    'title' => $extra->title,
                    'agency_note' => $extra->agency_note,
                    'status' => $extra->status,
                ];
            })->values()->all(),
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
                        ->orWhere('service_types', '[]')
                        ->orWhere('service_types', '');
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

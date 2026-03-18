<?php

namespace App\Services\Charter;

use App\Models\CharterRfqSupplier;
use App\Models\CharterQuote;
use App\Models\CharterRequest;
use App\Models\CharterSalesQuote;
use App\Models\SistemAyar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class RFQService
{
    /**
     * @return array{sent:int,failed:int,targets:array<int,array<string,mixed>>}
     */
    public function dispatch(CharterRequest $request, ?CharterSalesQuote $salesQuote = null, ?User $sender = null): array
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
                    $text = $this->buildMailBody(
                        $request,
                        $rfqReference,
                        (string) ($supplier['name'] ?? 'Degerli Is Ortagimiz'),
                        $sender
                    );
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
        $serviceLabel = $this->serviceLabel((string) $request->transport_type);
        $route = strtoupper((string) $request->from_iata) . '-' . strtoupper((string) $request->to_iata);
        $date = optional($request->departure_date)->format('d.m.Y') ?: '-';
        return "RFQ - {$serviceLabel} Talebi | {$route} | {$date} | {$request->pax} Pax";
    }

    private function buildRfqReference(CharterRequest $request): string
    {
        return 'RFQ-' . $request->id . '-' . now()->format('YmdHis');
    }

    private function buildMailBody(CharterRequest $request, string $rfqReference, string $supplierName, ?User $sender): string
    {
        $company = $this->companyContext();
        $senderContext = $this->senderContext($sender);
        $serviceLabel = $this->serviceLabel((string) $request->transport_type);
        $from = strtoupper((string) ($request->from_iata ?: '-'));
        $to = strtoupper((string) ($request->to_iata ?: '-'));

        $lines = [
            (string) $company['brand'],
            (string) $company['legal_name'],
            (string) $company['unit'],
            '',
            'Merhaba, sayin ' . trim($supplierName),
            '',
            'Asagidaki charter talebi icin net operasyon teklifinizi rica ederiz.',
            '',
            'RFQ Referansi: ' . $rfqReference,
            'Talep No: #' . $request->id,
            'Ucus Tipi: ' . $serviceLabel,
            'Rota: ' . $from . ' -> ' . $to,
            'Tarih: ' . (optional($request->departure_date)->format('d.m.Y') ?: '-'),
            'Pax: ' . ((string) ($request->pax ?: '-')),
            'Esnek Tarih: ' . ($request->is_flexible ? 'Evet' : 'Hayir'),
            'Yolcu Dagilimi: ' . $this->passengerBreakdown($request),
            'Bagaj: ' . $this->baggageInfo($request),
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
            if (! empty($returnDate)) {
                $lines[] = 'Donus: ' . $returnFrom . ' -> ' . $returnTo . ' | Tarih: ' . $returnDate;
            } else {
                $lines[] = 'Donus: ' . $returnFrom . ' -> ' . $returnTo . ' (donus tarihi net degil, alternatifli paylasabilirsiniz)';
                $lines[] = 'Donus Tarih Araligi: Belirtilmedi (ornek: 30.03.2026 - 02.04.2026 gibi iletebilirsiniz)';
            }
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
            $lines[] = 'Ek Hizmet: ' . $extras->implode(' / ');
        }

        $lines[] = '';
        $lines[] = 'Teklifte ozellikle belirtmenizi rica ederiz:';
        $lines[] = '- Ucak tipi/modeli';
        $lines[] = '- Toplam fiyat + para birimi';
        $lines[] = '- Fiyata dahil/haric kalemler';
        $lines[] = '- Slot/permit/handling durumu (dahil/haric aciklayin)';
        $lines[] = '- Opsiyon suresi (teklif gecerlilik, TSI saatine gore)';
        $lines[] = '- Iptal/degisiklik sartlari';
        $lines[] = '- Musaitlik teyidi';
        $lines[] = '- Odeme kosullari (on odeme orani / vade / fatura para birimi)';
        $lines[] = '- Operasyon belgeleri (AOC / sigorta) uygunluk teyidi';
        $lines[] = '';
        $lines[] = 'Mumkunse teklifinizi ' . $this->responseDeadlineText() . " (TSI, UTC+3)'e kadar paylasabilir misiniz?";
        $lines[] = '';
        $lines[] = 'Teklif Cevap Formati (hizli yanit icin):';
        $lines[] = 'Model:';
        $lines[] = 'Toplam Fiyat + Para Birimi:';
        $lines[] = 'Dahil / Haric Kalemler:';
        $lines[] = 'Opsiyon Suresi:';
        $lines[] = 'Musaitlik:';
        $lines[] = 'Iptal / Degisiklik Sartlari:';
        $lines[] = 'Ek Not:';

        $lines[] = '';
        $lines[] = 'Tesekkurler.';
        $lines[] = '';
        $lines[] = trim((string) $senderContext['name']);
        $lines[] = 'E-posta: ' . (string) $company['support_email'];
        $lines[] = 'Telefon: ' . trim((string) $senderContext['phone']);
        $lines[] = (string) $company['website'];
        $lines[] = '';
        $lines[] = '---';
        $lines[] = (string) $company['legal_name'];
        $lines[] = 'Adres: ' . (string) $company['address'];
        $lines[] = 'Tel: ' . (string) $company['phone'] . ' | E-posta: ' . (string) $company['support_email'];
        $lines[] = 'Web: ' . (string) $company['website'];
        $lines[] = '';
        $lines[] = 'Bu e-posta yalnizca ilgili alici icindir. Yanlislikla aldiysaniz lutfen siliniz.';

        return implode("\n", $lines);
    }

    private function responseDeadlineText(): string
    {
        $deadlineHour = (int) config('charter.rfq_deadline_hour', 16);
        $deadlineAt = now()->copy()->setTime($deadlineHour, 0, 0);
        if ($deadlineAt->lessThan(now())) {
            $deadlineAt->addDay();
        }

        return $deadlineAt->format('d.m.Y H:i');
    }

    /**
     * @return array{brand:string,legal_name:string,unit:string,address:string,phone:string,support_email:string,website:string}
     */
    private function companyContext(): array
    {
        return [
            'brand' => (string) config('charter.company.brand', 'GrupTalepleri.com'),
            'legal_name' => (string) config('charter.company.legal_name', 'GrupTalepleri Turizm Organizasyon ve Tic. Ltd. Sti.'),
            'unit' => (string) config('charter.company.unit', 'Kurumsal Charter Operasyon Birimi'),
            'address' => (string) config('charter.company.address', 'Inonu Mah. Cumhuriyet Cad. No:93/12 Sisli / Istanbul'),
            'phone' => (string) config('charter.company.phone', '+90 535 415 47 99'),
            'support_email' => (string) config('charter.company.support_email', 'destek@gruptalepleri.com'),
            'website' => (string) config('charter.company.website', 'www.gruptalepleri.com'),
        ];
    }

    /**
     * @return array{name:string,phone:string}
     */
    private function senderContext(?User $sender): array
    {
        $companyPhone = (string) config('charter.company.phone', '+90 535 415 47 99');
        if (! $sender) {
            return [
                'name' => 'GrupTalepleri Operasyon',
                'phone' => $companyPhone,
            ];
        }

        return [
            'name' => (string) ($sender->name ?: 'GrupTalepleri Operasyon'),
            'phone' => (string) ($sender->phone ?: $companyPhone),
        ];
    }

    private function serviceLabel(string $transportType): string
    {
        return match ($transportType) {
            CharterRequest::TYPE_JET => 'Ozel Jet',
            CharterRequest::TYPE_HELICOPTER => 'Helikopter',
            CharterRequest::TYPE_AIRLINER => 'Charter Ucak',
            default => strtoupper($transportType),
        };
    }

    private function passengerBreakdown(CharterRequest $request): string
    {
        $jetSpecs = is_array($request->jetDetail?->specs_json) ? $request->jetDetail->specs_json : [];
        $adults = $jetSpecs['adult_count'] ?? $jetSpecs['adults'] ?? null;
        $children = $jetSpecs['child_count'] ?? $jetSpecs['children'] ?? null;
        $infants = $jetSpecs['infant_count'] ?? $jetSpecs['infants'] ?? null;

        if ($adults === null && $children === null && $infants === null) {
            return 'Belirtilmedi (yetiskin/cocuk/bebek dagilimi teyit edilebilir).';
        }

        $parts = [];
        if ($adults !== null) {
            $parts[] = 'Yetiskin: ' . (int) $adults;
        }
        if ($children !== null) {
            $parts[] = 'Cocuk: ' . (int) $children;
        }
        if ($infants !== null) {
            $parts[] = 'Bebek: ' . (int) $infants;
        }

        return implode(', ', $parts);
    }

    private function baggageInfo(CharterRequest $request): string
    {
        $jet = $request->jetDetail;
        if (! $jet) {
            return 'Belirtilmedi (adet/toplam kg belirtilebilir).';
        }

        $count = $jet->luggage_count;
        $hasSpecial = (bool) $jet->special_luggage;

        if ($count !== null && $hasSpecial) {
            return "Adet: {$count} + Ozel bagaj mevcut.";
        }
        if ($count !== null) {
            return "Adet: {$count}.";
        }
        if ($hasSpecial) {
            return 'Ozel bagaj mevcut (adet/olcu teyidi bekleniyor).';
        }

        return 'Belirtilmedi (adet/toplam kg belirtilebilir).';
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

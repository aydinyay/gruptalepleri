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
                'supplier_kind' => $supplier['supplier_kind'] ?? null,
                'selection_score' => $supplier['selection_score'] ?? null,
                'selection_reason' => $supplier['selection_reason'] ?? null,
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
                'description' => ($supplier['name'] ?? 'Supplier') . ' kanalina RFQ gonderimi (' . $rfqReference . ')'
                    . (! empty($supplier['selection_score']) ? ' | Skor: ' . $supplier['selection_score'] : ''),
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
        $lines[] = '- Teklif gecerlilik suresi (TSI saatine gore)';
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
        $pax = max(0, (int) ($request->pax ?? 0));
        $cargoIntent = $this->isCargoIntent($request);
        $noticeHours = $this->hoursToDeparture($request);
        $marketClass = $this->resolveMarketClass($request, $cargoIntent);
        $targetModel = $this->resolveTargetModel($request, $cargoIntent);
        $effectiveMaxSuppliers = $this->effectiveMaxSuppliers($maxSuppliers, $marketClass);

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
                ->get()
                ->map(function (CharterRfqSupplier $supplier) use ($pax, $cargoIntent, $noticeHours, $marketClass, $targetModel): array {
                    $hardCheck = $this->passesHardFilters(
                        $supplier,
                        $pax,
                        $cargoIntent,
                        $noticeHours,
                        $marketClass
                    );
                    $score = $this->scoreSupplier(
                        $supplier,
                        $pax,
                        $cargoIntent,
                        $targetModel,
                        $marketClass,
                        $hardCheck['passed']
                    );
                    return [
                        'name' => $supplier->name,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone,
                        'service_types' => $supplier->service_types ?? [],
                        'supplier_kind' => $supplier->supplier_kind ?: 'operator',
                        'selection_score' => $score,
                        'selection_reason' => $hardCheck['reason'],
                        'hard_passed' => $hardCheck['passed'],
                        'priority' => (int) ($supplier->priority ?? 100),
                    ];
                })
                ->filter(static fn (array $supplier): bool => (bool) ($supplier['hard_passed'] ?? false))
                ->sort(function (array $a, array $b): int {
                    $scoreDiff = ((float) ($b['selection_score'] ?? 0)) <=> ((float) ($a['selection_score'] ?? 0));
                    if ($scoreDiff !== 0) {
                        return $scoreDiff;
                    }

                    $priorityDiff = ((int) ($a['priority'] ?? 100)) <=> ((int) ($b['priority'] ?? 100));
                    if ($priorityDiff !== 0) {
                        return $priorityDiff;
                    }

                    return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
                })
                ->take($effectiveMaxSuppliers)
                ->values()
                ->map(function (array $supplier): array {
                    unset($supplier['hard_passed'], $supplier['priority']);
                    return $supplier;
                })
                ->values();

            if ($dbSuppliers->isNotEmpty()) {
                return $dbSuppliers;
            }
        }

        return collect(config('charter.suppliers', []))
            ->filter(static fn (array $supplier): bool => in_array($request->transport_type, $supplier['service_types'] ?? [], true))
            ->take($effectiveMaxSuppliers)
            ->values();
    }

    private function effectiveMaxSuppliers(int $baseLimit, string $marketClass): int
    {
        $baseLimit = max(1, $baseLimit);

        return match ($marketClass) {
            'cargo' => min($baseLimit, 3),
            'small_group', 'helicopter' => min($baseLimit, 4),
            'regional', 'narrowbody' => min($baseLimit, 5),
            'widebody' => min($baseLimit, 4),
            default => $baseLimit,
        };
    }

    private function isCargoIntent(CharterRequest $request): bool
    {
        $haystack = mb_strtolower(trim((string) $request->group_type . ' ' . (string) $request->notes));
        if ($haystack === '') {
            return false;
        }

        foreach (['cargo', 'kargo', 'freight', 'yuk', 'yük', 'load', 'pallet', 'il76', 'b747f'] as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function hoursToDeparture(CharterRequest $request): ?int
    {
        if (! $request->departure_date) {
            return null;
        }

        $hours = now()->diffInHours($request->departure_date->copy()->startOfDay(), false);
        return $hours < 0 ? 0 : (int) $hours;
    }

    private function resolveMarketClass(CharterRequest $request, bool $cargoIntent): string
    {
        if ($cargoIntent) {
            return 'cargo';
        }

        $pax = (int) ($request->pax ?? 0);
        if ($request->transport_type === CharterRequest::TYPE_HELICOPTER) {
            return 'helicopter';
        }

        if ($pax <= 18) {
            return 'small_group';
        }
        if ($pax <= 50) {
            return 'regional';
        }
        if ($pax <= 189) {
            return 'narrowbody';
        }

        return 'widebody';
    }

    private function resolveTargetModel(CharterRequest $request, bool $cargoIntent): string
    {
        if ($cargoIntent) {
            return 'cargo';
        }

        if ($request->transport_type === CharterRequest::TYPE_HELICOPTER) {
            return 'full_charter';
        }

        $pax = (int) ($request->pax ?? 0);
        return $pax >= 50 ? 'full_charter' : 'acmi';
    }

    /**
     * @return array{passed:bool,reason:string}
     */
    private function passesHardFilters(
        CharterRfqSupplier $supplier,
        int $pax,
        bool $cargoIntent,
        ?int $noticeHours,
        string $marketClass
    ): array {
        if ($supplier->is_cargo_operator && ! $cargoIntent) {
            return ['passed' => false, 'reason' => 'Cargo operator, yolcu talebi degil.'];
        }

        if (! $supplier->is_cargo_operator && $cargoIntent) {
            return ['passed' => false, 'reason' => 'Yolcu operatoru, cargo talebine uygun degil.'];
        }

        if ($supplier->min_pax !== null && $pax > 0 && $pax < (int) $supplier->min_pax) {
            return ['passed' => false, 'reason' => 'Talep pax, minimum degerin altinda.'];
        }

        if ($supplier->max_pax !== null && $pax > 0 && $pax > (int) $supplier->max_pax) {
            return ['passed' => false, 'reason' => 'Talep pax, maksimum degerin ustunde.'];
        }

        if ($supplier->is_premium_only && $pax > 0 && $pax < 80) {
            return ['passed' => false, 'reason' => 'Premium-only operator, bu talep segmenti icin uygun degil.'];
        }

        if (
            $supplier->min_notice_hours !== null
            && $noticeHours !== null
            && $noticeHours < (int) $supplier->min_notice_hours
        ) {
            return ['passed' => false, 'reason' => 'Talep aciliyeti minimum bildirim suresinin altinda.'];
        }

        if ($marketClass === 'small_group' && in_array((string) $supplier->supplier_kind, ['carrier', 'cargo'], true)) {
            return ['passed' => false, 'reason' => 'Kucuk grup talebinde airline/cargo filtrelendi.'];
        }

        return ['passed' => true, 'reason' => 'Kural uyumu saglandi.'];
    }

    private function scoreSupplier(
        CharterRfqSupplier $supplier,
        int $pax,
        bool $cargoIntent,
        string $targetModel,
        string $marketClass,
        bool $hardPassed
    ): float {
        if (! $hardPassed) {
            return 0;
        }

        $score = 50.0;

        $priority = (int) ($supplier->priority ?? 100);
        $score += max(0, 120 - $priority) / 10;

        $models = collect((array) ($supplier->charter_models ?? []))->filter()->values();
        if ($models->isNotEmpty() && $models->contains($targetModel)) {
            $score += 14;
        } elseif ($models->isNotEmpty() && $models->contains('full_charter') && in_array($targetModel, ['acmi', 'block_seat'], true)) {
            $score += 8;
        }

        $kind = (string) ($supplier->supplier_kind ?: 'operator');
        if ($cargoIntent) {
            if (in_array($kind, ['cargo', 'carrier', 'hybrid'], true)) {
                $score += 12;
            }
        } elseif ($marketClass === 'small_group') {
            if (in_array($kind, ['operator', 'hybrid'], true)) {
                $score += 12;
            } elseif ($kind === 'broker') {
                $score += 6;
            }
        } else {
            if (in_array($kind, ['carrier', 'operator', 'hybrid'], true)) {
                $score += 10;
            } elseif ($kind === 'broker') {
                $score += 5;
            }
        }

        if ($pax > 0) {
            if ($supplier->min_pax !== null && $supplier->max_pax !== null) {
                $mid = ((int) $supplier->min_pax + (int) $supplier->max_pax) / 2;
                $distance = abs($pax - $mid);
                $score += max(0, 12 - min(12, $distance / 10));
            } elseif ($supplier->max_pax !== null && $pax <= (int) $supplier->max_pax) {
                $score += 4;
            } elseif ($supplier->min_pax !== null && $pax >= (int) $supplier->min_pax) {
                $score += 4;
            }
        }

        return round($score, 2);
    }
}

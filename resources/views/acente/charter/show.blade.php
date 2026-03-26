<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Air Charter Talep #{{ $charterRequest->id }}</title>
    @include('acente.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family:'Segoe UI',sans-serif; }
        .card-box { border-radius:12px; border:1px solid rgba(0,0,0,.08); }
        .status-pill { font-size:.75rem; border-radius:999px; padding:4px 10px; }
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="charter" />

@php
    $jetDetail = $charterRequest->jetDetail;
    $jetSpecs = is_array($jetDetail?->specs_json) ? $jetDetail->specs_json : [];
    $jetReturnDate = $jetSpecs['return_date'] ?? null;
    $jetReturnDateFormatted = null;
    if ($jetReturnDate) {
        try {
            $jetReturnDateFormatted = \Carbon\Carbon::parse($jetReturnDate)->format('d.m.Y');
        } catch (\Throwable $e) {
            $jetReturnDateFormatted = (string) $jetReturnDate;
        }
    }
    $jetDifferentReturnRoute = (bool) ($jetSpecs['different_return_route'] ?? false);
    $jetReturnFromIata = strtoupper((string) ($jetSpecs['return_from_iata'] ?? $charterRequest->to_iata));
    $jetReturnToIata = strtoupper((string) ($jetSpecs['return_to_iata'] ?? $charterRequest->from_iata));
    $jetSegmentsRaw = $jetSpecs['segments'] ?? [];
    $jetSegments = collect(is_array($jetSegmentsRaw) ? $jetSegmentsRaw : [])
        ->map(function ($segment) {
            return [
                'from_iata' => strtoupper(trim((string) ($segment['from_iata'] ?? ''))),
                'to_iata' => strtoupper(trim((string) ($segment['to_iata'] ?? ''))),
                'departure_date' => trim((string) ($segment['departure_date'] ?? '')),
            ];
        })
        ->filter(function ($segment) {
            return ! empty($segment['from_iata']) && ! empty($segment['to_iata']) && ! empty($segment['departure_date']);
        })
        ->values();

    $flightPlanRows = collect([
        [
            'label' => 'Ana Parkur',
            'from_iata' => strtoupper((string) $charterRequest->from_iata),
            'to_iata' => strtoupper((string) $charterRequest->to_iata),
            'departure_date' => optional($charterRequest->departure_date)->format('d.m.Y') ?: '-',
        ],
    ]);

    if ($jetDetail?->round_trip) {
        $flightPlanRows->push([
            'label' => $jetDifferentReturnRoute ? 'Dönüş (Farklı Rota)' : 'Dönüş',
            'from_iata' => $jetReturnFromIata,
            'to_iata' => $jetReturnToIata,
            'departure_date' => $jetReturnDateFormatted ?: '-',
        ]);
    }

    $jetSegments->each(function ($segment, $index) use ($flightPlanRows) {
        $segmentDate = $segment['departure_date'];
        try {
            $segmentDate = \Carbon\Carbon::parse($segment['departure_date'])->format('d.m.Y');
        } catch (\Throwable $e) {
            $segmentDate = $segment['departure_date'];
        }

        $flightPlanRows->push([
            'label' => 'Ek Parkur #' . ($index + 1),
            'from_iata' => $segment['from_iata'],
            'to_iata' => $segment['to_iata'],
            'departure_date' => $segmentDate,
        ]);
    });

    $jetPreferenceMap = [
        'ekonomik_jet' => 'Ekonomik Jet Öncelikli',
        'vip_jet' => 'VIP Jet Öncelikli',
        'farketmez' => 'Farketmez',
    ];
    $jetPreference = $jetPreferenceMap[$jetDetail?->cabin_preference] ?? ($jetDetail?->cabin_preference ?: '-');
    $jetServiceTags = collect([
        $jetDetail?->round_trip ? 'Gidiş - Dönüş' : null,
        $jetDetail?->pet_onboard ? 'Evcil Hayvan' : null,
        $jetDetail?->vip_catering ? 'VIP Catering' : null,
        $jetDetail?->wifi_required ? 'Wi-Fi' : null,
        $jetDetail?->special_luggage ? 'Özel Bagaj' : null,
    ])->filter()->values();
    $presetPackageSnapshot = is_array($charterRequest->preset_package_snapshot) ? $charterRequest->preset_package_snapshot : [];
    $presetPackageHighlights = collect($presetPackageSnapshot['highlights'] ?? [])->filter()->values();
@endphp

<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-helicopter me-2 text-danger"></i>Air Charter Talep #{{ $charterRequest->id }}
            </h4>
            <div class="text-muted small">
                {{ strtoupper($charterRequest->transport_type) }} ·
                {{ strtoupper($charterRequest->from_iata) }} - {{ strtoupper($charterRequest->to_iata) }} ·
                {{ optional($charterRequest->departure_date)->format('d.m.Y') }}
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('acente.charter.index') }}" class="btn btn-outline-primary btn-sm">Charter Taleplerim</a>
            <a href="{{ route('acente.charter.create') }}" class="btn btn-outline-secondary btn-sm">Yeni Charter Talebi</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card card-box shadow-sm h-100">
                <div class="card-header fw-bold">Talep Ozeti</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <div class="text-muted small">Rota</div>
                            <div class="fw-bold">{{ strtoupper($charterRequest->from_iata) }} → {{ strtoupper($charterRequest->to_iata) }}</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="text-muted small">Gidiş Tarihi</div>
                            <div class="fw-bold">{{ optional($charterRequest->departure_date)->format('d.m.Y') ?: '-' }}</div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="text-muted small">Durum</div>
                            <span class="badge bg-secondary status-pill">{{ $charterRequest->status }}</span>
                        </div>

                        <div class="col-6 col-md-3"><div class="text-muted small">PAX</div><div class="fw-bold">{{ $charterRequest->pax }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted small">Esnek Tarih</div><div class="fw-bold">{{ $charterRequest->is_flexible ? 'Evet' : 'Hayır' }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted small">Uçuş Türü</div><div class="fw-bold text-uppercase">{{ $charterRequest->transport_type }}</div></div>
                        <div class="col-6 col-md-3"><div class="text-muted small">Grup Tipi</div><div class="fw-bold">{{ $charterRequest->group_type ?: '-' }}</div></div>

                        @if($charterRequest->preset_package_title)
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div>
                                            <div class="text-muted small">Secilen Hazir Paket</div>
                                            <div class="fw-bold">{{ $charterRequest->preset_package_title }}</div>
                                        </div>
                                        <div class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            {{ number_format((float) ($charterRequest->preset_package_price ?? 0), 0, ',', '.') }} {{ $charterRequest->preset_package_currency ?: 'EUR' }}
                                        </div>
                                    </div>
                                    @if(!empty($presetPackageSnapshot['summary']))
                                        <div class="small mt-2">{{ $presetPackageSnapshot['summary'] }}</div>
                                    @endif
                                    <div class="small mt-2">
                                        {{ strtoupper((string) ($presetPackageSnapshot['from_iata'] ?? $charterRequest->from_iata)) }}
                                        -
                                        {{ strtoupper((string) ($presetPackageSnapshot['to_iata'] ?? $charterRequest->to_iata)) }}
                                        · {{ $presetPackageSnapshot['aircraft_label'] ?? '-' }}
                                        · {{ (int) ($presetPackageSnapshot['suggested_pax'] ?? $charterRequest->pax) }} PAX
                                    </div>
                                    @if($presetPackageHighlights->isNotEmpty())
                                        <div class="d-flex flex-wrap gap-1 mt-2">
                                            @foreach($presetPackageHighlights as $highlight)
                                                <span class="badge bg-white text-dark border">{{ $highlight }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($charterRequest->transport_type === 'jet')
                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Uçak Tercihi</div>
                                <div class="fw-bold">{{ $jetPreference }}</div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Dönüş Tarihi</div>
                                <div class="fw-bold">
                                    @if($jetDetail?->round_trip)
                                        {{ $jetReturnDateFormatted ?: 'Belirtilmedi' }}
                                    @else
                                        Tek Yön
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Dönüş Rota Tipi</div>
                                <div class="fw-bold">
                                    @if(!$jetDetail?->round_trip)
                                        -
                                    @elseif($jetDifferentReturnRoute)
                                        Farklı Rota
                                    @else
                                        Ana Rotanın Tersi
                                    @endif
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Çoklu Uçuş</div>
                                <div class="fw-bold">{{ $jetSegments->isNotEmpty() ? 'Evet (' . $jetSegments->count() . ' ek parkur)' : 'Hayır' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted small">Jet Hizmetleri</div>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @forelse($jetServiceTags as $tag)
                                        <span class="badge bg-light text-dark border">{{ $tag }}</span>
                                    @empty
                                        <span class="text-muted">Özel hizmet seçilmedi.</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted small mb-1">Uçuş Planı</div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 align-middle">
                                        <thead>
                                        <tr>
                                            <th>Parkur</th>
                                            <th>Kalkış</th>
                                            <th>Varış</th>
                                            <th>Tarih</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($flightPlanRows as $planRow)
                                            <tr>
                                                <td>{{ $planRow['label'] }}</td>
                                                <td>{{ $planRow['from_iata'] }}</td>
                                                <td>{{ $planRow['to_iata'] }}</td>
                                                <td>{{ $planRow['departure_date'] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="col-12"><div class="text-muted small">Talep Notu</div><div>{{ $charterRequest->notes ?: '-' }}</div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card card-box shadow-sm h-100">
                <div class="card-header fw-bold">AI On Teklif</div>
                <div class="card-body">
                    @if($charterRequest->ai_suggested_model)
                        <div class="mb-2"><span class="text-muted small">Model</span><div class="fw-bold">{{ $charterRequest->ai_suggested_model }}</div></div>
                        <div class="mb-2"><span class="text-muted small">Fiyat Araligi</span><div class="fw-bold">{{ number_format((float) $charterRequest->ai_price_min, 0, ',', '.') }} - {{ number_format((float) $charterRequest->ai_price_max, 0, ',', '.') }} {{ $charterRequest->ai_currency }}</div></div>
                        <div class="mb-2"><span class="text-muted small">AI Yorum</span><div>{{ $charterRequest->ai_comment ?: '-' }}</div></div>
                        @if($charterRequest->aircraft_image_url)
                            <img src="{{ $charterRequest->aircraft_image_url }}" alt="aircraft" class="img-fluid rounded border mt-2">
                        @endif
                    @else
                        <div class="text-muted">AI on teklif henuz olusturulmamis.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-box shadow-sm mb-3">
        <div class="card-header fw-bold">Ekstralar</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Baslik</th><th>Acente Notu</th><th>Fiyat</th><th>Durum</th></tr></thead>
                    <tbody>
                    @forelse($charterRequest->extras as $extra)
                        <tr>
                            <td>{{ $extra->title }}</td>
                            <td>{{ $extra->agency_note ?: '-' }}</td>
                            <td>
                                @if($extra->admin_price !== null)
                                    {{ number_format((float) $extra->admin_price, 2, ',', '.') }} {{ $extra->currency }}
                                @else
                                    -
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $extra->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted p-3">Ekstra kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-box shadow-sm mb-3">
        <div class="card-header fw-bold">Acenteye Sunulan Teklifler</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Tedarikci</th><th>Satis Fiyati</th><th>Durum</th><th></th></tr></thead>
                    <tbody>
                    @forelse($charterRequest->salesQuotes as $salesQuote)
                        <tr>
                            <td>{{ $salesQuote->id }}</td>
                            <td>{{ $salesQuote->supplierQuote?->supplier_name ?: '-' }}</td>
                            <td>{{ number_format((float) $salesQuote->sale_price, 2, ',', '.') }} {{ $salesQuote->currency }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $salesQuote->status }}</span></td>
                            <td class="text-end">
                                @if($salesQuote->status === 'sent')
                                    <form method="POST" action="{{ route('acente.charter.accept', [$charterRequest, $salesQuote]) }}">
                                        @csrf
                                        <button class="btn btn-success btn-sm">Teklifi Kabul Et</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted p-3">Henuz satis teklifi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($charterRequest->booking)
        <div class="card card-box shadow-sm">
            <div class="card-header fw-bold">Odeme Ozeti</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3"><div class="text-muted small">Booking Durumu</div><div class="fw-bold">{{ $charterRequest->booking->status }}</div></div>
                    <div class="col-6 col-md-3"><div class="text-muted small">Toplam</div><div class="fw-bold">{{ number_format((float) $charterRequest->booking->total_amount, 2, ',', '.') }}</div></div>
                    <div class="col-6 col-md-3"><div class="text-muted small">Odenen</div><div class="fw-bold">{{ number_format((float) $charterRequest->booking->total_paid, 2, ',', '.') }}</div></div>
                    <div class="col-6 col-md-3"><div class="text-muted small">Kalan</div><div class="fw-bold">{{ number_format((float) $charterRequest->booking->remaining_amount, 2, ',', '.') }}</div></div>
                </div>
                @if((float) $charterRequest->booking->remaining_amount > 0.0001)
                    <form method="POST" action="{{ route('acente.charter.payments.gateway-start', $charterRequest->booking) }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-credit-card me-1"></i>Odemeye Gec (Paynkolay)
                        </button>
                        <div class="small text-muted mt-1">
                            Sadece tam odeme: {{ number_format((float) $charterRequest->booking->remaining_amount, 2, ',', '.') }} {{ $charterRequest->booking->payments->first()?->currency ?: 'EUR' }}
                        </div>
                    </form>
                @endif
                @if($charterRequest->booking->payments->isNotEmpty())
                    <hr>
                    <div class="small text-muted mb-1">Odeme hareketleri</div>
                    @foreach($charterRequest->booking->payments->sortByDesc('id') as $payment)
                        <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 small">
                            <div>#{{ $payment->id }} · {{ number_format((float) $payment->amount, 2, ',', '.') }} {{ $payment->currency }}</div>
                            <span class="badge bg-light text-dark border">{{ $payment->status }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
</div>

@include('acente.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

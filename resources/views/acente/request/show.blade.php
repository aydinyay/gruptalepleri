<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $talep->gtpnr }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
      body { background: #f0f2f5; overflow-y: auto !important; }
        .navbar { background: #1a1a2e !important; }
        .navbar-brand { color: #e94560 !important; font-weight: 700; }
        #map { height: 250px; border-radius: 0 0 12px 12px; display: block; overflow: hidden; }

        .segment-card { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; border-radius: 10px; }
        .iata-code { font-size: 2.2rem; font-weight: 700; letter-spacing: 3px; }
        .teklif-card { border-left: 5px solid #e94560; border-radius: 8px; transition: transform 0.2s; }
        .teklif-card:hover { transform: translateY(-2px); }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 10px; top: 0; bottom: 0; width: 2px; background: #dee2e6; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: -24px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: #e94560; border: 2px solid white; box-shadow: 0 0 0 2px #e94560; }
        .ozet-item { text-align: center; padding: 15px; border-right: 1px solid #dee2e6; }
        .ozet-item:last-child { border-right: none; }
        .ozet-label { font-size: 0.75rem; text-transform: uppercase; color: #6c757d; letter-spacing: 1px; }
        .ozet-value { font-size: 1.1rem; font-weight: 700; margin-top: 4px; }
        .status-beklemede { background: #6c757d; }
        .status-islemde { background: #0d6efd; }
        .status-fiyatlandirildi { background: #ffc107; color: #000 !important; }
        .status-depozitoda { background: #6f42c1; }
        .status-depozito { background: #6f42c1; }
        .status-biletlendi { background: #198754; }
        .status-olumsuz { background: #dc3545; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-0">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('acente.dashboard') }}">✈️ GrupTalepleri</a>
        <div class="d-flex gap-2 align-items-center">
            <x-notification-bell />
            <a href="{{ route('acente.dashboard') }}" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <a href="https://wa.me/905324262630?text={{ urlencode($talep->gtpnr . ' numaralı talep hakkında bilgi almak istiyorum') }}"
               target="_blank" class="btn btn-success btn-sm">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4">

    {{-- 1. HEADER --}}
    @php
        // AI hash hesapla
        $hashKaynagi = $talep->segments->map(fn($s) =>
            $s->from_iata . $s->to_iata . $s->departure_date . $s->departure_time
        )->join('|') . '||' . $talep->offers->map(fn($o) =>
            $o->airline . $o->price_per_pax . $o->total_price . $o->option_date . $o->option_time
        )->join('|') . '||' . $talep->pax_total . $talep->flight_purpose;
        $mevcutHash = md5($hashKaynagi);
        $analizVarMi = $talep->ai_analysis && $talep->ai_analysis_hash === $mevcutHash;
        $analizEskiMi = $talep->ai_analysis && $talep->ai_analysis_hash !== $mevcutHash;

        // Fiyatlı ve görünür ilk teklifi al
        $ilkTeklif = $talep->offers->first(fn($o) => ($o->price_per_pax ?? 0) > 0 && $o->is_visible)
                  ?? $talep->offers->first(fn($o) => !empty($o->airline) && $o->is_visible)
                  ?? null;

        // Havayolu logo haritası (ad → IATA kodu)
        $airlineIata = [
            'turkish airlines' => 'TK', 'thy' => 'TK', 'tk' => 'TK',
            'pegasus' => 'PC', 'pc' => 'PC',
            'sunexpress' => 'XQ', 'sun express' => 'XQ', 'xq' => 'XQ',
            'ajet' => 'VF', 'vf' => 'VF',
            'freebird' => 'FH', 'fh' => 'FH',
            'corendon' => 'CAI', 'corendon airlines' => 'CAI',
            'wizz' => 'W6', 'wizz air' => 'W6', 'w6' => 'W6',
            'ryanair' => 'FR', 'fr' => 'FR',
            'easyjet' => 'U2', 'u2' => 'U2',
            'lufthansa' => 'LH', 'lh' => 'LH',
            'emirates' => 'EK', 'ek' => 'EK',
            'qatar' => 'QR', 'qatar airways' => 'QR', 'qr' => 'QR',
            'flydubai' => 'FZ', 'fz' => 'FZ',
            'atlas' => 'KK', 'atlasjet' => 'KK', 'atlasglobal' => 'KK',
        ];
        $havayoluAdi  = $ilkTeklif?->airline;
        $havayoluIata = $havayoluAdi ? ($airlineIata[strtolower(trim($havayoluAdi))] ?? null) : null;
        $havayoluLogo = $havayoluIata ? "https://images.kiwi.com/airlines/64/{$havayoluIata}.png" : null;

        $opsiyonKalan = null;
        $opsiyonRenk = 'success';
        if ($ilkTeklif?->option_date) {
            $opsiyonTs = \Carbon\Carbon::parse($ilkTeklif->option_date . ' ' . ($ilkTeklif->option_time ?? '23:59'));
            $opsiyonKalan = \Carbon\Carbon::now()->diffInHours($opsiyonTs, false);
            $opsiyonRenk = $opsiyonKalan <= 0 ? 'danger' : ($opsiyonKalan <= 24 ? 'danger' : ($opsiyonKalan <= 48 ? 'warning' : 'success'));
        }
    @endphp
    <div class="card shadow-sm mb-3" style="border-left: 5px solid #e94560;">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold mb-1 fs-3">{{ $talep->gtpnr }}</h2>
                    <div class="text-muted small">
                        ✈️ Uçak Grup Talebi &nbsp;|&nbsp;
                        @foreach($talep->segments as $s)
                            {{ $s->from_iata }}@if(!$loop->last) → @endif{{ $s->to_iata }}
                        @endforeach
                        &nbsp;|&nbsp; {{ $talep->pax_total }} Pax
                    </div>
                </div>
                <div class="text-end">
                    @php $statusClass = 'status-' . $talep->status; @endphp
                    <span class="badge {{ $statusClass }} fs-6 px-3 py-2">{{ ucfirst($talep->status) }}</span>
                    @if($talep->status === 'beklemede')
                    <div class="mt-2">
                        <a href="#" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-edit"></i> Düzenle
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            {{-- DASHBOARD BAR --}}
            <div class="row g-2 mt-2">
                <div class="col-6 col-md-2">
                    <div class="bg-light rounded p-2 text-center h-100">
                        <div class="small text-muted">Rota</div>
                        <div class="fw-bold">
                            {{ $talep->segments->first()?->from_iata }}
                            <i class="fas fa-arrow-right text-danger mx-1" style="font-size:0.7rem;"></i>
                            {{ $talep->segments->last()?->to_iata }}
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="bg-light rounded p-2 text-center h-100">
                        <div class="small text-muted">Gidiş</div>
                        <div class="fw-bold">
                            @if($talep->segments->first()?->departure_date)
                                {{ \Carbon\Carbon::parse($talep->segments->first()->departure_date)->format('d M Y') }}
                            @else — @endif
                        </div>
                        @if($talep->segments->first()?->departure_time)
                        <div class="small text-muted">{{ $talep->segments->first()->departure_time }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-1">
                    <div class="bg-light rounded p-2 text-center h-100">
                        <div class="small text-muted">PAX</div>
                        <div class="fw-bold text-primary">{{ $talep->pax_total }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="bg-light rounded p-2 text-center h-100">
                        <div class="small text-muted">Havayolu</div>
                        @if($havayoluLogo)
                            <img src="{{ $havayoluLogo }}" alt="{{ $havayoluAdi }}"
                                 style="max-height:28px; max-width:72px; object-fit:contain;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <div class="fw-bold" style="display:none;">{{ $havayoluAdi }}</div>
                        @else
                            <div class="fw-bold">{{ $havayoluAdi ?? '—' }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="bg-light rounded p-2 text-center h-100">
                        <div class="small text-muted">Kişi Başı</div>
                        <div class="fw-bold text-success">
                            @if($ilkTeklif?->price_per_pax > 0)
                                {{ number_format($ilkTeklif->price_per_pax, 0) }} {{ $ilkTeklif->currency }}
                            @else — @endif
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-{{ $opsiyonRenk }} bg-opacity-10 border border-{{ $opsiyonRenk }} border-opacity-25 rounded p-2 text-center h-100">
                        <div class="small text-muted">Opsiyon</div>
                        @if($opsiyonKalan === null)
                            <div class="fw-bold text-muted">—</div>
                        @elseif($opsiyonKalan <= 0)
                            <div class="fw-bold text-danger"><i class="fas fa-ban me-1"></i>Süresi Doldu</div>
                        @else
                            @php $gun = floor($opsiyonKalan / 24); $saat = $opsiyonKalan % 24; @endphp
                            <div class="fw-bold text-{{ $opsiyonRenk }}">
                                @if($gun > 0) {{ $gun }} gün @endif{{ $saat }} saat kaldı
                            </div>
                            <div class="small text-muted">{{ $opsiyonTs->format('d.m.Y H:i') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-md-7">

            {{-- 3. HARİTA --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center"
                     style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#harita-collapse" aria-expanded="true">
                    <span>🗺️ Rota Haritası</span>
                    <i class="fas fa-chevron-up" id="harita-chevron" style="transition:transform 0.2s;"></i>
                </div>
                <div class="collapse show" id="harita-collapse">
                    <div id="map"></div>
                </div>
            </div>

            {{-- UÇUŞ SEGMENTLERİ --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold">✈️ Uçuş Segmentleri</div>
                <div class="card-body">
                    @foreach($talep->segments as $segment)
                    <div class="segment-card p-3 mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="text-center">
                                <div class="iata-code">{{ $segment->from_iata }}</div>
                                <small class="opacity-75">Kalkış</small>
                            </div>
                            <div class="text-center flex-grow-1">
                                <div style="height:2px;background:rgba(255,255,255,0.3);position:relative;margin:0 20px;">
                                    <span style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);font-size:20px;">✈</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="iata-code">{{ $segment->to_iata }}</div>
                                <small class="opacity-75">Varış</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-3 opacity-75">
                            <small><i class="fas fa-calendar"></i> {{ $segment->departure_date }}</small>
                            @if($segment->departure_time)
                            <small><i class="fas fa-clock"></i> {{ $segment->departure_time }}</small>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    @if($talep->group_company_name || $talep->email || $talep->phone)
                    <div class="card shadow-sm mb-3 mt-3">
                        <div class="card-body">
                            <div class="row g-2">
                                @if($talep->group_company_name)
                                <div class="col-6">
                                    <small class="text-muted">Grup Firma</small>
                                    <div class="fw-bold">{{ $talep->group_company_name }}</div>
                                </div>
                                @endif
                                @if($talep->email)
                                <div class="col-6">
                                    <small class="text-muted">E-posta</small>
                                    <div class="fw-bold">{{ $talep->email }}</div>
                                </div>
                                @endif
                                @if($talep->phone)
                                <div class="col-6">
                                    <small class="text-muted">Telefon</small>
                                    <div class="fw-bold">{{ $talep->phone }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($talep->notes)
                    <div class="alert alert-light mt-2">
                        <small class="text-muted">📝 Not:</small>
                        <div>{{ $talep->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- AI HAVALİMANI İSTİHBARATI --}}
            <div class="card shadow-sm mb-4" id="ai-havalimani-kart">
                <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                    <div>
                        <span>🤖 AI Operasyon Analizi</span>
                        @if($analizVarMi)
                            <small class="text-muted fw-normal ms-2">
                                · {{ $talep->ai_analysis_updated_at?->format('d.m.Y H:i') }}
                            </small>
                        @endif
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @if($analizEskiMi)
                            <span class="badge bg-warning text-dark">Veri değişti</span>
                        @endif
                        <button class="btn btn-sm {{ $analizVarMi ? 'btn-outline-secondary' : 'btn-dark' }}"
                                id="ai-analiz-btn" onclick="aiAnalizBaslat()">
                            @if($analizVarMi)
                                <i class="fas fa-sync me-1"></i> Yenile
                            @else
                                <i class="fas fa-robot me-1"></i> Analiz Başlat
                            @endif
                        </button>
                    </div>
                </div>
                <div class="card-body" id="ai-analiz-icerik">
                    @if($analizVarMi || $analizEskiMi)
                        {!! $talep->ai_analysis !!}
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-robot fa-3x opacity-25 mb-3 d-block"></i>
                            <div>Havalimanı istihbaratı, transfer önerisi ve operasyon notları için butona tıklayın.</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 7. TİMELINE --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold">📅 Operasyon Zaman Çizelgesi :</div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="fw-bold">Talep Oluşturuldu</div>
                            <small class="text-muted">{{ $talep->created_at->format('d.m.Y H:i') }}</small>
                        </div>
                        @foreach($talep->logs as $log)
                        <div class="timeline-item">
                            <div class="fw-bold">{{ $log->description }}</div>
                            <small class="text-muted">
                                {{ $log->created_at->format('d.m.Y H:i') }}
                                @if($log->user) · {{ $log->user->name }} @endif
                            </small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- SMS BİLDİRİMLERİ --}}
            @php $acenteNotifs = $talep->notifications->where('recipient', 'acente'); @endphp
            @if($acenteNotifs->isNotEmpty())
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold">📲 Size Gönderilen SMS Bildirimleri</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 small">
                        <tbody>
                            @foreach($acenteNotifs as $notif)
                            <tr>
                                <td class="text-muted text-nowrap ps-3">{{ $notif->created_at->format('d.m.Y H:i') }}</td>
                                <td>{{ $notif->message }}</td>
                                <td class="pe-3 text-nowrap">
                                    @if($notif->status === 'sent')
                                        <span class="badge bg-success">İletildi</span>
                                    @elseif($notif->status === 'failed')
                                        <span class="badge bg-danger">Hata</span>
                                    @else
                                        <span class="badge bg-secondary">Bekliyor</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        <div class="col-md-5">

            {{-- 5. TEKLİFLER --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold text-danger">
                    💰 Teklifler
                    <span class="badge bg-danger ms-1">{{ $talep->offers->where('price_per_pax', '>', 0)->where('is_visible', true)->count() }}</span>
                </div>
                <div class="card-body">
                    @forelse($talep->offers->where('price_per_pax', '>', 0)->where('is_visible', true) as $teklif)
                    @php
                        $tklKey  = strtolower(trim($teklif->airline ?? ''));
                        $tklIata = $airlineIata[$tklKey] ?? null;
                        $tklLogo = $tklIata ? "https://images.kiwi.com/airlines/64/{$tklIata}.png" : null;
                    @endphp
                    <div class="teklif-card card mb-3 p-3">
                        {{-- Havayolu başlık --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                @if($tklLogo)
                                    <img src="{{ $tklLogo }}" alt="{{ $teklif->airline }}"
                                         style="max-height:30px; max-width:80px; object-fit:contain;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='';">
                                    <span class="fw-bold fs-5" style="display:none;">{{ $teklif->airline ?? '—' }}</span>
                                @else
                                    <span class="fw-bold fs-5">{{ $teklif->airline ?? '—' }}</span>
                                @endif
                            </div>
                            <span class="badge bg-secondary">{{ $teklif->currency }}</span>
                        </div>

                        {{-- Uçuş bilgileri --}}
                        @if($teklif->airline_pnr || $teklif->flight_number || $teklif->flight_departure_time || $teklif->baggage_kg || $teklif->pax_confirmed)
                        <div class="rounded p-2 mb-3" style="background:#f0f4ff; border:1px solid #c5d3f0;">
                            <div class="row g-2 text-center">
                                @if($teklif->airline_pnr)
                                <div class="col-6">
                                    <div class="small text-muted">PNR</div>
                                    <div class="fw-bold font-monospace text-primary">{{ $teklif->airline_pnr }}</div>
                                </div>
                                @endif
                                @if($teklif->flight_number)
                                <div class="col-6">
                                    <div class="small text-muted">Sefer</div>
                                    <div class="fw-bold">{{ $teklif->flight_number }}</div>
                                </div>
                                @endif
                                @if($teklif->flight_departure_time || $teklif->flight_arrival_time)
                                <div class="col-6">
                                    <div class="small text-muted">Saat</div>
                                    <div class="fw-bold">{{ $teklif->flight_departure_time ? substr($teklif->flight_departure_time, 0, 5) : '--' }} → {{ $teklif->flight_arrival_time ? substr($teklif->flight_arrival_time, 0, 5) : '--' }}</div>
                                </div>
                                @endif
                                @if($teklif->baggage_kg)
                                <div class="col-6">
                                    <div class="small text-muted">Bagaj</div>
                                    <div class="fw-bold">🧳 {{ $teklif->baggage_kg }} KG</div>
                                </div>
                                @endif
                                @if($teklif->pax_confirmed)
                                <div class="col-6">
                                    <div class="small text-muted">PAX</div>
                                    <div class="fw-bold">👥 {{ $teklif->pax_confirmed }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <div class="row g-2 text-center mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded p-2">
                                    <div class="small text-muted">Kişi Başı</div>
                                    <div class="fw-bold text-success fs-5">
                                        {{ number_format($teklif->price_per_pax, 0) }} {{ $teklif->currency }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2">
                                    <div class="small text-muted">Toplam</div>
                                    <div class="fw-bold fs-5">
                                        {{ number_format($teklif->total_price, 0) }} {{ $teklif->currency }}
                                    </div>
                                </div>
                            </div>
                            @if($teklif->deposit_amount)
                            <div class="col-6">
                                <div class="bg-warning bg-opacity-10 rounded p-2">
                                    <div class="small text-muted">Depozito (%{{ $teklif->deposit_rate }})</div>
                                    <div class="fw-bold text-warning">
                                        {{ number_format($teklif->deposit_amount, 0) }} {{ $teklif->currency }}
                                    </div>
                                </div>
                            </div>
                            @endif
            @if($teklif->option_date)
@php
    $opsiyonStr = $teklif->option_date . ' ' . ($teklif->option_time ?? '23:59');
    $opsiyonTs = \Carbon\Carbon::parse($opsiyonStr);
    $kalanSaniye = \Carbon\Carbon::now()->diffInSeconds($opsiyonTs, false);
    $kalanSaat = $kalanSaniye / 3600;
@endphp
<div class="col-12">
@if($kalanSaniye <= 0)
    <div class="alert alert-danger text-center fw-bold py-2 mb-0">
        <i class="fas fa-ban me-1"></i> OPSİYON SÜRESİ DOLDU
        <div class="small fw-normal">{{ $opsiyonTs->format('d.m.Y H:i') }}</div>
    </div>
@elseif($kalanSaat <= 6)
    <div class="alert alert-danger border-2 mb-0 py-2" id="sayac-kutu-{{ $teklif->id }}">
        <div class="fw-bold text-danger"><i class="fas fa-exclamation-triangle me-1"></i>KRİTİK — OPSİYON BİTİYOR!</div>
        <div class="fs-3 fw-bold font-monospace text-danger" id="sayac-{{ $teklif->id }}">--:--:--</div>
        <small>Son: {{ $opsiyonTs->format('d.m.Y H:i') }}</small>
    </div>
@elseif($kalanSaat <= 24)
    <div class="alert alert-warning border-2 mb-0 py-2" id="sayac-kutu-{{ $teklif->id }}">
        <div class="fw-bold"><i class="fas fa-clock me-1"></i>Opsiyon Bitiyor</div>
        <div class="fs-4 fw-bold font-monospace text-warning" id="sayac-{{ $teklif->id }}">--:--:--</div>
        <small>Son: {{ $opsiyonTs->format('d.m.Y H:i') }}</small>
    </div>
@else
    <div class="bg-success bg-opacity-10 rounded p-2 border border-success border-opacity-25" id="sayac-kutu-{{ $teklif->id }}">
        <div class="small text-muted"><i class="fas fa-hourglass-half me-1 text-success"></i>Opsiyona Kalan</div>
        <div class="fw-bold font-monospace text-success" id="sayac-{{ $teklif->id }}">--g --:--:--</div>
        <small class="text-muted">Son: {{ $opsiyonTs->format('d.m.Y H:i') }}</small>
    </div>
@endif
<input type="hidden" id="opsiyon-ts-{{ $teklif->id }}" value="{{ $opsiyonTs->timestamp }}">
</div>
@endif
                        </div>

                        @if($teklif->offer_text)
                        <details class="mb-3">
                            <summary class="small text-muted" style="cursor:pointer; user-select:none;">
                                📄 Operasyon Notunu Gör
                            </summary>
                            <div class="bg-light rounded p-2 mt-2 small font-monospace" style="white-space:pre-wrap; font-size:0.78rem; color:#555;">{{ $teklif->offer_text }}</div>
                        </details>
                        @endif

                        @if($teklif->created_by)
                        <div class="text-muted mb-3" style="font-size:0.8rem;">
                            <i class="fas fa-user me-1"></i>Teklifi Hazırlayan: <strong>{{ $teklif->created_by }}</strong>
                        </div>
                        @endif

                        @if($teklif->is_accepted)
                        <div class="alert alert-success py-2 mb-2 text-center">
                            <i class="fas fa-check-circle me-1"></i><strong>Kabul Edildi</strong>
                            <div class="small text-muted">{{ $teklif->accepted_at ? \Carbon\Carbon::parse($teklif->accepted_at)->format('d.m.Y H:i') : '' }}</div>
                        </div>
                        @endif

                        <div class="d-flex gap-2">
                            @if(!$teklif->is_accepted)
                            <button type="button" class="btn btn-success btn-sm flex-fill"
                                onclick="kabulOnayGoster(
                                    {{ $teklif->id }},
                                    '{{ addslashes($teklif->airline ?? '—') }}',
                                    '{{ number_format($teklif->price_per_pax, 0) }} {{ $teklif->currency }}',
                                    '{{ number_format($teklif->total_price, 0) }} {{ $teklif->currency }}',
                                    '{{ $teklif->option_date ? \Carbon\Carbon::parse($teklif->option_date)->format("d.m.Y") . " " . substr($teklif->option_time ?? "23:59", 0, 5) : "—" }}'
                                )">
                                <i class="fas fa-check me-1"></i> Kabul Et
                            </button>
                            @else
                            <a href="https://wa.me/905324262630?text={{ urlencode($talep->gtpnr . ' - depozito ödemesi hakkında bilgi almak istiyorum') }}"
                               target="_blank" class="btn btn-success btn-sm flex-fill">
                                <i class="fab fa-whatsapp me-1"></i> Depozito Bilgisi Al
                            </a>
                            @endif
                            <a href="https://wa.me/905324262630?text={{ urlencode($talep->gtpnr . ' - ' . ($teklif->airline ?? '') . ' teklifi hakkında sorum var') }}"
                               target="_blank" class="btn btn-outline-secondary btn-sm flex-fill">
                                <i class="fas fa-question"></i> Sor
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-hourglass-half fa-3x mb-3 opacity-25"></i>
                        <p class="mb-1">Teklif hazırlanıyor...</p>
                        <small>Operasyon ekibimiz en kısa sürede size dönecektir.</small>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- 6. MUHASEBE & ÖDEMELER --}}
            @if($talep->offers->count() > 0)
            @php
                $ilkTeklif = $talep->offers->first(fn($o) => ($o->price_per_pax ?? 0) > 0)
                          ?? $talep->offers->first();
                $toplamTutar = $ilkTeklif->total_price;
                $odenenCurrency = $ilkTeklif->currency;
                $toplamOdenen = $talep->payments->where('status', 'alindi')->sum('amount');
                $kalanTutar = max(0, $toplamTutar - $toplamOdenen);
                $yuzde = $toplamTutar > 0 ? min(100, round(($toplamOdenen / $toplamTutar) * 100)) : 0;
            @endphp
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-bold">💳 Muhasebe Durumu</div>
                <div class="card-body">

                    {{-- Progress bar --}}
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Tahsilat</span>
                        <span class="fw-bold">%{{ $yuzde }}</span>
                    </div>
                    <div class="progress mb-3" style="height:12px; border-radius:6px;">
                        <div class="progress-bar {{ $yuzde >= 100 ? 'bg-success' : ($yuzde >= 50 ? 'bg-primary' : 'bg-warning') }}"
                             style="width:{{ $yuzde }}%; border-radius:6px;"></div>
                    </div>

                    {{-- Özet satırlar --}}
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>Toplam Tutar</span>
                        <strong>{{ number_format($toplamTutar, 0) }} {{ $odenenCurrency }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom text-success">
                        <span>Toplam Ödenen</span>
                        <strong>{{ number_format($toplamOdenen, 0) }} {{ $odenenCurrency }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 {{ $kalanTutar > 0 ? 'text-danger' : 'text-success' }}">
                        <span>Kalan Bakiye</span>
                        <strong>{{ number_format($kalanTutar, 0) }} {{ $odenenCurrency }}</strong>
                    </div>

                    {{-- Ödeme detayları --}}
                    @if($talep->payments->count() > 0)
                    <hr class="my-2">
                    @foreach($talep->payments as $odeme)
                    <div class="d-flex justify-content-between align-items-center py-1 small">
                        <div>
                            <span class="fw-bold">{{ $odeme->sequence }}. {{ ucfirst($odeme->payment_type) }}</span>
                            @if($odeme->payment_date)
                            <span class="text-muted ms-1">· {{ \Carbon\Carbon::parse($odeme->payment_date)->format('d.m.Y') }}</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="{{ $odeme->status === 'alindi' ? 'text-success fw-bold' : ($odeme->status === 'iade' ? 'text-danger' : 'text-warning fw-bold') }}">
                                {{ number_format($odeme->amount, 0) }} {{ $odeme->currency }}
                            </span>
                            @if($odeme->status === 'bekleniyor')
                                <span class="badge bg-warning text-dark" style="font-size:0.65rem;">Bekleniyor</span>
                            @elseif($odeme->status === 'iade')
                                <span class="badge bg-danger" style="font-size:0.65rem;">İade</span>
                            @else
                                <span class="badge bg-success" style="font-size:0.65rem;">Alındı</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @endif

                </div>
            </div>
            @endif

            {{-- İLETİŞİM --}}
            <div class="card shadow-sm">
                <div class="card-header fw-bold">📞 İletişim</div>
                <div class="card-body d-grid gap-2">
                    <a href="https://wa.me/905324262630?text={{ urlencode($talep->gtpnr . ' numaralı talep hakkında bilgi almak istiyorum') }}"
                       target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp"></i> WhatsApp ile Yaz
                    </a>
                    <a href="tel:+905324262630" class="btn btn-outline-primary">
                        <i class="fas fa-phone"></i> Ara
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
async function aiAnalizBaslat() {
    const btn = document.getElementById('ai-analiz-btn');
    const icerik = document.getElementById('ai-analiz-icerik');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Analiz yapılıyor...';

    const fromIata = "{{ $talep->segments->first()?->from_iata }}";
    const toIata   = "{{ $talep->segments->last()?->to_iata }}";
    const pax      = {{ $talep->pax_total ?? 0 }};
    const tarih    = "{{ $talep->segments->first()?->departure_date }}";
    const amaç     = "{{ $talep->flight_purpose ?? '' }}";

    @php
    $offerData = $talep->offers->map(function($o) {
        return [
            'airline'         => $o->airline,
            'currency'        => $o->currency,
            'price_per_pax'   => $o->price_per_pax,
            'total_price'     => $o->total_price,
            'cost_price'      => $o->cost_price,
            'profit_amount'   => $o->profit_amount,
            'profit_percent'  => $o->profit_percent,
            'deposit_rate'    => $o->deposit_rate,
            'deposit_amount'  => $o->deposit_amount,
            'option_date'     => $o->option_date,
            'option_time'     => $o->option_time,
            'offer_text'      => $o->offer_text,
            'created_by'      => $o->created_by,
        ];
    })->values()->toArray();
    @endphp
    const teklifler = @json($offerData);

    let teklifBilgisi = 'Henüz teklif girilmemiş.';
    if (teklifler.length > 0) {
        teklifBilgisi = teklifler.map((t, i) => {
            const karMarj = t.profit_percent ? `%${t.profit_percent}` : '-';
            const karTutar = t.profit_amount ? `${t.profit_amount} ${t.currency}` : '-';
            const depozito = t.deposit_amount ? `${t.deposit_amount} ${t.currency} (%${t.deposit_rate})` : '-';
            const opsiyon = (t.option_date && t.option_time) ? `${t.option_date} ${t.option_time}` : (t.option_date || '-');
            return `Teklif #${i+1}: ${t.airline || '-'} | Kişi Başı: ${t.price_per_pax} ${t.currency} | Toplam Satış: ${t.total_price} ${t.currency} | Maliyet: ${t.cost_price || '-'} ${t.currency} | Kâr: ${karTutar} (${karMarj}) | Depozito: ${depozito} | Opsiyon: ${opsiyon}${t.offer_text ? ' | Not: ' + t.offer_text : ''}${t.created_by ? ' | Hazırlayan: ' + t.created_by : ''}`;
        }).join('\n');
    }

    const prompt = `Sen bir havacılık operasyon uzmanısın. Aşağıdaki grup uçuşu için KISA ve ÖZET analiz yap.

TALEP: ${fromIata} → ${toIata} | ${pax} PAX | ${tarih} | ${amaç || '-'}
TEKLİF: ${teklifBilgisi}

4 ayrı Bootstrap card oluştur. Her card MAX 4-5 madde, kısa cümleler, emoji ikon kullan.

CARD 1 (border-primary): 🛬 VARIŞ - ${toIata}
• Şehir ve uzaklık
• En iyi ulaşım (${pax} kişi için)
• Tahmini transfer süresi/ücreti
• 1 kritik operasyon notu

CARD 2 (border-success): 🛫 KALKIŞ - ${fromIata}
• Kaç saat önce havalimanında olunmalı
• Check-in tahmini süre
• Terminal/buluşma noktası
• 1 önemli not

CARD 3 (border-warning): 📅 TARİH - ${tarih}
• Hava durumu karakteri
• Yakın tatil/bayram/etkinlik varsa
• Trafik/yoğunluk uyarısı

CARD 4 (border-purple, style="border-color:#6f42c1"): 💰 FİNANS
• Kişi başı fiyat değerlendirmesi
• Kar marjı: yeterli/düşük/yüksek
• Opsiyon riski
• Öneri: kabul et / bekle / müzakere et

Sadece 4 card HTML ver, başka hiçbir şey yazma. Her card compact olsun, card-body padding az olsun (p-2).`;

    try {
        const response = await fetch('{{ route("acente.requests.ai-analiz", $talep->gtpnr) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ prompt })
        });

        const raw  = await response.text();
        let data;
        try { data = JSON.parse(raw); } catch(je) {
            icerik.innerHTML = '<div class="alert alert-danger">Sunucu geçersiz yanıt döndürdü:<br><pre style="font-size:11px">' + raw.substring(0, 300) + '</pre></div>';
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-robot me-1"></i> Tekrar Dene';
            return;
        }
        const metin = data.html ?? data.error ?? 'Analiz alınamadı.';

        // Markdown code block varsa temizle
        const temiz = metin.replace(/```html|```/g, '').trim();

        icerik.innerHTML = temiz;
        btn.innerHTML = '<i class="fas fa-check me-1"></i> Analiz Tamamlandı';
        btn.className = 'btn btn-sm btn-success';

        // DB'ye kaydet
        fetch('{{ route("acente.requests.ai-kaydet", $talep->gtpnr) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ html: temiz, hash: '{{ $mevcutHash }}' })
        }).catch(() => {});  // sessiz hata — kayıt başarısız olsa da UI etkilenmesin

    } catch (e) {
        icerik.innerHTML = '<div class="alert alert-danger">Analiz sırasında hata oluştu: ' + e.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-robot me-1"></i> Tekrar Dene';
    }
}
</script>

<script>
@php
$segmentData = $talep->segments->map(function($s) {
    return [
        'from'     => $s->from_iata,
        'to'       => $s->to_iata,
        'fromCity' => $s->from_city ? $s->from_city : $s->from_iata,
        'toCity'   => $s->to_city   ? $s->to_city   : $s->to_iata,
    ];
})->values()->toArray();
@endphp
const segmentler = @json($segmentData);

const havalimanları = {
    'IST': {lat:41.2753,lng:28.7519}, 'SAW': {lat:40.8985,lng:29.3092},
    'ESB': {lat:40.1281,lng:32.9951}, 'AYT': {lat:36.8987,lng:30.7992},
    'ADB': {lat:38.2924,lng:27.1570}, 'CDG': {lat:49.0097,lng:2.5479},
    'LHR': {lat:51.4700,lng:-0.4543}, 'LGW': {lat:51.1537,lng:-0.1821},
    'DXB': {lat:25.2532,lng:55.3657}, 'JFK': {lat:40.6413,lng:-73.7781},
    'FRA': {lat:50.0379,lng:8.5622}, 'AMS': {lat:52.3105,lng:4.7683},
    'BCN': {lat:41.2974,lng:2.0833}, 'FCO': {lat:41.8003,lng:12.2389},
    'MUC': {lat:48.3538,lng:11.7861}, 'GZT': {lat:36.9473,lng:37.4787},
    'TZX': {lat:40.9950,lng:39.7897}, 'JNB': {lat:-26.1392,lng:28.2460},
    'AUH': {lat:24.4330,lng:54.6511}, 'DOH': {lat:25.2732,lng:51.6080},
    'BKK': {lat:13.6811,lng:100.7472}, 'SIN': {lat:1.3644,lng:103.9915},
};

function initMap() {
    const map = new google.maps.Map(document.getElementById('map'), {
        zoom: 5,
        center: {lat: 41.0, lng: 29.0},
        mapTypeId: 'roadmap',
        styles: [{featureType:'poi',stylers:[{visibility:'off'}]}]
    });
    map.addListener('click', function() {});
    document.getElementById('map').addEventListener('wheel', function(e) {
        e.stopPropagation();
    }, { passive: true });

    const geocoder = new google.maps.Geocoder();

    function getCoords(iata, city, callback) {
        if (havalimanları[iata]) {
            callback(havalimanları[iata]);
        } else if (city) {
            geocoder.geocode({ address: city + ' airport' }, function(results, status) {
                if (status === 'OK') callback(results[0].geometry.location);
            });
        }
    }

    function addMarker(pos, code) {
        new google.maps.Marker({
            position: pos,
            map: map,
            title: code,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: '#e94560',
                fillOpacity: 1,
                strokeColor: 'white',
                strokeWeight: 2
            },
            label: { text: code, color: 'white', fontSize: '9px', fontWeight: 'bold' }
        });
    }

    segmentler.forEach(segment => {
        getCoords(segment.from, segment.fromCity, function(from) {
            getCoords(segment.to, segment.toCity, function(to) {
                if (!from || !to) return;

                new google.maps.Polyline({
                    path: [from, to],
                    geodesic: true,
                    strokeColor: '#e94560',
                    strokeOpacity: 0.9,
                    strokeWeight: 3,
                    map: map
                });

                addMarker(from, segment.from);
                addMarker(to, segment.to);
            });
        });
    });
}
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA4CoEHudF9V3Zn4h6udx6Ftr3u6h51EXo&libraries=geometry&callback=initMap" async defer></script>
</body>
<script>
// Opsiyon sayaçları
document.querySelectorAll('[id^="opsiyon-ts-"]').forEach(input => {
    const tId = input.id.replace('opsiyon-ts-', '');
    const hedef = parseInt(input.value) * 1000;
    const sayacEl = document.getElementById('sayac-' + tId);
    const kutuEl = document.getElementById('sayac-kutu-' + tId);
    if (!sayacEl) return;

    function guncelle() {
        const kalan = hedef - Date.now();
        if (kalan <= 0) {
            sayacEl.closest('.alert, .bg-success') && (sayacEl.closest('[id^="sayac-kutu"]').innerHTML =
                '<div class="fw-bold text-danger"><i class="fas fa-ban me-1"></i>OPSİYON SÜRESİ DOLDU</div>');
            return;
        }

        const gun = Math.floor(kalan / 86400000);
        const saat = Math.floor((kalan % 86400000) / 3600000);
        const dk = Math.floor((kalan % 3600000) / 60000);
        const sn = Math.floor((kalan % 60000) / 1000);

        if (gun > 0) {
            sayacEl.textContent = gun + 'g ' + String(saat).padStart(2,'0') + ':' + String(dk).padStart(2,'0') + ':' + String(sn).padStart(2,'0');
        } else {
            sayacEl.textContent = String(saat).padStart(2,'0') + ':' + String(dk).padStart(2,'0') + ':' + String(sn).padStart(2,'0');
        }

        // Son 6 saat: yanıp sön
        if (kalan < 6 * 3600000 && kutuEl) {
            kutuEl.style.animation = 'none';
            setTimeout(() => { if(kutuEl) kutuEl.style.animation = ''; }, 10);
        }

        setTimeout(guncelle, 1000);
    }
    guncelle();
});
</script>

<style>
@keyframes blink-alarm {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
.alert-danger [id^="sayac-"] {
    animation: blink-alarm 1s infinite;
}
</style>
<script>
const haritaCollapse = document.getElementById('harita-collapse');
const haritaChevron = document.getElementById('harita-chevron');
if (haritaCollapse) {
    haritaCollapse.addEventListener('hide.bs.collapse', () => haritaChevron.style.transform = 'rotate(180deg)');
    haritaCollapse.addEventListener('show.bs.collapse', () => haritaChevron.style.transform = 'rotate(0deg)');
}
</script>

{{-- KABUL ONAY MODALI --}}
<div class="modal fade" id="kabulOnayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fas fa-check-circle me-2"></i>Teklifi Kabul Et
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Aşağıdaki teklifi kabul etmek üzeresiniz:</p>
                <div class="bg-light rounded p-3 mb-3">
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="small text-muted">Havayolu</div>
                            <div class="fw-bold" id="k-airline"></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Kişi Başı</div>
                            <div class="fw-bold text-success" id="k-price"></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Toplam</div>
                            <div class="fw-bold" id="k-total"></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Opsiyon Bitiş</div>
                            <div class="fw-bold text-danger" id="k-option"></div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning py-2 small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Kabul ettikten sonra operasyon ekibimiz depozito bilgilerini WhatsApp üzerinden iletecektir.
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Vazgeç
                </button>
                <form id="kabul-form" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-check me-1"></i>Evet, Kabul Ediyorum
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const kabulModal = new bootstrap.Modal(document.getElementById('kabulOnayModal'));
const KABUL_BASE = '{{ url("acente/talep/" . $talep->gtpnr . "/teklif") }}';

function kabulOnayGoster(id, airline, price, total, option) {
    document.getElementById('k-airline').textContent = airline;
    document.getElementById('k-price').textContent   = price;
    document.getElementById('k-total').textContent   = total;
    document.getElementById('k-option').textContent  = option;
    document.getElementById('kabul-form').action     = KABUL_BASE + '/' + id + '/kabul';
    kabulModal.show();
}
</script>
</html>
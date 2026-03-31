<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('acente.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acente Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        #map { height: 400px; width: 100%; border-radius: 0 0 8px 8px; }
        .map-filters { padding: 10px 15px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #dee2e6; }
        .filter-badge { cursor: pointer; user-select: none; }
        html[data-theme="dark"] .map-filters { background: #16213e !important; border-color: #2a2a4e !important; }
        html[data-theme="light"] .map-filters { background: #fff; }

        /* Stat chips */
        .stat-chips { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:1.2rem; align-items:center; }
        .stat-chip {
            display:inline-flex; align-items:center; gap:7px;
            padding:6px 14px; border-radius:999px;
            font-size:0.82rem; font-weight:600;
            border:2px solid; cursor:default;
            transition:transform 0.15s, box-shadow 0.15s;
            white-space:nowrap;
        }
        .stat-chip:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.15); }
        .stat-chip .chip-num { font-size:1.05rem; font-weight:800; line-height:1; }
        .stat-chip .chip-icon { font-size:0.85rem; opacity:0.85; }
        html[data-theme="dark"] .stat-chip { background: rgba(255,255,255,0.04); }
        html[data-theme="light"] .stat-chip { background: #fff; }
        .stat-chip-zero { opacity:0.45; }

        /* Table */
        #talepTablosu { font-size:0.79rem; }
        #talepTablosu thead tr th {
            background: #0d6efd;
            color: #fff;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            white-space: nowrap;
            padding: 8px 6px;
            border: none;
        }
        #talepTablosu tbody tr { cursor: pointer; }
        html[data-theme="dark"] #talepTablosu tbody tr:hover { background:#2a2a4e !important; }
        html[data-theme="light"] #talepTablosu tbody tr:hover { background:#f0f5ff !important; }
        #talepTablosu td { vertical-align: middle; padding: 5px 6px; }
        .airline-logo { width:64px; height:auto; max-height:32px; object-fit:contain; display:block; }
        .airline-cell { text-align:center; min-width:72px; }
        .airline-name { font-size:0.62rem; color:#888; margin-top:2px; display:block; line-height:1.2; }
        .yon-badge { font-size:0.68rem; padding:2px 7px; }
        .tur-badge { font-size:0.64rem; padding:2px 6px; }
        .gtpnr-code { color:#e94560; font-family:monospace; font-weight:700; font-size:0.85rem; }
        .parkur { font-weight:700; font-size:0.82rem; letter-spacing:0.5px; }

        /* DataTables overrides */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_info { font-size:0.78rem; }
        .dataTables_wrapper .dataTables_paginate .page-link { font-size:0.78rem; padding:3px 8px; }
        html[data-theme="dark"] .dataTables_wrapper .dataTables_info { color:#adb5bd; }
        html[data-theme="dark"] table.dataTable tbody tr { color: inherit; }
        html[data-theme="dark"] table.dataTable thead th,
        html[data-theme="dark"] table.dataTable thead td { border-bottom-color: #2a2a4e; }
        html[data-theme="dark"] table.dataTable.stripe tbody tr.odd,
        html[data-theme="dark"] table.dataTable.display tbody tr.odd { background-color: rgba(255,255,255,0.025); }
    </style>
</head>
<body>

<x-navbar-acente active="dashboard" />

<div class="container-fluid px-4">
    @php
        $statusMetaMap = \App\Models\Request::statusMetaMap();
        $dashboardStatusOrder = ['beklemede', 'islemde', 'fiyatlandirildi', 'iptal', 'biletlendi', 'depozitoda'];
        $trIata = ['IST','SAW','ESB','AYT','ADB','GZT','TZX','DIY','DYB','ASR','VAN','EZS','BJV','MLX','SZF','KYA','ERZ','ERC','KSY','IGD','SXZ','HTY','NAV','AFY','KCO','USQ','MQM','NKT','YEI','BAL','KFS','AOE','TEQ','KZR','OGU','ONQ','MSR','KYS','DNZ','DLM','SIC'];
    @endphp

    {{-- ÖZET CHİPLER --}}
    @php
        $chipIkonlar = [
            'beklemede'       => ['fa-clock',      '#6c757d'],
            'islemde'         => ['fa-spinner',    '#0d6efd'],
            'fiyatlandirildi' => ['fa-tag',        '#ffc107'],
            'depozitoda'      => ['fa-credit-card','#6f42c1'],
            'biletlendi'      => ['fa-ticket-alt', '#198754'],
            'iptal'           => ['fa-ban',        '#dc3545'],
        ];
    @endphp
    <div class="stat-chips pt-2">
        <div class="stat-chip" style="border-color:#495057;color:#495057;" title="Tüm talepler">
            <i class="fas fa-layer-group chip-icon"></i>
            <span class="chip-num">{{ $istatistik['toplam'] }}</span>
            <span>Toplam</span>
        </div>
        @foreach(['beklemede','islemde','fiyatlandirildi','depozitoda','biletlendi','iptal'] as $sk)
        @php
            $meta  = $statusMetaMap[$sk] ?? \App\Models\Request::statusMeta($sk);
            $sayi  = $istatistik[$sk] ?? 0;
            $ikon  = $chipIkonlar[$sk][0];
            $renk  = $meta['bg'];
        @endphp
        <div class="stat-chip {{ $sayi == 0 ? 'stat-chip-zero' : '' }}"
             style="border-color:{{ $renk }};color:{{ $renk }};cursor:pointer;"
             onclick="filterByStatus('{{ $sk }}')"
             title="{{ $meta['label'] }} taleplerini filtrele">
            <i class="fas {{ $ikon }} chip-icon"></i>
            <span class="chip-num">{{ $sayi }}</span>
            <span>{{ $meta['label'] }}</span>
        </div>
        @endforeach
    </div>

    {{-- HARİTA --}}
    <div class="card shadow-sm mb-3">
        <div class="map-filters d-flex align-items-center gap-2 flex-wrap">
            <small class="text-muted me-1">Harita Filtresi:</small>
            @foreach($dashboardStatusOrder as $statusKey)
                @php $meta = $statusMetaMap[$statusKey] ?? \App\Models\Request::statusMeta($statusKey); @endphp
                <span class="badge filter-badge"
                      data-status="{{ $statusKey }}"
                      style="background:{{ $meta['bg'] }};color:{{ $meta['text'] }};"
                      onclick="toggleMapFilter(this)">{{ $meta['label'] }}</span>
            @endforeach
            <button class="btn btn-sm btn-outline-secondary ms-auto" type="button"
                    id="haritaToggleBtn"
                    data-bs-toggle="collapse" data-bs-target="#haritaCollapse"
                    onclick="haritaToggle()">
                <i class="fas fa-map me-1"></i><span id="haritaToggleLabel">Haritayı Göster</span>
            </button>
        </div>
        <div class="collapse" id="haritaCollapse">
            <div id="map"></div>
        </div>
    </div>

    {{-- TALEPLER LİSTESİ --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
            <h6 class="mb-0 fw-bold"><i class="fas fa-list-alt me-1 text-primary"></i> Taleplerim</h6>
            <div class="d-flex align-items-center gap-2">
                <input type="search" id="tabloArama" class="form-control form-control-sm"
                       placeholder="Ara: GTPNR, rota, durum..."
                       style="width:220px; font-size:0.8rem;">
                <button class="btn btn-sm btn-outline-secondary" onclick="filterByStatus(null)" title="Filtreyi temizle">
                    <i class="fas fa-times"></i>
                </button>
                <span class="badge bg-secondary">{{ $istatistik['toplam'] }} talep</span>
            </div>
        </div>
        <div class="table-responsive">
            <table id="talepTablosu" class="table table-sm table-hover mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width:32px;">#</th>
                        <th>GTPNR</th>
                        <th>TÜR</th>
                        <th>YÖN</th>
                        <th style="text-align:center;">PAX</th>
                        <th>GİDİŞ</th>
                        <th>GİDİŞ PARKUR</th>
                        <th>DÖNÜŞ</th>
                        <th>DÖNÜŞ PARKUR</th>
                        <th>HAVAYOLU</th>
                        <th>OPSİYON / ADIM</th>
                        <th>ÖDEME</th>
                        <th>DURUM</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($talepler as $talep)
                    @php
                        $sc   = \App\Models\Request::statusMeta($talep->status);
                        $segs = $talep->segments->sortBy('order');
                        $ilkSeg   = $segs->first();
                        $donusSeg = $segs->count() > 1 ? $segs->last() : null;

                        // Tip: İÇHAT / DIŞHAT
                        $isIchat = $ilkSeg
                            && in_array(strtoupper($ilkSeg->from_iata), $trIata)
                            && in_array(strtoupper($ilkSeg->to_iata), $trIata);

                        // Yön badge
                        $yonBadge = match($talep->trip_type) {
                            'one_way'    => '<span class="badge yon-badge bg-primary"><i class="fas fa-arrow-right me-1"></i>Tek Yön</span>',
                            'round_trip' => '<span class="badge yon-badge bg-success"><i class="fas fa-exchange-alt me-1"></i>G/D</span>',
                            'multi'      => '<span class="badge yon-badge bg-warning text-dark"><i class="fas fa-route me-1"></i>Çok Ayaklı</span>',
                            default      => '<span class="text-muted">—</span>',
                        };

                        // Gidiş saat dilimi
                        $slotLabel = match($ilkSeg?->departure_time_slot ?? '') {
                            'sabah' => '☀ 06-12', 'ogle' => '🌤 12-17',
                            'aksam' => '🌙 17-23', 'esnek' => '⚡ Esnek', default => '',
                        };

                        // Kabul edilen teklif
                        $kabulTeklif  = $talep->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL);
                        $aktifAdim    = $talep->aktif_adim;
                        $aktifPayment = $talep->payments->first();

                        // Havayolu adları
                        $airlineAdlari = [
                            'TK' => 'THY', 'PC' => 'Pegasus', 'XQ' => 'SunExpress',
                            'VF' => 'VFly', 'AJ' => 'AnadoluJet', 'A3' => 'Aegean',
                            'LH' => 'Lufthansa', 'EK' => 'Emirates', 'TF' => 'TF',
                            'QR' => 'Qatar', 'EY' => 'Etihad', 'W6' => 'Wizz Air',
                            'U2' => 'easyJet', 'FR' => 'Ryanair', 'BA' => 'British',
                            'AF' => 'Air France', 'KL' => 'KLM', 'SU' => 'Aeroflot',
                            'OS' => 'Austrian', 'LX' => 'Swiss', 'IB' => 'Iberia',
                            'AZ' => 'ITA', 'SK' => 'SAS', 'MS' => 'EgyptAir',
                            'ET' => 'Ethiopian', 'RJ' => 'Royal Jordanian',
                        ];

                        // Havayolu logo
                        $airlineCode = $kabulTeklif?->airline ?? null;
                        if (!$airlineCode) {
                            // Beklemedeki tekliflerden al
                            $ilkTeklif = $talep->offers->first();
                            $airlineCode = $ilkTeklif?->airline ?? null;
                        }
                        $airlineCode = $airlineCode ? strtoupper(trim($airlineCode)) : null;

                        // Opsiyon deadline hesabı
                        $deadlineDt  = null;
                        $opsSortVal  = 9999999999;

                        // Opsiyon kolonu SADECE teklif option_date'ini gösterir.
                        // Ödeme son tarihi → zaten ÖDEME kolonunda ayrıca görünür.
                        if ($kabulTeklif?->option_date) {
                            // Kabul edilmiş teklifin opsiyon tarihi — tüm aşamalarda göster
                            $deadlineDt = \Carbon\Carbon::parse(
                                $kabulTeklif->option_date .
                                ($kabulTeklif->option_time ? ' '.$kabulTeklif->option_time : ' 23:59:59')
                            );
                        } elseif ($aktifAdim === 'karar_bekleniyor') {
                            // Henüz kabul yok — beklemedeki teklifin opsiyon tarihi
                            $opsOffer = $talep->offers
                                ->where('durum', \App\Models\Offer::DURUM_BEKLEMEDE)
                                ->filter(fn($o) => $o->option_date)
                                ->sortBy('option_date')->first();
                            if ($opsOffer) {
                                $deadlineDt = \Carbon\Carbon::parse(
                                    $opsOffer->option_date .
                                    ($opsOffer->option_time ? ' '.$opsOffer->option_time : ' 23:59:59')
                                );
                            }
                        }

                        if ($deadlineDt) {
                            $opsSortVal = $deadlineDt->isFuture() ? $deadlineDt->timestamp : 9999999998;
                        }

                        // Opsiyon HTML — hem tarih hem kalan süre göster
                        $opsiyonHtml = '';
                        if ($deadlineDt) {
                            $diff = now()->diffInMinutes($deadlineDt, false);
                            $tarihStr = '<div class="text-muted" style="font-size:0.70rem;">'.$deadlineDt->format('d.m.Y H:i').'</div>';
                            if ($diff <= 0) {
                                $opsiyonHtml = '<div class="text-danger fw-bold" style="font-size:0.76rem; white-space:nowrap;">🔴 OPSİYON BİTTİ</div>'.$tarihStr;
                            } elseif ($diff <= 60) {
                                $opsiyonHtml = '<div class="text-danger fw-bold" style="white-space:nowrap;">🚨 '.ceil($diff).'dk kaldı</div>'.$tarihStr;
                            } elseif ($diff <= 360) {
                                $opsiyonHtml = '<div class="text-warning fw-bold" style="white-space:nowrap;">⏰ '.floor($diff/60).'s kaldı</div>'.$tarihStr;
                            } elseif ($diff <= 1440) {
                                $g = floor($diff/60); $m = $diff % 60;
                                $opsiyonHtml = '<div class="text-warning" style="white-space:nowrap;">⏳ '.$g.'s '.floor($m).'dk kaldı</div>'.$tarihStr;
                            } else {
                                $g = floor($diff/1440); $s = floor(($diff%1440)/60);
                                $opsiyonHtml = '<div class="text-info" style="white-space:nowrap;">'.$g.' gün '.$s.' sa kaldı</div>'.$tarihStr;
                            }
                        } else {
                            $opsiyonHtml = match($aktifAdim) {
                                'teklif_bekleniyor'      => '<span class="text-muted" style="font-size:0.75rem;">Teklif bekleniyor</span>',
                                'karar_bekleniyor'       => '<span class="text-secondary" style="font-size:0.75rem;">Karar bekleniyor</span>',
                                'odeme_plani_bekleniyor' => '<span class="text-warning" style="font-size:0.75rem;">⏳ Ödeme planı</span>',
                                'odeme_bekleniyor'       => '<span class="text-warning fw-bold">💳 Ödeme bekleniyor</span>',
                                'odeme_gecikti'          => '<span class="text-danger fw-bold">⚠️ Ödeme gecikti</span>',
                                'odeme_alindi_devam'     => '<span class="text-info" style="font-size:0.75rem;">✅ Kısmi ödeme alındı</span>',
                                'biletleme_bekleniyor'   => '<span class="text-success" style="font-size:0.75rem;">✈️ Biletleme bekleniyor</span>',
                                'tamamlandi'             => '<span class="text-success" style="font-size:0.75rem;">✅ Tamamlandı</span>',
                                default                  => '<span class="text-muted">—</span>',
                            };
                        }
                    @endphp
                    <tr data-status="{{ $talep->status }}"
                        style="border-left: 3px solid {{ $sc['bg'] }};"
                        onclick="window.location='{{ route('acente.requests.show', $talep->gtpnr) }}'">

                        {{-- # --}}
                        <td class="text-muted text-center" style="font-size:0.7rem;">{{ $loop->iteration }}</td>

                        {{-- GTPNR --}}
                        <td>
                            <span class="gtpnr-code">{{ $talep->gtpnr }}</span><br>
                            <small class="text-muted">{{ $talep->created_at->format('d.m.Y') }}</small>
                        </td>

                        {{-- TÜR --}}
                        <td>
                            @if($isIchat)
                                <span class="badge tur-badge" style="background:#198754;">İÇHAT</span>
                            @else
                                <span class="badge tur-badge" style="background:#0d6efd;">DIŞHAT</span>
                            @endif
                        </td>

                        {{-- YÖN --}}
                        <td>{!! $yonBadge !!}</td>

                        {{-- PAX --}}
                        <td class="text-center fw-bold">{{ $talep->pax_total }}</td>

                        {{-- GİDİŞ TARİHİ --}}
                        <td>
                            @if($ilkSeg && $ilkSeg->departure_date)
                                <span class="fw-bold">{{ \Carbon\Carbon::parse($ilkSeg->departure_date)->format('d.m.Y') }}</span>
                                @if($slotLabel)
                                    <br><small class="text-muted">{{ $slotLabel }}</small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- GİDİŞ PARKUR --}}
                        <td>
                            @if($ilkSeg)
                                <span class="parkur">{{ strtoupper($ilkSeg->from_iata) }} <i class="fas fa-arrow-right" style="font-size:0.6rem; opacity:0.6;"></i> {{ strtoupper($ilkSeg->to_iata) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- DÖNÜŞ TARİHİ --}}
                        <td>
                            @if($donusSeg && $donusSeg->departure_date)
                                <span class="fw-bold">{{ \Carbon\Carbon::parse($donusSeg->departure_date)->format('d.m.Y') }}</span>
                                @php
                                    $dSlot = match($donusSeg->departure_time_slot ?? '') {
                                        'sabah' => '☀ 06-12', 'ogle' => '🌤 12-17',
                                        'aksam' => '🌙 17-23', 'esnek' => '⚡ Esnek', default => '',
                                    };
                                @endphp
                                @if($dSlot)
                                    <br><small class="text-muted">{{ $dSlot }}</small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- DÖNÜŞ PARKUR --}}
                        <td>
                            @if($donusSeg)
                                <span class="parkur">{{ strtoupper($donusSeg->from_iata) }} <i class="fas fa-arrow-right" style="font-size:0.6rem; opacity:0.6;"></i> {{ strtoupper($donusSeg->to_iata) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- HAVAYOLU --}}
                        <td class="airline-cell">
                            @if($airlineCode)
                                @php $airlineAd = $airlineAdlari[$airlineCode] ?? $airlineCode; @endphp
                                <img src="/airline-logos/{{ $airlineCode }}.png"
                                     onerror="this.onerror=null;this.style.display='none';"
                                     class="airline-logo mx-auto" alt="{{ $airlineAd }}">
                                <span class="airline-name">{{ $airlineAd }}</span>
                                @if($kabulTeklif?->baggage_kg)
                                    <span class="airline-name">{{ $kabulTeklif->baggage_kg }} KG</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- OPSİYON / ADIM --}}
                        <td data-order="{{ $opsSortVal }}">{!! $opsiyonHtml !!}</td>

                        {{-- BEKLEYEN ÖDEME --}}
                        <td>
                            @if($aktifPayment)
                                <span class="fw-bold {{ $talep->odeme_durumu === 'gecikti' ? 'text-danger' : 'text-warning' }}">
                                    {{ number_format($aktifPayment->amount, 0) }} {{ $aktifPayment->currency }}
                                </span>
                                @if($aktifPayment->due_date)
                                    <br><small class="text-muted">
                                        {{ ($aktifPayment->due_date instanceof \Carbon\Carbon ? $aktifPayment->due_date : \Carbon\Carbon::parse($aktifPayment->due_date))->format('d.m.Y') }}
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- DURUM --}}
                        <td class="text-center">
                            <span class="badge" style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }};font-size:0.7rem;">
                                {{ $sc['label'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">
                            Henüz talep yok.
                            <a href="{{ route('acente.requests.create') }}">İlk talebi oluştur →</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
// ─── Harita collapse localStorage ───────────────────────────────────────
function haritaToggle() {
    setTimeout(() => {
        const open = document.getElementById('haritaCollapse').classList.contains('show');
        localStorage.setItem('haritaAcik', open ? '1' : '0');
        document.getElementById('haritaToggleLabel').textContent = open ? 'Haritayı Gizle' : 'Haritayı Göster';
        if (open && typeof google !== 'undefined' && typeof map !== 'undefined') {
            google.maps.event.trigger(map, 'resize');
        }
    }, 50);
}

// ─── DataTables ──────────────────────────────────────────────────────────
let table;
let aktifFiltre = null;

document.addEventListener('DOMContentLoaded', () => {
    // Harita localStorage
    const acik = localStorage.getItem('haritaAcik') === '1';
    if (acik) {
        document.getElementById('haritaCollapse').classList.add('show');
        document.getElementById('haritaToggleLabel').textContent = 'Haritayı Gizle';
    }

    // DataTables init (jQuery 1.13.x)
    table = $('#talepTablosu').DataTable({
        order: [[10, 'asc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        dom: 'rtip',
        language: {
            lengthMenu:   '_MENU_ talep göster',
            info:         '_START_–_END_ / _TOTAL_ talep',
            infoEmpty:    '0 talep',
            infoFiltered: '(_MAX_ talepten filtrelendi)',
            zeroRecords:  'Eşleşen talep bulunamadı.',
            paginate: { first:'«', last:'»', next:'›', previous:'‹' },
        },
        columnDefs: [
            { orderable: false, targets: [2, 3, 6, 8, 9] },
            { className: 'text-center', targets: [0, 4, 12] },
        ],
        drawCallback: function() {
            let i = 1;
            this.api().rows({ page: 'current' }).nodes().each(function(row) {
                $('td:first-child', row).text(i++);
            });
        }
    });

    // Custom arama kutusu
    $('#tabloArama').on('input', function() {
        table.search(this.value).draw();
    });
});

// Status filtresi (chip'ler + harita badge'leri)
function filterByStatus(status) {
    if (aktifFiltre === status || !status) {
        aktifFiltre = null;
        document.querySelectorAll('.filter-badge').forEach(b => b.style.opacity = '1');
        document.querySelectorAll('.stat-chip').forEach(c => c.style.outline = '');
    } else {
        aktifFiltre = status;
        document.querySelectorAll('.filter-badge').forEach(b => {
            b.style.opacity = b.dataset.status === status ? '1' : '0.35';
        });
    }
    table.draw();
    try { cizRotalar(); } catch(e) {}
}

function toggleMapFilter(el) {
    filterByStatus(el.dataset.status);
}

// DataTables custom row filter
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (!aktifFiltre) return true;
    const row = table ? table.row(dataIndex).node() : null;
    return row ? $(row).attr('data-status') === aktifFiltre : true;
});

// ─── Harita ──────────────────────────────────────────────────────────────
const haritaVerisi = @json($haritaVerisi);
let aktifHaritaFiltre = null;
const statusMeta = @json($statusMetaMap);
const statusRenkler   = Object.fromEntries(Object.entries(statusMeta).map(([s, m]) => [s, m.bg]));
const statusEtiketler = Object.fromEntries(Object.entries(statusMeta).map(([s, m]) => [s, m.label]));

const havalimanları = {
    'IST':{lat:41.2753,lng:28.7519},'SAW':{lat:40.8985,lng:29.3092},
    'ESB':{lat:40.1281,lng:32.9951},'AYT':{lat:36.8987,lng:30.7992},
    'ADB':{lat:38.2924,lng:27.1570},'CDG':{lat:49.0097,lng:2.5479},
    'LHR':{lat:51.4700,lng:-0.4543},'LGW':{lat:51.1537,lng:-0.1821},
    'DXB':{lat:25.2532,lng:55.3657},'JFK':{lat:40.6413,lng:-73.7781},
    'FRA':{lat:50.0379,lng:8.5622},'AMS':{lat:52.3105,lng:4.7683},
    'BCN':{lat:41.2974,lng:2.0833},'FCO':{lat:41.8003,lng:12.2389},
    'MUC':{lat:48.3538,lng:11.7861},'GZT':{lat:36.9473,lng:37.4787},
    'TZX':{lat:40.9950,lng:39.7897},'JNB':{lat:-26.1392,lng:28.2460},
    'AUH':{lat:24.4330,lng:54.6511},'DOH':{lat:25.2732,lng:51.6080},
    'BKK':{lat:13.6811,lng:100.7472},'SIN':{lat:1.3644,lng:103.9915},
};

let map, polylines = [], markers = [];

function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 4,
        center: {lat: 41.0, lng: 29.0},
        mapTypeId: 'roadmap',
        styles: [{featureType:'poi',stylers:[{visibility:'off'}]}]
    });
    cizRotalar();
}

function cizRotalar() {
    polylines.forEach(p => p.setMap(null));
    markers.forEach(m => m.setMap(null));
    polylines = []; markers = [];

    haritaVerisi.forEach(talep => {
        if (aktifFiltre !== null && aktifFiltre !== talep.status) return;
        const renk = statusRenkler[talep.status] || '#6c757d';

        talep.segments.forEach(segment => {
            const from = havalimanları[segment.from];
            const to   = havalimanları[segment.to];
            if (!from || !to) return;

            const line = new google.maps.Polyline({
                path: [from, to], geodesic: true,
                strokeColor: renk, strokeOpacity: 0.8, strokeWeight: 2, map
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `<div style="min-width:150px"><strong>${talep.gtpnr}</strong><br>
                    ${segment.from} → ${segment.to}<br>PAX: ${talep.pax}<br>
                    Durum: <span style="color:${renk}">${statusEtiketler[talep.status]||talep.status}</span></div>`
            });
            line.addListener('click', e => { infoWindow.setPosition(e.latLng); infoWindow.open(map); });
            polylines.push(line);

            [from, to].forEach((pos, i) => {
                const marker = new google.maps.Marker({
                    position: pos, map,
                    title: i === 0 ? segment.from : segment.to,
                    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 7,
                        fillColor: renk, fillOpacity: 1,
                        strokeColor: 'white', strokeWeight: 2 }
                });
                markers.push(marker);
            });
        });
    });
}
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA4CoEHudF9V3Zn4h6udx6Ftr3u6h51EXo&libraries=geometry&callback=initMap" async defer></script>
@include('acente.partials.theme-script')
</body>
</html>

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
    <link href="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3.0/dist/css/tabulator_simple.min.css" rel="stylesheet">
    <style>
        /* ── Harita ── */
        #map { height: 400px; width: 100%; border-radius: 0 0 8px 8px; }
        .map-filters { padding: 10px 15px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #dee2e6; }
        .filter-badge { cursor: pointer; user-select: none; }
        html[data-theme="dark"] .map-filters { background: #16213e !important; border-color: #2a2a4e !important; }
        html[data-theme="light"] .map-filters { background: #fff; }

        /* ── Stat chips ── */
        .stat-chips { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:1.2rem; align-items:center; }
        .stat-chip {
            display:inline-flex; align-items:center; gap:7px;
            padding:6px 14px; border-radius:999px;
            font-size:0.82rem; font-weight:600;
            border:1.5px solid; cursor:pointer;
            transition:transform 0.15s, box-shadow 0.15s, background 0.15s;
            white-space:nowrap;
        }
        .stat-chip:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.15); }
        .stat-chip.aktif { box-shadow:0 0 0 3px rgba(0,0,0,0.15); transform:translateY(-1px); }
        .stat-chip .chip-num { font-size:1.05rem; font-weight:800; line-height:1; }
        .stat-chip .chip-icon { font-size:0.85rem; opacity:0.85; }
        html[data-theme="dark"]  .stat-chip { background: rgba(255,255,255,0.04); }
        html[data-theme="light"] .stat-chip { background: #fff; }
        .stat-chip-zero { opacity:0.45; }

        /* ── Tabulator overrides ── */
        .tabulator {
            border: none !important;
            font-size: 0.79rem;
        }
        .tabulator .tabulator-header {
            border-bottom: 2px solid #e9ecef !important;
        }
        .tabulator .tabulator-header .tabulator-col {
            background: #f8f9fa !important;
            border-right: 1px solid #e9ecef !important;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #495057;
            padding: 8px 6px !important;
        }
        .tabulator .tabulator-header .tabulator-col.tabulator-sortable:hover { background: #e9ecef !important; }
        .tabulator-row { border-bottom: 1px solid #f0f2f5 !important; cursor: pointer; }
        .tabulator-row .tabulator-cell { padding: 5px 6px !important; vertical-align: middle; border-right: none !important; }
        .tabulator-row:hover .tabulator-cell { background: #f0f5ff !important; }
        .tabulator .tabulator-footer { background: #f8f9fa !important; border-top: 1px solid #e9ecef !important; font-size: 0.78rem; }
        .tabulator-page { border: 1px solid #dee2e6 !important; color: #495057 !important; border-radius: 4px !important; margin: 0 2px !important; font-size: 0.78rem !important; }
        .tabulator-page.active { background: #0d6efd !important; color: #fff !important; border-color: #0d6efd !important; }
        .tabulator-page:hover:not(.active) { background: #e9ecef !important; }
        .tabulator-page-size { font-size: 0.78rem !important; border: 1px solid #dee2e6 !important; border-radius: 4px !important; padding: 2px 4px !important; }

        /* ── Dark mode: Tabulator ── */
        html[data-theme="dark"] .tabulator { background: #1a1a2e !important; color: #d0d0d0 !important; }
        html[data-theme="dark"] .tabulator .tabulator-header { background: #16213e !important; border-color: #2a2a4e !important; }
        html[data-theme="dark"] .tabulator .tabulator-header .tabulator-col { background: #16213e !important; border-color: #2a2a4e !important; color: #adb5bd !important; }
        html[data-theme="dark"] .tabulator-row { border-color: #2a2a4e !important; }
        html[data-theme="dark"] .tabulator-row .tabulator-cell { background: transparent !important; }
        html[data-theme="dark"] .tabulator-row:hover .tabulator-cell { background: #2a2a4e !important; }
        html[data-theme="dark"] .tabulator-row.tabulator-row-even .tabulator-cell { background: rgba(255,255,255,0.02) !important; }
        html[data-theme="dark"] .tabulator .tabulator-footer { background: #16213e !important; border-color: #2a2a4e !important; color: #adb5bd !important; }
        html[data-theme="dark"] .tabulator-page { background: #1a1a2e !important; color: #d0d0d0 !important; border-color: #2a2a4e !important; }
        html[data-theme="dark"] .tabulator-page.active { background: #0d6efd !important; color: #fff !important; }
        html[data-theme="dark"] .tabulator-page:hover:not(.active) { background: #2a2a4e !important; }
        html[data-theme="dark"] .tabulator-page-size { background: #1a1a2e !important; color: #d0d0d0 !important; border-color: #2a2a4e !important; }
    </style>
</head>
<body>

<x-navbar-acente active="dashboard" />

@php
    $statusMetaMap       = \App\Models\Request::statusMetaMap();
    $dashboardStatusOrder = ['beklemede', 'islemde', 'fiyatlandirildi', 'iptal', 'biletlendi', 'depozitoda'];
    $trIata = ['IST','SAW','ESB','AYT','ADB','GZT','TZX','DIY','DYB','ASR','VAN','EZS','BJV','MLX','SZF',
               'KYA','ERZ','ERC','KSY','IGD','SXZ','HTY','NAV','AFY','KCO','USQ','MQM','NKT','YEI','BAL',
               'KFS','AOE','TEQ','KZR','OGU','ONQ','MSR','KYS','DNZ','DLM','SIC'];

    $airlineAdlari = [
        'TK'=>'THY','PC'=>'Pegasus','XQ'=>'SunExpress','VF'=>'VFly','AJ'=>'AnadoluJet',
        'A3'=>'Aegean','LH'=>'Lufthansa','EK'=>'Emirates','QR'=>'Qatar','EY'=>'Etihad',
        'W6'=>'Wizz Air','U2'=>'easyJet','FR'=>'Ryanair','BA'=>'British','AF'=>'Air France',
        'KL'=>'KLM','SU'=>'Aeroflot','OS'=>'Austrian','LX'=>'Swiss','IB'=>'Iberia',
        'UX'=>'Air Europa','AZ'=>'ITA','SK'=>'SAS','MS'=>'EgyptAir','ET'=>'Ethiopian','RJ'=>'RJ',
    ];
    $slotMap = ['sabah'=>'06-12','ogle'=>'12-17','aksam'=>'17-23','esnek'=>'Esnek'];

    // ─── Tabulator için JSON veri ───────────────────────────────────────────
    $taleplerJson = [];
    foreach ($talepler as $talep) {
        $sc   = \App\Models\Request::statusMeta($talep->status);
        $segs = $talep->segments->sortBy('order');
        $ilkSeg   = $segs->first();
        $donusSeg = $segs->count() > 1 ? $segs->last() : null;

        $isIchat = $ilkSeg
            && in_array(strtoupper($ilkSeg->from_iata), $trIata)
            && in_array(strtoupper($ilkSeg->to_iata), $trIata);

        $segSayisi = $segs->count();
        if ($segSayisi > 2)                        { $tripType = 'multi'; }
        elseif ($segSayisi === 2)                  { $tripType = 'round_trip'; }
        elseif ($talep->trip_type === 'multi')     { $tripType = 'multi'; }
        else                                        { $tripType = 'one_way'; }

        $kabulTeklif  = $talep->offers->firstWhere('durum', \App\Models\Offer::DURUM_KABUL);
        $aktifAdim    = $talep->aktif_adim ?? 'teklif_bekleniyor';
        $aktifPayment = $talep->payments->first();

        $airlineCode = $kabulTeklif?->airline ?? $talep->offers->first()?->airline ?? null;
        $airlineCode = $airlineCode ? strtoupper(trim($airlineCode)) : null;
        $airlineName = $airlineCode ? ($airlineAdlari[$airlineCode] ?? $airlineCode) : null;

        $bagajKg = $kabulTeklif?->baggage_kg ?? null;

        // Opsiyon deadline
        $deadlineDt = null;
        $opsSortVal = 9999999999;
        if ($kabulTeklif?->option_date) {
            $deadlineDt = \Carbon\Carbon::parse(
                $kabulTeklif->option_date .
                ($kabulTeklif->option_time ? ' '.$kabulTeklif->option_time : ' 23:59:59')
            );
        } elseif ($aktifAdim === 'karar_bekleniyor') {
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

        // Ödeme
        $odemeDueDate = null;
        if ($aktifPayment?->due_date) {
            $dd = $aktifPayment->due_date;
            $odemeDueDate = ($dd instanceof \Carbon\Carbon ? $dd : \Carbon\Carbon::parse($dd))->format('d.m.Y');
        }

        $taleplerJson[] = [
            'id'            => $talep->id,
            'gtpnr'         => $talep->gtpnr,
            'url'           => route('acente.requests.show', $talep->gtpnr),
            'createdAt'     => $talep->created_at->format('d.m.Y'),
            'status'        => $talep->status,
            'statusLabel'   => $sc['label'],
            'statusBg'      => $sc['bg'],
            'airlineCode'   => $airlineCode,
            'airlineName'   => $airlineName,
            'isIchat'       => $isIchat,
            'tripType'      => $tripType,
            'pax'           => $talep->pax_total,
            'bagajKg'       => $bagajKg,
            'gidisTarih'    => $ilkSeg?->departure_date ? \Carbon\Carbon::parse($ilkSeg->departure_date)->format('d.m.Y') : null,
            'gidisSlot'     => $slotMap[$ilkSeg?->departure_time_slot ?? ''] ?? null,
            'gidisParkur'   => $ilkSeg ? strtoupper($ilkSeg->from_iata).' → '.strtoupper($ilkSeg->to_iata) : null,
            'donusTarih'    => $donusSeg?->departure_date ? \Carbon\Carbon::parse($donusSeg->departure_date)->format('d.m.Y') : null,
            'donusSlot'     => $slotMap[$donusSeg?->departure_time_slot ?? ''] ?? null,
            'donusParkur'   => $donusSeg ? strtoupper($donusSeg->from_iata).' → '.strtoupper($donusSeg->to_iata) : null,
            'opsSortVal'    => $opsSortVal,
            'opsDeadlineIso'=> $deadlineDt?->toIso8601String(),
            'aktifAdim'     => $aktifAdim,
            'odemeAmount'   => $aktifPayment?->amount,
            'odemeCurrency' => $aktifPayment?->currency ?? 'TRY',
            'odemeDueDate'  => $odemeDueDate,
            'odemeGecikti'  => $talep->odeme_durumu === 'gecikti',
            'teklifTutar'   => $kabulTeklif?->total_price,
            'teklifCurrency'=> $kabulTeklif?->currency ?? 'TRY',
        ];
    }
@endphp

<div class="container-fluid px-4">

    {{-- ── ÖZET CHİPLER ── --}}
    @php
        $chipIkonlar = [
            'beklemede'       => 'fa-clock',
            'islemde'         => 'fa-spinner',
            'fiyatlandirildi' => 'fa-tag',
            'depozitoda'      => 'fa-credit-card',
            'biletlendi'      => 'fa-ticket-alt',
            'iptal'           => 'fa-ban',
        ];
    @endphp
    <div class="stat-chips pt-2">
        <div class="stat-chip" style="border-color:#495057;color:#495057;" title="Tüm talepler"
             onclick="filterByStatus(null)">
            <i class="fas fa-layer-group chip-icon"></i>
            <span class="chip-num">{{ $istatistik['toplam'] }}</span>
            <span>Toplam</span>
        </div>
        @foreach(['beklemede','islemde','fiyatlandirildi','depozitoda','biletlendi','iptal'] as $sk)
        @php
            $meta = $statusMetaMap[$sk] ?? \App\Models\Request::statusMeta($sk);
            $sayi = $istatistik[$sk] ?? 0;
            $renk = $meta['bg'];
        @endphp
        <div class="stat-chip {{ $sayi == 0 ? 'stat-chip-zero' : '' }}"
             id="chip-{{ $sk }}"
             style="border-color:{{ $renk }};color:{{ $renk }};"
             onclick="filterByStatus('{{ $sk }}')"
             title="{{ $meta['label'] }} taleplerini filtrele">
            <i class="fas {{ $chipIkonlar[$sk] ?? 'fa-circle' }} chip-icon"></i>
            <span class="chip-num">{{ $sayi }}</span>
            <span>{{ $meta['label'] }}</span>
        </div>
        @endforeach
    </div>

    {{-- ── HARİTA ── --}}
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

    {{-- ── TALEPLER LİSTESİ ── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2 flex-wrap gap-2">
            <h6 class="mb-0 fw-bold">
                <i class="fas fa-list-alt me-1 text-primary"></i> Taleplerim
                <span class="badge bg-secondary ms-1 fw-normal" id="talepSayac">{{ $istatistik['toplam'] }}</span>
            </h6>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="width:220px;">
                    <span class="input-group-text bg-transparent border-end-0">
                        <i class="fas fa-search text-muted" style="font-size:0.75rem;"></i>
                    </span>
                    <input type="search" id="tabloArama" class="form-control border-start-0"
                           placeholder="GTPNR, rota, durum..." style="font-size:0.8rem;">
                </div>
                <button class="btn btn-sm btn-outline-secondary" onclick="filterByStatus(null)" title="Filtreyi temizle">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div id="talepTablo"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tabulator-tables@6.3.0/dist/js/tabulator.min.js"></script>

<script>
// ─── Veri ────────────────────────────────────────────────────────────────────
const taleplerData  = @json($taleplerJson);
const haritaVerisi  = @json($haritaVerisi);
const statusMeta    = @json($statusMetaMap);
const statusRenkler   = Object.fromEntries(Object.entries(statusMeta).map(([s,m]) => [s, m.bg]));
const statusEtiketler = Object.fromEntries(Object.entries(statusMeta).map(([s,m]) => [s, m.label]));

// ─── Yardımcılar ─────────────────────────────────────────────────────────────
function softPill(label, bgColor) {
    const r = parseInt(bgColor.slice(1,3), 16);
    const g = parseInt(bgColor.slice(3,5), 16);
    const b = parseInt(bgColor.slice(5,7), 16);
    // Sarı (#ffc107) için text rengini koyulaştır
    const textColor = (r > 200 && g > 170 && b < 50) ? '#7a5c00' : bgColor;
    return `<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px 3px 8px;border-radius:20px;background:rgba(${r},${g},${b},0.12);color:${textColor};font-size:0.72rem;font-weight:600;white-space:nowrap;">
        <span style="width:6px;height:6px;border-radius:50%;background:${bgColor};display:inline-block;flex-shrink:0;"></span>
        ${label}
    </span>`;
}

function formatCountdown(isoStr) {
    if (!isoStr) return null;
    const dt   = new Date(isoStr);
    const diff = Math.floor((dt - new Date()) / 60000);
    const gun  = dt.toLocaleDateString('tr-TR', {day:'2-digit',month:'2-digit',year:'numeric'});
    const saat = dt.toLocaleTimeString('tr-TR', {hour:'2-digit',minute:'2-digit'});
    const tarih = `<div style="font-size:0.67rem;color:#888;margin-top:1px;">${gun} ${saat}</div>`;
    if (diff <= 0)   return `<div style="color:#dc3545;font-weight:700;font-size:0.75rem;white-space:nowrap;"><i class="fas fa-circle-xmark me-1"></i>Opsiyon Bitti</div>${tarih}`;
    if (diff <= 60)  return `<div style="color:#dc3545;font-weight:700;font-size:0.75rem;white-space:nowrap;"><i class="fas fa-triangle-exclamation me-1"></i>${Math.ceil(diff)}dk kaldı</div>${tarih}`;
    if (diff <= 360) return `<div style="color:#fd7e14;font-weight:700;font-size:0.75rem;white-space:nowrap;"><i class="fas fa-clock me-1"></i>${Math.floor(diff/60)}s kaldı</div>${tarih}`;
    if (diff <= 1440){ const h=Math.floor(diff/60),m=diff%60; return `<div style="color:#ffc107;font-size:0.75rem;white-space:nowrap;"><i class="fas fa-hourglass-half me-1"></i>${h}s ${m}dk kaldı</div>${tarih}`; }
    const g = Math.floor(diff/1440), s = Math.floor((diff%1440)/60);
    return `<div style="color:#0dcaf0;font-size:0.75rem;white-space:nowrap;"><i class="fas fa-calendar me-1"></i>${g}g ${s}sa kaldı</div>${tarih}`;
}

const adimHtml = {
    'teklif_bekleniyor':      '<span style="color:#6c757d;font-size:0.75rem;"><i class="fas fa-hourglass fa-xs me-1"></i>Teklif bekleniyor</span>',
    'karar_bekleniyor':       '<span style="color:#6c757d;font-size:0.75rem;"><i class="fas fa-scale-balanced fa-xs me-1"></i>Karar bekleniyor</span>',
    'odeme_plani_bekleniyor': '<span style="color:#fd7e14;font-size:0.75rem;"><i class="fas fa-calendar-plus fa-xs me-1"></i>Ödeme planı bekleniyor</span>',
    'odeme_bekleniyor':       '<span style="color:#ffc107;font-weight:600;font-size:0.75rem;"><i class="fas fa-credit-card fa-xs me-1"></i>Ödeme bekleniyor</span>',
    'odeme_gecikti':          '<span style="color:#dc3545;font-weight:700;font-size:0.75rem;"><i class="fas fa-circle-exclamation fa-xs me-1"></i>Ödeme gecikti</span>',
    'odeme_alindi_devam':     '<span style="color:#0dcaf0;font-size:0.75rem;"><i class="fas fa-check fa-xs me-1"></i>Kısmi alındı</span>',
    'biletleme_bekleniyor':   '<span style="color:#198754;font-size:0.75rem;"><i class="fas fa-ticket fa-xs me-1"></i>Biletleme bekleniyor</span>',
    'tamamlandi':             '<span style="color:#198754;font-size:0.75rem;"><i class="fas fa-circle-check fa-xs me-1"></i>Tamamlandı</span>',
};

const slotIkon = {'06-12':'☀','12-17':'⛅','17-23':'🌙','Esnek':'⚡'};

// ─── Tabulator kolonları ──────────────────────────────────────────────────────
const kolonlar = [
    {
        formatter: 'rownum',
        width: 36, hozAlign: 'center', headerSort: false,
        headerHozAlign: 'center',
        title: '#',
    },
    {
        title: '<i class="fas fa-qrcode me-1"></i>GTPNR',
        field: 'gtpnr',
        minWidth: 100,
        formatter(cell) {
            const d = cell.getData();
            return `<span style="color:#e94560;font-family:monospace;font-weight:700;font-size:0.85rem;">${d.gtpnr}</span><br><small style="color:#aaa;font-size:0.68rem;">${d.createdAt}</small>`;
        },
        sorter: 'string',
    },
    {
        title: '<i class="fas fa-plane me-1"></i>HAVAYOLU',
        field: 'airlineCode',
        minWidth: 82, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getData();
            if (!d.airlineCode) return '<span style="color:#bbb;">—</span>';
            return `<div style="text-align:center;"><img src="/airline-logos/${d.airlineCode}.png" onerror="this.style.display='none'" style="width:64px;max-height:28px;object-fit:contain;display:block;margin:0 auto;"><div style="font-size:0.62rem;color:#888;margin-top:2px;">${d.airlineName || d.airlineCode}</div></div>`;
        },
    },
    {
        title: '<i class="fas fa-globe me-1"></i>TÜR',
        field: 'isIchat',
        width: 80, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            return cell.getValue()
                ? `<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;background:rgba(25,135,84,0.1);color:#198754;font-size:0.68rem;font-weight:600;">🇹🇷 İÇHAT</span>`
                : `<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;background:rgba(13,110,253,0.1);color:#0d6efd;font-size:0.68rem;font-weight:600;">🌍 DIŞHAT</span>`;
        },
    },
    {
        title: '<i class="fas fa-route me-1"></i>YÖN',
        field: 'tripType',
        width: 82, hozAlign: 'center',
        formatter(cell) {
            const t = cell.getValue();
            if (t === 'round_trip') return `<div style="text-align:center;"><img src="/airline-logos/roundtrip.png" style="height:20px;width:auto;" alt="⇄"><div style="font-size:0.6rem;color:#198754;font-weight:700;margin-top:1px;">GİDİŞ-DÖNÜŞ</div></div>`;
            if (t === 'multi')      return `<div style="text-align:center;font-size:1.2rem;color:#fd7e14;">⤳<div style="font-size:0.6rem;font-weight:700;">ÇOK AYAKLI</div></div>`;
            return `<div style="text-align:center;"><img src="/airline-logos/oneway.png" style="height:20px;width:auto;" alt="→"><div style="font-size:0.6rem;color:#0d6efd;font-weight:700;margin-top:1px;">TEK YÖN</div></div>`;
        },
    },
    {
        title: '<i class="fas fa-users me-1"></i>PAX',
        field: 'pax',
        width: 52, hozAlign: 'center',
        formatter(cell) { return `<span style="font-weight:700;font-size:0.9rem;">${cell.getValue()}</span>`; },
        sorter: 'number',
    },
    {
        title: '<i class="fas fa-suitcase-rolling me-1"></i>BAGAJ',
        field: 'bagajKg',
        width: 68, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const kg = cell.getValue();
            if (kg !== null && kg !== undefined && kg !== '') return `<span style="font-weight:600;font-size:0.8rem;"><i class="fas fa-suitcase-rolling" style="color:#6c757d;"></i> ${kg}kg</span>`;
            return `<span style="color:#bbb;"><i class="fas fa-suitcase-rolling"></i></span>`;
        },
    },
    {
        title: '<i class="fas fa-calendar-day me-1"></i>GİDİŞ',
        field: 'gidisTarih',
        minWidth: 88,
        formatter(cell) {
            const d = cell.getData();
            if (!d.gidisTarih) return '<span style="color:#bbb;">—</span>';
            const ikon = slotIkon[d.gidisSlot] || '';
            return `<span style="font-weight:700;font-size:0.82rem;">${d.gidisTarih}</span>${d.gidisSlot ? `<br><small style="color:#888;font-size:0.67rem;">${ikon} ${d.gidisSlot}</small>` : ''}`;
        },
        sorter(a, b) {
            if (!a) return 1; if (!b) return -1;
            return a.split('.').reverse().join('') < b.split('.').reverse().join('') ? -1 : 1;
        },
    },
    {
        title: '<i class="fas fa-plane-departure me-1"></i>GİDİŞ PARKUR',
        field: 'gidisParkur',
        minWidth: 88, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v ? `<span style="font-weight:700;font-size:0.82rem;letter-spacing:0.5px;">${v}</span>` : '<span style="color:#bbb;">—</span>';
        },
    },
    {
        title: '<i class="fas fa-calendar-day me-1"></i>DÖNÜŞ',
        field: 'donusTarih',
        minWidth: 88,
        formatter(cell) {
            const d = cell.getData();
            if (!d.donusTarih) return '<span style="color:#bbb;">—</span>';
            const ikon = slotIkon[d.donusSlot] || '';
            return `<span style="font-weight:700;font-size:0.82rem;">${d.donusTarih}</span>${d.donusSlot ? `<br><small style="color:#888;font-size:0.67rem;">${ikon} ${d.donusSlot}</small>` : ''}`;
        },
        sorter(a, b) {
            if (!a) return 1; if (!b) return -1;
            return a.split('.').reverse().join('') < b.split('.').reverse().join('') ? -1 : 1;
        },
    },
    {
        title: '<i class="fas fa-plane-arrival me-1"></i>DÖNÜŞ PARKUR',
        field: 'donusParkur',
        minWidth: 88, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v ? `<span style="font-weight:700;font-size:0.82rem;letter-spacing:0.5px;">${v}</span>` : '<span style="color:#bbb;">—</span>';
        },
    },
    {
        title: '<i class="fas fa-clock me-1"></i>OPSİYON',
        field: 'opsSortVal',
        minWidth: 120,
        formatter(cell) {
            const d = cell.getData();
            const countdown = d.opsDeadlineIso ? formatCountdown(d.opsDeadlineIso) : null;
            return countdown || adimHtml[d.aktifAdim] || '<span style="color:#bbb;">—</span>';
        },
        sorter: 'number',
    },
    {
        title: '<i class="fas fa-money-bill-wave me-1"></i>KALAN ÖDEME',
        field: 'odemeAmount',
        minWidth: 110,
        formatter(cell) {
            const d = cell.getData();
            if (d.odemeAmount) {
                const renk = d.odemeGecikti ? '#dc3545' : '#ffc107';
                const fiyat = Number(d.odemeAmount).toLocaleString('tr-TR') + ' ' + (d.odemeCurrency || 'TRY');
                return `<span style="font-weight:600;color:${renk};"><i class="fas fa-credit-card me-1"></i>${fiyat}</span>${d.odemeDueDate ? `<br><small style="color:#888;font-size:0.67rem;">Son: ${d.odemeDueDate}</small>` : ''}`;
            }
            if (d.teklifTutar) {
                return `<span style="color:#aaa;font-size:0.75rem;"><i class="fas fa-file-invoice me-1"></i>${Number(d.teklifTutar).toLocaleString('tr-TR')} ${d.teklifCurrency || 'TRY'}</span><br><span style="font-size:0.6rem;color:#bbb;">teklif toplam</span>`;
            }
            return '<span style="color:#bbb;">—</span>';
        },
        sorter: 'number',
    },
    {
        title: '<i class="fas fa-tag me-1"></i>DURUM',
        field: 'status',
        width: 110, hozAlign: 'center',
        formatter(cell) {
            const d = cell.getData();
            return softPill(d.statusLabel, d.statusBg);
        },
    },
];

// ─── Tabulator init ───────────────────────────────────────────────────────────
let tablo;
let searchVal  = '';
let aktifFiltre = null;

document.addEventListener('DOMContentLoaded', () => {
    // Harita localStorage
    if (localStorage.getItem('haritaAcik') === '1') {
        document.getElementById('haritaCollapse').classList.add('show');
        document.getElementById('haritaToggleLabel').textContent = 'Haritayı Gizle';
    }

    tablo = new Tabulator('#talepTablo', {
        data: taleplerData,
        layout: 'fitDataFill',
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100],
        initialSort: [{ column: 'opsSortVal', dir: 'asc' }],
        columns: kolonlar,
        rowFormatter(row) {
            const d = row.getData();
            const el = row.getElement();
            el.setAttribute('data-status', d.status);
            el.style.borderLeft = `3px solid ${d.statusBg}`;
        },
        rowClick(e, row) {
            window.location = row.getData().url;
        },
        dataFiltered(filters, rows) {
            document.getElementById('talepSayac').textContent = rows.length;
        },
    });

    // Varsayılan filtre: iptal gizle
    applyFilters();

    // Arama
    document.getElementById('tabloArama').addEventListener('input', function() {
        searchVal = this.value;
        applyFilters();
    });
});

function applyFilters() {
    if (!tablo) return;
    tablo.setFilter(function(data) {
        const statusOk = aktifFiltre ? data.status === aktifFiltre : data.status !== 'iptal';
        if (!statusOk) return false;
        if (!searchVal) return true;
        const q = searchVal.toLowerCase();
        return (data.gtpnr      || '').toLowerCase().includes(q) ||
               (data.gidisParkur || '').toLowerCase().includes(q) ||
               (data.donusParkur || '').toLowerCase().includes(q) ||
               (data.airlineName  || '').toLowerCase().includes(q) ||
               (data.statusLabel  || '').toLowerCase().includes(q);
    });
}

function filterByStatus(status) {
    aktifFiltre = (aktifFiltre === status || !status) ? null : status;
    // Chip aktif görsel
    document.querySelectorAll('.stat-chip').forEach(c => c.classList.remove('aktif'));
    if (aktifFiltre) {
        const el = document.getElementById('chip-' + aktifFiltre);
        if (el) el.classList.add('aktif');
    }
    applyFilters();
    try { cizRotalar(); } catch(e) {}
}

function toggleMapFilter(el) { filterByStatus(el.dataset.status); }

// ─── Harita collapse ──────────────────────────────────────────────────────────
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

// ─── Harita ──────────────────────────────────────────────────────────────────
let aktifFiltrePrev = null;

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
    'ASR':{lat:38.7703,lng:35.4955},
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

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
    <style>
        #map { height: 400px; width: 100%; border-radius: 0 0 8px 8px; }
        .stat-card { border-radius: 12px; border: none; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-number { font-size: 2rem; font-weight: 700; }
        .map-filters { padding: 10px 15px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #dee2e6; }
        .filter-badge { cursor: pointer; user-select: none; }
        html[data-theme="dark"] .map-filters { background: #16213e !important; border-color: #2a2a4e !important; }
        html[data-theme="light"] .map-filters { background: #fff; }
        /* Tablo hover - tıklanabilir satır */
        html[data-theme="dark"]  #talepTablosu tr:hover { background:#2a2a4e !important; }
        html[data-theme="light"] #talepTablosu tr:hover { background:#f0f5ff !important; }
    </style>
</head>
<body>

<x-navbar-acente active="dashboard" />

<div class="container-fluid px-4">
    @php
        $statusMetaMap = \App\Models\Request::statusMetaMap();
        $dashboardStatusOrder = ['beklemede', 'islemde', 'fiyatlandirildi', 'iptal', 'biletlendi', 'depozitoda'];
    @endphp

    {{-- ÖZET KARTLAR --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card stat-card text-center p-3 shadow-sm">
                <div class="stat-number text-dark">{{ $istatistik['toplam'] }}</div>
                <div class="text-muted small">Toplam</div>
            </div>
        </div>
        @foreach($dashboardStatusOrder as $statusKey)
            @php($meta = $statusMetaMap[$statusKey] ?? \App\Models\Request::statusMeta($statusKey))
            <div class="col-6 col-md-2">
                <div class="card stat-card text-center p-3 shadow-sm" style="border-top: 3px solid {{ $meta['bg'] }}">
                    <div class="stat-number" style="color:{{ $meta['bg'] }}">{{ $istatistik[$statusKey] ?? 0 }}</div>
                    <div class="text-muted small">{{ $meta['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- HARİTA --}}
    <div class="card shadow-sm mb-4">
        <div class="map-filters d-flex align-items-center gap-2 flex-wrap">
            <small class="text-muted me-2">Filtre:</small>
            @foreach($dashboardStatusOrder as $statusKey)
                @php($meta = $statusMetaMap[$statusKey] ?? \App\Models\Request::statusMeta($statusKey))
                <span class="badge filter-badge"
                      data-status="{{ $statusKey }}"
                      style="background:{{ $meta['bg'] }};color:{{ $meta['text'] }};"
                      onclick="toggleFilter(this)">{{ $meta['label'] }}</span>
            @endforeach
        </div>
        <div id="map"></div>
    </div>

    {{-- TALEPLER LİSTESİ --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0 fw-bold">📋 Taleplerim</h6>
            <span class="badge bg-secondary">{{ $istatistik['toplam'] }} talep</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:0.82rem;">
                <thead style="background:#0d6efd; font-size:0.72rem; text-transform:uppercase; letter-spacing:1px;">
                    <tr>
                        <th style="color:#fff;">GTPNR</th>
                        <th style="color:#fff;">Rota</th>
                        <th style="color:#fff;">PAX</th>
                        <th style="color:#fff;">Gidiş</th>
                        <th style="color:#fff;">Opsiyon</th>
                        <th style="color:#fff;" class="text-center">Durum</th>
                    </tr>
                </thead>
                <tbody id="talepTablosu">
                    @forelse($talepler as $talep)
                    @php
                        $sc = \App\Models\Request::statusMeta($talep->status);
                        $segs = $talep->segments->sortBy('order');
                        $ilkSeg = $segs->first();

                        // Opsiyon: kabul edilmiş veya ilk teklif
                        $aktifTeklif = $talep->offers->firstWhere('is_accepted', true) ?? $talep->offers->first();
                        $opsiyonHtml = '';
                        if ($aktifTeklif?->option_date) {
                            try {
                                $rawSaat = trim($aktifTeklif->option_time ?? '');
                                if (preg_match('/^(\d{1,2}):(\d{2})/', $rawSaat, $m)) {
                                    $rawSaat = sprintf('%02d:%02d', $m[1], $m[2]);
                                } elseif (preg_match('/^\d{1,2}$/', $rawSaat)) {
                                    $rawSaat = sprintf('%02d:00', (int)$rawSaat);
                                } else {
                                    $rawSaat = '23:59';
                                }
                                $optDt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $aktifTeklif->option_date . ' ' . $rawSaat);
                                if ($optDt->isFuture()) {
                                    $diff = now()->diff($optDt);
                                    $parts = [];
                                    if ($diff->d) $parts[] = $diff->d.'g';
                                    if ($diff->h) $parts[] = $diff->h.'s';
                                    if (!$diff->d) $parts[] = $diff->i.'d';
                                    $opsiyonHtml = '<span class="text-warning fw-bold" id="op-'.$talep->id.'" data-ts="'.$optDt->timestamp.'">'.(implode(' ',$parts) ?: '<1s').' kaldı</span>';
                                } else {
                                    $opsiyonHtml = '<span class="text-danger fw-bold">OPSİYON BİTTİ</span>';
                                }
                            } catch(\Throwable $e) {}
                        } elseif ($talep->status === 'beklemede') {
                            $opsiyonHtml = '<span class="text-muted">—</span>';
                        } elseif ($talep->status === 'biletlendi') {
                            $opsiyonHtml = '<span class="text-success">BİLETLENDİ</span>';
                        }
                    @endphp
                    <tr data-status="{{ $talep->status }}"
                        style="cursor:pointer; border-left: 3px solid {{ $sc['bg'] }};"
                        onclick="window.location='{{ route('acente.requests.show', $talep->gtpnr) }}'">
                        <td>
                            <strong class="font-monospace" style="color:#e94560;">{{ $talep->gtpnr }}</strong><br>
                            <small class="text-muted">{{ $talep->created_at->format('d.m.Y') }}</small>
                        </td>
                        <td>
                            @foreach($segs as $seg)
                                <span class="fw-bold">{{ $seg->from_iata }}–{{ $seg->to_iata }}</span><br>
                            @endforeach
                        </td>
                        <td><span class="fw-bold">{{ $talep->pax_total }}</span></td>
                        <td>
                            @if($ilkSeg)
                                <span>{{ \Carbon\Carbon::parse($ilkSeg->departure_date)->format('d.m.Y') }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{!! $opsiyonHtml !!}</td>
                        <td class="text-center">
                            <span class="badge" style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }};">{{ $sc['label'] }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
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

<script>
const haritaVerisi = @json($haritaVerisi);
let aktifFiltre = null; // null = hepsi göster
const statusMeta = @json($statusMetaMap);
const statusRenkler = Object.fromEntries(Object.entries(statusMeta).map(([status, meta]) => [status, meta.bg]));
const statusEtiketler = Object.fromEntries(Object.entries(statusMeta).map(([status, meta]) => [status, meta.label]));
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
            const to = havalimanları[segment.to];
            if (!from || !to) return;

            const line = new google.maps.Polyline({
                path: [from, to],
                geodesic: true,
                strokeColor: renk,
                strokeOpacity: 0.8,
                strokeWeight: 2,
                map: map
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `<div style="min-width:150px">
                    <strong>${talep.gtpnr}</strong><br>
                    ${segment.from} → ${segment.to}<br>
                    PAX: ${talep.pax}<br>
                    Durum: <span style="color:${renk}">${statusEtiketler[talep.status] || talep.status}</span>
                </div>`
            });

            line.addListener('click', (e) => {
                infoWindow.setPosition(e.latLng);
                infoWindow.open(map);
            });

            polylines.push(line);

            [from, to].forEach((pos, i) => {
                const marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: i === 0 ? segment.from : segment.to,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 7,
                        fillColor: renk,
                        fillOpacity: 1,
                        strokeColor: 'white',
                        strokeWeight: 2
                    }
                });
                markers.push(marker);
            });
        });
    });
}

function toggleFilter(el) {
    const status = el.dataset.status;
    if (aktifFiltre === status) {
        // Aynı badge'e tekrar tıklandı → filtreyi kaldır
        aktifFiltre = null;
        document.querySelectorAll('.filter-badge').forEach(b => b.style.opacity = '1');
    } else {
        // Yeni badge seçildi → sadece onu göster
        aktifFiltre = status;
        document.querySelectorAll('.filter-badge').forEach(b => {
            b.style.opacity = b.dataset.status === status ? '1' : '0.35';
        });
    }
    filtreTabloyu();
    try { cizRotalar(); } catch(e) {}
}

function filtreTabloyu() {
    document.querySelectorAll('#talepTablosu tr').forEach(row => {
        const s = row.getAttribute('data-status');
        if (s !== null) {
            row.style.display = (aktifFiltre === null || aktifFiltre === s) ? '' : 'none';
        }
    });
}

// ── Opsiyon geri sayım ──
document.querySelectorAll('[id^="op-"][data-ts]').forEach(el => {
    const hedef = parseInt(el.dataset.ts) * 1000;
    function guncelle() {
        const kalan = hedef - Date.now();
        if (kalan <= 0) { el.textContent = 'OPSİYON BİTTİ'; el.className = 'text-danger fw-bold'; return; }
        const g = Math.floor(kalan / 86400000);
        const s = Math.floor((kalan % 86400000) / 3600000);
        const d = Math.floor((kalan % 3600000) / 60000);
        el.textContent = g > 0 ? g+'g '+s+'s' : (s > 0 ? s+'s '+d+'d' : d+'d kaldı');
        setTimeout(guncelle, 30000);
    }
    guncelle();
});
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA4CoEHudF9V3Zn4h6udx6Ftr3u6h51EXo&libraries=geometry&callback=initMap" async defer></script>
@include('acente.partials.theme-script')
</body>
</html>

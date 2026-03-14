<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acente Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .navbar { background: #1a1a2e !important; }
        .navbar-brand { color: #e94560 !important; font-weight: 700; }
        #map { height: 450px; width: 100%; border-radius: 0 0 12px 12px; }
        .stat-card { border-radius: 12px; border: none; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-number { font-size: 2rem; font-weight: 700; }
        .map-filters { background: white; padding: 10px 15px; border-radius: 12px 12px 0 0; border-bottom: 1px solid #dee2e6; }
        .filter-badge { cursor: pointer; user-select: none; }
    </style>
</head>
<body>

{{-- NAVBAR --}}
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">✈️ GrupTalepleri</a>
        <div class="d-flex align-items-center gap-3">
            <div class="text-white-50 text-end d-none d-md-block">
                <div class="fw-bold text-white">{{ $agency->contact_name ?? auth()->user()->name }}</div>
                <small>{{ $agency->company_title ?? 'Acente' }}</small>
            </div>
            <a href="{{ route('acente.requests.create') }}" class="btn btn-danger btn-sm">
                <i class="fas fa-plus"></i> Yeni Talep
            </a>

<a href="{{ route('acente.profil') }}" class="btn btn-outline-light btn-sm">
    <i class="fas fa-user"></i> Profilim
</a>


            <x-notification-bell />
            <a href="https://wa.me/905324262630" target="_blank" class="btn btn-success btn-sm">
                <i class="fab fa-whatsapp"></i>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">

    {{-- ÖZET KARTLAR --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card stat-card text-center p-3 shadow-sm">
                <div class="stat-number text-dark">{{ $istatistik['toplam'] }}</div>
                <div class="text-muted small">Toplam</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card text-center p-3 shadow-sm" style="border-top: 3px solid #6c757d">
                <div class="stat-number text-secondary">{{ $istatistik['beklemede'] }}</div>
                <div class="text-muted small">Beklemede</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card text-center p-3 shadow-sm" style="border-top: 3px solid #0d6efd">
                <div class="stat-number text-primary">{{ $istatistik['islemde'] }}</div>
                <div class="text-muted small">İşlemde</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card text-center p-3 shadow-sm" style="border-top: 3px solid #ffc107">
                <div class="stat-number text-warning">{{ $istatistik['fiyatlandirıldi'] }}</div>
                <div class="text-muted small">Fiyatlandırıldı</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card text-center p-3 shadow-sm" style="border-top: 3px solid #dc3545">
                <div class="stat-number text-danger">{{ $istatistik['iptal'] }}</div>
                <div class="text-muted small">İptal</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card text-center p-3 shadow-sm" style="border-top: 3px solid #198754">
                <div class="stat-number text-success">{{ $istatistik['biletlendi'] }}</div>
                <div class="text-muted small">Biletlendi</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card stat-card text-center p-3 shadow-sm" style="border-top: 3px solid #6f42c1">
                <div class="stat-number" style="color:#6f42c1">{{ $istatistik['depozitoda'] }}</div>
                <div class="text-muted small">Depozitoda</div>
            </div>
        </div>
    </div>

    {{-- HARİTA --}}
    <div class="card shadow-sm mb-4">
        <div class="map-filters d-flex align-items-center gap-2 flex-wrap">
            <small class="text-muted me-2">Filtre:</small>
            <span class="badge filter-badge bg-secondary" data-status="beklemede" onclick="toggleFilter(this)">Beklemede</span>
            <span class="badge filter-badge bg-primary" data-status="islemde" onclick="toggleFilter(this)">İşlemde</span>
            <span class="badge filter-badge bg-warning text-dark" data-status="fiyatlandirıldi" onclick="toggleFilter(this)">Fiyatlandırıldı</span>
            <span class="badge filter-badge bg-danger" data-status="iptal" onclick="toggleFilter(this)">İptal</span>
            <span class="badge filter-badge bg-success" data-status="biletlendi" onclick="toggleFilter(this)">Biletlendi</span>
            <span class="badge filter-badge" data-status="depozitoda" style="background:#6f42c1" onclick="toggleFilter(this)">Depozitoda</span>
        </div>
        <div id="map"></div>
    </div>

    {{-- TALEPLER LİSTESİ --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">📋 Taleplerim</h6>
            <span class="badge bg-secondary">{{ $istatistik['toplam'] }} talep</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>GTPNR</th>
                        <th>Rota</th>
                        <th>PAX</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody id="talepTablosu">
                    @php
                    $statusConfig = [
                        'beklemede'       => ['bg'=>'#6c757d','color'=>'#fff','label'=>'Beklemede'],
                        'islemde'         => ['bg'=>'#0d6efd','color'=>'#fff','label'=>'İşlemde'],
                        'fiyatlandirıldi' => ['bg'=>'#ffc107','color'=>'#000','label'=>'Fiyatlandırıldı'],
                        'iptal'           => ['bg'=>'#dc3545','color'=>'#fff','label'=>'İptal'],
                        'biletlendi'      => ['bg'=>'#198754','color'=>'#fff','label'=>'Biletlendi'],
                        'depozitoda'      => ['bg'=>'#6f42c1','color'=>'#fff','label'=>'Depozitoda'],
                    ];
                    @endphp
                    @forelse($talepler as $talep)
                    @php $sc = $statusConfig[$talep->status] ?? ['bg'=>'#6c757d','color'=>'#fff','label'=>$talep->status]; @endphp
                    <tr data-status="{{ $talep->status }}" style="border-left: 4px solid {{ $sc['bg'] }}">
                        <td><strong>{{ $talep->gtpnr }}</strong></td>
                        <td>
                            @foreach($talep->segments as $seg)
                                <span class="badge bg-light text-dark border">{{ $seg->from_iata }}→{{ $seg->to_iata }}</span>
                            @endforeach
                        </td>
                        <td>{{ $talep->pax_total }}</td>
                        <td>
                            <span class="badge" style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }}">{{ $sc['label'] }}</span>
                        </td>
                        <td>{{ $talep->created_at->format('d.m.Y') }}</td>
                        <td>
                            <a href="{{ route('acente.requests.show', $talep->gtpnr) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Detay
                            </a>
                            <a href="https://wa.me/905324262630?text={{ urlencode($talep->gtpnr . ' talebi hakkında bilgi almak istiyorum') }}"
                               target="_blank" class="btn btn-sm btn-success">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Henüz talep yok. <a href="{{ route('acente.requests.create') }}">İlk talebi oluştur</a></td>
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
const statusRenkler = {
    'beklemede':       '#6c757d',
    'islemde':         '#0d6efd',
    'fiyatlandirıldi': '#ffc107',
    'iptal':           '#dc3545',
    'biletlendi':      '#198754',
    'depozitoda':      '#6f42c1',
};
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

            const statusEtiketler = {'beklemede':'Beklemede','islemde':'İşlemde','fiyatlandirıldi':'Fiyatlandırıldı','iptal':'İptal','biletlendi':'Biletlendi','depozitoda':'Depozitoda'};
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
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA4CoEHudF9V3Zn4h6udx6Ftr3u6h51EXo&libraries=geometry&callback=initMap" async defer></script>
</body>
</html>

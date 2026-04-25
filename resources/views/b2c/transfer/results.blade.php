@extends('b2c.layouts.app')

@section('title', 'Transfer Sonuçları')
@section('meta_description', 'Uygun transfer seçeneklerini inceleyin ve rezervasyon yapın.')

@push('head_styles')
<style>
.results-header { background: #0f2444; color: #fff; padding: 24px 0; }
.results-header h2 { font-size: 1.3rem; font-weight: 700; }
.results-header .breadcrumb-item, .results-header .breadcrumb-item a { color: rgba(255,255,255,.7); font-size: .85rem; }
.results-header .breadcrumb-item.active { color: #fff; }

.option-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: #fff;
    transition: box-shadow .15s;
}
.option-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.1); }
.option-card .vehicle-img {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    background: #f1f5f9;
}
.option-card .vehicle-img-placeholder {
    width: 120px;
    height: 80px;
    border-radius: 8px;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 2rem;
}
.price-tag { font-size: 1.8rem; font-weight: 800; color: #0f2444; line-height: 1; }
.price-tag small { font-size: .75rem; font-weight: 400; color: #64748b; display: block; }
.amenity-badge { background: #f0f4ff; color: #3b5fc0; font-size: .72rem; padding: 3px 8px; border-radius: 20px; }
.book-btn { background: #FF5533; color: #fff; border: none; border-radius: 10px; padding: 12px 28px; font-weight: 700; font-size: 1rem; transition: background .15s; }
.book-btn:hover { background: #e04420; color: #fff; }
.no-results { text-align: center; padding: 60px 20px; }
.no-results i { font-size: 3rem; color: #cbd5e1; }

.sidebar-search { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.25rem; }
.sidebar-search .form-label { font-size: .82rem; font-weight: 600; color: #334155; }
</style>
@endpush

@section('content')

{{-- Başlık --}}
<div class="results-header">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ lroute('b2c.transfer.index') }}">Transfer</a></li>
                <li class="breadcrumb-item active">Sonuçlar</li>
            </ol>
        </nav>
        <h2>
            <i class="bi bi-car-front-fill me-2"></i>
            @php
                $directionLabels = ['ARR' => 'Varış (Havalimanı → Otel)', 'DEP' => 'Gidiş (Otel → Havalimanı)', 'BOTH' => 'Gidiş-Dönüş'];
            @endphp
            {{ $directionLabels[$search['direction']] ?? $search['direction'] }} &mdash;
            {{ $search['pax'] }} yolcu &mdash;
            {{ \Carbon\Carbon::parse($search['pickup_at'])->format('d.m.Y H:i') }}
        </h2>
    </div>
</div>

<div class="container py-4">
<div class="row g-4">

    {{-- Kenar: Arama Düzenle --}}
    <div class="col-lg-3">
        <div class="sidebar-search">
            <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-1"></i>Aramayı Düzenle</h6>
            <form method="POST" action="{{ lroute('b2c.transfer.search') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Yön</label>
                    <select name="direction" class="form-select form-select-sm">
                        @foreach(['ARR' => 'Varış', 'DEP' => 'Gidiş', 'BOTH' => 'Gidiş-Dönüş'] as $val => $lbl)
                            <option value="{{ $val }}" {{ $search['direction'] === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Havalimanı</label>
                    <select name="airport_id" class="form-select form-select-sm" id="sidebar_airport">
                        @foreach($airports as $a)
                            <option value="{{ $a['id'] }}" {{ $search['airport_id'] == $a['id'] ? 'selected' : '' }}>
                                {{ $a['code'] }} — {{ $a['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Bölge</label>
                    <input type="hidden" name="zone_id" id="sidebar_zone_id" value="{{ $search['zone_id'] }}">
                    <select id="sidebar_zone_select" class="form-select form-select-sm">
                        <option value="{{ $search['zone_id'] }}" selected>Mevcut Seçim</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Tarih/Saat</label>
                    <input type="datetime-local" name="pickup_at" class="form-control form-control-sm"
                           value="{{ $search['pickup_at'] }}"
                           min="{{ now()->addHour()->format('Y-m-d\TH:i') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Yolcu Sayısı</label>
                    <input type="number" name="pax" class="form-control form-control-sm"
                           value="{{ $search['pax'] }}" min="1" max="100">
                </div>
                <button type="submit" class="btn btn-sm btn-primary w-100">Yeniden Ara</button>
            </form>
        </div>
    </div>

    {{-- Sonuçlar --}}
    <div class="col-lg-9">
        @if($error && empty($options))
            <div class="no-results">
                <i class="bi bi-search d-block mb-3"></i>
                <h5 class="fw-bold">Sonuç Bulunamadı</h5>
                <p class="text-muted">{{ $error }}</p>
                <a href="{{ lroute('b2c.transfer.index') }}" class="btn btn-primary mt-2">Yeniden Ara</a>
            </div>
        @else
            <p class="text-muted mb-3"><strong>{{ count($options) }}</strong> seçenek bulundu</p>

            @foreach($options as $option)
            <div class="option-card">
                <div class="d-flex gap-3 align-items-start">
                    {{-- Araç Görseli --}}
                    @if(!empty($option['vehicle_photos'][0]))
                        <img src="{{ $option['vehicle_photos'][0] }}" alt="{{ $option['vehicle_type'] }}"
                             class="vehicle-img d-none d-sm-block">
                    @else
                        <div class="vehicle-img-placeholder d-none d-sm-block">
                            <i class="bi bi-car-front"></i>
                        </div>
                    @endif

                    {{-- Detaylar --}}
                    <div class="flex-fill">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <h5 class="fw-bold mb-1">{{ $option['vehicle_type'] }}</h5>
                                @if($option['vehicle_description'])
                                    <p class="text-muted small mb-2">{{ $option['vehicle_description'] }}</p>
                                @endif

                                <div class="d-flex gap-3 flex-wrap mb-2">
                                    <span class="small text-muted">
                                        <i class="bi bi-people-fill me-1"></i>Maks. {{ $option['vehicle_max_passengers'] }} kişi
                                    </span>
                                    @if($option['vehicle_luggage_capacity'])
                                    <span class="small text-muted">
                                        <i class="bi bi-luggage-fill me-1"></i>{{ $option['vehicle_luggage_capacity'] }} bagaj
                                    </span>
                                    @endif
                                    <span class="small text-muted">
                                        <i class="bi bi-clock me-1"></i>~{{ $option['duration_minutes'] }} dk
                                    </span>
                                    <span class="small text-muted">
                                        <i class="bi bi-geo me-1"></i>{{ number_format($option['distance_km'], 1) }} km
                                    </span>
                                </div>

                                @if(!empty($option['vehicle_amenities']))
                                <div class="d-flex gap-1 flex-wrap mb-2">
                                    @foreach(array_slice($option['vehicle_amenities'], 0, 4) as $amenity)
                                        <span class="amenity-badge">{{ is_array($amenity) ? ($amenity['name'] ?? '') : $amenity }}</span>
                                    @endforeach
                                </div>
                                @endif

                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>{{ $option['cancellation_policy'] }}
                                </small>
                            </div>

                            <div class="text-end">
                                <div class="price-tag">
                                    {{ number_format($option['total_price'], 0, ',', '.') }}
                                    <small>{{ $option['currency'] }}</small>
                                </div>
                                <div class="small text-muted mb-2">Toplam fiyat</div>
                                <a href="{{ $option['booking_url'] }}" class="book-btn btn">
                                    Rezervasyon Yap
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebarAirport = document.getElementById('sidebar_airport');
    const sidebarZoneSel = document.getElementById('sidebar_zone_select');
    const sidebarZoneId  = document.getElementById('sidebar_zone_id');
    const currentZoneId  = '{{ $search['zone_id'] }}';

    function loadZones(airportId, selectedZone) {
        fetch('{{ lroute('b2c.transfer.zones') }}?airport_id=' + airportId)
            .then(function (r) { return r.json(); })
            .then(function (zones) {
                sidebarZoneSel.innerHTML = '<option value="">— Seçin —</option>';
                zones.forEach(function (z) {
                    const o = document.createElement('option');
                    o.value = z.id;
                    o.textContent = z.name;
                    if (String(z.id) === String(selectedZone)) { o.selected = true; }
                    sidebarZoneSel.appendChild(o);
                });
                sidebarZoneId.value = sidebarZoneSel.value;
            });
    }

    sidebarAirport.addEventListener('change', function () {
        loadZones(this.value, '');
    });

    sidebarZoneSel.addEventListener('change', function () {
        sidebarZoneId.value = this.value;
    });

    // İlk yükleme
    if (sidebarAirport.value) {
        loadZones(sidebarAirport.value, currentZoneId);
    }
});
</script>
@endpush

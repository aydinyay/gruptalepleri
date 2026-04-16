@extends('b2c.layouts.app')

@section('title', 'Transfer Ara — Havalimanı Transferi')
@section('meta_description', 'Uygun fiyatlı havalimanı ve şehir içi transfer rezervasyonu yapın. Güvenilir araçlar, anlık fiyat ve kolay ödeme.')

@push('head_styles')
<style>
.transfer-hero {
    background: linear-gradient(135deg, #0f2444 0%, #1a3c6b 60%, #2a5298 100%);
    padding: 60px 0 40px;
    color: #fff;
}
.transfer-hero h1 { font-size: 2.2rem; font-weight: 800; }
.transfer-hero p   { color: rgba(255,255,255,.75); font-size: 1.05rem; }
.search-card {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    max-width: 860px;
    margin: -30px auto 0;
    position: relative;
    z-index: 10;
}
.form-label-lg { font-weight: 600; font-size: .92rem; color: #334155; margin-bottom: .4rem; display:block; }
.direction-btn { border: 2px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; cursor: pointer; transition: all .15s; }
.direction-btn:hover, .direction-btn.active { border-color: #1a3c6b; background: #e8f0fa; }
.direction-btn input { display: none; }
.direction-btn i { font-size: 1.4rem; color: #1a3c6b; }
.feature-icons .fi { text-align: center; }
.feature-icons .fi i { font-size: 2rem; color: #1a3c6b; }
.feature-icons .fi p { font-size: .85rem; color: #64748b; margin-top: .4rem; }
</style>
@endpush

@section('content')
{{-- Hero --}}
<div class="transfer-hero">
    <div class="container text-center">
        <h1><i class="bi bi-car-front-fill me-2"></i>Transfer Ara</h1>
        <p>Havalimanı-otel transferleri için en uygun fiyatı bulun.</p>
    </div>
</div>

{{-- Arama Kartı --}}
<div class="container pb-5">
<div class="search-card">

    @if(session('error'))
        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('b2c.transfer.search') }}" id="transferSearchForm">
        @csrf

        {{-- Yön --}}
        <div class="mb-4">
            <label class="form-label-lg">Seyahat Yönü</label>
            <div class="d-flex gap-2 flex-wrap">
                <label class="direction-btn flex-fill text-center {{ old('direction', 'ARR') === 'ARR' ? 'active' : '' }}">
                    <input type="radio" name="direction" value="ARR" {{ old('direction', 'ARR') === 'ARR' ? 'checked' : '' }}>
                    <i class="bi bi-airplane-landing d-block"></i>
                    <span class="fw-bold small">Varış</span>
                    <small class="text-muted d-block">Havalimanı → Otel</small>
                </label>
                <label class="direction-btn flex-fill text-center {{ old('direction') === 'DEP' ? 'active' : '' }}">
                    <input type="radio" name="direction" value="DEP" {{ old('direction') === 'DEP' ? 'checked' : '' }}>
                    <i class="bi bi-airplane-fill d-block"></i>
                    <span class="fw-bold small">Gidiş</span>
                    <small class="text-muted d-block">Otel → Havalimanı</small>
                </label>
                <label class="direction-btn flex-fill text-center {{ old('direction') === 'BOTH' ? 'active' : '' }}">
                    <input type="radio" name="direction" value="BOTH" {{ old('direction') === 'BOTH' ? 'checked' : '' }}>
                    <i class="bi bi-arrow-left-right d-block"></i>
                    <span class="fw-bold small">Gidiş-Dönüş</span>
                    <small class="text-muted d-block">Her iki yön</small>
                </label>
            </div>
        </div>

        <div class="row g-3">
            {{-- Havalimanı --}}
            <div class="col-md-6">
                <label class="form-label-lg" for="airport_id">Havalimanı</label>
                <select name="airport_id" id="airport_id" class="form-select @error('airport_id') is-invalid @enderror" required>
                    <option value="">— Havalimanı seçin —</option>
                    @foreach($airports as $a)
                        <option value="{{ $a['id'] }}" {{ old('airport_id') == $a['id'] ? 'selected' : '' }}>
                            {{ $a['code'] }} — {{ $a['name'] }}
                        </option>
                    @endforeach
                </select>
                @error('airport_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Bölge --}}
            <div class="col-md-6">
                <label class="form-label-lg" for="zone_id">Bölge / Otel</label>
                <select name="zone_id" id="zone_id" class="form-select @error('zone_id') is-invalid @enderror" required disabled>
                    <option value="">— Önce havalimanı seçin —</option>
                </select>
                @error('zone_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Kalkış Tarihi --}}
            <div class="col-md-4">
                <label class="form-label-lg" for="pickup_at">Kalkış Tarihi/Saati</label>
                <input type="datetime-local" name="pickup_at" id="pickup_at"
                       class="form-control @error('pickup_at') is-invalid @enderror"
                       value="{{ old('pickup_at') }}"
                       min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                       required>
                @error('pickup_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Dönüş Tarihi (BOTH) --}}
            <div class="col-md-4" id="returnDateWrapper" style="display:none;">
                <label class="form-label-lg" for="return_at">Dönüş Tarihi/Saati</label>
                <input type="datetime-local" name="return_at" id="return_at"
                       class="form-control @error('return_at') is-invalid @enderror"
                       value="{{ old('return_at') }}">
                @error('return_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Yolcu Sayısı --}}
            <div class="col-md-4">
                <label class="form-label-lg" for="pax">Yolcu Sayısı</label>
                <input type="number" name="pax" id="pax"
                       class="form-control @error('pax') is-invalid @enderror"
                       value="{{ old('pax', 1) }}"
                       min="1" max="100" required>
                @error('pax')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold" id="searchBtn">
                <i class="bi bi-search me-2"></i>Transfer Ara
            </button>
        </div>
    </form>
</div>

{{-- Özellikler --}}
<div class="row g-4 feature-icons mt-4 mb-2 justify-content-center">
    <div class="col-6 col-md-3 fi">
        <i class="bi bi-shield-check-fill d-block"></i>
        <p>Güvenli Ödeme</p>
    </div>
    <div class="col-6 col-md-3 fi">
        <i class="bi bi-clock-fill d-block"></i>
        <p>7/24 Hizmet</p>
    </div>
    <div class="col-6 col-md-3 fi">
        <i class="bi bi-geo-alt-fill d-block"></i>
        <p>Tüm Havalimanları</p>
    </div>
    <div class="col-6 col-md-3 fi">
        <i class="bi bi-cash-coin d-block"></i>
        <p>En İyi Fiyat</p>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const airportSel = document.getElementById('airport_id');
    const zoneSel    = document.getElementById('zone_id');
    const directionInputs = document.querySelectorAll('input[name="direction"]');
    const returnWrapper   = document.getElementById('returnDateWrapper');
    const directionBtns   = document.querySelectorAll('.direction-btn');

    // Yön seçim renklendirmesi
    directionInputs.forEach(function (inp) {
        inp.addEventListener('change', function () {
            directionBtns.forEach(function (b) { b.classList.remove('active'); });
            inp.closest('.direction-btn').classList.add('active');
            returnWrapper.style.display = (inp.value === 'BOTH') ? 'block' : 'none';
        });
    });

    if (document.querySelector('input[name="direction"]:checked')?.value === 'BOTH') {
        returnWrapper.style.display = 'block';
    }

    // Havalimanı değişince bölgeleri yükle
    airportSel.addEventListener('change', function () {
        const airportId = this.value;
        zoneSel.innerHTML = '<option>Yükleniyor...</option>';
        zoneSel.disabled = true;

        if (! airportId) {
            zoneSel.innerHTML = '<option value="">— Önce havalimanı seçin —</option>';
            return;
        }

        fetch('{{ route('b2c.transfer.zones') }}?airport_id=' + airportId, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function (r) { return r.json(); })
        .then(function (zones) {
            zoneSel.innerHTML = '<option value="">— Bölge seçin —</option>';
            zones.forEach(function (z) {
                const opt = document.createElement('option');
                opt.value = z.id;
                opt.textContent = z.name + (z.city ? ' — ' + z.city : '');
                zoneSel.appendChild(opt);
            });
            zoneSel.disabled = false;
            @if(old('zone_id'))
            zoneSel.value = '{{ old('zone_id') }}';
            @endif
        })
        .catch(function () {
            zoneSel.innerHTML = '<option value="">Bölgeler yüklenemedi</option>';
            zoneSel.disabled = false;
        });
    });

    // Sayfa yüklendiğinde eski değer varsa bölgeleri yükle
    if (airportSel.value) {
        airportSel.dispatchEvent(new Event('change'));
    }

    // Arama butonu loading
    document.getElementById('transferSearchForm').addEventListener('submit', function () {
        const btn = document.getElementById('searchBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Aranıyor...';
    });
});
</script>
@endpush

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acenta Adına Talep Oluştur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .segment-card { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; border-radius: 10px; padding: 16px; margin-bottom: 12px; }
        .airport-wrap { position: relative; }
        .airport-display { font-size: 0.82rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 6px; padding: 6px 10px; width: 100%; cursor: text; }
        .airport-display::placeholder { color: rgba(255,255,255,0.35); font-size: 0.78rem; }
        .airport-display:focus { outline: none; background: rgba(255,255,255,0.15); border-color: #0d6efd; }
        .airport-iata-badge { font-size: 1.3rem; font-weight: 700; letter-spacing: 2px; color: #fff; display: block; text-align: center; min-height: 2rem; }
        .airport-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #1e2a45; border: 1px solid rgba(255,255,255,0.15); border-radius: 0 0 8px 8px; z-index: 1050; max-height: 260px; overflow-y: auto; display: none; }
        .airport-dropdown.show { display: block; }
        .airport-option { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .airport-option:hover, .airport-option.focused { background: rgba(13,110,253,0.25); }
        .airport-option .ap-iata { font-weight: 700; color: #0d6efd; font-size: 0.95rem; margin-right: 6px; }
        .airport-option .ap-city { color: #fff; font-size: 0.82rem; }
        .airport-option .ap-name { color: rgba(255,255,255,0.5); font-size: 0.72rem; display: block; }
    </style>
</head>
<body>

<x-navbar-admin active="talepler" />

<div class="container-fluid px-3 py-3" style="max-width:860px;">

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary btn-sm">← Geri</a>
        <h5 class="mb-0 fw-bold">Acenta Adına Talep Oluştur</h5>
    </div>

    <div class="alert alert-warning py-2 mb-3 small">
        <i class="fas fa-user-shield me-1"></i>
        <strong>Admin işlemi.</strong> Bu talep seçilen acentanın adına sisteme kaydedilecek. Log'da admin adınız görünecek.
    </div>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 small">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.requests.store-on-behalf') }}" id="talep-form">
        @csrf

        {{-- Acenta Seç --}}
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold">👤 Acenta Seç</div>
            <div class="card-body py-2">
                <select name="acente_user_id" class="form-select" required>
                    <option value="">— Acenta seçin —</option>
                    @foreach($acenteler as $u)
                    <option value="{{ $u->id }}" {{ old('acente_user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }} ({{ $u->email }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Acente Bilgileri --}}
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold">📋 Talep Bilgileri</div>
            <div class="card-body py-2">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Acente Adı <span class="text-danger">*</span></label>
                        <input type="text" name="agency_name" class="form-control" value="{{ old('agency_name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Telefon <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">E-posta <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Grup / Firma Adı</label>
                        <input type="text" name="group_company_name" class="form-control" value="{{ old('group_company_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Uçuş Amacı</label>
                        <select name="flight_purpose" class="form-select">
                            <option value="">Seçin</option>
                            @foreach(['turizm'=>'Turizm','hac_umre'=>'Hac / Umre','is'=>'İş','okul'=>'Okul / Eğitim','diger'=>'Diğer'] as $val => $label)
                            <option value="{{ $val }}" {{ old('flight_purpose') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Yolcu --}}
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold">👥 Yolcu</div>
            <div class="card-body py-2">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Yetişkin</label>
                        <input type="number" name="pax_adult" id="pax-adult" class="form-control text-center" value="{{ old('pax_adult', 0) }}" min="0" oninput="paxHesapla()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Çocuk</label>
                        <input type="number" name="pax_child" id="pax-child" class="form-control text-center" value="{{ old('pax_child', 0) }}" min="0" oninput="paxHesapla()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Bebek</label>
                        <input type="number" name="pax_infant" id="pax-infant" class="form-control text-center" value="{{ old('pax_infant', 0) }}" min="0" oninput="paxHesapla()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Toplam PAX <span class="text-danger">*</span></label>
                        <input type="number" name="pax_total" id="pax-total" class="form-control text-center fw-bold" value="{{ old('pax_total', 10) }}" min="1" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Uçuş --}}
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold">✈️ Uçuş</div>
            <div class="card-body py-2">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">Uçuş Tipi <span class="text-danger">*</span></label>
                        <select name="trip_type" id="trip-type" class="form-select" onchange="tripTipiDegisti()">
                            <option value="one_way">Tek Yön</option>
                            <option value="round_trip">Gidiş - Dönüş</option>
                            <option value="multi">Çoklu Uçuş</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tercih Edilen Havayolu</label>
                        <input type="text" name="preferred_airline" class="form-control" value="{{ old('preferred_airline') }}" placeholder="TK, Turkish Airlines...">
                    </div>
                </div>
                <div id="segments"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="add-segment-btn" onclick="segmentEkle()" style="display:none;">
                    <i class="fas fa-plus me-1"></i>Ara Uçuş Ekle
                </button>
            </div>
        </div>

        {{-- Ek --}}
        <div class="card mb-4">
            <div class="card-header py-2 fw-semibold">📝 Ek Bilgiler</div>
            <div class="card-body py-2">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="hotel_needed" value="1" id="hotel">
                            <label class="form-check-label" for="hotel">🏨 Otel isteniyor</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="visa_needed" value="1" id="visa">
                            <label class="form-check-label" for="visa">📋 Vize desteği isteniyor</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Notlar</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success px-4">
                <i class="fas fa-paper-plane me-2"></i>Talebi Oluştur
            </button>
            <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary">Vazgeç</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let segmentCount = 0;
const AIRPORT_SEARCH_URL = '{{ route('airports.search') }}';

function airportWidget(name, placeholder, iataVal, required) {
    const req = required ? 'required' : '';
    return `
    <div class="airport-wrap">
        <span class="airport-iata-badge" data-iata-badge>${iataVal || '—'}</span>
        <input type="hidden" name="${name}" value="${iataVal}" data-iata-hidden ${req}>
        <input type="text" class="airport-display mt-1" placeholder="${placeholder}"
               autocomplete="off" data-airport-input data-field="${name}">
        <div class="airport-dropdown" data-airport-dropdown></div>
    </div>`;
}

function segmentHtmlOlustur(index, label = '', silinebilir = false) {
    return `
    <div class="segment-card" id="seg-${index}">
        ${label ? `<div class="small text-white-50 mb-2 fw-bold">${label}</div>` : ''}
        <div class="row g-2 align-items-end">
            <div class="col-5 col-md-3">
                <label class="form-label small text-white-50">Kalkış</label>
                ${airportWidget('segments['+index+'][from_iata]', 'IST, İstanbul...', '', true)}
            </div>
            <div class="col-2 text-center" style="padding-top:2.2rem;">
                <i class="fas fa-arrow-right text-primary" style="font-size:1.2rem;"></i>
            </div>
            <div class="col-5 col-md-3">
                <label class="form-label small text-white-50">Varış</label>
                ${airportWidget('segments['+index+'][to_iata]', 'CDG, Paris...', '', true)}
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-white-50">Tarih <span style="color:#f0ad4e;">*</span></label>
                <input type="date" name="segments[${index}][departure_date]" class="form-control form-control-sm bg-dark text-white border-secondary" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-white-50">Kalkış Zamanı <span style="color:#f0ad4e;">*</span></label>
                <select name="segments[${index}][departure_time_slot]" class="form-select form-select-sm bg-dark text-white border-secondary" required>
                    <option value="">Seçin...</option>
                    <option value="sabah">🌅 Sabah (06–12)</option>
                    <option value="ogle">☀️ Öğle (12–17)</option>
                    <option value="aksam">🌆 Akşam / Gece</option>
                    <option value="esnek">🔄 Esnek / Fark etmez</option>
                </select>
            </div>
            ${silinebilir ? `<div class="col-12 text-end"><button type="button" class="btn btn-outline-danger btn-sm" onclick="segmentSil(${index})"><i class="fas fa-times me-1"></i>Sil</button></div>` : ''}
        </div>
    </div>`;
}

function segmentleriYenile(tip) {
    const container = document.getElementById('segments');
    const addBtn = document.getElementById('add-segment-btn');
    segmentCount = 0;
    container.innerHTML = '';
    if (tip === 'one_way') {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, 'Gidiş');
        addBtn.style.display = 'none';
    } else if (tip === 'round_trip') {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, 'Gidiş');
        container.innerHTML += segmentHtmlOlustur(segmentCount++, 'Dönüş');
        addBtn.style.display = 'none';
    } else {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, '1. Uçuş');
        container.innerHTML += segmentHtmlOlustur(segmentCount++, '2. Uçuş', true);
        addBtn.style.display = 'inline-block';
    }
    initAirportInputs();
}

function segmentEkle() {
    const container = document.getElementById('segments');
    const div = document.createElement('div');
    div.innerHTML = segmentHtmlOlustur(segmentCount, (segmentCount + 1) + '. Uçuş', true);
    container.appendChild(div.firstElementChild);
    segmentCount++;
    initAirportInputs();
}

function segmentSil(index) {
    const el = document.getElementById('seg-' + index);
    if (el) el.remove();
}

function tripTipiDegisti() {
    segmentleriYenile(document.getElementById('trip-type').value);
}

function paxHesapla() {
    const y = parseInt(document.getElementById('pax-adult').value) || 0;
    const c = parseInt(document.getElementById('pax-child').value) || 0;
    const b = parseInt(document.getElementById('pax-infant').value) || 0;
    if (y + c + b > 0) document.getElementById('pax-total').value = y + c + b;
}

document.getElementById('talep-form').addEventListener('submit', function(e) {
    const hiddens = this.querySelectorAll('[data-iata-hidden]');
    let ok = true;
    hiddens.forEach(h => {
        const wrap = h.closest('.airport-wrap');
        const display = wrap.querySelector('[data-airport-input]');
        if (!h.value) { ok = false; display.style.borderColor = '#dc3545'; display.focus(); }
        else { display.style.borderColor = ''; }
    });
    if (!ok) { e.preventDefault(); alert('Lütfen kalkış ve varış havalimanlarını listeden seçin.'); }
});

// Airport autocomplete
let acTimer = null;

function initAirportInputs() {
    document.querySelectorAll('[data-airport-input]').forEach(input => {
        if (input._acInitialized) return;
        input._acInitialized = true;
    });
}

document.addEventListener('input', function(e) {
    const input = e.target;
    if (!input.hasAttribute('data-airport-input')) return;
    clearTimeout(acTimer);
    const q = input.value.trim();
    if (q.length < 2) { closeAcDd(input); return; }
    showAcLoading(input);
    acTimer = setTimeout(() => fetchAirports(q, input), 280);
});

document.addEventListener('keydown', function(e) {
    const input = e.target;
    if (!input.hasAttribute('data-airport-input')) return;
    const dd = getAcDd(input);
    const items = dd.querySelectorAll('.airport-option');
    let focused = dd.querySelector('.airport-option.focused');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!focused) items[0]?.classList.add('focused');
        else { focused.classList.remove('focused'); (focused.nextElementSibling || items[0]).classList.add('focused'); }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (!focused) items[items.length-1]?.classList.add('focused');
        else { focused.classList.remove('focused'); (focused.previousElementSibling || items[items.length-1]).classList.add('focused'); }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (focused) selectAirport(focused, input);
    } else if (e.key === 'Escape') { closeAcDd(input); }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.airport-wrap')) {
        document.querySelectorAll('.airport-dropdown.show').forEach(dd => dd.classList.remove('show'));
    }
});

function getAcDd(input) { return input.closest('.airport-wrap').querySelector('[data-airport-dropdown]'); }
function getAcBadge(input) { return input.closest('.airport-wrap').querySelector('[data-iata-badge]'); }
function getAcHidden(input) { return input.closest('.airport-wrap').querySelector('[data-iata-hidden]'); }
function showAcLoading(input) {
    const dd = getAcDd(input);
    dd.innerHTML = '<div style="padding:8px 12px;color:rgba(255,255,255,.4);font-size:.8rem;"><i class="fas fa-spinner fa-spin me-1"></i>Aranıyor...</div>';
    dd.classList.add('show');
}
function closeAcDd(input) { getAcDd(input).classList.remove('show'); }

async function fetchAirports(q, input) {
    try {
        const resp = await fetch(AIRPORT_SEARCH_URL + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await resp.json();
        const dd = getAcDd(input);
        if (!data.length) {
            dd.innerHTML = '<div style="padding:8px 12px;color:rgba(255,255,255,.4);font-size:.8rem;">Sonuç bulunamadı</div>';
            dd.classList.add('show');
            return;
        }
        dd.innerHTML = data.map(a => `
            <div class="airport-option" data-iata="${esc(a.iata)}" data-city="${esc(a.city||'')}" data-country="${esc(a.country||'')}">
                <span class="ap-iata">${a.iata}</span>
                <span class="ap-city">${esc(a.city||'')}${a.city&&a.country?', ':''}${esc(a.country||'')}</span>
                <span class="ap-name">${esc(a.name)}</span>
            </div>`).join('');
        dd.classList.add('show');
        dd.querySelectorAll('.airport-option').forEach(opt => {
            opt.addEventListener('mousedown', function(e) { e.preventDefault(); selectAirport(opt, input); });
        });
    } catch(err) { closeAcDd(input); }
}

function selectAirport(opt, input) {
    const iata = opt.dataset.iata;
    const city = opt.dataset.city;
    const country = opt.dataset.country;
    getAcHidden(input).value = iata;
    getAcBadge(input).textContent = iata;
    input.value = city ? `${iata} — ${city}${country?', '+country:''}` : iata;
    closeAcDd(input);
}

function esc(str) { return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

tripTipiDegisti();
</script>
</body>
</html>

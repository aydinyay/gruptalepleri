<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('acente.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Yeni Grup Uçuş Talebi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        html[data-theme="dark"] body { background: #1a1a2e !important; }
        .section-header { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 700; margin-bottom: 0.75rem; }
        .segment-card { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; border-radius: 10px; }
        .iata-input { font-size: 1.4rem; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; text-align: center; }
        .required-star { color: #e94560; }
        .pax-box { border: 2px solid #dee2e6; border-radius: 8px; padding: 12px; text-align: center; }
        .pax-box.active { border-color: #0d6efd; background: #f0f5ff; }

        /* Havayolu autocomplete */
        .airline-wrap { position: relative; }
        .airline-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #dee2e6; border-radius: 0 0 8px 8px; z-index: 1050; max-height: 240px; overflow-y: auto; display: none; box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .airline-dropdown.show { display: block; }
        .airline-option { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
        .airline-option:hover, .airline-option.focused { background: #fff5f7; }
        .airline-option .al-code { font-weight: 700; color: #e94560; font-size: 0.9rem; margin-right: 6px; }
        .airline-option .al-name { font-size: 0.85rem; color: #333; }
        .airline-option .al-country { font-size: 0.72rem; color: #6c757d; }

        /* Havalimanı autocomplete */
        .airport-wrap { position: relative; }
        .airport-display { font-size: 0.82rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 6px; padding: 6px 10px; width: 100%; cursor: text; }
        .airport-display::placeholder { color: rgba(255,255,255,0.35); font-size: 0.78rem; }
        .airport-display:focus { outline: none; background: rgba(255,255,255,0.15); border-color: #e94560; }
        .airport-iata-badge { font-size: 1.3rem; font-weight: 700; letter-spacing: 2px; color: #fff; display: block; text-align: center; min-height: 2rem; }
        .airport-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #1e2a45; border: 1px solid rgba(255,255,255,0.15); border-radius: 0 0 8px 8px; z-index: 1050; max-height: 260px; overflow-y: auto; display: none; }
        .airport-dropdown.show { display: block; }
        .airport-option { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.06); }
        .airport-option:hover, .airport-option.focused { background: rgba(233,69,96,0.25); }
        .airport-option .ap-iata { font-weight: 700; color: #e94560; font-size: 0.95rem; margin-right: 6px; }
        .airport-option .ap-city { color: #fff; font-size: 0.82rem; }
        .airport-option .ap-name { color: rgba(255,255,255,0.5); font-size: 0.72rem; display: block; }
        .airport-loading { padding: 10px 12px; color: rgba(255,255,255,0.4); font-size: 0.8rem; text-align: center; }
        .airport-empty { padding: 10px 12px; color: rgba(255,255,255,0.4); font-size: 0.8rem; text-align: center; }
    </style>
</head>
<body>

<x-navbar-acente active="create" />

<div class="container py-4" style="max-width:860px;">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">✈️ Yeni Grup Uçuş Talebi</h4>
        <p class="text-muted small mb-0">Bilgileri doldurun, operasyon ekibimiz en kısa sürede fiyat teklifi iletecektir.</p>
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

    <form method="POST" action="{{ route('acente.requests.store') }}" id="talep-form">
        @csrf

        {{-- 1. ACENTE BİLGİLERİ --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="section-header"><i class="fas fa-building me-1"></i>Acente Bilgileri</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Acente Adı <span class="required-star">*</span></label>
                        <input type="text" name="agency_name" class="form-control"
                               value="{{ old('agency_name', auth()->user()->name) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Telefon <span class="required-star">*</span></label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone') }}" placeholder="05xx xxx xx xx" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">E-posta <span class="required-star">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', auth()->user()->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Grup / Firma Adı</label>
                        <input type="text" name="group_company_name" class="form-control"
                               value="{{ old('group_company_name') }}" placeholder="Opsiyonel">
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

        {{-- 2. YOLCU BİLGİLERİ --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="section-header"><i class="fas fa-users me-1"></i>Yolcu Bilgileri</div>
                <div class="row g-3 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Yetişkin</label>
                        <input type="number" name="pax_adult" id="pax-adult" class="form-control text-center pax-input"
                               value="{{ old('pax_adult', 0) }}" min="0" oninput="paxHesapla()">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Çocuk <span class="text-muted">(2-11 yaş)</span></label>
                        <input type="number" name="pax_child" id="pax-child" class="form-control text-center pax-input"
                               value="{{ old('pax_child', 0) }}" min="0" oninput="paxHesapla()">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Bebek <span class="text-muted">(0-2 yaş)</span></label>
                        <input type="number" name="pax_infant" id="pax-infant" class="form-control text-center pax-input"
                               value="{{ old('pax_infant', 0) }}" min="0" oninput="paxHesapla()">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Toplam <span class="required-star">*</span></label>
                        <div class="input-group">
                            <input type="number" name="pax_total" id="pax-total" class="form-control text-center fw-bold"
                                   value="{{ old('pax_total', 10) }}" min="1" required>
                            <span class="input-group-text text-muted small">PAX</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Yetişkin/Çocuk/Bebek girerseniz toplam otomatik hesaplanır. Ya da doğrudan toplam girebilirsiniz.</small>
                </div>
            </div>
        </div>

        {{-- 3. UÇUŞ BİLGİLERİ --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="section-header"><i class="fas fa-plane me-1"></i>Uçuş Bilgileri</div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">Uçuş Tipi <span class="required-star">*</span></label>
                        @php($tripTypeOld = \App\Models\Request::normalizeTripType(old('trip_type', 'one_way')))
                        <select name="trip_type" id="trip-type" class="form-select" onchange="tripTipiDegisti()">
                            <option value="one_way"    {{ $tripTypeOld === 'one_way' ? 'selected' : '' }}>Tek Yön</option>
                            <option value="round_trip" {{ $tripTypeOld === 'round_trip' ? 'selected' : '' }}>Gidiş - Dönüş</option>
                            <option value="multi"      {{ $tripTypeOld === 'multi' ? 'selected' : '' }}>Çoklu Uçuş</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tercih Edilen Havayolu</label>
                        <div class="airline-wrap">
                            <input type="text" name="preferred_airline" id="airline-input"
                                   class="form-control" autocomplete="off"
                                   value="{{ old('preferred_airline') }}"
                                   placeholder="TK, Turkish Airlines...">
                            <div class="airline-dropdown" id="airline-dropdown"></div>
                        </div>
                    </div>
                </div>

                {{-- Segment(ler) --}}
                <div id="segments"></div>

                <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="add-segment-btn" onclick="segmentEkle()" style="display:none;">
                    <i class="fas fa-plus me-1"></i>Ara Uçuş Ekle
                </button>
            </div>
        </div>

        {{-- 4. EK BİLGİLER --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="section-header"><i class="fas fa-comment-alt me-1"></i>Ek Bilgiler</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="hotel_needed" value="1" id="hotel"
                                   {{ old('hotel_needed') ? 'checked' : '' }}>
                            <label class="form-check-label" for="hotel">🏨 Otel de isteniyor</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="visa_needed" value="1" id="visa"
                                   {{ old('visa_needed') ? 'checked' : '' }}>
                            <label class="form-check-label" for="visa">📋 Vize desteği isteniyor</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Notlar <span class="text-muted">(özel istek, tercih, bilgi)</span></label>
                        <textarea name="notes" class="form-control" rows="3"
                                  placeholder="Bagaj tercihi, koltuk düzeni, bütçe beklentisi vb.">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="fas fa-paper-plane me-2"></i>Talebi Gönder
            </button>
            <a href="{{ route('acente.dashboard') }}" class="btn btn-outline-secondary btn-lg">Vazgeç</a>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let segmentCount = 0;

function airportWidget(name, placeholder, iataVal, displayVal, required) {
    const req = required ? 'required' : '';
    return `
    <div class="airport-wrap">
        <span class="airport-iata-badge" data-iata-badge>${iataVal || '—'}</span>
        <input type="hidden" name="${name}" value="${iataVal}" data-iata-hidden ${req}>
        <input type="text"
               class="airport-display mt-1"
               placeholder="${placeholder}"
               value="${displayVal}"
               autocomplete="off"
               data-airport-input
               data-field="${name}">
        <div class="airport-dropdown" data-airport-dropdown></div>
    </div>`;
}

function segmentHtmlOlustur(index, fromVal = '', toVal = '', fromDisplay = '', toDisplay = '', dateVal = '', timeVal = '', label = '', silinebilir = false) {
    return `
    <div class="segment-card p-3 mb-3" id="seg-${index}">
        ${label ? `<div class="small text-white-50 mb-2 fw-bold">${label}</div>` : ''}
        <div class="row g-2 align-items-end">
            <div class="col-5 col-md-3">
                <label class="form-label small text-white-50">Kalkış</label>
                ${airportWidget('segments['+index+'][from_iata]', 'IST, İstanbul...', fromVal, fromDisplay, true)}
            </div>
            <div class="col-2 text-center" style="padding-top:2.2rem;">
                <i class="fas fa-arrow-right text-danger" style="font-size:1.2rem;"></i>
            </div>
            <div class="col-5 col-md-3">
                <label class="form-label small text-white-50">Varış</label>
                ${airportWidget('segments['+index+'][to_iata]', 'CDG, Paris...', toVal, toDisplay, true)}
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-white-50">Tarih</label>
                <input type="date" name="segments[${index}][departure_date]" class="form-control form-control-sm bg-dark text-white border-secondary"
                       value="${dateVal}" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-white-50">Saat</label>
                <input type="time" name="segments[${index}][departure_time]" class="form-control form-control-sm bg-dark text-white border-secondary"
                       value="${timeVal}">
            </div>
            ${silinebilir ? `<div class="col-12 col-md-2 text-end"><button type="button" class="btn btn-outline-danger btn-sm mt-1" onclick="segmentSil(${index})"><i class="fas fa-times"></i></button></div>` : ''}
        </div>
    </div>`;
}

function segmentleriYenile(tip) {
    const container = document.getElementById('segments');
    const addBtn = document.getElementById('add-segment-btn');
    segmentCount = 0;
    container.innerHTML = '';

    if (tip === 'one_way') {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, '', '', '', '', '', '', 'Gidiş');
        addBtn.style.display = 'none';
    } else if (tip === 'round_trip') {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, '', '', '', '', '', '', 'Gidiş');
        container.innerHTML += segmentHtmlOlustur(segmentCount++, '', '', '', '', '', '', 'Dönüş');
        addBtn.style.display = 'none';
    } else {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, '', '', '', '', '', '', '1. Uçuş');
        container.innerHTML += segmentHtmlOlustur(segmentCount++, '', '', '', '', '', '', '2. Uçuş', true);
        addBtn.style.display = 'inline-block';
    }
}

function segmentEkle() {
    const container = document.getElementById('segments');
    const div = document.createElement('div');
    div.innerHTML = segmentHtmlOlustur(segmentCount, '', '', '', '', '', '', (segmentCount + 1) + '. Uçuş', true);
    container.appendChild(div.firstElementChild);
    segmentCount++;
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
    if (y + c + b > 0) {
        document.getElementById('pax-total').value = y + c + b;
    }
}

// Form submit — kalkış/varış seçildi mi kontrol et
document.getElementById('talep-form').addEventListener('submit', function(e) {
    const hiddens = this.querySelectorAll('[data-iata-hidden]');
    let ok = true;
    hiddens.forEach(h => {
        const wrap = h.closest('.airport-wrap');
        const display = wrap.querySelector('[data-airport-input]');
        if (!h.value) {
            ok = false;
            display.style.borderColor = '#e94560';
            display.focus();
        } else {
            display.style.borderColor = '';
        }
    });
    if (!ok) {
        e.preventDefault();
        alert('Lütfen kalkış ve varış havalimanlarını listeden seçin.');
    }
});

// Sayfa yüklenince
tripTipiDegisti();

// ── Havalimanı Autocomplete ──────────────────────────────────────────────────
const AIRPORT_SEARCH_URL = '{{ route('airports.search') }}';

let acTimer = null;

// Event delegation — dinamik eklenen inputlar için
document.addEventListener('input', function(e) {
    const input = e.target;
    if (!input.hasAttribute('data-airport-input')) return;
    clearTimeout(acTimer);
    const q = input.value.trim();
    if (q.length < 2) {
        closeDropdown(input);
        return;
    }
    showLoading(input);
    acTimer = setTimeout(() => fetchAirports(q, input), 280);
});

document.addEventListener('keydown', function(e) {
    const input = e.target;
    if (!input.hasAttribute('data-airport-input')) return;
    const dd = getDropdown(input);
    const items = dd.querySelectorAll('.airport-option');
    let focused = dd.querySelector('.airport-option.focused');

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!focused) { items[0]?.classList.add('focused'); }
        else {
            focused.classList.remove('focused');
            const next = focused.nextElementSibling;
            (next || items[0]).classList.add('focused');
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (!focused) { items[items.length-1]?.classList.add('focused'); }
        else {
            focused.classList.remove('focused');
            const prev = focused.previousElementSibling;
            (prev || items[items.length-1]).classList.add('focused');
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (focused) selectAirport(focused, input);
    } else if (e.key === 'Escape') {
        closeDropdown(input);
    }
});

// Dışarı tıklanınca kapat
document.addEventListener('click', function(e) {
    if (!e.target.closest('.airport-wrap')) {
        document.querySelectorAll('.airport-dropdown.show').forEach(dd => dd.classList.remove('show'));
    }
});

function getDropdown(input) {
    return input.closest('.airport-wrap').querySelector('[data-airport-dropdown]');
}

function getBadge(input) {
    return input.closest('.airport-wrap').querySelector('[data-iata-badge]');
}

function getHidden(input) {
    return input.closest('.airport-wrap').querySelector('[data-iata-hidden]');
}

function showLoading(input) {
    const dd = getDropdown(input);
    dd.innerHTML = '<div class="airport-loading"><i class="fas fa-spinner fa-spin me-1"></i>Aranıyor...</div>';
    dd.classList.add('show');
}

function closeDropdown(input) {
    const dd = getDropdown(input);
    dd.classList.remove('show');
}

async function fetchAirports(q, input) {
    try {
        const resp = await fetch(AIRPORT_SEARCH_URL + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        renderDropdown(data, input);
    } catch(err) {
        closeDropdown(input);
    }
}

function renderDropdown(airports, input) {
    const dd = getDropdown(input);
    if (!airports.length) {
        dd.innerHTML = '<div class="airport-empty">Sonuç bulunamadı</div>';
        dd.classList.add('show');
        return;
    }
    dd.innerHTML = airports.map(a => `
        <div class="airport-option"
             data-iata="${a.iata}"
             data-label="${escHtml(a.label)}"
             data-city="${escHtml(a.city || '')}"
             data-country="${escHtml(a.country || '')}">
            <span class="ap-iata">${a.iata}</span>
            <span class="ap-city">${escHtml(a.city || '')}${a.city && a.country ? ', ' : ''}${escHtml(a.country || '')}</span>
            <span class="ap-name">${escHtml(a.name)}</span>
        </div>
    `).join('');
    dd.classList.add('show');

    dd.querySelectorAll('.airport-option').forEach(opt => {
        opt.addEventListener('mousedown', function(e) {
            e.preventDefault();
            selectAirport(opt, input);
        });
    });
}

function selectAirport(opt, input) {
    const iata    = opt.dataset.iata;
    const city    = opt.dataset.city;
    const country = opt.dataset.country;
    const display = city ? `${iata} — ${city}${country ? ', ' + country : ''}` : iata;

    getHidden(input).value  = iata;
    getBadge(input).textContent = iata;
    input.value = display;
    closeDropdown(input);
    input.dispatchEvent(new Event('change'));
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Havayolu Autocomplete ─────────────────────────────────────────────────────
const AIRLINE_SEARCH_URL = '{{ route('airlines.search') }}';
const airlineInput    = document.getElementById('airline-input');
const airlineDropdown = document.getElementById('airline-dropdown');
let alTimer = null;

airlineInput.addEventListener('input', function() {
    clearTimeout(alTimer);
    const q = this.value.trim();
    if (q.length < 2) { airlineDropdown.classList.remove('show'); return; }
    airlineDropdown.innerHTML = '<div style="padding:8px 12px;color:#aaa;font-size:0.8rem;"><i class="fas fa-spinner fa-spin me-1"></i>Aranıyor...</div>';
    airlineDropdown.classList.add('show');
    alTimer = setTimeout(() => fetchAirlines(q), 280);
});

airlineInput.addEventListener('keydown', function(e) {
    const items   = airlineDropdown.querySelectorAll('.airline-option');
    let focused   = airlineDropdown.querySelector('.airline-option.focused');
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
        if (focused) selectAirline(focused);
    } else if (e.key === 'Escape') {
        airlineDropdown.classList.remove('show');
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.airline-wrap')) airlineDropdown.classList.remove('show');
});

async function fetchAirlines(q) {
    try {
        const resp = await fetch(AIRLINE_SEARCH_URL + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        if (!data.length) {
            airlineDropdown.innerHTML = '<div style="padding:8px 12px;color:#aaa;font-size:0.8rem;">Sonuç bulunamadı</div>';
            return;
        }
        airlineDropdown.innerHTML = data.map(a => `
            <div class="airline-option" data-name="${escHtml(a.name)}" data-iata="${escHtml(a.iata||'')}" data-label="${escHtml(a.label)}">
                ${a.iata ? `<span class="al-code">${a.iata}</span>` : ''}
                ${a.icao && a.icao !== a.iata ? `<span class="al-code" style="color:#666;font-size:0.78rem;">${a.icao}</span>` : ''}
                <span class="al-name">${escHtml(a.name)}</span>
                ${a.country ? `<span class="al-country d-block">${escHtml(a.country)}</span>` : ''}
            </div>
        `).join('');
        airlineDropdown.querySelectorAll('.airline-option').forEach(opt => {
            opt.addEventListener('mousedown', e => { e.preventDefault(); selectAirline(opt); });
        });
    } catch(err) {
        airlineDropdown.classList.remove('show');
    }
}

function selectAirline(opt) {
    // input'a "TK — Turkish Airlines" formatında yaz
    const iata  = opt.dataset.iata;
    const name  = opt.dataset.name;
    airlineInput.value = iata ? `${iata} — ${name}` : name;
    airlineDropdown.classList.remove('show');
}
</script>
@include('acente.partials.theme-script')
</body>
</html>

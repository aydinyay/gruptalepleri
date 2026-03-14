<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Grup Uçuş Talebi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .navbar { background: #1a1a2e !important; }
        .navbar-brand { color: #e94560 !important; font-weight: 700; }
        .section-header { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 700; margin-bottom: 0.75rem; }
        .segment-card { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; border-radius: 10px; }
        .iata-input { font-size: 1.4rem; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; text-align: center; }
        .required-star { color: #e94560; }
        .pax-box { border: 2px solid #dee2e6; border-radius: 8px; padding: 12px; text-align: center; }
        .pax-box.active { border-color: #0d6efd; background: #f0f5ff; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-0">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('acente.dashboard') }}">✈️ GrupTalepleri</a>
        <a href="{{ route('acente.dashboard') }}" class="btn btn-outline-light btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>
</nav>

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
                        <select name="trip_type" id="trip-type" class="form-select" onchange="tripTipiDegisti()">
                            <option value="one_way"    {{ old('trip_type','one_way') === 'one_way'    ? 'selected' : '' }}>Tek Yön</option>
                            <option value="round_trip" {{ old('trip_type') === 'round_trip' ? 'selected' : '' }}>Gidiş - Dönüş</option>
                            <option value="multi"      {{ old('trip_type') === 'multi'      ? 'selected' : '' }}>Çoklu Uçuş</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tercih Edilen Havayolu</label>
                        <input type="text" name="preferred_airline" class="form-control"
                               value="{{ old('preferred_airline') }}" placeholder="Turkish Airlines, Pegasus...">
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

function segmentHtmlOlustur(index, fromVal = '', toVal = '', dateVal = '', timeVal = '', label = '', silinebilir = false) {
    return `
    <div class="segment-card p-3 mb-3" id="seg-${index}">
        ${label ? `<div class="small text-white-50 mb-2 fw-bold">${label}</div>` : ''}
        <div class="row g-2 align-items-end">
            <div class="col-5 col-md-3">
                <label class="form-label small text-white-50">Kalkış</label>
                <input type="text" name="segments[${index}][from_iata]" class="form-control iata-input bg-transparent border-0 border-bottom border-secondary text-white"
                       placeholder="IST" maxlength="3" value="${fromVal}" required
                       oninput="this.value=this.value.toUpperCase()">
            </div>
            <div class="col-2 text-center pt-3">
                <i class="fas fa-arrow-right text-danger" style="font-size:1.2rem;"></i>
            </div>
            <div class="col-5 col-md-3">
                <label class="form-label small text-white-50">Varış</label>
                <input type="text" name="segments[${index}][to_iata]" class="form-control iata-input bg-transparent border-0 border-bottom border-secondary text-white"
                       placeholder="CDG" maxlength="3" value="${toVal}" required
                       oninput="this.value=this.value.toUpperCase()">
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
            ${silinebilir ? `<div class="col-12 col-md-2 text-end"><button type="button" class="btn btn-outline-danger btn-sm" onclick="segmentSil(${index})"><i class="fas fa-times"></i></button></div>` : ''}
        </div>
    </div>`;
}

function segmentleriYenile(tip) {
    const container = document.getElementById('segments');
    const addBtn = document.getElementById('add-segment-btn');
    segmentCount = 0;
    container.innerHTML = '';

    if (tip === 'one_way') {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, '', '', '', '', 'Gidiş');
        addBtn.style.display = 'none';
    } else if (tip === 'round_trip') {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, '', '', '', '', 'Gidiş');
        container.innerHTML += segmentHtmlOlustur(segmentCount++, '', '', '', '', 'Dönüş');
        addBtn.style.display = 'none';
    } else {
        container.innerHTML = segmentHtmlOlustur(segmentCount++, '', '', '', '', '1. Uçuş');
        container.innerHTML += segmentHtmlOlustur(segmentCount++, '', '', '', '', '2. Uçuş', true);
        addBtn.style.display = 'inline-block';
    }
}

function segmentEkle() {
    const container = document.getElementById('segments');
    const div = document.createElement('div');
    div.innerHTML = segmentHtmlOlustur(segmentCount, '', '', '', '', (segmentCount + 1) + '. Uçuş', true);
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

// Sayfa yüklenince
tripTipiDegisti();
</script>
</body>
</html>

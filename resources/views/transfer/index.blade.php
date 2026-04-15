<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Transfer Merkezi - GrupTalepleri</title>
    @if(in_array($roleContext, ['admin', 'superadmin'], true))
        @include('admin.partials.theme-styles')
    @else
        @include('acente.partials.theme-styles')
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .gt-transfer-page .gt-transfer-hero {
            border-radius: 18px;
            border: 1px solid rgba(15, 23, 42, .08);
            background: linear-gradient(130deg, #0f1f48 0%, #1a3b7a 100%);
            color: #f8fafc;
            padding: 1.4rem 1.5rem;
            box-shadow: 0 18px 32px rgba(15, 23, 42, .16);
        }
        .gt-transfer-page .gt-transfer-hero .kicker {
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: rgba(226, 232, 240, .78);
            font-weight: 700;
        }
        .gt-transfer-page .gt-transfer-hero h1 {
            font-size: clamp(1.45rem, 2.2vw, 2rem);
            margin: .35rem 0 .45rem;
            font-weight: 800;
        }
        .gt-transfer-page .gt-transfer-hero p {
            margin: 0;
            color: rgba(241, 245, 249, .86);
            max-width: 780px;
        }
        .gt-transfer-page .gt-transfer-card {
            border-radius: 14px;
            border: 1px solid rgba(15, 23, 42, .08);
            box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
        }
        .gt-transfer-page .gt-transfer-label {
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
            font-weight: 700;
            margin-bottom: .35rem;
        }
        .gt-transfer-page .gt-transfer-muted {
            font-size: .84rem;
            color: #64748b;
        }
        .gt-transfer-page .gt-transfer-inline-stat {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: .75rem;
            padding: .55rem .65rem;
            font-size: .84rem;
            background: #f8fafc;
        }
        .gt-transfer-page .gt-transfer-result-card {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: .95rem;
            background: #fff;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: box-shadow .2s, transform .2s;
        }
        .gt-transfer-page .gt-transfer-result-card:hover {
            box-shadow: 0 8px 28px rgba(15, 23, 42, .13);
            transform: translateY(-2px);
        }
        .gt-transfer-page .gt-tr-img-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            overflow: hidden;
            background: #e2e8f0;
        }
        .gt-transfer-page .gt-tr-img-wrap img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .35s;
        }
        .gt-transfer-page .gt-transfer-result-card:hover .gt-tr-img-wrap img { transform: scale(1.04); }
        .gt-transfer-page .gt-tr-img-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: #94a3b8;
        }
        .gt-transfer-page .gt-tr-body { padding: .85rem; flex: 1; display: flex; flex-direction: column; gap: .45rem; }
        .gt-transfer-page .gt-tr-title { font-size: 1rem; font-weight: 700; color: #0f172a; }
        .gt-transfer-page .gt-tr-sub   { font-size: .8rem; color: #64748b; }
        .gt-transfer-page .gt-tr-amenities { display: flex; flex-wrap: wrap; gap: .3rem; }
        .gt-transfer-page .gt-tr-amenity {
            display: inline-flex; align-items: center; gap: .2rem;
            font-size: .72rem; color: #475569;
            background: #f1f5f9; border-radius: 4px; padding: .18rem .45rem;
        }
        .gt-transfer-page .gt-tr-footer {
            border-top: 1px solid rgba(15, 23, 42, .07);
            padding: .7rem .85rem;
            display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        }
        .gt-transfer-page .gt-transfer-price {
            font-size: 1.18rem;
            font-weight: 800;
            color: #0f172a;
        }
        .gt-transfer-page .gt-tr-suggested {
            font-size: .75rem; color: #64748b;
        }
        .gt-transfer-page .gt-transfer-empty {
            border: 1px dashed rgba(15, 23, 42, .18);
            border-radius: .9rem;
            padding: 1rem;
            text-align: center;
            color: #475569;
            background: #f8fafc;
        }
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-card,
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-result-card,
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-inline-stat {
            border-color: #2d4371;
            background: #0f1d36;
            color: #e5e7eb;
        }
        html[data-theme="dark"] .gt-transfer-page .gt-tr-body { background: #0f1d36; }
        html[data-theme="dark"] .gt-transfer-page .gt-tr-title { color: #f1f5f9; }
        html[data-theme="dark"] .gt-transfer-page .gt-tr-amenity { background: #1e3a5f; color: #93c5fd; }
        html[data-theme="dark"] .gt-transfer-page .gt-tr-footer { border-color: #2d4371; background: #0f1d36; }
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-label,
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-muted,
        html[data-theme="dark"] .gt-transfer-page .gt-tr-sub { color: #9fb2d9; }
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-price { color: #f8fafc; }
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-empty {
            border-color: #2d4371;
            background: #0f1d36;
            color: #c3d4f5;
        }
    </style>
</head>
<body class="theme-scope">
<x-dynamic-component :component="$navbarComponent" active="transfer" />

<div class="container py-4 gt-transfer-page">
    <div class="gt-transfer-hero mb-3">
        <div class="kicker"><i class="fas fa-shuttle-van me-1"></i>Transfer Marketplace</div>
        <h1>Havalimanı Transfer — Anlık Fiyat & Rezervasyon</h1>
        <p>
            Havalimanı, bölge, tarih ve yolcu bilgilerini girin; tedarikçi bazlı anlık fiyatları karşılaştırın,
            tek tıkla rezervasyona geçin.
        </p>
    </div>

    @if(session('success'))
        <div class="alert alert-success border shadow-sm">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-warning border shadow-sm">{{ session('error') }}</div>
    @endif

    @if(! $transferEnabled)
        <div class="alert alert-warning border shadow-sm">
            Transfer modülü devre dışı. Lütfen sistem ayarlarını kontrol edin.
        </div>
    @else
        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card gt-transfer-card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-shuttle-van me-2 text-primary"></i>Transfer Ara
                            </h5>
                            <div class="d-flex gap-2">
                                @if($roleContext === 'superadmin')
                                    <a href="{{ route('superadmin.transfer.ops.index') }}" class="btn btn-outline-primary btn-sm">Transfer Operasyon</a>
                                @elseif($roleContext === 'acente' && ($acenteSupplierState['show_panel_link'] ?? false))
                                    <a href="{{ $acenteSupplierState['panel_url'] }}" class="btn btn-outline-primary btn-sm">Tedarikci Paneli</a>
                                @elseif($roleContext === 'acente' && ($acenteSupplierState['show_terms_link'] ?? false))
                                    <a href="{{ $acenteSupplierState['terms_url'] }}" class="btn btn-warning btn-sm">Sozlesme Onayi</a>
                                @endif
                                <a href="{{ $dashboardRoute }}" class="btn btn-outline-secondary btn-sm">Panele Don</a>
                            </div>
                        </div>

                        <form id="gtTransferSearchForm" class="row g-3">
                            <div class="col-12">
                                <label class="gt-transfer-label" for="directionSelect">Yon</label>
                                <select id="directionSelect" class="form-select" name="direction" required>
                                    <option value="FROM_AIRPORT">Havalimanından Şehre</option>
                                    <option value="TO_AIRPORT">Şehirden Havalimanına</option>
                                    <option value="BOTH">Gidiş Dönüş</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="gt-transfer-label" for="airportSelect">Havalimani</label>
                                <select id="airportSelect" class="form-select" name="airport_id" required>
                                    <option value="">Yukleniyor...</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="gt-transfer-label" for="zoneSelect">Nereye (Bolge)</label>
                                <select id="zoneSelect" class="form-select" name="zone_id" disabled required>
                                    <option value="">Önce havalimanı seçin</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="gt-transfer-label" for="pickupDateInput">Alis tarihi</label>
                                <input id="pickupDateInput" type="date" class="form-control" name="pickup_date" required>
                            </div>
                            <div class="col-6">
                                <label class="gt-transfer-label" for="pickupTimeInput">Alis saati</label>
                                <input id="pickupTimeInput" type="time" class="form-control" name="pickup_time" value="10:00" required>
                            </div>

                            <div class="col-6">
                                <label class="gt-transfer-label" for="paxInput">PAX</label>
                                <input id="paxInput" type="number" min="1" max="50" class="form-control" name="pax" value="2" required>
                            </div>
                            <div class="col-6">
                                <label class="gt-transfer-label" for="currencySelect">Para birimi</label>
                                <select id="currencySelect" class="form-select" name="currency" required>
                                    <option value="TRY" selected>TRY</option>
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>

                            <div id="returnFields" class="row g-3 d-none">
                                <div class="col-6">
                                    <label class="gt-transfer-label" for="returnDateInput">Donus tarihi</label>
                                    <input id="returnDateInput" type="date" class="form-control" name="return_date">
                                </div>
                                <div class="col-6">
                                    <label class="gt-transfer-label" for="returnTimeInput">Donus saati</label>
                                    <input id="returnTimeInput" type="time" class="form-control" name="return_time" value="18:00">
                                </div>
                            </div>

                            <div class="col-12">
                                <button id="searchBtn" class="btn btn-primary w-100" type="submit">
                                    <i class="fas fa-magnifying-glass me-1"></i>Transfer ara
                                </button>
                            </div>
                        </form>

                        <div class="gt-transfer-muted mt-3">
                            Aktif provider: <strong>{{ strtoupper($provider) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card gt-transfer-card">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-list-check me-2 text-success"></i>Transfer Sonuclari
                            </h5>
                            <span id="resultCountBadge" class="badge bg-secondary">0 sonuc</span>
                        </div>

                        <div id="searchMeta" class="d-flex flex-wrap gap-2 mb-3"></div>
                        <div id="transferError" class="alert alert-danger d-none mb-3"></div>
                        <div id="transferLoading" class="d-none text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="small text-muted mt-2">Sonuçlar yükleniyor...</div>
                        </div>
                        <div id="transferEmpty" class="gt-transfer-empty">
                            Arama yapmak için soldaki alanları doldurun.
                        </div>
                        <div id="transferResults" class="row g-3 d-none" style="margin-top:.5rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@if(in_array($roleContext, ['admin', 'superadmin'], true))
    @include('admin.partials.theme-script')
@else
    @include('acente.partials.theme-script')
    @include('acente.partials.leisure-footer')
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const form = document.getElementById('gtTransferSearchForm');
    if (!form) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const endpoints = {
        airports: @json($airportsEndpoint),
        zones: @json($zonesEndpoint),
        search: @json($searchEndpoint),
    };
    const provider = @json($provider);

    const directionSelect = document.getElementById('directionSelect');
    const airportSelect = document.getElementById('airportSelect');
    const zoneSelect = document.getElementById('zoneSelect');
    const returnFields = document.getElementById('returnFields');
    const returnDateInput = document.getElementById('returnDateInput');
    const returnTimeInput = document.getElementById('returnTimeInput');
    const pickupDateInput = document.getElementById('pickupDateInput');
    const searchBtn = document.getElementById('searchBtn');
    const resultCountBadge = document.getElementById('resultCountBadge');
    const searchMeta = document.getElementById('searchMeta');
    const transferError = document.getElementById('transferError');
    const transferLoading = document.getElementById('transferLoading');
    const transferEmpty = document.getElementById('transferEmpty');
    const transferResults = document.getElementById('transferResults');

    const now = new Date();
    const today = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 10);
    pickupDateInput.value = pickupDateInput.value || today;
    pickupDateInput.min = today;
    if (returnDateInput) {
        returnDateInput.min = today;
    }

    const setLoading = (loading) => {
        searchBtn.disabled = loading;
        transferLoading.classList.toggle('d-none', !loading);
    };

    const showError = (message) => {
        transferError.textContent = message;
        transferError.classList.remove('d-none');
    };

    const clearError = () => {
        transferError.textContent = '';
        transferError.classList.add('d-none');
    };

    const resetResultArea = () => {
        transferResults.innerHTML = '';
        transferResults.classList.add('d-none');
        transferEmpty.classList.remove('d-none');
        resultCountBadge.textContent = '0 sonuc';
        searchMeta.innerHTML = '';
    };

    const setResultMeta = (metaItems) => {
        searchMeta.innerHTML = '';
        metaItems.forEach((item) => {
            const pill = document.createElement('div');
            pill.className = 'gt-transfer-inline-stat';
            pill.textContent = item;
            searchMeta.appendChild(pill);
        });
    };

    const loadAirports = async () => {
        airportSelect.innerHTML = '<option value="">Yukleniyor...</option>';
        try {
            const response = await fetch(endpoints.airports, { headers: { 'Accept': 'application/json' } });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Havalimani listesi alinamadi.');
            }

            const airports = Array.isArray(payload.data) ? payload.data : [];
            airportSelect.innerHTML = '<option value="">Havalimani secin</option>';
            airports.forEach((airport) => {
                const option = document.createElement('option');
                option.value = airport.id;
                option.textContent = `${airport.code} - ${airport.name} (${airport.city})`;
                airportSelect.appendChild(option);
            });
        } catch (error) {
            airportSelect.innerHTML = '<option value="">Havalimanı yüklenemedi</option>';
            showError(error.message || 'Havalimanı listesi alınamadı.');
        }
    };

    const loadZones = async (airportId) => {
        zoneSelect.disabled = true;
        zoneSelect.innerHTML = '<option value="">Yukleniyor...</option>';
        if (!airportId) {
            zoneSelect.innerHTML = '<option value="">Once havalimani secin</option>';
            return;
        }

        try {
            const query = new URLSearchParams({ airport_id: airportId });
            const response = await fetch(`${endpoints.zones}?${query.toString()}`, { headers: { 'Accept': 'application/json' } });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Bolge listesi alinamadi.');
            }

            const zones = Array.isArray(payload.data) ? payload.data : [];
            zoneSelect.innerHTML = '<option value="">Bölge seçin</option>';
            zones.forEach((zone) => {
                const option = document.createElement('option');
                option.value = zone.id;
                option.textContent = `${zone.name}${zone.city ? ` (${zone.city})` : ''}`;
                zoneSelect.appendChild(option);
            });
            zoneSelect.disabled = false;
        } catch (error) {
            zoneSelect.innerHTML = '<option value="">Bölge yüklenemedi</option>';
            showError(error.message || 'Bölge listesi alınamadı.');
        }
    };

    const syncReturnVisibility = () => {
        const isRoundTrip = directionSelect.value === 'BOTH';
        returnFields.classList.toggle('d-none', !isRoundTrip);
        if (returnDateInput) returnDateInput.required = isRoundTrip;
        if (returnTimeInput) returnTimeInput.required = isRoundTrip;
    };

    const esc = (str) => String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    const renderResults = (options, noResultReason, searchState) => {
        transferResults.innerHTML = '';
        transferResults.classList.add('d-none');
        transferEmpty.classList.remove('d-none');

        if (!Array.isArray(options) || options.length === 0) {
            transferEmpty.textContent = noResultReason || 'Bu kriterlerde aktif transfer seçeneği bulunamadı.';
            resultCountBadge.textContent = '0 sonuc';
            return;
        }

        resultCountBadge.textContent = `${options.length} sonuc`;
        transferEmpty.classList.add('d-none');
        transferResults.classList.remove('d-none');

        options.forEach((option) => {
            const col = document.createElement('div');
            col.className = 'col-12 col-md-6 col-xl-4';

            const photos = Array.isArray(option.vehicle_photos) ? option.vehicle_photos : [];
            const amenities = Array.isArray(option.vehicle_amenities) ? option.vehicle_amenities : [];
            const priceText = (option.total_price !== null && option.total_price !== undefined)
                ? `${Number(option.total_price).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2})} ${option.currency || searchState.currency}`
                : `Teklif bazlı`;
            const suggestedText = option.vehicle_suggested_retail
                ? `Önerilen sat.: ${Number(option.vehicle_suggested_retail).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2})} ${option.currency || searchState.currency}`
                : '';
            const durationText = option.duration_minutes ? `${option.duration_minutes} dk` : '';
            const openInNew = provider === 'atp';
            const ctaAttrs = openInNew ? 'target="_blank" rel="noopener noreferrer"' : '';

            // Fotoğraf alanı
            let imgHtml = '';
            if (photos.length > 0) {
                const imgId = `gttr_img_${Math.random().toString(36).slice(2,8)}`;
                imgHtml = `
                    <div class="gt-tr-img-wrap">
                        <img id="${imgId}" src="${esc(photos[0])}" alt="${esc(option.vehicle_type)}" loading="lazy">
                    </div>`;
                if (photos.length > 1) {
                    // slideshow çok basit
                    setTimeout(() => {
                        const el = document.getElementById(imgId);
                        if (!el) return;
                        let idx = 0;
                        el.parentElement.addEventListener('mouseenter', () => {
                            idx = (idx + 1) % photos.length;
                            el.src = photos[idx];
                        });
                    }, 50);
                }
            } else {
                imgHtml = `<div class="gt-tr-img-wrap"><div class="gt-tr-img-placeholder"><i class="fas fa-shuttle-van"></i></div></div>`;
            }

            // Donanım ikonları
            const amenityMap = {
                wifi:'fas fa-wifi', ac:'fas fa-snowflake', refreshments:'fas fa-bottle-water',
                child_seat:'fas fa-baby', usb:'fas fa-plug', leather:'fas fa-couch',
                panoramic:'fas fa-panorama', disabled_access:'fas fa-wheelchair',
                luggage_assist:'fas fa-suitcase-rolling', tv:'fas fa-tv'
            };
            const amenityLabelMap = {
                wifi:'WiFi', ac:'Klima', refreshments:'İkram', child_seat:'Çocuk Kol.',
                usb:'USB Şarj', leather:'Deri Koltuk', panoramic:'Panoramik',
                disabled_access:'Engelli', luggage_assist:'Bagaj Yard.', tv:'TV'
            };
            const amenityHtml = amenities.slice(0, 5).map(a =>
                `<span class="gt-tr-amenity"><i class="${esc(amenityMap[a] || 'fas fa-check')}"></i>${esc(amenityLabelMap[a] || a)}</span>`
            ).join('');

            // Kapasite satırı
            const cap = option.vehicle_max_passengers ? `<span class="gt-tr-amenity"><i class="fas fa-users"></i>${esc(option.vehicle_max_passengers)} kişi</span>` : '';
            const lug = option.vehicle_luggage_capacity ? `<span class="gt-tr-amenity"><i class="fas fa-suitcase"></i>${esc(option.vehicle_luggage_capacity)} valiz</span>` : '';

            col.innerHTML = `
                <div class="gt-transfer-result-card">
                    ${imgHtml}
                    <div class="gt-tr-body">
                        <div class="gt-tr-title">${esc(option.vehicle_type || 'Transfer Aracı')}</div>
                        <div class="gt-tr-sub"><i class="fas fa-building me-1"></i>${esc(option.supplier_name || '')}</div>
                        ${option.vehicle_description ? `<div class="gt-tr-sub">${esc(option.vehicle_description)}</div>` : ''}
                        ${(cap || lug || amenityHtml) ? `<div class="gt-tr-amenities">${cap}${lug}${amenityHtml}</div>` : ''}
                        ${durationText ? `<div class="gt-tr-sub"><i class="fas fa-clock me-1"></i>${esc(durationText)}</div>` : ''}
                        <div class="gt-tr-sub gt-transfer-muted" style="font-size:.76rem;">${esc(option.cancellation_policy || '')}</div>
                    </div>
                    <div class="gt-tr-footer">
                        <div>
                            <div class="gt-transfer-price">${priceText}</div>
                            ${suggestedText ? `<div class="gt-tr-suggested">${esc(suggestedText)}</div>` : ''}
                        </div>
                        <a class="btn btn-primary btn-sm" href="${esc(option.booking_url)}" ${ctaAttrs}>
                            Rezervasyon <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            `;
            transferResults.appendChild(col);
        });
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();
        setLoading(true);

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(endpoints.search, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();
            if (!response.ok || !result.ok) {
                const message = result.message || 'Transfer arama başarısız oldu.';
                throw new Error(message);
            }

            const options = result?.data?.options || [];
            const noResultReason = result?.data?.no_results_reason || null;

            const airportLabel = airportSelect.options[airportSelect.selectedIndex]?.text || '-';
            const zoneLabel = zoneSelect.options[zoneSelect.selectedIndex]?.text || '-';
            setResultMeta([
                `Yon: ${directionSelect.options[directionSelect.selectedIndex]?.text || '-'}`,
                `Havalimani: ${airportLabel}`,
                `Bolge: ${zoneLabel}`,
                `PAX: ${payload.pax || '-'}`,
            ]);

            renderResults(options, noResultReason, payload);
        } catch (error) {
            resetResultArea();
            showError(error.message || 'Transfer arama yapılırken bir hata oluştu.');
        } finally {
            setLoading(false);
        }
    });

    directionSelect.addEventListener('change', syncReturnVisibility);
    airportSelect.addEventListener('change', (event) => {
        clearError();
        loadZones(event.target.value);
    });

    syncReturnVisibility();
    loadAirports();
    resetResultArea();
})();
</script>
</body>
</html>

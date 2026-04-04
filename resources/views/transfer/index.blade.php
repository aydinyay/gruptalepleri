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
            padding: .95rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }
        .gt-transfer-page .gt-transfer-price {
            font-size: 1.18rem;
            font-weight: 800;
            color: #0f172a;
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
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-label,
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-muted {
            color: #9fb2d9;
        }
        html[data-theme="dark"] .gt-transfer-page .gt-transfer-price {
            color: #f8fafc;
        }
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
            Transfer modulu devre disi. Lutfen sistem ayarlarini kontrol edin.
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
                                    <option value="FROM_AIRPORT">Havaalanindan sehire</option>
                                    <option value="TO_AIRPORT">Sehirden havaalanina</option>
                                    <option value="BOTH">Gidis donus</option>
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
                                    <option value="">Once havalimani secin</option>
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
                            <div class="small text-muted mt-2">Sonuclar yukleniyor...</div>
                        </div>
                        <div id="transferEmpty" class="gt-transfer-empty">
                            Arama yapmak icin soldaki alanlari doldurun.
                        </div>
                        <div id="transferResults" class="row g-3 d-none"></div>
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
            airportSelect.innerHTML = '<option value="">Havalimani yuklenemedi</option>';
            showError(error.message || 'Havalimani listesi alinamadi.');
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
            zoneSelect.innerHTML = '<option value="">Bolge secin</option>';
            zones.forEach((zone) => {
                const option = document.createElement('option');
                option.value = zone.id;
                option.textContent = `${zone.name}${zone.city ? ` (${zone.city})` : ''}`;
                zoneSelect.appendChild(option);
            });
            zoneSelect.disabled = false;
        } catch (error) {
            zoneSelect.innerHTML = '<option value="">Bolge yuklenemedi</option>';
            showError(error.message || 'Bolge listesi alinamadi.');
        }
    };

    const syncReturnVisibility = () => {
        const isRoundTrip = directionSelect.value === 'BOTH';
        returnFields.classList.toggle('d-none', !isRoundTrip);
        if (returnDateInput) returnDateInput.required = isRoundTrip;
        if (returnTimeInput) returnTimeInput.required = isRoundTrip;
    };

    const renderResults = (options, noResultReason, searchState) => {
        transferResults.innerHTML = '';
        transferResults.classList.add('d-none');
        transferEmpty.classList.remove('d-none');

        if (!Array.isArray(options) || options.length === 0) {
            transferEmpty.textContent = noResultReason || 'Bu kriterlerde aktif transfer secenegi bulunamadi.';
            resultCountBadge.textContent = '0 sonuc';
            return;
        }

        resultCountBadge.textContent = `${options.length} sonuc`;
        transferEmpty.classList.add('d-none');
        transferResults.classList.remove('d-none');

        options.forEach((option) => {
            const col = document.createElement('div');
            col.className = 'col-12 col-xl-6';

            const durationText = option.duration_minutes
                ? `${option.duration_minutes} dk`
                : 'Sure bilgisi tedarikciye gore degisir';
            const ratingText = option.supplier_rating
                ? `${option.supplier_rating} / 5`
                : 'Puan bilgisi yok';
            const priceText = (option.total_price !== null && option.total_price !== undefined)
                ? `${Number(option.total_price).toFixed(2)} ${option.currency || searchState.currency}`
                : `Teklif bazli (${option.currency || searchState.currency})`;

            const openInNew = provider === 'atp';
            const ctaAttrs = openInNew ? 'target="_blank" rel="noopener noreferrer"' : '';
            const ctaLabel = openInNew ? 'Dis rezervasyona git' : 'Rezervasyona git';

            col.innerHTML = `
                <div class="gt-transfer-result-card">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="fw-bold">${option.vehicle_type || 'Transfer Araci'}</div>
                            <div class="small text-muted">${option.supplier_name || '-'}</div>
                        </div>
                        <span class="badge text-bg-light">${ratingText}</span>
                    </div>
                    <div class="gt-transfer-muted">${option.cancellation_policy || 'Iptal politikasi supplier kurallarina gore degisir.'}</div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="small text-muted"><i class="fas fa-clock me-1"></i>${durationText}</span>
                        <span class="gt-transfer-price">${priceText}</span>
                    </div>
                    <a class="btn btn-primary btn-sm mt-auto" href="${option.booking_url}" ${ctaAttrs}>
                        <i class="fas fa-arrow-right me-1"></i>${ctaLabel}
                    </a>
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
                const message = result.message || 'Transfer arama basarisiz oldu.';
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
            showError(error.message || 'Transfer arama yapilirken bir hata olustu.');
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

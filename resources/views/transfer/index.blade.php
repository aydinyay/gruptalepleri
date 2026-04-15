<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Transfer Merkezi - GrupTalepleri</title>

    @if(isset($roleContext) && in_array($roleContext, ['admin', 'superadmin'], true))
        @include('admin.partials.theme-styles')
    @else
        @include('acente.partials.theme-styles')
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; }
        .gt-transfer-hero { border-radius: 18px; background: linear-gradient(130deg, #0f1f48 0%, #1a3b7a 100%); color: #fff; padding: 1.5rem 2rem; box-shadow: 0 18px 32px rgba(15, 23, 42, .16); }

        .gt-input-group { position: relative; margin-bottom: 1rem; }
        .gt-input-group i { position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 1.1rem; z-index: 5; pointer-events: none; }
        .form-control, .form-select, .pax-display-box { font-weight: 700 !important; font-size: 0.95rem; padding: 0.75rem 1rem 0.75rem 3.2rem !important; border: 1px solid #cbd5e1; border-radius: 10px; color: #0f172a; background-color: #fff; cursor: pointer; }
        .form-control:focus, .form-select:focus { border-color: #0071eb; box-shadow: 0 0 0 4px rgba(0, 113, 235, 0.1); outline: none; }
        .gt-label { font-size: 0.76rem; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 0.4rem; display: block; }

        .direction-tabs { display: flex; gap: 8px; margin-bottom: 1.2rem; }
        .dir-tab { flex: 1; padding: 12px; text-align: center; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; font-weight: 700; cursor: pointer; font-size: 0.85rem; transition: 0.2s; }
        .dir-tab.active { background: #0071eb; color: #fff; border-color: #0071eb; }

        .pax-dropdown-wrap { position: relative; }
        .pax-popover { position: absolute; top: 105%; left: 0; width: 100%; background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); padding: 1.5rem; z-index: 1000; display: none; border: 1px solid #e2e8f0; }
        .pax-popover.show { display: block; }
        .stepper-ui { display: flex; align-items: center; justify-content: space-between; }
        .btn-step { width: 38px; height: 38px; border-radius: 50%; border: 1.5px solid #0071eb; background: #fff; color: #0071eb; font-weight: bold; cursor: pointer; }

        /* Sonuç Kartları */
        .summary-bar { background: #fff; border-radius: 12px; padding: 1rem 1.5rem; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.8rem; font-size: 0.9rem; }
        .res-card { border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; overflow: hidden; margin-bottom: 1.25rem; transition: 0.2s; }
        .res-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
        .res-price { font-weight: 800; font-size: 1.6rem; color: #0071eb; }
        .amenity-pill { background: #f1f5f9; color: #475569; font-size: 0.75rem; font-weight: 600; padding: 5px 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; }
        .btn-select { background: #0071eb; color: #fff; font-weight: 700; border-radius: 50px; padding: 0.6rem 2.5rem; border: none; text-decoration: none; }
        .btn-search { background: #0071eb; color: #fff; font-weight: 800; border-radius: 10px; padding: 0.9rem; width: 100%; border: none; font-size: 1.1rem; }
    </style>
</head>
<body class="theme-scope">

<x-dynamic-component :component="$navbarComponent ?? 'navbar-acente'" active="transfer" />

<div class="container py-4">
    <div class="gt-transfer-hero mb-4">
        <h1 class="h3 fw-800 mb-1">Havalimanı Transfer Rezervasyonu</h1>
        <p class="mb-0 opacity-75 small">Anlık fiyatları karşılaştırın, konforlu seyahatinizi hemen planlayın.</p>
    </div>

    @if(!($transferEnabled ?? true))
        <div class="alert alert-warning border-0 shadow-sm">Transfer modülü şu an kapalıdır.</div>
    @else
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm" style="border-radius:14px;">
                    <div class="card-body p-4">
                        <form id="gtTransferSearchForm">
                            <label class="gt-label">Yolculuk Tipi</label>
                            <div class="direction-tabs">
                                <div class="dir-tab active" data-value="FROM_AIRPORT">Tek Yön</div>
                                <div class="dir-tab" data-value="BOTH">Gidiş - Dönüş</div>
                            </div>
                            <input type="hidden" name="direction" id="directionInput" value="FROM_AIRPORT">

                            <label class="gt-label">Havalimanı</label>
                            <div class="gt-input-group"><i class="fas fa-plane-arrival"></i>
                                <select class="form-select" name="airport_id" id="airportSelect" required>
                                    <option value="">Yükleniyor...</option>
                                </select>
                            </div>

                            <label class="gt-label">Varış Noktası</label>
                            <div class="gt-input-group"><i class="fas fa-location-dot"></i>
                                <select class="form-select" name="zone_id" id="zoneSelect" disabled required>
                                    <option value="">Önce havalimanı seçin</option>
                                </select>
                            </div>

                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="gt-label">Alış Tarihi</label>
                                    <div class="gt-input-group">
                                        <i class="fas fa-calendar-alt"></i>
                                        <input type="text" id="pickupDateInput" name="pickup_date" class="form-control" required placeholder="Seçin">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="gt-label">Saat</label>
                                    <div class="gt-input-group">
                                        <i class="fas fa-clock"></i>
                                        <input type="time" name="pickup_time" class="form-control" value="10:00" required style="padding-left:3.2rem !important;">
                                    </div>
                                </div>
                            </div>

                            <div id="returnFields" class="d-none">
                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <label class="gt-label">Dönüş Tarihi</label>
                                        <div class="gt-input-group">
                                            <i class="fas fa-calendar-check"></i>
                                            <input type="text" id="returnDateInput" name="return_date" class="form-control" placeholder="Seçin">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="gt-label">Dönüş Saati</label>
                                        <input type="time" name="return_time" class="form-control" value="18:00" style="padding-left:1rem !important;">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <label class="gt-label">Yolcu Sayısı</label>
                                    <div class="pax-dropdown-wrap" id="paxWrap">
                                        <div class="gt-input-group">
                                            <i class="fas fa-user-group"></i>
                                            <div class="pax-display-box" id="paxDisplay">2 Yolcu</div>
                                        </div>
                                        <div class="pax-popover" id="paxPopover">
                                            <div class="fw-bold small mb-2">Yolcu Sayısı</div>
                                            <div class="stepper-ui">
                                                <button type="button" class="btn-step" id="stepMinus">-</button>
                                                <span class="fw-bold h5 mb-0" id="stepVal">2</span>
                                                <button type="button" class="btn-step" id="stepPlus">+</button>
                                            </div>
                                            <button type="button" class="btn btn-primary btn-sm w-100 mt-3" id="paxClose">Tamam</button>
                                        </div>
                                        <input type="hidden" name="pax" id="realPax" value="2">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="gt-label">Para Birimi</label>
                                    <select class="form-select" name="currency" style="padding-left:1rem !important;">
                                        <option value="TRY" selected>TRY</option><option value="EUR">EUR</option><option value="USD">USD</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn-search mt-4">Transferleri Listele</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div id="searchSummary" class="summary-bar d-none">
                    <i class="fas fa-info-circle text-primary"></i>
                    <span id="sumText" class="fw-bold text-dark"></span>
                </div>

                <div id="transferLoading" class="d-none text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 fw-bold">Size en uygun araçlar getiriliyor...</p>
                </div>

                <div id="transferResults" class="row g-3"></div>

                <div id="transferEmpty" class="text-center py-5 bg-white rounded-4 border">
                    <i class="fas fa-search fa-3x opacity-10 mb-3"></i>
                    <p class="text-muted">Arama kriterlerini belirleyip aramaya başlayın.</p>
                </div>
            </div>
        </div>
    @endif
</div>

@if(isset($roleContext) && in_array($roleContext, ['admin', 'superadmin'], true))
    @include('admin.partials.theme-script')
@else
    @include('acente.partials.theme-script')
    @include('acente.partials.leisure-footer')
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>

<script>
(() => {
    const form = document.getElementById('gtTransferSearchForm');
    const airportSelect = document.getElementById('airportSelect');
    const zoneSelect = document.getElementById('zoneSelect');
    const directionInput = document.getElementById('directionInput');
    const resultsArea = document.getElementById('transferResults');
    const loadingArea = document.getElementById('transferLoading');
    const emptyArea = document.getElementById('transferEmpty');
    const summaryArea = document.getElementById('searchSummary');

    const endpoints = {
        airports: @json($airportsEndpoint ?? ''),
        zones: @json($zonesEndpoint ?? ''),
        search: @json($searchEndpoint ?? '')
    };

    // 1. TAKVİM (Yarından Başlar, Y-m-d Gönderir)
    const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
    const fpConfig = { locale: "tr", minDate: tomorrow, altInput: true, altFormat: "d.m.Y", dateFormat: "Y-m-d", disableMobile: true };
    flatpickr("#pickupDateInput", fpConfig);
    flatpickr("#returnDateInput", fpConfig);

    // 2. YÖN KONTROLÜ
    document.querySelectorAll('.dir-tab').forEach(tab => {
        tab.onclick = () => {
            document.querySelectorAll('.dir-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const val = tab.getAttribute('data-value');
            directionInput.value = val;
            document.getElementById('returnFields').classList.toggle('d-none', val !== 'BOTH');
            document.getElementById('returnDateInput').required = (val === 'BOTH');
        };
    });

    // 3. KİŞİ SEÇİCİ (PAX)
    let paxCount = 2;
    const paxBox = document.getElementById('paxDisplay');
    const paxPop = document.getElementById('paxPopover');

    paxBox.onclick = (e) => { e.stopPropagation(); paxPop.classList.toggle('show'); };
    document.getElementById('stepPlus').onclick = (e) => { e.stopPropagation(); paxCount++; updatePaxUI(); };
    document.getElementById('stepMinus').onclick = (e) => { e.stopPropagation(); if(paxCount > 1) paxCount--; updatePaxUI(); };
    document.getElementById('paxClose').onclick = (e) => { e.stopPropagation(); paxPop.classList.remove('show'); };
    document.addEventListener('click', (e) => { if (!paxPop.contains(e.target) && e.target !== paxBox) paxPop.classList.remove('show'); });

    function updatePaxUI() {
        document.getElementById('stepVal').textContent = paxCount;
        document.getElementById('realPax').value = paxCount;
        paxBox.textContent = `${paxCount} Yolcu`;
        document.getElementById('stepMinus').disabled = (paxCount <= 1);
    }

    // 4. VERİ YÜKLEME
    const initData = async () => {
        if(!endpoints.airports) return;
        try {
            const res = await fetch(endpoints.airports, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
            airportSelect.innerHTML = '<option value="">Havalimanı Seçin</option>';
            res.data.forEach(a => airportSelect.innerHTML += `<option value="${a.id}">${a.code} - ${a.name}</option>`);
        } catch(e) {}
    };

    airportSelect.onchange = async (e) => {
        zoneSelect.disabled = true; zoneSelect.innerHTML = '<option>Yükleniyor...</option>';
        if(!e.target.value) return;
        try {
            const res = await fetch(`${endpoints.zones}?airport_id=${e.target.value}`, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
            zoneSelect.innerHTML = '<option value="">Bölge Seçin</option>';
            res.data.forEach(z => zoneSelect.innerHTML += `<option value="${z.id}">${z.name}</option>`);
            zoneSelect.disabled = false;
        } catch(e) {}
    };

    // 5. ARAMA VE RENDER
    form.onsubmit = async (e) => {
        e.preventDefault();
        loadingArea.classList.remove('d-none'); resultsArea.classList.add('d-none'); emptyArea.classList.add('d-none');

        const fd = new FormData(form);
        const payload = Object.fromEntries(fd.entries());

        if(!endpoints.search) return;

        try {
            const res = await fetch(endpoints.search, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            }).then(r => r.json());

            loadingArea.classList.add('d-none');
            resultsArea.innerHTML = '';

            if (res.ok && res.data.options.length > 0) {
                summaryArea.classList.remove('d-none');
                document.getElementById('sumText').textContent = `${airportSelect.options[airportSelect.selectedIndex].text.split('-')[0]} ➔ ${zoneSelect.options[zoneSelect.selectedIndex].text} | Sizin aramanız: ${paxCount} Yolcu`;

                res.data.options.forEach(opt => {
                    resultsArea.innerHTML += `
                        <div class="col-12">
                            <div class="res-card d-flex flex-column flex-md-row">
                                <div class="res-img-wrap d-none d-md-block" style="width:240px; background:#f8fafc;">
                                    <img src="${opt.vehicle_photos?.[0] || 'https://placehold.co/240x160/f8fafc/475569?text=Transfer'}" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                                <div class="p-4 flex-grow-1 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h3 class="fw-800 text-dark mb-1 h5">${opt.vehicle_type}</h3>
                                                <div class="text-muted" style="font-size:0.7rem; font-weight:600; text-transform:uppercase;"><i class="fas fa-building me-1"></i>${opt.supplier_name}</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="res-price">${Number(opt.total_price).toLocaleString('tr-TR')} ${opt.currency}</div>
                                                <div class="text-muted small">Her şey dahil</div>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-3 mb-3 text-dark" style="font-size:0.85rem; font-weight:600;">
                                            <span><i class="fas fa-user-friends text-primary opacity-75 me-1"></i> Araç Kapasitesi: Maks. ${opt.vehicle_max_passengers} Kişi</span>
                                            <span class="text-muted">|</span>
                                            <span><i class="fas fa-suitcase text-primary opacity-75 me-1"></i> Maks. ${opt.vehicle_luggage_capacity || 2} Bagaj</span>
                                        </div>

                                        <div class="d-flex flex-wrap gap-2 mb-4">
                                            <span class="amenity-pill"><i class="fas fa-snowflake"></i> Klima</span>
                                            <span class="amenity-pill"><i class="fas fa-wifi"></i> Ücretsiz WiFi</span>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-auto">
                                        <div class="text-success fw-bold small"><i class="fas fa-check-circle me-1"></i>Ücretsiz İptal & Anında Onay</div>
                                        <a href="${opt.booking_url}" class="btn-select px-5 fw-bold" style="border-radius:50px;">Seç <i class="fas fa-chevron-right ms-2"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });
                resultsArea.classList.remove('d-none');
            } else {
                emptyArea.classList.remove('d-none');
                emptyArea.innerHTML = `<p class="p-4 text-muted">${res.data.no_results_reason || 'Kriterlere uygun araç bulunamadı.'}</p>`;
            }
        } catch(e) {
            loadingArea.classList.add('d-none');
            emptyArea.classList.remove('d-none');
            emptyArea.innerHTML = '<p class="p-4 text-danger">Teknik bir hata oluştu. Lütfen tekrar deneyin.</p>';
        }
    };

    initData();
})();
</script>
</body>
</html>

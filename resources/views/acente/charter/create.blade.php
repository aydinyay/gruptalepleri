<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Air Charter Talebi - GrupTalepleri</title>
    @include('acente.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family:'Segoe UI',sans-serif; }
        .card-box { border-radius:12px; border:1px solid rgba(0,0,0,.08); }
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="charter" />

<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-helicopter me-2 text-danger"></i>Air Charter Talep Olustur</h4>
            <div class="text-muted small">Jet, helikopter ve charter ucak taleplerini bu ekrandan acabilirsin.</div>
        </div>
        <a href="{{ route('acente.dashboard') }}" class="btn btn-outline-secondary btn-sm">Panele Don</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-box shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('acente.charter.store') }}" id="charterRequestForm">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-lg-3">
                        <label class="form-label">Transport Type</label>
                        <select class="form-select" name="transport_type" id="transportType" required>
                            <option value="jet" @selected(old('transport_type') === 'jet')>Jet</option>
                            <option value="helicopter" @selected(old('transport_type') === 'helicopter')>Helikopter</option>
                            <option value="airliner" @selected(old('transport_type') === 'airliner')>Charter Ucak</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">From (IATA)</label>
                        <input name="from_iata" class="form-control text-uppercase" value="{{ old('from_iata') }}" required>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">To (IATA)</label>
                        <input name="to_iata" class="form-control text-uppercase" value="{{ old('to_iata') }}" required>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label class="form-label">Tarih</label>
                        <input type="date" name="departure_date" class="form-control" value="{{ old('departure_date') }}" required>
                    </div>
                    <div class="col-6 col-lg-1">
                        <label class="form-label">PAX</label>
                        <input type="number" min="1" max="400" name="pax" class="form-control" value="{{ old('pax') }}" required>
                    </div>
                    <div class="col-12 col-lg-2">
                        <label class="form-label">Esnek Tarih</label>
                        <select class="form-select" name="is_flexible">
                            <option value="0" @selected(old('is_flexible') == '0')>Hayir</option>
                            <option value="1" @selected(old('is_flexible') == '1')>Evet</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Grup Tipi</label>
                        <input name="group_type" class="form-control" value="{{ old('group_type') }}" placeholder="Kurumsal, spor kulubu, konferans vb.">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Talep Notu</label>
                        <textarea rows="3" name="notes" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div id="jetFields" class="transport-fields">
                    <h6 class="fw-bold mb-3">Jet Detaylari</h6>
                    <div class="row g-3">
                        <div class="col-6 col-lg-3">
                            <label class="form-label">Tahmini Ucus Saati</label>
                            <input type="number" name="jet[flight_hours_estimate]" class="form-control" value="{{ old('jet.flight_hours_estimate') }}">
                        </div>
                        <div class="col-6 col-lg-3">
                            <label class="form-label">Bagaj Adedi</label>
                            <input type="number" name="jet[luggage_count]" class="form-control" value="{{ old('jet.luggage_count') }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Kabin Tercihi</label>
                            <input name="jet[cabin_preference]" class="form-control" value="{{ old('jet.cabin_preference') }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Slot Notu</label>
                            <input name="jet[airport_slot_note]" class="form-control" value="{{ old('jet.airport_slot_note') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Specs JSON (opsiyonel)</label>
                            <textarea rows="2" name="jet[specs_json]" class="form-control">{{ old('jet.specs_json') }}</textarea>
                        </div>
                        <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[round_trip]" value="1" @checked(old('jet.round_trip'))><label class="form-check-label">Round Trip</label></div></div>
                        <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[pet_onboard]" value="1" @checked(old('jet.pet_onboard'))><label class="form-check-label">Pet</label></div></div>
                        <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[vip_catering]" value="1" @checked(old('jet.vip_catering'))><label class="form-check-label">VIP Catering</label></div></div>
                        <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[wifi_required]" value="1" @checked(old('jet.wifi_required'))><label class="form-check-label">Wi-Fi</label></div></div>
                        <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[special_luggage]" value="1" @checked(old('jet.special_luggage'))><label class="form-check-label">Ozel Bagaj</label></div></div>
                    </div>
                </div>

                <div id="helicopterFields" class="transport-fields d-none">
                    <h6 class="fw-bold mb-3">Helikopter Detaylari</h6>
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Pickup</label>
                            <input name="helicopter[pickup]" class="form-control" value="{{ old('helicopter.pickup') }}">
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Dropoff</label>
                            <input name="helicopter[dropoff]" class="form-control" value="{{ old('helicopter.dropoff') }}">
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Landing Details</label>
                            <textarea name="helicopter[landing_details]" rows="2" class="form-control">{{ old('helicopter.landing_details') }}</textarea>
                        </div>
                    </div>
                </div>

                <div id="airlinerFields" class="transport-fields d-none">
                    <h6 class="fw-bold mb-3">Charter Ucak Detaylari</h6>
                    <div class="row g-3">
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Tarih Esnek mi?</label>
                            <select name="airliner[date_flexible]" class="form-select">
                                <option value="0" @selected(old('airliner.date_flexible') == '0')>Hayir</option>
                                <option value="1" @selected(old('airliner.date_flexible') == '1')>Evet</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Grup Tipi</label>
                            <input name="airliner[group_type]" class="form-control" value="{{ old('airliner.group_type') }}">
                        </div>
                        <div class="col-12 col-lg-5">
                            <label class="form-label">Rota Notu</label>
                            <textarea name="airliner[route_notes]" rows="2" class="form-control">{{ old('airliner.route_notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold mb-0">Ekstralar</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addExtraBtn">
                        <i class="fas fa-plus me-1"></i>Ekstra Ekle
                    </button>
                </div>
                <div id="extrasWrap" class="d-flex flex-column gap-2"></div>

                <div class="mt-4">
                    <button class="btn btn-danger px-4">
                        <i class="fas fa-paper-plane me-1"></i>Talebi Kaydet ve AI On Teklif Uret
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="extraRowTemplate">
    <div class="row g-2 align-items-start extra-row">
        <div class="col-12 col-lg-4">
            <input class="form-control" name="extras[__INDEX__][title]" placeholder="Ekstra basligi">
        </div>
        <div class="col-12 col-lg-7">
            <input class="form-control" name="extras[__INDEX__][agency_note]" placeholder="Ekstra detayi">
        </div>
        <div class="col-12 col-lg-1">
            <button type="button" class="btn btn-outline-danger w-100 js-remove-extra">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</template>

@include('acente.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const transportType = document.getElementById('transportType');
        const sections = {
            jet: document.getElementById('jetFields'),
            helicopter: document.getElementById('helicopterFields'),
            airliner: document.getElementById('airlinerFields'),
        };

        function syncTransportFields() {
            Object.values(sections).forEach(el => el.classList.add('d-none'));
            const active = sections[transportType.value];
            if (active) active.classList.remove('d-none');
        }

        transportType.addEventListener('change', syncTransportFields);
        syncTransportFields();
    })();

    (function () {
        const wrap = document.getElementById('extrasWrap');
        const template = document.getElementById('extraRowTemplate');
        const addBtn = document.getElementById('addExtraBtn');
        let index = 0;

        function addRow() {
            const html = template.innerHTML.replaceAll('__INDEX__', String(index));
            index += 1;
            const container = document.createElement('div');
            container.innerHTML = html.trim();
            const row = container.firstElementChild;
            wrap.appendChild(row);
        }

        addBtn.addEventListener('click', addRow);
        wrap.addEventListener('click', (e) => {
            const btn = e.target.closest('.js-remove-extra');
            if (!btn) return;
            const row = btn.closest('.extra-row');
            if (row) row.remove();
        });
    })();
</script>
</body>
</html>

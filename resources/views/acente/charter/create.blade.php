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
        .charter-create-page .charter-section-title { font-size:.76rem; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; font-weight:700; margin-bottom:.75rem; }
        .charter-create-page .transport-cards { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.65rem; }
        .charter-create-page .transport-card { border:1px solid #d4d8e1; border-radius:12px; background:#fff; padding:.75rem .85rem; text-align:left; transition:.2s ease; }
        .charter-create-page .transport-card .title { font-size:.92rem; font-weight:700; }
        .charter-create-page .transport-card .desc { font-size:.75rem; color:#6b7280; }
        .charter-create-page .transport-card.active { border-color:#2563eb; background:#eff6ff; box-shadow:0 0 0 .16rem rgba(37,99,235,.12); }
        .charter-create-page .airport-wrap { position:relative; }
        .charter-create-page .airport-results { position:absolute; top:100%; left:0; right:0; z-index:40; display:none; border:1px solid #d4d8e1; border-radius:0 0 10px 10px; background:#fff; max-height:240px; overflow-y:auto; box-shadow:0 8px 18px rgba(0,0,0,.08); }
        .charter-create-page .airport-results.show { display:block; }
        .charter-create-page .airport-option { padding:.55rem .7rem; border-bottom:1px solid #eef1f5; cursor:pointer; }
        .charter-create-page .airport-option:last-child { border-bottom:0; }
        .charter-create-page .airport-option:hover, .charter-create-page .airport-option.focused { background:#f8fafc; }
        .charter-create-page .airport-option .line1 { font-size:.86rem; font-weight:600; color:#111827; }
        .charter-create-page .airport-option .line2 { font-size:.74rem; color:#6b7280; }
        .charter-create-page .field-micro { font-size:.74rem; color:#6b7280; margin-top:.25rem; }
        .charter-create-page .transport-fields { border:1px dashed #d4d8e1; border-radius:12px; padding:.95rem; background:#fcfdff; }
        .charter-create-page .advisory-sticky { position:sticky; top:1rem; }
        .charter-create-page .charter-advisory-card { border-radius:12px; overflow:hidden; }
        .charter-create-page .charter-advisory-card .card-body { max-height:calc(100vh - 150px); overflow-y:auto; overflow-x:hidden; }
        .charter-create-page .charter-advisory-message { font-size:.82rem; color:#1d4ed8; background:#eff6ff; border:1px solid #bfdbfe; border-radius:.65rem; padding:.5rem .65rem; }
        .charter-create-page .charter-advisory-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.5rem; }
        .charter-create-page .charter-advisory-item { border:1px solid #e5e7eb; border-radius:.6rem; padding:.5rem .55rem; background:#fff; }
        .charter-create-page .charter-advisory-label { font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; font-weight:700; }
        .charter-create-page .charter-advisory-value { margin-top:.2rem; font-size:.92rem; font-weight:600; color:#111827; }
        .charter-create-page .charter-advisory-list { margin:0; padding-left:1.1rem; font-size:.82rem; color:#374151; }
        .charter-create-page .charter-timeline-wrap { display:flex; flex-wrap:wrap; gap:.4rem; }
        .charter-create-page .charter-advisory-step { border:1px solid #e5e7eb; border-radius:.55rem; padding:.4rem .55rem; font-size:.8rem; color:#4b5563; background:#fff; }
        .charter-create-page .charter-advisory-step.active { border-color:#2563eb; background:#eff6ff; color:#1d4ed8; font-weight:700; }
        .charter-create-page .charter-advisory-disclaimer { font-size:.74rem; color:#6b7280; border-top:1px dashed #d1d5db; padding-top:.6rem; margin-top:.55rem; }
        .charter-create-page .advisory-summary { border:1px solid #d4d8e1; border-radius:10px; background:#fafcff; padding:.6rem .7rem; font-size:.81rem; }
        .charter-create-page .advisory-summary .value { font-weight:700; color:#111827; }
        .charter-create-page .submit-note { font-size:.82rem; color:#374151; margin-top:.45rem; }
        .charter-create-page #jetFields .form-check { display:flex; align-items:flex-start; gap:.45rem; margin-top:.35rem !important; }
        .charter-create-page #jetFields .form-check .form-check-input { float:none; margin-left:0; margin-top:.22rem; }
        .charter-create-page #jetFields .form-check .form-check-label { margin-left:0; line-height:1.25; }

        html[data-theme="dark"] .charter-create-page .card-box { border-color:#2d4371; }
        html[data-theme="dark"] .charter-create-page .charter-section-title { color:#9fb2d9; }
        html[data-theme="dark"] .charter-create-page .transport-card { background:#0f1e3d; border-color:#2d4371; color:#e5e7eb; }
        html[data-theme="dark"] .charter-create-page .transport-card .desc { color:#9fb2d9; }
        html[data-theme="dark"] .charter-create-page .transport-card.active { background:#162f61; border-color:#4f83ff; box-shadow:0 0 0 .16rem rgba(79,131,255,.2); }
        html[data-theme="dark"] .charter-create-page .field-micro,
        html[data-theme="dark"] .charter-create-page .submit-note { color:#aab8d8; }
        html[data-theme="dark"] .charter-create-page .transport-fields { background:#0f1d36; border-color:#2d4371; }
        html[data-theme="dark"] .charter-create-page .transport-fields h6,
        html[data-theme="dark"] .charter-create-page .transport-fields .form-label,
        html[data-theme="dark"] .charter-create-page .transport-fields .form-check-label,
        html[data-theme="dark"] .charter-create-page .transport-fields summary { color:#e5e7eb; }
        html[data-theme="dark"] .charter-create-page .charter-advisory-message { background:#10254d; border-color:#345fb4; color:#9bc3ff; }
        html[data-theme="dark"] .charter-create-page .charter-advisory-item,
        html[data-theme="dark"] .charter-create-page .charter-advisory-step { background:#0f1d36; border-color:#2d4371; }
        html[data-theme="dark"] .charter-create-page .charter-advisory-label { color:#9fb2d9; }
        html[data-theme="dark"] .charter-create-page .charter-advisory-value,
        html[data-theme="dark"] .charter-create-page .charter-advisory-list,
        html[data-theme="dark"] .charter-create-page .advisory-summary .value { color:#e5e7eb; }
        html[data-theme="dark"] .charter-create-page .charter-advisory-step.active { background:#1b3b74; border-color:#4f83ff; color:#d9e8ff; }
        html[data-theme="dark"] .charter-create-page .charter-advisory-disclaimer { color:#9fb2d9; border-top-color:#2d4371; }
        html[data-theme="dark"] .charter-create-page .advisory-summary { background:#0f1d36; border-color:#2d4371; color:#e5e7eb; }
        @media (max-width: 991.98px) {
            .charter-create-page .transport-cards { grid-template-columns:1fr; }
            .charter-create-page .charter-advisory-grid { grid-template-columns:1fr; }
            .charter-create-page .charter-advisory-card .card-body { max-height:none; overflow:visible; }
        }
    </style>
</head>
<body class="theme-scope charter-create-page">
<x-navbar-acente active="charter" />

@php($transportOld = old('transport_type', 'jet'))
@php($fromIataOld = old('from_iata', ''))
@php($toIataOld = old('to_iata', ''))
@php($oldExtras = old('extras', []))

<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-helicopter me-2 text-danger"></i>Air Charter Talep Oluştur</h4>
            <div class="text-muted small">Jet, helikopter ve charter uçak taleplerini bu ekrandan açabilirsiniz.</div>
        </div>
        <a href="{{ route('acente.dashboard') }}" class="btn btn-outline-secondary btn-sm">Panele Dön</a>
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

    <div class="mb-3 d-lg-none">
        @include('acente.charter.partials.advisory-panel', [
            'isCollapsible' => true,
            'collapseId' => 'charterAdvisoryMobile',
            'title' => 'Canlı Talep Rehberi (Mobil)',
        ])
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card card-box shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('acente.charter.store') }}" id="charterRequestForm">
                        @csrf

                        <div class="charter-section-title">1) Uçuş Türü</div>
                        <input type="hidden" name="transport_type" id="transportTypeInput" value="{{ $transportOld }}">
                        <div class="transport-cards mb-3" id="transportCards">
                            <button type="button" class="transport-card {{ $transportOld === 'jet' ? 'active' : '' }}" data-value="jet">
                                <div class="title">Private Jet</div>
                                <div class="desc">Küçük ve orta grup talepleri</div>
                            </button>
                            <button type="button" class="transport-card {{ $transportOld === 'helicopter' ? 'active' : '' }}" data-value="helicopter">
                                <div class="title">Helikopter</div>
                                <div class="desc">Noktadan noktaya hızlı erişim</div>
                            </button>
                            <button type="button" class="transport-card {{ $transportOld === 'airliner' ? 'active' : '' }}" data-value="airliner">
                                <div class="title">Charter Uçak</div>
                                <div class="desc">Büyük gövdeli grup uçuşları</div>
                            </button>
                        </div>

                        <div class="charter-section-title">2) Rota ve Temel Bilgiler</div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Kalkış Noktası <span class="text-danger">*</span></label>
                                <input type="hidden" name="from_iata" id="fromIataHidden" value="{{ $fromIataOld }}">
                                <div class="airport-wrap">
                                    <input id="fromIataSearch" class="form-control text-uppercase" value="{{ $fromIataOld }}" placeholder="IST - Istanbul Airport" autocomplete="off">
                                    <div class="airport-results" id="fromIataResults"></div>
                                </div>
                                <div class="field-micro">Şehir, havalimanı adı veya IATA kodu ile arayın</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Varış Noktası <span class="text-danger">*</span></label>
                                <input type="hidden" name="to_iata" id="toIataHidden" value="{{ $toIataOld }}">
                                <div class="airport-wrap">
                                    <input id="toIataSearch" class="form-control text-uppercase" value="{{ $toIataOld }}" placeholder="SAW - Sabiha Gokcen" autocomplete="off">
                                    <div class="airport-results" id="toIataResults"></div>
                                </div>
                                <div class="field-micro">Şehir, havalimanı adı veya IATA kodu ile arayın</div>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label">Tarih <span class="text-danger">*</span></label>
                                <input type="date" name="departure_date" class="form-control" value="{{ old('departure_date') }}" required>
                            </div>
                            <div class="col-6 col-md-4">
                                <label class="form-label">PAX <span class="text-danger">*</span></label>
                                <input type="number" min="1" max="400" name="pax" class="form-control" value="{{ old('pax') }}" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Esnek Tarih</label>
                                <select class="form-select" name="is_flexible">
                                    <option value="0" @selected(old('is_flexible') == '0')>Hayır</option>
                                    <option value="1" @selected(old('is_flexible') == '1')>Evet</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Grup Tipi</label>
                                <input name="group_type" class="form-control" value="{{ old('group_type') }}" placeholder="Kurumsal, spor kulübü, etkinlik vb.">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Talep Notu</label>
                                <textarea rows="3" name="notes" class="form-control" placeholder="Örnek: bagaj, kabin, transfer, zaman hassasiyeti">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div id="jetFields" class="transport-fields">
                            <h6 class="fw-bold mb-3">Jet Detayları (Gelişmiş Alan)</h6>
                            <div class="row g-3">
                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Tahmini Uçuş Süresi (Saat)</label>
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
                                    <details>
                                        <summary class="small text-muted">Teknik JSON (opsiyonel)</summary>
                                        <textarea rows="2" name="jet[specs_json]" class="form-control mt-2">{{ old('jet.specs_json') }}</textarea>
                                    </details>
                                </div>
                                <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[round_trip]" value="1" title="Dönüş operasyonu da planlanıyorsa seçin" data-bs-toggle="tooltip" @checked(old('jet.round_trip'))><label class="form-check-label">Round Trip</label></div></div>
                                <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[pet_onboard]" value="1" title="Evcil hayvan transferi varsa seçin" data-bs-toggle="tooltip" @checked(old('jet.pet_onboard'))><label class="form-check-label">Pet</label></div></div>
                                <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[vip_catering]" value="1" title="Özel ikram talebi varsa seçin" data-bs-toggle="tooltip" @checked(old('jet.vip_catering'))><label class="form-check-label">VIP Catering</label></div></div>
                                <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[wifi_required]" value="1" title="Uçuş sırasında internet ihtiyacı varsa seçin" data-bs-toggle="tooltip" @checked(old('jet.wifi_required'))><label class="form-check-label">Wi-Fi</label></div></div>
                                <div class="col-6 col-lg-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="jet[special_luggage]" value="1" title="Özel ebatlı bagaj/ekipman varsa seçin" data-bs-toggle="tooltip" @checked(old('jet.special_luggage'))><label class="form-check-label">Özel Bagaj</label></div></div>
                            </div>
                        </div>

                        <div id="helicopterFields" class="transport-fields d-none">
                            <h6 class="fw-bold mb-3">Helikopter Detayları (Gelişmiş Alan)</h6>
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
                            <h6 class="fw-bold mb-3">Charter Uçak Detayları (Gelişmiş Alan)</h6>
                            <div class="row g-3">
                                <div class="col-12 col-lg-3">
                                    <label class="form-label">Tarih Esnek mi?</label>
                                    <select name="airliner[date_flexible]" class="form-select">
                                        <option value="0" @selected(old('airliner.date_flexible') == '0')>Hayır</option>
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
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <h6 class="fw-bold mb-0">Ekstralar</h6>
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <button type="button" class="btn btn-outline-secondary btn-sm js-quick-extra" data-title="VIP Transfer">VIP Transfer</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm js-quick-extra" data-title="Özel İkram">Özel İkram</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm js-quick-extra" data-title="Yer Hizmeti">Yer Hizmeti</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addExtraBtn">
                                    <i class="fas fa-plus me-1"></i>Ekstra Ekle
                                </button>
                            </div>
                        </div>
                        <div id="extrasWrap" class="d-flex flex-column gap-2">
                            @foreach($oldExtras as $idx => $extra)
                                <div class="row g-2 align-items-start extra-row">
                                    <div class="col-12 col-lg-4">
                                        <input class="form-control" name="extras[{{ $idx }}][title]" placeholder="Ekstra başlığı" value="{{ $extra['title'] ?? '' }}">
                                    </div>
                                    <div class="col-12 col-lg-7">
                                        <input class="form-control" name="extras[{{ $idx }}][agency_note]" placeholder="Ekstra detayı" value="{{ $extra['agency_note'] ?? '' }}">
                                    </div>
                                    <div class="col-12 col-lg-1">
                                        <button type="button" class="btn btn-outline-danger w-100 js-remove-extra">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="advisory-summary d-lg-none mt-4 mb-2">
                            <div class="small text-muted mb-1">Kısa Advisory Özeti</div>
                            <div>Uçak kategorisi: <span class="value js-summary-category">-</span></div>
                            <div>Talep hazırlığı: <span class="value js-summary-prep">-</span></div>
                            <div>Operasyonel durum: <span class="value js-summary-operational">-</span></div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-danger px-4" id="submitBtn">
                                <i class="fas fa-paper-plane me-1"></i>Talebi Kaydet ve Ön Değerlendirmeyi Başlat
                            </button>
                            <div class="submit-note">
                                <strong>Bilgilendirme:</strong> Talebiniz birden fazla operatör tarafından değerlendirilir.
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4 d-none d-lg-block">
            <div class="advisory-sticky">
                @include('acente.charter.partials.advisory-panel', [
                    'title' => 'Canlı Talep Rehberi',
                ])
            </div>
        </div>
    </div>
</div>

<template id="extraRowTemplate">
    <div class="row g-2 align-items-start extra-row">
        <div class="col-12 col-lg-4">
            <input class="form-control" name="extras[__INDEX__][title]" placeholder="Ekstra başlığı">
        </div>
        <div class="col-12 col-lg-7">
            <input class="form-control" name="extras[__INDEX__][agency_note]" placeholder="Ekstra detayı">
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
(() => {
    const form = document.getElementById('charterRequestForm');
    if (!form) return;

    const airportSearchUrl = @json(route('airports.search'));
    const advisoryUrl = @json(route('acente.charter.advisory'));

    const transportInput = document.getElementById('transportTypeInput');
    const transportCards = Array.from(document.querySelectorAll('#transportCards .transport-card'));
    const sections = {
        jet: document.getElementById('jetFields'),
        helicopter: document.getElementById('helicopterFields'),
        airliner: document.getElementById('airlinerFields'),
    };
    const fromHidden = document.getElementById('fromIataHidden');
    const toHidden = document.getElementById('toIataHidden');

    const extrasWrap = document.getElementById('extrasWrap');
    const extraTemplate = document.getElementById('extraRowTemplate');
    const addExtraBtn = document.getElementById('addExtraBtn');
    const quickExtraButtons = Array.from(document.querySelectorAll('.js-quick-extra'));
    let extraIndex = extrasWrap.querySelectorAll('.extra-row').length;

    const debounce = (fn, delay) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    };

    const setTextAll = (selector, text) => {
        document.querySelectorAll(selector).forEach((el) => { el.textContent = text; });
    };

    const setBadgeAll = (selector, label, color) => {
        document.querySelectorAll(selector).forEach((el) => {
            el.textContent = label || '-';
            el.classList.remove('bg-success', 'bg-warning', 'bg-danger', 'bg-secondary', 'text-dark');
            if (color === 'success') el.classList.add('bg-success');
            else if (color === 'warning') el.classList.add('bg-warning', 'text-dark');
            else if (color === 'danger') el.classList.add('bg-danger');
            else el.classList.add('bg-secondary');
        });
    };

    const renderSuggestions = (items) => {
        const listItems = Array.isArray(items) && items.length ? items : ['Alanlar dolduruldukça canlı öneri güncellenir.'];
        document.querySelectorAll('.js-advisory-suggestions').forEach((list) => {
            list.innerHTML = '';
            listItems.forEach((item) => {
                const li = document.createElement('li');
                li.textContent = item;
                list.appendChild(li);
            });
        });
    };

    const renderTimeline = (items) => {
        const timeline = Array.isArray(items) && items.length ? items : [{ title: 'Talep Oluşturma', is_active: true }];
        document.querySelectorAll('.js-advisory-timeline').forEach((wrap) => {
            wrap.innerHTML = '';
            timeline.forEach((step) => {
                const chip = document.createElement('span');
                chip.className = 'charter-advisory-step' + (step.is_active ? ' active' : '');
                chip.textContent = step.title || '-';
                wrap.appendChild(chip);
            });
        });
    };

    const applyAdvisory = (data) => {
        setTextAll('.js-advisory-confidence', data.confidence_text || 'Talebiniz birden fazla operatör tarafından değerlendirilir.');
        setTextAll('.js-advisory-category', data.category || '-');
        setTextAll('.js-advisory-duration', data.duration?.label || '-');
        setBadgeAll('.js-advisory-prep-badge', data.preparation_status?.label || '-', data.preparation_status?.color || null);
        setBadgeAll('.js-advisory-operational-badge', data.operational_status?.label || '-', data.operational_status?.color || null);
        setTextAll('.js-advisory-disclaimer', data.disclaimer || 'Bu panel karar destek amaçlıdır.');
        renderSuggestions(data.missing_suggestions);
        renderTimeline(data.timeline);
        setTextAll('.js-summary-category', data.category || '-');
        setTextAll('.js-summary-prep', data.preparation_status?.label || '-');
        setTextAll('.js-summary-operational', data.operational_status?.label || '-');
    };

    const setTransport = (value) => {
        const selected = ['jet', 'helicopter', 'airliner'].includes(value) ? value : 'jet';
        transportInput.value = selected;
        transportCards.forEach((card) => card.classList.toggle('active', card.dataset.value === selected));
        Object.entries(sections).forEach(([key, node]) => {
            if (!node) return;
            node.classList.toggle('d-none', key !== selected);
        });
    };

    transportCards.forEach((card) => {
        card.addEventListener('click', () => {
            setTransport(card.dataset.value || 'jet');
            requestAdvisory();
        });
    });
    setTransport(transportInput.value || 'jet');

    const buildAirportLine = (item) => {
        const iata = (item.iata || '').toUpperCase();
        const name = item.name || item.label || '';
        return name ? `${iata} - ${name}` : iata;
    };

    const setupAirportAutocomplete = ({ inputId, hiddenId, resultsId }) => {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const results = document.getElementById(resultsId);
        if (!input || !hidden || !results) return;

        let items = [];
        let focused = -1;
        let searchToken = 0;

        const close = () => {
            focused = -1;
            results.classList.remove('show');
        };

        const render = () => {
            results.innerHTML = '';
            if (!items.length) return close();

            items.forEach((item, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'airport-option' + (idx === focused ? ' focused' : '');
                const line2 = `${item.city || ''}${item.country ? ', ' + item.country : ''}`;
                btn.innerHTML = `<div class="line1">${buildAirportLine(item)}</div><div class="line2">${line2}</div>`;
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    hidden.value = (item.iata || '').toUpperCase();
                    input.value = buildAirportLine(item);
                    close();
                    requestAdvisory();
                });
                results.appendChild(btn);
            });

            results.classList.add('show');
        };

        const search = debounce(async () => {
            const q = input.value.trim();
            if (q.length < 2) {
                items = [];
                return close();
            }

            const token = ++searchToken;
            try {
                const response = await fetch(`${airportSearchUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const json = await response.json();
                if (token !== searchToken) return;
                items = Array.isArray(json) ? json : [];
                focused = -1;
                render();
            } catch (_) {
                items = [];
                close();
            }
        }, 220);

        input.addEventListener('input', () => {
            hidden.value = '';
            search();
            requestAdvisory();
        });

        input.addEventListener('focus', () => { if (items.length) results.classList.add('show'); });

        input.addEventListener('keydown', (e) => {
            if (!items.length || !results.classList.contains('show')) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                focused = Math.min(focused + 1, items.length - 1);
                render();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                focused = Math.max(focused - 1, 0);
                render();
            } else if (e.key === 'Enter' && focused >= 0 && items[focused]) {
                e.preventDefault();
                hidden.value = (items[focused].iata || '').toUpperCase();
                input.value = buildAirportLine(items[focused]);
                close();
                requestAdvisory();
            } else if (e.key === 'Escape') {
                close();
            }
        });

        document.addEventListener('click', (e) => {
            if (!results.contains(e.target) && e.target !== input) close();
        });
    };

    setupAirportAutocomplete({ inputId: 'fromIataSearch', hiddenId: 'fromIataHidden', resultsId: 'fromIataResults' });
    setupAirportAutocomplete({ inputId: 'toIataSearch', hiddenId: 'toIataHidden', resultsId: 'toIataResults' });

    const addExtraRow = (title = '') => {
        const html = extraTemplate.innerHTML.replaceAll('__INDEX__', String(extraIndex++));
        const holder = document.createElement('div');
        holder.innerHTML = html.trim();
        const row = holder.firstElementChild;
        const titleInput = row.querySelector('input[name$="[title]"]');
        const noteInput = row.querySelector('input[name$="[agency_note]"]');
        if (titleInput && title) titleInput.value = title;
        extrasWrap.appendChild(row);
        if (noteInput) noteInput.focus();
    };

    addExtraBtn.addEventListener('click', () => addExtraRow());
    quickExtraButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const title = (btn.dataset.title || '').trim();
            if (!title) return;

            const exists = Array.from(extrasWrap.querySelectorAll('input[name$="[title]"]'))
                .find((el) => (el.value || '').trim().toLocaleLowerCase('tr-TR') === title.toLocaleLowerCase('tr-TR'));
            if (exists) return exists.focus();
            addExtraRow(title);
        });
    });

    extrasWrap.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.js-remove-extra');
        if (!removeBtn) return;
        const row = removeBtn.closest('.extra-row');
        if (row) row.remove();
    });

    const advisoryFields = [
        'input[name="departure_date"]',
        'input[name="pax"]',
        'select[name="is_flexible"]',
        'input[name="jet[flight_hours_estimate]"]',
        'input[name="jet[luggage_count]"]',
        'input[name="jet[cabin_preference]"]',
        'input[name="helicopter[pickup]"]',
        'input[name="helicopter[dropoff]"]',
        'textarea[name="helicopter[landing_details]"]',
        'input[name="airliner[group_type]"]',
        'textarea[name="airliner[route_notes]"]',
    ];

    let advisoryToken = 0;
    const fetchAdvisory = async () => {
        const token = ++advisoryToken;
        const params = new URLSearchParams();
        params.set('transport_type', transportInput.value || 'jet');
        params.set('from_iata', (fromHidden.value || '').trim().toUpperCase());
        params.set('to_iata', (toHidden.value || '').trim().toUpperCase());
        params.set('departure_date', form.querySelector('input[name="departure_date"]')?.value || '');
        params.set('pax', form.querySelector('input[name="pax"]')?.value || '');
        params.set('is_flexible', form.querySelector('select[name="is_flexible"]')?.value || '0');

        [
            'input[name="jet[flight_hours_estimate]"]',
            'input[name="jet[luggage_count]"]',
            'input[name="jet[cabin_preference]"]',
            'input[name="helicopter[pickup]"]',
            'input[name="helicopter[dropoff]"]',
            'textarea[name="helicopter[landing_details]"]',
            'input[name="airliner[group_type]"]',
            'textarea[name="airliner[route_notes]"]',
        ].forEach((selector) => {
            const field = form.querySelector(selector);
            if (field?.name) params.set(field.name, field.value || '');
        });

        try {
            const response = await fetch(`${advisoryUrl}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (token !== advisoryToken) return;
            applyAdvisory(data);
        } catch (_) {
            if (token !== advisoryToken) return;
            setTextAll('.js-advisory-disclaimer', 'Advisory bilgisi geçici olarak alınamadı. Formu doldurup devam edebilirsiniz.');
        }
    };

    const requestAdvisory = debounce(fetchAdvisory, 320);
    advisoryFields.forEach((selector) => {
        const field = form.querySelector(selector);
        if (!field) return;
        field.addEventListener('input', requestAdvisory);
        field.addEventListener('change', requestAdvisory);
    });

    form.addEventListener('submit', (e) => {
        if (!fromHidden.value || !toHidden.value) {
            e.preventDefault();
            window.alert('Lütfen kalkış ve varış noktasını listeden seçin.');
            return;
        }

        const prep = document.querySelector('.js-advisory-prep-badge')?.textContent?.trim() || '';
        if (prep === 'Eksik Bilgi Var') {
            const confirmed = window.confirm('Talep hazırlık durumunda eksik bilgi görünüyor. Yine de talebi kaydetmek istiyor musunuz?');
            if (!confirmed) e.preventDefault();
        }
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el);
    });

    requestAdvisory();
})();
</script>
</body>
</html>

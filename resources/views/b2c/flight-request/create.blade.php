@extends('b2c.layouts.app')

@section('title', 'Grup Uçak Talebi — Ücretsiz Teklif Alın')
@section('meta_description', 'Grup uçuşunuz için ücretsiz fiyat teklifi alın. 10+ kişilik yurt içi ve yurt dışı gruplar için özel charter ve blok koltuk fiyatları.')

@push('head_styles')
<style>
/* ── Sayfa genel ─────────────────────────────────────────── */
.fr-page {
    min-height: calc(100vh - var(--nav-height));
    background: linear-gradient(160deg, #0f2444 0%, #1a3c6b 60%, #1e4d8c 100%);
    padding: 40px 0 60px;
}

/* ── Kart ────────────────────────────────────────────────── */
.fr-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    overflow: hidden;
    max-width: 720px;
    margin: 0 auto;
}

/* ── Kart başlık ─────────────────────────────────────────── */
.fr-header {
    background: linear-gradient(135deg, #0f2444 0%, #1a3c6b 100%);
    color: #fff;
    padding: 28px 32px 24px;
}
.fr-header h1 {
    font-size: 1.45rem;
    font-weight: 700;
    margin: 0 0 4px;
}
.fr-header p {
    margin: 0;
    opacity: 0.75;
    font-size: 0.88rem;
}

/* ── İlerleme çubuğu ─────────────────────────────────────── */
.fr-progress {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 0 32px;
    margin-top: 22px;
}
.fr-step-dot {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.6);
    flex-shrink: 0;
    transition: all 0.25s;
}
.fr-step-dot.active {
    background: var(--gr-accent);
    border-color: var(--gr-accent);
    color: #fff;
}
.fr-step-dot.done {
    background: #22c55e;
    border-color: #22c55e;
    color: #fff;
}
.fr-step-line {
    flex: 1;
    height: 2px;
    background: rgba(255,255,255,0.18);
    transition: background 0.25s;
}
.fr-step-line.done { background: #22c55e; }
.fr-step-labels {
    display: flex;
    justify-content: space-between;
    padding: 6px 32px 0;
    margin-bottom: -4px;
}
.fr-step-labels span {
    font-size: 0.68rem;
    color: rgba(255,255,255,0.55);
    width: 32px;
    text-align: center;
    line-height: 1.2;
}
.fr-step-labels span.active { color: rgba(255,255,255,0.9); }

/* ── Form body ───────────────────────────────────────────── */
.fr-body { padding: 28px 32px 32px; }

/* ── Adım paneli ─────────────────────────────────────────── */
.fr-step-panel { display: none; }
.fr-step-panel.active { display: block; }

/* ── Section başlık ──────────────────────────────────────── */
.fr-section-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f2444;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.fr-section-title .icon-bg {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #eef2fb;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

/* ── Trip type seçici ────────────────────────────────────── */
.trip-type-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 10px;
    margin-bottom: 22px;
}
.trip-type-card {
    border: 2px solid var(--gr-border, #e2e8f0);
    border-radius: 12px;
    padding: 14px 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.18s;
    background: #fafbff;
}
.trip-type-card:hover { border-color: #1a3c6b; background: #f0f4ff; }
.trip-type-card.selected {
    border-color: var(--gr-accent);
    background: #fff5f2;
    box-shadow: 0 0 0 3px rgba(255,85,51,0.12);
}
.trip-type-card .tt-icon { font-size: 1.6rem; margin-bottom: 5px; }
.trip-type-card .tt-label { font-size: 0.78rem; font-weight: 600; color: #1a3c6b; }

/* ── Segment kartı ───────────────────────────────────────── */
.segment-card {
    background: #f8faff;
    border: 1px solid #dde4f5;
    border-radius: 14px;
    padding: 18px;
    margin-bottom: 12px;
    position: relative;
}
.segment-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #1a3c6b;
    margin-bottom: 12px;
    opacity: 0.7;
}
.segment-remove {
    position: absolute;
    top: 12px; right: 14px;
    background: none; border: none;
    color: #aaa; font-size: 1.1rem;
    cursor: pointer; padding: 0;
}
.segment-remove:hover { color: #dc3545; }

/* ── Route row ───────────────────────────────────────────── */
.route-row {
    display: grid;
    grid-template-columns: 1fr 36px 1fr;
    align-items: end;
    gap: 8px;
    margin-bottom: 12px;
}
.route-arrow {
    text-align: center;
    font-size: 1.2rem;
    color: #1a3c6b;
    padding-bottom: 8px;
    opacity: 0.5;
}

/* ── Airport widget ──────────────────────────────────────── */
.airport-wrap { position: relative; }
.fr-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #6b7a99;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.airport-display {
    width: 100%;
    border: 1.5px solid #dde4f5;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 0.88rem;
    color: #1a202c;
    background: #fff;
    transition: border-color 0.18s;
}
.airport-display:focus { outline: none; border-color: #1a3c6b; box-shadow: 0 0 0 3px rgba(26,60,107,0.08); }
.airport-display.is-invalid { border-color: #dc3545 !important; }
.airport-iata-badge {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1a3c6b;
    letter-spacing: 1px;
    display: block;
    margin-bottom: 3px;
    min-height: 1em;
}
.airport-dropdown {
    position: absolute;
    top: 100%; left: 0; right: 0;
    background: #fff;
    border: 1.5px solid #dde4f5;
    border-top: none;
    border-radius: 0 0 10px 10px;
    z-index: 1050;
    max-height: 240px;
    overflow-y: auto;
    display: none;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}
.airport-dropdown.show { display: block; }
.airport-option {
    padding: 9px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f4ff;
    transition: background 0.12s;
}
.airport-option:last-child { border-bottom: none; }
.airport-option:hover, .airport-option.focused { background: #f0f4ff; }
.ap-iata { font-weight: 700; color: var(--gr-accent); font-size: 0.88rem; margin-right: 6px; }
.ap-city { color: #1a202c; font-size: 0.83rem; }
.ap-name { color: #718096; font-size: 0.72rem; display: block; margin-top: 1px; }
.airport-loading, .airport-empty { padding: 10px 12px; color: #718096; font-size: 0.82rem; text-align: center; }

/* ── Tarih / saat ────────────────────────────────────────── */
.date-time-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

/* ── Zaman slot butonları ────────────────────────────────── */
.time-slots {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 6px;
}
.time-slot-btn {
    border: 1.5px solid #dde4f5;
    border-radius: 8px;
    padding: 7px 4px;
    text-align: center;
    cursor: pointer;
    font-size: 0.72rem;
    font-weight: 600;
    color: #6b7a99;
    background: #fff;
    transition: all 0.15s;
    line-height: 1.3;
}
.time-slot-btn:hover { border-color: #1a3c6b; color: #1a3c6b; }
.time-slot-btn.selected {
    border-color: #1a3c6b;
    background: #1a3c6b;
    color: #fff;
}
.time-slot-btn input[type=radio] { display: none; }

/* ── Segment ekle butonu ─────────────────────────────────── */
.btn-add-segment {
    width: 100%;
    border: 1.5px dashed #c3cfe0;
    border-radius: 10px;
    padding: 10px;
    background: none;
    color: #1a3c6b;
    font-size: 0.83rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    margin-top: 4px;
}
.btn-add-segment:hover { border-color: #1a3c6b; background: #f0f4ff; }

/* ── PAX counter ─────────────────────────────────────────── */
.pax-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: #f8faff;
    border: 1px solid #dde4f5;
    border-radius: 10px;
    margin-bottom: 8px;
}
.pax-info .pax-name { font-size: 0.88rem; font-weight: 600; color: #1a202c; }
.pax-info .pax-desc { font-size: 0.72rem; color: #718096; }
.pax-counter { display: flex; align-items: center; gap: 12px; }
.pax-btn {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: 1.5px solid #dde4f5;
    background: #fff;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #1a3c6b; font-weight: 700;
    transition: all 0.15s;
    line-height: 1;
}
.pax-btn:hover:not(:disabled) { border-color: #1a3c6b; background: #f0f4ff; }
.pax-btn:disabled { opacity: 0.35; cursor: not-allowed; }
.pax-val { font-size: 1.05rem; font-weight: 700; color: #1a202c; min-width: 28px; text-align: center; }

/* ── Total pax göstergesi ────────────────────────────────── */
.total-pax-strip {
    background: linear-gradient(135deg, #1a3c6b, #0f2444);
    border-radius: 10px;
    padding: 12px 16px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.total-pax-strip .label { font-size: 0.83rem; opacity: 0.8; }
.total-pax-strip .num { font-size: 1.6rem; font-weight: 800; }

/* ── Form kontroller ─────────────────────────────────────── */
.fr-form-group { margin-bottom: 16px; }
.fr-form-group label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: #6b7a99;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}
.fr-input {
    width: 100%;
    border: 1.5px solid #dde4f5;
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 0.9rem;
    color: #1a202c;
    transition: border-color 0.18s;
    background: #fff;
}
.fr-input:focus { outline: none; border-color: #1a3c6b; box-shadow: 0 0 0 3px rgba(26,60,107,0.08); }
.fr-input.is-invalid { border-color: #dc3545; }

/* ── Amaç seçici ─────────────────────────────────────────── */
.purpose-grid {
    display: grid;
    grid-template-columns: repeat(5,1fr);
    gap: 8px;
    margin-bottom: 6px;
}
.purpose-chip {
    border: 1.5px solid #dde4f5;
    border-radius: 10px;
    padding: 10px 4px;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s;
    background: #fafbff;
}
.purpose-chip:hover { border-color: #1a3c6b; background: #f0f4ff; }
.purpose-chip.selected {
    border-color: var(--gr-accent);
    background: #fff5f2;
}
.purpose-chip .pc-icon { font-size: 1.3rem; display: block; margin-bottom: 3px; }
.purpose-chip .pc-label { font-size: 0.65rem; font-weight: 600; color: #1a3c6b; line-height: 1.2; }
.purpose-chip input[type=radio] { display: none; }

/* ── Toggle ──────────────────────────────────────────────── */
.fr-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border: 1px solid #dde4f5;
    border-radius: 10px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: background 0.15s;
}
.fr-toggle-row:hover { background: #f8faff; }
.fr-toggle-label { font-size: 0.88rem; color: #1a202c; display: flex; align-items: center; gap: 8px; }
.fr-toggle-label .tl-icon { font-size: 1.1rem; }

/* ── Navigasyon butonları ────────────────────────────────── */
.fr-nav {
    display: flex;
    gap: 10px;
    margin-top: 24px;
    padding-top: 18px;
    border-top: 1px solid #eef1f7;
}
.btn-fr-back {
    flex: 0 0 auto;
    padding: 11px 20px;
    border: 1.5px solid #dde4f5;
    border-radius: 10px;
    background: #fff;
    color: #6b7a99;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-fr-back:hover { border-color: #1a3c6b; color: #1a3c6b; }
.btn-fr-next {
    flex: 1;
    padding: 13px;
    border: none;
    border-radius: 10px;
    background: #1a3c6b;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s;
}
.btn-fr-next:hover { background: #0f2444; }
.btn-fr-submit {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: var(--gr-accent);
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-fr-submit:hover { background: #e0401e; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(255,85,51,0.35); }
.btn-fr-submit:disabled { opacity: 0.65; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── Trust strip ─────────────────────────────────────────── */
.trust-strip {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
    padding: 0 20px;
}
.trust-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: rgba(255,255,255,0.65);
}
.trust-item i { color: rgba(255,255,255,0.5); font-size: 0.9rem; }

/* ── Hata mesajı ─────────────────────────────────────────── */
.fr-error { color: #dc3545; font-size: 0.75rem; margin-top: 4px; }

/* ── Responsive ──────────────────────────────────────────── */
@@media (max-width: 576px) {
    .fr-card { border-radius: 0; }
    .fr-header { padding: 20px 18px 18px; }
    .fr-progress { padding: 0 18px; }
    .fr-step-labels { padding: 6px 18px 0; }
    .fr-body { padding: 20px 18px 24px; }
    .fr-progress { margin-top: 16px; }
    .route-row { grid-template-columns: 1fr 28px 1fr; }
    .time-slots { grid-template-columns: repeat(2,1fr); }
    .purpose-grid { grid-template-columns: repeat(3,1fr); }
    .trip-type-grid { grid-template-columns: repeat(3,1fr); }
    .trip-type-card .tt-icon { font-size: 1.3rem; }
}
</style>
@endpush

@section('content')
<div class="fr-page">
    <div class="container px-3">

        {{-- Sayfa başlığı --}}
        <div class="text-center text-white mb-4">
            <h2 class="fw-bold mb-1" style="font-size:1.7rem;">✈️ Grup Uçuş Teklifi Alın</h2>
            <p class="opacity-75 mb-0" style="font-size:0.9rem;">10+ kişilik gruplar için özel charter ve blok koltuk fiyatları</p>
        </div>

        <div class="fr-card">

            {{-- Header + Progress --}}
            <div class="fr-header">
                <h1 id="step-title">Uçuş Bilgileri</h1>
                <p id="step-desc">Nereden nereye, hangi tarihte uçmak istiyorsunuz?</p>

                <div class="fr-progress mt-3">
                    <div class="fr-step-dot active" id="dot-1">1</div>
                    <div class="fr-step-line" id="line-1"></div>
                    <div class="fr-step-dot" id="dot-2">2</div>
                    <div class="fr-step-line" id="line-2"></div>
                    <div class="fr-step-dot" id="dot-3">3</div>
                </div>
                <div class="fr-step-labels">
                    <span class="active" id="label-1">Uçuş</span>
                    <span id="label-2">Yolcular</span>
                    <span id="label-3">Detaylar</span>
                </div>
            </div>

            {{-- Form --}}
            <div class="fr-body">

                @if($errors->any())
                <div class="alert alert-danger py-2 mb-3">
                    <ul class="mb-0 ps-3" style="font-size:0.82rem;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form id="fr-form" method="POST" action="{{ lroute('b2c.flight.store') }}">
                    @csrf

                    {{-- ═══════════════════════════════════════ ADIM 1 ══ --}}
                    <div class="fr-step-panel active" id="panel-1">

                        {{-- Trip type --}}
                        <div class="fr-section-title">
                            <div class="icon-bg">✈️</div>
                            Uçuş Tipi
                        </div>

                        <div class="trip-type-grid" id="trip-type-grid">
                            <div class="trip-type-card selected" data-value="one_way">
                                <div class="tt-icon">→</div>
                                <div class="tt-label">Tek Yön</div>
                            </div>
                            <div class="trip-type-card" data-value="round_trip">
                                <div class="tt-icon">⇄</div>
                                <div class="tt-label">Gidiş-Dönüş</div>
                            </div>
                            <div class="trip-type-card" data-value="multi">
                                <div class="tt-icon">⊕</div>
                                <div class="tt-label">Çoklu Uçuş</div>
                            </div>
                        </div>
                        <input type="hidden" name="trip_type" id="trip-type-val" value="one_way">

                        {{-- Segmentler --}}
                        <div class="fr-section-title mt-3">
                            <div class="icon-bg">📍</div>
                            Rota & Tarih
                        </div>

                        <div id="segments-container"></div>

                        <button type="button" class="btn-add-segment" id="btn-add-segment" style="display:none;">
                            <i class="bi bi-plus-circle me-1"></i> Uçuş Ekle
                        </button>

                        <div class="fr-nav">
                            <button type="button" class="btn-fr-next w-100" onclick="goStep(2)">
                                Devam <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════ ADIM 2 ══ --}}
                    <div class="fr-step-panel" id="panel-2">

                        <div class="fr-section-title">
                            <div class="icon-bg">👥</div>
                            Yolcu Sayısı
                        </div>

                        <div class="total-pax-strip">
                            <span class="label">Toplam Yolcu</span>
                            <span class="num" id="total-pax-display">0</span>
                        </div>

                        <div class="pax-row">
                            <div class="pax-info">
                                <div class="pax-name">Yetişkin</div>
                                <div class="pax-desc">12 yaş ve üzeri</div>
                            </div>
                            <div class="pax-counter">
                                <button type="button" class="pax-btn" onclick="changePax('adult',-1)" id="btn-adult-minus">−</button>
                                <span class="pax-val" id="val-adult">0</span>
                                <button type="button" class="pax-btn" onclick="changePax('adult',1)">+</button>
                            </div>
                        </div>

                        <div class="pax-row">
                            <div class="pax-info">
                                <div class="pax-name">Çocuk</div>
                                <div class="pax-desc">2–11 yaş</div>
                            </div>
                            <div class="pax-counter">
                                <button type="button" class="pax-btn" onclick="changePax('child',-1)" id="btn-child-minus">−</button>
                                <span class="pax-val" id="val-child">0</span>
                                <button type="button" class="pax-btn" onclick="changePax('child',1)">+</button>
                            </div>
                        </div>

                        <div class="pax-row">
                            <div class="pax-info">
                                <div class="pax-name">Bebek</div>
                                <div class="pax-desc">0–2 yaş</div>
                            </div>
                            <div class="pax-counter">
                                <button type="button" class="pax-btn" onclick="changePax('infant',-1)" id="btn-infant-minus">−</button>
                                <span class="pax-val" id="val-infant">0</span>
                                <button type="button" class="pax-btn" onclick="changePax('infant',1)">+</button>
                            </div>
                        </div>

                        <input type="hidden" name="pax_total"  id="inp-pax-total"  value="0">
                        <input type="hidden" name="pax_adult"  id="inp-pax-adult"  value="0">
                        <input type="hidden" name="pax_child"  id="inp-pax-child"  value="0">
                        <input type="hidden" name="pax_infant" id="inp-pax-infant" value="0">

                        <div class="mt-3 mb-1" style="border-top:1px solid #eef1f7; padding-top:18px;">
                        <div class="fr-section-title">
                            <div class="icon-bg">📞</div>
                            İletişim Bilgileri
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="fr-form-group mb-0">
                                    <label for="inp-name">Ad Soyad *</label>
                                    <input type="text" id="inp-name" name="contact_name" class="fr-input {{ $errors->has('contact_name') ? 'is-invalid' : '' }}"
                                           placeholder="Adınız Soyadınız" value="{{ old('contact_name') }}" required autocomplete="name">
                                    @error('contact_name')<div class="fr-error">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="fr-form-group mb-0">
                                    <label for="inp-phone">Telefon *</label>
                                    <input type="tel" id="inp-phone" name="phone" class="fr-input {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                           placeholder="05XX XXX XX XX" value="{{ old('phone') }}" required autocomplete="tel">
                                    @error('phone')<div class="fr-error">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="fr-form-group mb-0">
                                    <label for="inp-email">E-posta *</label>
                                    <input type="email" id="inp-email" name="email" class="fr-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                           placeholder="ornek@email.com" value="{{ old('email') }}" required autocomplete="email">
                                    @error('email')<div class="fr-error">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        </div>

                        <div class="fr-nav">
                            <button type="button" class="btn-fr-back" onclick="goStep(1)">
                                <i class="bi bi-arrow-left me-1"></i> Geri
                            </button>
                            <button type="button" class="btn-fr-next" onclick="goStep(3)">
                                Devam <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════ ADIM 3 ══ --}}
                    <div class="fr-step-panel" id="panel-3">

                        <div class="fr-section-title">
                            <div class="icon-bg">🎯</div>
                            Seyahat Amacı
                        </div>

                        <div class="purpose-grid">
                            <label class="purpose-chip">
                                <input type="radio" name="flight_purpose" value="turizm">
                                <span class="pc-icon">🏖️</span>
                                <span class="pc-label">Turizm</span>
                            </label>
                            <label class="purpose-chip">
                                <input type="radio" name="flight_purpose" value="hac_umre">
                                <span class="pc-icon">🕌</span>
                                <span class="pc-label">Hac / Umre</span>
                            </label>
                            <label class="purpose-chip">
                                <input type="radio" name="flight_purpose" value="is">
                                <span class="pc-icon">💼</span>
                                <span class="pc-label">İş Seyahati</span>
                            </label>
                            <label class="purpose-chip">
                                <input type="radio" name="flight_purpose" value="okul">
                                <span class="pc-icon">🎓</span>
                                <span class="pc-label">Okul / Gezi</span>
                            </label>
                            <label class="purpose-chip">
                                <input type="radio" name="flight_purpose" value="diger">
                                <span class="pc-icon">✨</span>
                                <span class="pc-label">Diğer</span>
                            </label>
                        </div>

                        <div class="fr-form-group mt-3">
                            <label for="inp-airline">Havayolu Tercihi (opsiyonel)</label>
                            <div style="position:relative;">
                                <input type="text" id="inp-airline-display" class="fr-input"
                                       placeholder="THY, Pegasus, SunExpress..." autocomplete="off">
                                <input type="hidden" name="preferred_airline" id="inp-airline-hidden">
                                <div id="airline-dropdown" class="airport-dropdown"></div>
                            </div>
                        </div>

                        <div class="fr-toggle-row" onclick="toggleHotel()">
                            <span class="fr-toggle-label">
                                <span class="tl-icon">🏨</span>
                                Otel de ayarlansın
                            </span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="hotel_needed" id="hotel-toggle" value="1" style="pointer-events:none;">
                            </div>
                        </div>

                        <div class="fr-form-group mt-3">
                            <label for="inp-notes">Notlar / Özel İstekler</label>
                            <textarea id="inp-notes" name="notes" class="fr-input" rows="3"
                                      placeholder="Bütçe beklentiniz, tercihleriniz veya özel istekleriniz...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="fr-nav">
                            <button type="button" class="btn-fr-back" onclick="goStep(2)">
                                <i class="bi bi-arrow-left me-1"></i> Geri
                            </button>
                            <button type="submit" class="btn-fr-submit" id="btn-submit">
                                <i class="bi bi-send-fill"></i>
                                Teklif İste
                            </button>
                        </div>

                        <p class="text-center text-muted mt-3 mb-0" style="font-size:0.72rem;">
                            <i class="bi bi-lock-fill me-1"></i>Bilgileriniz güvende. Ortalama 2-4 saat içinde dönüş yapıyoruz.
                        </p>
                    </div>

                </form>
            </div>
        </div>

        {{-- Trust strip --}}
        <div class="trust-strip">
            <div class="trust-item"><i class="bi bi-shield-check"></i> Güvenli & Ücretsiz</div>
            <div class="trust-item"><i class="bi bi-clock"></i> 2-4 Saat İçinde Dönüş</div>
            <div class="trust-item"><i class="bi bi-people-fill"></i> 10+ Kişilik Gruplar</div>
            <div class="trust-item"><i class="bi bi-airplane"></i> Yurt İçi & Dışı</div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Sayfa verileri ──────────────────────────────────────────────────────────
const AIRPORT_URL = '{{ lroute("b2c.airports.search") }}';
const AIRLINE_URL = '{{ lroute("b2c.airlines.search") }}';
const TODAY       = '{{ now()->format("Y-m-d") }}';

// ── Adım yönetimi ───────────────────────────────────────────────────────────
const STEP_META = {
    1: { title: 'Uçuş Bilgileri',      desc: 'Nereden nereye, hangi tarihte uçmak istiyorsunuz?' },
    2: { title: 'Yolcu Bilgileri',     desc: 'Kaç kişilik grup? Sizinle nasıl iletişim kuralım?' },
    3: { title: 'Ek Tercihler',        desc: 'Son adım — teklifinizi gönderelim!' },
};

let currentStep = 1;

function goStep(target) {
    if (target > currentStep && !validateStep(currentStep)) return;

    document.getElementById(`panel-${currentStep}`).classList.remove('active');
    document.getElementById(`panel-${target}`).classList.add('active');

    // Progress güncelle
    for (let i = 1; i <= 3; i++) {
        const dot   = document.getElementById(`dot-${i}`);
        const label = document.getElementById(`label-${i}`);
        dot.classList.remove('active','done');
        label.classList.remove('active');
        if (i < target) { dot.classList.add('done'); dot.innerHTML = '✓'; }
        else if (i === target) { dot.classList.add('active'); dot.innerHTML = i; label.classList.add('active'); }
        else { dot.innerHTML = i; }
        if (i < 3) {
            const line = document.getElementById(`line-${i}`);
            line.classList.toggle('done', i < target);
        }
    }

    document.getElementById('step-title').textContent = STEP_META[target].title;
    document.getElementById('step-desc').textContent  = STEP_META[target].desc;

    currentStep = target;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep(step) {
    if (step === 1) return validateSegments();
    if (step === 2) return validateContact();
    return true;
}

function validateSegments() {
    let ok = true;
    document.querySelectorAll('[data-iata-hidden]').forEach(h => {
        const wrap    = h.closest('.airport-wrap');
        const display = wrap.querySelector('[data-airport-input]');
        if (!h.value) {
            ok = false;
            display.classList.add('is-invalid');
        } else {
            display.classList.remove('is-invalid');
        }
    });
    document.querySelectorAll('.segment-date').forEach(d => {
        if (!d.value) { ok = false; d.classList.add('is-invalid'); }
        else d.classList.remove('is-invalid');
    });
    if (!ok) alert('Lütfen tüm kalkış/varış ve tarih alanlarını doldurun.');
    return ok;
}

function validateContact() {
    const total = parseInt(document.getElementById('inp-pax-total').value) || 0;
    if (total < 1) { alert('Lütfen en az 1 yolcu ekleyin.'); return false; }
    const name  = document.getElementById('inp-name').value.trim();
    const phone = document.getElementById('inp-phone').value.trim();
    const email = document.getElementById('inp-email').value.trim();
    if (!name || !phone || !email) { alert('Lütfen ad soyad, telefon ve e-posta alanlarını doldurun.'); return false; }
    return true;
}

// ── Trip type seçimi ────────────────────────────────────────────────────────
document.getElementById('trip-type-grid').addEventListener('click', function(e) {
    const card = e.target.closest('.trip-type-card');
    if (!card) return;
    this.querySelectorAll('.trip-type-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('trip-type-val').value = card.dataset.value;
    renderSegments();
});

// ── Segment render ──────────────────────────────────────────────────────────
let segCount = 0;

function renderSegments() {
    const type = document.getElementById('trip-type-val').value;
    const container = document.getElementById('segments-container');
    const addBtn    = document.getElementById('btn-add-segment');

    container.innerHTML = '';
    segCount = 0;

    if (type === 'one_way') {
        addSegment('Gidiş');
        addBtn.style.display = 'none';
    } else if (type === 'round_trip') {
        addSegment('Gidiş');
        addSegment('Dönüş');
        addBtn.style.display = 'none';
    } else {
        addSegment('1. Uçuş');
        addSegment('2. Uçuş');
        addBtn.style.display = '';
    }
}

function addSegment(label) {
    const idx = segCount++;
    const isMulti = document.getElementById('trip-type-val').value === 'multi';
    const canRemove = isMulti && idx >= 2;

    const card = document.createElement('div');
    card.className = 'segment-card';
    card.dataset.segIdx = idx;
    card.innerHTML = `
        <div class="segment-label">${label || (idx + 1) + '. Uçuş'}</div>
        ${canRemove ? `<button type="button" class="segment-remove" onclick="removeSegment(this)" title="Kaldır"><i class="bi bi-x-lg"></i></button>` : ''}
        <div class="route-row">
            <div>
                <div class="fr-label">Kalkış</div>
                ${airportWidget(`segments[${idx}][from_iata]`, 'Şehir veya IATA kodu...', true)}
            </div>
            <div class="route-arrow">→</div>
            <div>
                <div class="fr-label">Varış</div>
                ${airportWidget(`segments[${idx}][to_iata]`, 'Şehir veya IATA kodu...', true)}
            </div>
        </div>
        <div class="date-time-row">
            <div>
                <div class="fr-label">Tarih *</div>
                <input type="date" name="segments[${idx}][departure_date]"
                       class="fr-input segment-date" min="${TODAY}" required>
            </div>
            <div>
                <div class="fr-label">Saat Tercihi *</div>
                <div class="time-slots">
                    ${timeSlotBtn(idx,'sabah','🌅 Sabah','06:00–12:00')}
                    ${timeSlotBtn(idx,'ogle','☀️ Öğlen','12:00–17:00')}
                    ${timeSlotBtn(idx,'aksam','🌆 Akşam','17:00+',)}
                    ${timeSlotBtn(idx,'esnek','🕐 Esnek','Fark etmez', true)}
                </div>
            </div>
        </div>
    `;
    document.getElementById('segments-container').appendChild(card);
}

function airportWidget(name, placeholder, required) {
    const req = required ? 'required' : '';
    return `
    <div class="airport-wrap">
        <span class="airport-iata-badge" data-iata-badge>—</span>
        <input type="hidden" name="${name}" data-iata-hidden>
        <input type="text"
               class="airport-display"
               placeholder="${placeholder}"
               autocomplete="off"
               data-airport-input
               ${req}>
        <div class="airport-dropdown" data-airport-dropdown></div>
    </div>`;
}

function timeSlotBtn(idx, value, label, sub, checked) {
    return `<label class="time-slot-btn ${checked ? 'selected' : ''}">
        <input type="radio" name="segments[${idx}][departure_time_slot]" value="${value}" ${checked ? 'checked' : ''}>
        <span style="display:block;">${label}</span>
        <span style="font-size:0.62rem;opacity:0.7;">${sub}</span>
    </label>`;
}

function removeSegment(btn) {
    btn.closest('.segment-card').remove();
    reindexSegments();
}

function reindexSegments() {
    document.querySelectorAll('.segment-card').forEach((card, i) => {
        card.dataset.segIdx = i;
        card.querySelectorAll('[name^="segments["]').forEach(el => {
            el.name = el.name.replace(/segments\[\d+\]/, `segments[${i}]`);
        });
        const label = card.querySelector('.segment-label');
        if (label) label.textContent = `${i + 1}. Uçuş`;
    });
    segCount = document.querySelectorAll('.segment-card').length;
}

document.getElementById('btn-add-segment').addEventListener('click', function() {
    addSegment(`${segCount + 1}. Uçuş`);
});

// Zaman slot tıklaması
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.time-slot-btn');
    if (!btn) return;
    const card = btn.closest('.segment-card');
    card.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    btn.querySelector('input').checked = true;
});

// ── PAX counter ─────────────────────────────────────────────────────────────
const paxState = { adult: 0, child: 0, infant: 0 };

function changePax(type, delta) {
    paxState[type] = Math.max(0, paxState[type] + delta);
    document.getElementById(`val-${type}`).textContent = paxState[type];
    document.getElementById(`btn-${type}-minus`).disabled = paxState[type] === 0;
    const total = paxState.adult + paxState.child + paxState.infant;
    document.getElementById('total-pax-display').textContent = total;
    document.getElementById('inp-pax-total').value  = total;
    document.getElementById('inp-pax-adult').value  = paxState.adult;
    document.getElementById('inp-pax-child').value  = paxState.child;
    document.getElementById('inp-pax-infant').value = paxState.infant;
}

// ── Amaç chip seçimi ─────────────────────────────────────────────────────────
document.querySelectorAll('.purpose-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        document.querySelectorAll('.purpose-chip').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('input').checked = true;
    });
});

// ── Hotel toggle ─────────────────────────────────────────────────────────────
function toggleHotel() {
    const cb = document.getElementById('hotel-toggle');
    cb.checked = !cb.checked;
}

// ── Form submit ───────────────────────────────────────────────────────────────
document.getElementById('fr-form').addEventListener('submit', function(e) {
    if (!validateStep(1) || !validateStep(2)) { e.preventDefault(); return; }
    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Gönderiliyor...';
});

// ── Havalimanı Autocomplete ───────────────────────────────────────────────────
let acTimer = null;

document.addEventListener('input', function(e) {
    if (!e.target.hasAttribute('data-airport-input')) return;
    clearTimeout(acTimer);
    const q = e.target.value.trim();
    if (q.length < 2) { closeDropdown(e.target); return; }
    showLoading(e.target);
    acTimer = setTimeout(() => fetchAirports(q, e.target), 280);
});

document.addEventListener('keydown', function(e) {
    if (!e.target.hasAttribute('data-airport-input')) return;
    const dd    = getDropdown(e.target);
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
        if (focused) selectAirport(focused, e.target);
    } else if (e.key === 'Escape') {
        closeDropdown(e.target);
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.airport-wrap')) {
        document.querySelectorAll('.airport-dropdown.show').forEach(dd => dd.classList.remove('show'));
    }
});

function getDropdown(input) { return input.closest('.airport-wrap').querySelector('[data-airport-dropdown]'); }
function getBadge(input)    { return input.closest('.airport-wrap').querySelector('[data-iata-badge]'); }
function getHidden(input)   { return input.closest('.airport-wrap').querySelector('[data-iata-hidden]'); }

function showLoading(input) {
    const dd = getDropdown(input);
    dd.innerHTML = '<div class="airport-loading"><i class="bi bi-arrow-repeat me-1"></i>Aranıyor...</div>';
    dd.classList.add('show');
}

function closeDropdown(input) { getDropdown(input).classList.remove('show'); }

async function fetchAirports(q, input) {
    try {
        const resp = await fetch(AIRPORT_URL + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        renderDropdown(await resp.json(), input);
    } catch { closeDropdown(input); }
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
             data-city="${escHtml(a.city || '')}"
             data-country="${escHtml(a.country || '')}">
            <span class="ap-iata">${a.iata}</span>
            <span class="ap-city">${escHtml(a.city || '')}${a.city && a.country ? ', ' : ''}${escHtml(a.country || '')}</span>
            <span class="ap-name">${escHtml(a.name)}</span>
        </div>`).join('');
    dd.classList.add('show');
    dd.querySelectorAll('.airport-option').forEach(opt =>
        opt.addEventListener('mousedown', e => { e.preventDefault(); selectAirport(opt, input); })
    );
}

function selectAirport(opt, input) {
    const iata    = opt.dataset.iata;
    const city    = opt.dataset.city;
    const country = opt.dataset.country;
    const display = city ? `${iata} — ${city}${country ? ', ' + country : ''}` : iata;
    getHidden(input).value      = iata;
    getBadge(input).textContent = iata;
    input.value                 = display;
    input.classList.remove('is-invalid');
    closeDropdown(input);
}

// ── Havayolu Autocomplete ─────────────────────────────────────────────────────
let alTimer = null;
const alInput = document.getElementById('inp-airline-display');
const alDD    = document.getElementById('airline-dropdown');
const alHid   = document.getElementById('inp-airline-hidden');

alInput.addEventListener('input', function() {
    clearTimeout(alTimer);
    const q = this.value.trim();
    if (q.length < 2) { alDD.classList.remove('show'); return; }
    alDD.innerHTML = '<div class="airport-loading">Aranıyor...</div>';
    alDD.classList.add('show');
    alTimer = setTimeout(() => fetchAirlines(q), 280);
});

async function fetchAirlines(q) {
    try {
        const resp = await fetch(AIRLINE_URL + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await resp.json();
        if (!data.length) { alDD.innerHTML = '<div class="airport-empty">Sonuç bulunamadı</div>'; return; }
        alDD.innerHTML = data.map(a => `
            <div class="airport-option" data-code="${a.code || ''}" data-name="${escHtml(a.name || '')}">
                <span class="ap-iata">${a.code || ''}</span>
                <span class="ap-city">${escHtml(a.name || '')}</span>
            </div>`).join('');
        alDD.querySelectorAll('.airport-option').forEach(opt =>
            opt.addEventListener('mousedown', e => {
                e.preventDefault();
                alHid.value  = opt.dataset.code + ' ' + opt.dataset.name;
                alInput.value = opt.dataset.code + ' — ' + opt.dataset.name;
                alDD.classList.remove('show');
            })
        );
    } catch { alDD.classList.remove('show'); }
}

document.addEventListener('click', e => {
    if (!e.target.closest('#inp-airline-display') && !e.target.closest('#airline-dropdown')) {
        alDD.classList.remove('show');
    }
});

// ── Yardımcı ─────────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Başlangıç ─────────────────────────────────────────────────────────────────
renderSegments();

// Hata varsa ilgili adıma git
@if($errors->has('contact_name') || $errors->has('phone') || $errors->has('email') || $errors->has('pax_total'))
    goStep(2);
@elseif($errors->has('flight_purpose') || $errors->has('notes'))
    goStep(3);
@endif
</script>
@endpush

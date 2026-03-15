<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $talep->gtpnr }} — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .section-title { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600; margin-bottom: 0.75rem; }
        .field-label { font-size: 0.75rem; color: #6c757d; }
        .ai-field { background: #fff3cd; border-left: 3px solid #ffc107; }
    </style>
</head>
<body>

<x-navbar-admin active="talepler" />

<div class="container-fluid px-4 py-3">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">{{ $talep->gtpnr }}</h4>
            <small class="text-muted">
                @if($talep->agency_name === 'MÜNFERİT')
                    <span class="badge" style="background:#20c997;color:#fff;">MÜNFERİT</span>
                @else
                    {{ $talep->agency_name }}
                @endif
                · {{ $talep->pax_total }} PAX · {{ $talep->created_at->format('d.m.Y') }}
            </small>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary btn-sm">← Talepler</a>
            <a href="/acente/talep/{{ $talep->gtpnr }}" target="_blank" class="btn btn-outline-primary btn-sm">Acente Görünümü →</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">

        {{-- SOL KOLON --}}
        <div class="col-md-7">

            {{-- TALEP BİLGİLERİ --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-bold">📋 Talep Bilgileri</span>
                    @php
                        $durumEtiketleri = ['beklemede'=>'Beklemede','islemde'=>'İşlemde','fiyatlandirıldi'=>'Fiyatlandırıldı','depozitoda'=>'Depozitoda','biletlendi'=>'Biletlendi','iade'=>'İade','olumsuz'=>'Olumsuz'];
                        $durumRenkleri   = ['beklemede'=>'secondary','islemde'=>'primary','fiyatlandirıldi'=>'warning','depozitoda'=>'purple','biletlendi'=>'success','iade'=>'danger','olumsuz'=>'dark'];
                    @endphp
                    <span class="badge" style="background-color:{{ ['beklemede'=>'#6c757d','islemde'=>'#0d6efd','fiyatlandirıldi'=>'#ffc107','depozitoda'=>'#6f42c1','biletlendi'=>'#198754','iade'=>'#dc3545','olumsuz'=>'#343a40'][$talep->status] ?? '#6c757d' }}; {{ $talep->status==='fiyatlandirıldi'?'color:#000;':'' }}">
                        {{ $durumEtiketleri[$talep->status] ?? $talep->status }}
                    </span>
                    <x-iade-badge :talep="$talep" />
                </div>
                <div class="card-body py-2">
                    <div class="row g-2">
                        <div class="col-6 col-md-3"><div class="field-label">Acente</div><div class="fw-bold">
                            @if($talep->agency_name === 'MÜNFERİT')
                                <span class="badge" style="background:#20c997;color:#fff;">MÜNFERİT</span>
                            @else
                                {{ $talep->agency_name }}
                            @endif
                        </div></div>
                        <div class="col-6 col-md-3"><div class="field-label">Telefon</div><div>{{ $talep->phone }}</div></div>
                        <div class="col-6 col-md-3"><div class="field-label">E-posta</div><div>{{ $talep->email }}</div></div>
                        <div class="col-6 col-md-3"><div class="field-label">PAX</div><div class="fw-bold">{{ $talep->pax_total }} (Y:{{ $talep->pax_adult }} Ç:{{ $talep->pax_child }} B:{{ $talep->pax_infant }})</div></div>
                        @if($talep->group_company_name)<div class="col-6 col-md-3"><div class="field-label">Grup Firma</div><div>{{ $talep->group_company_name }}</div></div>@endif
                        @if($talep->flight_purpose)<div class="col-6 col-md-3"><div class="field-label">Uçuş Amacı</div><div>{{ $talep->flight_purpose }}</div></div>@endif
                        @if($talep->notes)<div class="col-12"><div class="field-label">Acente Notu</div><div class="bg-light rounded p-2 small">{{ $talep->notes }}</div></div>@endif
                    </div>
                </div>
            </div>

            {{-- SEGMENTLER --}}
            <div class="card mb-3">
                <div class="card-header py-2 fw-bold">✈️ Uçuş Segmentleri</div>
                <div class="card-body py-2">
                    @foreach($talep->segments as $s)
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                        <span class="fw-bold fs-5">{{ $s->from_iata }}</span>
                        <i class="fas fa-arrow-right text-muted"></i>
                        <span class="fw-bold fs-5">{{ $s->to_iata }}</span>
                        <span class="text-muted">{{ $s->departure_date }}</span>
                        @if($s->departure_time)<span class="text-muted">{{ $s->departure_time }}</span>@endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- AI PARSE KUTUSU --}}
            <div class="card mb-3 border-warning">
                <div class="card-header py-2 fw-bold bg-warning bg-opacity-10">
                    🤖 Operasyon Notu → AI ile Doldur
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Ham operasyon notunu buraya yapıştır (banka bildirimi, PNR, fiyat, tarih vb.)</label>
                        <textarea id="raw-note-input" class="form-control font-monospace" rows="5" placeholder="Firmaniza ait, 234-*348 numarali hesabiniza..."></textarea>
                    </div>
                    <button class="btn btn-warning btn-sm" onclick="aiParseBaslat()">
                        <i class="fas fa-magic me-1"></i> AI ile Ayrıştır
                    </button>
                    <span id="parse-spinner" class="ms-2 text-muted small d-none"><i class="fas fa-spinner fa-spin me-1"></i>Ayrıştırılıyor...</span>
                </div>
            </div>

            {{-- TEKLİF FORMU --}}
            <div class="card mb-3">
                <div class="card-header py-2 fw-bold">💰 Teklif / Rezervasyon Bilgileri Ekle</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.requests.offer', $talep->gtpnr) }}" id="offer-form">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small">Havayolu</label>
                                <input type="text" name="airline" id="f-airline" class="form-control form-control-sm" placeholder="AJET, TK...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Havayolu PNR</label>
                                <input type="text" name="airline_pnr" id="f-airline-pnr" class="form-control form-control-sm" placeholder="3SLS2E">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Sefer Kodu</label>
                                <input type="text" name="flight_number" id="f-flight-number" class="form-control form-control-sm" placeholder="VF3180">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Kalkış Saati</label>
                                <input type="time" name="flight_departure_time" id="f-dep-time" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Varış Saati</label>
                                <input type="time" name="flight_arrival_time" id="f-arr-time" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Bagaj (KG)</label>
                                <input type="number" name="baggage_kg" id="f-baggage" class="form-control form-control-sm" placeholder="15">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Teyit PAX</label>
                                <input type="number" name="pax_confirmed" id="f-pax" class="form-control form-control-sm" placeholder="{{ $talep->pax_total }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Para Birimi</label>
                                <select name="currency" id="f-currency" class="form-select form-select-sm">
                                    <option value="TRY">TRY</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Kişi Başı Fiyat</label>
                                <input type="number" name="price_per_pax" id="f-price-pax" class="form-control form-control-sm" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Maliyet Fiyatı</label>
                                <input type="number" name="cost_price" id="f-cost" class="form-control form-control-sm" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Depozito Oranı (%)</label>
                                <input type="number" name="deposit_rate" class="form-control form-control-sm" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Depozito Tutarı</label>
                                <input type="number" name="deposit_amount" class="form-control form-control-sm" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Opsiyon Tarihi</label>
                                <input type="date" name="option_date" id="f-option-date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Opsiyon Saati</label>
                                <input type="time" name="option_time" id="f-option-time" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Teklif Notu (acenteye görünür)</label>
                                <textarea name="offer_text" id="f-offer-text" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted">Tedarikçi / İç Referans (acenteye gizli)</label>
                                <input type="text" name="supplier_reference" id="f-supplier-ref" class="form-control form-control-sm" placeholder="KUNEYILD2633080">
                            </div>
                            <input type="hidden" name="admin_raw_note" id="f-raw-note">
                            <input type="hidden" name="ai_raw_output" id="f-ai-raw-output">

                            {{-- Gizli ödeme alanları: AI parse varsa otomatik kaydedilir --}}
                            <input type="hidden" name="p_amount" id="f-p-amount">
                            <input type="hidden" name="p_currency" id="f-p-currency">
                            <input type="hidden" name="p_date" id="f-p-date">
                            <input type="hidden" name="p_method" id="f-p-method">
                            <input type="hidden" name="p_bank" id="f-p-bank">
                            <input type="hidden" name="p_sender" id="f-p-sender">
                            <input type="hidden" name="p_account" id="f-p-account">
                            <input type="hidden" name="p_sequence" id="f-p-sequence">
                            <input type="hidden" name="p_type" id="f-p-type">

                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-save me-1"></i> Teklifi Kaydet
                                </button>
                                <span id="ai-odeme-bilgisi" class="ms-2 text-success small d-none">
                                    <i class="fas fa-check-circle me-1"></i>Ödeme bilgisi de kaydedilecek
                                </span>
                                <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="formuTemizle()">Temizle</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ÖDEME KAYDET --}}
            <div class="card mb-3">
                <div class="card-header py-2 fw-bold">💳 Ödeme Kaydet</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.requests.payment', $talep->gtpnr) }}" id="payment-form">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-2">
                                <label class="form-label small">Sıra No</label>
                                <input type="number" name="sequence" id="p-sequence" class="form-control form-control-sm" value="1" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Ödeme Tipi</label>
                                <select name="payment_type" class="form-select form-select-sm">
                                    <option value="depozito">Depozito</option>
                                    <option value="bakiye">Bakiye</option>
                                    <option value="full">Full</option>
                                    <option value="diger">Diğer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Yöntem</label>
                                <select name="payment_method" id="p-method" class="form-select form-select-sm">
                                    <option value="">Seç</option>
                                    <option value="FAST">FAST</option>
                                    <option value="EFT">EFT</option>
                                    <option value="havale">Havale</option>
                                    <option value="kart">Kart</option>
                                    <option value="nakit">Nakit</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Banka</label>
                                <input type="text" name="bank_name" id="p-bank" class="form-control form-control-sm" placeholder="Garanti BBVA">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Gönderen (maskeli)</label>
                                <input type="text" name="sender_masked" id="p-sender" class="form-control form-control-sm" placeholder="EC* TU**...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Hesap No (maskeli)</label>
                                <input type="text" name="account_masked" id="p-account" class="form-control form-control-sm" placeholder="234-*348">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Tutar</label>
                                <input type="number" name="amount" id="p-amount" class="form-control form-control-sm" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Para Birimi</label>
                                <select name="currency" id="p-currency" class="form-select form-select-sm">
                                    <option value="TRY">TRY</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Ödeme Tarihi</label>
                                <input type="date" name="payment_date" id="p-date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Durum</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="alindi">Alındı</option>
                                    <option value="bekleniyor">Bekleniyor</option>
                                    <option value="iade">İade</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save me-1"></i> Ödemeyi Kaydet
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        {{-- SAĞ KOLON --}}
        <div class="col-md-5">

            {{-- DURUM GÜNCELLE --}}
            <div class="card mb-3">
                <div class="card-header py-2 fw-bold">🔄 Durum Güncelle</div>
                <div class="card-body py-2">
                    @if($talep->status === 'biletlendi')
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="fas fa-lock me-1"></i>Biletlenmiş talepler değiştirilemez.
                        </div>
                    @else
                    <form method="POST" action="{{ route('admin.requests.status', $talep->gtpnr) }}">
                        @csrf
                        <div class="input-group input-group-sm">
                            <select name="status" class="form-select">
                                @foreach(['beklemede'=>'Beklemede','islemde'=>'İşlemde','fiyatlandirıldi'=>'Fiyatlandırıldı','depozitoda'=>'Depozitoda','biletlendi'=>'Biletlendi','iade'=>'İade','olumsuz'=>'Olumsuz'] as $val => $label)
                                <option value="{{ $val }}" {{ $talep->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>

            {{-- MEVCUT TEKLİFLER --}}
            @if($talep->offers->count() > 0)
            <div class="card mb-3">
                <div class="card-header py-2 fw-bold">Mevcut Teklifler ({{ $talep->offers->count() }})</div>
                <div class="card-body py-2">
                    @foreach($talep->offers as $teklif)
                    <div class="border rounded p-2 mb-2 small {{ !$teklif->is_visible ? 'opacity-50 border-dashed' : '' }}" style="{{ !$teklif->is_visible ? 'border-style:dashed!important;' : '' }}">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <strong>{{ $teklif->airline ?? '—' }}</strong>
                                @if($teklif->airline_pnr)<span class="badge bg-primary ms-1">PNR: {{ $teklif->airline_pnr }}</span>@endif
                                @if($teklif->flight_number)<span class="badge bg-secondary ms-1">{{ $teklif->flight_number }}</span>@endif
                                @if(!$teklif->is_visible)<span class="badge bg-warning text-dark ms-1"><i class="fas fa-eye-slash me-1"></i>Gizli</span>@endif
                                @if($teklif->is_accepted)<span class="badge bg-success ms-1"><i class="fas fa-check me-1"></i>Acente Kabul Etti</span>@endif
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-1" style="font-size:0.7rem;"
                                    onclick="teklifDuzenle({{ $teklif->id }}, {{ json_encode([
                                        'airline'               => $teklif->airline,
                                        'airline_pnr'           => $teklif->airline_pnr,
                                        'flight_number'         => $teklif->flight_number,
                                        'flight_departure_time' => $teklif->flight_departure_time,
                                        'flight_arrival_time'   => $teklif->flight_arrival_time,
                                        'baggage_kg'            => $teklif->baggage_kg,
                                        'pax_confirmed'         => $teklif->pax_confirmed,
                                        'currency'              => $teklif->currency,
                                        'price_per_pax'         => $teklif->price_per_pax,
                                        'cost_price'            => $teklif->cost_price,
                                        'deposit_rate'          => $teklif->deposit_rate,
                                        'deposit_amount'        => $teklif->deposit_amount,
                                        'option_date'           => $teklif->option_date,
                                        'option_time'           => $teklif->option_time,
                                        'offer_text'            => $teklif->offer_text,
                                        'supplier_reference'    => $teklif->supplier_reference,
                                    ]) }})">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                                <form method="POST" action="{{ route('admin.requests.offer.toggle', [$talep->gtpnr, $teklif->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm py-0 px-1 {{ $teklif->is_visible ? 'btn-outline-secondary' : 'btn-outline-success' }}" style="font-size:0.7rem;"
                                        title="{{ $teklif->is_visible ? 'Acenteden gizle' : 'Acenteye göster' }}">
                                        <i class="fas {{ $teklif->is_visible ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                        {{ $teklif->is_visible ? 'Gizle' : 'Göster' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.requests.offer.delete', [$talep->gtpnr, $teklif->id]) }}" class="sil-form">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" style="font-size:0.7rem;"
                                        onclick="silOnayGoster(
                                            this.closest('form'),
                                            'Teklif silinecek',
                                            '<strong>{{ addslashes($teklif->airline ?? '—') }}</strong>' +
                                            '{{ $teklif->airline_pnr ? " · PNR: " . $teklif->airline_pnr : "" }}' +
                                            '{{ $teklif->flight_number ? " · " . $teklif->flight_number : "" }}' +
                                            '<br>{{ number_format($teklif->price_per_pax, 0) }} {{ $teklif->currency }}/kişi' +
                                            ' · {{ ($teklif->pax_confirmed ?? $talep->pax_total) }} PAX' +
                                            '{{ $teklif->option_date ? " · Opsiyon: " . $teklif->option_date : "" }}'
                                        )">
                                        <i class="fas fa-trash"></i> Sil
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="row g-1 text-muted">
                            <div class="col-6">💺 {{ $teklif->price_per_pax }} {{ $teklif->currency }}/kişi</div>
                            <div class="col-6">👥 {{ $teklif->pax_confirmed ?? $talep->pax_total }} PAX</div>
                            @if($teklif->flight_departure_time)<div class="col-6">⏰ {{ $teklif->flight_departure_time }} - {{ $teklif->flight_arrival_time }}</div>@endif
                            @if($teklif->baggage_kg)<div class="col-6">🧳 {{ $teklif->baggage_kg }} KG</div>@endif
                            @if($teklif->option_date)<div class="col-12">📅 Opsiyon: {{ $teklif->option_date }} {{ $teklif->option_time }}</div>@endif
                            @if($teklif->supplier_reference)<div class="col-12 text-danger">🔒 {{ $teklif->supplier_reference }}</div>@endif
                        </div>
                        @if($teklif->offer_text)<div class="mt-1 text-muted">{{ $teklif->offer_text }}</div>@endif
                        @if($teklif->created_by)<div class="text-muted mt-1" style="font-size:0.7rem;">Hazırlayan: {{ $teklif->created_by }}</div>@endif

                        {{-- Ham not + AI çıktısı (admin iç kontrol) --}}
                        @if($teklif->admin_raw_note || $teklif->ai_raw_output)
                        <div class="mt-2">
                            <button class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:0.7rem;"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#raw-{{ $teklif->id }}">
                                🔍 Ham Veri &amp; AI Kaydı
                            </button>
                            <div class="collapse mt-2" id="raw-{{ $teklif->id }}">
                                @if($teklif->admin_raw_note)
                                <div class="mb-2">
                                    <div class="fw-semibold" style="font-size:0.72rem;color:#6c757d;text-transform:uppercase;letter-spacing:.05em;">Ham Operasyon Notu</div>
                                    <pre class="bg-light border rounded p-2 mb-0" style="font-size:0.75rem;white-space:pre-wrap;max-height:180px;overflow-y:auto;">{{ $teklif->admin_raw_note }}</pre>
                                </div>
                                @endif
                                @if($teklif->ai_raw_output)
                                <div>
                                    <div class="fw-semibold" style="font-size:0.72rem;color:#6c757d;text-transform:uppercase;letter-spacing:.05em;">AI Ayrıştırma Çıktısı</div>
                                    <pre class="bg-light border rounded p-2 mb-0" style="font-size:0.72rem;white-space:pre-wrap;max-height:180px;overflow-y:auto;">{{ json_encode($teklif->ai_raw_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ÖDEMELER & MUHASEBE --}}
            @php
                $ilkTeklif = $talep->offers->first();
                $toplamTutar = $ilkTeklif?->total_price ?? 0;
                $toplamOdenen = $talep->payments->where('status', 'alindi')->sum('amount');
                $kalanTutar = max(0, $toplamTutar - $toplamOdenen);
                $yuzde = $toplamTutar > 0 ? min(100, round(($toplamOdenen / $toplamTutar) * 100)) : 0;
                $odenenCurrency = $ilkTeklif?->currency ?? 'TRY';
            @endphp
            <div class="card mb-3">
                <div class="card-header py-2 fw-bold d-flex justify-content-between align-items-center">
                    <span>💳 Muhasebe & Ödemeler</span>
                    <span class="badge bg-secondary">{{ $talep->payments->count() }} kayıt</span>
                </div>
                <div class="card-body py-2">

                    {{-- Muhasebe Özeti --}}
                    @if($toplamTutar > 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Tahsilat Durumu</span>
                            <span class="fw-bold">{{ number_format($toplamOdenen, 0) }} / {{ number_format($toplamTutar, 0) }} {{ $odenenCurrency }}</span>
                        </div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar {{ $yuzde >= 100 ? 'bg-success' : ($yuzde >= 50 ? 'bg-primary' : 'bg-warning') }}"
                                 style="width:{{ $yuzde }}%"></div>
                        </div>
                        <div class="row g-1 mt-2 text-center">
                            <div class="col-4">
                                <div class="bg-light rounded p-1">
                                    <div style="font-size:0.65rem;" class="text-muted">Toplam</div>
                                    <div class="fw-bold small">{{ number_format($toplamTutar, 0) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-success bg-opacity-10 rounded p-1">
                                    <div style="font-size:0.65rem;" class="text-muted">Ödenen</div>
                                    <div class="fw-bold small text-success">{{ number_format($toplamOdenen, 0) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-danger bg-opacity-10 rounded p-1">
                                    <div style="font-size:0.65rem;" class="text-muted">Kalan</div>
                                    <div class="fw-bold small text-danger">{{ number_format($kalanTutar, 0) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-2">
                    @endif

                    {{-- Ödeme Listesi --}}
                    @forelse($talep->payments as $odeme)
                    <div class="border rounded p-2 mb-2 small {{ $odeme->status === 'iade' ? 'border-danger' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $odeme->sequence }}. {{ ucfirst($odeme->payment_type) }}</strong>
                                @if($odeme->status === 'bekleniyor')
                                    <span class="badge bg-warning text-dark ms-1">Bekleniyor</span>
                                @elseif($odeme->status === 'iade')
                                    <span class="badge bg-danger ms-1">İade</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge {{ $odeme->status === 'alindi' ? 'bg-success' : ($odeme->status === 'iade' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                    {{ number_format($odeme->amount, 0) }} {{ $odeme->currency }}
                                </span>
                                <form method="POST" action="{{ route('admin.requests.payment.delete', [$talep->gtpnr, $odeme->id]) }}" class="sil-form">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" style="font-size:0.7rem;"
                                        onclick="silOnayGoster(
                                            this.closest('form'),
                                            'Ödeme kaydı silinecek',
                                            '<strong>{{ $odeme->sequence }}. {{ ucfirst($odeme->payment_type) }}</strong>' +
                                            ' · <strong>{{ number_format($odeme->amount, 0) }} {{ $odeme->currency }}</strong>' +
                                            '{{ $odeme->payment_date ? " · " . \Carbon\Carbon::parse($odeme->payment_date)->format("d.m.Y") : "" }}' +
                                            '{{ $odeme->payment_method ? " · " . $odeme->payment_method : "" }}' +
                                            '{{ $odeme->bank_name ? " · " . $odeme->bank_name : "" }}'
                                        )">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="text-muted mt-1">
                            @if($odeme->payment_method)<span>{{ $odeme->payment_method }}</span>@endif
                            @if($odeme->bank_name) · {{ $odeme->bank_name }}@endif
                            @if($odeme->payment_date) · {{ \Carbon\Carbon::parse($odeme->payment_date)->format('d.m.Y') }}@endif
                        </div>
                        @if($odeme->sender_masked)
                        <div class="text-muted">Gönderen: {{ $odeme->sender_masked }}
                            @if($odeme->account_masked) · Hesap: {{ $odeme->account_masked }}@endif
                        </div>
                        @endif
                        @if($odeme->created_by)<div class="text-muted" style="font-size:0.7rem;">Kaydeden: {{ $odeme->created_by }}</div>@endif
                    </div>
                    @empty
                    <div class="text-muted small text-center py-2">Henüz ödeme kaydı yok.</div>
                    @endforelse
                </div>
            </div>

            {{-- TİMELINE --}}
            <div class="card">
                <div class="card-header py-2 fw-bold">📅 Geçmiş</div>
                <div class="card-body py-2 small">
                    <div class="text-muted mb-1">{{ $talep->created_at->format('d.m.Y H:i') }} — Talep oluşturuldu</div>
                    @foreach($talep->logs as $log)
                    @if($log->action === 'ai_parse')
                    <div class="mb-1">
                        <span class="text-muted">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                        — <span class="badge bg-warning text-dark" style="font-size:0.68rem;">🤖 AI Parse</span>
                        @if($log->user)<span class="text-primary">· {{ $log->user->name }}</span>@endif
                        <button class="btn btn-link btn-sm p-0 ms-1" style="font-size:0.72rem;"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#log-ai-{{ $log->id }}">detay</button>
                        <div class="collapse" id="log-ai-{{ $log->id }}">
                            <pre class="bg-light border rounded p-2 mt-1 mb-0" style="font-size:0.7rem;white-space:pre-wrap;max-height:200px;overflow-y:auto;">{{ $log->description }}</pre>
                        </div>
                    </div>
                    @else
                    <div class="text-muted mb-1">
                        {{ $log->created_at->format('d.m.Y H:i') }} — {{ $log->description }}
                        @if($log->user)<span class="text-primary">· {{ $log->user->name }}</span>@endif
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            {{-- BİLDİRİMLER --}}
            @if($talep->notifications->isNotEmpty())
            <div class="card mt-3">
                <div class="card-header py-2 fw-bold d-flex justify-content-between align-items-center">
                    <span>📲 SMS Bildirimleri</span>
                    <span class="badge bg-secondary">{{ $talep->notifications->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Alıcı</th>
                                <th>Telefon</th>
                                <th>Durum</th>
                                <th>Mesaj</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($talep->notifications as $notif)
                            <tr>
                                <td class="text-nowrap">{{ $notif->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    @if($notif->recipient === 'admin')
                                        <span class="badge bg-dark">Admin</span>
                                    @else
                                        <span class="badge bg-info text-dark">{{ $notif->recipient_name }}</span>
                                    @endif
                                </td>
                                <td>{{ $notif->phone }}</td>
                                <td>
                                    @if($notif->status === 'sent')
                                        <span class="badge bg-success">Gönderildi</span>
                                    @elseif($notif->status === 'failed')
                                        <span class="badge bg-danger">Hata</span>
                                        @if($notif->provider_code)
                                            <small class="text-muted d-block">{{ $notif->provider_code }}</small>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary">Bekliyor</span>
                                    @endif
                                </td>
                                <td class="text-muted" style="max-width:220px; white-space:normal;">{{ $notif->message }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

{{-- TEKLİF DÜZENLEME MODALI --}}
<div class="modal fade" id="teklifDuzenleModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h6 class="modal-title fw-bold mb-0"><i class="fas fa-edit me-2"></i>Teklif Düzenle</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="teklif-duzenle-form">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Havayolu</label>
                            <input type="text" name="airline" id="e-airline" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Havayolu PNR</label>
                            <input type="text" name="airline_pnr" id="e-airline-pnr" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Sefer Kodu</label>
                            <input type="text" name="flight_number" id="e-flight-number" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Kalkış Saati</label>
                            <input type="time" name="flight_departure_time" id="e-dep-time" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Varış Saati</label>
                            <input type="time" name="flight_arrival_time" id="e-arr-time" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Bagaj (KG)</label>
                            <input type="number" name="baggage_kg" id="e-baggage" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Teyit PAX</label>
                            <input type="number" name="pax_confirmed" id="e-pax" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Para Birimi</label>
                            <select name="currency" id="e-currency" class="form-select form-select-sm">
                                <option value="TRY">TRY</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Kişi Başı Fiyat</label>
                            <input type="number" name="price_per_pax" id="e-price-pax" class="form-control form-control-sm" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Maliyet Fiyatı</label>
                            <input type="number" name="cost_price" id="e-cost" class="form-control form-control-sm" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Depozito Oranı (%)</label>
                            <input type="number" name="deposit_rate" id="e-deposit-rate" class="form-control form-control-sm" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Depozito Tutarı</label>
                            <input type="number" name="deposit_amount" id="e-deposit-amount" class="form-control form-control-sm" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Opsiyon Tarihi</label>
                            <input type="date" name="option_date" id="e-option-date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Opsiyon Saati</label>
                            <input type="time" name="option_time" id="e-option-time" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Teklif Notu (acenteye görünür)</label>
                            <textarea name="offer_text" id="e-offer-text" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted">Tedarikçi / İç Referans (acenteye gizli)</label>
                            <input type="text" name="supplier_reference" id="e-supplier-ref" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- SİLME ONAY MODALI --}}
<div class="modal fade" id="silOnayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Silme Onayı
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-danger fw-bold" id="sil-modal-baslik"></p>
                <div class="bg-light border rounded p-2 small" id="sil-modal-detay"></div>
                <p class="mt-3 mb-0 small text-muted">
                    <i class="fas fa-exclamation-circle text-warning me-1"></i>
                    Bu işlem <strong>geri alınamaz.</strong> Silmek istediğinizden emin misiniz?
                </p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Vazgeç
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="sil-onayla-btn">
                    <i class="fas fa-trash me-1"></i>Evet, Kalıcı Olarak Sil
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF = '{{ csrf_token() }}';
const PARSE_URL = '{{ route("admin.requests.ai-parse", $talep->gtpnr) }}';

async function aiParseBaslat() {
    const rawNote = document.getElementById('raw-note-input').value.trim();
    if (!rawNote) { alert('Önce notu yapıştır.'); return; }

    const spinner = document.getElementById('parse-spinner');
    spinner.classList.remove('d-none');

    try {
        const res  = await fetch(PARSE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ raw_note: rawNote })
        });
        const json = await res.json();
        if (json.error) { alert('Hata: ' + json.error); return; }

        const d = json.data;

        // Teklif formu
        if (d.airline)               document.getElementById('f-airline').value          = d.airline;
        if (d.airline_pnr)           document.getElementById('f-airline-pnr').value      = d.airline_pnr;
        if (d.flight_number)         document.getElementById('f-flight-number').value    = d.flight_number;
        if (d.flight_departure_time) document.getElementById('f-dep-time').value         = d.flight_departure_time;
        if (d.flight_arrival_time)   document.getElementById('f-arr-time').value         = d.flight_arrival_time;
        if (d.baggage_kg)            document.getElementById('f-baggage').value          = d.baggage_kg;
        if (d.pax_confirmed)         document.getElementById('f-pax').value              = d.pax_confirmed;
        if (d.price_per_pax)         document.getElementById('f-price-pax').value        = d.price_per_pax;
        if (d.currency)              document.getElementById('f-currency').value         = d.currency;
        if (d.supplier_reference)    document.getElementById('f-supplier-ref').value     = d.supplier_reference;
        if (d.ticketing_deadline || d.balance_deadline) {
            const dl = d.ticketing_deadline || d.balance_deadline;
            const parts = dl.split(' ');
            document.getElementById('f-option-date').value = parts[0] ?? '';
            document.getElementById('f-option-time').value = parts[1] ?? '';
        }
        document.getElementById('f-raw-note').value     = rawNote;
        document.getElementById('f-ai-raw-output').value = JSON.stringify(d);

        // Ödeme formu (bağımsız form)
        if (d.payment_method) document.getElementById('p-method').value   = d.payment_method;
        if (d.bank_name)      document.getElementById('p-bank').value     = d.bank_name;
        if (d.sender_masked)  document.getElementById('p-sender').value   = d.sender_masked;
        if (d.account_masked) document.getElementById('p-account').value  = d.account_masked;
        if (d.payment_amount) document.getElementById('p-amount').value   = d.payment_amount;
        if (d.payment_currency) document.getElementById('p-currency').value = d.payment_currency;
        if (d.payment_date)   document.getElementById('p-date').value     = d.payment_date;
        if (d.payment_sequence) document.getElementById('p-sequence').value = d.payment_sequence;

        // Teklif formundaki gizli ödeme alanları — "Teklifi Kaydet" ile otomatik kaydedilir
        if (d.payment_amount) {
            document.getElementById('f-p-amount').value   = d.payment_amount;
            document.getElementById('f-p-currency').value = d.payment_currency || 'TRY';
            document.getElementById('f-p-date').value     = d.payment_date || '';
            document.getElementById('f-p-method').value   = d.payment_method || '';
            document.getElementById('f-p-bank').value     = d.bank_name || '';
            document.getElementById('f-p-sender').value   = d.sender_masked || '';
            document.getElementById('f-p-account').value  = d.account_masked || '';
            document.getElementById('f-p-sequence').value = d.payment_sequence || 1;
            document.getElementById('f-p-type').value     = 'depozito';
            document.getElementById('ai-odeme-bilgisi').classList.remove('d-none');
        }

        // Alanları sarı renkle vurgula
        document.querySelectorAll('#offer-form input, #offer-form select, #offer-form textarea, #payment-form input, #payment-form select').forEach(el => {
            if (el.value) el.classList.add('bg-warning', 'bg-opacity-25');
        });

    } catch(e) {
        alert('İstek hatası: ' + e.message);
    } finally {
        spinner.classList.add('d-none');
    }
}

function formuTemizle() {
    document.getElementById('offer-form').reset();
    document.getElementById('ai-odeme-bilgisi').classList.add('d-none');
    document.querySelectorAll('#offer-form input, #offer-form select, #offer-form textarea').forEach(el => {
        el.classList.remove('bg-warning', 'bg-opacity-25');
    });
}

// Teklif düzenleme modali
const teklifDuzenleModal = new bootstrap.Modal(document.getElementById('teklifDuzenleModal'));
const BASE_URL = '{{ url("admin/talepler/" . $talep->gtpnr . "/teklif") }}';

function teklifDuzenle(id, data) {
    document.getElementById('teklif-duzenle-form').action = BASE_URL + '/' + id;
    document.getElementById('e-airline').value        = data.airline || '';
    document.getElementById('e-airline-pnr').value    = data.airline_pnr || '';
    document.getElementById('e-flight-number').value  = data.flight_number || '';
    document.getElementById('e-dep-time').value       = data.flight_departure_time || '';
    document.getElementById('e-arr-time').value       = data.flight_arrival_time || '';
    document.getElementById('e-baggage').value        = data.baggage_kg || '';
    document.getElementById('e-pax').value            = data.pax_confirmed || '';
    document.getElementById('e-currency').value       = data.currency || 'TRY';
    document.getElementById('e-price-pax').value      = data.price_per_pax || '';
    document.getElementById('e-cost').value           = data.cost_price || '';
    document.getElementById('e-deposit-rate').value   = data.deposit_rate || '';
    document.getElementById('e-deposit-amount').value = data.deposit_amount || '';
    document.getElementById('e-option-date').value    = data.option_date || '';
    document.getElementById('e-option-time').value    = data.option_time || '';
    document.getElementById('e-offer-text').value     = data.offer_text || '';
    document.getElementById('e-supplier-ref').value   = data.supplier_reference || '';
    teklifDuzenleModal.show();
}

// Silme onay modali
let silFormPending = null;
const silModal = new bootstrap.Modal(document.getElementById('silOnayModal'));

function silOnayGoster(form, baslik, detay) {
    silFormPending = form;
    document.getElementById('sil-modal-baslik').textContent = baslik;
    document.getElementById('sil-modal-detay').innerHTML = detay;
    silModal.show();
}

document.getElementById('sil-onayla-btn').addEventListener('click', function () {
    if (silFormPending) {
        silModal.hide();
        silFormPending.submit();
        silFormPending = null;
    }
});
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hızlı Yanıtla</title>
    @include('admin.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .qr-card { border-radius: 14px; border: 1px solid rgba(0,0,0,.08); }
        .qr-box { border: 1px solid rgba(0,0,0,.1); border-radius: 12px; padding: 12px; background: #fff; }
        .qr-label { font-size: .76rem; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; }
        .qr-value { font-size: .95rem; font-weight: 600; word-break: break-word; }
        .qr-table td, .qr-table th { vertical-align: middle; font-size: .86rem; }
        .agency-suggest {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 4px);
            z-index: 20;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            max-height: 220px;
            overflow-y: auto;
            box-shadow: 0 8px 20px rgba(0,0,0,.08);
        }
        .agency-suggest-item {
            display: block;
            width: 100%;
            border: 0;
            background: #fff;
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f3f5;
            cursor: pointer;
        }
        .agency-suggest-item:hover { background: #f8f9fa; }
        .agency-suggest-item:last-child { border-bottom: 0; }
        @media (max-width: 576px) {
            .qr-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body class="theme-scope">
@if(auth()->user()->role === 'superadmin')
    <x-navbar-superadmin active="quick-reply" />
@else
    <x-navbar-admin active="quick-reply" />
@endif

<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-bolt text-warning me-2"></i>Hızlı Yanıtla</h4>
            <div class="text-muted small">AI destekli ayrıştırma + insan onaylı kayıt akışı.</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3">
            <div class="fw-bold mb-1">Form doğrulama hatası:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card qr-card shadow-sm mb-4">
        <div class="card-header fw-bold">1) Havayolu Cevabını Yapıştır ve Ayrıştır</div>
        <div class="card-body">
            <form method="POST" action="{{ route($routeNamePrefix . '.parse') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-lg-6 position-relative">
                        <label class="form-label">Acente Seç (opsiyonel, doğruluk artırır)</label>
                        <input type="text" id="manual_agency_search" class="form-control" placeholder="Acente adı yazın...">
                        <input type="hidden" name="manual_agency_id" id="manual_agency_id">
                        <div id="agencySuggestBox" class="agency-suggest d-none"></div>
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="form-label">Üyelik tipi</label>
                        <select name="membership_mode" class="form-select">
                            @foreach($membershipModes as $modeKey => $modeLabel)
                                <option value="{{ $modeKey }}">{{ $modeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Yapıştırılan Havayolu Cevabı</label>
                        <textarea name="raw_text" rows="8" class="form-control" required placeholder="Örn:
ECCO
27 pax AJET SAW ECN SAW
kişi başı:9452 tl
fiyat teklif opsiyonu: 19 mart saat 12:00
VF153 14/05/2026 SAW - ECN 07:15-08:45"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary">
                        <i class="fas fa-robot me-1"></i>AI ile Ayrıştır
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card qr-card shadow-sm mb-4">
        <div class="card-header fw-bold">Son Oturumlar</div>
        <div class="card-body p-0">
            @if($recentSessions->isEmpty())
                <div class="p-3 text-muted">Henüz Hızlı Yanıtla oturumu yok.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0 qr-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Durum</th>
                            <th>Güven</th>
                            <th>Üyelik</th>
                            <th>Talep</th>
                            <th>Tarih</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($recentSessions as $sessionItem)
                            <tr>
                                <td>{{ $sessionItem->id }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($sessionItem->status) }}</span></td>
                                <td>{{ $sessionItem->match_confidence ?? '-' }}</td>
                                <td>{{ $sessionItem->resolved_membership }}</td>
                                <td>{{ $sessionItem->selectedRequest?->gtpnr ?? '-' }}</td>
                                <td>{{ $sessionItem->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route($routeNamePrefix . '.index', ['session' => $sessionItem->id]) }}">
                                        Aç
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($activeSession)
        @php
            $payload = $activeSession->edited_payload ?: $activeSession->parsed_payload ?: [];
            $agencyCandidates = collect($activeSession->agency_candidates ?: []);
            $requestCandidates = collect($activeSession->request_candidates ?: []);
            $flightLines = collect($payload['flight_lines'] ?? [])->filter(fn($f) => is_array($f));
        @endphp

        <div class="card qr-card shadow-sm mb-4">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span>2) Önizleme ve Doğrulama (Oturum #{{ $activeSession->id }})</span>
                <span class="badge bg-dark">Skor: {{ $activeSession->match_confidence ?? '0' }}</span>
            </div>
            <div class="card-body">
                <div class="d-grid qr-grid gap-2 mb-3" style="grid-template-columns:repeat(3,minmax(0,1fr));">
                    <div class="qr-box"><div class="qr-label">Acente</div><div class="qr-value">{{ $payload['agency_name'] ?? 'bulunamadı' }}</div></div>
                    <div class="qr-box"><div class="qr-label">GT PNR</div><div class="qr-value">{{ $payload['gtpnr'] ?? 'bulunamadı' }}</div></div>
                    <div class="qr-box"><div class="qr-label">PAX</div><div class="qr-value">{{ $payload['pax'] ?? 'bulunamadı' }}</div></div>
                    <div class="qr-box"><div class="qr-label">Rota</div><div class="qr-value">{{ ($payload['from_iata'] ?? '-') . ' - ' . ($payload['to_iata'] ?? '-') }}</div></div>
                    <div class="qr-box"><div class="qr-label">Gidiş</div><div class="qr-value">{{ $payload['departure_date'] ?? 'bulunamadı' }}</div></div>
                    <div class="qr-box"><div class="qr-label">Dönüş</div><div class="qr-value">{{ $payload['return_date'] ?? 'bulunamadı' }}</div></div>
                    <div class="qr-box"><div class="qr-label">Fiyat</div><div class="qr-value">{{ $payload['price_per_pax'] ?? 'bulunamadı' }}</div></div>
                    <div class="qr-box"><div class="qr-label">Para Birimi</div><div class="qr-value">{{ $payload['currency'] ?? 'bulunamadı' }}</div></div>
                    <div class="qr-box"><div class="qr-label">Havayolu</div><div class="qr-value">{{ $payload['airline'] ?? 'bulunamadı' }}</div></div>
                </div>

                @if($flightLines->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm qr-table">
                            <thead>
                            <tr>
                                <th>Sefer</th>
                                <th>Tarih</th>
                                <th>Rota</th>
                                <th>Saat</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($flightLines as $line)
                                <tr>
                                    <td>{{ $line['flight_number'] ?? '-' }}</td>
                                    <td>{{ $line['departure_date'] ?? '-' }}</td>
                                    <td>{{ ($line['from_iata'] ?? '-') . ' - ' . ($line['to_iata'] ?? '-') }}</td>
                                    <td>{{ ($line['departure_time'] ?? '-') . ' - ' . ($line['arrival_time'] ?? '-') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-12 col-xl-6">
                        <div class="qr-box h-100">
                            <div class="fw-semibold mb-2">Acente Adayları</div>
                            @if($agencyCandidates->isEmpty())
                                <div class="text-muted small">Aday bulunamadı.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm qr-table mb-0">
                                        <thead><tr><th>Acente</th><th>İrtibat</th><th>Skor</th></tr></thead>
                                        <tbody>
                                        @foreach($agencyCandidates as $cand)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $cand['agency_name'] ?? '-' }}</div>
                                                    <div class="small text-muted">{{ $cand['user_name'] ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    <div class="small">{{ $cand['phone'] ?? '-' }}</div>
                                                    <div class="small text-muted">{{ $cand['email'] ?? '-' }}</div>
                                                </td>
                                                <td><span class="badge bg-info text-dark">{{ $cand['score'] ?? 0 }}</span></td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-xl-6">
                        <div class="qr-box h-100">
                            <div class="fw-semibold mb-2">Talep Adayları</div>
                            @if($requestCandidates->isEmpty())
                                <div class="text-muted small">Aday talep bulunamadı.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm qr-table mb-0">
                                        <thead><tr><th>GTPNR</th><th>Rota</th><th>Gidiş</th><th>PAX</th><th>Skor</th></tr></thead>
                                        <tbody>
                                        @foreach($requestCandidates as $cand)
                                            <tr>
                                                <td>{{ $cand['gtpnr'] ?? '-' }}</td>
                                                <td>{{ ($cand['from_iata'] ?? '-') . ' - ' . ($cand['to_iata'] ?? '-') }}</td>
                                                <td>{{ $cand['departure_date'] ?? '-' }}</td>
                                                <td>{{ $cand['pax_total'] ?? '-' }}</td>
                                                <td><span class="badge bg-primary">{{ $cand['score'] ?? 0 }}</span></td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card qr-card shadow-sm mb-4">
            <div class="card-header fw-bold">3) Düzeltme ve Seçim</div>
            <div class="card-body">
                <form method="POST" action="{{ route($routeNamePrefix . '.save-review', $activeSession) }}" class="js-quick-reply-sync">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Üyelik tipi</label>
                            <select name="membership_mode" class="form-select">
                                @foreach($membershipModes as $modeKey => $modeLabel)
                                    <option value="{{ $modeKey }}" @selected($activeSession->membership_mode === $modeKey)>{{ $modeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Talep / GTPNR</label>
                            <select name="selected_request_id" class="form-select js-request-select">
                                <option value="">Seçiniz</option>
                                @foreach($requestCandidates as $cand)
                                    <option value="{{ $cand['request_id'] }}"
                                            data-request-user-id="{{ $cand['user_id'] ?? '' }}"
                                            data-request-agency-id="{{ $cand['agency_id'] ?? '' }}"
                                            data-request-agency-name="{{ $cand['agency_name'] ?? '' }}"
                                            @selected((int)$activeSession->selected_request_id === (int)$cand['request_id'])>
                                        {{ $cand['gtpnr'] }}
                                        · {{ ($cand['from_iata'] ?? '-') . '-' . ($cand['to_iata'] ?? '-') }}
                                        · PAX {{ $cand['pax_total'] ?? '-' }}
                                        · {{ $cand['score'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Talep seçildiğinde acente adayı ve kullanıcı ID otomatik doldurulur.</div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Acente adayı</label>
                            <select name="selected_agency_id" class="form-select js-agency-select">
                                <option value="">Seçiniz</option>
                                @foreach($agencyCandidates as $cand)
                                    <option value="{{ $cand['agency_id'] }}" @selected((int)$activeSession->selected_agency_id === (int)$cand['agency_id'])>
                                        {{ $cand['agency_name'] }} · {{ $cand['score'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Manuel kullanıcı ID (opsiyonel)</label>
                            <input type="number" class="form-control js-user-id-input" name="selected_user_id" value="{{ $activeSession->selected_user_id }}">
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Acente</label>
                            <input type="text" class="form-control" name="edited_payload[agency_name]" value="{{ $payload['agency_name'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">PAX</label>
                            <input type="number" class="form-control" name="edited_payload[pax]" value="{{ $payload['pax'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">Para Birimi</label>
                            <input type="text" class="form-control" name="edited_payload[currency]" value="{{ $payload['currency'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Havayolu</label>
                            <input type="text" class="form-control" name="edited_payload[airline]" value="{{ $payload['airline'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Fiyat (Kişi Başı)</label>
                            <input type="number" step="0.01" class="form-control" name="edited_payload[price_per_pax]" value="{{ $payload['price_per_pax'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">From</label>
                            <input type="text" class="form-control" name="edited_payload[from_iata]" value="{{ $payload['from_iata'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">To</label>
                            <input type="text" class="form-control" name="edited_payload[to_iata]" value="{{ $payload['to_iata'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Gidiş Tarihi</label>
                            <input type="date" class="form-control" name="edited_payload[departure_date]" value="{{ $payload['departure_date'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Dönüş Tarihi</label>
                            <input type="date" class="form-control" name="edited_payload[return_date]" value="{{ $payload['return_date'] ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Teklif Metni (opsiyonel)</label>
                            <textarea class="form-control" rows="3" name="edited_payload[offer_text]">{{ $payload['offer_text'] ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-save me-1"></i>Düzeltmeleri Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card qr-card shadow-sm mb-5">
            <div class="card-header fw-bold">4) Son Onay ve Kayıt</div>
            <div class="card-body">
                <form method="POST" action="{{ route($routeNamePrefix . '.confirm', $activeSession) }}" class="js-quick-reply-sync">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Üyelik tipi</label>
                            <select name="membership_mode" class="form-select" required>
                                @foreach($membershipModes as $modeKey => $modeLabel)
                                    <option value="{{ $modeKey }}" @selected($activeSession->membership_mode === $modeKey)>{{ $modeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Seçilen Talep</label>
                            <select name="selected_request_id" class="form-select js-request-select" required>
                                <option value="">Seçiniz</option>
                                @foreach($requestCandidates as $cand)
                                    <option value="{{ $cand['request_id'] }}"
                                            data-request-user-id="{{ $cand['user_id'] ?? '' }}"
                                            data-request-agency-id="{{ $cand['agency_id'] ?? '' }}"
                                            data-request-agency-name="{{ $cand['agency_name'] ?? '' }}"
                                            @selected((int)$activeSession->selected_request_id === (int)$cand['request_id'])>
                                        {{ $cand['gtpnr'] }}
                                        · {{ ($cand['from_iata'] ?? '-') . '-' . ($cand['to_iata'] ?? '-') }}
                                        · PAX {{ $cand['pax_total'] ?? '-' }}
                                        · {{ $cand['score'] }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Talep seçildiğinde acente adayı ve kullanıcı ID otomatik doldurulur.</div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Seçilen Acente (opsiyonel)</label>
                            <select name="selected_agency_id" class="form-select js-agency-select">
                                <option value="">Seçiniz</option>
                                @foreach($agencyCandidates as $cand)
                                    <option value="{{ $cand['agency_id'] }}" @selected((int)$activeSession->selected_agency_id === (int)$cand['agency_id'])>
                                        {{ $cand['agency_name'] }} · {{ $cand['score'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Seçilen Kullanıcı ID (opsiyonel)</label>
                            <input type="number" class="form-control js-user-id-input" name="selected_user_id" value="{{ $activeSession->selected_user_id }}">
                        </div>
                        <div class="col-12">
                            <hr>
                            <div class="fw-semibold mb-2">Teklif Alanları</div>
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Havayolu</label>
                            <input type="text" class="form-control" name="offer[airline]" value="{{ $payload['airline'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Airline PNR</label>
                            <input type="text" class="form-control" name="offer[airline_pnr]" value="{{ $payload['airline_pnr'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Flight Number</label>
                            <input type="text" class="form-control" name="offer[flight_number]" value="{{ $payload['flight_lines'][0]['flight_number'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">PAX</label>
                            <input type="number" class="form-control" name="offer[pax_confirmed]" value="{{ $payload['pax'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Fiyat (Kişi Başı)</label>
                            <input type="number" step="0.01" class="form-control" name="offer[price_per_pax]" value="{{ $payload['price_per_pax'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">Para Birimi</label>
                            <input type="text" class="form-control" name="offer[currency]" value="{{ $payload['currency'] ?? 'TRY' }}">
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">Opsiyon Tarihi</label>
                            <input type="date" class="form-control" name="offer[option_date]" value="{{ $payload['option_date'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-2">
                            <label class="form-label">Opsiyon Saati</label>
                            <input type="time" class="form-control" name="offer[option_time]" value="{{ $payload['option_time'] ?? '' }}">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Tedarikçi Ref.</label>
                            <input type="text" class="form-control" name="offer[supplier_reference]" value="{{ $payload['supplier_reference'] ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Teklif Notu</label>
                            <textarea class="form-control" rows="3" name="offer[offer_text]">{{ $payload['offer_text'] ?? '' }}</textarea>
                        </div>
                        <div class="col-12">
                            <hr>
                            <div class="fw-semibold mb-2">Üye Olmayan Acente İçin Yeni Kayıt Bilgileri</div>
                            <div class="small text-muted mb-2">Üyelik tipi "Üye Olmayan Acenteye Yanıt" seçildiğinde bu alanlar zorunludur.</div>
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Acente Adı</label>
                            <input type="text" class="form-control" name="new_account[agency_name]" placeholder="Örn: ECCO Turizm">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Yetkili Ad Soyad</label>
                            <input type="text" class="form-control" name="new_account[contact_name]" placeholder="Örn: Ayşe Yılmaz">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">Cep Telefonu</label>
                            <input type="text" class="form-control" name="new_account[phone]" placeholder="905xxxxxxxxx">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="new_account[email]" placeholder="ornek@acente.com">
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3 mb-3">
                        @if($activeSession->selectedRequest)
                            <strong>Onay:</strong>
                            Bu cevabı <strong>{{ $activeSession->selectedRequest->agency_name }}</strong> acentesinin
                            <strong>{{ $activeSession->selectedRequest->gtpnr }}</strong> talebine cevap olarak kaydediyorum.
                            Onaylıyor musunuz?
                        @else
                            <strong>Onay:</strong> Bu cevap için seçtiğiniz talebe kayıt yapacağım. Onaylıyor musunuz?
                        @endif
                        <div class="small mt-1">İşlem sonrası mevcut teklif akışı tetiklenir (SMS, e-posta, push ve log süreçleri dahil).</div>
                    </div>
                    <button class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Evet, bu talebe cevap olarak kaydet
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>

<script>
    (function () {
        const searchInput = document.getElementById('manual_agency_search');
        const hiddenIdInput = document.getElementById('manual_agency_id');
        const suggestBox = document.getElementById('agencySuggestBox');
        if (!searchInput || !hiddenIdInput || !suggestBox) return;

        let timer = null;
        let currentReq = null;

        const closeSuggest = () => {
            suggestBox.classList.add('d-none');
            suggestBox.innerHTML = '';
        };

        const renderItems = (items) => {
            if (!items.length) {
                closeSuggest();
                return;
            }

            suggestBox.innerHTML = '';
            items.forEach((item) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'agency-suggest-item';
                btn.innerHTML = `<div class="fw-semibold">${item.name}</div>
                                 <div class="small text-muted">${item.user_name || '-'} · ${item.phone || '-'} · ${item.email || '-'}</div>`;
                btn.addEventListener('click', () => {
                    searchInput.value = item.name;
                    hiddenIdInput.value = item.id;
                    closeSuggest();
                });
                suggestBox.appendChild(btn);
            });
            suggestBox.classList.remove('d-none');
        };

        searchInput.addEventListener('input', () => {
            hiddenIdInput.value = '';
            const q = searchInput.value.trim();
            if (q.length < 2) {
                closeSuggest();
                return;
            }

            if (timer) clearTimeout(timer);
            timer = setTimeout(async () => {
                try {
                    if (currentReq) currentReq.abort();
                    currentReq = new AbortController();
                    const endpoint = @json(route($routeNamePrefix . '.agency-search'));
                    const res = await fetch(`${endpoint}?q=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json' },
                        signal: currentReq.signal
                    });
                    if (!res.ok) return closeSuggest();
                    const data = await res.json();
                    renderItems(data.items || []);
                } catch (e) {
                    closeSuggest();
                }
            }, 250);
        });

        document.addEventListener('click', (e) => {
            if (!suggestBox.contains(e.target) && e.target !== searchInput) {
                closeSuggest();
            }
        });
    })();

    (function () {
        const forms = document.querySelectorAll('.js-quick-reply-sync');
        if (!forms.length) return;

        const ensureAgencyOption = (agencySelect, agencyId, agencyName) => {
            if (!agencySelect || !agencyId) return;
            const exists = Array.from(agencySelect.options).some((opt) => String(opt.value) === String(agencyId));
            if (exists) return;

            const option = document.createElement('option');
            option.value = String(agencyId);
            option.textContent = `${agencyName || ('Acente #' + agencyId)} · otomatik`;
            agencySelect.appendChild(option);
        };

        const bindForm = (form) => {
            const requestSelect = form.querySelector('.js-request-select');
            const agencySelect = form.querySelector('.js-agency-select');
            const userInput = form.querySelector('.js-user-id-input');
            if (!requestSelect) return;

            const syncFromRequest = () => {
                const option = requestSelect.options[requestSelect.selectedIndex];
                if (!option || !option.value) {
                    return;
                }

                const requestUserId = option.dataset.requestUserId || '';
                const requestAgencyId = option.dataset.requestAgencyId || '';
                const requestAgencyName = option.dataset.requestAgencyName || '';

                if (userInput && requestUserId) {
                    userInput.value = requestUserId;
                }

                if (agencySelect && requestAgencyId) {
                    ensureAgencyOption(agencySelect, requestAgencyId, requestAgencyName);
                    agencySelect.value = requestAgencyId;
                }
            };

            requestSelect.addEventListener('change', syncFromRequest);
            syncFromRequest();
        };

        forms.forEach(bindForm);
    })();
</script>

@include('admin.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

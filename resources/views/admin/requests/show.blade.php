<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $talep->gtpnr }} — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .lbl { font-size: 0.72rem; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; }
        .offer-card { border-left: 4px solid #0d6efd; border-radius: 6px; }
        .offer-card.offer-hidden { border-left-color: #adb5bd; opacity: .8; }
        .offer-card.offer-accepted { border-left-color: #198754; }
        .section-divider { font-size: 0.7rem; text-transform: uppercase; letter-spacing: .08em; color: #6c757d; font-weight: 600; padding: .25rem 0; border-bottom: 1px solid rgba(0,0,0,.1); margin-bottom: .75rem; }
    </style>
</head>
<body>

<x-navbar-admin active="talepler" />

@php
    $durumEtiketleri = ['beklemede'=>'Beklemede','islemde'=>'İşlemde','fiyatlandirildi'=>'Fiyatlandırıldı','onaylandi'=>'Onaylandı','depozitoda'=>'Depozitoda','biletlendi'=>'Biletlendi','iade'=>'İade','olumsuz'=>'Olumsuz'];
    $durumRenkleri   = ['beklemede'=>'#6c757d','islemde'=>'#0d6efd','fiyatlandirildi'=>'#ffc107','onaylandi'=>'#0d6efd','depozitoda'=>'#6f42c1','biletlendi'=>'#198754','iade'=>'#dc3545','olumsuz'=>'#343a40'];
    $durumTextRenk   = ['fiyatlandirildi'=>'#000'];

    $yoneticiMesajlari = $talep->offers->pluck('offer_text')->filter(fn($m) => filled(trim((string)$m)));
    $acenteNotu = filled(trim((string)$talep->notes)) ? trim((string)$talep->notes) : null;
    if ($acenteNotu && $yoneticiMesajlari->contains(fn($m) => trim((string)$m) === $acenteNotu)) {
        $acenteNotu = null;
    }

    $kabulEdilenTeklif = $talep->offers->firstWhere('is_accepted', true);
    $ilkTeklif      = $kabulEdilenTeklif ?? $talep->offers->first();
    $toplamTutar    = $ilkTeklif?->total_price ?? 0;
    $toplamOdenen   = $talep->payments->where('status', 'alindi')->sum('amount');
    $kalanTutar     = max(0, $toplamTutar - $toplamOdenen);
    $yuzde          = $toplamTutar > 0 ? min(100, round(($toplamOdenen / $toplamTutar) * 100)) : 0;
    $odenenCurrency   = $ilkTeklif?->currency ?? 'TRY';
    $toplamBekleniyor = $talep->payments->where('status', 'bekleniyor')->sum('amount');
    $planlanmamisKalan = max(0, $toplamTutar - $toplamOdenen - $toplamBekleniyor);
    $hicBekleniyor    = $talep->payments->where('status', 'bekleniyor')->count() === 0;
    $maxSequence      = $talep->payments->max('sequence') ?? 0;
@endphp

<div class="container-fluid px-3 px-md-4 py-3">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if($kabulEdilenTeklif && $kabulEdilenTeklif->accepted_at)
    <div class="alert alert-success py-2 px-3 mb-3 d-flex align-items-center gap-2">
        <i class="fas fa-check-circle fs-5"></i>
        <div>
            <strong>Acenta teklifi kabul etti:</strong>
            {{ $kabulEdilenTeklif->airline ?? '—' }} —
            {{ number_format($kabulEdilenTeklif->price_per_pax, 0) }} {{ $kabulEdilenTeklif->currency }}/kişi
            <span class="text-muted ms-2 small">
                {{ \Carbon\Carbon::parse($kabulEdilenTeklif->accepted_at)->format('d.m.Y H:i') }}
            </span>
        </div>
    </div>
    @endif

    {{-- ── BAŞLIK ── --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary btn-sm py-0 px-2">← Liste</a>
                <h4 class="fw-bold mb-0">{{ $talep->gtpnr }}</h4>
                <span class="badge" style="background-color:{{ $durumRenkleri[$talep->status] ?? '#6c757d' }};color:{{ $durumTextRenk[$talep->status] ?? '#fff' }};">
                    {{ $durumEtiketleri[$talep->status] ?? $talep->status }}
                </span>
                <x-iade-badge :talep="$talep" />
            </div>
            <div class="text-muted small">
                @if($talep->agency_name === 'MÜNFERİT')
                    <span class="badge" style="background:#20c997;color:#fff;">MÜNFERİT</span>
                @else
                    {{ $talep->agency_name }}
                @endif
                · {{ $talep->pax_total }} PAX
                @if($talep->segments->isNotEmpty())
                    · {{ $talep->segments->first()->from_iata }} → {{ $talep->segments->last()->to_iata }}
                    · {{ \Carbon\Carbon::parse($talep->segments->first()->departure_date)->format('d M Y') }}
                @endif
                · {{ $talep->created_at->format('d.m.Y') }}
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <a href="{{ route('acente.preview.request', $talep->gtpnr) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                Acente Görünümü →
            </a>
            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#talepDuzenleModal">
                <i class="fas fa-edit me-1"></i>Talebi Düzenle
            </button>
            <form method="POST" action="{{ route('admin.requests.destroy', $talep->gtpnr) }}" id="talep-sil-form">
                @csrf @method('DELETE')
                <button type="button" class="btn btn-outline-danger btn-sm"
                    onclick="silOnayGoster(document.getElementById('talep-sil-form'), 'Talep silinecek', '<strong>{{ $talep->gtpnr }}</strong> — {{ $talep->agency_name }} · {{ $talep->pax_total }} PAX<br><span class=\'text-danger small\'>Tüm teklifler, ödemeler ve segmentler de silinir.</span>')">
                    <i class="fas fa-trash me-1"></i>Talebi Sil
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3">

        {{-- ════════════════════ SOL SÜTUN ════════════════════ --}}
        <div class="col-lg-4 col-md-5">

            {{-- Talep Bilgileri --}}
            <div class="card mb-3">
                <div class="card-header py-2 fw-semibold">📋 Talep Bilgileri</div>
                <div class="card-body py-2 small">
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="lbl">Acente</div>
                            <div class="fw-bold">
                                @if($talep->agency_name === 'MÜNFERİT')
                                    <span class="badge" style="background:#20c997;color:#fff;">MÜNFERİT</span>
                                @else
                                    {{ $talep->agency_name }}
                                @endif
                            </div>
                        </div>
                        <div class="col-6"><div class="lbl">Telefon</div><div>{{ $talep->phone }}</div></div>
                        <div class="col-6"><div class="lbl">E-posta</div><div style="word-break:break-all;">{{ $talep->email }}</div></div>
                        <div class="col-4">
                            <div class="lbl">PAX</div>
                            <div class="fw-bold">{{ $talep->pax_total }}</div>
                            <div class="text-muted" style="font-size:.7rem;">Y:{{ $talep->pax_adult }} Ç:{{ $talep->pax_child }} B:{{ $talep->pax_infant }}</div>
                        </div>
                        @if($talep->group_company_name)
                        <div class="col-8"><div class="lbl">Grup Firma</div><div>{{ $talep->group_company_name }}</div></div>
                        @endif
                        @if($talep->flight_purpose)
                        <div class="col-6"><div class="lbl">Uçuş Amacı</div><div>{{ $talep->flight_purpose }}</div></div>
                        @endif
                        <div class="col-6"><div class="lbl">Talep Tarihi</div><div>{{ $talep->created_at->format('d.m.Y H:i') }}</div></div>
                    </div>
                    @if($acenteNotu)
                    <hr class="my-2">
                    <div class="lbl mb-1" style="text-transform:none;font-size:.75rem;color:#6c757d;">
                        📝 Acente Notu <span class="fw-normal">(taleple birlikte gönderildi)</span>
                    </div>
                    <div class="rounded p-2" style="background:rgba(255,193,7,.1);border-left:3px solid #ffc107;white-space:pre-line;">{{ $acenteNotu }}</div>
                    @endif
                </div>
            </div>

            {{-- Uçuş Segmentleri --}}
            <div class="card mb-3">
                <div class="card-header py-2 fw-semibold">✈️ Uçuş Segmentleri</div>
                <div class="card-body py-2">
                    @foreach($talep->segments as $s)
                    <div class="d-flex align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <span class="fw-bold fs-5">{{ $s->from_iata }}</span>
                        <i class="fas fa-arrow-right text-muted small"></i>
                        <span class="fw-bold fs-5">{{ $s->to_iata }}</span>
                        <div class="ms-auto text-end small text-muted">
                            <div>{{ \Carbon\Carbon::parse($s->departure_date)->format('d M Y') }}</div>
                            @if($s->departure_time_slot)
                            @php $slotLabel = ['sabah'=>'🌅 Sabah','ogle'=>'☀️ Öğle','aksam'=>'🌆 Akşam','esnek'=>'🔄 Esnek'][$s->departure_time_slot] ?? $s->departure_time_slot; @endphp
                            <span class="badge bg-info text-dark" style="font-size:.65rem;">{{ $slotLabel }}</span>
                            @elseif($s->departure_time)
                            <div>{{ $s->departure_time }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Durum Güncelle --}}
            <div class="card mb-3">
                <div class="card-header py-2 fw-semibold">🔄 Durum Güncelle</div>
                <div class="card-body py-2">
                    @if($talep->status === 'biletlendi')
                        <div class="alert alert-warning py-2 mb-0 small"><i class="fas fa-lock me-1"></i>Biletlenmiş talep değiştirilemez.</div>
                    @else
                    <form method="POST" action="{{ route('admin.requests.status', $talep->gtpnr) }}">
                        @csrf
                        <div class="input-group input-group-sm">
                            <select name="status" class="form-select">
                                @foreach(['beklemede'=>'Beklemede','islemde'=>'İşlemde','fiyatlandirildi'=>'Fiyatlandırıldı','onaylandi'=>'Onaylandı','depozitoda'=>'Depozitoda','biletlendi'=>'Biletlendi','iade'=>'İade','olumsuz'=>'Olumsuz'] as $val => $label)
                                <option value="{{ $val }}" {{ $talep->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_email_acente" value="1" id="status-notif-email">
                                <label class="form-check-label small" for="status-notif-email">E-posta gönder — Acente</label>
                            </div>
                            <div class="text-muted small mt-1" id="status-notif-info">
                                <i class="fas fa-bell-slash me-1"></i>Bildirim gönderilmeyecek
                            </div>
                        </div>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Geçmiş --}}
            <div class="card mb-3">
                <div class="card-header py-2 fw-semibold">📅 Geçmiş</div>
                <div class="card-body py-2 small">
                    <div class="text-muted mb-1">{{ $talep->created_at->format('d.m.Y H:i') }} — Talep oluşturuldu</div>
                    @foreach($talep->logs as $log)
                    @if($log->action === 'ai_parse')
                    <div class="mb-1">
                        <span class="text-muted">{{ $log->created_at->format('d.m.Y H:i') }}</span>
                        — <span class="badge bg-warning text-dark" style="font-size:.68rem;">🤖 AI Parse</span>
                        @if($log->user)<span class="text-primary small">· {{ $log->user->name }}</span>@endif
                        <button class="btn btn-link btn-sm p-0 ms-1" style="font-size:.72rem;"
                                type="button" data-bs-toggle="collapse" data-bs-target="#log-ai-{{ $log->id }}">detay</button>
                        <div class="collapse" id="log-ai-{{ $log->id }}">
                            <pre class="bg-light border rounded p-2 mt-1 mb-0" style="font-size:.7rem;white-space:pre-wrap;max-height:200px;overflow-y:auto;">{{ $log->description }}</pre>
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

            {{-- SMS Bildirimleri --}}
            @if($talep->notifications->isNotEmpty())
            <div class="card">
                <div class="card-header py-2 fw-semibold d-flex justify-content-between">
                    <span>📲 SMS Bildirimleri</span>
                    <span class="badge bg-secondary">{{ $talep->notifications->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light">
                            <tr><th>Tarih</th><th>Alıcı</th><th>Durum</th><th>Mesaj</th></tr>
                        </thead>
                        <tbody>
                            @foreach($talep->notifications as $notif)
                            <tr>
                                <td class="text-nowrap">{{ $notif->created_at->format('d.m H:i') }}</td>
                                <td>
                                    @if($notif->recipient === 'admin')
                                        <span class="badge bg-dark" style="font-size:.65rem;">Admin</span>
                                    @else
                                        <span class="badge bg-info text-dark" style="font-size:.65rem;">{{ $notif->recipient_name }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($notif->status === 'sent')<span class="badge bg-success" style="font-size:.65rem;">✓</span>
                                    @elseif($notif->status === 'failed')<span class="badge bg-danger" style="font-size:.65rem;">✗</span>
                                    @else<span class="badge bg-secondary" style="font-size:.65rem;">?</span>@endif
                                </td>
                                <td class="text-muted" style="max-width:160px;white-space:normal;font-size:.72rem;">{{ Str::limit($notif->message, 80) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- ════════════════════ SAĞ SÜTUN ════════════════════ --}}
        <div class="col-lg-8 col-md-7">

            {{-- Teklifler Kartı --}}
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">💼 Teklifler
                        @if($talep->offers->count() > 0)
                            <span class="badge bg-secondary ms-1">{{ $talep->offers->count() }}</span>
                        @endif
                    </span>
                    <button class="btn btn-success btn-sm py-0 px-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#yeniTeklifPanel">
                        <i class="fas fa-plus me-1"></i>Yeni Teklif
                    </button>
                </div>

                {{-- YENİ TEKLİF PANELİ --}}
                <div class="collapse {{ $talep->offers->count() === 0 ? 'show' : '' }}" id="yeniTeklifPanel">
                    <div class="card-body border-bottom" style="background:rgba(13,110,253,.03);">

                        {{-- AI PARSE --}}
                        <div class="mb-3 p-3 rounded" style="background:rgba(255,193,7,.08);border:1px solid rgba(255,193,7,.3);">
                            <div class="fw-semibold small mb-2">🤖 Operasyon Notu → AI ile Doldur</div>
                            <textarea id="raw-note-input" class="form-control form-control-sm font-monospace mb-2" rows="4"
                                placeholder="Ham notu buraya yapıştır (PNR, fiyat, tarih, ödeme vb.)..."></textarea>
                            <button class="btn btn-warning btn-sm" onclick="aiParseBaslat()">
                                <i class="fas fa-magic me-1"></i>AI ile Ayrıştır
                            </button>
                            <span id="parse-spinner" class="ms-2 text-muted small d-none">
                                <i class="fas fa-spinner fa-spin me-1"></i>Ayrıştırılıyor...
                            </span>
                        </div>

                        {{-- TEKLİF FORMU --}}
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
                                    <label class="form-label small">Kalkış</label>
                                    <input type="time" name="flight_departure_time" id="f-dep-time" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Varış</label>
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
                                <div class="col-md-3">
                                    <label class="form-label small">Para Birimi</label>
                                    <select name="currency" id="f-currency" class="form-select form-select-sm">
                                        <option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Kişi Başı</label>
                                    <input type="number" name="price_per_pax" id="f-price-pax" class="form-control form-control-sm" step="0.01" oninput="depHesaplaFiyatDegisti('f')">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Maliyet <span class="fw-normal">(acenteye gizli)</span></label>
                                    <input type="number" name="cost_price" id="f-cost" class="form-control form-control-sm" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Kar (tutar) <span class="fw-normal">(gizli)</span></label>
                                    <input type="number" name="profit_amount" id="f-profit-amt" class="form-control form-control-sm" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Kar (%) <span class="fw-normal">(gizli)</span></label>
                                    <input type="number" name="profit_percent" id="f-profit-pct" class="form-control form-control-sm" step="0.01">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Dep. % <span class="text-muted fw-normal" id="f-dep-pct-hint"></span></label>
                                    <input type="number" name="deposit_rate" id="f-deposit-rate" class="form-control form-control-sm" step="0.01" oninput="depHesapla('f')">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Dep. Tutarı <span class="text-muted fw-normal" id="f-dep-amt-hint"></span></label>
                                    <input type="number" name="deposit_amount" id="f-deposit-amount" class="form-control form-control-sm" step="0.01" oninput="depHesapla('f', true)">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Opsiyon Tarihi</label>
                                    <input type="date" name="option_date" id="f-option-date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Opsiyon Saati</label>
                                    <input type="time" name="option_time" id="f-option-time" class="form-control form-control-sm">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">Teklif Notu <span class="text-muted fw-normal">(acenteye görünür)</span></label>
                                    <textarea name="offer_text" id="f-offer-text" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small text-muted">Tedarikçi / İç Referans <span class="fw-normal">(acenteye gizli)</span></label>
                                    <input type="text" name="supplier_reference" id="f-supplier-ref" class="form-control form-control-sm" placeholder="KUNEYILD2633080">
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="kk_enabled" value="1" id="f-kk-enabled" class="form-check-input">
                                        <label class="form-check-label small" for="f-kk-enabled">
                                            💳 KK ile ödemeye izin ver <span class="text-muted fw-normal">(default: kapalı — acenta görmez)</span>
                                        </label>
                                    </div>
                                </div>
                                <input type="hidden" name="admin_raw_note" id="f-raw-note">
                                <input type="hidden" name="ai_raw_output" id="f-ai-raw-output">
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
                                    <div class="border rounded p-2 bg-light">
                                        <div class="small fw-semibold mb-2">
                                            <i class="fas fa-bell me-1"></i>Bildirimler
                                            <span class="text-muted fw-normal">(varsayılan: hiçbiri)</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input notif-check" type="checkbox" name="notify_push_acente" value="1" id="notif-push">
                                                <label class="form-check-label small" for="notif-push">Push — Acente</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input notif-check" type="checkbox" name="notify_sms_acente" value="1" id="notif-sms-acente">
                                                <label class="form-check-label small" for="notif-sms-acente">SMS — Acente</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input notif-check" type="checkbox" name="notify_email_acente" value="1" id="notif-email">
                                                <label class="form-check-label small" for="notif-email">E-posta — Acente</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input notif-check" type="checkbox" name="notify_sms_admin" value="1" id="notif-sms-admin">
                                                <label class="form-check-label small" for="notif-sms-admin">SMS — Operasyon</label>
                                            </div>
                                        </div>
                                        <div class="mt-2 small text-muted" id="notif-ozet">
                                            <i class="fas fa-bell-slash me-1"></i>Bildirim gönderilmeyecek
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex gap-2 align-items-center">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-save me-1"></i>Teklifi Kaydet
                                    </button>
                                    <span id="ai-odeme-bilgisi" class="text-success small d-none">
                                        <i class="fas fa-check-circle me-1"></i>Ödeme de kaydedilecek
                                    </span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" onclick="formuTemizle()">Temizle</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- MEVCUT TEKLİFLER --}}
                @if($talep->offers->count() > 0)
                <div class="card-body py-3">
                    @php $digerTeklifSayisi = $kabulEdilenTeklif ? $talep->offers->where('id', '!=', $kabulEdilenTeklif->id)->count() : 0; @endphp
                    @foreach($talep->offers->sortByDesc('is_accepted') as $teklif)
                    @php $offerLogo = app(\App\Services\AirlineLogoService::class)->resolve($teklif->airline); @endphp
                    @if($kabulEdilenTeklif && !$teklif->is_accepted && $loop->index === 1 && $digerTeklifSayisi > 0)
                    <details class="mt-1 mb-1">
                        <summary class="py-1" style="cursor:pointer;user-select:none;list-style:none;">
                            <span class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:.75rem;">
                                🗂 Diğer teklifler ({{ $digerTeklifSayisi }}) — görüntülemek için tıkla
                            </span>
                        </summary>
                        <div class="mt-2" style="opacity:.82;">
                    @endif
                    <div class="offer-card p-3 mb-3 {{ !$teklif->is_visible ? 'offer-hidden' : '' }} {{ $teklif->is_accepted ? 'offer-accepted border-start border-success border-3' : '' }}"
                         style="background:rgba(0,0,0,.02);">

                        {{-- Başlık --}}
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                @if($offerLogo['has_logo'])
                                    <img src="{{ $offerLogo['path'] }}" alt="{{ $offerLogo['display_name'] }}" style="width:30px;height:30px;object-fit:contain;">
                                @endif
                                <strong>{{ $teklif->airline ?? '—' }}</strong>
                                @if($teklif->airline_pnr)<span class="badge bg-primary" style="font-size:.7rem;">{{ $teklif->airline_pnr }}</span>@endif
                                @if($teklif->flight_number)<span class="badge bg-secondary" style="font-size:.7rem;">{{ $teklif->flight_number }}</span>@endif
                                @if(!$teklif->is_visible)<span class="badge bg-warning text-dark" style="font-size:.7rem;"><i class="fas fa-eye-slash me-1"></i>Gizli</span>@endif
                                @if($teklif->is_accepted)<span class="badge bg-success" style="font-size:.7rem;"><i class="fas fa-check me-1"></i>Kabul Edildi</span>@endif
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size:.72rem;"
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
                                    <button type="submit" class="btn btn-sm py-0 px-2 {{ $teklif->is_visible ? 'btn-outline-secondary' : 'btn-outline-success' }}" style="font-size:.72rem;"
                                        title="{{ $teklif->is_visible ? 'Acenteden gizle' : 'Acenteye göster' }}">
                                        <i class="fas {{ $teklif->is_visible ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                        {{ $teklif->is_visible ? 'Gizle' : 'Göster' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.requests.offer.delete', [$talep->gtpnr, $teklif->id]) }}" class="sil-form">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size:.72rem;"
                                        onclick="silOnayGoster(
                                            this.closest('form'),
                                            'Teklif silinecek',
                                            '<strong>{{ addslashes($teklif->airline ?? '—') }}</strong>' +
                                            '{{ $teklif->airline_pnr ? " · PNR: ".$teklif->airline_pnr : "" }}' +
                                            '<br>{{ number_format($teklif->price_per_pax,0) }} {{ $teklif->currency }}/kişi · {{ $teklif->pax_confirmed ?? $talep->pax_total }} PAX'
                                        )">
                                        <i class="fas fa-trash"></i> Sil
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Detay --}}
                        <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
                            <span>💺 <strong>{{ $teklif->price_per_pax }} {{ $teklif->currency }}</strong>/kişi</span>
                            <span>👥 {{ $teklif->pax_confirmed ?? $talep->pax_total }} PAX</span>
                            @if($teklif->flight_departure_time)<span>⏰ {{ $teklif->flight_departure_time }}–{{ $teklif->flight_arrival_time }}</span>@endif
                            @if($teklif->baggage_kg)<span>🧳 {{ $teklif->baggage_kg }} KG</span>@endif
                            @if($teklif->option_date)
                            <span>📅 Opsiyon: <strong>{{ \Carbon\Carbon::parse($teklif->option_date)->format('d.m.Y') }} {{ $teklif->option_time ? substr($teklif->option_time,0,5) : '' }}</strong></span>
                            @endif
                            @if($teklif->supplier_reference)<span class="text-danger">🔒 {{ $teklif->supplier_reference }}</span>@endif
                        </div>

                        {{-- Acenteye Mesaj --}}
                        @if($teklif->offer_text)
                        <div class="p-2 rounded" style="background:rgba(13,202,240,.07);border-left:3px solid #0dcaf0;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold" style="font-size:.72rem;color:#0dcaf0;text-transform:uppercase;letter-spacing:.04em;">📨 Acenteye Mesaj</span>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-warning btn-sm py-0 px-2" style="font-size:.7rem;"
                                        onclick="aiIleDoldur({{ json_encode($teklif->offer_text) }})">
                                        ✨ AI Ayrıştır
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm py-0 px-2" style="font-size:.7rem;"
                                        id="fmt-btn-{{ $teklif->id }}"
                                        onclick="aiFormatlaAcenteye({{ $teklif->id }}, {{ json_encode($teklif->offer_text) }})">
                                        📨 Formatla
                                    </button>
                                </div>
                            </div>
                            <div style="white-space:pre-line;font-size:.82rem;">{{ $teklif->offer_text }}</div>
                        </div>
                        @endif

                        {{-- Ham Veri & AI Kaydı --}}
                        @if($teklif->admin_raw_note || $teklif->ai_raw_output)
                        <div class="mt-2">
                            <button class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:.7rem;"
                                    type="button" data-bs-toggle="collapse" data-bs-target="#raw-{{ $teklif->id }}">
                                🔍 Ham Veri & AI
                            </button>
                            <div class="collapse mt-2" id="raw-{{ $teklif->id }}">
                                @if($teklif->admin_raw_note)
                                <pre class="bg-light border rounded p-2 mb-1" style="font-size:.72rem;white-space:pre-wrap;max-height:160px;overflow-y:auto;">{{ $teklif->admin_raw_note }}</pre>
                                @endif
                                @if($teklif->ai_raw_output)
                                <pre class="bg-light border rounded p-2 mb-0" style="font-size:.7rem;white-space:pre-wrap;max-height:160px;overflow-y:auto;">{{ json_encode($teklif->ai_raw_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                @endif
                            </div>
                        </div>
                        @endif

                    </div>
                    @endforeach
                    @if($kabulEdilenTeklif && $digerTeklifSayisi > 0)
                        </div>
                    </details>
                    @endif
                </div>
                @else
                <div class="card-body text-center text-muted small py-3">Henüz teklif eklenmemiş.</div>
                @endif
            </div>

            {{-- Muhasebe & Ödemeler --}}
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">💳 Muhasebe & Ödemeler</span>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-secondary">{{ $talep->payments->count() }} kayıt</span>
                        @if($hicBekleniyor && $kabulEdilenTeklif)
                        <button class="btn btn-success btn-sm py-0 px-2" type="button"
                            onclick="odemePlanlaAc()">
                            <i class="fas fa-calendar-plus me-1"></i>Ödeme Planla
                        </button>
                        @endif
                        <button class="btn btn-primary btn-sm py-0 px-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#odemeEklePanel">
                            <i class="fas fa-plus me-1"></i>Ödeme Ekle
                        </button>
                    </div>
                </div>

                {{-- Muhasebe Özeti --}}
                @if(!$kabulEdilenTeklif)
                <div class="card-body py-2 text-muted small">
                    <i class="fas fa-info-circle me-1"></i>Acenta teklif seçtiğinde ödeme planı ve toplam tutar burada görünecek.
                </div>
                @elseif($toplamTutar > 0)
                <div class="card-body pb-0 pt-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Tahsilat</span>
                        <span class="fw-bold">{{ number_format($toplamOdenen,0) }} / {{ number_format($toplamTutar,0) }} {{ $odenenCurrency }}</span>
                    </div>
                    <div class="progress mb-2" style="height:8px;">
                        <div class="progress-bar {{ $yuzde >= 100 ? 'bg-success' : ($yuzde >= 50 ? 'bg-primary' : 'bg-warning') }}" style="width:{{ $yuzde }}%"></div>
                    </div>
                    <div class="row g-1 mb-2 text-center small">
                        <div class="col-4"><div class="bg-light rounded p-1"><div class="text-muted" style="font-size:.68rem;">Toplam Teklif</div><div class="fw-bold">{{ number_format($toplamTutar,0) }}</div></div></div>
                        <div class="col-4"><div class="bg-success bg-opacity-10 rounded p-1"><div class="text-muted" style="font-size:.68rem;">Ödenen</div><div class="fw-bold text-success">{{ number_format($toplamOdenen,0) }}</div></div></div>
                        <div class="col-4"><div class="bg-danger bg-opacity-10 rounded p-1"><div class="text-muted" style="font-size:.68rem;">Kalan</div><div class="fw-bold text-danger">{{ number_format($kalanTutar,0) }}</div></div></div>
                    </div>
                    @if($toplamBekleniyor > 0 || $planlanmamisKalan > 0)
                    <div class="row g-1 mb-2 text-center small">
                        @if($toplamBekleniyor > 0)
                        <div class="{{ $planlanmamisKalan > 0 ? 'col-6' : 'col-12' }}">
                            <div class="bg-warning bg-opacity-10 rounded p-1">
                                <div class="text-muted" style="font-size:.68rem;">Planlanan (bekleniyor)</div>
                                <div class="fw-bold text-warning">{{ number_format($toplamBekleniyor,0) }} {{ $odenenCurrency }}</div>
                            </div>
                        </div>
                        @endif
                        @if($planlanmamisKalan > 0)
                        <div class="{{ $toplamBekleniyor > 0 ? 'col-6' : 'col-12' }}">
                            <div class="bg-danger bg-opacity-10 rounded p-1">
                                <div class="text-muted" style="font-size:.68rem;">Planlanmamış Kalan ⚠</div>
                                <div class="fw-bold text-danger">{{ number_format($planlanmamisKalan,0) }} {{ $odenenCurrency }}</div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                    @if($kalanTutar <= 0 && $toplamTutar > 0 && $talep->status === 'depozitoda')
                    <div class="text-center mb-2">
                        <form method="POST" action="{{ route('admin.requests.status', $talep->gtpnr) }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="status" value="biletlendi">
                            <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                                <i class="fas fa-ticket-alt me-1"></i>Biletlemeye Geç
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                @endif

                <div class="collapse" id="odemeEklePanel">
                    <div class="card-body border-top border-bottom py-3" style="background:rgba(13,110,253,.03);">
                        <div class="fw-semibold small mb-2 text-primary">Yeni Ödeme Kaydı</div>
                        <form method="POST" action="{{ route('admin.requests.payment', $talep->gtpnr) }}" id="payment-form">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <label class="form-label small">Sıra</label>
                                    <input type="number" name="sequence" id="p-sequence" class="form-control form-control-sm" value="1" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Tip</label>
                                    <select name="payment_type" class="form-select form-select-sm">
                                        <option value="depozito">Depozito</option><option value="bakiye">Bakiye</option>
                                        <option value="full">Full</option><option value="diger">Diğer</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Yöntem</label>
                                    <select name="payment_method" id="p-method" class="form-select form-select-sm">
                                        <option value="">Seç</option><option value="FAST">FAST</option>
                                        <option value="EFT">EFT</option><option value="havale">Havale</option>
                                        <option value="kart">Kart</option><option value="nakit">Nakit</option>
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
                                    <label class="form-label small">Hesap (maskeli)</label>
                                    <input type="text" name="account_masked" id="p-account" class="form-control form-control-sm" placeholder="234-*348">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Tutar</label>
                                    <input type="number" name="amount" id="p-amount" class="form-control form-control-sm" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Birim</label>
                                    <select name="currency" id="p-currency" class="form-select form-select-sm">
                                        <option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Ödeme Tarihi</label>
                                    <input type="date" name="payment_date" id="p-date" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Son Ödeme Tarihi</label>
                                    <input type="date" name="due_date" id="p-due-date" class="form-control form-control-sm">
                                    <div class="form-text" style="font-size:.65rem;">Bekleniyor ise son tarih</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Durum</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="alindi">Alındı</option><option value="bekleniyor">Bekleniyor</option><option value="iade">İade</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save me-1"></i>Ödemeyi Kaydet
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Ödeme Listesi --}}
                <div class="card-body {{ $talep->payments->count() === 0 ? 'py-3 text-center text-muted small' : 'py-2' }}">
                    @forelse($talep->payments->sortBy('sequence') as $odeme)
                    @php $odemeLabel = $odeme->sequence == 1 ? '1. Depozito' : $odeme->sequence . '. Depozito (Bakiye Tamamlama)'; @endphp
                    <div class="border rounded p-2 mb-2 small
                        {{ $odeme->status === 'iade' ? 'border-danger' : '' }}
                        {{ $odeme->status === 'bekleniyor' ? 'border-warning' : '' }}
                        {{ $odeme->status === 'alindi' ? 'border-success border-opacity-50' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-1">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <strong>{{ $odemeLabel }}</strong>
                                    @if($odeme->status === 'alindi')
                                        <span class="badge bg-success" style="font-size:.68rem;">✓ Alındı</span>
                                    @elseif($odeme->status === 'bekleniyor')
                                        <span class="badge bg-warning text-dark" style="font-size:.68rem;">⏳ Bekleniyor</span>
                                        @if($odeme->due_date)
                                            <span class="text-muted" style="font-size:.72rem;">Son: {{ $odeme->due_date->format('d.m.Y') }}</span>
                                        @endif
                                    @elseif($odeme->status === 'iade')
                                        <span class="badge bg-danger" style="font-size:.68rem;">İade</span>
                                    @endif
                                    <span class="ms-auto fw-bold">{{ number_format($odeme->amount,0) }} {{ $odeme->currency }}</span>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    @if($odeme->payment_method){{ $odeme->payment_method }}@endif
                                    @if($odeme->bank_name) · {{ $odeme->bank_name }}@endif
                                    @if($odeme->payment_date) · {{ $odeme->payment_date->format('d.m.Y') }}@endif
                                    @if($odeme->sender_masked) · {{ $odeme->sender_masked }}@if($odeme->account_masked) / {{ $odeme->account_masked }}@endif @endif
                                </div>
                                @if($odeme->created_by)<div class="text-muted" style="font-size:.68rem;">Kaydeden: {{ $odeme->created_by }}</div>@endif
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                @if($odeme->status === 'bekleniyor')
                                <button type="button" class="btn btn-success btn-sm py-0 px-1" style="font-size:.7rem;" title="Ödendi İşaretle"
                                    onclick="odemePaidAc(
                                        {{ $odeme->id }},
                                        '{{ route('admin.requests.payment.update', [$talep->gtpnr, $odeme->id]) }}',
                                        {{ $odeme->sequence }},
                                        {{ $odeme->amount }},
                                        '{{ $odeme->currency }}',
                                        '{{ $odeme->due_date?->format('Y-m-d') }}',
                                        '{{ $odeme->payment_type }}'
                                    )">
                                    <i class="fas fa-check me-1"></i>Ödendi
                                </button>
                                @endif
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-1" style="font-size:.7rem;" title="Düzenle"
                                    onclick="odemeDuzenle(
                                        {{ $odeme->id }},
                                        '{{ route('admin.requests.payment.update', [$talep->gtpnr, $odeme->id]) }}',
                                        {{ $odeme->sequence }}, '{{ $odeme->payment_type }}',
                                        '{{ $odeme->payment_method }}', {{ json_encode($odeme->bank_name) }},
                                        {{ json_encode($odeme->sender_masked) }}, {{ json_encode($odeme->account_masked) }},
                                        {{ $odeme->amount }}, '{{ $odeme->currency }}',
                                        '{{ $odeme->payment_date?->format('Y-m-d') }}', '{{ $odeme->status }}',
                                        '{{ $odeme->due_date?->format('Y-m-d') }}'
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.requests.payment.delete', [$talep->gtpnr, $odeme->id]) }}" class="sil-form">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" style="font-size:.7rem;"
                                        onclick="silOnayGoster(
                                            this.closest('form'),
                                            'Ödeme silinecek',
                                            '<strong>{{ addslashes($odemeLabel) }}</strong> · {{ number_format($odeme->amount,0) }} {{ $odeme->currency }}{{ $odeme->payment_date ? " · ".$odeme->payment_date->format("d.m.Y") : "" }}'
                                        )">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    Henüz ödeme kaydı yok.
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ═══ TEKLİF DÜZENLEME MODALİ ═══ --}}
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
                        <div class="col-md-4"><label class="form-label small">Havayolu</label><input type="text" name="airline" id="e-airline" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><label class="form-label small">Havayolu PNR</label><input type="text" name="airline_pnr" id="e-airline-pnr" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><label class="form-label small">Sefer Kodu</label><input type="text" name="flight_number" id="e-flight-number" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small">Kalkış</label><input type="time" name="flight_departure_time" id="e-dep-time" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small">Varış</label><input type="time" name="flight_arrival_time" id="e-arr-time" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small">Bagaj (KG)</label><input type="number" name="baggage_kg" id="e-baggage" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small">Teyit PAX</label><input type="number" name="pax_confirmed" id="e-pax" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><label class="form-label small">Para Birimi</label>
                            <select name="currency" id="e-currency" class="form-select form-select-sm">
                                <option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label small">Kişi Başı</label><input type="number" name="price_per_pax" id="e-price-pax" class="form-control form-control-sm" step="0.01" oninput="depHesaplaFiyatDegisti('e')"></div>
                        <div class="col-md-4"><label class="form-label small">Maliyet</label><input type="number" name="cost_price" id="e-cost" class="form-control form-control-sm" step="0.01"></div>
                        <div class="col-md-4"><label class="form-label small">Dep. % <span class="text-muted fw-normal" id="e-dep-pct-hint"></span></label><input type="number" name="deposit_rate" id="e-deposit-rate" class="form-control form-control-sm" step="0.01" oninput="depHesapla('e')"></div>
                        <div class="col-md-4"><label class="form-label small">Dep. Tutarı <span class="text-muted fw-normal" id="e-dep-amt-hint"></span></label><input type="number" name="deposit_amount" id="e-deposit-amount" class="form-control form-control-sm" step="0.01" oninput="depHesapla('e', true)"></div>
                        <div class="col-md-2"><label class="form-label small">Opsiyon Tarihi</label><input type="date" name="option_date" id="e-option-date" class="form-control form-control-sm"></div>
                        <div class="col-md-2"><label class="form-label small">Opsiyon Saati</label><input type="time" name="option_time" id="e-option-time" class="form-control form-control-sm"></div>
                        <div class="col-12"><label class="form-label small">Teklif Notu (acenteye görünür)</label><textarea name="offer_text" id="e-offer-text" class="form-control form-control-sm" rows="2"></textarea></div>
                        <div class="col-12"><label class="form-label small text-muted">Tedarikçi / İç Referans (gizli)</label><input type="text" name="supplier_reference" id="e-supplier-ref" class="form-control form-control-sm"></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ ÖDEME DÜZENLEME MODALİ ═══ --}}
<div class="modal fade" id="odemeDuzenleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="odemeDuzenleForm">
                @csrf @method('PATCH')
                <div class="modal-header py-2 bg-primary text-white">
                    <h6 class="modal-title fw-bold mb-0"><i class="fas fa-edit me-2"></i>Ödeme Düzenle</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-4"><label class="form-label small fw-bold">Sıra</label><input type="number" name="sequence" id="od_sequence" class="form-control form-control-sm" min="1"></div>
                        <div class="col-4"><label class="form-label small fw-bold">Tip</label>
                            <select name="payment_type" id="od_type" class="form-select form-select-sm">
                                <option value="depozito">Depozito</option><option value="bakiye">Bakiye</option><option value="full">Full</option><option value="diger">Diğer</option>
                            </select>
                        </div>
                        <div class="col-4"><label class="form-label small fw-bold">Durum</label>
                            <select name="status" id="od_status" class="form-select form-select-sm">
                                <option value="alindi">Alındı</option><option value="bekleniyor">Bekleniyor</option><option value="iade">İade</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label small fw-bold">Yöntem</label>
                            <select name="payment_method" id="od_method" class="form-select form-select-sm">
                                <option value="">Seç</option><option value="FAST">FAST</option><option value="EFT">EFT</option><option value="havale">Havale</option><option value="kart">Kart</option><option value="nakit">Nakit</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label small fw-bold">Banka</label><input type="text" name="bank_name" id="od_bank" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label small fw-bold">Gönderen</label><input type="text" name="sender_masked" id="od_sender" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label small fw-bold">Hesap</label><input type="text" name="account_masked" id="od_account" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label small fw-bold">Tutar</label><input type="number" name="amount" id="od_amount" class="form-control form-control-sm" step="0.01" required></div>
                        <div class="col-3"><label class="form-label small fw-bold">Birim</label>
                            <select name="currency" id="od_currency" class="form-select form-select-sm">
                                <option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div class="col-3"><label class="form-label small fw-bold">Ödeme Tarihi</label><input type="date" name="payment_date" id="od_date" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="form-label small fw-bold">Son Ödeme Tarihi (due_date)</label><input type="date" name="due_date" id="od_due_date" class="form-control form-control-sm"><div class="form-text" style="font-size:.65rem;">Bekleniyor ise son tarih</div></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold"><i class="fas fa-save me-1"></i>Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ ÖDEME PLANLA MODALİ ═══ --}}
<div class="modal fade" id="odemePlanlaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.requests.payment', $talep->gtpnr) }}" id="odemePlanlaForm">
                @csrf
                <input type="hidden" name="status" value="bekleniyor">
                <div class="modal-header py-2 bg-success text-white">
                    <h6 class="modal-title fw-bold mb-0"><i class="fas fa-calendar-plus me-2"></i>Ödeme Planla</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label small fw-bold">Sıra</label>
                            <input type="number" name="sequence" id="pl_sequence" class="form-control form-control-sm" readonly>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-bold">Tip</label>
                            <select name="payment_type" id="pl_type" class="form-select form-select-sm">
                                <option value="depozito">Depozito</option>
                                <option value="bakiye">Bakiye</option>
                                <option value="full">Full</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Tutar</label>
                            <input type="number" name="amount" id="pl_amount" class="form-control form-control-sm" step="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Para Birimi</label>
                            <select name="currency" id="pl_currency" class="form-select form-select-sm">
                                <option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Son Ödeme Tarihi</label>
                            <input type="date" name="due_date" id="pl_due_date" class="form-control form-control-sm" required>
                            <div class="form-text" style="font-size:.68rem;">Acente görünümünde opsiyon tarihi olarak gösterilir.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="fas fa-calendar-check me-1"></i>Planla (Bekleniyor)</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ ÖDENDİ İŞARETLE MODALİ ═══ --}}
<div class="modal fade" id="odemePaidModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="odemePaidForm">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="alindi">
                <div class="modal-header py-2 bg-success text-white">
                    <h6 class="modal-title fw-bold mb-0"><i class="fas fa-check-circle me-2"></i>Ödendi İşaretle</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success py-2 small mb-3" id="paid_ozet"></div>
                    <div class="row g-2">
                        <input type="hidden" name="sequence" id="paid_sequence">
                        <input type="hidden" name="payment_type" id="paid_type" value="depozito">
                        <input type="hidden" name="amount" id="paid_amount_hidden">
                        <input type="hidden" name="currency" id="paid_currency_hidden">
                        <input type="hidden" name="due_date" id="paid_due_date_hidden">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Gerçek Ödeme Tarihi</label>
                            <input type="date" name="payment_date" id="paid_date" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Gerçek Tutar (farklıysa)</label>
                            <input type="number" name="amount_override" id="paid_amount_override" class="form-control form-control-sm" step="0.01" placeholder="Boş = aynı">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Ödeme Yöntemi</label>
                            <select name="payment_method" id="paid_method" class="form-select form-select-sm">
                                <option value="">Seç</option>
                                <option value="FAST">FAST</option>
                                <option value="EFT">EFT</option>
                                <option value="havale">Havale</option>
                                <option value="kart">Kart</option>
                                <option value="nakit">Nakit</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Banka</label>
                            <input type="text" name="bank_name" id="paid_bank" class="form-control form-control-sm" placeholder="Garanti BBVA">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Gönderen (maskeli)</label>
                            <input type="text" name="sender_masked" id="paid_sender" class="form-control form-control-sm" placeholder="EC* TU**">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Hesap (maskeli)</label>
                            <input type="text" name="account_masked" id="paid_account" class="form-control form-control-sm" placeholder="234-*348">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="fas fa-check me-1"></i>Ödendi Olarak Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ TALEP DÜZENLEME MODALİ ═══ --}}
<div class="modal fade" id="talepDuzenleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.requests.update', $talep->gtpnr) }}">
                @csrf @method('PATCH')
                <div class="modal-header py-2 bg-warning text-dark">
                    <h6 class="modal-title fw-bold mb-0"><i class="fas fa-edit me-2"></i>Talebi Düzenle — {{ $talep->gtpnr }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 small">
                        <div class="col-6">
                            <label class="form-label fw-bold">Telefon</label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ $talep->phone }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">E-posta</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ $talep->email }}">
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-bold">PAX Toplam</label>
                            <input type="number" name="pax_total" class="form-control form-control-sm" value="{{ $talep->pax_total }}" min="1">
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-bold">Yetişkin</label>
                            <input type="number" name="pax_adult" class="form-control form-control-sm" value="{{ $talep->pax_adult }}" min="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-bold">Çocuk</label>
                            <input type="number" name="pax_child" class="form-control form-control-sm" value="{{ $talep->pax_child }}" min="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label fw-bold">Bebek</label>
                            <input type="number" name="pax_infant" class="form-control form-control-sm" value="{{ $talep->pax_infant }}" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Grup Firma Adı</label>
                            <input type="text" name="group_company_name" class="form-control form-control-sm" value="{{ $talep->group_company_name }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Uçuş Amacı</label>
                            <input type="text" name="flight_purpose" class="form-control form-control-sm" value="{{ $talep->flight_purpose }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Tercih Edilen Havayolu</label>
                            <input type="text" name="preferred_airline" class="form-control form-control-sm" value="{{ $talep->preferred_airline }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Notlar</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="3">{{ $talep->notes }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold"><i class="fas fa-save me-1"></i>Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══ SİLME ONAY MODALİ ═══ --}}
<div class="modal fade" id="silOnayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title fw-bold mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Silme Onayı</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-danger fw-bold" id="sil-modal-baslik"></p>
                <div class="bg-light border rounded p-2 small" id="sil-modal-detay"></div>
                <p class="mt-3 mb-0 small text-muted">
                    <i class="fas fa-exclamation-circle text-warning me-1"></i>
                    Bu işlem <strong>geri alınamaz.</strong>
                </p>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-danger btn-sm" id="sil-onayla-btn">
                    <i class="fas fa-trash me-1"></i>Evet, Sil
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('admin.partials.theme-script')
<script>
const CSRF      = '{{ csrf_token() }}';
const PARSE_URL = '{{ route("admin.requests.ai-parse", $talep->gtpnr) }}';

async function aiFormatlaAcenteye(offerId, rawNote) {
    const btn  = document.getElementById('fmt-btn-' + offerId);
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const res  = await fetch('{{ route("admin.requests.ai-format-offer", $talep->gtpnr) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ offer_id: offerId, raw_note: rawNote })
        });
        const data = await res.json();
        if (data.error) { alert('Hata: ' + data.error); btn.disabled = false; btn.innerHTML = orig; return; }
        location.reload();
    } catch(e) { alert('Hata: ' + e.message); btn.disabled = false; btn.innerHTML = orig; }
}

function aiIleDoldur(metin) {
    const el = document.getElementById('raw-note-input');
    el.value = metin;
    // Yeni teklif panelini aç
    const panel = document.getElementById('yeniTeklifPanel');
    if (!panel.classList.contains('show')) {
        new bootstrap.Collapse(panel, { toggle: true });
    }
    setTimeout(() => { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus(); aiParseBaslat(); }, 400);
}

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
        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) { alert('Sunucu hatası (HTTP ' + res.status + ')'); return; }
        const json = await res.json();
        if (json.error) { alert('Hata: ' + json.error); return; }
        const d = json.data;
        if (d.airline)               document.getElementById('f-airline').value       = d.airline;
        if (d.airline_pnr)           document.getElementById('f-airline-pnr').value   = d.airline_pnr;
        if (d.flight_number)         document.getElementById('f-flight-number').value = d.flight_number;
        if (d.flight_departure_time) document.getElementById('f-dep-time').value      = d.flight_departure_time;
        if (d.flight_arrival_time)   document.getElementById('f-arr-time').value      = d.flight_arrival_time;
        if (d.baggage_kg)            document.getElementById('f-baggage').value       = d.baggage_kg;
        if (d.pax_confirmed)         document.getElementById('f-pax').value           = d.pax_confirmed;
        if (d.price_per_pax)         document.getElementById('f-price-pax').value     = d.price_per_pax;
        if (d.currency)              document.getElementById('f-currency').value      = d.currency;
        if (d.supplier_reference)    document.getElementById('f-supplier-ref').value  = d.supplier_reference;
        if (d.ticketing_deadline || d.balance_deadline) {
            const dl = d.ticketing_deadline || d.balance_deadline;
            const parts = dl.split(' ');
            document.getElementById('f-option-date').value = parts[0] ?? '';
            document.getElementById('f-option-time').value = parts[1] ?? '';
        }
        document.getElementById('f-raw-note').value      = rawNote;
        document.getElementById('f-ai-raw-output').value = JSON.stringify(d);
        if (d.payment_method) document.getElementById('p-method').value   = d.payment_method;
        if (d.bank_name)      document.getElementById('p-bank').value     = d.bank_name;
        if (d.sender_masked)  document.getElementById('p-sender').value   = d.sender_masked;
        if (d.account_masked) document.getElementById('p-account').value  = d.account_masked;
        if (d.payment_amount) document.getElementById('p-amount').value   = d.payment_amount;
        if (d.payment_currency) document.getElementById('p-currency').value = d.payment_currency;
        if (d.payment_date)   document.getElementById('p-date').value     = d.payment_date;
        if (d.payment_sequence) document.getElementById('p-sequence').value = d.payment_sequence;
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

const teklifDuzenleModal = new bootstrap.Modal(document.getElementById('teklifDuzenleModal'));
const BASE_URL = '{{ url("admin/talepler/".$talep->gtpnr."/teklif") }}';

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

const odemeDuzenleModal = new bootstrap.Modal(document.getElementById('odemeDuzenleModal'));
const odemeDuzenleForm  = document.getElementById('odemeDuzenleForm');

function odemeDuzenle(id, url, sequence, type, method, bank, sender, account, amount, currency, date, status, dueDate) {
    odemeDuzenleForm.action = url;
    document.getElementById('od_sequence').value  = sequence || 1;
    document.getElementById('od_type').value      = type     || 'depozito';
    document.getElementById('od_method').value    = method   || '';
    document.getElementById('od_bank').value      = bank     || '';
    document.getElementById('od_sender').value    = sender   || '';
    document.getElementById('od_account').value   = account  || '';
    document.getElementById('od_amount').value    = amount   || '';
    document.getElementById('od_currency').value  = currency || 'TRY';
    document.getElementById('od_date').value      = date     || '';
    document.getElementById('od_status').value    = status   || 'alindi';
    document.getElementById('od_due_date').value  = dueDate  || '';
    odemeDuzenleModal.show();
}

const odemePlanlaModal = new bootstrap.Modal(document.getElementById('odemePlanlaModal'));

function odemePlanlaAc() {
    const nextSeq = {{ $maxSequence + 1 }};
    document.getElementById('pl_sequence').value  = nextSeq;
    document.getElementById('pl_type').value      = nextSeq === 1 ? 'depozito' : 'bakiye';
    document.getElementById('pl_amount').value    = '';
    document.getElementById('pl_currency').value  = '{{ $odenenCurrency }}';
    document.getElementById('pl_due_date').value  = '';
    odemePlanlaModal.show();
}

const odemePaidModal = new bootstrap.Modal(document.getElementById('odemePaidModal'));

function odemePaidAc(id, url, sequence, amount, currency, dueDate, paymentType) {
    document.getElementById('odemePaidForm').action = url;
    document.getElementById('paid_sequence').value        = sequence;
    document.getElementById('paid_type').value            = paymentType || 'depozito';
    document.getElementById('paid_amount_hidden').value   = amount;
    document.getElementById('paid_currency_hidden').value = currency;
    document.getElementById('paid_due_date_hidden').value = dueDate || '';
    document.getElementById('paid_date').value            = '';
    document.getElementById('paid_amount_override').value = '';
    document.getElementById('paid_method').value          = '';
    document.getElementById('paid_bank').value            = '';
    document.getElementById('paid_sender').value          = '';
    document.getElementById('paid_account').value         = '';
    const label = sequence === 1 ? '1. Depozito' : sequence + '. Depozito (Bakiye Tamamlama)';
    document.getElementById('paid_ozet').innerHTML =
        '<strong>' + label + '</strong> · ' +
        parseFloat(amount).toLocaleString('tr-TR') + ' ' + currency +
        (dueDate ? ' · Son: ' + dueDate.split('-').reverse().join('.') : '');
    odemePaidModal.show();
}

// Handle amount_override: if set, override hidden amount before submit
document.getElementById('odemePaidForm').addEventListener('submit', function() {
    const override = document.getElementById('paid_amount_override').value;
    if (override && parseFloat(override) > 0) {
        document.getElementById('paid_amount_hidden').value = override;
    }
});

let silFormPending = null;
const silModal = new bootstrap.Modal(document.getElementById('silOnayModal'));

function silOnayGoster(form, baslik, detay) {
    silFormPending = form;
    document.getElementById('sil-modal-baslik').textContent = baslik;
    document.getElementById('sil-modal-detay').innerHTML = detay;
    silModal.show();
}

document.getElementById('sil-onayla-btn').addEventListener('click', function () {
    if (silFormPending) { silModal.hide(); silFormPending.submit(); silFormPending = null; }
});

// ── Bildirim checkbox özeti (teklif formu) ──
document.querySelectorAll('.notif-check').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var secililer = Array.from(document.querySelectorAll('.notif-check:checked'))
            .map(function(c) { return c.nextElementSibling.textContent.trim(); });
        var ozet = document.getElementById('notif-ozet');
        if (!ozet) return;
        if (secililer.length === 0) {
            ozet.innerHTML = '<i class="fas fa-bell-slash me-1"></i>Bildirim gönderilmeyecek';
        } else {
            ozet.innerHTML = '<i class="fas fa-bell me-1 text-primary"></i>Gönderilecek: <strong>' + secililer.join(', ') + '</strong>';
        }
    });
});

// ── Bildirim checkbox özeti (durum formu) ──
var statusNotifEmail = document.getElementById('status-notif-email');
if (statusNotifEmail) {
    statusNotifEmail.addEventListener('change', function() {
        var info = document.getElementById('status-notif-info');
        if (!info) return;
        info.innerHTML = this.checked
            ? '<i class="fas fa-envelope me-1 text-primary"></i>E-posta gönderilecek'
            : '<i class="fas fa-bell-slash me-1"></i>Bildirim gönderilmeyecek';
    });
}

/* ── Depozito % ↔ Tutar Dinamik Hesaplama ───────────────────────────── */
const TALEP_PAX_TOTAL = {{ $talep->pax_total ?? 1 }};

function depToplamiAl(prefix) {
    const fiyat = parseFloat(document.getElementById(prefix + '-price-pax')?.value) || 0;
    const pax   = parseInt(document.getElementById(prefix + '-pax')?.value)
               || (prefix === 'e' ? TALEP_PAX_TOTAL : TALEP_PAX_TOTAL);
    return fiyat * pax;
}

function depHesapla(prefix, tutardanHesapla = false) {
    const toplam  = depToplamiAl(prefix);
    const rateEl  = document.getElementById(prefix + '-deposit-rate');
    const amtEl   = document.getElementById(prefix + '-deposit-amount');
    const pctHint = document.getElementById(prefix + '-dep-pct-hint');
    const amtHint = document.getElementById(prefix + '-dep-amt-hint');

    if (toplam <= 0) return;

    if (!tutardanHesapla) {
        // Yüzde girildi → tutarı hesapla
        const rate = parseFloat(rateEl.value);
        if (!isNaN(rate) && rate > 0) {
            const amt = Math.round(toplam * rate / 100 * 100) / 100;
            amtEl.value = amt;
            if (amtHint) amtHint.textContent = '= ' + amt.toLocaleString('tr-TR') + ' (hesaplı)';
        }
    } else {
        // Tutar girildi → yüzdeyi hesapla
        const amt = parseFloat(amtEl.value);
        if (!isNaN(amt) && amt > 0) {
            const rate = Math.round(amt / toplam * 10000) / 100;
            rateEl.value = rate;
            if (pctHint) pctHint.textContent = '= %' + rate + ' (hesaplı)';
        }
    }
}

function depHesaplaFiyatDegisti(prefix) {
    // Fiyat değiştiğinde, eğer yüzde girilmişse tutarı güncelle
    const rateEl = document.getElementById(prefix + '-deposit-rate');
    if (rateEl && parseFloat(rateEl.value) > 0) {
        depHesapla(prefix, false);
    }
}
</script>
</body>
</html>

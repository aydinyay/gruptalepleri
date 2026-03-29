<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Site Ayarlari - Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,0.55); font-size:0.82rem; margin:0; }
        .tab-btn { border-radius:999px !important; font-weight:600; }
        .tab-btn.active { background:#1a1a2e !important; border-color:#1a1a2e !important; }
        .kpi-card { border:none; border-radius:12px; }
        .kpi-icon {
            width:42px; height:42px; border-radius:10px;
            display:flex; align-items:center; justify-content:center; font-size:1.05rem;
        }
        .table td, .table th { font-size:0.86rem; vertical-align:middle; }

        /* Placeholder silik + italik — boş alan hemen anlaşılsın */
        .form-control::placeholder,
        .form-select::placeholder {
            color: #b0bec5 !important;
            font-style: italic;
            font-weight: 400;
        }
        .form-control:placeholder-shown,
        textarea:placeholder-shown {
            border-color: #dee2e6;
            background-color: #fafbfc;
        }
        .form-control:not(:placeholder-shown),
        textarea:not(:placeholder-shown) {
            border-color: #86b7fe;
            background-color: #fff;
            font-weight: 500;
        }
    </style>
</head>
<body>

<x-navbar-superadmin active="site-ayarlar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-cogs me-2" style="color:#e94560;"></i>Site Ayarlari Merkezi</h5>
        <p>Bildirim, duyuru, rapor ve AI kutlama akislarini tek ekrandan yonetin.</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <ul class="nav nav-pills gap-2 mb-4 flex-wrap">
        <li class="nav-item">
            <a class="nav-link tab-btn {{ $activeTab === 'bildirim' ? 'active' : 'btn btn-outline-secondary' }}"
               href="{{ route('superadmin.site.ayarlar', ['sekme' => 'bildirim']) }}">
                <i class="fas fa-sliders-h me-1"></i>Bildirim
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link tab-btn {{ $activeTab === 'sms' ? 'active' : 'btn btn-outline-secondary' }}"
               href="{{ route('superadmin.site.ayarlar', ['sekme' => 'sms']) }}">
                <i class="fas fa-sms me-1"></i>SMS ve Opsiyon
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link tab-btn {{ $activeTab === 'duyuru' ? 'active' : 'btn btn-outline-secondary' }}"
               href="{{ route('superadmin.site.ayarlar', ['sekme' => 'duyuru']) }}">
                <i class="fas fa-bullhorn me-1"></i>Duyuru
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link tab-btn {{ $activeTab === 'rapor' ? 'active' : 'btn btn-outline-secondary' }}"
               href="{{ route('superadmin.site.ayarlar', ['sekme' => 'rapor']) }}">
                <i class="fas fa-chart-bar me-1"></i>Rapor
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link tab-btn {{ $activeTab === 'ai' ? 'active' : 'btn btn-outline-secondary' }}"
               href="{{ route('superadmin.site.ayarlar', ['sekme' => 'ai']) }}">
                <i class="fas fa-robot me-1"></i>AI Kutlama
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link tab-btn {{ $activeTab === 'sirket' ? 'active' : 'btn btn-outline-secondary' }}"
               href="{{ route('superadmin.site.ayarlar', ['sekme' => 'sirket']) }}">
                <i class="fas fa-building me-1"></i>Şirket Bilgileri
            </a>
        </li>
    </ul>

    @if($activeTab === 'bildirim')
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-header fw-bold d-flex align-items-center gap-2" style="background:#eef4ff;border-bottom:1px solid #b6d4fe;">
                <i class="fas fa-sliders-h text-primary"></i>
                <span>Bildirim Sistemleri (Global)</span>
            </div>
            <div class="card-body py-3">
                <form method="POST" action="{{ route('superadmin.bildirim.sistemleri') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="sms_enabled" name="sms_enabled" value="1" {{ ($notificationSystems['sms'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="sms_enabled">SMS</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="email_enabled" name="email_enabled" value="1" {{ ($notificationSystems['email'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="email_enabled">Email</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="push_enabled" name="push_enabled" value="1" {{ ($notificationSystems['push'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="push_enabled">Push</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="broadcast_enabled" name="broadcast_enabled" value="1" {{ ($notificationSystems['broadcast'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="broadcast_enabled">Broadcast</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-sm btn-primary fw-bold">
                            <i class="fas fa-save me-1"></i>Sistem Durumlarini Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon" style="background:#e8f4fd;color:#0d6efd;"><i class="fas fa-sms"></i></div>
                        <div>
                            <div class="fw-bold">{{ $stats['sms_kural'] }}</div>
                            <div class="text-muted small">SMS Kurali</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon" style="background:#fff3cd;color:#856404;"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="fw-bold">{{ $stats['opsiyon_kural'] }}</div>
                            <div class="text-muted small">Opsiyon Kurali</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon" style="background:#ffe6ea;color:#e94560;"><i class="fas fa-bullhorn"></i></div>
                        <div>
                            <div class="fw-bold">{{ $stats['duyuru'] }}</div>
                            <div class="text-muted small">Toplam Duyuru</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon" style="background:#d1e7dd;color:#0a3622;"><i class="fas fa-chart-bar"></i></div>
                        <div>
                            <div class="fw-bold">{{ $stats['iletisim_log'] }}</div>
                            <div class="text-muted small">Iletisim Logu</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($activeTab === 'sms')
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-bold">SMS Gonderim Saatleri</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('superadmin.sms.saat') }}" class="d-flex align-items-end gap-2 flex-wrap">
                            @csrf
                            <div>
                                <label class="form-label small mb-1">Baslangic</label>
                                <input type="time" name="sms_baslangic" class="form-control form-control-sm" value="{{ $smsBaslangic }}" required>
                            </div>
                            <div>
                                <label class="form-label small mb-1">Bitis</label>
                                <input type="time" name="sms_bitis" class="form-control form-control-sm" value="{{ $smsBitis }}" required>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Kaydet</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header fw-bold">Opsiyon Kontrol Araligi</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('superadmin.scheduler.aralik') }}" class="d-flex align-items-end gap-2 flex-wrap">
                            @csrf
                            <div>
                                <label class="form-label small mb-1">Aralik</label>
                                <select name="aralik" class="form-select form-select-sm">
                                    @foreach([1,5,15,30,60,360,720,1440] as $item)
                                        <option value="{{ $item }}" {{ $schedulerAralik === $item ? 'selected' : '' }}>
                                            {{ $item }} dakika
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Guncelle</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Son SMS Kurallari</span>
                <a href="{{ route('superadmin.sms.ayarlar') }}" class="btn btn-sm btn-outline-primary">Tumunu Yonet</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Kisi</th>
                        <th>Numara</th>
                        <th>Olay</th>
                        <th>Durum</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentSmsRules as $rule)
                        <tr>
                            <td>{{ $rule->label }}</td>
                            <td>{{ $rule->phone }}</td>
                            <td>{{ \App\Models\SmsNotificationSetting::eventLabel($rule->event) }}</td>
                            <td>
                                <span class="badge {{ $rule->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $rule->is_active ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Kural bulunamadi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-bold">Aktif Opsiyon Uyari Kurallari</div>
            <div class="card-body">
                @forelse($opsiyonAyarlar as $item)
                    <span class="badge bg-light text-dark border me-1 mb-1">
                        {{ $item->saat_oncesi }}s once
                        @if($item->sms_aktif) · SMS @endif
                        @if($item->push_aktif) · Push @endif
                    </span>
                @empty
                    <div class="text-muted small">Opsiyon uyarisi tanimli degil.</div>
                @endforelse
            </div>
        </div>
    @endif

    @if($activeTab === 'duyuru')
        <div class="card shadow-sm">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Son Duyurular</span>
                <a href="{{ route('superadmin.broadcast.gecmisi') }}" class="btn btn-sm btn-outline-warning">Tum Gecmis</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Baslik</th>
                        <th>Gonderen</th>
                        <th>Tarih</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentBroadcasts as $broadcast)
                        <tr>
                            <td>{{ $broadcast->title }}</td>
                            <td>{{ $broadcast->sender?->name ?? '-' }}</td>
                            <td>{{ $broadcast->created_at?->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Duyuru kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($activeTab === 'rapor')
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon" style="background:#e8f4fd;color:#0d6efd;"><i class="fas fa-sms"></i></div>
                        <div>
                            <div class="fw-bold">{{ $channelCounts['sms'] }}</div>
                            <div class="text-muted small">SMS Log</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon" style="background:#fff3cd;color:#856404;"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="fw-bold">{{ $channelCounts['email'] }}</div>
                            <div class="text-muted small">Email Log</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span>Son Iletisim Kayitlari</span>
                <a href="{{ route('superadmin.sms.raporlar') }}" class="btn btn-sm btn-outline-success">Detayli Raporlar</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Kanal</th>
                        <th>Hedef</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ strtoupper($log->channel ?? '-') }}</td>
                            <td>{{ $log->recipient ?? '-' }}</td>
                            <td>{{ $log->status ?? '-' }}</td>
                            <td>{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Iletisim logu yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($activeTab === 'ai')
        <div class="card shadow-sm border-info mb-3">
            <div class="card-header fw-bold d-flex align-items-center gap-2" style="background:#e8f4fd;border-bottom:1px solid #b8daff;">
                <i class="fas fa-robot text-info"></i>
                <span>AI Kutlama ve Ozel Gun Modulu</span>
            </div>
            <div class="card-body py-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <form method="POST" action="{{ route('superadmin.ai-kutlama.ayar') }}" class="d-flex align-items-center gap-3">
                        @csrf
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="ai_celebration_enabled" name="ai_celebration_enabled" value="1" {{ ($aiModuleEnabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="ai_celebration_enabled">AI kutlama modulu aktif</label>
                        </div>
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="fas fa-save me-1"></i>Kaydet
                        </button>
                    </form>

                    <form method="POST" action="{{ route('superadmin.ai-kutlama.tara') }}" class="d-flex align-items-center gap-2 flex-wrap">
                        @csrf
                        <label class="small text-muted mb-0">Tarama gunu</label>
                        <input type="number" min="1" max="30" name="days" value="7" class="form-control form-control-sm" style="max-width:90px;">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="force_refresh" name="force_refresh" value="1" checked>
                            <label class="form-check-label small" for="force_refresh">Cache sifirla</label>
                        </div>
                        <button class="btn btn-sm btn-info text-white" type="submit">
                            <i class="fas fa-sync-alt me-1"></i>Secileni Tara
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="fw-bold fs-5">{{ $aiStats['toplam'] ?? 0 }}</div>
                    <div class="text-muted small">Toplam Kampanya</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="fw-bold fs-5 text-success">{{ $aiStats['yayinda'] ?? 0 }}</div>
                    <div class="text-muted small">Yayında</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="fw-bold fs-5 text-primary">{{ $aiStats['taslak'] ?? 0 }}</div>
                    <div class="text-muted small">Taslak / Onayli</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card kpi-card shadow-sm p-3">
                    <div class="fw-bold fs-5 text-danger">{{ $aiStats['istenmeyen'] ?? 0 }}</div>
                    <div class="text-muted small">İstenmeyen</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold">
                <i class="fas fa-magic me-1 text-info"></i>Manuel AI Onerisi Uret
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('superadmin.ai-kutlama.manual') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label small mb-1">Özel gün / konu</label>
                            <input type="text" name="event_name" class="form-control form-control-sm" required placeholder="Orn: Ramazan Bayrami">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-1">Tarih</label>
                            <input type="date" name="event_date" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-1">Gösterim</label>
                            <select name="display_mode" class="form-select form-select-sm">
                                <option value="banner">Top Banner</option>
                                <option value="popup">Popup</option>
                                <option value="card">Dashboard Card</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-1">Frekans</label>
                            <input type="number" min="1" max="20" name="frequency_cap" value="1" class="form-control form-control-sm">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-1">Öncelik</label>
                            <input type="number" min="1" max="999" name="priority" value="100" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label small mb-1">Yayın başlangıç</label>
                            <input type="datetime-local" name="publish_starts_at" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label small mb-1">Yayın bitiş</label>
                            <input type="datetime-local" name="publish_ends_at" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-lg-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="show_on_public" value="1" id="manual_show_on_public">
                                <label class="form-check-label small" for="manual_show_on_public">
                                    Üye girişi gerekmeyen sayfalarda da göster
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1">Gemini konu/prompt</label>
                            <textarea name="topic_prompt" rows="3" class="form-control" required placeholder="Kutlama dili, ton, gorsel tarz beklentisi..."></textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-info text-white" type="submit">
                            <i class="fas fa-sparkles me-1"></i>AI Önerisi Oluştur
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header fw-bold">Aktif / Taslak Kayıtlar</div>
            <div class="card-body">
                @if(($aiCampaigns ?? collect())->isEmpty())
                    <div class="text-muted small">Henüz AI kutlama kaydı yok.</div>
                @else
                    @foreach($aiCampaigns as $campaign)
                        @php
                            $statusColor = match($campaign->status) {
                                'published' => 'success',
                                'approved' => 'primary',
                                default => 'secondary'
                            };
                            $imageSource = data_get($campaign->ai_payload, 'image_generation.source');
                            $imageError = data_get($campaign->ai_payload, 'image_generation.error');
                        @endphp
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
                                <div>
                                    <div class="fw-bold">{{ $campaign->event_name }}</div>
                                    <div class="small text-muted">
                                        Tarih: {{ $campaign->event_date?->format('d.m.Y') ?? '-' }} ·
                                        Gosterim: {{ strtoupper($campaign->display_mode) }} ·
                                        Goren: {{ $campaign->seen_users_count ?? 0 }} ·
                                        Kapatan: {{ $campaign->closed_users_count ?? 0 }}
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    @if($imageSource === 'gemini')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Görsel: AI</span>
                                    @elseif($imageSource === 'fallback')
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Görsel: Fallback</span>
                                    @endif
                                    <span class="badge bg-{{ $statusColor }}">{{ strtoupper($campaign->status) }}</span>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('superadmin.ai-kutlama.onizleme', $campaign) }}" target="_blank">Önizleme</a>
                                    <form method="POST" action="{{ route('superadmin.ai-kutlama.yeniden-uret', $campaign) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-info">Yeniden Üret</button>
                                    </form>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                @if($campaign->image_path)
                                    <div class="col-12 col-lg-3">
                                        <img src="{{ $campaign->image_path }}" alt="AI gorsel" class="img-fluid rounded border">
                                        @if($imageError)
                                            <div class="small text-warning mt-2">Not: {{ $imageError }}</div>
                                        @endif
                                    </div>
                                @endif
                                <div class="col-12 {{ $campaign->image_path ? 'col-lg-9' : '' }}">
                                    <form method="POST" action="{{ route('superadmin.ai-kutlama.guncelle', $campaign) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="show_on_authenticated" value="1">
                                        <div class="row g-2">
                                            <div class="col-12 col-lg-4">
                                                <label class="form-label small mb-1">Özel gün</label>
                                                <input type="text" name="event_name" value="{{ $campaign->event_name }}" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="col-6 col-lg-2">
                                                <label class="form-label small mb-1">Tarih</label>
                                                <input type="date" name="event_date" value="{{ $campaign->event_date?->format('Y-m-d') }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6 col-lg-2">
                                                <label class="form-label small mb-1">Kategori</label>
                                                <input type="text" name="category" value="{{ $campaign->category }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6 col-lg-2">
                                                <label class="form-label small mb-1">Gösterim</label>
                                                <select name="display_mode" class="form-select form-select-sm">
                                                    <option value="banner" {{ $campaign->display_mode === 'banner' ? 'selected' : '' }}>Banner</option>
                                                    <option value="popup" {{ $campaign->display_mode === 'popup' ? 'selected' : '' }}>Popup</option>
                                                    <option value="card" {{ $campaign->display_mode === 'card' ? 'selected' : '' }}>Card</option>
                                                </select>
                                            </div>
                                            <div class="col-6 col-lg-2">
                                                <label class="form-label small mb-1">Frekans</label>
                                                <input type="number" min="1" max="20" name="frequency_cap" value="{{ $campaign->frequency_cap }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6 col-lg-2">
                                                <label class="form-label small mb-1">Öncelik</label>
                                                <input type="number" min="1" max="999" name="priority" value="{{ $campaign->priority }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6 col-lg-5">
                                                <label class="form-label small mb-1">Başlık</label>
                                                <input type="text" name="title" value="{{ $campaign->title }}" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="col-6 col-lg-3">
                                                <label class="form-label small mb-1">Buton Metni</label>
                                                <input type="text" name="cta_text" value="{{ $campaign->cta_text }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-12 col-lg-4">
                                                <label class="form-label small mb-1">Buton linki</label>
                                                <input type="text" name="cta_url" value="{{ $campaign->cta_url }}" class="form-control form-control-sm" placeholder="/dashboard">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small mb-1">Mesaj</label>
                                                <textarea name="message" rows="3" class="form-control form-control-sm" required>{{ $campaign->message }}</textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small mb-1">AI Topic Prompt</label>
                                                <textarea name="topic_prompt" rows="2" class="form-control form-control-sm">{{ $campaign->topic_prompt }}</textarea>
                                            </div>
                                            <div class="col-6 col-lg-4">
                                                <label class="form-label small mb-1">Yayın başlangıç</label>
                                                <input type="datetime-local" name="publish_starts_at" value="{{ $campaign->publish_starts_at?->format('Y-m-d\\TH:i') }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6 col-lg-4">
                                                <label class="form-label small mb-1">Yayın bitiş</label>
                                                <input type="datetime-local" name="publish_ends_at" value="{{ $campaign->publish_ends_at?->format('Y-m-d\\TH:i') }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-12 col-lg-4 d-flex align-items-end">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="show_on_public" value="1" id="show_public_{{ $campaign->id }}" {{ $campaign->show_on_public ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="show_public_{{ $campaign->id }}">Public sayfalarda göster</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 d-flex flex-wrap gap-2">
                                            <button class="btn btn-sm btn-primary" type="submit">Kaydet</button>
                                    </form>

                                    <form method="POST" action="{{ route('superadmin.ai-kutlama.yayinla', $campaign) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-success" type="submit">Yayına Al</button>
                                    </form>

                                    <form method="POST" action="{{ route('superadmin.ai-kutlama.durdur', $campaign) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning" type="submit">Yayından Kaldır</button>
                                    </form>

                                    <form method="POST" action="{{ route('superadmin.ai-kutlama.istenmeyen', $campaign) }}" class="d-inline" onsubmit="return confirm('Bu kayit istenmeyen listesine tasinsin mi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">İstenmeyen Yap</button>
                                    </form>
                                        </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-bold text-danger">İstenmeyen Listesi</div>
            <div class="card-body">
                @if(($aiDismissedCampaigns ?? collect())->isEmpty())
                    <div class="text-muted small">İstenmeyen listesinde kayıt yok.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Etkinlik</th>
                                    <th>Tarih</th>
                                    <th>Neden</th>
                                    <th>Islem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($aiDismissedCampaigns as $dismissedCampaign)
                                    <tr>
                                        <td>{{ $dismissedCampaign->event_name }}</td>
                                        <td>{{ $dismissedCampaign->event_date?->format('d.m.Y') ?? '-' }}</td>
                                        <td>{{ $dismissedCampaign->dismiss_reason ?: '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('superadmin.ai-kutlama.geri-al', $dismissedCampaign) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Geri Al</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

    @if($activeTab === 'sirket')
    <form method="POST" action="{{ route('superadmin.sirket.guncelle') }}">
        @csrf

        {{-- ── Şirket Kimlik Bilgileri ── --}}
        <div class="card shadow-sm mb-4" style="border:none;border-radius:16px;overflow:hidden;">
            <div class="card-header d-flex align-items-center gap-2 py-3 px-4"
                 style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);border:none;">
                <div style="width:36px;height:36px;background:rgba(233,69,96,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-building" style="color:#e94560;font-size:0.9rem;"></i>
                </div>
                <div>
                    <div class="fw-bold text-white" style="font-size:0.95rem;">Şirket Kimlik Bilgileri</div>
                    <div style="color:rgba(255,255,255,0.45);font-size:0.75rem;">Ünvan, vergi ve TÜRSAB bilgileri</div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-briefcase me-1 text-muted"></i>Şirket Tam Ünvanı
                        </label>
                        <input type="text" name="sirket_unvan" class="form-control"
                               placeholder="Grup Talepleri Turizm San. ve Tic. Ltd. Şti."
                               value="{{ $sirketBilgileri['sirket_unvan'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-id-card me-1 text-muted"></i>Vergi Kimlik No (VKN)
                        </label>
                        <input type="text" name="sirket_vkn" class="form-control font-monospace"
                               placeholder="1234567890"
                               value="{{ $sirketBilgileri['sirket_vkn'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-landmark me-1 text-muted"></i>Vergi Dairesi
                        </label>
                        <input type="text" name="sirket_vergi_dairesi" class="form-control"
                               placeholder="Şişli Vergi Dairesi"
                               value="{{ $sirketBilgileri['sirket_vergi_dairesi'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-certificate me-1 text-muted"></i>TÜRSAB Belge No
                        </label>
                        <div class="input-group">
                            <input type="text" name="sirket_tursab_no" class="form-control font-monospace"
                                   placeholder="12572"
                                   value="{{ $sirketBilgileri['sirket_tursab_no'] ?? '' }}">
                            <select name="sirket_tursab_grup" class="form-select" style="max-width:80px;">
                                @foreach(['A','B','C','AG'] as $g)
                                    <option value="{{ $g }}" {{ ($sirketBilgileri['sirket_tursab_grup'] ?? '') === $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-fingerprint me-1 text-muted"></i>Mersis No
                        </label>
                        <input type="text" name="sirket_mersis_no" class="form-control font-monospace"
                               placeholder="0411047752900001"
                               value="{{ $sirketBilgileri['sirket_mersis_no'] ?? '' }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-map-marker-alt me-1 text-muted"></i>Adres
                        </label>
                        <textarea name="sirket_adres" class="form-control" rows="2"
                                  placeholder="İnönü Mah. Cumhuriyet Cad. No:93/12 Şişli / İstanbul">{{ $sirketBilgileri['sirket_adres'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── İletişim Bilgileri ── --}}
        <div class="card shadow-sm mb-4" style="border:none;border-radius:16px;overflow:hidden;">
            <div class="card-header d-flex align-items-center gap-2 py-3 px-4"
                 style="background:linear-gradient(135deg,#0f3460 0%,#16213e 100%);border:none;">
                <div style="width:36px;height:36px;background:rgba(233,69,96,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-headset" style="color:#e94560;font-size:0.9rem;"></i>
                </div>
                <div>
                    <div class="fw-bold text-white" style="font-size:0.95rem;">İletişim Bilgileri</div>
                    <div style="color:rgba(255,255,255,0.45);font-size:0.75rem;">Telefon, WhatsApp ve e-posta</div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-phone me-1 text-muted"></i>Telefon (Sabit / Merkez)
                        </label>
                        <input type="text" name="sirket_telefon" class="form-control"
                               placeholder="+90 212 000 00 00"
                               value="{{ $sirketBilgileri['sirket_telefon'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-mobile-alt me-1 text-muted"></i>Cep Telefonu
                        </label>
                        <input type="text" name="sirket_cep" class="form-control"
                               placeholder="0 532 426 26 30"
                               value="{{ $sirketBilgileri['sirket_cep'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fab fa-whatsapp me-1" style="color:#25d366;"></i>WhatsApp
                            <span class="badge bg-success ms-1" style="font-size:0.65rem;">Acil hattı</span>
                        </label>
                        <input type="text" name="sirket_whatsapp" class="form-control"
                               placeholder="+90 535 415 47 99"
                               value="{{ $sirketBilgileri['sirket_whatsapp'] ?? '' }}">
                        <div class="form-text" style="font-size:0.72rem;">TURAi bu numaraya yönlendirecek</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fas fa-envelope me-1 text-muted"></i>E-posta
                        </label>
                        <input type="email" name="sirket_eposta" class="form-control"
                               placeholder="destek@gruptalepleri.com"
                               value="{{ $sirketBilgileri['sirket_eposta'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fab fa-instagram me-1" style="color:#e1306c;"></i>Instagram Kullanıcı Adı
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:0.8rem;background:#f8f9fa;">@</span>
                            <input type="text" name="sirket_instagram" class="form-control"
                                   placeholder="grup.talepleri"
                                   value="{{ $sirketBilgileri['sirket_instagram'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fab fa-facebook me-1" style="color:#1877f2;"></i>Facebook Sayfası URL
                        </label>
                        <input type="text" name="sirket_facebook" class="form-control"
                               placeholder="https://facebook.com/gruptalepleri"
                               value="{{ $sirketBilgileri['sirket_facebook'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fab fa-x-twitter me-1" style="color:#000;"></i>X (Twitter) Kullanıcı Adı
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:0.8rem;background:#f8f9fa;">@</span>
                            <input type="text" name="sirket_twitter" class="form-control"
                                   placeholder="gruptalepleri"
                                   value="{{ $sirketBilgileri['sirket_twitter'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:#495057;">
                            <i class="fab fa-linkedin me-1" style="color:#0a66c2;"></i>LinkedIn Sayfası URL
                        </label>
                        <input type="text" name="sirket_linkedin" class="form-control"
                               placeholder="https://linkedin.com/company/gruptalepleri"
                               value="{{ $sirketBilgileri['sirket_linkedin'] ?? '' }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Banka / Ödeme Bilgileri ── --}}
        <div class="card shadow-sm mb-4" style="border:none;border-radius:16px;overflow:hidden;">
            <div class="card-header d-flex align-items-center gap-2 py-3 px-4"
                 style="background:linear-gradient(135deg,#1a3a1a 0%,#1e4a1e 100%);border:none;">
                <div style="width:36px;height:36px;background:rgba(40,167,69,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-university" style="color:#28a745;font-size:0.9rem;"></i>
                </div>
                <div>
                    <div class="fw-bold text-white" style="font-size:0.95rem;">Banka / Ödeme Hesapları</div>
                    <div style="color:rgba(255,255,255,0.45);font-size:0.75rem;">TRY, USD, EUR ve farklı bankalar — TURAi ve ödeme sayfalarında otomatik kullanılır</div>
                </div>
            </div>
            <div class="card-body p-4">

                @php
                $bankaSlotlar = [
                    ['key'=>'1', 'label'=>'1. Hesap', 'color'=>'#1a3a1a', 'badge'=>'Ana Hesap'],
                    ['key'=>'2', 'label'=>'2. Hesap', 'color'=>'#1a2a3a', 'badge'=>''],
                    ['key'=>'3', 'label'=>'3. Hesap', 'color'=>'#2a1a3a', 'badge'=>''],
                    ['key'=>'4', 'label'=>'4. Hesap', 'color'=>'#3a1a1a', 'badge'=>''],
                ];
                @endphp

                <div class="accordion" id="bankaAccordion">
                @foreach($bankaSlotlar as $slot)
                    @php
                    $sk = $slot['key'];
                    $bAdi    = $sirketBilgileri['banka_adi_'.$sk]    ?? ($sk==='1' ? ($sirketBilgileri['banka_adi']    ?? '') : '');
                    $bSube   = $sirketBilgileri['banka_sube_'.$sk]   ?? ($sk==='1' ? ($sirketBilgileri['banka_sube']   ?? '') : '');
                    $bSahip  = $sirketBilgileri['banka_hesap_sahibi_'.$sk] ?? ($sk==='1' ? ($sirketBilgileri['banka_hesap_sahibi'] ?? '') : '');
                    $bIban   = $sirketBilgileri['banka_iban_'.$sk]   ?? ($sk==='1' ? ($sirketBilgileri['banka_iban']   ?? '') : '');
                    $bDoviz  = $sirketBilgileri['banka_doviz_'.$sk]  ?? 'TRY';
                    $bNot    = $sirketBilgileri['banka_aciklama_'.$sk] ?? ($sk==='1' ? ($sirketBilgileri['banka_aciklama'] ?? '') : '');
                    $dolu    = !empty($bIban);
                    @endphp
                    <div class="accordion-item mb-2" style="border:1.5px solid {{ $dolu ? '#b7dfb8' : '#e0e3e8' }};border-radius:12px !important;overflow:hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $sk !== '1' && !$dolu ? 'collapsed' : '' }} py-2 px-3"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#banka-collapse-{{ $sk }}"
                                    style="font-size:0.85rem;font-weight:600;background:{{ $dolu ? '#f0fff0' : '#f8f9fa' }};">
                                <span class="me-2">
                                    @if($dolu)<i class="fas fa-check-circle text-success me-1"></i>@else<i class="fas fa-circle text-muted me-1" style="font-size:0.5rem;"></i>@endif
                                </span>
                                {{ $slot['label'] }}
                                @if($dolu)
                                    <span class="ms-2 badge" style="background:#1a3a1a;font-size:0.65rem;">{{ strtoupper($bDoviz) }} · {{ $bAdi }}</span>
                                    <span class="ms-1 font-monospace text-muted" style="font-size:0.72rem;">TR{{ substr($bIban, -4) }}</span>
                                @endif
                                @if($slot['badge'])<span class="ms-auto badge bg-danger" style="font-size:0.65rem;">{{ $slot['badge'] }}</span>@endif
                            </button>
                        </h2>
                        <div id="banka-collapse-{{ $sk }}" class="accordion-collapse collapse {{ ($sk === '1' || $dolu) ? 'show' : '' }}">
                            <div class="accordion-body p-3">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold" style="font-size:0.78rem;color:#495057;">Para Birimi</label>
                                        <select name="banka_doviz_{{ $sk }}" class="form-select form-select-sm">
                                            @foreach(['TRY'=>'₺ TRY','USD'=>'$ USD','EUR'=>'€ EUR','GBP'=>'£ GBP'] as $kod=>$etiket)
                                                <option value="{{ $kod }}" {{ $bDoviz === $kod ? 'selected' : '' }}>{{ $etiket }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold" style="font-size:0.78rem;color:#495057;">Banka Adı</label>
                                        <input type="text" name="banka_adi_{{ $sk }}" class="form-control form-control-sm"
                                               placeholder="Ziraat Bankası" value="{{ $bAdi }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold" style="font-size:0.78rem;color:#495057;">Şube</label>
                                        <input type="text" name="banka_sube_{{ $sk }}" class="form-control form-control-sm"
                                               placeholder="Şişli Şubesi" value="{{ $bSube }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size:0.78rem;color:#495057;">Hesap Sahibi</label>
                                        <input type="text" name="banka_hesap_sahibi_{{ $sk }}" class="form-control form-control-sm"
                                               placeholder="Grup Talepleri Turizm..." value="{{ $bSahip }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" style="font-size:0.78rem;color:#495057;">IBAN (TR hariç)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold" style="font-size:0.78rem;">TR</span>
                                            <input type="text" name="banka_iban_{{ $sk }}" class="form-control font-monospace"
                                                   placeholder="00 0000 0000 0000 0000 0000 00"
                                                   value="{{ $bIban }}"
                                                   oninput="this.value=this.value.replace(/[^0-9\s]/g,'')">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold" style="font-size:0.78rem;color:#495057;">
                                            Havale Açıklama Notu
                                            <span class="badge bg-secondary ms-1" style="font-size:0.6rem;">TURAi aktarır</span>
                                        </label>
                                        <input type="text" name="banka_aciklama_{{ $sk }}" class="form-control form-control-sm"
                                               placeholder="Açıklama kısmına GTPNR numaranızı yazınız."
                                               value="{{ $bNot }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                </div>

                {{-- Eski key'lerden geçiş (geriye uyumluluk) --}}
                <input type="hidden" name="banka_adi"          value="{{ $sirketBilgileri['banka_adi_1'] ?? ($sirketBilgileri['banka_adi'] ?? '') }}">
                <input type="hidden" name="banka_sube"         value="{{ $sirketBilgileri['banka_sube_1'] ?? ($sirketBilgileri['banka_sube'] ?? '') }}">
                <input type="hidden" name="banka_hesap_sahibi" value="{{ $sirketBilgileri['banka_hesap_sahibi_1'] ?? ($sirketBilgileri['banka_hesap_sahibi'] ?? '') }}">
                <input type="hidden" name="banka_iban"         value="{{ $sirketBilgileri['banka_iban_1'] ?? ($sirketBilgileri['banka_iban'] ?? '') }}">
                <input type="hidden" name="banka_aciklama"     value="{{ $sirketBilgileri['banka_aciklama_1'] ?? ($sirketBilgileri['banka_aciklama'] ?? '') }}">
            </div>
        </div>

        {{-- Kaydet --}}
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-lg px-5 fw-bold"
                    style="background:linear-gradient(135deg,#1a1a2e 0%,#e94560 100%);color:#fff;border:none;border-radius:12px;box-shadow:0 4px 20px rgba(233,69,96,0.35);">
                <i class="fas fa-save me-2"></i>Bilgileri Kaydet
            </button>
        </div>
    </form>
    @endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

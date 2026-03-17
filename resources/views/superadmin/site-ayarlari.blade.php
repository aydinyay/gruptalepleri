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
        <div class="card shadow-sm border-info">
            <div class="card-header fw-bold d-flex align-items-center gap-2" style="background:#e8f4fd;border-bottom:1px solid #b8daff;">
                <i class="fas fa-robot text-info"></i>
                <span>AI Kutlama ve Ozel Gun Modulu</span>
            </div>
            <div class="card-body">
                <p class="mb-2">Bu adimda hedeflenen akis:</p>
                <p class="mb-1 small text-muted">1. Sistem onumuzdeki 7 gunu tarar ve ozel gun onerileri uretir.</p>
                <p class="mb-1 small text-muted">2. Superadmin gorsel/metni duzenler, onaylar ve yayin saatini belirler.</p>
                <p class="mb-1 small text-muted">3. Kullanicida tekil gosterim kurali ile spam olusmadan yayinlanir.</p>
                <p class="mb-0 small text-muted">4. Kim gordu / kim kapatti raporlanir, silinenler istenmeyen listesine gider.</p>
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

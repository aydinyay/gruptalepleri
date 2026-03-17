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
        .module-card { border:none; border-radius:12px; transition:all 0.2s; }
        .module-card:hover { transform:translateY(-2px); box-shadow:0 0.4rem 1rem rgba(0,0,0,0.08); }
        .module-icon {
            width:44px; height:44px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.1rem;
        }
    </style>
</head>
<body>

<x-navbar-superadmin active="site-ayarlar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-cogs me-2" style="color:#e94560;"></i>Site Ayarlari Merkezi</h5>
        <p>Bildirim, duyuru ve iletisim modullerini tek yerden yonetin.</p>
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
                        <i class="fas fa-save me-1"></i> Sistem Durumlarini Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card module-card shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="module-icon" style="background:#e8f4fd;color:#0d6efd;">
                            <i class="fas fa-sms"></i>
                        </div>
                        <div>
                            <div class="fw-bold">SMS ve Opsiyon Ayarlari</div>
                            <div class="text-muted small">SMS kurallari, saat araligi ve opsiyon uyarilari</div>
                            <div class="small mt-1">
                                <span class="badge bg-light text-dark border me-1">SMS Kural: {{ $stats['sms_kural'] }}</span>
                                <span class="badge bg-light text-dark border">Opsiyon Kural: {{ $stats['opsiyon_kural'] }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('superadmin.sms.ayarlar') }}" class="btn btn-sm btn-outline-primary">Ac</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card module-card shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="module-icon" style="background:#fff3cd;color:#856404;">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Duyuru ve Broadcast</div>
                            <div class="text-muted small">Duyuru gecmisi, yetkiler ve temizleme islemleri</div>
                            <div class="small mt-1">
                                <span class="badge bg-light text-dark border">Toplam Duyuru: {{ $stats['duyuru'] }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('superadmin.broadcast.gecmisi') }}" class="btn btn-sm btn-outline-warning">Ac</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card module-card shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="module-icon" style="background:#d1e7dd;color:#0a3622;">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Iletisim Raporlari</div>
                            <div class="text-muted small">SMS ve Email gonderim loglari ve durum takibi</div>
                            <div class="small mt-1">
                                <span class="badge bg-light text-dark border">Toplam Log: {{ $stats['iletisim_log'] }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('superadmin.sms.raporlar') }}" class="btn btn-sm btn-outline-success">Ac</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card module-card shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="module-icon" style="background:#fce8ff;color:#6f42c1;">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Ozel Gun ve AI Yayinlari</div>
                            <div class="text-muted small">Sonraki adim: superadmin onayli kutlama/duyuru otomasyonu</div>
                            <div class="small mt-1">
                                <span class="badge bg-light text-dark border">Yakin zamanda eklenecek</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Hazirlaniyor</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

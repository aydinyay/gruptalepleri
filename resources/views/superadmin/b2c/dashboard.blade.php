<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>B2C Dashboard — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .stat-card { background:#fff; border-radius:10px; padding:1.25rem 1.5rem; border-left:4px solid #1a3c6b; }
        .stat-card .stat-num { font-size:2rem; font-weight:800; color:#1a1a2e; }
        .stat-card .stat-label { font-size:.82rem; color:#6c757d; font-weight:500; }
        .b2c-badge { background:#1a3c6b; color:#fff; padding:3px 8px; border-radius:4px; font-size:.7rem; font-weight:600; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-store me-2" style="color:#e8a020;"></i>B2C Vitrin Yönetimi</h5>
        <p>gruprezervasyonlari.com ürün kataloğu ve müşteri yönetimi</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- İstatistikler --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#1a3c6b;">
                <div class="stat-num">{{ $stats['total_items'] }}</div>
                <div class="stat-label">Toplam Ürün</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#27ae60;">
                <div class="stat-num text-success">{{ $stats['published_items'] }}</div>
                <div class="stat-label">Yayında</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#e67e22;">
                <div class="stat-num text-warning">{{ $stats['pending_publish'] }}</div>
                <div class="stat-label">Yayın Bekliyor</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-color:#e74c3c;">
                <div class="stat-num text-danger">{{ $stats['pending_supplier_apps'] }}</div>
                <div class="stat-label">Tedarikçi Başvurusu</div>
            </div>
        </div>
    </div>

    {{-- Hızlı Linkler --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-4">
            <a href="{{ route('superadmin.b2c.catalog') }}" class="btn btn-primary w-100">
                <i class="fas fa-list me-1"></i>Katalog
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="{{ route('superadmin.b2c.catalog.create') }}" class="btn btn-success w-100">
                <i class="fas fa-plus me-1"></i>Yeni Ürün
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="{{ route('superadmin.b2c.categories') }}" class="btn btn-secondary w-100">
                <i class="fas fa-tags me-1"></i>Kategoriler
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="{{ route('superadmin.b2c.supplier-apps') }}" class="btn btn-warning w-100">
                <i class="fas fa-building me-1"></i>Başvurular
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="https://gruprezervasyonlari.com" target="_blank" class="btn btn-outline-primary w-100">
                <i class="fas fa-external-link-alt me-1"></i>Siteyi Gör
            </a>
        </div>
    </div>

    {{-- Leisure Hizmetler --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-ship me-2 text-primary"></i>Leisure Hizmetler <small class="text-muted fw-normal">(Dinner Cruise · Yacht Charter · Günübirlik Tur)</small></h6>
            <a href="{{ route('superadmin.leisure.settings.index') }}" class="btn btn-sm btn-outline-secondary">Düzenle</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr><th>Hizmet</th><th>Tip</th><th>Fiyat</th><th>B2B</th><th>B2C Durumu</th><th>B2C Toggle</th></tr>
                </thead>
                <tbody>
                @forelse($leisureTemplates as $tpl)
                @php
                    $typeLabel = match($tpl->product_type) {
                        'dinner_cruise'  => 'Dinner Cruise',
                        'yacht_charter'  => 'Yacht Charter',
                        'gunubirlik_tur' => 'Günübirlik Tur',
                        'tour'           => 'Tur',
                        default          => $tpl->product_type,
                    };
                    $ci = $tpl->catalogItem;
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $tpl->name_tr }}</div>
                        @if($tpl->hero_image_url)
                        <img src="{{ $tpl->hero_image_url }}" style="height:32px;border-radius:4px;object-fit:cover;" alt="">
                        @endif
                    </td>
                    <td><span class="badge bg-info text-dark">{{ $typeLabel }}</span></td>
                    <td>
                        @if($tpl->base_price_per_person)
                            {{ number_format($tpl->base_price_per_person, 0, ',', '.') }} {{ $tpl->currency ?? 'EUR' }}<small class="text-muted">/kişi</small>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($tpl->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Pasif</span>
                        @endif
                    </td>
                    <td>
                        @if($ci)
                            @if($ci->is_published)
                                <span class="badge bg-success"><i class="fas fa-eye me-1"></i>Yayında</span>
                            @else
                                <span class="badge bg-warning text-dark">Taslak</span>
                            @endif
                        @else
                            <span class="text-muted small">Eklenmedi</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('superadmin.b2c.leisure.toggle-publish', $tpl) }}">
                            @csrf
                            @if($ci && $ci->is_published)
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-eye-slash me-1"></i>Kaldır
                                </button>
                            @else
                                <button class="btn btn-sm btn-success">
                                    <i class="fas fa-eye me-1"></i>Yayına Al
                                </button>
                            @endif
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">Leisure şablonu bulunamadı.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Transfer Araçları --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="fas fa-car me-2 text-warning"></i>Transfer Araç Tipleri</h6>
            <a href="{{ route('superadmin.transfer.ops.index') }}" class="btn btn-sm btn-outline-secondary">Düzenle</a>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead class="table-light">
                    <tr><th>Araç</th><th>Kapasite</th><th>Fiyat (önerilen)</th><th>B2C Durumu</th><th>B2C Toggle</th></tr>
                </thead>
                <tbody>
                @forelse($transferVehicleTypes as $vt)
                @php $ci = $vt->catalogItem; @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($vt->firstPhotoUrl())
                                <img src="{{ $vt->firstPhotoUrl() }}" style="width:48px;height:36px;object-fit:cover;border-radius:4px;" alt="">
                            @endif
                            <div class="fw-semibold">{{ $vt->name }}</div>
                        </div>
                    </td>
                    <td>
                        <span class="text-muted small"><i class="fas fa-users me-1"></i>Maks. {{ $vt->max_passengers }} kişi</span>
                    </td>
                    <td>
                        @if($vt->suggested_retail_price)
                            {{ number_format($vt->suggested_retail_price, 0, ',', '.') }} TRY
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($ci)
                            @if($ci->is_published)
                                <span class="badge bg-success"><i class="fas fa-eye me-1"></i>Yayında</span>
                            @else
                                <span class="badge bg-warning text-dark">Taslak</span>
                            @endif
                        @else
                            <span class="text-muted small">Eklenmedi</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('superadmin.b2c.transfer-vehicle.toggle-publish', $vt) }}">
                            @csrf
                            @if($ci && $ci->is_published)
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-eye-slash me-1"></i>Kaldır
                                </button>
                            @else
                                <button class="btn btn-sm btn-success">
                                    <i class="fas fa-eye me-1"></i>Yayına Al
                                </button>
                            @endif
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">Transfer aracı bulunamadı.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        {{-- Son Ürünler --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                    <h6 class="mb-0 fw-700">Son Eklenen Ürünler</h6>
                    <a href="{{ route('superadmin.b2c.catalog') }}" class="btn btn-sm btn-outline-primary">Tümü</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" style="font-size:.875rem;">
                        <thead class="table-light"><tr>
                            <th>Ürün</th><th>Tip</th><th>Fiyat</th><th>Durum</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($latestItems as $item)
                        <tr>
                            <td>
                                <div class="fw-600">{{ Str::limit($item->title, 45) }}</div>
                                <small class="text-muted">{{ $item->category->name ?? '—' }}</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $item->product_type }}</span></td>
                            <td>
                                @if($item->base_price)
                                    {{ number_format($item->base_price,0,',','.') }} {{ $item->currency }}
                                @else
                                    <span class="text-muted">Teklif</span>
                                @endif
                            </td>
                            <td>
                                @if($item->is_published)
                                    <span class="badge bg-success">Yayında</span>
                                @else
                                    <span class="badge bg-warning text-dark">Taslak</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('superadmin.b2c.catalog.edit', $item) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Henüz ürün yok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tedarikçi Başvuruları --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                    <h6 class="mb-0 fw-700">Bekleyen Başvurular</h6>
                    <a href="{{ route('superadmin.b2c.supplier-apps') }}" class="btn btn-sm btn-outline-warning">Tümü</a>
                </div>
                <div class="card-body p-0">
                    @forelse($pendingApps as $app)
                    <div class="border-bottom px-3 py-2">
                        <div class="fw-600" style="font-size:.88rem;">{{ $app->company_name }}</div>
                        <div style="font-size:.78rem;color:#6c757d;">{{ $app->applicant_name }} · {{ $app->created_at->diffForHumans() }}</div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4" style="font-size:.88rem;">Bekleyen başvuru yok</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

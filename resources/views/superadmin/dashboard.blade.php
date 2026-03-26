<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Süperadmin Paneli — GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .stat-card-link { display: block; color: inherit; text-decoration: none; }
        .stat-card-link:focus-visible { outline: 2px solid #0d6efd; outline-offset: 2px; border-radius: 12px; }
        .stat-card { border: none; border-radius: 12px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

        .section-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #6c757d; margin-bottom: 1rem; }

        .table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .table td { vertical-align: middle; font-size: 0.875rem; }

        .status-badge { padding: 4px 10px; border-radius: 50px; font-size: 0.72rem; font-weight: 600; }
        .status-beklemede { background: #e9ecef; color: #495057; }
        .status-islemde { background: #cfe2ff; color: #084298; }
        .status-fiyatlandirildi { background: #fff3cd; color: #856404; }
        .status-biletlendi { background: #d1e7dd; color: #0a3622; }
        .status-depozito { background: #e8d5f5; color: #4a0072; }
        .status-iptal { background: #f8d7da; color: #842029; }

        .role-badge { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
        .role-superadmin { background: #1a1a2e; color: #e94560; }
        .role-admin { background: #084298; color: #fff; }
        .role-acente { background: #e9ecef; color: #495057; }

        .alert-opsiyon { background: linear-gradient(135deg, #fff3cd, #ffe5b4); border: 1px solid #ffc107; border-radius: 10px; }
        .opsiyon-item { border-bottom: 1px solid rgba(0,0,0,0.06); padding: 10px 0; }
        .opsiyon-item:last-child { border-bottom: none; }

        .quick-action { border: 2px dashed #dee2e6; border-radius: 12px; padding: 1.2rem; text-align: center; cursor: pointer; transition: all 0.2s; color: #6c757d; text-decoration: none; display: block; }
        .quick-action:hover { border-color: #e94560; color: #e94560; background: rgba(233,69,96,0.04); }
        .quick-action i { font-size: 1.5rem; display: block; margin-bottom: 0.5rem; }
        .quick-action span { font-size: 0.8rem; font-weight: 600; }

        .system-info { background: #1a1a2e; border-radius: 12px; color: white; padding: 1.2rem; }
        .system-info-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.07); font-size: 0.85rem; }
        .system-info-item:last-child { border-bottom: none; }
        .system-info-item .label { color: rgba(255,255,255,0.5); }
        .system-info-item .value { color: #fff; font-weight: 600; }
        .system-info-item .value.red { color: #e94560; }
        .system-info-item .value.green { color: #28a745; }

        .page-header { background: #1a1a2e; padding: 1.5rem 0; margin-bottom: 2rem; }
        .page-header h4 { color: #fff; font-weight: 700; margin: 0; }
        .page-header p { color: rgba(255,255,255,0.5); font-size: 0.85rem; margin: 0; }

        .btn-sm-red { background: #e94560; color: #fff; border: none; padding: 5px 12px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-sm-red:hover { background: #c73652; color: #fff; }
    </style>
</head>
<body>

<x-navbar-superadmin active="dashboard" />

{{-- PAGE HEADER --}}
<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-tachometer-alt me-2" style="color:#e94560;"></i>Sistem Kontrol Paneli</h4>
                <p>{{ now()->format('d F Y, H:i') }} · Tüm sistemler aktif</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.requests.index') }}" class="btn btn-sm-red">
                    <i class="fas fa-list me-1"></i> Tüm Talepler
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    {{-- İSTATİSTİK KARTLARI --}}
    @php
        $toplamKullanici = \App\Models\User::count();
        $toplamTalep = \App\Models\Request::count();
        $toplamTeklif = \App\Models\Offer::count();
        $toplamAcente = \App\Models\User::where('role', 'acente')->count();
        $bugunTalep = \App\Models\Request::whereDate('created_at', today())->count();
        $bekleyenTalep = \App\Models\Request::where('status', 'beklemede')->count();
        $biletlendi = \App\Models\Request::where('status', 'biletlendi')->count();
        $simdiSaat = now()->format('H:i:s');
        $opsiyonluTeklif = \App\Models\Offer::whereNotNull('option_date')
            ->where(function ($query) use ($simdiSaat) {
                $query->whereDate('option_date', '>', today())
                    ->orWhere(function ($todayQuery) use ($simdiSaat) {
                        $todayQuery->whereDate('option_date', today())
                            ->where(function ($timeQuery) use ($simdiSaat) {
                                // option_time bos ise varsayilan olarak gun sonuna kadar aktif sayilir
                                $timeQuery->whereNull('option_time')
                                    ->orWhere('option_time', '>=', $simdiSaat);
                            });
                    });
            })
            ->count();
        $depozito = \App\Models\Request::where('status', \App\Models\Request::STATUS_DEPOZITODA)->count();

        // Opsiyon kritik olanlar
        $kritikOpsiyonlar = \App\Models\Offer::whereNotNull('option_date')
            ->whereRaw("STR_TO_DATE(CONCAT(option_date, ' ', COALESCE(option_time, '23:59')), '%Y-%m-%d %H:%i') > NOW()")
            ->whereRaw("STR_TO_DATE(CONCAT(option_date, ' ', COALESCE(option_time, '23:59')), '%Y-%m-%d %H:%i') < DATE_ADD(NOW(), INTERVAL 48 HOUR)")
            ->with('request')
            ->orderByRaw("STR_TO_DATE(CONCAT(option_date, ' ', COALESCE(option_time, '23:59')), '%Y-%m-%d %H:%i') ASC")
            ->limit(5)
            ->get();

        // Son talepler — aktif durumlar (biletlendi/olumsuz/iade hariç)
        $sonTalepler = \App\Models\Request::with(['segments'])
            ->whereNotIn('status', ['biletlendi', 'olumsuz', 'iade', 'iptal'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Kullanıcılar
        $kullanicilar = \App\Models\User::with('agency')->orderBy('created_at', 'desc')->get();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <a href="#kullanici-yonetimi" class="stat-card-link">
                <div class="card stat-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#e8f4fd;color:#0d6efd;"><i class="fas fa-users"></i></div>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;">{{ $toplamKullanici }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Kullanici</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('superadmin.acenteler') }}" class="stat-card-link">
                <div class="card stat-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#fff3cd;color:#856404;"><i class="fas fa-building"></i></div>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;">{{ $toplamAcente }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Acente</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.requests.index', ['durum' => 'tumu']) }}" class="stat-card-link">
                <div class="card stat-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#d1e7dd;color:#0a3622;"><i class="fas fa-paper-plane"></i></div>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;">{{ $toplamTalep }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Toplam Talep</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.requests.index', ['durum' => 'beklemede']) }}" class="stat-card-link">
                <div class="card stat-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#f8d7da;color:#842029;"><i class="fas fa-clock"></i></div>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;">{{ $bekleyenTalep }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Bekleyen</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.requests.index', ['durum' => 'biletlendi']) }}" class="stat-card-link">
                <div class="card stat-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#d1e7dd;color:#0a3622;"><i class="fas fa-ticket-alt"></i></div>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;">{{ $biletlendi }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Biletlendi</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.requests.index', ['durum' => 'tumu', 'teklif' => 1]) }}" class="stat-card-link" title="Teklif = en az bir offer kaydi olan talepler">
                <div class="card stat-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#fce8ff;color:#6f42c1;"><i class="fas fa-tag"></i></div>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;">{{ $toplamTeklif }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Teklif</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.requests.index', ['durum' => 'tumu', 'opsiyon' => 1]) }}" class="stat-card-link">
                <div class="card stat-card shadow-sm p-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#fff3cd;color:#856404;"><i class="fas fa-hourglass-half"></i></div>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;">{{ $opsiyonluTeklif }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Opsiyonlar</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <a href="{{ route('admin.requests.index', ['durum' => 'tumu', 'tarih_baslangic' => today()->toDateString(), 'tarih_bitis' => today()->toDateString()]) }}" class="stat-card-link">
                <div class="card stat-card shadow-sm p-3" style="border-left:3px solid #e94560;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon" style="background:#fce8eb;color:#e94560;"><i class="fas fa-calendar-day"></i></div>
                        <div>
                            <div style="font-size:1.6rem;font-weight:700;color:#e94560;">{{ $bugunTalep }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">Bugun</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="row g-4">

        {{-- SOL KOLON --}}
        <div class="col-12 col-xl-8">

            {{-- KRİTİK OPSİYONLAR --}}
            @if($kritikOpsiyonlar->count() > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex align-items-center gap-2" style="background:#fff3cd;border-bottom:1px solid #ffc107;">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    <span class="fw-bold">Kritik Opsiyon Uyarıları</span>
                    <span class="badge bg-warning text-dark ms-1">{{ $kritikOpsiyonlar->count() }}</span>
                </div>
                <div class="card-body p-3">
                    @foreach($kritikOpsiyonlar as $teklif)
                    @php
                        $opsTs = \Carbon\Carbon::parse($teklif->option_date . ' ' . ($teklif->option_time ?? '23:59'));
                        $kalanSaat = \Carbon\Carbon::now()->diffInHours($opsTs, false);
                        $renk = $kalanSaat <= 6 ? 'danger' : 'warning';
                        $airlineLogo = app(\App\Services\AirlineLogoService::class)->resolve($teklif->airline);
                    @endphp
                    <div class="opsiyon-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold">{{ $teklif->request?->gtpnr ?? '—' }}</span>
                            <span class="text-muted ms-2 d-inline-flex align-items-center gap-2">
                                @if($airlineLogo['has_logo'])
                                    <img src="{{ $airlineLogo['path'] }}" alt="{{ $airlineLogo['display_name'] }}" style="width:24px;height:24px;object-fit:contain;">
                                @endif
                                <span>{{ $teklif->airline }}</span>
                            </span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $renk }}">
                                @if($kalanSaat <= 0) DOLDU
                                @elseif($kalanSaat < 24) {{ round($kalanSaat) }} saat kaldı
                                @else {{ floor($kalanSaat/24) }}g {{ $kalanSaat%24 }}s kaldı
                                @endif
                            </span>
                            <div class="small text-muted">{{ $opsTs->format('d.m.Y H:i') }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- SON TALEPLER --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold">📋 Son Talepler</span>
                    <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-outline-secondary">Tümünü Gör</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>GTPNR</th>
                                <th>Acente</th>
                                <th>Rota</th>
                                <th>PAX</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sonTalepler as $talep)
                            @php
                                $statusMap = [
                                    'beklemede' => ['class'=>'status-beklemede','label'=>'Beklemede'],
                                    'islemde' => ['class'=>'status-islemde','label'=>'İşlemde'],
                                    'fiyatlandirildi' => ['class'=>'status-fiyatlandirildi','label'=>'Fiyatlandırıldı'],
                                    'fiyatlandirildi' => ['class'=>'status-fiyatlandirildi','label'=>'Fiyatlandırıldı'],
                                    'biletlendi' => ['class'=>'status-biletlendi','label'=>'Biletlendi'],
                                    'depozitoda' => ['class'=>'status-depozito','label'=>'Depozitoda'],
                                    'iptal' => ['class'=>'status-iptal','label'=>'İptal'],
                                ];
                                $sc = $statusMap[$talep->status] ?? ['class'=>'status-beklemede','label'=>$talep->status];
                            @endphp
                            <tr>
                                <td><strong>{{ $talep->gtpnr }}</strong></td>
                                <td>
                                    @if(($talep->agency_name ?? '') === 'MÜNFERİT')
                                        <span class="badge" style="background:#20c997;color:#fff;">MÜNFERİT</span>
                                    @else
                                        {{ $talep->agency_name ?? '—' }}
                                    @endif
                                </td>
                                <td>
                                    @foreach($talep->segments as $seg)
                                        <span class="badge bg-light text-dark border" style="font-size:0.7rem;">{{ $seg->from_iata }}→{{ $seg->to_iata }}</span>
                                    @endforeach
                                </td>
                                <td>{{ $talep->pax_total }}</td>
                                <td><span class="status-badge {{ $sc['class'] }}">{{ $sc['label'] }}</span></td>
                                <td class="text-muted">{{ $talep->created_at->format('d.m.Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.requests.show', $talep->gtpnr) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- KULLANICI YÖNETİMİ --}}
            <div id="kullanici-yonetimi" class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold">👥 Kullanıcı Yönetimi</span>
                    <span class="badge bg-secondary">{{ $kullanicilar->count() }} kullanıcı</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ad</th>
                                <th>E-posta</th>
                                <th>Rol</th>
                                <th>Acente</th>
                                <th>Kayıt</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kullanicilar as $u)
                            <tr>
                                <td><strong>{{ $u->name }}</strong></td>
                                <td class="text-muted">{{ $u->email }}</td>
                                <td>
                                    <span class="role-badge role-{{ $u->role }}">{{ strtoupper($u->role) }}</span>
                                </td>
                                <td>{{ $u->agency?->company_title ?? '—' }}</td>
                                <td class="text-muted">{{ $u->created_at->format('d.m.Y') }}</td>
                                <td>
                                    @if($u->agency?->is_active ?? true)
                                        <span class="badge bg-success" style="font-size:0.7rem;">Aktif</span>
                                    @else
                                        <span class="badge bg-danger" style="font-size:0.7rem;">Pasif</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- SAĞ KOLON --}}
        <div class="col-12 col-xl-4">

            {{-- HIZLI İŞLEMLER --}}
            <div class="mb-4">
                <div class="section-title">Hızlı İşlemler</div>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ route('admin.requests.index') }}" class="quick-action">
                            <i class="fas fa-list"></i>
                            <span>Tüm Talepler</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('acente.requests.create') }}" class="quick-action">
                            <i class="fas fa-plus-circle"></i>
                            <span>Yeni Talep</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('superadmin.site.ayarlar') }}" class="quick-action">
                            <i class="fas fa-bell"></i>
                            <span>SMS Ayarları</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('superadmin.acenteler') }}" class="quick-action">
                            <i class="fas fa-building"></i>
                            <span>Acenteler</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- SİSTEM BİLGİSİ --}}
            <div class="mb-4">
                <div class="section-title">Sistem Durumu</div>
                <div class="system-info">
                    <div class="system-info-item">
                        <span class="label">Altyapı</span>
                        <span class="value green">v{{ app()->version() }}</span>
                    </div>
                    <div class="system-info-item">
                        <span class="label">PHP</span>
                        <span class="value green">v{{ PHP_VERSION }}</span>
                    </div>
                    <div class="system-info-item">
                        <span class="label">Ortam</span>
                        <span class="value {{ config('app.env') === 'production' ? 'green' : 'red' }}">{{ strtoupper(config('app.env')) }}</span>
                    </div>
                    <div class="system-info-item">
                        <span class="label">Veritabanı</span>
                        <span class="value green">MySQL · Aktif</span>
                    </div>
                    <div class="system-info-item">
                        <span class="label">Son Talep</span>
                        <span class="value">{{ \App\Models\Request::latest()->first()?->created_at->diffForHumans() ?? '—' }}</span>
                    </div>
                    <div class="system-info-item">
                        <span class="label">Bekleyen Talep</span>
                        <span class="value {{ $bekleyenTalep > 0 ? 'red' : 'green' }}">{{ $bekleyenTalep }}</span>
                    </div>
                </div>
            </div>

            {{-- DURUM DAĞILIMI --}}
            <div class="mb-4">
                <div class="section-title">Talep Dağılımı</div>
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        @php
                        $dagilim = [
                            ['label'=>'Beklemede', 'count'=>\App\Models\Request::where('status','beklemede')->count(), 'color'=>'#6c757d'],
                            ['label'=>'İşlemde', 'count'=>\App\Models\Request::where('status','islemde')->count(), 'color'=>'#0d6efd'],
                            ['label'=>'Fiyatlandırıldı', 'count'=>\App\Models\Request::where('status', \App\Models\Request::STATUS_FIYATLANDIRILDI)->count(), 'color'=>'#ffc107'],
                            ['label'=>'Depozitoda', 'count'=>\App\Models\Request::where('status', \App\Models\Request::STATUS_DEPOZITODA)->count(), 'color'=>'#6f42c1'],
                            ['label'=>'Biletlendi', 'count'=>\App\Models\Request::where('status','biletlendi')->count(), 'color'=>'#198754'],
                            ['label'=>'İptal', 'count'=>\App\Models\Request::where('status','iptal')->count(), 'color'=>'#dc3545'],
                        ];
                        @endphp
                        @foreach($dagilim as $d)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:10px;height:10px;border-radius:50%;background:{{ $d['color'] }};flex-shrink:0;"></div>
                                <span style="font-size:0.82rem;">{{ $d['label'] }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:80px;height:6px;background:#f0f2f5;border-radius:3px;overflow:hidden;">
                                    @if($toplamTalep > 0)
                                    <div style="width:{{ ($d['count']/$toplamTalep)*100 }}%;height:100%;background:{{ $d['color'] }};border-radius:3px;"></div>
                                    @endif
                                </div>
                                <span style="font-size:0.82rem;font-weight:600;min-width:28px;text-align:right;">{{ $d['count'] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

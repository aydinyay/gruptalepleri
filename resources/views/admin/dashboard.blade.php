<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel — GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .stat-card { border: none; border-radius: 12px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }

        .table th { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        .table td { vertical-align: middle; font-size: 0.875rem; }

        .status-badge { padding: 3px 10px; border-radius: 50px; font-size: 0.7rem; font-weight: 600; }
        .status-beklemede { background: #e9ecef; color: #495057; }
        .status-islemde { background: #cfe2ff; color: #084298; }
        .status-fiyatlandirildi { background: #fff3cd; color: #856404; }
        .status-biletlendi { background: #d1e7dd; color: #0a3622; }
        .status-depozito { background: #e8d5f5; color: #4a0072; }
        .status-iptal { background: #f8d7da; color: #842029; }

        .page-header { background: #1a1a2e; padding: 1.2rem 0; margin-bottom: 1.5rem; }
        .page-header h4 { color: #fff; font-weight: 700; margin: 0; }
        .page-header p { color: rgba(255,255,255,0.5); font-size: 0.82rem; margin: 0; }

        /* Opsiyon uyarı listesi */
        .opsiyon-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f2f5; }
        .opsiyon-row:last-child { border-bottom: none; }

        /* Bekleyen talep kartları */
        .bekleyen-card { border-left: 4px solid #ffc107; border-radius: 8px; background: #fff; padding: 12px 16px; margin-bottom: 10px; transition: all 0.2s; }
        .bekleyen-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateX(3px); }
        .bekleyen-card .gtpnr { font-weight: 700; color: #1a1a2e; }
        .bekleyen-card .meta { font-size: 0.78rem; color: #6c757d; }

        /* Sayaç */
        .countdown { font-family: 'Courier New', monospace; font-weight: 700; font-size: 0.9rem; }

        .section-title { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #6c757d; margin-bottom: 0.8rem; }

        .btn-sm-red { background: #e94560; color: #fff; border: none; padding: 5px 14px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-sm-red:hover { background: #c73652; color: #fff; }

        /* Push notification dot */
        .notif-dot { width: 8px; height: 8px; background: #e94560; border-radius: 50%; animation: blink 1.5s infinite; display: inline-block; margin-right: 6px; }
        @@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

        .quick-btn { border: 1.5px solid #dee2e6; border-radius: 10px; padding: 1rem; text-align: center; cursor: pointer; transition: all 0.2s; color: #495057; text-decoration: none; display: block; background: #fff; }
        .quick-btn:hover { border-color: #e94560; color: #e94560; background: #fff5f7; }
        .quick-btn i { font-size: 1.3rem; display: block; margin-bottom: 6px; }
        .quick-btn span { font-size: 0.78rem; font-weight: 600; }
    </style>
</head>
<body>

<x-navbar-admin active="dashboard" />

{{-- PAGE HEADER --}}
<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-headset me-2" style="color:#e94560;"></i>Operasyon Merkezi</h4>
                <p>{{ now()->format('d F Y, H:i') }}</p>
            </div>
            <a href="{{ route('admin.requests.index') }}" class="btn btn-sm-red">
                <i class="fas fa-list me-1"></i> Tüm Talepler
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @php
        $toplamTalep      = \App\Models\Request::count();
        $bekleyenler      = \App\Models\Request::where('status','beklemede')->with('segments')->orderBy('created_at','asc')->get();
        $islemdekiler     = \App\Models\Request::where('status','islemde')->count();
        $fiyatlananlar    = \App\Models\Request::whereIn('status',['fiyatlandirildi','fiyatlandirıldi'])->count();
        $depozitodakiler  = \App\Models\Request::where('status','depozito')->count();
        $biletilenler     = \App\Models\Request::where('status','biletlendi')->count();
        $bugunTalep       = \App\Models\Request::whereDate('created_at', today())->count();
        $bugunTeklif      = \App\Models\Offer::whereDate('created_at', today())->count();

        // Opsiyon uyarıları — 48 saat içinde dolanlar
        $opsiyonUyarilari = \App\Models\Offer::whereNotNull('option_date')
            ->whereRaw("STR_TO_DATE(CONCAT(option_date, ' ', COALESCE(option_time, '23:59')), '%Y-%m-%d %H:%i') > NOW()")
            ->whereRaw("STR_TO_DATE(CONCAT(option_date, ' ', COALESCE(option_time, '23:59')), '%Y-%m-%d %H:%i') < DATE_ADD(NOW(), INTERVAL 48 HOUR)")
            ->with('request')
            ->orderByRaw("STR_TO_DATE(CONCAT(option_date, ' ', COALESCE(option_time, '23:59')), '%Y-%m-%d %H:%i') ASC")
            ->get();

        // Son talepler — aktif durumlar (biletlendi/olumsuz/iade hariç)
        $sonTalepler = \App\Models\Request::with('segments')
            ->whereNotIn('status', ['biletlendi', 'olumsuz', 'iade', 'iptal'])
            ->orderBy('created_at','desc')
            ->limit(10)
            ->get();
    @endphp

    {{-- İSTATİSTİK KARTLARI --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fce8eb;color:#e94560;"><i class="fas fa-clock"></i></div>
                    <div>
                        <div style="font-size:1.8rem;font-weight:700;color:#e94560;line-height:1;">{{ $bekleyenler->count() }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">Bekleyen</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#cfe2ff;color:#084298;"><i class="fas fa-spinner"></i></div>
                    <div>
                        <div style="font-size:1.8rem;font-weight:700;line-height:1;">{{ $islemdekiler }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">İşlemde</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fff3cd;color:#856404;"><i class="fas fa-tag"></i></div>
                    <div>
                        <div style="font-size:1.8rem;font-weight:700;line-height:1;">{{ $fiyatlananlar }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">Fiyatlandırıldı</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#e8d5f5;color:#6f42c1;"><i class="fas fa-hand-holding-usd"></i></div>
                    <div>
                        <div style="font-size:1.8rem;font-weight:700;line-height:1;">{{ $depozitodakiler }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">Depozito</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#d1e7dd;color:#0a3622;"><i class="fas fa-ticket-alt"></i></div>
                    <div>
                        <div style="font-size:1.8rem;font-weight:700;line-height:1;">{{ $biletilenler }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">Biletlendi</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm p-3" style="border-left:3px solid #e94560;">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fce8eb;color:#e94560;"><i class="fas fa-calendar-day"></i></div>
                    <div>
                        <div style="font-size:1.8rem;font-weight:700;color:#e94560;line-height:1;">{{ $bugunTalep }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">Bugün Gelen</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- SOL KOLON --}}
        <div class="col-12 col-xl-8">

            {{-- OPSİYON UYARILARI --}}
            @if($opsiyonUyarilari->count() > 0)
            <div class="card shadow-sm mb-4" style="border-top: 3px solid #ffc107;">
                <div class="card-header d-flex align-items-center gap-2" style="background:#fffbf0;">
                    <span class="notif-dot"></span>
                    <span class="fw-bold">⏰ Opsiyon Uyarıları</span>
                    <span class="badge bg-warning text-dark">{{ $opsiyonUyarilari->count() }} aktif</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>GTPNR</th>
                                <th>Havayolu</th>
                                <th>Opsiyon Sonu</th>
                                <th>Kalan Süre</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($opsiyonUyarilari as $teklif)
                            @php
                                $opsTs = \Carbon\Carbon::parse($teklif->option_date . ' ' . ($teklif->option_time ?? '23:59'));
                                $kalanSaniye = \Carbon\Carbon::now()->diffInSeconds($opsTs, false);
                                $kalanSaat = $kalanSaniye / 3600;
                                $renk = $kalanSaat <= 6 ? 'danger' : ($kalanSaat <= 24 ? 'warning' : 'success');
                            @endphp
                            <tr>
                                <td><strong>{{ $teklif->request?->gtpnr ?? '—' }}</strong></td>
                                <td>{{ $teklif->airline ?? '—' }}</td>
                                <td class="text-muted">{{ $opsTs->format('d.m.Y H:i') }}</td>
                                <td>
                                    <span class="countdown text-{{ $renk }}" data-ts="{{ $opsTs->timestamp }}">
                                        @if($kalanSaat < 24)
                                            {{ round($kalanSaat) }}s
                                        @else
                                            {{ floor($kalanSaat/24) }}g {{ round($kalanSaat%24) }}s
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @if($teklif->request)
                                    <a href="{{ route('admin.requests.show', $teklif->request->gtpnr) }}" class="btn-sm-red">
                                        <i class="fas fa-eye me-1"></i>Detay
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- BEKLEYENLERİ ÖNE AL --}}
            @if($bekleyenler->count() > 0)
            <div class="card shadow-sm mb-4" style="border-top: 3px solid #e94560;">
                <div class="card-header d-flex justify-content-between align-items-center" style="background:#fff5f7;">
                    <span class="fw-bold"><span class="notif-dot"></span>🔴 Teklif Bekleyen Talepler</span>
                    <span class="badge bg-danger">{{ $bekleyenler->count() }}</span>
                </div>
                <div class="card-body">
                    @foreach($bekleyenler as $talep)
                    <div class="bekleyen-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="gtpnr">{{ $talep->gtpnr }}</span>
                                <span class="ms-2 text-muted" style="font-size:0.8rem;">
                                    @foreach($talep->segments as $s)
                                        {{ $s->from_iata }}→{{ $s->to_iata }}
                                    @endforeach
                                </span>
                                <div class="meta mt-1">
                                    <i class="fas fa-users me-1"></i>{{ $talep->pax_total }} pax
                                    &nbsp;·&nbsp;
                                    <i class="fas fa-clock me-1"></i>{{ $talep->created_at->diffForHumans() }}
                                    @if($talep->agency_name)
                                        &nbsp;·&nbsp;
                                        @if($talep->agency_name === 'MÜNFERİT')
                                            <span class="badge" style="background:#20c997;color:#fff;">MÜNFERİT</span>
                                        @else
                                            {{ $talep->agency_name }}
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.requests.show', $talep->gtpnr) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-tag me-1"></i>Teklif Gir
                                </a>
                                <a href="{{ route('admin.requests.show', $talep->gtpnr) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- SON TALEPLER TABLOSU --}}
            <div class="card shadow-sm">
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
                                    'beklemede'        => ['class'=>'status-beklemede','label'=>'Beklemede'],
                                    'islemde'          => ['class'=>'status-islemde','label'=>'İşlemde'],
                                    'fiyatlandirildi'  => ['class'=>'status-fiyatlandirildi','label'=>'Fiyatlandırıldı'],
                                    'fiyatlandirıldi'  => ['class'=>'status-fiyatlandirildi','label'=>'Fiyatlandırıldı'],
                                    'biletlendi'       => ['class'=>'status-biletlendi','label'=>'Biletlendi'],
                                    'depozito'         => ['class'=>'status-depozito','label'=>'Depozito'],
                                    'iptal'            => ['class'=>'status-iptal','label'=>'İptal'],
                                ];
                                $sc = $statusMap[$talep->status] ?? ['class'=>'status-beklemede','label'=>$talep->status];
                            @endphp
                            <tr>
                                <td><strong>{{ $talep->gtpnr }}</strong></td>
                                <td class="text-muted">
                                    @if(($talep->agency_name ?? '') === 'MÜNFERİT')
                                        <span class="badge" style="background:#20c997;color:#fff;">MÜNFERİT</span>
                                    @else
                                        {{ $talep->agency_name ?? '—' }}
                                    @endif
                                </td>
                                <td>
                                    @foreach($talep->segments as $seg)
                                        <span class="badge bg-light text-dark border" style="font-size:0.68rem;">{{ $seg->from_iata }}→{{ $seg->to_iata }}</span>
                                    @endforeach
                                </td>
                                <td>{{ $talep->pax_total }}</td>
                                <td><span class="status-badge {{ $sc['class'] }}">{{ $sc['label'] }}</span></td>
                                <td class="text-muted" style="font-size:0.8rem;">{{ $talep->created_at->format('d.m.Y') }}</td>
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

        </div>

        {{-- SAĞ KOLON --}}
        <div class="col-12 col-xl-4">

            {{-- HIZLI İŞLEMLER --}}
            <div class="mb-4">
                <div class="section-title">Hızlı İşlemler</div>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ route('admin.requests.index') }}" class="quick-btn">
                            <i class="fas fa-list"></i>
                            <span>Tüm Talepler</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('admin.requests.index') }}?status=beklemede" class="quick-btn">
                            <i class="fas fa-clock" style="color:#e94560;"></i>
                            <span>Bekleyenler</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('admin.requests.index') }}?status=islemde" class="quick-btn">
                            <i class="fas fa-spinner" style="color:#0d6efd;"></i>
                            <span>İşlemdekiler</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('acente.requests.create') }}" class="quick-btn">
                            <i class="fas fa-plus-circle" style="color:#198754;"></i>
                            <span>Yeni Talep</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- BUGÜNÜN ÖZETİ --}}
            <div class="mb-4">
                <div class="section-title">Bugünün Özeti</div>
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <span style="font-size:0.85rem;">Yeni gelen talep</span>
                            <span class="fw-bold {{ $bugunTalep > 0 ? 'text-danger' : 'text-muted' }}">{{ $bugunTalep }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <span style="font-size:0.85rem;">Girilen teklif</span>
                            <span class="fw-bold text-primary">{{ $bugunTeklif }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <span style="font-size:0.85rem;">Aktif opsiyon uyarısı</span>
                            <span class="fw-bold {{ $opsiyonUyarilari->count() > 0 ? 'text-warning' : 'text-muted' }}">{{ $opsiyonUyarilari->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <span style="font-size:0.85rem;">Toplam bekleyen</span>
                            <span class="fw-bold {{ $bekleyenler->count() > 0 ? 'text-danger' : 'text-success' }}">{{ $bekleyenler->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DURUM DAĞILIMI --}}
            <div>
                <div class="section-title">Talep Dağılımı</div>
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        @php
                        $dagilim = [
                            ['label'=>'Beklemede',      'count'=>$bekleyenler->count(),  'color'=>'#e94560'],
                            ['label'=>'İşlemde',        'count'=>$islemdekiler,           'color'=>'#0d6efd'],
                            ['label'=>'Fiyatlandırıldı','count'=>$fiyatlananlar,          'color'=>'#ffc107'],
                            ['label'=>'Depozito',       'count'=>$depozitodakiler,        'color'=>'#6f42c1'],
                            ['label'=>'Biletlendi',     'count'=>$biletilenler,           'color'=>'#198754'],
                            ['label'=>'İptal',          'count'=>\App\Models\Request::where('status','iptal')->count(), 'color'=>'#dc3545'],
                        ];
                        @endphp
                        @foreach($dagilim as $d)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:8px;height:8px;border-radius:50%;background:{{ $d['color'] }};flex-shrink:0;"></div>
                                <span style="font-size:0.8rem;">{{ $d['label'] }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:80px;height:5px;background:#f0f2f5;border-radius:3px;overflow:hidden;">
                                    @if($toplamTalep > 0)
                                    <div style="width:{{ ($d['count']/$toplamTalep)*100 }}%;height:100%;background:{{ $d['color'] }};border-radius:3px;"></div>
                                    @endif
                                </div>
                                <span style="font-size:0.8rem;font-weight:600;min-width:28px;text-align:right;">{{ $d['count'] }}</span>
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
@include('admin.partials.theme-script')
<script>
// Canlı opsiyon sayaçları
document.querySelectorAll('.countdown[data-ts]').forEach(el => {
    const hedef = parseInt(el.dataset.ts) * 1000;
    function guncelle() {
        const kalan = hedef - Date.now();
        if (kalan <= 0) { el.textContent = 'DOLDU'; el.className = 'countdown text-danger'; return; }
        const gun = Math.floor(kalan / 86400000);
        const saat = Math.floor((kalan % 86400000) / 3600000);
        const dk = Math.floor((kalan % 3600000) / 60000);
        const sn = Math.floor((kalan % 60000) / 1000);
        if (gun > 0) {
            el.textContent = gun + 'g ' + String(saat).padStart(2,'0') + ':' + String(dk).padStart(2,'0');
        } else {
            el.textContent = String(saat).padStart(2,'0') + ':' + String(dk).padStart(2,'0') + ':' + String(sn).padStart(2,'0');
        }
        setTimeout(guncelle, 1000);
    }
    guncelle();
});

// Yeni talep push kontrolü
let lastCheck = new Date().toISOString();
setInterval(async () => {
    try {
        const res = await fetch(`{{ route('admin.push.yeni-talepler') }}?since=${lastCheck}`);
        const data = await res.json();
        if (data.talepler && data.talepler.length > 0) {
            lastCheck = data.ts;
            const gtpnrler = data.talepler.map(t => t.gtpnr).join(', ');
            const banner = document.createElement('div');
            banner.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;background:#e94560;color:#fff;padding:12px 20px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.3);font-weight:600;font-size:0.875rem;cursor:pointer;';
            banner.innerHTML = `🔔 Yeni talep: ${gtpnrler} <span style="margin-left:8px;opacity:0.7;">✕</span>`;
            banner.onclick = () => banner.remove();
            document.body.appendChild(banner);
            setTimeout(() => banner.remove(), 8000);
        }
    } catch(e) {}
}, 30000);
</script>
</body>
</html>
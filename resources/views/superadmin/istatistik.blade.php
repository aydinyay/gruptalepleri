<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <title>Site İstatistikleri — Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .page-header { background: #1a1a2e; padding: 1.2rem 0; margin-bottom: 1.5rem; }
        .page-header h4 { color: #fff; font-weight: 700; margin: 0; }
        .page-header p  { color: rgba(255,255,255,.5); font-size: .82rem; margin: 0; }
        .stat-card { border-radius: 12px; border: none; }
        .stat-num  { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .bar-wrap  { display:flex; align-items:flex-end; gap:3px; height:140px; }
        .bar-item  { flex:1; background:linear-gradient(180deg,#6f42c1,#0d6efd); border-radius:4px 4px 0 0; min-width:6px; position:relative; }
        .bar-item span { position:absolute;bottom:-18px;left:50%;transform:translateX(-50%);font-size:9px;color:#6c757d;white-space:nowrap; }
        .risk-zero  { background:#e9ecef; color:#495057; }
        .risk-low   { background:#d1e7dd; color:#0a3622; }
        .risk-mid   { background:#fff3cd; color:#856404; }
        .risk-high  { background:#f8d7da; color:#842029; }
        .tab-link   { padding:.45rem 1rem; border-radius:8px; text-decoration:none; color:#6c757d; font-size:.85rem; font-weight:500; }
        .tab-link.active, .tab-link:hover { background:#0d6efd; color:#fff; }
        .url-cell   { font-family:Consolas,monospace; font-size:.78rem; word-break:break-all; max-width:400px; }
        .member-row:hover { background:#f8f9fa; cursor:pointer; }
    </style>
</head>
<body>

<x-navbar-superadmin active="istatistik" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4><i class="fas fa-chart-mixed me-2" style="color:#6f42c1;"></i>Site İstatistikleri</h4>
                <p>Ziyaretçi takibi — GT Analytics verisi</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success">{{ $onlineCount }} online</span>
                <span class="text-white-50 small">{{ now()->format('d.m.Y H:i') }}</span>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    {{-- Sekmeler --}}
    <div class="d-flex gap-2 flex-wrap mb-4">
        <a href="{{ route('superadmin.istatistik') }}?tab=dashboard"
           class="tab-link {{ $tab === 'dashboard' ? 'active' : '' }}">
            <i class="fas fa-gauge me-1"></i>Dashboard
        </a>
        <a href="{{ route('superadmin.istatistik') }}?tab=uyeler"
           class="tab-link {{ $tab === 'uyeler' ? 'active' : '' }}">
            <i class="fas fa-users me-1"></i>Üye Aktivitesi
        </a>
        <a href="{{ route('superadmin.istatistik') }}?tab=timeline"
           class="tab-link {{ $tab === 'timeline' ? 'active' : '' }}">
            <i class="fas fa-timeline me-1"></i>IP Timeline
        </a>
        <a href="{{ route('superadmin.istatistik') }}?tab=404"
           class="tab-link {{ $tab === '404' ? 'active' : '' }}">
            <i class="fas fa-triangle-exclamation me-1"></i>404 Raporu
        </a>
    </div>

    {{-- Özet kartları (her sekmede görünür) --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['Bugün Toplam','fas fa-eye',$todayTotal,'#0d6efd'],
            ['Online','fas fa-circle',$onlineCount,'#198754'],
            ['404','fas fa-triangle-exclamation',$today404,'#dc3545'],
            ['403','fas fa-ban',$today403,'#fd7e14'],
            ['500 Hata','fas fa-server',$today500,'#6f42c1'],
            ['Riskli IP','fas fa-shield-halved',$riskyIps,'#d63384'],
            ['Login Fail','fas fa-key',$loginFail,'#6c757d'],
        ] as [$lbl,$icon,$val,$col])
        <div class="col-6 col-md-4 col-xl">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:42px;height:42px;border-radius:10px;background:{{ $col }}22;display:flex;align-items:center;justify-content:center;color:{{ $col }};flex-shrink:0;">
                        <i class="{{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="stat-num" style="color:{{ $col }};">{{ number_format($val) }}</div>
                        <div class="text-muted" style="font-size:.72rem;">{{ $lbl }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- DB bağlantı hatası (geçici debug) --}}
    @if(!empty($dbError))
    <div class="alert alert-danger mb-3" style="font-family:Consolas,monospace;font-size:.82rem;">
        <strong>DB Bağlantı Hatası:</strong> {{ $dbError }}
    </div>
    @endif

    {{-- ========== TAB: DASHBOARD ========== --}}
    @if($tab === 'dashboard')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm p-3 mb-4">
                <div class="fw-semibold mb-3" style="font-size:.85rem;">Son 24 Saat Trafik</div>
                <div class="bar-wrap px-1">
                    @php $maxH = max($hourly ?: [1]); @endphp
                    @foreach($hourly as $h => $c)
                    @php $ht = $maxH > 0 ? max(6, round(($c/$maxH)*120)) : 6; @endphp
                    <div class="bar-item" style="height:{{ $ht }}px" title="{{ $h }}:00 — {{ $c }} ziyaret">
                        <span>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}</span>
                    </div>
                    @endforeach
                </div>
                <div style="height:24px;"></div>
            </div>

            <div class="card shadow-sm p-3">
                <div class="fw-semibold mb-3" style="font-size:.85rem;">En Çok Gezilen Sayfalar (7 gün)</div>
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Sayfa</th><th>Hit</th><th>Unique IP</th></tr></thead>
                    <tbody>
                    @foreach($topPages as $p)
                    <tr>
                        <td class="url-cell">{{ $p->page_url }}</td>
                        <td>{{ number_format($p->c) }}</td>
                        <td>{{ number_format($p->u) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm p-3 mb-4">
                <div class="fw-semibold mb-3" style="font-size:.85rem;">En Riskli IP'ler</div>
                <table class="table table-sm mb-0">
                    <thead><tr><th>IP</th><th>Risk</th><th>Hit</th></tr></thead>
                    <tbody>
                    @foreach($topIps as $row)
                    @php
                        $rc = $row->max_risk >= 80 ? 'risk-high' : ($row->max_risk >= 50 ? 'risk-mid' : ($row->max_risk > 0 ? 'risk-low' : 'risk-zero'));
                    @endphp
                    <tr>
                        <td><a href="{{ route('superadmin.istatistik') }}?tab=timeline&ip={{ urlencode($row->ip) }}" style="font-family:Consolas,monospace;font-size:.78rem;">{{ $row->ip }}</a></td>
                        <td><span class="badge {{ $rc }}">{{ (int)$row->max_risk }}</span></td>
                        <td>{{ number_format($row->hits) }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card shadow-sm p-3">
                <div class="fw-semibold mb-3" style="font-size:.85rem;">Ülkeler</div>
                @foreach($topCountries as $c)
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size:.82rem;">
                    <span>{{ $c->flag }} {{ $c->country }}</span>
                    <span class="fw-semibold">{{ number_format($c->u) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ========== TAB: ÜYE AKTİVİTESİ ========== --}}
    @elseif($tab === 'uyeler')

    <div class="card shadow-sm p-3">
        <div class="fw-semibold mb-3" style="font-size:.85rem;">
            <i class="fas fa-users me-1 text-primary"></i>Oturum Açmış Kullanıcı Aktivitesi
        </div>
        @if(count($memberRows) === 0)
        <div class="text-muted small text-center py-4">
            Henüz üye aktivitesi kaydedilmedi. Kullanıcılar siteyi ziyaret ettikçe burada görünecek.
        </div>
        @else
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Toplam Hit</th>
                    <th>Aktif Gün</th>
                    <th>Son Giriş</th>
                    <th>Son IP</th>
                    <th>Ülke</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($memberRows as $m)
            <tr class="member-row">
                <td>
                    <div class="fw-semibold" style="font-size:.85rem;">{{ $m->member_name }}</div>
                    <div class="text-muted" style="font-size:.72rem;">ID: {{ $m->member_id }}</div>
                </td>
                <td>{{ number_format($m->total_hits) }}</td>
                <td>{{ $m->aktif_gun }}</td>
                <td style="font-size:.8rem;">{{ $m->last_seen }}</td>
                <td style="font-family:Consolas,monospace;font-size:.78rem;">{{ $m->last_ip }}</td>
                <td>{{ $m->flag }} {{ $m->country }}</td>
                <td>
                    <a href="{{ route('superadmin.istatistik') }}?tab=uye-detay&uye={{ urlencode($m->member_id) }}"
                       class="btn btn-sm btn-outline-primary" style="font-size:.72rem;">
                        Detay
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- ========== TAB: ÜYE DETAY ========== --}}
    @elseif($tab === 'uye-detay')

    <div class="mb-3">
        <a href="{{ route('superadmin.istatistik') }}?tab=uyeler" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Üye Listesi
        </a>
    </div>

    <div class="card shadow-sm p-3">
        <div class="fw-semibold mb-3" style="font-size:.85rem;">
            Sayfa Geçmişi — <span class="text-primary">{{ $memberDetailName }}</span>
        </div>
        @if(count($memberDetail) === 0)
        <div class="text-muted small text-center py-4">Kayıt bulunamadı.</div>
        @else
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Sayfa</th>
                    <th>IP</th>
                    <th>Ülke</th>
                    <th>Cihaz</th>
                    <th>Tarayıcı</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @foreach($memberDetail as $r)
            <tr>
                <td style="font-size:.78rem;white-space:nowrap;">{{ $r->created_at }}</td>
                <td class="url-cell {{ (int)$r->http_status === 404 ? 'text-danger' : '' }}">{{ $r->page_url }}</td>
                <td style="font-family:Consolas,monospace;font-size:.75rem;">{{ $r->ip }}</td>
                <td>{{ $r->flag }} {{ $r->country }}</td>
                <td style="font-size:.78rem;">{{ $r->device }}</td>
                <td style="font-size:.78rem;">{{ $r->browser }} / {{ $r->os }}</td>
                <td>
                    <span class="badge {{ (int)$r->http_status >= 400 ? 'bg-danger' : 'bg-success' }}" style="font-size:.7rem;">
                        {{ $r->http_status }}
                    </span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- ========== TAB: IP TIMELINE ========== --}}
    @elseif($tab === 'timeline')

    <div class="card shadow-sm p-3 mb-4">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="tab" value="timeline">
            <input type="text" name="ip" value="{{ $timelineIp }}" placeholder="IP adresi girin..."
                   class="form-control form-control-sm" style="max-width:260px;">
            <button type="submit" class="btn btn-sm btn-primary">Sorgula</button>
        </form>
    </div>

    @if($timelineIp !== '' && $timelineInfo)
    <div class="row g-3 mb-3">
        @foreach([['Toplam',$timelineStats->total,''],['404',$timelineStats->c404,'text-danger'],['403',$timelineStats->c403,'text-warning'],['500',$timelineStats->c500,'text-danger'],['Max Risk',$timelineStats->max_risk,'text-danger'],['Ort. Risk',round($timelineStats->avg_risk,1),'']] as [$l,$v,$cls])
        <div class="col-6 col-md-2">
            <div class="card shadow-sm p-3 text-center">
                <div class="fw-bold fs-4 {{ $cls }}">{{ $v }}</div>
                <div class="text-muted" style="font-size:.72rem;">{{ $l }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card shadow-sm p-3 mb-3">
        <div class="d-flex flex-wrap gap-2" style="font-size:.82rem;">
            <span class="badge bg-light text-dark">IP: {{ $timelineInfo->ip }}</span>
            <span class="badge bg-light text-dark">{{ $timelineInfo->flag }} {{ $timelineInfo->country }}</span>
            <span class="badge bg-light text-dark">{{ $timelineInfo->city }}</span>
            <span class="badge bg-light text-dark">{{ $timelineInfo->isp }}</span>
            <span class="badge bg-light text-dark">{{ $timelineInfo->device }}</span>
            <span class="badge bg-light text-dark">{{ $timelineInfo->browser }} / {{ $timelineInfo->os }}</span>
            <span class="badge bg-light text-dark">Son: {{ $timelineStats->last_seen }}</span>
        </div>
    </div>

    <div class="card shadow-sm p-0">
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Tarih</th><th>Sayfa</th><th>Status</th><th>Cihaz</th><th>Risk</th></tr></thead>
            <tbody>
            @foreach($timelineRows as $r)
            @php $rc = $r->risk_score >= 80 ? 'risk-high' : ($r->risk_score >= 50 ? 'risk-mid' : ($r->risk_score > 0 ? 'risk-low' : 'risk-zero')); @endphp
            <tr>
                <td style="font-size:.78rem;white-space:nowrap;">{{ $r->created_at }}</td>
                <td class="url-cell {{ (int)$r->is_404 ? 'text-danger' : '' }}">{{ $r->page_url }}</td>
                <td><span class="badge {{ (int)$r->http_status >= 400 ? 'bg-danger' : 'bg-success' }}" style="font-size:.7rem;">{{ $r->http_status }}</span></td>
                <td style="font-size:.75rem;">{{ $r->device }} / {{ $r->browser }}</td>
                <td><span class="badge {{ $rc }}" style="font-size:.7rem;">{{ (int)$r->risk_score }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @elseif($timelineIp !== '')
    <div class="text-muted small text-center py-4">Bu IP için kayıt bulunamadı.</div>
    @else
    <div class="text-muted small text-center py-4">Bir IP adresi girip sorgulayın.</div>
    @endif

    {{-- ========== TAB: 404 RAPORU ========== --}}
    @elseif($tab === '404')

    <div class="card shadow-sm p-0">
        <div class="p-3 fw-semibold" style="font-size:.85rem;">404 Sayfaları (tüm zamanlar)</div>
        @if(count($notFoundRows) === 0)
        <div class="text-muted small text-center py-4">404 kaydı yok.</div>
        @else
        <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Sayfa</th><th>Hit</th><th>Unique IP</th><th>Son Görülme</th></tr></thead>
            <tbody>
            @foreach($notFoundRows as $r)
            <tr>
                <td class="url-cell text-danger">{{ $r->page_url }}</td>
                <td>{{ number_format($r->c) }}</td>
                <td>{{ number_format($r->u) }}</td>
                <td style="font-size:.78rem;">{{ $r->last_seen }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('admin.partials.theme-script')
</body>
</html>

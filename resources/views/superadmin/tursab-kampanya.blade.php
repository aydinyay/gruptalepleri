<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Kampanya Hub — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:2rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.page-header p  { color:rgba(255,255,255,0.5); font-size:0.82rem; margin:0; }
.hub-card { border:none; border-radius:16px; transition:transform .15s, box-shadow .15s; cursor:pointer; text-decoration:none; color:inherit; }
.hub-card:hover { transform:translateY(-4px); box-shadow:0 12px 30px rgba(0,0,0,.12)!important; }
.hub-icon { width:64px; height:64px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:1rem; }
</style>
</head>
<body>

<x-navbar-superadmin active="tursab-kampanya" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-bullhorn me-2" style="color:#e94560;"></i>Kampanya Merkezi</h5>
        <p>Bakanlık acente veritabanı ile toplu email ve SMS kampanyaları</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- İstatistik Özeti --}}
    <div class="row g-3 mb-5">
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <div style="font-size:2rem;font-weight:800;color:#1a1a2e;">{{ number_format(\App\Models\Acenteler::count()) }}</div>
                <div class="text-muted small">Toplam Acente</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <div style="font-size:2rem;font-weight:800;color:#198754;">{{ number_format(\App\Models\Acenteler::whereNotNull('eposta')->where('eposta','!=','')->count()) }}</div>
                <div class="text-muted small">E-postalı Acente</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <div style="font-size:2rem;font-weight:800;color:#0dcaf0;">{{ number_format(\App\Models\Acenteler::whereNotNull('telefon')->where('telefon','!=','')->whereRaw("telefon REGEXP '^[[:space:]]*(\\\\+?90)?0?5[0-9]'")->count()) }}</div>
                <div class="text-muted small">Cep Numaralı Acente</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <div style="font-size:2rem;font-weight:800;color:#e94560;">{{ number_format(\App\Models\TursabDavet::where('status','sent')->count()) }}</div>
                <div class="text-muted small">Toplam Davet Gönderildi</div>
            </div>
        </div>
    </div>

    {{-- Hub Kartları --}}
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <a href="{{ route('superadmin.kampanya.email') }}" class="card shadow hub-card d-block p-4">
                <div class="hub-icon" style="background:#fff0f3;">
                    <i class="fas fa-envelope-open-text" style="color:#e94560;"></i>
                </div>
                <h5 class="fw-bold mb-1">Email Kampanyası</h5>
                <p class="text-muted small mb-0">İl, ilçe ve grup filtresiyle seçtiğin acentelere toplu davet emaili gönder. Günlük limit: 50.</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('superadmin.kampanya.sms') }}" class="card shadow hub-card d-block p-4">
                <div class="hub-icon" style="background:#e0f7ff;">
                    <i class="fas fa-sms" style="color:#0dcaf0;"></i>
                </div>
                <h5 class="fw-bold mb-1">SMS Kampanyası</h5>
                <p class="text-muted small mb-0">Cep numarası olan acenteleri il/ilçe/grup filtresiyle seç ve toplu SMS gönder.</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('superadmin.kampanya.csv-import') }}" class="card shadow hub-card d-block p-4">
                <div class="hub-icon" style="background:#f0fff4;">
                    <i class="fas fa-file-csv" style="color:#198754;"></i>
                </div>
                <h5 class="fw-bold mb-1">CSV Import</h5>
                <p class="text-muted small mb-0">Bakanlık Excel/CSV verisini sisteme yükle. Mevcut veriyi tamamen sıfırla veya güncelle.</p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('superadmin.kampanya.zamanlama') }}" class="card shadow hub-card d-block p-4">
                <div class="hub-icon" style="background:#fffbeb;">
                    <i class="fas fa-clock" style="color:#f59e0b;"></i>
                </div>
                <h5 class="fw-bold mb-1">Otomatik Zamanlama</h5>
                <p class="text-muted small mb-0">Her gün belirlediğin saatlerde otomatik email ve SMS gönder. cPanel cron ile çalışır.</p>
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

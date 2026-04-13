<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>İstanbul Yat Kiralama — Özel Boğaz Turları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root{--gt:#1a3a6e;--gy:#ff5533;--gy-d:#e8411d;--txt:#1a1a1a;--muted:#595959;--brd:#e8e8e8;--bg:#f5f5f5;--card:#fff;--star:#f5a623;}
        html[data-theme="dark"]{--txt:#f0f0f0;--muted:#b0b0b0;--brd:#333;--bg:#0f1520;--card:#1a2235;}
        body{background:var(--bg);color:var(--txt);min-height:100vh;}
        /* Hero */
        .yc-hero{background:linear-gradient(135deg,#06132a 0%,#0e2650 55%,#0b1e42 100%);color:#fff;padding:2.5rem 0 2rem;}
        .yc-hero h1{font-size:clamp(1.6rem,3.5vw,2.4rem);font-weight:800;margin:0 0 .6rem;line-height:1.15;}
        .yc-hero p{color:rgba(255,255,255,.82);margin:0 0 1rem;font-size:1rem;}
        .yc-chip-row{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.9rem;}
        .yc-chip{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);padding:.3rem .7rem;font-size:.78rem;font-weight:600;}
        /* Filter bar */
        .yc-filter-bar{background:var(--card);border-bottom:1px solid var(--brd);padding:.8rem 0;position:sticky;top:64px;z-index:99;}
        .yc-filter-bar .container{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;}
        .yc-pill{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;border:1px solid var(--brd);background:var(--card);color:var(--txt);padding:.38rem .75rem;font-size:.82rem;font-weight:600;cursor:pointer;text-decoration:none;transition:border-color .15s,background .15s;white-space:nowrap;}
        .yc-pill:hover,.yc-pill.active{border-color:var(--gy);color:var(--gy);background:rgba(255,85,51,.07);}
        /* Grid */
        .yc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;padding:1.5rem 0 2.5rem;}
        /* Card */
        .yc-card{background:var(--card);border-radius:12px;border:1px solid var(--brd);overflow:hidden;transition:box-shadow .2s,transform .2s;text-decoration:none;color:var(--txt);display:flex;flex-direction:column;}
        .yc-card:hover{box-shadow:0 8px 32px rgba(0,0,0,.13);transform:translateY(-2px);text-decoration:none;color:var(--txt);}
        .yc-card-img{position:relative;width:100%;aspect-ratio:4/3;overflow:hidden;background:#1a3a6e;}
        .yc-card-img img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s;}
        .yc-card:hover .yc-card-img img{transform:scale(1.04);}
        .yc-badge{position:absolute;top:.6rem;left:.6rem;border-radius:6px;padding:.3rem .55rem;font-size:.72rem;font-weight:700;z-index:2;}
        .yc-badge.featured{background:#fff;color:var(--txt);box-shadow:0 2px 6px rgba(0,0,0,.15);}
        .yc-badge.new{background:#12a354;color:#fff;}
        .yc-card-body{padding:.85rem;flex:1;display:flex;flex-direction:column;gap:.35rem;}
        .yc-card-meta{font-size:.78rem;color:var(--muted);display:flex;align-items:center;gap:.5rem;}
        .yc-card-title{font-size:1rem;font-weight:700;line-height:1.3;}
        .yc-card-feats{display:flex;flex-wrap:wrap;gap:.28rem;margin-top:.1rem;}
        .yc-card-feat{font-size:.74rem;color:var(--muted);display:flex;align-items:center;gap:.22rem;}
        .yc-card-footer{border-top:1px solid var(--brd);padding:.7rem .85rem;display:flex;align-items:center;justify-content:space-between;}
        .yc-price-label{font-size:.72rem;color:var(--muted);}
        .yc-price{font-size:1.15rem;font-weight:800;color:var(--gy);}
        .yc-price-unit{font-size:.72rem;color:var(--muted);margin-left:.1rem;}
        .yc-btn-sm{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;padding:.45rem .9rem;font-size:.82rem;font-weight:700;background:var(--gy);color:#fff;border:none;cursor:pointer;}
        /* Empty */
        .yc-empty{text-align:center;padding:4rem 1rem;color:var(--muted);}
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="yacht-charter" />

{{-- Hero --}}
<div class="yc-hero">
    <div class="container">
        <div style="max-width:820px;">
            <h1><i class="fas fa-anchor me-2" style="color:rgba(255,255,255,.6);"></i>İstanbul Özel Yat Kiralama</h1>
            <p>Boğaz'da eşsiz bir deneyim için lüks yat seçeneklerimizi keşfedin. Doğum günü, düğün, kurumsal etkinlik veya özel tur.</p>
            <div class="yc-chip-row">
                <span class="yc-chip"><i class="fas fa-shield-alt fa-xs"></i> Ücretsiz iptal</span>
                <span class="yc-chip"><i class="fas fa-users fa-xs"></i> 1'den 100+ kişiye</span>
                <span class="yc-chip"><i class="fas fa-clock fa-xs"></i> 1-8 saat seçeneği</span>
                <span class="yc-chip"><i class="fas fa-car fa-xs"></i> Transfer mevcut</span>
                <span class="yc-chip"><i class="fas fa-star fa-xs"></i> B2B Fiyatlar</span>
            </div>
        </div>
    </div>
</div>

{{-- Filter bar --}}
<div class="yc-filter-bar">
    <div class="container">
        <span class="yc-pill active"><i class="fas fa-ship fa-xs"></i> Tümü</span>
        <span class="yc-pill" onclick="filterCards('ozel')"><i class="fas fa-anchor fa-xs"></i> Özel Yat</span>
        <span class="yc-pill" onclick="filterCards('ada')"><i class="fas fa-island-tropical fa-xs"></i> Ada Turu</span>
        <span class="yc-pill" onclick="filterCards('luks')"><i class="fas fa-gem fa-xs"></i> Ultra Lüks</span>
        <span class="ms-auto" style="font-size:.82rem;color:var(--muted);">{{ $packages->count() }} seçenek</span>
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    @if($packages->isEmpty())
        <div class="yc-empty">
            <i class="fas fa-anchor fa-3x mb-3" style="color:var(--brd);"></i>
            <h5>Henüz yat paketi eklenmemiş</h5>
            <p>Superadmin panelinden Leisure Ayarları'na gidip "yacht" tipinde paket ekleyin.</p>
        </div>
    @else
        <div class="yc-grid" id="yachtGrid">
            @foreach($packages as $package)
                @php
                    $heroImg = $package->hero_image_url ?: 'https://images.pexels.com/photos/1001682/pexels-photo-1001682.jpeg?auto=compress&cs=tinysrgb&w=800';
                @endphp
                <a href="{{ route('acente.yacht-charter.show-product', $package->code) }}"
                   class="yc-card"
                   data-level="{{ $package->level }}">
                    <div class="yc-card-img">
                        <img src="{{ $heroImg }}" alt="{{ $package->name_tr }}" loading="lazy">
                        @if($package->badge_text)
                            <span class="yc-badge featured">{{ $package->badge_text }}</span>
                        @endif
                    </div>
                    <div class="yc-card-body">
                        <div class="yc-card-meta">
                            <i class="fas fa-anchor fa-xs"></i>
                            İstanbul Boğazı
                            @if($package->duration_hours)
                                <span>·</span><i class="fas fa-clock fa-xs"></i> min {{ number_format((float)$package->duration_hours,0) }} saat
                            @endif
                        </div>
                        <div class="yc-card-title">{{ $package->name_tr }}</div>
                        @if($package->summary_tr)
                            <div style="font-size:.82rem;color:var(--muted);line-height:1.4;">{{ Str::limit($package->summary_tr, 90) }}</div>
                        @endif
                        <div class="yc-card-feats">
                            @if($package->max_pax)
                                <span class="yc-card-feat"><i class="fas fa-users fa-xs"></i> maks {{ $package->max_pax }} kişi</span>
                            @endif
                            @if($package->pier_name)
                                <span class="yc-card-feat"><i class="fas fa-map-marker-alt fa-xs"></i> {{ $package->pier_name }}</span>
                            @endif
                            @if(!empty($package->includes_tr))
                                <span class="yc-card-feat"><i class="fas fa-check fa-xs text-success"></i> {{ count($package->includes_tr) }} dahil</span>
                            @endif
                        </div>
                    </div>
                    <div class="yc-card-footer">
                        <div>
                            <div class="yc-price-label">B2B saatlik</div>
                            <div>
                                <span class="yc-price">{{ number_format((float)($package->base_price_per_person??0),0,',','.') }}</span>
                                <span class="yc-price-unit">{{ $package->currency ?: 'EUR' }}/saat</span>
                            </div>
                            @if($package->original_price_per_person)
                                <div style="font-size:.72rem;color:var(--muted);">Önerilen: {{ number_format((float)$package->original_price_per_person,0,',','.') }} {{ $package->currency ?: 'EUR' }}/saat</div>
                            @endif
                        </div>
                        <span class="yc-btn-sm">Seç <i class="fas fa-arrow-right fa-xs"></i></span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

@include('acente.partials.theme-script')
<script>
function filterCards(level) {
    document.querySelectorAll('.yc-pill').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.querySelectorAll('.yc-card').forEach(card => {
        if (level === 'all') { card.style.display = ''; return; }
        const lv = card.dataset.level || '';
        card.style.display = (level === 'ozel' && ['standard','vip','premium'].includes(lv))
            || (level === 'ada' && lv === 'ada')
            || (level === 'luks' && lv === 'ultra')
            ? '' : 'none';
    });
}
document.querySelector('.yc-pill.active').addEventListener('click', () => {
    document.querySelectorAll('.yc-pill').forEach(p => p.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.querySelectorAll('.yc-card').forEach(c => c.style.display = '');
});
</script>
</body>
</html>

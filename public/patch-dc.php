<?php
/**
 * patch-dc.php — Dinner Cruise B2B dosya güncellemesi
 * Kullanım: https://gruptalepleri.com/patch-dc.php?key=gtp2026deploy
 * Kullandıktan sonra silin!
 */

$key = (string)($_GET['key'] ?? '');
if (!hash_equals('gtp2026deploy', $key)) {
    http_response_code(403);
    exit('Yetkisiz erisim.');
}

$base = realpath(__DIR__ . '/..');
if (!$base || !is_dir($base . '/app')) {
    $base = realpath(__DIR__ . '/../..');
}

$log = [];

// ── 1. catalog.blade.php ──────────────────────────────────────────────────
$catalog = <<<'BLADE_CATALOG'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Boğazda Türk Gecesi — Dinner Cruise Paketleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root {
            --gt-orange: #ff5533;
            --gt-orange-dark: #e8411d;
            --gt-text: #1a1a1a;
            --gt-muted: #595959;
            --gt-border: #e8e8e8;
            --gt-bg: #f5f5f5;
            --gt-card: #ffffff;
            --gt-star: #f5a623;
            --gt-radius: 12px;
        }
        html[data-theme="dark"] {
            --gt-text: #f0f0f0;
            --gt-muted: #b0b0b0;
            --gt-border: #333;
            --gt-bg: #0f1520;
            --gt-card: #1a2235;
        }

        body { background: var(--gt-bg); color: var(--gt-text); min-height: 100vh; }

        /* ── Hero bant ── */
        .dc-hero {
            background: linear-gradient(135deg, #0b1f42 0%, #1a3a6e 60%, #0e2d5a 100%);
            color: #fff;
            padding: 2.5rem 0 2rem;
        }
        .dc-hero-inner { max-width: 900px; }
        .dc-hero h1 { font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 800; margin: 0 0 .6rem; line-height: 1.15; }
        .dc-hero p { color: rgba(255,255,255,.82); margin: 0 0 1rem; font-size: 1rem; }
        .dc-hero-chips { display: flex; flex-wrap: wrap; gap: .5rem; }
        .dc-chip { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); padding: .3rem .7rem; font-size: .78rem; font-weight: 600; }
        .dc-rating-strip { display: flex; align-items: center; gap: .4rem; margin-top: .9rem; }
        .dc-rating-strip .stars { color: var(--gt-star); }
        .dc-rating-strip span { font-size: .88rem; color: rgba(255,255,255,.75); }

        /* ── Filtre bar ── */
        .dc-filter-bar { background: var(--gt-card); border-bottom: 1px solid var(--gt-border); padding: .8rem 0; position: sticky; top: 64px; z-index: 99; }
        .dc-filter-bar .container { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .dc-filter-pill { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1px solid var(--gt-border); background: var(--gt-card); color: var(--gt-text); padding: .38rem .75rem; font-size: .82rem; font-weight: 600; cursor: pointer; transition: border-color .15s, background .15s; white-space: nowrap; }
        .dc-filter-pill:hover, .dc-filter-pill.active { border-color: var(--gt-orange); color: var(--gt-orange); background: rgba(255,85,51,.07); }
        .dc-result-count { margin-left: auto; font-size: .82rem; color: var(--gt-muted); }

        /* ── Kart grid ── */
        .dc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; padding: 1.5rem 0 2.5rem; }

        /* ── Ürün kartı ── */
        .dc-card { background: var(--gt-card); border-radius: var(--gt-radius); border: 1px solid var(--gt-border); overflow: hidden; transition: box-shadow .2s, transform .2s; cursor: pointer; text-decoration: none; color: var(--gt-text); display: flex; flex-direction: column; }
        .dc-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,.13); transform: translateY(-2px); text-decoration: none; color: var(--gt-text); }
        .dc-card-img { position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden; background: #ddd; }
        .dc-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .35s; }
        .dc-card:hover .dc-card-img img { transform: scale(1.04); }
        .dc-badge { position: absolute; top: .6rem; left: .6rem; border-radius: 6px; padding: .3rem .55rem; font-size: .72rem; font-weight: 700; z-index: 2; }
        .dc-badge.popular { background: #fff; color: var(--gt-text); box-shadow: 0 2px 6px rgba(0,0,0,.15); }
        .dc-badge.certified { background: #0a6fcf; color: #fff; }
        .dc-badge.new { background: #12a354; color: #fff; }
        .dc-wishlist { position: absolute; top: .6rem; right: .6rem; width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,.92); border: none; display: flex; align-items: center; justify-content: center; color: #888; font-size: .9rem; cursor: pointer; z-index: 2; transition: color .15s; }
        .dc-wishlist:hover { color: var(--gt-orange); }
        .dc-card-body { padding: .85rem; flex: 1; display: flex; flex-direction: column; gap: .35rem; }
        .dc-card-meta { font-size: .78rem; color: var(--gt-muted); display: flex; align-items: center; gap: .5rem; }
        .dc-card-title { font-size: 1rem; font-weight: 700; line-height: 1.3; color: var(--gt-text); }
        .dc-card-features { display: flex; flex-wrap: wrap; gap: .28rem; margin-top: .1rem; }
        .dc-card-feat { font-size: .74rem; color: var(--gt-muted); display: flex; align-items: center; gap: .22rem; }
        .dc-card-rating { display: flex; align-items: center; gap: .32rem; margin-top: auto; padding-top: .5rem; }
        .dc-card-rating .star { color: var(--gt-star); font-size: .85rem; }
        .dc-card-rating .score { font-weight: 700; font-size: .88rem; }
        .dc-card-rating .count { font-size: .78rem; color: var(--gt-muted); }
        .dc-card-footer { border-top: 1px solid var(--gt-border); padding: .7rem .85rem; display: flex; align-items: center; justify-content: space-between; }
        .dc-price-label { font-size: .72rem; color: var(--gt-muted); }
        .dc-price-original { font-size: .78rem; color: var(--gt-muted); text-decoration: line-through; }
        .dc-price-current { font-size: 1.15rem; font-weight: 800; color: var(--gt-orange); }
        .dc-btn-detail { display: inline-flex; align-items: center; gap: .3rem; border-radius: 999px; border: 1.5px solid var(--gt-orange); background: transparent; color: var(--gt-orange); padding: .36rem .75rem; font-size: .8rem; font-weight: 700; text-decoration: none; transition: background .15s, color .15s; }
        .dc-btn-detail:hover { background: var(--gt-orange); color: #fff; text-decoration: none; }

        /* ── Sayfa başlığı ── */
        .dc-section-title { font-size: 1.2rem; font-weight: 800; margin: 0 0 .1rem; color: var(--gt-text); }
        .dc-section-sub { font-size: .88rem; color: var(--gt-muted); }

        @media (max-width: 575px) {
            .dc-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="dinner-cruise" />

{{-- Hero --}}
<div class="dc-hero">
    <div class="container">
        <div class="dc-hero-inner">
            <h1>İstanbul: Türk Gecesi Gösterisi ile<br>Boğazda Akşam Yemeği Gezisi</h1>
            <p>Profesyonel oryantal dans, halk oyunları ve 3 kurslu akşam yemeği eşliğinde Boğaz'da unutulmaz bir gece.</p>
            <div class="dc-hero-chips">
                <span class="dc-chip"><i class="fas fa-clock"></i> 3 saat</span>
                <span class="dc-chip"><i class="fas fa-ship"></i> Kabataş İskelesi</span>
                <span class="dc-chip"><i class="fas fa-utensils"></i> Akşam yemeği dahil</span>
                <span class="dc-chip"><i class="fas fa-star"></i> Canlı Türk Gecesi Şovu</span>
                <span class="dc-chip"><i class="fas fa-car"></i> Transfer opsiyonel</span>
            </div>
            <div class="dc-rating-strip">
                <div class="stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                </div>
                <strong style="color:#fff;">4.6</strong>
                <span>• 2.053 yorum • GT sertifikalı ürün</span>
            </div>
        </div>
    </div>
</div>

{{-- Filtre bar --}}
<div class="dc-filter-bar">
    <div class="container">
        <button class="dc-filter-pill active" onclick="filterCards('all', this)"><i class="fas fa-border-all"></i> Tümü</button>
        <button class="dc-filter-pill" onclick="filterCards('popular', this)"><i class="fas fa-fire"></i> En Popüler</button>
        <button class="dc-filter-pill" onclick="filterCards('alcohol', this)"><i class="fas fa-wine-glass-alt"></i> Alkollü</button>
        <button class="dc-filter-pill" onclick="filterCards('transfer', this)"><i class="fas fa-car"></i> Transfer Dahil</button>
        <span class="dc-result-count">{{ $packages->count() }} paket</span>
    </div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success mt-3">{{ session('success') }}</div>
    @endif

    <div class="d-flex align-items-center justify-content-between mt-4 mb-1">
        <div>
            <div class="dc-section-title">Tüm Dinner Cruise Paketleri</div>
            <div class="dc-section-sub">Paketi seçin, detayları inceleyin ve direkt rezervasyon yapın.</div>
        </div>
        <a href="{{ route('acente.dinner-cruise.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Talep Sistemi
        </a>
    </div>

    <div class="dc-grid" id="dc-grid">
        @forelse($packages as $pkg)
            @php
                $badgeClass = match(strtolower((string)($pkg->badge_text ?? ''))) {
                    'en çok tercih edilen' => 'popular',
                    'gt sertifikalı'        => 'certified',
                    'yeni etkinlik'         => 'new',
                    default                 => 'popular',
                };
                $filterTags = 'all';
                if (str_contains(strtolower((string)($pkg->name_tr ?? '')), 'transfer')) $filterTags .= ' transfer';
                if (str_contains(strtolower(implode(' ', (array)($pkg->includes_tr ?? []))), 'alkollü')) $filterTags .= ' alcohol';
                if ($pkg->badge_text === 'En Çok Tercih Edilen') $filterTags .= ' popular';

                $heroImg = $pkg->hero_image_url ?: 'https://images.pexels.com/photos/3411083/pexels-photo-3411083.jpeg?auto=compress&cs=tinysrgb&w=600';
            @endphp
            <a href="{{ route('acente.dinner-cruise.show-product', $pkg->code) }}"
               class="dc-card"
               data-tags="{{ $filterTags }}">
                <div class="dc-card-img">
                    <img src="{{ $heroImg }}" alt="{{ $pkg->name_tr }}" loading="lazy">
                    @if($pkg->badge_text)
                        <span class="dc-badge {{ $badgeClass }}">{{ $pkg->badge_text }}</span>
                    @endif
                    <button class="dc-wishlist" onclick="event.preventDefault(); toggleWishlist(this)">
                        <i class="far fa-heart"></i>
                    </button>
                </div>

                <div class="dc-card-body">
                    <div class="dc-card-meta">
                        @if($pkg->duration_hours)
                            <span><i class="fas fa-clock fa-xs me-1"></i>{{ number_format((float)$pkg->duration_hours, 0) }} saat</span>
                        @endif
                        @if($pkg->pier_name)
                            <span>• <i class="fas fa-ship fa-xs me-1"></i>{{ Str::before($pkg->pier_name, ',') }}</span>
                        @endif
                    </div>

                    <div class="dc-card-title">{{ $pkg->name_tr }}</div>

                    <div class="dc-card-features">
                        @foreach(collect($pkg->includes_tr ?? [])->take(3) as $inc)
                            <span class="dc-card-feat"><i class="fas fa-check-circle fa-xs" style="color:#12a354;"></i> {{ $inc }}</span>
                        @endforeach
                    </div>

                    <div class="dc-card-rating">
                        <span class="star"><i class="fas fa-star fa-xs"></i></span>
                        <span class="score">{{ $pkg->rating ?? '4.6' }}</span>
                        <span class="count">({{ number_format((int)($pkg->review_count ?? 2053)) }})</span>
                    </div>
                </div>

                <div class="dc-card-footer">
                    <div>
                        @if($pkg->original_price_per_person)
                            <div class="dc-price-label">Önerilen satış: {{ number_format((float)$pkg->original_price_per_person, 0, ',', '.') }} {{ $pkg->currency ?: 'EUR' }}/kişi</div>
                        @else
                            <div class="dc-price-label">Kişi başı B2B fiyat</div>
                        @endif
                        <div class="dc-price-current">{{ number_format((float)($pkg->base_price_per_person ?? 0), 0, ',', '.') }} {{ $pkg->currency ?: 'EUR' }}</div>
                    </div>
                    <span class="dc-btn-detail">İncele <i class="fas fa-arrow-right fa-xs"></i></span>
                </div>
            </a>
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-ship fa-3x mb-3 opacity-25"></i>
                    <p>Henüz aktif paket tanımı bulunmuyor.</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Bilgi kutusu --}}
    <div class="row g-3 pb-4">
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: var(--gt-card); border: 1px solid var(--gt-border);">
                <div class="fw-bold mb-1"><i class="fas fa-shield-alt text-success me-2"></i>Ücretsiz İptal</div>
                <div class="small text-muted">Hizmetten 24 saat öncesine kadar ücretsiz iptal hakkı.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: var(--gt-card); border: 1px solid var(--gt-border);">
                <div class="fw-bold mb-1"><i class="fas fa-clock text-primary me-2"></i>Anında Onay</div>
                <div class="small text-muted">Rezervasyonunuz ödeme sonrası anında onaylanır, beklemenize gerek yok.</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 rounded-3" style="background: var(--gt-card); border: 1px solid var(--gt-border);">
                <div class="fw-bold mb-1"><i class="fas fa-headset text-warning me-2"></i>7/24 Destek</div>
                <div class="small text-muted">Operasyon ekibimiz her zaman ulaşılabilir, sorularınızı yanıtlar.</div>
            </div>
        </div>
    </div>
</div>

@include('acente.partials.theme-script')
<script>
function filterCards(tag, btn) {
    document.querySelectorAll('.dc-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#dc-grid .dc-card').forEach(card => {
        const tags = card.dataset.tags || 'all';
        card.style.display = (tag === 'all' || tags.includes(tag)) ? '' : 'none';
    });
}

function toggleWishlist(btn) {
    const icon = btn.querySelector('i');
    if (icon.classList.contains('far')) {
        icon.classList.replace('far', 'fas');
        btn.style.color = '#ff5533';
    } else {
        icon.classList.replace('fas', 'far');
        btn.style.color = '';
    }
}
</script>
</body>
</html>

BLADE_CATALOG;

$path = $base . '/resources/views/acente/dinner-cruise/catalog.blade.php';
file_put_contents($path, $catalog);
$log[] = 'OK: catalog.blade.php (' . strlen($catalog) . ' byte)';

// ── 2. show.blade.php ─────────────────────────────────────────────────────
$show = <<<'BLADE_SHOW'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $package->name_tr }} — Dinner Cruise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root{--gy:#ff5533;--gy-dark:#e8411d;--txt:#1a1a1a;--muted:#595959;--brd:#e8e8e8;--bg:#f5f5f5;--card:#fff;--star:#f5a623;}
        html[data-theme="dark"]{--txt:#f0f0f0;--muted:#b0b0b0;--brd:#333;--bg:#0f1520;--card:#1a2235;}
        body{background:var(--bg);color:var(--txt);}
        /* breadcrumb */
        .dc-breadcrumb{background:var(--card);border-bottom:1px solid var(--brd);padding:.6rem 0;font-size:.82rem;color:var(--muted);}
        .dc-breadcrumb a{color:var(--muted);text-decoration:none;}.dc-breadcrumb a:hover{color:var(--gy);}
        /* gallery */
        .dc-gallery{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:1fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;background:#ccc;}
        .dc-gallery-main{grid-row:1/3;overflow:hidden;}.dc-gallery-main img,.dc-gallery-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
        .dc-gallery-thumb{overflow:hidden;cursor:pointer;position:relative;}
        .dc-gallery-thumb:last-child .dc-gallery-more{position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;}
        @media(max-width:767px){.dc-gallery{grid-template-columns:1fr;grid-template-rows:260px;}.dc-gallery-thumb{display:none;}}
        /* layout */
        .dc-layout{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:2rem;align-items:start;padding:1.5rem 0 3rem;}
        @media(max-width:991px){.dc-layout{grid-template-columns:1fr;}}
        /* sticky panel */
        .dc-panel{position:sticky;top:72px;background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:1.25rem;box-shadow:0 4px 24px rgba(0,0,0,.08);}
        .dc-panel-price{font-size:1.6rem;font-weight:800;color:var(--gy);}
        .dc-panel-old{font-size:.9rem;color:var(--muted);text-decoration:line-through;}
        .dc-panel-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:.25rem;}
        .dc-panel-select{width:100%;padding:.55rem .7rem;border-radius:8px;border:1px solid var(--brd);background:var(--card);color:var(--txt);font-size:.9rem;margin-bottom:.8rem;}
        .dc-panel-input{width:100%;padding:.55rem .7rem;border-radius:8px;border:1px solid var(--brd);background:var(--card);color:var(--txt);font-size:.9rem;}
        .dc-pax-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;margin-bottom:.8rem;}
        .dc-pax-label{font-size:.7rem;color:var(--muted);margin-bottom:.18rem;font-weight:600;}
        .dc-btn-book{width:100%;padding:.78rem;border-radius:999px;background:var(--gy);border:none;color:#fff;font-size:1rem;font-weight:800;cursor:pointer;transition:background .15s;margin-top:.5rem;}
        .dc-btn-book:hover{background:var(--gy-dark);}
        .dc-total-row{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--brd);padding-top:.7rem;margin-top:.5rem;}
        .dc-total-label{font-size:.85rem;color:var(--muted);}
        .dc-total-amount{font-size:1.15rem;font-weight:800;color:var(--txt);}
        /* sections */
        .dc-sec{margin-bottom:1.8rem;}
        .dc-sec-title{font-size:1.15rem;font-weight:800;margin-bottom:.8rem;padding-bottom:.5rem;border-bottom:1px solid var(--brd);}
        .dc-check-list{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:1fr 1fr;gap:.4rem .8rem;}
        @media(max-width:575px){.dc-check-list{grid-template-columns:1fr;}}
        .dc-check-list li{font-size:.9rem;color:var(--txt);display:flex;align-items:flex-start;gap:.4rem;}
        .dc-check-list li .ico{margin-top:.1rem;flex-shrink:0;}
        .dc-x-list{list-style:none;padding:0;margin:0;}
        .dc-x-list li{font-size:.9rem;color:var(--muted);display:flex;align-items:flex-start;gap:.4rem;margin-bottom:.3rem;}
        /* timeline */
        .dc-timeline{list-style:none;padding:0;margin:0;position:relative;}
        .dc-timeline::before{content:'';position:absolute;left:52px;top:0;bottom:0;width:2px;background:var(--brd);}
        .dc-timeline li{display:flex;gap:1rem;margin-bottom:1rem;position:relative;}
        .dc-tl-time{min-width:44px;text-align:right;font-weight:700;font-size:.82rem;color:var(--muted);padding-top:.1rem;}
        .dc-tl-dot{width:10px;height:10px;border-radius:50%;background:var(--gy);margin-top:.3rem;flex-shrink:0;position:relative;z-index:1;}
        .dc-tl-text{font-size:.9rem;}
        /* pkg tabs */
        .dc-pkg-tabs{display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap;}
        .dc-pkg-tab{padding:.4rem .9rem;border-radius:999px;border:1.5px solid var(--brd);font-size:.82rem;font-weight:700;cursor:pointer;background:var(--card);color:var(--txt);transition:all .15s;}
        .dc-pkg-tab.active{border-color:var(--gy);background:rgba(255,85,51,.08);color:var(--gy);}
        /* rating */
        .dc-stars{color:var(--star);}
        .dc-rating-bar-row{display:flex;align-items:center;gap:.7rem;margin-bottom:.3rem;}
        .dc-rating-bar-bg{flex:1;height:8px;border-radius:4px;background:var(--brd);overflow:hidden;}
        .dc-rating-bar-fill{height:100%;background:var(--star);border-radius:4px;}
        /* info tags */
        .dc-info-tag{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid var(--brd);padding:.45rem .7rem;font-size:.82rem;background:var(--card);}
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="dinner-cruise" />

{{-- Breadcrumb --}}
<div class="dc-breadcrumb">
    <div class="container">
        <a href="{{ route('acente.dinner-cruise.catalog') }}">Dinner Cruise</a>
        <span class="mx-1">/</span>
        <span>{{ $package->name_tr }}</span>
    </div>
</div>

@php
    $heroImg = $package->hero_image_url ?: 'https://images.pexels.com/photos/3411083/pexels-photo-3411083.jpeg?auto=compress&cs=tinysrgb&w=1200';
    $galleryImgs = $mediaAssets->where('media_type','photo')->take(3)->values();
    $timeline = is_array($package->timeline_tr) ? $package->timeline_tr : json_decode($package->timeline_tr ?? '[]', true);
    $departureTimes = is_array($package->departure_times) ? $package->departure_times : json_decode($package->departure_times ?? '[]', true);
    $importantNotes = is_array($package->important_notes_tr) ? $package->important_notes_tr : json_decode($package->important_notes_tr ?? '[]', true);
@endphp

<div class="container mt-3">
    {{-- Gallery --}}
    <div class="dc-gallery mb-3">
        <div class="dc-gallery-main">
            <img src="{{ $heroImg }}" alt="{{ $package->name_tr }}" loading="lazy">
        </div>
        @foreach($galleryImgs->take(2) as $i => $asset)
            <div class="dc-gallery-thumb">
                <img src="{{ $asset->resolvedUrl() }}" alt="{{ $asset->title_tr }}" loading="lazy">
                @if($i === 1 && $galleryImgs->count() > 2)
                    <div class="dc-gallery-more">+{{ $galleryImgs->count() - 2 }} fotoğraf</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="dc-layout">
        {{-- ── Sol: İçerik ── --}}
        <div>
            {{-- Başlık --}}
            <div class="mb-3">
                @if($package->badge_text)
                    <span class="badge text-bg-warning mb-2" style="font-size:.75rem;">{{ $package->badge_text }}</span>
                @endif
                <h1 style="font-size:1.7rem;font-weight:800;line-height:1.2;margin:0 0 .5rem;">{{ $package->name_tr }}</h1>
                <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:.88rem;color:var(--muted);">
                    <span class="dc-stars">
                        @for($s=1;$s<=5;$s++)
                            <i class="fa{{ $s <= floor($package->rating ?? 4.6) ? 's' : ($s - ($package->rating ?? 4.6) < 1 ? 's fa-star-half-alt' : 'r') }} fa-star fa-xs"></i>
                        @endfor
                    </span>
                    <strong style="color:var(--txt);">{{ $package->rating ?? '4.6' }}</strong>
                    <span>{{ number_format((int)($package->review_count ?? 2053)) }} yorum</span>
                    @if($package->duration_hours)
                        <span><i class="fas fa-clock fa-xs me-1"></i>{{ number_format((float)$package->duration_hours, 0) }} saat</span>
                    @endif
                    @if($package->pier_name)
                        <span><i class="fas fa-ship fa-xs me-1"></i>{{ $package->pier_name }}</span>
                    @endif
                </div>
            </div>

            {{-- Öne çıkan bilgiler --}}
            <div class="d-flex gap-2 flex-wrap mb-4">
                <span class="dc-info-tag"><i class="fas fa-ban-smoking fa-xs text-success"></i> Sıra beklemeden giriş</span>
                <span class="dc-info-tag"><i class="fas fa-car fa-xs text-primary"></i> Araçla alma mevcut</span>
                <span class="dc-info-tag"><i class="fas fa-utensils fa-xs text-warning"></i> Yemek dahil</span>
                <span class="dc-info-tag"><i class="fas fa-shield-alt fa-xs text-success"></i> Ücretsiz iptal</span>
            </div>

            {{-- Paket seçici --}}
            <div class="dc-sec">
                <div class="dc-sec-title">4 mevsimsel seçenek seçin yap</div>
                <div class="dc-pkg-tabs">
                    @foreach($allPackages as $p)
                        <a href="{{ route('acente.dinner-cruise.show-product', $p->code) }}"
                           class="dc-pkg-tab {{ $p->id === $package->id ? 'active' : '' }}">
                            {{ $p->name_tr }}
                        </a>
                    @endforeach
                </div>
                <div class="p-3 rounded-3" style="background:rgba(255,85,51,.06);border:1px solid rgba(255,85,51,.2);">
                    <div class="fw-bold mb-1">{{ $package->name_tr }}</div>
                    <div style="font-size:.88rem;color:var(--muted);">{{ $package->summary_tr }}</div>
                    <div class="mt-2">
                        @if($package->original_price_per_person)
                            <div style="font-size:.75rem;color:var(--muted);margin-bottom:.15rem;">Önerilen satış: <strong>{{ number_format((float)$package->original_price_per_person,0,',','.') }} {{ $package->currency ?: 'EUR' }}</strong>/kişi</div>
                        @endif
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:1.25rem;font-weight:800;color:var(--gy);">{{ number_format((float)($package->base_price_per_person??0),0,',','.') }} {{ $package->currency ?: 'EUR' }}</span>
                            <span style="font-size:.78rem;color:var(--muted);">/ kişi (B2B)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Açıklama --}}
            @if($package->long_description_tr)
            <div class="dc-sec">
                <div class="dc-sec-title">Hakkında</div>
                <div style="font-size:.94rem;line-height:1.75;white-space:pre-line;">{{ $package->long_description_tr }}</div>
            </div>
            @endif

            {{-- Dahil olanlar --}}
            @if(!empty($package->includes_tr))
            <div class="dc-sec">
                <div class="dc-sec-title">Dahil olanlar</div>
                <ul class="dc-check-list">
                    @foreach($package->includes_tr as $inc)
                        <li><span class="ico"><i class="fas fa-check-circle text-success"></i></span>{{ $inc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Hariç olanlar --}}
            @if(!empty($package->excludes_tr))
            <div class="dc-sec">
                <div class="dc-sec-title">Hariç olanlar</div>
                <ul class="dc-x-list">
                    @foreach($package->excludes_tr as $exc)
                        <li><i class="fas fa-times text-danger fa-xs mt-1"></i>{{ $exc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Program akışı --}}
            @if(!empty($timeline))
            <div class="dc-sec">
                <div class="dc-sec-title">Program akışı</div>
                <ul class="dc-timeline">
                    @foreach($timeline as $step)
                        <li>
                            <span class="dc-tl-time">{{ $step['time'] ?? '' }}</span>
                            <span class="dc-tl-dot"></span>
                            <span class="dc-tl-text">{{ $step['event'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Buluşma noktası --}}
            <div class="dc-sec">
                <div class="dc-sec-title">Buluşma noktası</div>
                <div class="d-flex gap-2 align-items-start p-3 rounded-3" style="background:var(--card);border:1px solid var(--brd);">
                    <i class="fas fa-map-marker-alt text-danger mt-1"></i>
                    <div>
                        <div class="fw-bold" style="font-size:.9rem;">{{ $package->pier_name ?? 'Kabataş İskelesi' }}</div>
                        <div style="font-size:.84rem;color:var(--muted);">{{ $package->meeting_point ?? 'Kabataş İskelesi D kapısı önünde rehber karşılama — kalkıştan 30 dk önce olunuz.' }}</div>
                    </div>
                </div>
            </div>

            {{-- Önemli bilgiler --}}
            @if(!empty($importantNotes))
            <div class="dc-sec">
                <div class="dc-sec-title">Önemli bilgiler</div>
                <ul class="dc-x-list">
                    @foreach($importantNotes as $note)
                        <li><i class="fas fa-info-circle text-primary fa-xs mt-1"></i><span>{{ $note }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- İptal politikası --}}
            <div class="dc-sec">
                <div class="dc-sec-title">İptal politikası</div>
                <div class="d-flex gap-2 p-3 rounded-3" style="background:rgba(18,163,84,.07);border:1px solid rgba(18,163,84,.2);">
                    <i class="fas fa-shield-alt text-success mt-1"></i>
                    <span style="font-size:.9rem;">{{ $package->cancellation_policy_tr ?? 'Hizmetten 24 saat öncesine kadar ücretsiz iptal.' }}</span>
                </div>
            </div>

            {{-- Değerlendirmeler --}}
            <div class="dc-sec">
                <div class="dc-sec-title">Müşteri yorumları</div>
                <div class="d-flex gap-4 align-items-center mb-3">
                    <div class="text-center">
                        <div style="font-size:3rem;font-weight:800;line-height:1;">{{ $package->rating ?? '4.6' }}</div>
                        <div class="dc-stars mb-1"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                        <div style="font-size:.8rem;color:var(--muted);">{{ number_format((int)($package->review_count??2053)) }} yorum</div>
                    </div>
                    <div class="flex-1 w-100">
                        @foreach([5=>75, 4=>15, 3=>6, 2=>2, 1=>2] as $star => $pct)
                        <div class="dc-rating-bar-row">
                            <span style="font-size:.78rem;min-width:12px;">{{ $star }}</span>
                            <div class="dc-rating-bar-bg"><div class="dc-rating-bar-fill" style="width:{{ $pct }}%"></div></div>
                            <span style="font-size:.78rem;color:var(--muted);min-width:28px;">{{ $pct }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Sağ: Rezervasyon Paneli ── --}}
        <div>
            <div class="dc-panel">
                <div class="mb-1">
                    @if($package->original_price_per_person)
                        <div style="font-size:.75rem;color:var(--muted);">Önerilen satış: <strong>{{ number_format((float)$package->original_price_per_person,0,',','.') }} {{ $package->currency ?: 'EUR' }}</strong>/kişi</div>
                    @endif
                </div>
                <div class="dc-panel-price">{{ number_format((float)($package->base_price_per_person??0),0,',','.') }} {{ $package->currency ?: 'EUR' }}</div>
                <div style="font-size:.78rem;color:var(--muted);margin-bottom:1rem;">kişi başı (B2B fiyat)</div>

                <form id="bookForm" action="{{ route('acente.dinner-cruise.book', $package->code) }}" method="POST">
                    @csrf
                    {{-- Tarih --}}
                    <div class="dc-panel-label">Tarih</div>
                    <input type="date" name="service_date" class="dc-panel-input mb-3"
                           min="{{ now()->addDay()->format('Y-m-d') }}" required>

                    {{-- Kalkış saati --}}
                    @if(count($departureTimes) === 1)
                        <div class="dc-panel-label">Kalkış saati</div>
                        <div class="dc-panel-input" style="display:flex;align-items:center;font-weight:600;">{{ $departureTimes[0] }}</div>
                        <input type="hidden" name="departure_time" value="{{ $departureTimes[0] }}">
                    @elseif(count($departureTimes) > 1)
                        <div class="dc-panel-label">Kalkış saati</div>
                        <select name="departure_time" class="dc-panel-select" required>
                            <option value="">Seçin</option>
                            @foreach($departureTimes as $dt)
                                <option value="{{ $dt }}">{{ $dt }}</option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Kişi sayısı --}}
                    <div class="dc-panel-label">Kişi sayısı</div>
                    <div class="dc-pax-row">
                        <div>
                            <div class="dc-pax-label">Yetişkin</div>
                            <input type="number" name="pax_adult" id="paxAdult" class="dc-panel-input" min="1" max="500" value="1" required>
                        </div>
                        <div>
                            <div class="dc-pax-label">Çocuk (2-12)</div>
                            <input type="number" name="pax_child" id="paxChild" class="dc-panel-input" min="0" max="500" value="0">
                        </div>
                        <div>
                            <div class="dc-pax-label">Bebek (0-2)</div>
                            <input type="number" name="pax_infant" id="paxInfant" class="dc-panel-input" min="0" max="100" value="0">
                        </div>
                    </div>

                    {{-- Ad Soyad + Tel --}}
                    <div class="dc-panel-label">Yetkili adı soyadı</div>
                    <input type="text" name="guest_name" class="dc-panel-input mb-3" placeholder="Ad Soyad" required>
                    <div class="dc-panel-label">Telefon</div>
                    <input type="text" name="guest_phone" class="dc-panel-input mb-3" placeholder="+90 5xx xxx xx xx" required>

                    {{-- Transfer --}}
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="transfer_required" value="1" id="chkTransfer">
                        <label class="form-check-label" style="font-size:.85rem;" for="chkTransfer">Otelden alınma transfer ekle</label>
                    </div>
                    <div id="transferFields" style="display:none;">
                        <input type="text" name="hotel_name" class="dc-panel-input mb-2" placeholder="Otel adı">
                        <input type="text" name="transfer_region" class="dc-panel-input mb-3" placeholder="Bölge (ör: Sultanahmet)">
                    </div>

                    <div class="dc-total-row">
                        <span class="dc-total-label">Tahmini toplam</span>
                        <span class="dc-total-amount" id="totalPrice">—</span>
                    </div>

                    <button type="submit" class="dc-btn-book">
                        <i class="fas fa-lock me-2"></i>Rezervasyon Yap &amp; Öde
                    </button>
                </form>

                <div class="text-center mt-2" style="font-size:.75rem;color:var(--muted);">
                    <i class="fas fa-shield-alt me-1 text-success"></i>Güvenli ödeme · 24s ücretsiz iptal
                </div>
            </div>

            {{-- Diğer paketler --}}
            <div class="mt-3 p-3 rounded-3" style="background:var(--card);border:1px solid var(--brd);">
                <div style="font-size:.8rem;font-weight:700;margin-bottom:.6rem;color:var(--muted);">DİĞER PAKETLER</div>
                @foreach($allPackages->where('id','!=',$package->id) as $p)
                    <a href="{{ route('acente.dinner-cruise.show-product', $p->code) }}" class="d-flex justify-content-between align-items-center text-decoration-none py-2" style="border-bottom:1px solid var(--brd);color:var(--txt);">
                        <span style="font-size:.85rem;">{{ $p->name_tr }}</span>
                        <span style="font-size:.88rem;font-weight:700;color:var(--gy);">{{ number_format((float)($p->base_price_per_person??0),0,',','.') }} {{ $p->currency ?: 'EUR' }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@include('acente.partials.theme-script')
<script>
const pricePerPerson = {{ (float)($package->base_price_per_person ?? 0) }};
const childRate = 0.5;
const currency = '{{ $package->currency ?: 'EUR' }}';

function calcTotal() {
    const adult  = parseInt(document.getElementById('paxAdult').value)  || 0;
    const child  = parseInt(document.getElementById('paxChild').value)   || 0;
    const total  = (adult * pricePerPerson) + (child * pricePerPerson * childRate);
    document.getElementById('totalPrice').textContent =
        total > 0 ? total.toLocaleString('tr-TR', {maximumFractionDigits:0}) + ' ' + currency : '—';
}

['paxAdult','paxChild','paxInfant'].forEach(id => {
    document.getElementById(id).addEventListener('input', calcTotal);
});
calcTotal();

document.getElementById('chkTransfer').addEventListener('change', function() {
    document.getElementById('transferFields').style.display = this.checked ? '' : 'none';
});
</script>
</body>
</html>

BLADE_SHOW;

$path = $base . '/resources/views/acente/dinner-cruise/show.blade.php';
file_put_contents($path, $show);
$log[] = 'OK: show.blade.php (' . strlen($show) . ' byte)';

// ── 3. settings.blade.php ─────────────────────────────────────────────────
$settings = <<<'BLADE_SETTINGS'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>Leisure Ayarlari</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .leisure-settings .shell-card{border-radius:18px;border:1px solid rgba(148,163,184,.2)}
        .leisure-settings .code-badge{font-size:.72rem;padding:.25rem .5rem;border-radius:999px;background:rgba(37,99,235,.12);color:#1d4ed8;font-weight:700}
        .leisure-settings .edit-surface{border-top:1px dashed rgba(148,163,184,.35);background:rgba(248,250,252,.55)}
        .leisure-settings .thumb-preview{width:68px;height:50px;object-fit:cover;border-radius:10px;border:1px solid rgba(148,163,184,.25)}
        html[data-theme="dark"] .leisure-settings .shell-card{border-color:#2d4371}
        html[data-theme="dark"] .leisure-settings .edit-surface{border-top-color:#2d4371;background:rgba(15,29,54,.55)}
        html[data-theme="dark"] .leisure-settings .thumb-preview{border-color:#2d4371}
    </style>
</head>
<body class="theme-scope leisure-settings">
<x-navbar-superadmin active="leisure-settings" />

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Leisure Ayarlari</h3>
            <div class="text-muted small">Dinner Cruise ve Yacht Charter icin paket, ekstra ve medya kutuphanesini yonetin.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('superadmin.dinner-cruise.showcase') }}" class="btn btn-primary btn-sm">Dinner Vitrin</a>
            <a href="{{ route('superadmin.dinner-cruise.index') }}" class="btn btn-outline-primary btn-sm">Dinner Talepleri</a>
            <a href="{{ route('superadmin.yacht-charter.index') }}" class="btn btn-outline-secondary btn-sm">Yacht Talepleri</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @php
        $bosphorusPackageSample = [
            'product_type' => 'dinner_cruise',
            'code' => 'bosphorus_dinner_cruise',
            'level' => 'premium',
            'sort_order' => 15,
            'name_tr' => 'Bosphorus Dinner Cruise',
            'name_en' => 'Bosphorus Dinner Cruise',
            'summary_tr' => 'Bogaz hattinda premium masa, show ve transfer dahil aksam deneyimi.',
            'summary_en' => 'Evening Bosphorus dinner cruise with premium seating, show and transfer support.',
            'hero_image_url' => '/uploads/leisure-media/seed-bosphorus/bosphorus-exterior-night-blue.jpg',
            'includes_tr_text' => "Shuttle transfer\nPremium menu\nBogaz manzarali premium masa\nCanli show programi",
            'includes_en_text' => "Shuttle transfer\nPremium menu\nPremium Bosphorus view table\nLive show program",
            'excludes_tr_text' => "Private yacht kapama\nOzel foto-video cekimi",
            'excludes_en_text' => "Private yacht buyout\nPrivate photo-video production",
            'is_active' => true,
        ];
    @endphp

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card shell-card shadow-sm mb-4">
                <div class="card-header fw-bold d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span>Paket Sablonu Ekle</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="fillBosphorusPackageBtn">Bosphorus Ornegini Doldur</button>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.leisure.settings.packages.store') }}" enctype="multipart/form-data" class="row g-3" id="leisurePackageCreateForm" data-bosphorus-sample='@json($bosphorusPackageSample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)'>
                        @csrf
                        <div class="col-12 col-md-6"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="dinner_cruise">Dinner Cruise</option><option value="yacht">Yacht Charter</option></select></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kod</label><input type="text" name="code" class="form-control" placeholder="standard"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Seviye</label><input type="text" name="level" class="form-control" placeholder="standard"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="100"></div>
                        <div class="col-12"><label class="form-label">TR Ad</label><input type="text" name="name_tr" class="form-control"></div>
                        <div class="col-12"><label class="form-label">EN Ad</label><input type="text" name="name_en" class="form-control"></div>
                        <div class="col-12"><label class="form-label">TR Ozet</label><input type="text" name="summary_tr" class="form-control"></div>
                        <div class="col-12"><label class="form-label">EN Ozet</label><input type="text" name="summary_en" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Hero Gorsel URL / Path</label><input type="text" name="hero_image_url" class="form-control" placeholder="/uploads/leisure-media/... veya https://..."></div>
                        <div class="col-12"><label class="form-label">Hero Gorsel Dosyasi (opsiyonel)</label><input type="file" name="hero_image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif"><div class="form-text">Dosya secilirse URL yerine bu dosya kullanilir.</div></div>
                        <div class="col-12"><label class="form-label">TR Dahil Olanlar</label><textarea name="includes_tr_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">EN Dahil Olanlar</label><textarea name="includes_en_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">TR Haric Olanlar</label><textarea name="excludes_tr_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">EN Haric Olanlar</label><textarea name="excludes_en_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold text-uppercase">Fiyat & Katalog Bilgileri</small></div>
                        <div class="col-12 col-md-6"><label class="form-label">B2B Fiyat (kisi basi)</label><input type="number" step="0.01" name="base_price_per_person" class="form-control" placeholder="850.00"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Önerilen Satış Fiyatı</label><input type="number" step="0.01" name="original_price_per_person" class="form-control" placeholder="1060.00"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Para Birimi</label><input type="text" name="currency" class="form-control" value="TRY" maxlength="3"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Sure (saat)</label><input type="number" step="0.5" name="duration_hours" class="form-control" placeholder="3"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Max Kisi</label><input type="number" name="max_pax" class="form-control" placeholder="300"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Iskele</label><input type="text" name="pier_name" class="form-control" placeholder="Kabatas"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Rozet Metni</label><input type="text" name="badge_text" class="form-control" placeholder="En Cok Tercih Edilen"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Puan (0-5)</label><input type="number" step="0.1" name="rating" class="form-control" placeholder="4.6"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Yorum Sayisi</label><input type="number" name="review_count" class="form-control" placeholder="2053"></div>
                        <div class="col-12"><label class="form-label">Kalkis Saatleri <small class="text-muted">(her satira bir saat)</small></label><textarea name="departure_times_text" class="form-control" rows="2" placeholder="19:30&#10;22:00"></textarea></div>
                        <div class="col-12"><label class="form-label">Bulusma Noktasi</label><input type="text" name="meeting_point" class="form-control"></div>
                        <div class="col-12"><label class="form-label">TR Uzun Aciklama</label><textarea name="long_description_tr" class="form-control" rows="4"></textarea></div>
                        <div class="col-12"><label class="form-label">EN Uzun Aciklama</label><textarea name="long_description_en" class="form-control" rows="4"></textarea></div>
                        <div class="col-12"><label class="form-label">Timeline JSON <small class="text-muted">[{"time":"...","title":"...","desc":"..."}]</small></label><textarea name="timeline_tr_json" class="form-control font-monospace" rows="5" spellcheck="false"></textarea></div>
                        <div class="col-12"><label class="form-label">Iptal Politikasi (TR)</label><textarea name="cancellation_policy_tr" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">Onemli Notlar (TR) <small class="text-muted">(her satira bir madde)</small></label><textarea name="important_notes_tr_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12 form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div>
                        <div class="col-12"><button class="btn btn-primary">Paket Ekle</button></div>
                    </form>
                </div>
            </div>

            <div class="card shell-card shadow-sm">
                <div class="card-header fw-bold">Ekstra Secenegi Ekle</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.leisure.settings.extras.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12 col-md-6"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="">Tum urunler</option><option value="dinner_cruise">Dinner Cruise</option><option value="yacht">Yacht Charter</option></select></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" placeholder="transfer"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kod</label><input type="text" name="code" class="form-control" placeholder="vip_transfer"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="100"></div>
                        <div class="col-12"><label class="form-label">TR Baslik</label><input type="text" name="title_tr" class="form-control"></div>
                        <div class="col-12"><label class="form-label">EN Baslik</label><input type="text" name="title_en" class="form-control"></div>
                        <div class="col-12"><label class="form-label">TR Aciklama</label><input type="text" name="description_tr" class="form-control"></div>
                        <div class="col-12"><label class="form-label">EN Aciklama</label><input type="text" name="description_en" class="form-control"></div>
                        <div class="col-12 d-flex flex-wrap gap-3">
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="default_included" value="1"><label class="form-check-label">Varsayilan dahil</label></div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div>
                        </div>
                        <div class="col-12"><button class="btn btn-primary">Ekstra Ekle</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card shell-card shadow-sm mb-4">
                <div class="card-header fw-bold">Medya Kutuphanesi</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.leisure.settings.media.store') }}" enctype="multipart/form-data" class="row g-3 mb-4">
                        @csrf
                        <div class="col-12 col-md-4"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="">Tum urunler</option><option value="dinner_cruise">Dinner Cruise</option><option value="yacht">Yacht Charter</option></select></div>
                        <div class="col-12 col-md-4"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" placeholder="ambiyans"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Medya Tipi</label><select name="media_type" class="form-select"><option value="photo">Foto</option><option value="video">Video</option></select></div>
                        <div class="col-12 col-md-4"><label class="form-label">Kaynak Tipi</label><select name="source_type" class="form-select"><option value="upload">Upload</option><option value="link">Link</option></select></div>
                        <div class="col-12 col-md-4"><label class="form-label">TR Baslik</label><input type="text" name="title_tr" class="form-control"></div>
                        <div class="col-12 col-md-4"><label class="form-label">EN Baslik</label><input type="text" name="title_en" class="form-control"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Link</label><input type="url" name="external_url" class="form-control" placeholder="https://..."></div>
                        <div class="col-12 col-md-6"><label class="form-label">Dosya</label><input type="file" name="upload_file" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Etiketler</label><textarea name="tags_text" class="form-control" rows="2"></textarea></div>
                        <div class="col-12 col-md-3"><label class="form-label">Min kapasite</label><input type="number" name="capacity_min" class="form-control"></div>
                        <div class="col-12 col-md-3"><label class="form-label">Max kapasite</label><input type="number" name="capacity_max" class="form-control"></div>
                        <div class="col-12 col-md-3"><label class="form-label">Luks seviyesi</label><input type="text" name="luxury_level" class="form-control" placeholder="vip"></div>
                        <div class="col-12 col-md-3"><label class="form-label">Kullanim tipi</label><input type="text" name="usage_type" class="form-control" placeholder="shared/private"></div>
                        <div class="col-12 col-md-3"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="100"></div>
                        <div class="col-12 col-md-3 form-check form-switch align-self-end"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div>
                        <div class="col-12"><button class="btn btn-primary">Medya Ekle</button></div>
                    </form>

                    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Medya</th><th>Urun</th><th>Kategori</th><th>Tip</th><th>Etiket</th><th>Durum</th><th class="text-end">Islem</th></tr></thead>
                        <tbody>
                        @forelse($mediaAssets as $asset)
                            <tr>
                                <td><div class="d-flex align-items-center gap-2">@if($asset->media_type==='photo')<img src="{{ $asset->resolvedUrl() }}" alt="{{ $asset->title_tr }}" class="thumb-preview">@else<div class="thumb-preview d-flex align-items-center justify-content-center bg-body-secondary small fw-bold">VIDEO</div>@endif<div><div class="fw-semibold">{{ $asset->title_tr }}</div>@if($asset->title_en)<div class="small text-muted">{{ $asset->title_en }}</div>@endif</div></div></td>
                                <td>{{ $asset->product_type ?: 'Tum urunler' }}</td><td>{{ $asset->category ?: '-' }}</td><td>{{ strtoupper($asset->media_type) }}</td>
                                <td>@foreach(($asset->tags_json ?? []) as $tag)<span class="code-badge">{{ $tag }}</span>@endforeach</td>
                                <td>{{ $asset->is_active ? 'Aktif' : 'Pasif' }}</td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#media-edit-{{ $asset->id }}">Duzenle</button></td>
                            </tr>
                            <tr class="collapse" id="media-edit-{{ $asset->id }}"><td colspan="7" class="edit-surface p-0">
                                <form method="POST" action="{{ route('superadmin.leisure.settings.media.update', $asset) }}" enctype="multipart/form-data" class="row g-3 p-3">
                                    @csrf @method('PATCH')
                                    <div class="col-12 col-md-3"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="" @selected($asset->product_type===null)>Tum urunler</option><option value="dinner_cruise" @selected($asset->product_type==='dinner_cruise')>Dinner Cruise</option><option value="yacht" @selected($asset->product_type==='yacht')>Yacht Charter</option></select></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" value="{{ $asset->category }}"></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Medya Tipi</label><select name="media_type" class="form-select"><option value="photo" @selected($asset->media_type==='photo')>Foto</option><option value="video" @selected($asset->media_type==='video')>Video</option></select></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Kaynak Tipi</label><select name="source_type" class="form-select"><option value="upload" @selected($asset->source_type==='upload')>Upload</option><option value="link" @selected($asset->source_type==='link')>Link</option></select></div>
                                    <div class="col-12 col-md-6"><label class="form-label">TR Baslik</label><input type="text" name="title_tr" class="form-control" value="{{ $asset->title_tr }}"></div>
                                    <div class="col-12 col-md-6"><label class="form-label">EN Baslik</label><input type="text" name="title_en" class="form-control" value="{{ $asset->title_en }}"></div>
                                    <div class="col-12 col-md-6"><label class="form-label">Link</label><input type="url" name="external_url" class="form-control" value="{{ $asset->external_url }}"></div>
                                    <div class="col-12 col-md-6"><label class="form-label">Dosya (opsiyonel)</label><input type="file" name="upload_file" class="form-control"></div>
                                    <div class="col-12"><label class="form-label">Etiketler</label><textarea name="tags_text" class="form-control" rows="2">{{ implode(', ', $asset->tags_json ?? []) }}</textarea></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Min kapasite</label><input type="number" name="capacity_min" class="form-control" value="{{ $asset->capacity_min }}"></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Max kapasite</label><input type="number" name="capacity_max" class="form-control" value="{{ $asset->capacity_max }}"></div>
                                    <div class="col-12 col-md-2"><label class="form-label">Luks</label><input type="text" name="luxury_level" class="form-control" value="{{ $asset->luxury_level }}"></div>
                                    <div class="col-12 col-md-2"><label class="form-label">Kullanim</label><input type="text" name="usage_type" class="form-control" value="{{ $asset->usage_type }}"></div>
                                    <div class="col-12 col-md-2"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="{{ $asset->sort_order }}"></div>
                                    <div class="col-12 d-flex flex-wrap gap-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($asset->is_active)><label class="form-check-label">Aktif</label></div></div>
                                    <div class="col-12"><button class="btn btn-primary btn-sm">Kaydi Guncelle</button></div>
                                </form>
                            </td></tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Henuz medya eklenmedi.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>

            <div class="card shell-card shadow-sm mb-4">
                <div class="card-header fw-bold">Paketler</div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Urun</th><th>Kod</th><th>Hero</th><th>TR Ad</th><th>EN Ad</th><th>Durum</th><th class="text-end">Islem</th></tr></thead>
                    <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td>{{ $package->product_type }}</td>
                            <td><span class="code-badge">{{ $package->code }}</span></td>
                            <td>
                                @if($package->hero_image_url)
                                    <img src="{{ $package->hero_image_url }}" alt="{{ $package->name_tr }}" class="thumb-preview">
                                @else
                                    <span class="text-muted small">Yok</span>
                                @endif
                            </td>
                            <td>{{ $package->name_tr }}</td><td>{{ $package->name_en }}</td><td>{{ $package->is_active ? 'Aktif' : 'Pasif' }}</td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#package-edit-{{ $package->id }}">Duzenle</button></td>
                        </tr>
                        <tr class="collapse" id="package-edit-{{ $package->id }}"><td colspan="7" class="edit-surface p-0">
                            <form method="POST" action="{{ route('superadmin.leisure.settings.packages.update', $package) }}" enctype="multipart/form-data" class="row g-3 p-3">
                                @csrf @method('PATCH')
                                <div class="col-12 col-md-3"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="dinner_cruise" @selected($package->product_type==='dinner_cruise')>Dinner Cruise</option><option value="yacht" @selected($package->product_type==='yacht')>Yacht Charter</option></select></div>
                                <div class="col-12 col-md-3"><label class="form-label">Kod</label><input type="text" name="code" class="form-control" value="{{ $package->code }}"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Seviye</label><input type="text" name="level" class="form-control" value="{{ $package->level }}"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="{{ $package->sort_order }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Ad</label><input type="text" name="name_tr" class="form-control" value="{{ $package->name_tr }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Ad</label><input type="text" name="name_en" class="form-control" value="{{ $package->name_en }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Ozet</label><input type="text" name="summary_tr" class="form-control" value="{{ $package->summary_tr }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Ozet</label><input type="text" name="summary_en" class="form-control" value="{{ $package->summary_en }}"></div>
                                <div class="col-12"><label class="form-label">Hero Gorsel URL / Path</label><input type="text" name="hero_image_url" class="form-control" value="{{ $package->hero_image_url }}" placeholder="/uploads/leisure-media/... veya https://..."></div>
                                <div class="col-12"><label class="form-label">Hero Gorsel Dosyasi (opsiyonel)</label><input type="file" name="hero_image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif"><div class="form-text">Yeni dosya secersen mevcut URL/path yerine bu dosya kullanilir.</div></div>
                                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="clear_hero_image" value="1" id="clear-hero-image-{{ $package->id }}"><label class="form-check-label" for="clear-hero-image-{{ $package->id }}">Mevcut hero gorseli kaldir</label></div></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Dahil Olanlar</label><textarea name="includes_tr_text" class="form-control" rows="3">{{ implode(PHP_EOL, $package->includes_tr ?? []) }}</textarea></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Dahil Olanlar</label><textarea name="includes_en_text" class="form-control" rows="3">{{ implode(PHP_EOL, $package->includes_en ?? []) }}</textarea></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Haric Olanlar</label><textarea name="excludes_tr_text" class="form-control" rows="3">{{ implode(PHP_EOL, $package->excludes_tr ?? []) }}</textarea></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Haric Olanlar</label><textarea name="excludes_en_text" class="form-control" rows="3">{{ implode(PHP_EOL, $package->excludes_en ?? []) }}</textarea></div>

                                {{-- Katalog / Fiyat Alanlari --}}
                                <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold text-uppercase">Fiyat & Katalog Bilgileri</small></div>
                                <div class="col-12 col-md-3"><label class="form-label">B2B Fiyat (kisi basi)</label><input type="number" step="0.01" name="base_price_per_person" class="form-control" value="{{ $package->base_price_per_person }}" placeholder="850.00"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Önerilen Satış Fiyatı</label><input type="number" step="0.01" name="original_price_per_person" class="form-control" value="{{ $package->original_price_per_person }}" placeholder="1060.00"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Para Birimi</label><input type="text" name="currency" class="form-control" value="{{ $package->currency ?? 'TRY' }}" placeholder="TRY" maxlength="3"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Sure (saat)</label><input type="number" step="0.5" name="duration_hours" class="form-control" value="{{ $package->duration_hours }}" placeholder="3"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Max Kisi</label><input type="number" name="max_pax" class="form-control" value="{{ $package->max_pax }}" placeholder="300"></div>

                                <div class="col-12 col-md-4"><label class="form-label">Iskele / Kalkis Noktasi</label><input type="text" name="pier_name" class="form-control" value="{{ $package->pier_name }}" placeholder="Kabatas"></div>
                                <div class="col-12 col-md-4"><label class="form-label">Rozet Metni</label><input type="text" name="badge_text" class="form-control" value="{{ $package->badge_text }}" placeholder="En Cok Tercih Edilen"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Puan (0-5)</label><input type="number" step="0.1" name="rating" class="form-control" value="{{ $package->rating }}" placeholder="4.6"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Yorum Sayisi</label><input type="number" name="review_count" class="form-control" value="{{ $package->review_count }}" placeholder="2053"></div>

                                <div class="col-12"><label class="form-label">Kalkis Saatleri <small class="text-muted">(her satira bir saat, ornek: 19:30)</small></label><textarea name="departure_times_text" class="form-control" rows="2">{{ implode(PHP_EOL, $package->departure_times ?? []) }}</textarea></div>
                                <div class="col-12"><label class="form-label">Bulusma Noktasi</label><input type="text" name="meeting_point" class="form-control" value="{{ $package->meeting_point }}"></div>

                                <div class="col-12"><label class="form-label">TR Uzun Aciklama</label><textarea name="long_description_tr" class="form-control" rows="5">{{ $package->long_description_tr }}</textarea></div>
                                <div class="col-12"><label class="form-label">EN Uzun Aciklama</label><textarea name="long_description_en" class="form-control" rows="5">{{ $package->long_description_en }}</textarea></div>

                                <div class="col-12"><label class="form-label">Program / Timeline (JSON) <small class="text-muted">[{"time":"19:00","title":"...","desc":"..."}]</small></label><textarea name="timeline_tr_json" class="form-control font-monospace" rows="6" spellcheck="false">{{ $package->timeline_tr ? json_encode($package->timeline_tr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '' }}</textarea></div>

                                <div class="col-12"><label class="form-label">Iptal Politikasi (TR)</label><textarea name="cancellation_policy_tr" class="form-control" rows="3">{{ $package->cancellation_policy_tr }}</textarea></div>
                                <div class="col-12"><label class="form-label">Onemli Notlar (TR) <small class="text-muted">(her satira bir madde)</small></label><textarea name="important_notes_tr_text" class="form-control" rows="4">{{ implode(PHP_EOL, $package->important_notes_tr ?? []) }}</textarea></div>

                                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($package->is_active)><label class="form-check-label">Aktif</label></div></div>
                                <div class="col-12"><button class="btn btn-primary btn-sm">Kaydi Guncelle</button></div>
                            </form>
                        </td></tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Paket kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table></div></div>
            </div>

            <div class="card shell-card shadow-sm">
                <div class="card-header fw-bold">Ekstralar</div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Urun</th><th>Kod</th><th>TR Baslik</th><th>Varsayilan</th><th>Durum</th><th class="text-end">Islem</th></tr></thead>
                    <tbody>
                    @forelse($extras as $extra)
                        <tr>
                            <td>{{ $extra->product_type ?: 'Tum urunler' }}</td><td><span class="code-badge">{{ $extra->code }}</span></td><td>{{ $extra->title_tr }}</td><td>{{ $extra->default_included ? 'Evet' : 'Hayir' }}</td><td>{{ $extra->is_active ? 'Aktif' : 'Pasif' }}</td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#extra-edit-{{ $extra->id }}">Duzenle</button></td>
                        </tr>
                        <tr class="collapse" id="extra-edit-{{ $extra->id }}"><td colspan="6" class="edit-surface p-0">
                            <form method="POST" action="{{ route('superadmin.leisure.settings.extras.update', $extra) }}" class="row g-3 p-3">
                                @csrf @method('PATCH')
                                <div class="col-12 col-md-3"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="" @selected($extra->product_type===null)>Tum urunler</option><option value="dinner_cruise" @selected($extra->product_type==='dinner_cruise')>Dinner Cruise</option><option value="yacht" @selected($extra->product_type==='yacht')>Yacht Charter</option></select></div>
                                <div class="col-12 col-md-3"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" value="{{ $extra->category }}"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Kod</label><input type="text" name="code" class="form-control" value="{{ $extra->code }}"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="{{ $extra->sort_order }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Baslik</label><input type="text" name="title_tr" class="form-control" value="{{ $extra->title_tr }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Baslik</label><input type="text" name="title_en" class="form-control" value="{{ $extra->title_en }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Aciklama</label><input type="text" name="description_tr" class="form-control" value="{{ $extra->description_tr }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Aciklama</label><input type="text" name="description_en" class="form-control" value="{{ $extra->description_en }}"></div>
                                <div class="col-12 d-flex flex-wrap gap-3">
                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="default_included" value="1" @checked($extra->default_included)><label class="form-check-label">Varsayilan dahil</label></div>
                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($extra->is_active)><label class="form-check-label">Aktif</label></div>
                                </div>
                                <div class="col-12"><button class="btn btn-primary btn-sm">Kaydi Guncelle</button></div>
                            </form>
                        </td></tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Ekstra kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table></div></div>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const form = document.getElementById('leisurePackageCreateForm');
    const fillBtn = document.getElementById('fillBosphorusPackageBtn');
    if (!form || !fillBtn) return;

    const raw = form.getAttribute('data-bosphorus-sample') || '{}';
    let sample = {};
    try {
        sample = JSON.parse(raw);
    } catch (_) {
        sample = {};
    }

    const setValue = (name, value) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;
        field.value = value ?? '';
    };

    fillBtn.addEventListener('click', () => {
        setValue('product_type', sample.product_type || 'dinner_cruise');
        setValue('code', sample.code || 'bosphorus_dinner_cruise');
        setValue('level', sample.level || 'premium');
        setValue('sort_order', sample.sort_order || 15);
        setValue('name_tr', sample.name_tr || 'Bosphorus Dinner Cruise');
        setValue('name_en', sample.name_en || 'Bosphorus Dinner Cruise');
        setValue('summary_tr', sample.summary_tr || '');
        setValue('summary_en', sample.summary_en || '');
        setValue('hero_image_url', sample.hero_image_url || '');
        setValue('includes_tr_text', sample.includes_tr_text || '');
        setValue('includes_en_text', sample.includes_en_text || '');
        setValue('excludes_tr_text', sample.excludes_tr_text || '');
        setValue('excludes_en_text', sample.excludes_en_text || '');

        const activeCheckbox = form.querySelector('input[name="is_active"]');
        if (activeCheckbox) {
            activeCheckbox.checked = Boolean(sample.is_active);
        }
    });
})();
</script>
</body>
</html>

BLADE_SETTINGS;

$path = $base . '/resources/views/superadmin/leisure/settings.blade.php';
file_put_contents($path, $settings);
$log[] = 'OK: settings.blade.php (' . strlen($settings) . ' byte)';

// ── 4. ModulePaymentController.php — hedefli yama ────────────────────────
$ctrlPath = $base . '/app/Http/Controllers/Payments/ModulePaymentController.php';
if (file_exists($ctrlPath)) {
    $ctrl = file_get_contents($ctrlPath);
    $old  = <<<'CTRL_OLD'
        if ($module === 'leisure') {
            return $normalizedRole . '.yacht-charter.show';
        }
CTRL_OLD;
    $new  = <<<'CTRL_NEW'
        if ($module === 'leisure') {
            if (($data['product_type'] ?? '') === 'dinner_cruise') {
                return $normalizedRole === 'acente'
                    ? 'acente.dinner-cruise.booking-show'
                    : $normalizedRole . '.dinner-cruise.show';
            }

            return $normalizedRole . '.yacht-charter.show';
        }
CTRL_NEW;
    if (str_contains($ctrl, 'booking-show')) {
        $log[] = 'ZATEN YAPILMIS: ModulePaymentController.php (booking-show mevcut)';
    } elseif (str_contains($ctrl, "return \$normalizedRole . '.yacht-charter.show'")) {
        $patched = str_replace(
            "        if (\$module === 'leisure') {\n            return \$normalizedRole . '.yacht-charter.show';\n        }",
            "        if (\$module === 'leisure') {\n            if ((\$data['product_type'] ?? '') === 'dinner_cruise') {\n                return \$normalizedRole === 'acente'\n                    ? 'acente.dinner-cruise.booking-show'\n                    : \$normalizedRole . '.dinner-cruise.show';\n            }\n\n            return \$normalizedRole . '.yacht-charter.show';\n        }",
            $ctrl
        );
        file_put_contents($ctrlPath, $patched);
        $log[] = 'OK: ModulePaymentController.php yamalandı';
    } else {
        $log[] = 'UYARI: ModulePaymentController.php — beklenen pattern bulunamadı, atlandı';
    }
} else {
    $log[] = 'HATA: ModulePaymentController.php bulunamadı: ' . $ctrlPath;
}

// ── 5. Cache temizle ──────────────────────────────────────────────────────
$viewsDir = $base . '/storage/framework/views';
$deleted = 0;
foreach (glob($viewsDir . '/*.php') ?: [] as $f) { @unlink($f); $deleted++; }
$log[] = "Cache: $deleted compiled view silindi";

foreach ([
    $base . '/bootstrap/cache/config.php',
    $base . '/bootstrap/cache/routes-v7.php',
    $base . '/bootstrap/cache/routes.php',
    $base . '/bootstrap/cache/packages.php',
] as $f) {
    if (file_exists($f)) { @unlink($f); $log[] = "Cache: " . basename($f) . " silindi"; }
}

// ── Çıktı ─────────────────────────────────────────────────────────────────
header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $log) . "\n\nTAMAM! Bu dosyayi silin: /public/patch-dc.php\n";

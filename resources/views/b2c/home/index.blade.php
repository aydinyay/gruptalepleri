@extends('b2c.layouts.app')

@section('title', 'Grup Seyahat Deneyimleri')
@section('meta_description', 'Transfer, charter uçuş, dinner cruise, yat kiralama, tur paketleri. Türkiye\'nin en geniş grup seyahat kataloğu.')

@push('head_styles')
<style>
/* ── Hero ── */
.grt-hero {
    position: relative;
    min-height: 560px;
    display: flex;
    align-items: flex-start;
    padding-top: 4rem;
    overflow: hidden;
    /* Arka plan görseli varsa üstten CSS değişkeni ile gelir, yoksa gradient */
    background: var(--hero-bg, linear-gradient(135deg,#0c1f3d 0%,#1a3c6b 50%,#0e4d6b 100%));
    background-size: cover !important;
    background-position: center !important;
}
.grt-hero.has-image::after {
    content:'';
    position:absolute;inset:0;
    background: linear-gradient(to bottom, rgba(5,20,45,.72) 0%, rgba(5,20,45,.55) 60%, rgba(5,20,45,.80) 100%);
    z-index:0;
}
.grt-hero .hero-inner { position:relative;z-index:1;width:100%; }

/* Hero Featured Cards */
.grt-hero-card {
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 14px;
    overflow: hidden;
    display:flex;
    transition: transform .2s, box-shadow .2s;
    text-decoration:none;color:inherit;
    cursor:pointer;
}
.grt-hero-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.3); }
.grt-hero-card .hc-img {
    width: 90px; min-height: 90px;
    object-fit:cover;flex-shrink:0;
    background:linear-gradient(135deg,#1a4d8a,#0e6b8a);
    display:flex;align-items:center;justify-content:center;
    font-size:2rem;color:rgba(255,255,255,.4);
}
.grt-hero-card .hc-body { padding:.75rem 1rem;flex:1; }
.grt-hero-card .hc-title { font-weight:700;font-size:.88rem;color:#fff;line-height:1.3;margin-bottom:.35rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.grt-hero-card .hc-meta { font-size:.75rem;color:rgba(255,255,255,.65);margin-bottom:.3rem; }
.grt-hero-card .hc-rating { display:flex;align-items:center;gap:.3rem;margin-bottom:.3rem; }
.grt-hero-card .hc-stars { color:#f4a418;font-size:.8rem;letter-spacing:-.05em; }
.grt-hero-card .hc-score { font-weight:700;font-size:.82rem;color:#fff; }
.grt-hero-card .hc-rcount { font-size:.75rem;color:rgba(255,255,255,.55); }
.grt-hero-card .hc-price-label { font-size:.7rem;color:rgba(255,255,255,.6); }
.grt-hero-card .hc-price { font-weight:800;font-size:.95rem;color:#fff; }
.grt-search-box {
    background:#fff;
    border-radius:50px;
    padding:8px 8px 8px 24px;
    display:flex;
    align-items:center;
    gap:8px;
    box-shadow:0 8px 32px rgba(0,0,0,.25);
    max-width:680px;
    margin:0 auto;
}
.grt-search-box input {
    border:none;outline:none;
    flex:1;font-size:1.05rem;
    color:#333;background:transparent;
}
.grt-search-box .btn-search {
    background:var(--gr-accent);
    color:#fff;border:none;
    border-radius:40px;
    padding:.65rem 1.6rem;
    font-weight:700;
    font-size:.97rem;
    white-space:nowrap;
}
.grt-search-box .btn-search:hover { background:#c98a15; }

/* ── Kategori Pills ── */
.grt-cat-pills {
    display:flex;gap:.6rem;
    flex-wrap:nowrap;overflow-x:auto;
    padding-bottom:.5rem;
    scrollbar-width:none;
}
.grt-cat-pills::-webkit-scrollbar { display:none; }
.grt-cat-pill {
    display:inline-flex;align-items:center;gap:.45rem;
    background:#fff;
    border:1.5px solid var(--gr-border);
    border-radius:50px;
    padding:.5rem 1.1rem;
    font-size:.88rem;font-weight:600;
    color:var(--gr-text);
    text-decoration:none;
    white-space:nowrap;
    transition:all .2s;
    flex-shrink:0;
}
.grt-cat-pill:hover, .grt-cat-pill.active {
    border-color:var(--gr-primary);
    background:var(--gr-primary);
    color:#fff;
}
.grt-cat-pill i { font-size:1rem; }

/* ── Ürün Kartı ── */
.grt-product-card {
    border-radius:12px;
    overflow:hidden;
    background:#fff;
    border:1px solid var(--gr-border);
    transition:transform .2s,box-shadow .2s;
    text-decoration:none;color:inherit;
    display:block;height:100%;
}
.grt-product-card:hover {
    transform:translateY(-4px);
    box-shadow:0 12px 32px rgba(0,0,0,.12);
    color:inherit;
}
.grt-product-card .card-img {
    height:220px;width:100%;object-fit:cover;
    display:block;
    background:linear-gradient(135deg,#1a3c6b,#2a5298);
    position:relative;
}
.grt-product-card .card-img-placeholder {
    height:220px;
    display:flex;align-items:center;justify-content:center;
    font-size:3rem;color:rgba(255,255,255,.5);
}
.grt-product-card .img-overlay {
    position:absolute;bottom:0;left:0;right:0;
    padding:.5rem .75rem;
    background:linear-gradient(transparent,rgba(0,0,0,.5));
    display:flex;justify-content:space-between;align-items:flex-end;
}
.grt-product-card .card-body-grt { padding:1rem 1.1rem 1.2rem; }
.grt-product-card .card-cat-badge {
    display:inline-flex;align-items:center;gap:.3rem;
    font-size:.75rem;font-weight:600;
    color:var(--gr-muted);margin-bottom:.35rem;
}
.grt-product-card .card-title-grt {
    font-weight:700;font-size:.97rem;
    color:var(--gr-text);
    margin-bottom:.5rem;
    line-height:1.4;
    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.grt-product-card .card-price {
    font-size:1.1rem;font-weight:800;
    color:var(--gr-primary);
}
.grt-product-card .card-price-label {
    font-size:.75rem;color:var(--gr-muted);font-weight:400;
}
.grt-product-card .card-cta {
    font-size:.82rem;font-weight:600;
    color:var(--gr-accent);
}

/* ── Destinasyon Kartı ── */
.grt-dest-card {
    border-radius:14px;overflow:hidden;
    position:relative;height:180px;
    display:block;text-decoration:none;
    transition:transform .2s,box-shadow .2s;
}
.grt-dest-card:hover { transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.2); }
.grt-dest-card .dest-bg {
    width:100%;height:100%;object-fit:cover;
    background:linear-gradient(135deg,#1a3c6b 0%,#0e4d6b 100%);
    display:flex;align-items:center;justify-content:center;
    font-size:3rem;color:rgba(255,255,255,.3);
}
.grt-dest-card .dest-overlay {
    position:absolute;inset:0;
    background:linear-gradient(transparent 30%,rgba(0,0,0,.65) 100%);
    display:flex;flex-direction:column;justify-content:flex-end;
    padding:1rem 1.1rem;
}
.grt-dest-card .dest-city { color:#fff;font-weight:700;font-size:1.05rem; }
.grt-dest-card .dest-count { color:rgba(255,255,255,.8);font-size:.8rem; }

/* ── Destinasyon renk paleti ── */
.dest-color-0 { background: linear-gradient(135deg,#c0392b,#8e44ad); }
.dest-color-1 { background: linear-gradient(135deg,#2980b9,#6dd5fa); }
.dest-color-2 { background: linear-gradient(135deg,#f39c12,#e74c3c); }
.dest-color-3 { background: linear-gradient(135deg,#27ae60,#2c3e50); }
.dest-color-4 { background: linear-gradient(135deg,#8e44ad,#3498db); }
.dest-color-5 { background: linear-gradient(135deg,#16a085,#1a3c6b); }

/* ── Blog Kartı ── */
.grt-blog-card {
    border-radius:12px;overflow:hidden;
    background:#fff;
    border:1px solid var(--gr-border);
    transition:box-shadow .2s,transform .2s;
    text-decoration:none;color:inherit;
    display:block;height:100%;
}
.grt-blog-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.1);transform:translateY(-2px);color:inherit; }
.grt-blog-card .blog-img-placeholder {
    height:160px;
    display:flex;align-items:center;justify-content:center;
    font-size:2.5rem;color:rgba(255,255,255,.4);
}
.grt-blog-card .blog-body { padding:1.1rem; }
</style>
@endpush

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════
     HERO — Arama Çubuğu + Featured Cards
     Hero arka planı için: public/images/b2c-hero.jpg dosyasını yükleyin
═══════════════════════════════════════════════════════════════════════ --}}
@php
$heroImage = public_path('images/b2c-hero.jpg');
$hasHeroImage = file_exists($heroImage);
$heroStyle = $hasHeroImage
    ? 'style="--hero-bg: url(\'' . asset('images/b2c-hero.jpg') . '\')"'
    : '';
@endphp
<section class="grt-hero py-0 {{ $hasHeroImage ? 'has-image' : '' }}" {!! $heroStyle !!}>
    <div class="container hero-inner pb-4">
        <div class="text-center mb-4 pt-2">
            <h1 class="text-white fw-800 mb-3" style="font-size:clamp(1.7rem,4vw,2.6rem);line-height:1.2;">
                Keşfedin, karşılaştırın,<br>
                <span style="color:var(--gr-accent);">rezervasyon yapın</span>
            </h1>
        </div>

        {{-- Arama Kutusu --}}
        <form action="{{ route('b2c.catalog.index') }}" method="GET">
            <div class="grt-search-box">
                <i class="bi bi-search" style="color:var(--gr-muted);font-size:1.1rem;flex-shrink:0;"></i>
                <input type="text" name="q" placeholder="Hizmet, destinasyon veya aktivite ara..."
                       value="{{ request('q') }}" autocomplete="off">
                <button type="submit" class="btn-search">
                    <i class="bi bi-search me-1 d-none d-md-inline"></i>Ara
                </button>
            </div>
        </form>

        {{-- Popüler aramalar --}}
        <div class="text-center mt-3 mb-4">
            <span style="color:rgba(255,255,255,.55);font-size:.85rem;">Popüler:</span>
            @foreach(['İstanbul Dinner Cruise','Kapadokya Turu','Özel Jet','Yat Kiralama','Havalimanı Transferi'] as $tag)
            <a href="{{ route('b2c.catalog.index', ['q' => $tag]) }}"
               class="badge rounded-pill ms-1 text-decoration-none"
               style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.85);font-size:.8rem;font-weight:500;padding:.35rem .75rem;">
                {{ $tag }}
            </a>
            @endforeach
        </div>

        {{-- Hero Featured Cards --}}
        @php
        $heroCards = $featuredItems->isNotEmpty()
            ? $featuredItems->take(2)
            : collect([
                (object)[
                    'title'           => 'İstanbul: Türk Gecesi Gösterisi ile Boğazda Akşam Yemeği Gezisi',
                    'product_type'    => 'leisure',
                    'duration_hours'  => 3,
                    'cover_image'     => null,
                    'rating_avg'      => 4.6,
                    'review_count'    => 2040,
                    'base_price'      => 1284,
                    'currency'        => 'TRY',
                    'pricing_type'    => 'fixed',
                    'slug'            => 'dinner-cruise',
                    'destination_city'=> 'İstanbul',
                ],
                (object)[
                    'title'           => 'İstanbul: Üst Açık Otobüsle Şehir Turu & Panoramik Boğaz Gezisi',
                    'product_type'    => 'tour',
                    'duration_hours'  => 4,
                    'cover_image'     => null,
                    'rating_avg'      => 4.3,
                    'review_count'    => 890,
                    'base_price'      => 650,
                    'currency'        => 'TRY',
                    'pricing_type'    => 'fixed',
                    'slug'            => 'istanbul-sehir-turu',
                    'destination_city'=> 'İstanbul',
                ],
            ]);
        $typeIcons = ['transfer'=>'bi-car-front-fill','charter'=>'bi-airplane-fill','leisure'=>'bi-water','tour'=>'bi-map-fill','hotel'=>'bi-building','visa'=>'bi-passport','other'=>'bi-grid'];
        @endphp

        <div style="max-width:780px;margin:0 auto;">
            <div style="font-size:.8rem;color:rgba(255,255,255,.6);margin-bottom:.6rem;font-weight:500;">
                <i class="bi bi-clock-history me-1"></i>Öne Çıkan Deneyimler
            </div>
            <div class="row g-2">
                @foreach($heroCards as $hc)
                @php
                $hcIcon = $typeIcons[$hc->product_type] ?? 'bi-grid';
                $hcSlug = $hc->slug ?? 'dinner-cruise';
                $hcRating = $hc->rating_avg ?? 0;
                $hcReviews = $hc->review_count ?? 0;
                $hcPrice = $hc->base_price ?? 0;
                $hcHours = $hc->duration_hours ?? null;
                $hcCity = $hc->destination_city ?? '';
                @endphp
                <div class="col-md-6">
                    <a href="{{ route('b2c.product.show', $hcSlug) }}" class="grt-hero-card">
                        @if(isset($hc->cover_image) && $hc->cover_image)
                            <img src="{{ asset('storage/'.$hc->cover_image) }}" class="hc-img" alt="{{ $hc->title }}">
                        @else
                            <div class="hc-img"><i class="bi {{ $hcIcon }}"></i></div>
                        @endif
                        <div class="hc-body">
                            <div class="hc-title">{{ $hc->title }}</div>
                            <div class="hc-meta">
                                Sıra beklemeden giriş
                                @if($hcHours) · {{ $hcHours }} saat @endif
                                @if($hcCity) · {{ $hcCity }} @endif
                            </div>
                            @if($hcRating > 0)
                            <div class="hc-rating">
                                <span class="hc-stars">
                                    @for($s=1;$s<=5;$s++)
                                        @if($s <= floor($hcRating))★@elseif($s - $hcRating < 1)½@else☆@endif
                                    @endfor
                                </span>
                                <span class="hc-score">{{ number_format($hcRating,1) }}</span>
                                @if($hcReviews > 0)
                                <span class="hc-rcount">({{ number_format($hcReviews, 0, ',', '.') }})</span>
                                @endif
                            </div>
                            @endif
                            @if($hcPrice > 0)
                            <div class="hc-price-label">Başlangıç fiyatı</div>
                            <div class="hc-price">{{ number_format($hcPrice, 0, ',', '.') }} {{ $hc->currency ?? 'TRY' }}</div>
                            @endif
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     KATEGORİ PILLS
═══════════════════════════════════════════════════════════════════════ --}}
<section class="py-0" style="border-bottom:1px solid var(--gr-border);background:#fff;">
    <div class="container">
        <div class="grt-cat-pills py-3">
            @php
            $staticCats = [
                ['bi-car-front-fill','transfer','Havalimanı Transferi'],
                ['bi-airplane-fill','ozel-jet','Özel Jet & Charter'],
                ['bi-helicopter','helikopter','Helikopter'],
                ['bi-water','dinner-cruise','Dinner Cruise'],
                ['bi-tsunami','yat-kiralama','Yat Kiralama'],
                ['bi-map-fill','yurt-ici-turlar','Yurt İçi Turlar'],
                ['bi-globe-americas','yurt-disi-turlar','Yurt Dışı Turlar'],
                ['bi-passport','vize','Vize Hizmetleri'],
            ];
            @endphp
            @if($categories->isNotEmpty())
                @foreach($categories as $cat)
                <a href="{{ route('b2c.catalog.category', $cat->slug) }}" class="grt-cat-pill">
                    <i class="bi {{ $cat->icon ?? 'bi-grid' }}"></i>
                    {{ $cat->name }}
                </a>
                @endforeach
            @else
                @foreach($staticCats as $cat)
                <a href="{{ route('b2c.catalog.category', $cat[1]) }}" class="grt-cat-pill">
                    <i class="bi {{ $cat[0] }}"></i>
                    {{ $cat[2] }}
                </a>
                @endforeach
            @endif
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     ÖNE ÇIKAN ÜRÜNLER
═══════════════════════════════════════════════════════════════════════ --}}
<section style="background:#fff;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="gr-section-title">Öne Çıkan Deneyimler</h2>
                <p class="gr-section-subtitle mb-0">En çok tercih edilen hizmetlerimiz</p>
            </div>
            <a href="{{ route('b2c.catalog.index') }}" class="text-decoration-none d-none d-md-flex align-items-center gap-1"
               style="color:var(--gr-primary);font-weight:600;font-size:.9rem;">
                Tümünü Gör <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        @if($featuredItems->isNotEmpty())
        <div class="row g-3">
            @foreach($featuredItems as $item)
            <div class="col-sm-6 col-lg-3">
                @include('b2c.home._product-card', ['item' => $item])
            </div>
            @endforeach
        </div>
        @else
        {{-- Placeholder kartlar — DB'ye ürün eklenince bunlar kaybolur --}}
        @php
        $sampleCards = [
            ['icon'=>'bi-water',       'cat'=>'Dinner Cruise',     'city'=>'İstanbul', 'slug'=>'dinner-cruise',   'title'=>'İstanbul: Türk Gecesi Gösterisi ile Boğazda Akşam Yemeği', 'price'=>1284, 'hours'=>3,  'rating'=>4.6, 'reviews'=>2040, 'quote'=>false, 'color'=>0],
            ['icon'=>'bi-airplane',    'cat'=>'Charter & Uçuş',   'city'=>'İstanbul', 'slug'=>'ozel-jet',        'title'=>'İstanbul - Antalya Özel Jet Kiralama',                      'price'=>0,    'hours'=>2,  'rating'=>4.8, 'reviews'=>137,  'quote'=>true,  'color'=>1],
            ['icon'=>'bi-tsunami',     'cat'=>'Yat Kiralama',      'city'=>'Bodrum',   'slug'=>'yat-kiralama',    'title'=>'Bodrum: Günlük Özel Yat Turu & Mavi Yolculuk',              'price'=>850,  'hours'=>8,  'rating'=>4.9, 'reviews'=>523,  'quote'=>false, 'color'=>2],
            ['icon'=>'bi-map-fill',    'cat'=>'Tur',               'city'=>'Nevşehir', 'slug'=>'yurt-ici-turlar', 'title'=>'Kapadokya: Gün Doğarken Sıcak Hava Balonu Uçuşu',          'price'=>5351, 'hours'=>3,  'rating'=>5.0, 'reviews'=>5045, 'quote'=>false, 'color'=>3],
        ];
        @endphp
        <div class="row g-3">
            @foreach($sampleCards as $p)
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('b2c.catalog.category', $p['slug']) }}" class="grt-product-card">
                    <div class="position-relative">
                        <div class="card-img-placeholder dest-color-{{ $p['color'] }}">
                            <i class="bi {{ $p['icon'] }}"></i>
                        </div>
                        <div class="img-overlay">
                            <span style="color:rgba(255,255,255,.85);font-size:.78rem;">
                                <i class="bi bi-geo-alt-fill me-1"></i>{{ $p['city'] }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body-grt">
                        <div class="card-cat-badge">
                            <i class="bi {{ $p['icon'] }}"></i>{{ $p['cat'] }}
                            &nbsp;·&nbsp;{{ $p['hours'] }} saat
                        </div>
                        <div class="card-title-grt">{{ $p['title'] }}</div>

                        {{-- Yıldız puanlaması --}}
                        <div class="d-flex align-items-center gap-1 mb-2" style="font-size:.82rem;">
                            <span style="color:#f4a418;">
                                @for($s=1;$s<=5;$s++){{ $s <= round($p['rating']) ? '★' : '☆' }}@endfor
                            </span>
                            <span style="font-weight:700;color:#2d3748;">{{ $p['rating'] }}</span>
                            <span style="color:#718096;">({{ number_format($p['reviews'],0,',','.') }})</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-end">
                            @if($p['quote'])
                            <div>
                                <div class="card-price-label">Fiyat için</div>
                                <div class="card-cta">Teklif Alın <i class="bi bi-arrow-right"></i></div>
                            </div>
                            @else
                            <div>
                                <div class="card-price-label">Başlangıç fiyatı</div>
                                <div class="card-price">{{ number_format($p['price'],0,',','.') }} TRY</div>
                            </div>
                            @endif
                            <i class="bi bi-heart" style="color:#718096;font-size:1.1rem;"></i>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-4">
            <a href="{{ route('b2c.catalog.index') }}" class="btn btn-gr-outline px-4">
                Tüm Hizmetleri Keşfet <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        @endif
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     DESTİNASYONLAR
═══════════════════════════════════════════════════════════════════════ --}}
<section style="background:var(--gr-light);">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="gr-section-title">Popüler Destinasyonlar</h2>
                <p class="gr-section-subtitle mb-0">Şehir bazlı hizmetleri keşfedin</p>
            </div>
        </div>
        <div class="row g-3">
            @php
            $destColors = ['dest-color-0','dest-color-1','dest-color-2','dest-color-3','dest-color-4','dest-color-5'];
            $destIcons  = ['bi-buildings','bi-water','bi-sun','bi-tree','bi-airplane','bi-compass'];
            $staticDests = [
                ['İstanbul',0,'280+ hizmet'],
                ['Bodrum',1,'120+ hizmet'],
                ['Antalya',2,'95+ hizmet'],
                ['Kapadokya',3,'60+ hizmet'],
                ['Ege',4,'45+ hizmet'],
                ['Karadeniz',5,'38+ hizmet'],
            ];
            @endphp
            @if($destinations->isNotEmpty())
                @foreach($destinations->take(6) as $i => $dest)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('b2c.destination', \Illuminate\Support\Str::slug($dest->destination_city)) }}"
                       class="grt-dest-card">
                        <div class="dest-bg {{ $destColors[$i % 6] }}">
                            <i class="bi {{ $destIcons[$i % 6] }}"></i>
                        </div>
                        <div class="dest-overlay">
                            <div class="dest-city">{{ $dest->destination_city }}</div>
                            <div class="dest-count">{{ $dest->item_count }} hizmet</div>
                        </div>
                    </a>
                </div>
                @endforeach
            @else
                @foreach($staticDests as $dest)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('b2c.destination', \Illuminate\Support\Str::slug($dest[0])) }}"
                       class="grt-dest-card">
                        <div class="dest-bg {{ $destColors[$dest[1]] }}">
                            <i class="bi {{ $destIcons[$dest[1]] }}"></i>
                        </div>
                        <div class="dest-overlay">
                            <div class="dest-city">{{ $dest[0] }}</div>
                            <div class="dest-count">{{ $dest[2] }}</div>
                        </div>
                    </a>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     TÜM HİZMETLER (katalogdan ürünler varsa)
═══════════════════════════════════════════════════════════════════════ --}}
@if($latestItems->isNotEmpty())
<section style="background:#fff;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="gr-section-title">Unutulmaz Seyahat Deneyimleri</h2>
                <p class="gr-section-subtitle mb-0">Tüm kategorilerde seçkin hizmetler</p>
            </div>
            <a href="{{ route('b2c.catalog.index') }}" class="text-decoration-none d-none d-md-flex align-items-center gap-1"
               style="color:var(--gr-primary);font-weight:600;font-size:.9rem;">
                Tümünü Gör <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="row g-3">
            @foreach($latestItems as $item)
            <div class="col-sm-6 col-md-4 col-lg-3">
                @include('b2c.home._product-card', ['item' => $item])
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════
     HIZLI TEKLİF
═══════════════════════════════════════════════════════════════════════ --}}
<section id="hizli-teklif" style="background:var(--gr-light);border-top:1px solid var(--gr-border);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 text-center">
                <i class="bi bi-lightning-charge-fill fs-2 mb-3" style="color:var(--gr-accent);"></i>
                <h2 class="gr-section-title">Hızlı Teklif Alın</h2>
                <p class="gr-section-subtitle">İhtiyacınızı bırakın, uzmanlarımız sizi arasın.</p>

                <form action="{{ route('b2c.quick-lead.store') }}" method="POST" class="text-start needs-validation" novalidate>
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="name" class="form-control form-control-lg"
                                   placeholder="Adınız Soyadınız" required>
                        </div>
                        <div class="col-md-6">
                            <input type="tel" name="phone" class="form-control form-control-lg"
                                   placeholder="Telefon Numaranız" required>
                        </div>
                        <div class="col-12">
                            <select name="service_type" class="form-select form-select-lg">
                                <option value="">Hizmet Türü Seçin</option>
                                <option value="transfer">Havalimanı Transferi</option>
                                <option value="charter">Charter / Özel Jet</option>
                                <option value="dinner_cruise">Dinner Cruise</option>
                                <option value="yat">Yat Kiralama</option>
                                <option value="tur">Tur Paketi</option>
                                <option value="diger">Diğer</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Kısa not (isteğe bağlı)" style="resize:none;"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-gr-accent btn-lg w-100 rounded-pill">
                                <i class="bi bi-send me-2"></i>Teklif Talebimi Gönder
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════
     BLOG
═══════════════════════════════════════════════════════════════════════ --}}
@if($blogPosts->isNotEmpty())
<section style="background:#fff;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="gr-section-title">Seyahat Rehberi & Blog</h2>
                <p class="gr-section-subtitle mb-0">İpuçları, destinasyon rehberleri ve haberler</p>
            </div>
            <a href="{{ route('b2c.blog.index') }}" class="text-decoration-none d-none d-md-flex align-items-center gap-1"
               style="color:var(--gr-primary);font-weight:600;font-size:.9rem;">
                Tüm Yazılar <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <div class="row g-3">
            @foreach($blogPosts as $i => $post)
            <div class="col-md-4">
                <a href="{{ route('b2c.blog.show', $post->slug) }}" class="grt-blog-card">
                    <div class="blog-img-placeholder dest-color-{{ $i % 6 }}">
                        <i class="bi bi-newspaper"></i>
                    </div>
                    <div class="blog-body">
                        @if($post->kategori)
                        <span style="font-size:.75rem;font-weight:600;color:var(--gr-accent);text-transform:uppercase;letter-spacing:.05em;">
                            {{ $post->kategori->ad }}
                        </span>
                        @endif
                        <h5 class="fw-700 mt-1 mb-2" style="font-size:.97rem;line-height:1.4;color:var(--gr-text);">
                            {{ Str::limit($post->baslik, 70) }}
                        </h5>
                        @if($post->ozet ?? null)
                        <p style="font-size:.85rem;color:var(--gr-muted);margin-bottom:.75rem;">
                            {{ Str::limit($post->ozet, 90) }}
                        </p>
                        @endif
                        <span style="font-size:.82rem;font-weight:600;color:var(--gr-primary);">
                            Devamını Oku <i class="bi bi-arrow-right"></i>
                        </span>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════
     TEDARİKÇİ OL
═══════════════════════════════════════════════════════════════════════ --}}
<section class="py-small" style="background:var(--gr-primary);">
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <h4 class="text-white fw-700 mb-1">Ürününüzü veya Hizmetinizi Platformda Listeleyin</h4>
                <p class="mb-0" style="color:rgba(255,255,255,.75);font-size:.94rem;">
                    Tur, transfer, konaklama veya özel hizmet sunuyorsanız platformumuza katılın.
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('b2c.supplier-apply.show') }}" class="btn btn-gr-accent btn-lg rounded-pill">
                    <i class="bi bi-building me-2"></i>Tedarikçi Olun
                </a>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
(function() {
    const form = document.querySelector('.needs-validation');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
    });
})();
</script>
@endpush

@extends('b2c.layouts.app')

@section('title', 'Grup Rezervasyonları — Transfer, Tur, Charter, Dinner Cruise')
@section('meta_description', 'Türkiye\'nin en büyük grup seyahat platformu. İstanbul Boğaz turu, havalimanı transferi, özel jet, yat kiralama ve çok daha fazlası.')

@push('head_styles')
<style>
/* ═══════════════════════════════════════════════════════
   HERO — GYG stili tam genişlik
═══════════════════════════════════════════════════════ */
.gyg-hero {
    position: relative;
    min-height: 520px;
    display: flex;
    align-items: center;
    overflow: hidden;
    background: {{ $heroBgColor }};
}
.gyg-hero-bg {
    position: absolute;
    inset: 0;
    @if($heroBgImage)
    background-image: url('{{ $heroBgImage }}');
    @else
    background-image: url('{{ asset("images/b2c-hero.jpg") }}');
    @endif
    background-size: cover;
    background-position: center;
    opacity: .35;
}
.gyg-hero-content {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    padding: 64px 24px;
    text-align: center;
}
.gyg-hero h1 {
    font-size: 2.8rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
    margin-bottom: .6rem;
    letter-spacing: -.02em;
}
.gyg-hero .hero-sub {
    font-size: 1.15rem;
    color: rgba(255,255,255,.8);
    margin-bottom: 2rem;
}

/* Arama kutusu */
.gyg-search-box {
    background: #fff;
    border-radius: 50px;
    display: flex;
    align-items: center;
    max-width: 680px;
    margin: 0 auto 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,.25);
    padding: 6px 6px 6px 24px;
    gap: 8px;
}
.gyg-search-box input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 1rem;
    color: #1a202c;
    background: transparent;
    min-width: 0;
}
.gyg-search-box input::placeholder { color: #a0aec0; }
.gyg-search-btn {
    background: #FF5533;
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 11px 24px;
    font-weight: 700;
    font-size: .95rem;
    cursor: pointer;
    white-space: nowrap;
    transition: background .15s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.gyg-search-btn:hover { background: #e04420; }

/* Popüler aramalar */
.gyg-hero-tags {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: .88rem;
    color: rgba(255,255,255,.75);
}
.gyg-hero-tags span { font-weight: 500; }
.gyg-hero-tag {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    color: #fff;
    padding: 4px 14px;
    border-radius: 50px;
    text-decoration: none;
    font-size: .85rem;
    transition: background .15s;
    backdrop-filter: blur(4px);
}
.gyg-hero-tag:hover { background: rgba(255,255,255,.3); color: #fff; }

/* Arama öneri dropdown */
.gyg-search-wrap {
    position: relative;
    max-width: 680px;
    margin: 0 auto 2rem;
}
.gyg-search-wrap .gyg-search-box {
    margin: 0;
}
.gyg-suggest-box {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(0,0,0,.18);
    border: 1px solid #e5e5e5;
    z-index: 500;
    overflow: hidden;
}
.gyg-suggest-box.visible { display: block; }
.gyg-suggest-section-title {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #718096;
    padding: 14px 16px 6px;
}
.gyg-suggest-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    cursor: pointer;
    text-decoration: none;
    color: #1a202c;
    transition: background .12s;
}
.gyg-suggest-item:hover, .gyg-suggest-item.focused { background: #f8f9fc; }
.gyg-suggest-item-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #edf2f7;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1rem;
    color: #4a5568;
}
.gyg-suggest-item-icon.city { background: #ebf8ff; color: #3182ce; }
.gyg-suggest-item-icon.product { background: #faf5ff; color: #805ad5; }
.gyg-suggest-item-text { flex: 1; min-width: 0; }
.gyg-suggest-item-label { font-size: .92rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.gyg-suggest-item-sub { font-size: .8rem; color: #718096; }
.gyg-suggest-item-price { font-size: .8rem; font-weight: 600; color: #1a3c6b; white-space: nowrap; }
.gyg-suggest-divider { height: 1px; background: #f0f0f0; margin: 4px 0; }

/* Hero öne çıkan kartlar */
.gyg-hero-cards {
    display: flex;
    gap: 14px;
    justify-content: center;
    margin-top: 2.5rem;
    flex-wrap: wrap;
}
.gyg-hero-card {
    background: #fff;
    border-radius: 12px;
    width: 500px;
    max-width: calc(50% - 8px);
    text-align: left;
    text-decoration: none;
    transition: transform .2s, box-shadow .2s;
    color: #1a202c;
    display: flex;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.18);
}
.gyg-hero-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.25); color: #1a202c; }
.gyg-hero-card .hc-img {
    width: 150px;
    min-width: 150px;
    background: #e8eef5 center/cover no-repeat;
    flex-shrink: 0;
}
.gyg-hero-card .hc-body { padding: 14px 16px; flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; }
.gyg-hero-card .hc-cat { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #718096; margin-bottom: 5px; }
.gyg-hero-card .hc-title { font-weight: 700; font-size: .9rem; line-height: 1.4; margin-bottom: 6px; color: #1a202c; }
.gyg-hero-card .hc-meta { font-size: .78rem; color: #718096; margin-bottom: 8px; }
.gyg-hero-card .hc-stars { color: #f4a418; font-size: .82rem; }
.gyg-hero-card .hc-price { font-weight: 800; font-size: 1.05rem; color: #1a202c; }
.gyg-hero-card .hc-price-label { font-size: .7rem; color: #718096; }
@@media (max-width: 680px) {
    .gyg-hero-card { max-width: 100%; width: 100%; }
    .gyg-hero-card .hc-img { width: 110px; min-width: 110px; }
}

/* ═══════════════════════════════════════════════════════
   KATEGORİ PILLS — sticky
═══════════════════════════════════════════════════════ */
.gyg-pills-wrap {
    border-bottom: 1px solid #e5e5e5;
    background: #fff;
    position: sticky;
    top: 64px;
    z-index: 100;
}
.gyg-pills {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    gap: 4px;
    overflow-x: auto;
    scrollbar-width: none;
}
.gyg-pills::-webkit-scrollbar { display: none; }
.gyg-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 14px 16px;
    white-space: nowrap;
    font-size: .88rem;
    font-weight: 500;
    color: #4a5568;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: color .15s, border-color .15s;
    flex-shrink: 0;
}
.gyg-pill:hover { color: #1a3c6b; border-bottom-color: #1a3c6b; }
.gyg-pill.active { color: #1a3c6b; border-bottom-color: #1a3c6b; font-weight: 600; }
.gyg-pill i { font-size: 1rem; }

/* ═══════════════════════════════════════════════════════
   ÜRÜN GRID
═══════════════════════════════════════════════════════ */
.gyg-products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}
@@media (max-width: 1200px) { .gyg-products-grid { grid-template-columns: repeat(3, 1fr); } }
@@media (max-width: 768px)  { .gyg-products-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; } }
@@media (max-width: 480px)  { .gyg-products-grid { grid-template-columns: 1fr; } }

.gyg-pcard {
    border-radius: 12px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform .2s, box-shadow .2s;
    background: #fff;
    border: 1px solid #f0f0f0;
}
.gyg-pcard:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.1); color: inherit; }
.gyg-pcard-img {
    position: relative;
    height: 220px;
    overflow: hidden;
}
.gyg-pcard-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
.gyg-pcard:hover .gyg-pcard-img img { transform: scale(1.05); }
.gyg-pcard-img .img-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 3rem; color: rgba(255,255,255,.7);
}
.gyg-pcard-heart {
    position: absolute; top: 12px; right: 12px;
    width: 36px; height: 36px;
    background: rgba(255,255,255,.9); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #718096; font-size: .95rem; cursor: pointer;
    transition: color .15s; z-index: 2;
}
.gyg-pcard-heart:hover { color: #e53e3e; }
.gyg-pcard-badge {
    position: absolute; bottom: 12px; left: 12px;
    background: rgba(0,0,0,.55); color: #fff;
    font-size: .72rem; font-weight: 600;
    padding: 3px 10px; border-radius: 50px;
    backdrop-filter: blur(4px);
}
.gyg-pcard-body { padding: 14px; }
.gyg-pcard-cat { font-size: .75rem; color: #718096; margin-bottom: 4px; }
.gyg-pcard-title { font-size: .93rem; font-weight: 600; line-height: 1.4; color: #1a202c; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.gyg-pcard-stars { color: #f4a418; font-size: .85rem; letter-spacing: -.05em; }
.gyg-pcard-rating { font-weight: 700; font-size: .88rem; color: #1a202c; }
.gyg-pcard-reviews { font-size: .82rem; color: #718096; }
.gyg-pcard-price-label { font-size: .75rem; color: #718096; margin-top: 8px; }
.gyg-pcard-price { font-size: 1.05rem; font-weight: 700; color: #1a202c; }

/* ═══════════════════════════════════════════════════════
   DESTİNASYON KARTLARI
═══════════════════════════════════════════════════════ */
.gyg-dest-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
}
@@media (max-width: 1200px) { .gyg-dest-grid { grid-template-columns: repeat(4, 1fr); } }
@@media (max-width: 768px)  { .gyg-dest-grid { grid-template-columns: repeat(2, 1fr); } }

.gyg-dest-card {
    border-radius: 12px; overflow: hidden;
    position: relative; aspect-ratio: 3/4;
    text-decoration: none; display: block;
    transition: transform .2s;
}
.gyg-dest-card:hover { transform: translateY(-4px); }
.gyg-dest-card .dest-bg { width: 100%; height: 100%; display: flex; align-items: flex-end; padding: 16px; }
.gyg-dest-card .dest-icon {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -70%);
    font-size: 2.5rem; color: rgba(255,255,255,.8);
}
.gyg-dest-card .dest-name { color: #fff; font-weight: 700; font-size: 1rem; position: relative; z-index: 2; }
.gyg-dest-card .dest-count { color: rgba(255,255,255,.8); font-size: .82rem; position: relative; z-index: 2; }
.gyg-dest-card::after {
    content: ''; position: absolute; bottom: 0; left: 0; right: 0;
    height: 60%; background: linear-gradient(to top, rgba(0,0,0,.6) 0%, transparent 100%);
}

/* ═══════════════════════════════════════════════════════
   SECTION ÜSTÜ
═══════════════════════════════════════════════════════ */
.gyg-section-head { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 1.25rem; }
.gyg-section-head h2 { font-size: 1.5rem; font-weight: 700; color: #1a202c; margin: 0; }
.gyg-section-head p  { color: #718096; font-size: .93rem; margin: .25rem 0 0; }
.gyg-see-all { font-size: .9rem; font-weight: 600; color: #1a3c6b; text-decoration: none; white-space: nowrap; }
.gyg-see-all:hover { text-decoration: underline; }

/* Güven şeridi */
.gyg-trust-strip { background: #f8f9fc; border-top: 1px solid #e5e5e5; border-bottom: 1px solid #e5e5e5; padding: 2.5rem 0; }
.gyg-trust-item { display: flex; align-items: flex-start; gap: 14px; }
.gyg-trust-item i { font-size: 1.8rem; color: #1a3c6b; flex-shrink: 0; margin-top: 3px; }
.gyg-trust-item strong { font-size: .95rem; display: block; margin-bottom: 3px; color: #1a202c; }
.gyg-trust-item span { font-size: .85rem; color: #718096; line-height: 1.5; }

/* Blog */
.gyg-blog-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 24px; }
@@media (max-width: 768px) { .gyg-blog-grid { grid-template-columns: 1fr; } }
.gyg-blog-card { text-decoration: none; color: inherit; display: block; }
.gyg-blog-card:hover .blog-title { color: #1a3c6b; text-decoration: underline; }
.gyg-blog-card .blog-thumb {
    height: 200px; border-radius: 12px; overflow: hidden;
    background: linear-gradient(135deg, #1a3c6b, #2a5298);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.5); font-size: 2.5rem; margin-bottom: 12px;
}
.gyg-blog-card .blog-cat { font-size: .75rem; font-weight: 600; text-transform: uppercase; color: #FF5533; letter-spacing: .06em; margin-bottom: 6px; }
.gyg-blog-card .blog-title { font-weight: 700; font-size: .97rem; color: #1a202c; line-height: 1.4; margin-bottom: 6px; }
.gyg-blog-card .blog-date { font-size: .8rem; color: #718096; }

/* Tedarikçi banner */
.gyg-supplier-banner {
    background: linear-gradient(135deg, #0f2444 0%, #1a3c6b 100%);
    border-radius: 16px; padding: 3rem;
    display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap;
}
.gyg-supplier-banner h3 { color: #fff; font-size: 1.4rem; font-weight: 700; margin-bottom: .4rem; }
.gyg-supplier-banner p  { color: rgba(255,255,255,.75); margin: 0; font-size: .95rem; }
.gyg-supplier-btn {
    background: #FF5533; color: #fff; font-weight: 700;
    padding: 12px 28px; border-radius: 8px; text-decoration: none;
    white-space: nowrap; transition: background .15s; flex-shrink: 0;
    border: none; cursor: pointer; font-size: 1rem;
}
.gyg-supplier-btn:hover { background: #e04420; color: #fff; }
</style>
@endpush

@section('content')

{{-- ════════════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════════════ --}}
<section class="gyg-hero" style="padding:0;">
    <div class="gyg-hero-bg"></div>
    <div class="gyg-hero-content">
        <p style="color:rgba(255,255,255,.65);font-size:.82rem;text-transform:uppercase;letter-spacing:.18em;font-weight:600;margin-bottom:.6rem;">Türkiye'nin Lider Grup Seyahat Platformu</p>
        <h1>Keşfedin, karşılaştırın,<br><span style="color:var(--gr-accent,#f4a418);">rezervasyon yapın.</span></h1>
        <p class="hero-sub">Transfer'den charter'a, dinner cruise'dan yat kiralama'ya — hepsi burada.</p>

        <div class="gyg-search-wrap">
            <form action="{{ route('b2c.catalog.index') }}" method="GET" id="heroSearchForm">
                <div class="gyg-search-box">
                    <i class="bi bi-search" style="color:#a0aec0;font-size:1.1rem;flex-shrink:0;"></i>
                    <input type="text" id="heroSearchInput" name="q" placeholder="Aktivite, tur veya destinasyon ara..." autocomplete="off">
                    <button type="submit" class="gyg-search-btn">
                        <i class="bi bi-search"></i> Ara
                    </button>
                </div>
            </form>
            <div class="gyg-suggest-box" id="heroSuggestBox"></div>
        </div>

        <div class="gyg-hero-tags">
            <span>Popüler:</span>
            <a href="{{ route('b2c.catalog.category', 'dinner-cruise') }}" class="gyg-hero-tag">Dinner Cruise</a>
            <a href="{{ route('b2c.catalog.category', 'transfer') }}" class="gyg-hero-tag">Havalimanı Transferi</a>
            <a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}" class="gyg-hero-tag">Yat Kiralama</a>
            <a href="{{ route('b2c.catalog.category', 'helikopter') }}" class="gyg-hero-tag">Helikopter Turu</a>
            <a href="{{ route('b2c.catalog.category', 'yurt-ici-turlar') }}" class="gyg-hero-tag">Kapadokya</a>
        </div>

        {{-- Hero kartları --}}
        @php
            $heroDisplay = $heroItems->isNotEmpty() ? $heroItems->take(2) : collect([
                (object)['slug'=>'dinner-cruise','category'=>null,'product_type'=>'leisure',
                 'title'=>'İstanbul: Türk Gecesi Gösterisi ile Boğazda Akşam Yemeği Gezisi',
                 'duration_hours'=>3,'destination_city'=>'İstanbul','min_pax'=>1,
                 'rating_avg'=>4.6,'review_count'=>2040,'pricing_type'=>'fixed','base_price'=>1284,'currency'=>'TRY'],
                (object)['slug'=>'yurt-ici-turlar','category'=>null,'product_type'=>'tour',
                 'title'=>'İstanbul: Üst Açık Otobüsle Şehir Turu & Panoramik Boğaz Gezisi',
                 'duration_hours'=>4,'destination_city'=>'İstanbul','min_pax'=>1,
                 'rating_avg'=>4.3,'review_count'=>890,'pricing_type'=>'fixed','base_price'=>650,'currency'=>'TRY'],
            ]);
        @endphp
        <div class="gyg-hero-cards">
            @foreach($heroDisplay as $hi)
            @php
                $hiImg = isset($hi->cover_image) && $hi->cover_image
                    ? (str_starts_with($hi->cover_image, 'http') ? $hi->cover_image : rtrim(config('app.url'), '/') . '/uploads/' . $hi->cover_image)
                    : null;
            @endphp
            <a href="{{ route('b2c.product.show', $hi->slug) }}" class="gyg-hero-card">
                <div class="hc-img" @if($hiImg) style="background-image:url('{{ $hiImg }}')" @endif></div>
                <div class="hc-body">
                    <div>
                        <div class="hc-cat">{{ optional($hi->category)->name ?? ucfirst($hi->product_type) }}</div>
                        <div class="hc-title">{{ Str::limit($hi->title, 60) }}</div>
                        <div class="hc-meta">
                            @if($hi->duration_hours) {{ $hi->duration_hours }} saat · @endif
                            {{ $hi->destination_city ?? 'Türkiye' }}
                        </div>
                        @if($hi->rating_avg > 0)
                        <div style="margin-bottom:6px;">
                            <span class="hc-stars">{!! str_repeat('★', (int)floor($hi->rating_avg)) !!}</span>
                            <span style="font-size:.82rem;font-weight:700;color:#1a202c;"> {{ number_format($hi->rating_avg,1) }}</span>
                            @if($hi->review_count > 0)<span style="font-size:.75rem;color:#718096;"> ({{ number_format($hi->review_count,0,',','.') }})</span>@endif
                        </div>
                        @endif
                    </div>
                    <div>
                        <div class="hc-price-label">Başlangıç fiyatı</div>
                        @if($hi->pricing_type === 'fixed' && $hi->base_price)
                            <div class="hc-price">{{ number_format($hi->base_price,0,',','.') }} {{ $hi->currency }}</div>
                        @else
                            <div class="hc-price" style="font-size:.9rem;">Fiyat Al</div>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     SOSYAL KANIT ŞERİDİ
════════════════════════════════════════════════════════════════ --}}
<div class="gyg-trust-strip">
    <div class="inner">
        <div class="gyg-trust-item">
            <i class="bi bi-people-fill"></i>
            <span><strong>14.000+</strong> memnun müşteri</span>
        </div>
        <div class="gyg-trust-item">
            <i class="bi bi-star-fill" style="color:#f4a418;"></i>
            <span><strong>4.8 / 5</strong> ortalama puan</span>
        </div>
        <div class="gyg-trust-item">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span><strong>Ücretsiz iptal</strong> 24 saat öncesine kadar</span>
        </div>
        <div class="gyg-trust-item">
            <i class="bi bi-shield-check-fill"></i>
            <span><strong>Güvenli ödeme</strong> SSL korumalı</span>
        </div>
        <div class="gyg-trust-item">
            <i class="bi bi-headset"></i>
            <span><strong>7/24</strong> müşteri desteği</span>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     KATEGORİ PILLS
════════════════════════════════════════════════════════════════ --}}
<div class="gyg-pills-wrap">
    <div class="gyg-pills">
        <a href="{{ route('b2c.catalog.index') }}" class="gyg-pill active">
            <i class="bi bi-grid-3x3-gap-fill"></i> Tümü
        </a>
        @if($categories->isNotEmpty())
            @foreach($categories->take(10) as $cat)
            <a href="{{ route('b2c.catalog.category', $cat->slug) }}" class="gyg-pill">
                <i class="bi {{ $cat->icon ?? 'bi-grid' }}"></i> {{ $cat->name }}
            </a>
            @endforeach
        @else
            <a href="{{ route('b2c.catalog.category', 'transfer') }}" class="gyg-pill"><i class="bi bi-car-front-fill"></i> Havalimanı Transferi</a>
            <a href="{{ route('b2c.catalog.category', 'ozel-jet') }}" class="gyg-pill"><i class="bi bi-airplane-fill"></i> Özel Jet & Charter</a>
            <a href="{{ route('b2c.catalog.category', 'helikopter') }}" class="gyg-pill"><i class="bi bi-helicopter"></i> Helikopter</a>
            <a href="{{ route('b2c.catalog.category', 'dinner-cruise') }}" class="gyg-pill"><i class="bi bi-water"></i> Dinner Cruise</a>
            <a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}" class="gyg-pill"><i class="bi bi-tsunami"></i> Yat Kiralama</a>
            <a href="{{ route('b2c.catalog.category', 'yurt-ici-turlar') }}" class="gyg-pill"><i class="bi bi-map-fill"></i> Yurt İçi Turlar</a>
            <a href="{{ route('b2c.catalog.category', 'yurt-disi-turlar') }}" class="gyg-pill"><i class="bi bi-globe-americas"></i> Yurt Dışı Turlar</a>
            <a href="{{ route('b2c.catalog.category', 'vize') }}" class="gyg-pill"><i class="bi bi-passport"></i> Vize</a>
        @endif
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     ÖNE ÇIKAN DENEYİMLER
════════════════════════════════════════════════════════════════ --}}
<section style="padding:3rem 0;">
    <div class="container" style="max-width:1280px;">
        <div class="gyg-section-head">
            <div>
                <h2>Öne Çıkan Deneyimler</h2>
                <p>En çok tercih edilen hizmetlerimiz</p>
            </div>
            <a href="{{ route('b2c.catalog.index') }}" class="gyg-see-all">Tümünü Gör →</a>
        </div>

        <div class="gyg-products-grid">
            @if($featuredItems->isNotEmpty())
                @foreach($featuredItems->take(8) as $item)
                    @include('b2c.home._product-card', ['item' => $item])
                @endforeach
            @else
                @php
                    $pcColors = [
                        'linear-gradient(135deg,#c0392b,#8e44ad)',
                        'linear-gradient(135deg,#1a3c6b,#2980b9)',
                        'linear-gradient(135deg,#e67e22,#f39c12)',
                        'linear-gradient(135deg,#27ae60,#16a085)',
                        'linear-gradient(135deg,#8e44ad,#2980b9)',
                        'linear-gradient(135deg,#e74c3c,#e67e22)',
                        'linear-gradient(135deg,#2ecc71,#1a3c6b)',
                        'linear-gradient(135deg,#3498db,#9b59b6)',
                    ];
                    $pcIcons = ['bi-water','bi-map-fill','bi-car-front-fill','bi-airplane-fill','bi-tsunami','bi-helicopter','bi-globe-americas','bi-passport'];
                    $pcs = [
                        ['cat'=>'Dinner Cruise','city'=>'İstanbul','slug'=>'dinner-cruise',
                         'title'=>'İstanbul: Türk Gecesi ile Boğazda Akşam Yemeği Gezisi',
                         'price'=>1284,'hours'=>3,'rating'=>4.6,'reviews'=>2040,'type'=>'fixed'],
                        ['cat'=>'Şehir Turu','city'=>'İstanbul','slug'=>'yurt-ici-turlar',
                         'title'=>'İstanbul: Üst Açık Otobüsle Şehir & Boğaz Turu',
                         'price'=>650,'hours'=>4,'rating'=>4.3,'reviews'=>890,'type'=>'fixed'],
                        ['cat'=>'Havalimanı Transferi','city'=>'İstanbul','slug'=>'transfer',
                         'title'=>'İstanbul Havalimanı VIP Transfer Hizmeti',
                         'price'=>850,'hours'=>null,'rating'=>4.8,'reviews'=>1250,'type'=>'fixed'],
                        ['cat'=>'Helikopter Turu','city'=>'İstanbul','slug'=>'helikopter',
                         'title'=>'İstanbul: Boğaz Panoraması Helikopter Turu',
                         'price'=>null,'hours'=>1,'rating'=>4.9,'reviews'=>342,'type'=>'quote'],
                        ['cat'=>'Yat Kiralama','city'=>'Bodrum','slug'=>'yat-kiralama',
                         'title'=>'Bodrum: Özel Yat Kiralama & Mavi Yolculuk',
                         'price'=>null,'hours'=>8,'rating'=>4.7,'reviews'=>176,'type'=>'quote'],
                        ['cat'=>'Özel Jet','city'=>'İstanbul','slug'=>'ozel-jet',
                         'title'=>'İstanbul — Antalya Özel Jet Charter Seferi',
                         'price'=>null,'hours'=>null,'rating'=>0,'reviews'=>0,'type'=>'quote'],
                        ['cat'=>'Yurt İçi Tur','city'=>'Kapadokya','slug'=>'yurt-ici-turlar',
                         'title'=>'Kapadokya: 2 Günlük Her Şey Dahil Tur Paketi',
                         'price'=>3200,'hours'=>null,'rating'=>4.5,'reviews'=>567,'type'=>'fixed'],
                        ['cat'=>'Yurt Dışı Tur','city'=>'Dubai','slug'=>'yurt-disi-turlar',
                         'title'=>'Dubai: 4 Gece 5 Gün Lüks Tur Paketi',
                         'price'=>15900,'hours'=>null,'rating'=>4.4,'reviews'=>231,'type'=>'fixed'],
                    ];
                @endphp
                @foreach($pcs as $idx => $ph)
                <a href="{{ route('b2c.catalog.category', $ph['slug']) }}" class="gyg-pcard">
                    <div class="gyg-pcard-img">
                        <div class="img-placeholder" style="background:{{ $pcColors[$idx] }};">
                            <i class="bi {{ $pcIcons[$idx] }}"></i>
                        </div>
                        <div class="gyg-pcard-heart"><i class="bi bi-heart"></i></div>
                        <div class="gyg-pcard-badge">
                            @if($ph['hours']) {{ $ph['hours'] }} saat @else Birden fazla gün @endif
                        </div>
                    </div>
                    <div class="gyg-pcard-body">
                        <div class="gyg-pcard-cat">{{ $ph['cat'] }} · {{ $ph['city'] }}</div>
                        <div class="gyg-pcard-title">{{ $ph['title'] }}</div>
                        @if($ph['rating'] > 0)
                        <div class="d-flex align-items-center gap-1">
                            <span class="gyg-pcard-stars">
                                {!! str_repeat('★', (int)floor($ph['rating'])) . ($ph['rating'] - floor($ph['rating']) >= 0.5 ? '★' : '') . str_repeat('☆', 5 - (int)ceil($ph['rating'])) !!}
                            </span>
                            <span class="gyg-pcard-rating">{{ number_format($ph['rating'],1) }}</span>
                            <span class="gyg-pcard-reviews">({{ number_format($ph['reviews'],0,',','.') }})</span>
                        </div>
                        @endif
                        <div class="gyg-pcard-price-label">
                            @if($ph['type']==='fixed') kişi başı itibaren @else Teklif alın @endif
                        </div>
                        <div class="gyg-pcard-price">
                            @if($ph['price']) {{ number_format($ph['price'],0,',','.') }} TRY
                            @else <span style="color:#718096;font-weight:500;font-size:.9rem;">Fiyat Al</span>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            @endif
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     NASIL ÇALIŞIR — 3 adım
════════════════════════════════════════════════════════════════ --}}
<div class="gyg-how-it-works">
    <div class="container" style="max-width:1280px;">
        <div style="text-align:center;margin-bottom:2rem;">
            <h2 style="font-size:1.6rem;font-weight:800;color:#1a202c;margin-bottom:.4rem;">Nasıl Çalışır?</h2>
            <p style="color:#718096;font-size:.95rem;">3 adımda rezervasyon tamamla</p>
        </div>
        <div class="gyg-steps">
            <div class="gyg-step">
                <div class="gyg-step-num">1</div>
                <h4>Keşfet &amp; Karşılaştır</h4>
                <p>Transfer, tur, charter, yat kiralama ve daha fazlasını kategorilere göre filtrele. Puan, fiyat ve özelliklerle karşılaştır.</p>
            </div>
            <div class="gyg-step">
                <div class="gyg-step-num">2</div>
                <h4>Seç &amp; Teklif Al</h4>
                <p>Sabit fiyatlı hizmetleri sepete ekle, teklif bazlı hizmetler için ücretsiz fiyat talebi oluştur. 4 saat içinde yanıt.</p>
            </div>
            <div class="gyg-step">
                <div class="gyg-step-num">3</div>
                <h4>Rezervasyon Yap</h4>
                <p>Güvenli ödeme, anında onay. Rezervasyon bilgileri e-posta ile iletilir. İptal hakkın saklı.</p>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     POPÜLER DESTİNASYONLAR
════════════════════════════════════════════════════════════════ --}}
<section style="padding:3rem 0;">
    <div class="container" style="max-width:1280px;">
        <div class="gyg-section-head">
            <div>
                <h2>Popüler Destinasyonlar</h2>
                <p>En çok tercih edilen şehirler ve bölgeler</p>
            </div>
            <a href="{{ route('b2c.catalog.index') }}" class="gyg-see-all">Tümünü Gör →</a>
        </div>
        <div class="gyg-dest-grid">
            @php
                $dests = [
                    ['name'=>'İstanbul',  'count'=>'120+ aktivite', 'icon'=>'bi-buildings-fill', 'bg'=>'linear-gradient(160deg,#1a3c6b,#2d5282)', 'sehir'=>'istanbul'],
                    ['name'=>'Antalya',   'count'=>'64 aktivite',   'icon'=>'bi-sun-fill',        'bg'=>'linear-gradient(160deg,#c05621,#dd6b20)', 'sehir'=>'antalya'],
                    ['name'=>'Bodrum',    'count'=>'48 aktivite',   'icon'=>'bi-water',           'bg'=>'linear-gradient(160deg,#2b6cb0,#3182ce)', 'sehir'=>'bodrum'],
                    ['name'=>'Kapadokya', 'count'=>'32 aktivite',   'icon'=>'bi-cloud-fill',      'bg'=>'linear-gradient(160deg,#6b2d1a,#9c4221)', 'sehir'=>'kapadokya'],
                    ['name'=>'Marmaris',  'count'=>'28 aktivite',   'icon'=>'bi-tsunami',         'bg'=>'linear-gradient(160deg,#276749,#38a169)', 'sehir'=>'marmaris'],
                ];
                if(isset($destinationCities) && $destinationCities->isNotEmpty()) {
                    $iconsMap = ['istanbul'=>'bi-buildings-fill','antalya'=>'bi-sun-fill','bodrum'=>'bi-water','kapadokya'=>'bi-cloud-fill','marmaris'=>'bi-tsunami','izmir'=>'bi-geo-alt-fill'];
                    $bgsMap   = ['istanbul'=>'linear-gradient(160deg,#1a3c6b,#2d5282)','antalya'=>'linear-gradient(160deg,#c05621,#dd6b20)','bodrum'=>'linear-gradient(160deg,#2b6cb0,#3182ce)','kapadokya'=>'linear-gradient(160deg,#6b2d1a,#9c4221)','marmaris'=>'linear-gradient(160deg,#276749,#38a169)','izmir'=>'linear-gradient(160deg,#553c9a,#6b46c1)'];
                    $dests = $destinationCities->take(5)->map(function($d) use($iconsMap,$bgsMap) {
                        $k = mb_strtolower($d->destination_city);
                        return ['name'=>$d->destination_city,'count'=>$d->cnt.' aktivite','icon'=>$iconsMap[$k]??'bi-geo-alt-fill','bg'=>$bgsMap[$k]??'linear-gradient(160deg,#1a3c6b,#2d5282)','sehir'=>$k];
                    })->toArray();
                }
            @endphp
            @foreach($dests as $dest)
            <a href="{{ route('b2c.catalog.index') }}?sehir={{ $dest['sehir'] }}" class="gyg-dest-card">
                <div class="dest-bg" style="background:{{ $dest['bg'] }};">
                    <div>
                        <div class="dest-name">{{ $dest['name'] }}</div>
                        <div class="dest-count">{{ $dest['count'] }}</div>
                    </div>
                </div>
                <div class="dest-icon"><i class="bi {{ $dest['icon'] }}"></i></div>
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     GEZİ İLHAMI — Şehir Rehberleri (GYG tarzı)
════════════════════════════════════════════════════════════════ --}}
<section style="padding:3rem 0;background:#f8f9fc;">
    <div class="container" style="max-width:1280px;">
        <div class="gyg-section-head">
            <div>
                <h2>Gezi İlhamı</h2>
                <p>Şehir rehberleri ve seyahat ipuçları</p>
            </div>
            <a href="{{ route('b2c.blog.index') }}" class="gyg-see-all">Tümünü Keşfet →</a>
        </div>
        <div class="gyg-blog-grid">
            @if(isset($blogPosts) && $blogPosts->isNotEmpty())
                @foreach($blogPosts->take(3) as $post)
                <a href="{{ route('b2c.blog.show', $post->slug) }}" class="gyg-blog-card">
                    <div class="blog-thumb">
                        @if($post->kapak_gorsel)
                            <img src="{{ asset('storage/'.$post->kapak_gorsel) }}" style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <i class="bi bi-journal-text"></i>
                        @endif
                    </div>
                    <div class="blog-cat">Seyahat Rehberi</div>
                    <div class="blog-title">{{ Str::limit($post->baslik, 70) }}</div>
                    <div class="blog-date"><i class="bi bi-calendar3 me-1"></i>{{ $post->created_at->format('d M Y') }}</div>
                </a>
                @endforeach
            @else
                <a href="{{ route('b2c.blog.index') }}?tag=istanbul" class="gyg-blog-card">
                    <div class="blog-thumb" style="background:linear-gradient(135deg,#1a3c6b,#2a5298);"><i class="bi bi-buildings-fill"></i></div>
                    <div class="blog-cat">Şehir Rehberi</div>
                    <div class="blog-title">İstanbul'u keşfet: En kapsamlı seyahat rehberi</div>
                    <div class="blog-date"><i class="bi bi-compass me-1"></i>Seyahat rehberi</div>
                </a>
                <a href="{{ route('b2c.blog.index') }}?tag=antalya" class="gyg-blog-card">
                    <div class="blog-thumb" style="background:linear-gradient(135deg,#c05621,#dd6b20);"><i class="bi bi-sun-fill"></i></div>
                    <div class="blog-cat">Destinasyon</div>
                    <div class="blog-title">Antalya'yı keşfet: Sahil tatili için tam rehber</div>
                    <div class="blog-date"><i class="bi bi-compass me-1"></i>Seyahat rehberi</div>
                </a>
                <a href="{{ route('b2c.blog.index') }}?tag=kapadokya" class="gyg-blog-card">
                    <div class="blog-thumb" style="background:linear-gradient(135deg,#6b2d1a,#9c4221);"><i class="bi bi-cloud-fill"></i></div>
                    <div class="blog-cat">Doğa & Kültür</div>
                    <div class="blog-title">Kapadokya seyahat rehberi: Peri bacaları ve balon turları</div>
                    <div class="blog-date"><i class="bi bi-compass me-1"></i>Seyahat rehberi</div>
                </a>
            @endif
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     HIZLI TEKLİF FORMU
════════════════════════════════════════════════════════════════ --}}
<section style="padding:3rem 0;">
    <div class="container" style="max-width:1280px;">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <h2 style="font-size:1.6rem;font-weight:700;color:#1a202c;margin-bottom:.5rem;">
                    Özel Bir Deneyim mi Planlıyorsunuz?
                </h2>
                <p style="color:#718096;line-height:1.7;">
                    Grup turları, özel organizasyonlar veya kurumsal etkinlikler için size özel teklif hazırlayalım. 4 saatte geri dönüş garantisi.
                </p>
                <div class="d-flex flex-wrap gap-3 mt-3">
                    @foreach(['Ücretsiz danışmanlık','4 saat içinde cevap','Yükümlülük yok'] as $badge)
                    <div style="display:flex;align-items:center;gap:8px;font-size:.9rem;color:#4a5568;">
                        <i class="bi bi-check-circle-fill" style="color:#48bb78;"></i> {{ $badge }}
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-7">
                <div style="background:#f8f9fc;border-radius:16px;padding:2rem;border:1px solid #e5e5e5;">
                    <form action="{{ route('b2c.quick-lead.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Adınız Soyadınız *" required>
                            </div>
                            <div class="col-md-6">
                                <input type="tel" name="phone" class="form-control" placeholder="Telefon *" required>
                            </div>
                            <div class="col-12">
                                <select name="service_type" class="form-select">
                                    <option value="">Hizmet Türü Seçin</option>
                                    <option>Havalimanı Transferi</option>
                                    <option>Özel Jet / Charter</option>
                                    <option>Dinner Cruise</option>
                                    <option>Yat Kiralama</option>
                                    <option>Tur Paketi</option>
                                    <option>Diğer</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <textarea name="notes" class="form-control" rows="2" placeholder="Kısa notunuz (opsiyonel)"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="gyg-supplier-btn w-100" style="border-radius:8px;font-size:1rem;padding:12px;">
                                    <i class="bi bi-send me-2"></i>Ücretsiz Teklif Al
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════════════════════
     TEDARİKÇİ BANNER
════════════════════════════════════════════════════════════════ --}}
<section style="padding:2rem 0 3rem;">
    <div class="container" style="max-width:1280px;">
        <div class="gyg-supplier-banner">
            <div>
                <h3><i class="bi bi-building me-2"></i>Ürününüzü Milyonlara Ulaştırın</h3>
                <p>Transfer, tur, charter veya deniz hizmetleri sunuyorsanız platformumuza katılın. Ücretsiz başvurun, onaylandıktan sonra yayınlanın.</p>
            </div>
            <a href="{{ route('b2c.supplier-apply.show') }}" class="gyg-supplier-btn">
                Tedarikçi Başvurusu →
            </a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
// ── Hero Arama Autocomplete ──────────────────────────────────────────────
(function() {
    const input   = document.getElementById('heroSearchInput');
    const box     = document.getElementById('heroSuggestBox');
    const apiUrl  = '/api/search-suggest';
    let fetchTimer = null;
    let activeFocus = -1;

    if (!input || !box) return;

    function renderItems(data) {
        box.innerHTML = '';
        const popular = data.popular || [];
        const items   = data.items   || [];
        if (!popular.length && !items.length) { box.classList.remove('visible'); return; }

        if (popular.length) {
            const t = document.createElement('div');
            t.className = 'gyg-suggest-section-title';
            t.textContent = input.value.trim().length < 2 ? 'Öneriler' : 'Şehirler';
            box.appendChild(t);
            popular.forEach(p => box.appendChild(makeItem(p, 'city')));
        }
        if (items.length) {
            if (popular.length) {
                const div = document.createElement('div');
                div.className = 'gyg-suggest-divider';
                box.appendChild(div);
            }
            const t2 = document.createElement('div');
            t2.className = 'gyg-suggest-section-title';
            t2.textContent = 'Ürünler';
            box.appendChild(t2);
            items.forEach(it => box.appendChild(makeItem(it, 'product')));
        }
        box.classList.add('visible');
        activeFocus = -1;
    }

    function makeItem(d, type) {
        const a = document.createElement(d.url ? 'a' : 'div');
        a.className = 'gyg-suggest-item';
        if (d.url) a.href = d.url;
        else {
            a.style.cursor = 'pointer';
            a.addEventListener('click', () => {
                input.value = d.label;
                document.getElementById('heroSearchForm').submit();
            });
        }
        a.innerHTML = `
            <div class="gyg-suggest-item-icon ${type}"><i class="bi ${d.icon}"></i></div>
            <div class="gyg-suggest-item-text">
                <div class="gyg-suggest-item-label">${d.label}</div>
                ${d.sub ? `<div class="gyg-suggest-item-sub">${d.sub}</div>` : ''}
            </div>
            ${d.price ? `<div class="gyg-suggest-item-price">${d.price}</div>` : ''}
        `;
        return a;
    }

    function fetchSuggestions(q) {
        fetch(apiUrl + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(renderItems)
            .catch(() => {});
    }

    input.addEventListener('focus', () => {
        fetchSuggestions(input.value.trim());
    });

    input.addEventListener('input', () => {
        clearTimeout(fetchTimer);
        const q = input.value.trim();
        fetchTimer = setTimeout(() => fetchSuggestions(q), 200);
    });

    // Klavye navigasyonu
    input.addEventListener('keydown', (e) => {
        const rows = box.querySelectorAll('.gyg-suggest-item');
        if (!rows.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeFocus = Math.min(activeFocus + 1, rows.length - 1);
            rows.forEach((r,i) => r.classList.toggle('focused', i === activeFocus));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeFocus = Math.max(activeFocus - 1, -1);
            rows.forEach((r,i) => r.classList.toggle('focused', i === activeFocus));
        } else if (e.key === 'Escape') {
            box.classList.remove('visible');
        } else if (e.key === 'Enter' && activeFocus >= 0) {
            e.preventDefault();
            rows[activeFocus].click();
        }
    });

    // Dışarı tıkla kapat
    document.addEventListener('click', (e) => {
        if (!input.closest('.gyg-search-wrap').contains(e.target)) {
            box.classList.remove('visible');
        }
    });
})();
</script>
@endpush

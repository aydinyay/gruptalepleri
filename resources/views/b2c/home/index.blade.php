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
    background: linear-gradient(135deg, #0f2444 0%, #1a3c6b 50%, #1e4d8c 100%);
}
.gyg-hero-bg {
    position: absolute;
    inset: 0;
    background-image: url('{{ asset("images/b2c-hero.jpg") }}');
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

/* Hero öne çıkan kartlar */
.gyg-hero-cards {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-top: 2.5rem;
    flex-wrap: wrap;
}
.gyg-hero-card {
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 16px;
    padding: 16px;
    width: 260px;
    text-align: left;
    text-decoration: none;
    transition: transform .2s, background .2s;
    color: #fff;
}
.gyg-hero-card:hover { transform: translateY(-4px); background: rgba(255,255,255,.2); color: #fff; }
.gyg-hero-card .hc-cat { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; opacity: .75; margin-bottom: 6px; }
.gyg-hero-card .hc-title { font-weight: 700; font-size: .92rem; line-height: 1.4; margin-bottom: 8px; }
.gyg-hero-card .hc-meta { font-size: .78rem; opacity: .75; margin-bottom: 8px; }
.gyg-hero-card .hc-stars { color: #f4a418; font-size: .85rem; }
.gyg-hero-card .hc-price { font-weight: 800; font-size: 1.1rem; }
.gyg-hero-card .hc-price-label { font-size: .72rem; opacity: .75; }

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
        <p style="color:rgba(255,255,255,.7);font-size:.9rem;text-transform:uppercase;letter-spacing:.15em;font-weight:600;margin-bottom:.75rem;">Türkiye'nin Lider Grup Seyahat Platformu</p>
        <h1>Keşfedin, karşılaştırın,<br>rezervasyon yapın.</h1>
        <p class="hero-sub">10.000+ deneyim · Anlık onay · Ücretsiz iptal</p>

        <form action="{{ route('b2c.catalog.index') }}" method="GET">
            <div class="gyg-search-box">
                <i class="bi bi-search" style="color:#a0aec0;font-size:1.1rem;flex-shrink:0;"></i>
                <input type="text" name="q" placeholder="Aktivite, tur veya destinasyon ara..." autocomplete="off">
                <button type="submit" class="gyg-search-btn">
                    <i class="bi bi-search"></i> Ara
                </button>
            </div>
        </form>

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
            <a href="{{ route('b2c.product.show', $hi->slug) }}" class="gyg-hero-card">
                <div class="hc-cat">{{ optional($hi->category)->name ?? ucfirst($hi->product_type) }}</div>
                <div class="hc-title">{{ Str::limit($hi->title, 55) }}</div>
                <div class="hc-meta">
                    @if($hi->duration_hours) {{ $hi->duration_hours }} saat · @endif
                    {{ $hi->destination_city ?? 'Türkiye' }}
                </div>
                @if($hi->rating_avg > 0)
                <div class="mb-1">
                    <span class="hc-stars">
                        @for($s=1;$s<=5;$s++)@if($s<=floor($hi->rating_avg))★@elseif($s-$hi->rating_avg<1)★@else☆@endif@endfor
                    </span>
                    <span style="font-size:.82rem;font-weight:700;"> {{ number_format($hi->rating_avg,1) }}</span>
                    @if($hi->review_count > 0)<span style="font-size:.78rem;opacity:.75;">({{ number_format($hi->review_count,0,',','.') }})</span>@endif
                </div>
                @endif
                <div class="hc-price-label">Başlangıç fiyatı</div>
                @if($hi->pricing_type === 'fixed' && $hi->base_price)
                    <div class="hc-price">{{ number_format($hi->base_price,0,',','.') }} {{ $hi->currency }}</div>
                @else
                    <div class="hc-price" style="font-size:.9rem;font-weight:600;">Fiyat Al</div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</section>

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
            @foreach([
                ['bi-car-front-fill','Havalimanı Transferi','transfer'],
                ['bi-airplane-fill','Özel Jet & Charter','ozel-jet'],
                ['bi-helicopter','Helikopter','helikopter'],
                ['bi-water','Dinner Cruise','dinner-cruise'],
                ['bi-tsunami','Yat Kiralama','yat-kiralama'],
                ['bi-map-fill','Yurt İçi Turlar','yurt-ici-turlar'],
                ['bi-globe-americas','Yurt Dışı Turlar','yurt-disi-turlar'],
                ['bi-passport','Vize','vize'],
            ] as [$icon,$name,$slug])
            <a href="{{ route('b2c.catalog.category', $slug) }}" class="gyg-pill">
                <i class="bi {{ $icon }}"></i> {{ $name }}
            </a>
            @endforeach
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
                                @for($s=1;$s<=5;$s++)@if($s<=floor($ph['rating']))★@elseif($s-$ph['rating']<1)★@else☆@endif@endfor
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
     GÜVEN ŞERİDİ
════════════════════════════════════════════════════════════════ --}}
<div class="gyg-trust-strip">
    <div class="container" style="max-width:1280px;">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="gyg-trust-item">
                    <i class="bi bi-shield-check"></i>
                    <div><strong>Güvenli Ödeme</strong><span>256-bit SSL şifreleme ile korunan ödemeler</span></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="gyg-trust-item">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <div><strong>Ücretsiz İptal</strong><span>Çoğu turda 24 saat öncesine kadar</span></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="gyg-trust-item">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <div><strong>Anlık Onay</strong><span>Rezervasyonunuz hemen onaylanır</span></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="gyg-trust-item">
                    <i class="bi bi-headset"></i>
                    <div><strong>7/24 Destek</strong><span>Seyahatiniz boyunca yanınızdayız</span></div>
                </div>
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
                @foreach([
                    ['bi-buildings-fill','Şehir Rehberi','linear-gradient(135deg,#1a3c6b,#2a5298)','İstanbul\'u keşfet: En kapsamlı seyahat rehberi','istanbul'],
                    ['bi-sun-fill','Destinasyon','linear-gradient(135deg,#c05621,#dd6b20)','Antalya\'yı keşfet: Sahil tatili için tam rehber','antalya'],
                    ['bi-cloud-fill','Doğa & Kültür','linear-gradient(135deg,#6b2d1a,#9c4221)','Kapadokya seyahat rehberi: Peri bacaları ve balon turları','kapadokya'],
                ] as [$icon,$cat,$bg,$title,$tag])
                <a href="{{ route('b2c.blog.index') }}?tag={{ $tag }}" class="gyg-blog-card">
                    <div class="blog-thumb" style="background:{{ $bg }};"><i class="bi {{ $icon }}"></i></div>
                    <div class="blog-cat">{{ $cat }}</div>
                    <div class="blog-title">{{ $title }}</div>
                    <div class="blog-date"><i class="bi bi-compass me-1"></i>Seyahat rehberi</div>
                </a>
                @endforeach
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

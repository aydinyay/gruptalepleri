@extends('b2c.layouts.app')

@section('title', 'Grup Seyahat Deneyimleri')
@section('meta_description', 'Transfer, charter uçuş, dinner cruise, yat kiralama, tur paketleri. Türkiye\'nin en geniş grup seyahat kataloğu.')

@push('head_styles')
<style>
/* ── Hero ── */
.grt-hero {
    position: relative;
    min-height: 520px;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg,#0c1f3d 0%,#1a3c6b 50%,#0e4d6b 100%);
    overflow: hidden;
}
.grt-hero::before {
    content:'';
    position:absolute;inset:0;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Ccircle cx='50' cy='50' r='40' fill='none' stroke='rgba(255,255,255,0.03)' stroke-width='1'/%3E%3C/svg%3E") repeat;
    opacity:.5;
}
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
     HERO — Arama Çubuğu
═══════════════════════════════════════════════════════════════════════ --}}
<section class="grt-hero py-0">
    <div class="container py-5 position-relative">
        <div class="text-center mb-5">
            <h1 class="text-white fw-800 mb-3" style="font-size:clamp(1.8rem,4vw,2.8rem);line-height:1.2;">
                Yapılacak şeyleri keşfedin ve<br>
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
        <div class="text-center mt-3">
            <span style="color:rgba(255,255,255,.55);font-size:.85rem;">Popüler:</span>
            @foreach(['İstanbul Dinner Cruise','Kapadokya Turu','Özel Jet','Yat Kiralama','Havalimanı Transferi'] as $tag)
            <a href="{{ route('b2c.catalog.index', ['q' => $tag]) }}"
               class="badge rounded-pill ms-1 text-decoration-none"
               style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.85);font-size:.8rem;font-weight:500;padding:.35rem .75rem;">
                {{ $tag }}
            </a>
            @endforeach
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
        {{-- Placeholder kartlar (henüz ürün eklenmemiş) --}}
        <div class="row g-3">
            @foreach([
                ['bi-water','Dinner Cruise','İstanbul','dinner-cruise','Akşam Yemeği & Boğaz Turu','350'],
                ['bi-airplane','Özel Jet Kiralama','İstanbul','ozel-jet','İstanbul - Antalya Özel Uçuş','quote'],
                ['bi-tsunami','Yat Kiralama','Bodrum','yat-kiralama','Bodrum Günlük Yat Turu','850'],
                ['bi-map','Kapadokya Turu','Nevşehir','yurt-ici-turlar','2 Gece 3 Gün Kapadokya','2400'],
            ] as $i => $p)
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('b2c.catalog.category', $p[3]) }}" class="grt-product-card">
                    <div class="position-relative">
                        <div class="card-img-placeholder dest-color-{{ $i }}">
                            <i class="bi {{ $p[0] }}"></i>
                        </div>
                        <div class="img-overlay">
                            <span style="color:rgba(255,255,255,.8);font-size:.78rem;">
                                <i class="bi bi-geo-alt-fill me-1"></i>{{ $p[2] }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body-grt">
                        <div class="card-cat-badge">
                            <i class="bi {{ $p[0] }}"></i>{{ $p[1] }}
                        </div>
                        <div class="card-title-grt">{{ $p[4] }}</div>
                        <div class="d-flex justify-content-between align-items-center">
                            @if($p[5] === 'quote')
                            <div>
                                <div class="card-price-label">Başlangıç fiyatı</div>
                                <div class="card-cta">Teklif Alın <i class="bi bi-arrow-right"></i></div>
                            </div>
                            @else
                            <div>
                                <div class="card-price-label">kişi başı itibaren</div>
                                <div class="card-price">{{ number_format($p[5]) }} TL</div>
                            </div>
                            @endif
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

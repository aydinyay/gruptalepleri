<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>B2B Katalog — Grup Talepleri</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
@include('acente.partials.theme-styles')
<style>
:root{--gt-orange:#ff5533;--gt-blue:#1a3c6b;}
.cat-hero{background:linear-gradient(135deg,#0b1f42 0%,#1a3a6e 60%,#0e2d5a 100%);color:#fff;padding:2rem 0 1.8rem;}
.cat-hero h1{font-size:clamp(1.5rem,3vw,2rem);font-weight:800;margin:0 0 .4rem;}
.cat-hero p{color:rgba(255,255,255,.75);margin:0;font-size:.95rem;}
.filter-bar{background:var(--gt-card,#fff);border-bottom:1px solid var(--gt-border,#e8e8e8);padding:.75rem 0;position:sticky;top:64px;z-index:99;}
.filter-pill{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;border:1px solid var(--gt-border,#e8e8e8);background:var(--gt-card,#fff);color:var(--gt-text,#1a1a1a);padding:.35rem .75rem;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap;}
.filter-pill:hover,.filter-pill.active{border-color:var(--gt-blue);color:var(--gt-blue);background:rgba(26,60,107,.07);}
.prd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;padding:1.5rem 0 3rem;}
.prd-card{background:var(--gt-card,#fff);border:1px solid var(--gt-border,#e8e8e8);border-radius:12px;overflow:hidden;text-decoration:none;color:inherit;transition:box-shadow .2s,transform .2s;display:flex;flex-direction:column;}
.prd-card:hover{box-shadow:0 8px 32px rgba(0,0,0,.13);transform:translateY(-2px);}
.prd-card-img{height:180px;width:100%;object-fit:cover;}
.prd-card-img-ph{height:180px;background:#eef2ff;display:flex;align-items:center;justify-content:center;}
.prd-card-body{padding:14px;flex:1;display:flex;flex-direction:column;}
.prd-card-cat{font-size:.72rem;font-weight:700;color:var(--gt-blue);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;}
.prd-card-title{font-size:.93rem;font-weight:700;margin-bottom:6px;line-height:1.35;}
.prd-card-meta{font-size:.78rem;color:#718096;display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;}
.prd-card-price{margin-top:auto;display:flex;align-items:baseline;gap:6px;}
.prd-card-amount{font-size:1.15rem;font-weight:800;color:var(--gt-blue);}
.prd-card-per{font-size:.75rem;color:#718096;}
.prd-card-pub{font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:4px;}
.pub-b2c{background:#f0fdf4;color:#166534;border:1px solid #86efac;}
.pub-b2b{background:#eef2ff;color:#1a3c6b;border:1px solid #c7d2fe;}
.pub-no{background:#fefce8;color:#854d0e;border:1px solid #fde047;}
.empty-state{text-align:center;padding:4rem 1rem;color:#718096;}
</style>
</head>
<body>
<x-navbar-acente active="katalog" />

<div class="cat-hero">
<div class="container">
    <h1><i class="bi bi-grid-3x3-gap-fill me-2"></i>B2B Hizmet Kataloğu</h1>
    <p>Tüm ürün ve hizmetler — B2B net fiyatlarıyla.</p>
</div>
</div>

<div class="filter-bar">
<div class="container d-flex gap-2 flex-wrap align-items-center">
    <button class="filter-pill active" data-cat="all" onclick="filterCat(this)">Tümü</button>
    @foreach($categories->where('active_count','>', 0) as $cat)
    <button class="filter-pill" data-cat="{{ $cat->slug }}" onclick="filterCat(this)">{{ $cat->name }}</button>
    @endforeach
    <span class="ms-auto" style="font-size:.82rem;color:#718096;" id="resultCount">{{ $items->count() }} hizmet</span>
</div>
</div>

<div class="container">
<div class="prd-grid" id="prdGrid">
@forelse($items as $item)
@php
$price    = $item->gt_price ?? $item->base_price;
$img      = $item->cover_image ? (str_starts_with($item->cover_image,'http') ? $item->cover_image : rtrim(config('app.url'),'/').'/uploads/'.$item->cover_image) : null;
$catSlug  = $item->category?->slug ?? '';
$subtype  = $item->product_subtype ?? '';
$priceLabel = $item->pricing_unit ?: match($subtype) {
    'yacht_charter'                => 'saatlik · grup başına',
    'dinner_cruise','evening_show' => 'kişi başına',
    'day_tour','activity_tour'     => 'kişi başına',
    'multi_day_tour'               => $item->duration_days ? 'kişi · '.$item->duration_days.' gün' : 'kişi başına',
    'airport_transfer','intercity_transfer' => 'araç başına',
    'private_jet','helicopter_tour'=> 'sefer başına',
    'hotel_room'                   => 'oda / gece',
    'apart_rental'                 => 'gecelik',
    'visa_service'                 => 'başvuru başına',
    default                        => 'kişi başına',
};
@endphp
<div class="prd-card" data-cat="{{ $catSlug }}">
    <a href="{{ route('acente.product.show', $item->slug) }}" class="text-decoration-none" style="color:inherit;position:relative;display:block;">
        @if($img)
        <img class="prd-card-img" src="{{ $img }}" alt="{{ $item->title }}">
        @else
        <div class="prd-card-img-ph"><i class="bi bi-image" style="font-size:2rem;color:#c7d2fe;"></i></div>
        @endif
        @if($item->badge_label)
        @php
        $_idxClr = ['Yeni'=>'#10b981','Popüler'=>'#f59e0b','Vizyon'=>'#6366f1','Son Fırsat'=>'#ef4444','İndirim'=>'#8b5cf6','Sınırlı'=>'#dc2626','Çok Satan'=>'#c05621','Sıradışı'=>'#0e7490','Hızlı Tükeniyor'=>'#be123c','Klasik'=>'#374151','Efsane'=>'#1e3a5f','Özel Teklif'=>'#065f46','Erken Rezervasyon'=>'#5b21b6','Gastronomi'=>'#92400e','Gurme'=>'#7c2d12','Lezzetler'=>'#a16207'];
        $_idxIco = ['Yeni'=>'bi-stars','Popüler'=>'bi-fire','Vizyon'=>'bi-eye-fill','Son Fırsat'=>'bi-alarm-fill','İndirim'=>'bi-tag-fill','Sınırlı'=>'bi-exclamation-circle-fill','Çok Satan'=>'bi-graph-up-arrow','Sıradışı'=>'bi-lightning-charge-fill','Hızlı Tükeniyor'=>'bi-hourglass-split','Klasik'=>'bi-award-fill','Efsane'=>'bi-gem','Özel Teklif'=>'bi-gift-fill','Erken Rezervasyon'=>'bi-calendar-check-fill','Gastronomi'=>'bi-egg-fried','Gurme'=>'bi-cup-hot-fill','Lezzetler'=>'bi-basket2-fill'];
        $_ic = $_idxClr[$item->badge_label] ?? '#1a3c6b';
        $_ii = $_idxIco[$item->badge_label] ?? 'bi-bookmark-fill';
        @endphp
        <span style="position:absolute;top:10px;left:10px;background:{{ $_ic }};color:#fff;padding:4px 11px;border-radius:50px;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(0,0,0,.2);">
            <i class="bi {{ $_ii }}"></i> {{ $item->badge_label }}
        </span>
        @endif
        @if($item->is_featured)
        <span style="position:absolute;top:10px;{{ $item->badge_label ? 'right' : 'left' }}:10px;background:#f59e0b;color:#fff;padding:4px 11px;border-radius:50px;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(0,0,0,.2);">
            <i class="bi bi-star-fill"></i> Öne Çıkan
        </span>
        @endif
        <button type="button" onclick="event.preventDefault();gtFavToggle(this,{{ $item->id }})"
                class="gt-fav-btn"
                style="position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.9);border:none;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;transition:transform .15s;">
            <i class="bi bi-heart" style="color:#e53e3e;"></i>
        </button>
    </a>
    <div class="prd-card-body">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
            @if($item->category)
            <span class="prd-card-cat">{{ $item->category->name }}</span>
            @endif
            @php
            $ps = $item->publish_status ?? ($item->is_published ? 'b2c' : 'draft');
            $pubClass = ['b2c' => 'pub-b2c', 'b2b' => 'pub-b2b', 'draft' => 'pub-no'][$ps] ?? 'pub-no';
            $pubLabel = ['b2c' => 'GR Yayında', 'b2b' => 'GT Yayında', 'draft' => 'Taslak'][$ps] ?? 'Taslak';
            @endphp
            <span class="prd-card-pub {{ $pubClass }}">{{ $pubLabel }}</span>
        </div>
        <a href="{{ route('acente.product.show', $item->slug) }}" class="text-decoration-none" style="color:inherit;">
            <div class="prd-card-title">{{ $item->title }}</div>
        </a>
        <div class="prd-card-meta">
            @if($item->destination_city)<span><i class="bi bi-geo-alt"></i> {{ $item->destination_city }}</span>@endif
            @if($item->duration_days)<span><i class="bi bi-clock"></i> {{ $item->duration_days }} gün</span>@elseif($item->duration_hours)<span><i class="bi bi-clock"></i> {{ $item->duration_hours }} saat</span>@endif
            @if($item->min_pax)<span><i class="bi bi-people"></i> Min {{ $item->min_pax }}</span>@endif
        </div>
        @php
        [$btnIcon, $btnLabel] = match($item->pricing_type) {
            'quote'   => ['bi-tag',            'Teklif Al'],
            'request' => ['bi-send',           'Talep Oluştur'],
            default   => ['bi-calendar2-check','Rezervasyon Yap'],
        };
        @endphp
        <div style="margin-top:auto;">
            @if($price)
            <div class="prd-card-price mb-2">
                <span class="prd-card-amount">{{ number_format($price,0,',','.') }} {{ $item->currency }}</span>
                <span class="prd-card-per">{{ $priceLabel }}</span>
            </div>
            @else
            <div style="font-size:.8rem;color:#718096;margin-bottom:.5rem;">Fiyat taleple belirlenir</div>
            @endif
            <a href="{{ route('acente.product.show', $item->slug) }}#rezervasyon"
               class="btn btn-sm w-100"
               style="background:#1a3c6b;color:#fff;font-weight:600;border-radius:8px;">
                <i class="bi {{ $btnIcon }} me-1"></i>{{ $btnLabel }}
            </a>
        </div>
    </div>
</div>
@empty
<div class="empty-state col-span-full" style="grid-column:1/-1;">
    <i class="bi bi-inbox" style="font-size:3rem;display:block;margin-bottom:1rem;color:#c7d2fe;"></i>
    Henüz aktif ürün bulunmuyor.
</div>
@endforelse
</div>
</div>

@include('acente.partials.leisure-footer')
@include('acente.partials.theme-script')
<script>
function gtFavToggle(btn, id) {
    var key = 'gt_favs';
    var favs = JSON.parse(localStorage.getItem(key) || '[]');
    var idx  = favs.indexOf(id);
    var icon = btn.querySelector('i');
    if (idx >= 0) {
        favs.splice(idx, 1);
        icon.className = 'bi bi-heart';
        btn.title = 'Favorilere ekle';
    } else {
        favs.push(id);
        icon.className = 'bi bi-heart-fill';
        btn.title = 'Favorilerden çıkar';
    }
    localStorage.setItem(key, JSON.stringify(favs));
}
document.addEventListener('DOMContentLoaded', function() {
    var favs = JSON.parse(localStorage.getItem('gt_favs') || '[]');
    document.querySelectorAll('.gt-fav-btn').forEach(function(btn) {
        var id = parseInt(btn.getAttribute('onclick').match(/\d+/)[0]);
        if (favs.indexOf(id) >= 0) {
            btn.querySelector('i').className = 'bi bi-heart-fill';
        }
    });
});
function filterCat(btn) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    var cat = btn.dataset.cat;
    var cards = document.querySelectorAll('#prdGrid [data-cat]');
    var count = 0;
    cards.forEach(c => {
        var show = cat === 'all' || c.dataset.cat === cat;
        c.style.display = show ? '' : 'none';
        if (show) count++;
    });
    document.getElementById('resultCount').textContent = count + ' hizmet';
}
</script>
</body>
</html>

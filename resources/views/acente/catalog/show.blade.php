<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $item->title }} — B2B Katalog</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
@include('acente.partials.theme-styles')
<style>
/* ── Galeri ── */
.prd-gallery{max-width:1280px;margin:0 auto;padding:16px 24px 0;position:relative;}
.prd-gal-1{height:420px;border-radius:12px;overflow:hidden;}
.prd-gal-2{display:grid;grid-template-columns:1fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;}
.prd-gal-3{display:grid;grid-template-columns:2fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;}
.prd-gal-3-right{display:grid;grid-rows:1fr 1fr;gap:4px;}
.prd-gal-4{display:grid;grid-template-columns:2fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;}
.prd-gal-4-right{display:grid;grid-template-rows:1fr 1fr 1fr;gap:4px;}
.prd-gal-n{display:grid;grid-template-columns:2fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;}
.prd-gal-n-right{display:grid;grid-template-rows:1fr 1fr;gap:4px;}
.prd-gal-img{width:100%;height:100%;object-fit:cover;display:block;cursor:pointer;transition:transform .2s;}
.prd-gal-img:hover{transform:scale(1.02);}
.prd-gal-thumb{overflow:hidden;position:relative;cursor:pointer;}
.prd-gal-thumb img{width:100%;height:100%;object-fit:cover;transition:transform .2s;}
.prd-gal-thumb:hover img{transform:scale(1.04);}
.prd-gal-more{position:absolute;inset:0;background:rgba(0,0,0,.48);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;font-weight:800;}
.prd-gal-btn{position:absolute;bottom:14px;right:36px;background:rgba(255,255,255,.92);border:1px solid #e5e5e5;border-radius:8px;padding:6px 14px;font-size:.82rem;font-weight:700;color:#1a202c;cursor:pointer;display:flex;align-items:center;gap:5px;}
/* Lightbox */
.prd-lb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.93);z-index:9999;align-items:center;justify-content:center;}
.prd-lb.open{display:flex;}
.prd-lb-img{max-width:92vw;max-height:88vh;object-fit:contain;border-radius:8px;}
.prd-lb-close{position:fixed;top:14px;right:18px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;}
.prd-lb-prev,.prd-lb-next{position:fixed;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:2rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;}
.prd-lb-prev{left:10px;}.prd-lb-next{right:10px;}
.prd-lb-count{position:fixed;bottom:14px;left:50%;transform:translateX(-50%);color:#fff;font-size:.82rem;background:rgba(0,0,0,.5);padding:.2rem .55rem;border-radius:999px;}
/* Layout */
.prd-wrap{max-width:1280px;margin:0 auto;padding:32px 24px 64px;display:grid;grid-template-columns:1fr 360px;gap:40px}
.prd-title{font-size:1.9rem;font-weight:800;color:var(--gt-text,#1a202c);line-height:1.25;margin-bottom:12px}
.prd-badge{display:inline-flex;align-items:center;gap:6px;background:#eef2ff;color:#1a3c6b;font-size:.8rem;font-weight:600;padding:4px 12px;border-radius:50px;margin-bottom:10px}
.prd-meta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;font-size:.88rem;color:#4a5568}
.prd-pill{display:flex;align-items:center;gap:5px;background:#f7f8fc;border-radius:50px;padding:4px 12px}
.prd-stars{color:#f4a418}
.prd-sec{font-size:1.05rem;font-weight:700;color:var(--gt-text,#1a202c);margin:24px 0 10px;padding-bottom:8px;border-bottom:2px solid #e5e5e5}
.prd-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:8px}
.prd-item{display:flex;align-items:flex-start;gap:10px;background:#f8f9fc;border-radius:10px;padding:12px}
.prd-item i{font-size:1.1rem;color:#1a3c6b;flex-shrink:0;margin-top:2px}
.prd-item strong{font-size:.85rem;display:block;color:#1a202c}
.prd-item span{font-size:.8rem;color:#718096}
.prd-full-desc h3,.prd-full-desc h2{font-size:1rem;font-weight:700;color:#1a202c;margin:18px 0 8px}
.prd-full-desc ul{padding-left:20px;margin-bottom:10px}
.prd-full-desc li{margin-bottom:4px}
.prd-full-desc p{margin-bottom:10px}
/* B2B Fiyat Kartı */
.b2b-card{position:sticky;top:84px;background:var(--gt-card,#fff);border:1px solid #e5e5e5;border-radius:16px;padding:24px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
.b2b-price{font-size:2rem;font-weight:800;color:#1a3c6b;line-height:1;margin-bottom:4px}
.b2b-label{font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#718096;margin-bottom:2px}
.b2b-per{font-size:.8rem;color:#718096;margin-bottom:16px}
.b2b-cta{display:block;width:100%;background:#1a3c6b;color:#fff;font-weight:700;font-size:1rem;padding:14px;border-radius:10px;text-align:center;text-decoration:none;margin-top:12px;border:0;cursor:pointer;transition:background .15s}
.b2b-cta:hover{background:#152f56;color:#fff}
.b2b-cta-sec{display:block;width:100%;border:2px solid #FF5533;color:#FF5533;font-weight:600;font-size:.93rem;padding:11px;border-radius:10px;text-align:center;text-decoration:none;margin-top:8px;transition:all .15s}
.b2b-cta-sec:hover{background:#FF5533;color:#fff}
.b2b-div{height:1px;background:#f0f0f0;margin:14px 0}
.b2b-trust{display:flex;align-items:center;gap:8px;font-size:.8rem;color:#718096;margin-top:8px}
.b2b-trust i{color:#48bb78}
/* Sağlayıcı */
.prd-supplier-card{display:flex;align-items:center;gap:16px;padding:16px 18px;background:#f8f9fc;border-radius:12px;border:1px solid #e5e5e5;margin-bottom:8px}
.prd-supplier-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#1a3c6b,#2d5282);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;flex-shrink:0;}
.prd-supplier-name{font-size:1rem;font-weight:700;color:#1a202c;margin-bottom:2px}
/* İlgili */
.rel-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.rel-card{background:var(--gt-card,#fff);border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;text-decoration:none;color:inherit;transition:box-shadow .2s,transform .2s;display:block}
.rel-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.1);transform:translateY(-2px)}
.rel-card img{width:100%;height:160px;object-fit:cover}
.rel-card-body{padding:12px 14px}
.rel-card-title{font-size:.88rem;font-weight:700;margin-bottom:4px}
.rel-card-price{font-size:.82rem;color:#1a3c6b;font-weight:700}
@@media(max-width:768px){.prd-wrap{grid-template-columns:1fr}.rel-grid{grid-template-columns:repeat(2,1fr)}.prd-gal-1,.prd-gal-2,.prd-gal-3,.prd-gal-4,.prd-gal-n{height:240px;border-radius:8px;}}
</style>
</head>
<body>
<x-navbar-acente active="katalog" />

{{-- Breadcrumb --}}
<div style="background:#f8f9fc;border-bottom:1px solid #e5e5e5;padding:10px 0;font-size:.84rem;color:#718096;">
<div style="max-width:1280px;margin:0 auto;padding:0 24px;">
<a href="{{ route('acente.dashboard') }}" style="color:#718096;text-decoration:none;">Ana Sayfa</a> ›
<a href="{{ route('acente.catalog.index') }}" style="color:#718096;text-decoration:none;">Katalog</a> ›
@if($item->category)
<a href="{{ route('acente.catalog.index') }}?kategori={{ $item->category->slug }}" style="color:#718096;text-decoration:none;">{{ $item->category->name }}</a> ›
@endif
{{ Str::limit($item->title, 50) }}
</div>
</div>

{{-- Galeri --}}
@php
$_imgs = [];
if ($item->cover_image) {
    $_imgs[] = str_starts_with($item->cover_image,'http')
        ? $item->cover_image
        : rtrim(config('app.url'),'/').'/uploads/'.$item->cover_image;
}
foreach (($item->gallery_json ?? []) as $_gi) {
    $_gu = is_array($_gi) ? ($_gi['url'] ?? $_gi['path'] ?? '') : $_gi;
    if ($_gu) $_imgs[] = str_starts_with($_gu,'http') ? $_gu : rtrim(config('app.url'),'/').'/uploads/'.$_gu;
}
foreach (($extraGallery ?? collect()) as $_ea) {
    $_eu = method_exists($_ea, 'resolvedUrl') ? $_ea->resolvedUrl() : ($_ea->url ?? '');
    if ($_eu) {
        if (!str_starts_with($_eu,'http')) $_eu = 'https://gruptalepleri.com/'.ltrim($_eu,'/');
        $_imgs[] = $_eu;
    }
}
$_imgCount = count($_imgs);
@endphp

{{-- Lightbox --}}
<div class="prd-lb" id="prdLb">
    <button class="prd-lb-close" onclick="prdLbClose()">✕</button>
    <button class="prd-lb-prev" onclick="prdLbMove(-1)">‹</button>
    <img class="prd-lb-img" id="prdLbImg" src="" alt="">
    <button class="prd-lb-next" onclick="prdLbMove(1)">›</button>
    <div class="prd-lb-count" id="prdLbCount"></div>
</div>

@if($_imgCount > 0)
<div class="prd-gallery">
@if($_imgCount === 1)
<div class="prd-gal-1"><img class="prd-gal-img" src="{{ $_imgs[0] }}" alt="{{ $item->title }}" onclick="prdLbOpen(0)"></div>
@elseif($_imgCount === 2)
<div class="prd-gal-2">
    <div class="prd-gal-thumb" onclick="prdLbOpen(0)"><img src="{{ $_imgs[0] }}" alt="{{ $item->title }}"></div>
    <div class="prd-gal-thumb" onclick="prdLbOpen(1)"><img src="{{ $_imgs[1] }}" alt="{{ $item->title }}"></div>
</div>
@elseif($_imgCount === 3)
<div class="prd-gal-3">
    <div class="prd-gal-thumb" onclick="prdLbOpen(0)"><img src="{{ $_imgs[0] }}" alt="{{ $item->title }}"></div>
    <div class="prd-gal-3-right">
        <div class="prd-gal-thumb" onclick="prdLbOpen(1)"><img src="{{ $_imgs[1] }}" alt="{{ $item->title }}"></div>
        <div class="prd-gal-thumb" onclick="prdLbOpen(2)"><img src="{{ $_imgs[2] }}" alt="{{ $item->title }}"></div>
    </div>
</div>
@elseif($_imgCount === 4)
<div class="prd-gal-4">
    <div class="prd-gal-thumb" onclick="prdLbOpen(0)"><img src="{{ $_imgs[0] }}" alt="{{ $item->title }}"></div>
    <div class="prd-gal-4-right">
        <div class="prd-gal-thumb" onclick="prdLbOpen(1)"><img src="{{ $_imgs[1] }}" alt="{{ $item->title }}"></div>
        <div class="prd-gal-thumb" onclick="prdLbOpen(2)"><img src="{{ $_imgs[2] }}" alt="{{ $item->title }}"></div>
        <div class="prd-gal-thumb" onclick="prdLbOpen(3)"><img src="{{ $_imgs[3] }}" alt="{{ $item->title }}"></div>
    </div>
</div>
@else
<div class="prd-gal-n">
    <div class="prd-gal-thumb" onclick="prdLbOpen(0)"><img src="{{ $_imgs[0] }}" alt="{{ $item->title }}"></div>
    <div class="prd-gal-n-right">
        <div class="prd-gal-thumb" onclick="prdLbOpen(1)"><img src="{{ $_imgs[1] }}" alt="{{ $item->title }}"></div>
        <div class="prd-gal-thumb" onclick="prdLbOpen(2)">
            <img src="{{ $_imgs[2] }}" alt="{{ $item->title }}">
            @if($_imgCount > 3)
            <div class="prd-gal-more">+{{ $_imgCount - 3 }} fotoğraf</div>
            @endif
        </div>
    </div>
    <button class="prd-gal-btn" onclick="prdLbOpen(0)">
        <i class="bi bi-images"></i> Tüm fotoğraflar ({{ $_imgCount }})
    </button>
</div>
@endif
</div>
@endif

{{-- Sağlayıcı Bandı --}}
@php
$supplierName     = $item->supplier_display_name;
$supplierLogo     = $item->supplier_logo_url ?? null;
$supplierInitials = collect(explode(' ', $supplierName))->filter()->take(2)->map(fn($w) => strtoupper(mb_substr($w,0,1)))->implode('');
@endphp
<div style="background:#fff;border-bottom:1px solid #f0f0f0;">
<div style="max-width:1280px;margin:0 auto;padding:14px 24px;">
<div style="display:flex;align-items:center;gap:16px;">
    @if($supplierLogo)
    <img src="{{ $supplierLogo }}" alt="{{ $supplierName }}" style="width:44px;height:44px;border-radius:8px;object-fit:contain;border:1px solid #e5e5e5;background:#fff;flex-shrink:0;">
    @else
    <div class="prd-supplier-avatar">{{ $supplierInitials }}</div>
    @endif
    <div>
        <div style="font-size:.8rem;color:#718096;margin-bottom:1px;">Hizmet Sağlayıcı</div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="prd-supplier-name">{{ $supplierName }}</span>
            <span style="font-size:.78rem;color:#38a169;font-weight:600;"><i class="bi bi-patch-check-fill"></i> Doğrulanmış</span>
        </div>
    </div>
    <div style="margin-left:auto;">
        @if($item->is_published)
        <span style="background:#f0fdf4;border:1px solid #86efac;color:#166534;font-size:.75rem;font-weight:700;padding:4px 10px;border-radius:6px;"><i class="bi bi-broadcast-pin me-1"></i>Yayında</span>
        @else
        <span style="background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:.75rem;font-weight:700;padding:4px 10px;border-radius:6px;"><i class="bi bi-pencil me-1"></i>Taslak</span>
        @endif
    </div>
</div>
</div>
</div>

{{-- Ana İçerik --}}
<div style="background:var(--gt-bg,#f5f5f5);">
<div class="prd-wrap">

{{-- Sol: Ürün Detayı --}}
<div>
@if($item->category)
<div class="prd-badge"><i class="bi {{ $item->category->icon ?? 'bi-grid' }}"></i> {{ $item->category->name }}</div>
@endif

<h1 class="prd-title">{{ $item->title }}</h1>

<div class="prd-meta">
@if($item->rating_avg > 0)
<span class="prd-stars">{!! str_repeat('★',(int)$item->rating_avg).str_repeat('☆',5-(int)$item->rating_avg) !!}</span>
<strong>{{ number_format($item->rating_avg,1) }}</strong>
@endif
@if($item->destination_city)
<span class="prd-pill"><i class="bi bi-geo-alt-fill"></i> {{ $item->destination_city }}</span>
@endif
@if($item->duration_days)
<span class="prd-pill"><i class="bi bi-clock"></i> {{ $item->duration_days }} gün</span>
@elseif($item->duration_hours)
<span class="prd-pill"><i class="bi bi-clock"></i> {{ $item->duration_hours }} saat</span>
@endif
@if($item->min_pax)
<span class="prd-pill"><i class="bi bi-people-fill"></i> Min. {{ $item->min_pax }} kişi</span>
@endif
</div>

@if($item->short_desc)
<p style="font-size:1rem;color:#4a5568;line-height:1.7;margin-bottom:0;">{{ $item->short_desc }}</p>
@endif

<div class="prd-sec">Hızlı Bilgi</div>
<div class="prd-grid">
@if($item->duration_days || $item->duration_hours)
<div class="prd-item"><i class="bi bi-clock-fill"></i><div><strong>Süre</strong><span>{{ $item->duration_days ? $item->duration_days.' gün' : '' }} {{ $item->duration_hours ? $item->duration_hours.' saat' : '' }}</span></div></div>
@endif
@if($item->destination_city)
<div class="prd-item"><i class="bi bi-geo-alt-fill"></i><div><strong>Lokasyon</strong><span>{{ $item->destination_city }}</span></div></div>
@endif
@if($item->min_pax)
<div class="prd-item"><i class="bi bi-people-fill"></i><div><strong>Min. Kapasite</strong><span>{{ $item->min_pax }} kişi</span></div></div>
@endif
@if($item->max_pax)
<div class="prd-item"><i class="bi bi-people-fill"></i><div><strong>Maks. Kapasite</strong><span>{{ $item->max_pax }} kişi</span></div></div>
@endif
<div class="prd-item"><i class="bi bi-translate"></i><div><strong>Dil</strong><span>Türkçe, İngilizce</span></div></div>
<div class="prd-item"><i class="bi bi-shield-check"></i><div><strong>Ürün Tipi</strong><span>{{ $item->product_subtype ?? $item->product_type ?? '—' }}</span></div></div>
</div>

@if($item->full_desc)
<div class="prd-sec">Detaylı Açıklama</div>
<div style="font-size:.95rem;color:#4a5568;line-height:1.8;" class="prd-full-desc">{!! $item->full_desc !!}</div>
@endif
</div>

{{-- Sağ: B2B Fiyat Kartı --}}
<div>
@php
$b2bPrice = $item->gt_price ?? $item->base_price;
$subtype  = $item->product_subtype ?? '';
$priceLabel = match($subtype) {
    'dinner_cruise','evening_show' => '/ kişi başı · akşam etkinliği',
    'day_tour'                     => '/ kişi başı · tam gün',
    'activity_tour'                => '/ kişi başı',
    'multi_day_tour'               => $item->duration_days ? '/ kişi · '.$item->duration_days.' gün' : '/ kişi başı',
    'airport_transfer','intercity_transfer' => '/ araç — toplam fiyat',
    'private_jet','helicopter_tour'=> 'sefer başı — tüm yolcular dahil',
    'hotel_room'                   => '/ oda · gecelik',
    'apart_rental'                 => '/ gece',
    'admission_ticket'             => '/ kişi (giriş)',
    'event_ticket'                 => '/ kişi (etkinlik)',
    'timed_experience'             => '/ kişi başı',
    'visa_service'                 => '/ başvuru',
    default                        => '/ kişi başı',
};
$talepeLink = match($item->product_type ?? '') {
    'leisure' => route('dinner-cruise.create'),
    'transfer'=> route('acente.dashboard'),
    default   => route('acente.dashboard'),
};
@endphp

<div class="b2b-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <span style="font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#1a3c6b;background:#eef2ff;padding:4px 10px;border-radius:6px;">B2B Net Fiyat</span>
        @if($item->gt_price && $item->base_price && $item->gt_price < $item->base_price)
        @php $margin = round((($item->base_price - $item->gt_price) / $item->base_price) * 100); @endphp
        <span style="font-size:.75rem;font-weight:700;color:#38a169;background:#f0fdf4;border:1px solid #86efac;padding:4px 10px;border-radius:6px;">%{{ $margin }} kazanç</span>
        @endif
    </div>

    @if($b2bPrice)
    <div class="b2b-label">Acente Fiyatı</div>
    <div class="b2b-price">{{ number_format($b2bPrice, 0, ',', '.') }} <span style="font-size:1rem;">{{ $item->currency }}</span></div>
    <div class="b2b-per">{{ $priceLabel }}</div>

    @if($item->base_price && $item->gt_price && $item->base_price > $item->gt_price)
    <div style="font-size:.8rem;color:#718096;margin-bottom:12px;">
        <span style="text-decoration:line-through;">GR Satış: {{ number_format($item->base_price, 0, ',', '.') }} {{ $item->currency }}</span>
    </div>
    @endif
    @else
    <div style="font-size:.95rem;color:#718096;margin-bottom:16px;">Fiyat bilgisi için talep oluşturun.</div>
    @endif

    <a href="{{ $talepeLink }}" class="b2b-cta">
        <i class="bi bi-send me-2"></i>Talep Oluştur
    </a>
    @if($item->is_published)
    <a href="{{ url('https://'.config('b2c.domain','gruprezervasyonlari.com').'/urun/'.$item->slug) }}"
       target="_blank" class="b2b-cta-sec">
        <i class="bi bi-box-arrow-up-right me-1"></i> GR'de Görüntüle
    </a>
    @endif

    <div class="b2b-div"></div>
    <div class="b2b-trust"><i class="bi bi-check-circle-fill"></i> B2B Net Fiyatlarla Erişim</div>
    <div class="b2b-trust"><i class="bi bi-check-circle-fill"></i> Anlık Teklif</div>
    <div class="b2b-trust"><i class="bi bi-check-circle-fill"></i> Grup Talepleri Güvencesi</div>
</div>
</div>

</div>
</div>

{{-- İlgili Ürünler --}}
@if($relatedItems->isNotEmpty())
<section style="padding:3rem 0;background:#f8f9fc;border-top:1px solid #e5e5e5;">
<div style="max-width:1280px;margin:0 auto;padding:0 24px;">
<h2 style="font-size:1.3rem;font-weight:800;color:#1a202c;margin-bottom:1.5rem;">Benzer Hizmetler</h2>
<div class="rel-grid">
@foreach($relatedItems as $rel)
@php
$relPrice = $rel->gt_price ?? $rel->base_price;
$relImg   = $rel->cover_image ? (str_starts_with($rel->cover_image,'http') ? $rel->cover_image : rtrim(config('app.url'),'/').'/uploads/'.$rel->cover_image) : null;
@endphp
<a href="{{ route('acente.product.show', $rel->slug) }}" class="rel-card">
    @if($relImg)
    <img src="{{ $relImg }}" alt="{{ $rel->title }}">
    @else
    <div style="height:160px;background:#eef2ff;display:flex;align-items:center;justify-content:center;"><i class="bi bi-image" style="font-size:2rem;color:#c7d2fe;"></i></div>
    @endif
    <div class="rel-card-body">
        <div class="rel-card-title">{{ Str::limit($rel->title, 45) }}</div>
        @if($relPrice)
        <div class="rel-card-price">{{ number_format($relPrice, 0, ',', '.') }} {{ $rel->currency }}</div>
        @endif
    </div>
</a>
@endforeach
</div>
</div>
</section>
@endif

@include('acente.partials.leisure-footer')
@include('acente.partials.theme-script')

<script>
var _prdImgs = @json($_imgs);
var _prdIdx  = 0;
function prdLbOpen(i) {
    _prdIdx = i;
    document.getElementById('prdLb').classList.add('open');
    document.getElementById('prdLbImg').src = _prdImgs[i];
    document.getElementById('prdLbCount').textContent = (i+1) + ' / ' + _prdImgs.length;
}
function prdLbClose() { document.getElementById('prdLb').classList.remove('open'); }
function prdLbMove(d) {
    _prdIdx = (_prdIdx + d + _prdImgs.length) % _prdImgs.length;
    document.getElementById('prdLbImg').src = _prdImgs[_prdIdx];
    document.getElementById('prdLbCount').textContent = (_prdIdx+1) + ' / ' + _prdImgs.length;
}
document.getElementById('prdLb').addEventListener('click', function(e) {
    if (e.target === this) prdLbClose();
});
document.addEventListener('keydown', function(e) {
    if (!document.getElementById('prdLb').classList.contains('open')) return;
    if (e.key === 'ArrowLeft') prdLbMove(-1);
    if (e.key === 'ArrowRight') prdLbMove(1);
    if (e.key === 'Escape') prdLbClose();
});
</script>
</body>
</html>

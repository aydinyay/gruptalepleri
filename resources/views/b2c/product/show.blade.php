@extends('b2c.layouts.app')
@section('title', $item->meta_title ?? $item->title)
@if($item->cover_image)
@section('og_image', str_starts_with($item->cover_image,'http') ? $item->cover_image : rtrim(config('app.url'),'/').'/uploads/'.$item->cover_image)
@endif
@section('meta_description', $item->meta_description ?? $item->short_desc ?? ($item->title . ' — Grup Rezervasyonları'))
@section('content')
<style>
/* GYG Galeri */
.prd-gallery{max-width:1280px;margin:0 auto;padding:16px 24px 0;position:relative;}
.prd-gal-gyg{position:relative;border-radius:12px;overflow:hidden;}
.prd-gal-gyg.lay-1{height:440px;}
.prd-gal-gyg.lay-2{display:grid;grid-template-columns:1fr 1fr;gap:4px;height:440px;}
.prd-gal-gyg.lay-3{display:grid;grid-template-columns:2fr 1fr;grid-auto-rows:1fr;gap:4px;height:440px;}
.prd-gal-gyg.lay-3 .g-main{grid-row:1/-1;}
.prd-gal-gyg.lay-5{display:grid;grid-template-columns:2fr 1fr 1fr;grid-template-rows:1fr 1fr;gap:4px;height:440px;}
.prd-gal-gyg.lay-5 .g-main{grid-row:1/3;}
.prd-gal-gyg.lay-vid{display:grid;grid-template-columns:1fr 2fr 1fr;grid-template-rows:1fr 1fr;gap:4px;height:440px;}
.prd-gal-gyg.lay-vid .g-vid,.prd-gal-gyg.lay-vid .g-main{grid-row:1/3;}
.prd-gal-img{width:100%;height:100%;object-fit:cover;display:block;cursor:pointer;transition:transform .2s;}
.prd-gal-img:hover{transform:scale(1.02);}
.prd-gal-thumb{overflow:hidden;position:relative;cursor:pointer;}
.prd-gal-thumb img,.prd-gal-thumb video{width:100%;height:100%;object-fit:cover;transition:transform .2s;display:block;}
.prd-gal-thumb:hover img,.prd-gal-thumb:hover video{transform:scale(1.04);}
.prd-gal-more{position:absolute;inset:0;background:rgba(0,0,0,.48);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;font-weight:800;}
.prd-gal-btn{position:absolute;bottom:14px;right:14px;background:rgba(255,255,255,.92);border:1px solid #e5e5e5;border-radius:8px;padding:6px 14px;font-size:.82rem;font-weight:700;color:#1a202c;cursor:pointer;display:flex;align-items:center;gap:5px;z-index:5;}
.prd-gal-btn:hover{background:#fff;}
.prd-heart-btn{position:absolute;top:14px;right:14px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.92);border:1px solid #e5e5e5;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.2rem;transition:transform .15s,background .15s;z-index:10;}
.prd-heart-btn:hover{background:#fff;transform:scale(1.1);}
.prd-heart-btn.saved i{color:#e53e3e;}
.prd-vid-thumb{position:relative;}
.prd-vid-play{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.3);transition:background .2s;pointer-events:none;}
.prd-vid-play i{font-size:3rem;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.4);}
.prd-vid-thumb:hover .prd-vid-play{background:rgba(0,0,0,.15);}
@@media(max-width:600px){.prd-gal-gyg{height:220px!important;border-radius:8px;}}
/* Lightbox */
.prd-lb{display:none;position:fixed;inset:0;background:rgba(0,0,0,.93);z-index:9999;align-items:center;justify-content:center;}
.prd-lb.open{display:flex;}
.prd-lb-img{max-width:92vw;max-height:88vh;object-fit:contain;border-radius:8px;}
.prd-lb-close{position:fixed;top:14px;right:18px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;}
.prd-lb-prev,.prd-lb-next{position:fixed;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:2rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;}
.prd-lb-prev{left:10px;}.prd-lb-next{right:10px;}
.prd-lb-count{position:fixed;bottom:14px;left:50%;transform:translateX(-50%);color:#fff;font-size:.82rem;background:rgba(0,0,0,.5);padding:.2rem .55rem;border-radius:999px;}
.prd-lb-nearby{display:none;position:fixed;top:60px;left:20px;background:rgba(16,185,129,.93);color:#fff;border-radius:20px;padding:.28rem .7rem .28rem .52rem;font-size:.75rem;font-weight:700;align-items:center;gap:.28rem;backdrop-filter:blur(6px);box-shadow:0 2px 8px rgba(0,0,0,.3);z-index:10000;pointer-events:none;}
.prd-lb.open .prd-lb-nearby{display:inline-flex;}
.prd-wrap{max-width:1280px;margin:0 auto;padding:32px 24px 64px;display:grid;grid-template-columns:1fr 360px;gap:40px}
.prd-title{font-size:1.9rem;font-weight:800;color:#1a202c;line-height:1.25;margin-bottom:12px}
.prd-badge{display:inline-flex;align-items:center;gap:6px;background:#eef2ff;color:#1a3c6b;font-size:.8rem;font-weight:600;padding:4px 12px;border-radius:50px;margin-bottom:10px}
.prd-meta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;font-size:.88rem;color:#4a5568}
.prd-pill{display:flex;align-items:center;gap:5px;background:#f7f8fc;border-radius:50px;padding:4px 12px}
.prd-pill i{color:#1a3c6b}
/* Sosyal Paylaşım */
.prd-share{display:flex;align-items:center;gap:7px;margin:16px 0 20px;flex-wrap:wrap;}
.prd-share-label{font-size:.8rem;color:#718096;font-weight:600;margin-right:2px;}
.prd-share-btn{width:34px;height:34px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:.95rem;text-decoration:none;cursor:pointer;border:none;transition:transform .15s,opacity .15s;flex-shrink:0;}
.prd-share-btn:hover{transform:translateY(-2px);opacity:.85;color:inherit;}
.shr-fb{background:#1877F2;color:#fff;}.shr-tw{background:#000;color:#fff;}.shr-li{background:#0A66C2;color:#fff;}
.shr-wa{background:#25D366;color:#fff;}.shr-tg{background:#229ED9;color:#fff;}.shr-pin{background:#E60023;color:#fff;}
.shr-em{background:#718096;color:#fff;}.shr-cp{background:#eef2ff;color:#1a3c6b;}
.prd-stars{color:#f4a418}
.prd-sec{font-size:1.05rem;font-weight:700;color:#1a202c;margin:24px 0 10px;padding-bottom:8px;border-bottom:2px solid #e5e5e5}
.prd-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:8px}
.prd-item{display:flex;align-items:flex-start;gap:10px;background:#f8f9fc;border-radius:10px;padding:12px}
.prd-item i{font-size:1.1rem;color:#1a3c6b;flex-shrink:0;margin-top:2px}
.prd-item strong{font-size:.85rem;display:block;color:#1a202c}
.prd-item span{font-size:.8rem;color:#718096}
.session-slot{padding:5px 10px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s;border:1.5px solid;}
.slot-open{background:#f0fdf4;border-color:#86efac;color:#166534;}
.slot-open:hover{background:#dcfce7;border-color:#4ade80;}
.slot-open.selected{background:#166534;border-color:#166534;color:#fff;}
.slot-full{background:#f9fafb;border-color:#e5e7eb;color:#9ca3af;cursor:not-allowed;}
.prd-full-desc h3{font-size:1rem;font-weight:700;color:#1a202c;margin:18px 0 8px}
.prd-full-desc h2{font-size:1.1rem;font-weight:700;color:#1a202c;margin:20px 0 8px}
.prd-full-desc ul{padding-left:20px;margin-bottom:10px}
.prd-full-desc li{margin-bottom:4px}
.prd-full-desc p{margin-bottom:10px}
.pc{position:sticky;top:84px;background:#fff;border:1px solid #e5e5e5;border-radius:16px;padding:24px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
.pc-price{font-size:2rem;font-weight:800;color:#FF5533;line-height:1;margin-bottom:4px}
.pc-label{font-size:.8rem;color:#718096;margin-bottom:4px}
.pc-per{font-size:.8rem;color:#718096;margin-bottom:16px}
.pc-cta{display:block;width:100%;background:#FF5533;color:#fff;font-weight:700;font-size:1rem;padding:14px;border-radius:10px;text-align:center;text-decoration:none;margin-top:12px;border:0;cursor:pointer}
.pc-cta:hover{background:#e04420;color:#fff}
.pc-sec{display:block;width:100%;border:2px solid #1a3c6b;color:#1a3c6b;font-weight:600;font-size:.93rem;padding:11px;border-radius:10px;text-align:center;text-decoration:none;margin-top:8px}
.pc-sec:hover{background:#1a3c6b;color:#fff}
.pc-trust{display:flex;align-items:center;gap:8px;font-size:.8rem;color:#718096;margin-top:8px}
.pc-trust i{color:#48bb78}
.pc-div{height:1px;background:#f0f0f0;margin:14px 0}
.pc-qbox{background:#f8f9fc;border-radius:10px;padding:14px;margin-bottom:14px;font-size:.87rem;color:#4a5568}
.rel-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.breadcrumb-bar{background:#f8f9fc;border-bottom:1px solid #e5e5e5;padding:10px 0;font-size:.84rem;color:#718096}
.breadcrumb-bar a{color:#718096;text-decoration:none}
/* Sağlayıcı kartı */
.prd-supplier-card{display:flex;align-items:center;gap:16px;padding:16px 18px;background:#f8f9fc;border-radius:12px;border:1px solid #e5e5e5;margin-bottom:8px}
.prd-supplier-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#1a3c6b,#2d5282);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;flex-shrink:0;letter-spacing:.04em}
.prd-supplier-name{font-size:1rem;font-weight:700;color:#1a202c;margin-bottom:2px}
.prd-supplier-badge{font-size:.78rem;color:#38a169;font-weight:600}
.prd-supplier-badge i{font-size:.82rem}
.prd-supplier-meta{font-size:.78rem;color:#718096;margin-top:2px}
.breadcrumb-bar a:hover{color:#1a3c6b}
/* Transfer Canlı Fiyat */
.tr-route-box{background:linear-gradient(135deg,#eef2ff,#f0f4ff);border:1px solid #c7d2fe;border-radius:12px;padding:16px 20px;margin-bottom:16px}
.tr-route-row{display:flex;align-items:center;gap:10px;font-size:.95rem;font-weight:600;color:#1a202c}
.tr-route-dir{display:flex;flex-direction:column;align-items:center;gap:2px;flex-shrink:0}
.tr-route-dir span{width:2px;height:20px;background:#c7d2fe;display:block}
.tr-search-form .form-label{font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px}
.tr-search-form input[type=date],
.tr-search-form input[type=number]{border:1px solid #e2e8f0;border-radius:8px;padding:9px 12px;font-size:.93rem;width:100%;outline:none}
.tr-search-form input:focus{border-color:#1a3c6b;box-shadow:0 0 0 2px rgba(26,60,107,.12)}
.tr-vehicle-list{margin-top:16px}
.tr-vehicle-card{border:1px solid #e5e5e5;border-radius:12px;padding:14px 16px;margin-bottom:10px;background:#fff;display:flex;align-items:center;gap:14px;transition:box-shadow .15s}
.tr-vehicle-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.1);border-color:#c7d2fe}
.tr-vehicle-img{width:80px;height:56px;object-fit:cover;border-radius:8px;flex-shrink:0;background:#f0f4ff}
.tr-vehicle-img-placeholder{width:80px;height:56px;border-radius:8px;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tr-vehicle-info{flex:1;min-width:0}
.tr-vehicle-name{font-weight:700;font-size:.93rem;color:#1a202c;margin-bottom:2px}
.tr-vehicle-meta{font-size:.78rem;color:#718096;display:flex;gap:10px;flex-wrap:wrap}
.tr-vehicle-price{text-align:right;flex-shrink:0}
.tr-vehicle-price .amount{font-size:1.3rem;font-weight:800;color:#FF5533}
.tr-vehicle-price .currency{font-size:.8rem;color:#718096}
.tr-select-btn{display:block;margin-top:6px;background:#FF5533;color:#fff;font-size:.8rem;font-weight:700;padding:6px 14px;border-radius:8px;text-decoration:none;text-align:center;white-space:nowrap}
.tr-select-btn:hover{background:#e04420;color:#fff}
.tr-spinner{text-align:center;padding:24px;color:#718096;font-size:.9rem}
.tr-error-box{background:#fff5f5;border:1px solid #fed7d7;border-radius:10px;padding:14px 16px;font-size:.87rem;color:#c53030;margin-top:10px}
@@media(max-width:768px){
  .prd-wrap{grid-template-columns:1fr}
  .rel-grid{grid-template-columns:repeat(2,1fr)}
  .tr-vehicle-card{flex-wrap:wrap}
}
</style>

<div class="breadcrumb-bar">
<div style="max-width:1280px;margin:0 auto;padding:0 24px;">
<a href="{{ route('b2c.home') }}">Ana Sayfa</a> ›
@if($item->category)
<a href="{{ route('b2c.catalog.category', $item->category->slug) }}">{{ $item->category->name }}</a> ›
@endif
{{ Str::limit($item->title, 50) }}
</div>
</div>

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
// Leisure şablonu galeri fotoğrafları (gruptalepleri.com sunucusunda)
foreach (($extraGallery ?? collect()) as $_ea) {
    $_eu = method_exists($_ea, 'resolvedUrl') ? $_ea->resolvedUrl() : ($_ea->url ?? '');
    if ($_eu) {
        $_eu = str_replace(
            ['gruprezervasyonlari.com', config('b2c.domain','gruprezervasyonlari.com')],
            'gruptalepleri.com', $_eu
        );
        if (!str_starts_with($_eu,'http')) $_eu = 'https://gruptalepleri.com/'.ltrim($_eu,'/');
        $_imgs[] = $_eu;
    }
}
$_imgCount = count($_imgs);
@endphp

@php
$_vexts      = ['mp4','mov','webm'];
$_videos     = array_values(array_filter($_imgs, fn($u) => in_array(strtolower(pathinfo($u, PATHINFO_EXTENSION)), $_vexts)));
$_photos     = array_values(array_filter($_imgs, fn($u) => !in_array(strtolower(pathinfo($u, PATHINFO_EXTENSION)), $_vexts)));
$_hasVideo   = count($_videos) > 0;
$_photoCount = count($_photos);
if ($_hasVideo)          { $_galLayout = 'vid'; }
elseif ($_imgCount === 1){ $_galLayout = '1'; }
elseif ($_imgCount === 2){ $_galLayout = '2'; }
elseif ($_imgCount <= 4) { $_galLayout = '3'; }
else                     { $_galLayout = '5'; }
@endphp

@if($_imgCount > 0)
<div class="prd-gallery">

{{-- Lightbox --}}
<div class="prd-lb" id="prdLb">
    <button class="prd-lb-close" onclick="prdLbClose()">✕</button>
    <button class="prd-lb-prev" onclick="prdLbMove(-1)">‹</button>
    <img class="prd-lb-img" id="prdLbImg" src="" alt="" style="display:none;">
    <video class="prd-lb-img" id="prdLbVid" src="" controls playsinline style="display:none;max-width:92vw;max-height:88vh;border-radius:8px;"></video>
    <button class="prd-lb-next" onclick="prdLbMove(1)">›</button>
    <div class="prd-lb-count" id="prdLbCount"></div>
    <div class="prd-lb-nearby" id="prdLbNearby">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16" style="flex-shrink:0"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/></svg>
        <span id="prdLbNearbyText">Size Yakın</span>
    </div>
</div>

@php
$heartBtn = '<div class="prd-heart-btn ' . (($isSaved ?? false) ? 'saved' : '') . '" data-item-id="' . $item->id . '" onclick="grtWishlistToggle(this)" title="İstek listesine ekle"><i class="bi ' . (($isSaved ?? false) ? 'bi-heart-fill' : 'bi-heart') . '"></i></div>';

$_badgeColorMap = [
    'Yeni'       => '#10b981',
    'Popüler'    => '#f59e0b',
    'Vizyon'     => '#6366f1',
    'Son Fırsat' => '#ef4444',
    'İndirim'    => '#8b5cf6',
    'Sınırlı'   => '#dc2626',
];
$_badgeIconMap = [
    'Yeni'       => 'bi-stars',
    'Popüler'    => 'bi-fire',
    'Vizyon'     => 'bi-eye-fill',
    'Son Fırsat' => 'bi-alarm-fill',
    'İndirim'    => 'bi-tag-fill',
    'Sınırlı'   => 'bi-exclamation-circle-fill',
];
$_bl    = $item->badge_label ?? null;
$_blClr = $_bl ? ($_badgeColorMap[$_bl] ?? '#1a3c6b') : '';
$_blIco = $_bl ? ($_badgeIconMap[$_bl]  ?? 'bi-bookmark-fill') : '';
$badgeOverlay = $_bl
    ? '<div style="position:absolute;top:14px;left:14px;z-index:10;background:' . $_blClr . ';color:#fff;padding:5px 13px;border-radius:50px;font-size:.8rem;font-weight:700;letter-spacing:.04em;display:flex;align-items:center;gap:5px;box-shadow:0 2px 8px rgba(0,0,0,.25);"><i class="bi ' . $_blIco . '"></i> ' . e($_bl) . '</div>'
    : '';
@endphp

@if($_galLayout === '1')
{{-- Tek görsel --}}
<div class="prd-gal-gyg lay-1">
    <img class="prd-gal-img" src="{{ $_imgs[0] }}" alt="{{ $item->title }}" onclick="prdLbOpen(0)">
    {!! $badgeOverlay !!}{!! $heartBtn !!}
</div>

@elseif($_galLayout === '2')
{{-- 2 görsel: 50/50 --}}
<div class="prd-gal-gyg lay-2">
    <div class="prd-gal-thumb" onclick="prdLbOpen(0)"><img src="{{ $_imgs[0] }}" alt="{{ $item->title }}"></div>
    <div class="prd-gal-thumb" onclick="prdLbOpen(1)"><img src="{{ $_imgs[1] }}" alt="{{ $item->title }}"></div>
    {!! $badgeOverlay !!}{!! $heartBtn !!}
</div>

@elseif($_galLayout === '3')
{{-- 3–4 görsel: sol büyük + sağ yığın --}}
<div class="prd-gal-gyg lay-3">
    <div class="prd-gal-thumb g-main" onclick="prdLbOpen(0)"><img src="{{ $_imgs[0] }}" alt="{{ $item->title }}"></div>
    @foreach(array_slice($_imgs, 1, 3) as $_ri => $_rs)
    <div class="prd-gal-thumb" onclick="prdLbOpen({{ $_ri + 1 }})"><img src="{{ $_rs }}" alt="{{ $item->title }}"></div>
    @endforeach
    {!! $badgeOverlay !!}{!! $heartBtn !!}
</div>

@elseif($_galLayout === '5')
{{-- 5+ görsel: GYG grid — sol büyük 2 satır, sağ 2×2 --}}
<div class="prd-gal-gyg lay-5">
    <div class="prd-gal-thumb g-main" onclick="prdLbOpen(0)"><img src="{{ $_imgs[0] }}" alt="{{ $item->title }}"></div>
    @foreach(array_slice($_imgs, 1, 4) as $_ri => $_rs)
    <div class="prd-gal-thumb" onclick="prdLbOpen({{ $_ri + 1 }})">
        <img src="{{ $_rs }}" alt="{{ $item->title }}">
        @if($_ri === 3 && $_imgCount > 5)
        <div class="prd-gal-more">+{{ $_imgCount - 5 }} fotoğraf</div>
        @endif
    </div>
    @endforeach
    <button class="prd-gal-btn" onclick="prdLbOpen(0)"><i class="bi bi-images"></i> Tüm fotoğraflar ({{ $_imgCount }})</button>
    {!! $badgeOverlay !!}{!! $heartBtn !!}
</div>

@else
{{-- Video layout: sol video tam yükseklik | orta ana foto tam yükseklik | sağ 2 foto --}}
@php $_vidSrc = $_videos[0]; $_vidOrigIdx = array_search($_vidSrc, $_imgs); @endphp
<div class="prd-gal-gyg lay-vid">
    <div class="prd-gal-thumb g-vid prd-vid-thumb" onclick="prdLbOpen({{ is_int($_vidOrigIdx) ? $_vidOrigIdx : 0 }})">
        <video src="{{ $_vidSrc }}" muted preload="metadata" playsinline></video>
        <div class="prd-vid-play"><i class="bi bi-play-circle-fill"></i></div>
    </div>
    @if($_photoCount > 0)
    <div class="prd-gal-thumb g-main" onclick="prdLbOpen({{ array_search($_photos[0], $_imgs) }})">
        <img src="{{ $_photos[0] }}" alt="{{ $item->title }}">
    </div>
    @endif
    @foreach(array_slice($_photos, 1, 2) as $_ri => $_rs)
    @php $_origIdx = array_search($_rs, $_imgs); @endphp
    <div class="prd-gal-thumb" onclick="prdLbOpen({{ is_int($_origIdx) ? $_origIdx : 0 }})">
        <img src="{{ $_rs }}" alt="{{ $item->title }}">
        @if($_ri === 1 && $_photoCount > 3)
        <div class="prd-gal-more">+{{ $_photoCount - 3 }} fotoğraf</div>
        @endif
    </div>
    @endforeach
    <button class="prd-gal-btn" onclick="prdLbOpen(0)"><i class="bi bi-play-circle"></i> Tüm medya ({{ $_imgCount }})</button>
    {!! $badgeOverlay !!}{!! $heartBtn !!}
</div>
@endif

</div>
@endif

@php
$supplierName     = $item->supplier_display_name;
$isPlatform       = $item->owner_type === 'platform' || (! $item->supplier_id && ! $item->supplier_name);
$supplierPhone    = $item->supplierAgency?->phone ?? $item->supplier?->phone ?? null;
$supplierLogo     = $item->supplier_logo_url ?? null;
$supplierInitials = collect(explode(' ', $supplierName))->filter()->take(2)->map(fn($w) => strtoupper(mb_substr($w,0,1)))->implode('');
$supplierCount    = $isPlatform
    ? \App\Models\B2C\CatalogItem::published()->where('owner_type','platform')->count()
    : ($item->supplier_id
        ? \App\Models\B2C\CatalogItem::published()->where('supplier_id', $item->supplier_id)->count()
        : 1);
@endphp
<div style="background:#fff;border-bottom:1px solid #f0f0f0;">
<div style="max-width:1280px;margin:0 auto;padding:14px 24px;">
<div class="prd-supplier-card" style="border:none;background:transparent;padding:0;">
    @if($supplierLogo)
    <img src="{{ $supplierLogo }}" alt="{{ $supplierName }}"
         style="width:44px;height:44px;border-radius:8px;object-fit:contain;border:1px solid #e5e5e5;background:#fff;flex-shrink:0;">
    @else
    <div class="prd-supplier-avatar">{{ $supplierInitials }}</div>
    @endif
    <div style="flex:1;min-width:0;">
        <div style="font-size:.8rem;color:#718096;margin-bottom:1px;">Hizmet Sağlayıcı</div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span class="prd-supplier-name">{{ $supplierName }}</span>
            <span class="prd-supplier-badge"><i class="bi bi-patch-check-fill"></i> Doğrulanmış</span>
            @if($supplierCount > 1)
            <span class="prd-supplier-meta">· {{ $supplierCount }} aktif hizmet</span>
            @endif
        </div>
    </div>
</div>
</div>
</div>

<div style="background:#fff;">
<div class="prd-wrap">

<div>
<div style="display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-bottom:10px;">
    @if($item->category)
    <div class="prd-badge" style="margin-bottom:0;"><i class="bi {{ $item->category->icon ?? 'bi-grid' }}"></i> {{ $item->category->name }}</div>
    @endif
    @if($_bl)
    <div style="display:inline-flex;align-items:center;gap:5px;background:{{ $_blClr }};color:#fff;font-size:.78rem;font-weight:700;padding:4px 12px;border-radius:50px;letter-spacing:.04em;">
        <i class="bi {{ $_blIco }}"></i> {{ $_bl }}
    </div>
    @endif
    @if($item->is_featured)
    <div style="display:inline-flex;align-items:center;gap:5px;background:#f59e0b;color:#fff;font-size:.78rem;font-weight:700;padding:4px 12px;border-radius:50px;">
        <i class="bi bi-star-fill"></i> Öne Çıkan
    </div>
    @endif
</div>

<h1 class="prd-title">{{ $item->title }}</h1>

<div class="prd-meta">
@if($item->rating_avg > 0)
<span class="prd-stars">{!! str_repeat('★',(int)$item->rating_avg).str_repeat('☆',5-(int)$item->rating_avg) !!}</span>
<strong>{{ number_format($item->rating_avg,1) }}</strong>
@if($item->review_count > 0)
<span>({{ number_format($item->review_count,0,',','.') }} değerlendirme)</span>
@endif
@endif
@if($item->destination_city)
<span class="prd-pill"><i class="bi bi-geo-alt-fill"></i> {{ $item->destination_city }}</span>
@endif
<span class="prd-pill" id="prdNearbyPill" style="display:none;background:rgba(16,185,129,.1);color:#059669;border-color:#10b981;">
    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/></svg>
    <span id="prdNearbyText">Size Yakın</span>
</span>
@if($item->duration_days)
<span class="prd-pill"><i class="bi bi-clock"></i> {{ $item->duration_days }} gün</span>
@elseif($item->duration_hours)
<span class="prd-pill"><i class="bi bi-clock"></i> {{ $item->duration_hours }} saat</span>
@endif
@if($item->min_pax)
<span class="prd-pill"><i class="bi bi-people-fill"></i> {{ $item->min_pax }}+ kişi</span>
@endif
</div>

{{-- Sosyal Paylaşım --}}
<div class="prd-share">
    <span class="prd-share-label"><i class="bi bi-share-fill"></i> Paylaş:</span>
    <a class="prd-share-btn shr-fb" onclick="grtShare('facebook')" title="Facebook"><i class="bi bi-facebook"></i></a>
    <a class="prd-share-btn shr-tw" onclick="grtShare('twitter')" title="Twitter / X"><i class="bi bi-twitter-x"></i></a>
    <a class="prd-share-btn shr-li" onclick="grtShare('linkedin')" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
    <a class="prd-share-btn shr-wa" onclick="grtShare('whatsapp')" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
    <a class="prd-share-btn shr-tg" onclick="grtShare('telegram')" title="Telegram"><i class="bi bi-telegram"></i></a>
    <a class="prd-share-btn shr-pin" onclick="grtShare('pinterest')" title="Pinterest"><i class="bi bi-pinterest"></i></a>
    <a class="prd-share-btn shr-em" onclick="grtShare('email')" title="E-posta"><i class="bi bi-envelope-fill"></i></a>
    <button class="prd-share-btn shr-cp" id="grtCopyBtn" onclick="grtCopyLink()" title="Linki Kopyala"><i class="bi bi-link-45deg"></i></button>
</div>

@if($item->short_desc)
<p style="font-size:1rem;color:#4a5568;line-height:1.7;margin-bottom:0;">{{ $item->short_desc }}</p>
@endif

{{-- Transfer: Rota bilgisi --}}
@if($item->hasLiveTransferPricing())
@php
$dirLabels = ['ARR' => 'Havalimanından Şehre', 'DEP' => 'Şehirden Havalimanına', 'BOTH' => 'Gidiş-Dönüş'];
$dirLabel  = $dirLabels[$item->transfer_direction] ?? $item->transfer_direction;
@endphp
<div class="prd-sec">Transfer Rotası</div>
<div class="tr-route-box">
    <div class="tr-route-row">
        <div style="flex:1">
            <div style="font-size:.75rem;color:#718096;font-weight:500;margin-bottom:2px;">
                {{ $item->transfer_direction === 'DEP' ? 'Kalkış' : 'Varış Noktası' }}
            </div>
            <div>
                @if($item->transfer_direction !== 'DEP')
                    <i class="bi bi-airplane-fill" style="color:#1a3c6b;"></i>
                    {{ $item->transferAirport->name ?? '—' }}
                    <span style="font-size:.78rem;color:#718096;">({{ $item->transferAirport->code ?? '' }})</span>
                @else
                    <i class="bi bi-building" style="color:#1a3c6b;"></i>
                    {{ $item->transferZone->name ?? '—' }}
                    <span style="font-size:.78rem;color:#718096;">{{ $item->transferZone->city ?? '' }}</span>
                @endif
            </div>
        </div>
        <div class="tr-route-dir">
            <i class="bi bi-arrow-down" style="color:#1a3c6b;font-size:1.1rem;"></i>
            @if($item->transfer_direction === 'BOTH')
            <i class="bi bi-arrow-up" style="color:#1a3c6b;font-size:1.1rem;"></i>
            @endif
        </div>
        <div style="flex:1;text-align:right">
            <div style="font-size:.75rem;color:#718096;font-weight:500;margin-bottom:2px;">
                {{ $item->transfer_direction === 'DEP' ? 'Varış' : 'Teslim Noktası' }}
            </div>
            <div>
                @if($item->transfer_direction !== 'DEP')
                    <i class="bi bi-building" style="color:#1a3c6b;"></i>
                    {{ $item->transferZone->name ?? '—' }}
                    <span style="font-size:.78rem;color:#718096;">{{ $item->transferZone->city ?? '' }}</span>
                @else
                    <i class="bi bi-airplane-fill" style="color:#1a3c6b;"></i>
                    {{ $item->transferAirport->name ?? '—' }}
                    <span style="font-size:.78rem;color:#718096;">({{ $item->transferAirport->code ?? '' }})</span>
                @endif
            </div>
        </div>
    </div>
    <div style="margin-top:10px;font-size:.8rem;color:#718096;">
        <i class="bi bi-tag-fill" style="color:#1a3c6b;"></i> {{ $dirLabel }}
        &nbsp;·&nbsp; <i class="bi bi-clock"></i> Özel araç, kapıdan kapıya
    </div>
</div>
@endif

<div class="prd-sec">Bu Deneyimde Neler Var?</div>
<div class="prd-grid">
@if($item->duration_days || $item->duration_hours)
<div class="prd-item"><i class="bi bi-clock-fill"></i><div><strong>Süre</strong><span>{{ $item->duration_days ? $item->duration_days.' gün' : '' }} {{ $item->duration_hours ? $item->duration_hours.' saat' : '' }}</span></div></div>
@endif
@if($item->destination_city)
<div class="prd-item"><i class="bi bi-geo-alt-fill"></i><div><strong>Lokasyon</strong><span>{{ $item->destination_city }}</span></div></div>
@endif
<div class="prd-item"><i class="bi bi-translate"></i><div><strong>Dil</strong><span>Türkçe, İngilizce</span></div></div>
<div class="prd-item"><i class="bi bi-arrow-counterclockwise"></i><div><strong>İptal</strong><span>24 saat öncesine kadar ücretsiz</span></div></div>
<div class="prd-item"><i class="bi bi-lightning-charge-fill"></i><div><strong>Onay</strong><span>Anında onay</span></div></div>
<div class="prd-item"><i class="bi bi-shield-check"></i><div><strong>Güvenli Ödeme</strong><span>256-bit SSL</span></div></div>
</div>

@if($item->full_desc)
<div class="prd-sec">Detaylı Açıklama</div>
<div style="font-size:.95rem;color:#4a5568;line-height:1.8;" class="prd-full-desc">{!! $item->full_desc !!}</div>
@endif

</div>

{{-- SAĞ KOLON: Fiyat / Rezervasyon --}}
<div>
@if($item->review_count > 10)
<div style="background:#fff7ed;border:1px solid #fbd38d;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:.82rem;">
<i class="bi bi-fire" style="color:#dd6b20;"></i> <strong>Popüler:</strong> Bu hizmet bu ay {{ $item->review_count * 3 }}+ kez rezerve edildi.
</div>
@endif

<div class="pc" id="price-card">

{{-- Canlı Transfer Fiyat Seçici --}}
@if($item->hasLiveTransferPricing())

<div style="font-size:.95rem;font-weight:700;color:#1a202c;margin-bottom:12px;">
    <i class="bi bi-calendar2-check" style="color:#1a3c6b;"></i> Tarih & Araç Seçin
</div>

<form class="tr-search-form" id="tr-form" onsubmit="return false;">
    <input type="hidden" id="tr-airport-id" value="{{ $item->transfer_airport_id }}">
    <input type="hidden" id="tr-zone-id"    value="{{ $item->transfer_zone_id }}">
    <input type="hidden" id="tr-direction"  value="{{ $item->transfer_direction }}">

    <div style="margin-bottom:10px;">
        <label class="form-label">{{ $item->transfer_direction === 'BOTH' ? 'Gidiş Tarihi' : 'Transfer Tarihi' }}</label>
        <input type="date" id="tr-pickup-at" min="{{ now()->addHours(2)->format('Y-m-d') }}"
               value="{{ now()->addDay()->format('Y-m-d') }}">
    </div>

    @if($item->transfer_direction === 'BOTH')
    <div style="margin-bottom:10px;">
        <label class="form-label">Dönüş Tarihi</label>
        <input type="date" id="tr-return-at" min="{{ now()->addDays(2)->format('Y-m-d') }}">
    </div>
    @endif

    <div style="margin-bottom:14px;">
        <label class="form-label">Yolcu Sayısı</label>
        <div style="display:flex;align-items:center;gap:10px;">
            <button type="button" onclick="trChangePax(-1)"
                    style="width:34px;height:34px;border-radius:50%;border:1px solid #e2e8f0;background:#f7f8fc;font-size:1.1rem;cursor:pointer;line-height:1;">−</button>
            <input type="number" id="tr-pax" value="1" min="1" max="100"
                   style="width:60px;text-align:center;" readonly>
            <button type="button" onclick="trChangePax(1)"
                    style="width:34px;height:34px;border-radius:50%;border:1px solid #e2e8f0;background:#f7f8fc;font-size:1.1rem;cursor:pointer;line-height:1;">+</button>
        </div>
    </div>

    <button type="button" onclick="trFetchPrices()" class="pc-cta" id="tr-search-btn">
        <i class="bi bi-search me-1"></i> Fiyatları Gör
    </button>
</form>

<div id="tr-results"></div>

{{-- Transfer değil → Rezervasyon formu --}}
@elseif($item->pricing_type === 'fixed' && $item->base_price)
@php
$subtype = $item->product_subtype ?? '';
$isGroupPrice = in_array($subtype, ['private_jet','helicopter_tour','yacht_charter','airport_transfer','intercity_transfer','corporate_event']);
$priceLabel = match($subtype) {
    'dinner_cruise','evening_show' => '/ kişi başı · akşam etkinliği',
    'day_tour'                     => '/ kişi başı · tam gün',
    'activity_tour'                => '/ kişi başı',
    'multi_day_tour'               => '/ kişi · ' . ($item->duration_days ? $item->duration_days . ' gün' : 'paket'),
    'airport_transfer','intercity_transfer' => '/ araç — toplam fiyat',
    'private_jet'                  => 'sefer başı — tüm yolcular dahil',
    'helicopter_tour'              => 'sefer başı — tüm yolcular dahil',
    'hotel_room'                   => '/ oda · gecelik',
    'apart_rental'                 => '/ gece',
    'admission_ticket'             => '/ kişi (giriş)',
    'event_ticket'                 => '/ kişi (etkinlik)',
    'timed_experience'             => '/ kişi başı',
    'visa_service'                 => '/ başvuru',
    default                        => '/ kişi başı',
};
$priceTitle = $isGroupPrice ? 'Fiyat' : 'Başlangıç fiyatı';
@endphp
<div class="pc-label">{{ $priceTitle }}</div>
<div class="pc-price" id="pcTotalPrice">{{ number_format($item->base_price,0,',','.') }} <span style="font-size:1rem;">{{ $item->currency }}</span></div>
@if($isGroupPrice && in_array($subtype, ['private_jet','helicopter_tour']))
<div class="pc-per">Kapasite: {{ $item->max_pax ?? $item->min_pax ?? '—' }} kişi — fiyat kişi sayısına göre değişmez</div>
@else
<div class="pc-per">{{ $priceLabel }}</div>
@endif

@if($errors->any())
    <div style="background:#fff5f5;border:1px solid #fed7d7;border-radius:8px;padding:10px 12px;margin:8px 0;font-size:.82rem;color:#c53030;">
        @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('b2c.guest.booking.book', $item->slug) }}" style="margin:10px 0 4px;" id="bookForm">
    @csrf
    @if($sessions->isNotEmpty())
    {{-- Seans seçici --}}
    <input type="hidden" name="session_id" id="selectedSessionId">
    <input type="hidden" name="service_date" id="selectedServiceDate">
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:6px;">Seans Seçin *</label>
        @php
        $sessionsByDate = $sessions->groupBy(fn($s) => $s->session_date->format('Y-m-d'));
        @endphp
        <div style="display:flex;flex-direction:column;gap:6px;max-height:260px;overflow-y:auto;padding-right:2px;">
        @foreach($sessionsByDate as $date => $slots)
        <div>
            <div style="font-size:.75rem;font-weight:700;color:#718096;text-transform:uppercase;margin-bottom:3px;">
                {{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y, D') }}
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:5px;">
            @foreach($slots as $s)
            @php
                $full = $s->isFull();
                $rem  = $s->remainingCapacity();
                $price = $s->price_override ?? $item->base_price;
            @endphp
            <button type="button"
                    class="session-slot {{ $full ? 'slot-full' : 'slot-open' }}"
                    data-id="{{ $s->id }}"
                    data-date="{{ $date }}"
                    data-price="{{ $price }}"
                    {{ $full ? 'disabled' : '' }}
                    onclick="selectSession(this)">
                {{ $s->session_time ? substr($s->session_time,0,5) : 'Tüm Gün' }}
                @if($s->label) · {{ $s->label }} @endif
                @if($rem !== null && $rem <= 5 && !$full)
                <span style="font-size:.7rem;color:#e04420;"> ({{ $rem }} kaldı)</span>
                @endif
                @if($full) <span style="font-size:.7rem;">(Dolu)</span> @endif
            </button>
            @endforeach
            </div>
        </div>
        @endforeach
        </div>
        <div id="noSessionWarning" style="display:none;font-size:.8rem;color:#c53030;margin-top:4px;">Lütfen bir seans seçin.</div>
    </div>
    @else
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Hizmet Tarihi</label>
        <input type="date" name="service_date" value="{{ old('service_date') }}"
               min="{{ now()->addDay()->format('Y-m-d') }}"
               style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;">
    </div>
    @endif
    @if(!$isGroupPrice)
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Kişi Sayısı</label>
        <select name="pax_count" id="pcPax" style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;" onchange="pcCalc(this.value)">
            @php $maxPax = $item->max_pax ?? 50; @endphp
            @for($p=($item->min_pax ?? 1);$p<=$maxPax;$p++)
                <option value="{{ $p }}" {{ old('pax_count',$item->min_pax ?? 1)==$p?'selected':'' }}>{{ $p }} kişi</option>
            @endfor
        </select>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e5e5e5;padding-top:8px;margin-bottom:10px;">
        <span style="font-size:.85rem;color:#718096;">Toplam</span>
        <span id="pcTotal" style="font-size:1.1rem;font-weight:800;color:#FF5533;">{{ number_format($item->base_price * ($item->min_pax ?? 1),0,',','.') }} {{ $item->currency }}</span>
    </div>
    @else
    <input type="hidden" name="pax_count" value="{{ $item->min_pax ?? 1 }}">
    <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #e5e5e5;padding-top:8px;margin-bottom:10px;">
        <span style="font-size:.85rem;color:#718096;">Fiyat</span>
        <span id="pcTotal" style="font-size:1.1rem;font-weight:800;color:#FF5533;">{{ number_format($item->base_price,0,',','.') }} {{ $item->currency }}</span>
    </div>
    @endif
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Ad Soyad</label>
        <input type="text" name="guest_name" value="{{ old('guest_name') }}" placeholder="Ad Soyad" required
               style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;">
    </div>
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Telefon</label>
        <input type="tel" name="guest_phone" value="{{ old('guest_phone') }}" placeholder="+90 5xx xxx xx xx" required
               style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;">
    </div>
    <button type="submit" class="pc-cta"><i class="bi bi-calendar-check me-2"></i>Rezervasyon Yap</button>
</form>

@elseif(in_array($item->pricing_type, ['quote','request']))
<div class="pc-qbox">
    <i class="bi bi-info-circle-fill me-2" style="color:#1a3c6b;"></i>
    @if($item->pricing_type === 'quote')Kişiye özel fiyatlandırma. Formu doldurun, 4 saat içinde size özel teklif iletilsin.
    @else Grup talebinizi oluşturun, ekibimiz sizinle iletişime geçsin.
    @endif
</div>

@if($errors->any())
    <div style="background:#fff5f5;border:1px solid #fed7d7;border-radius:8px;padding:10px 12px;margin:8px 0;font-size:.82rem;color:#c53030;">
        @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('b2c.guest.booking.book', $item->slug) }}" style="margin:6px 0 4px;">
    @csrf
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Tercih Edilen Tarih</label>
        <input type="date" name="service_date" value="{{ old('service_date') }}"
               min="{{ now()->addDay()->format('Y-m-d') }}"
               style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;">
    </div>
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Kişi Sayısı</label>
        <select name="pax_count" style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;">
            @for($p=1;$p<=($item->max_pax ?? 100);$p++)
                <option value="{{ $p }}" {{ old('pax_count',2)==$p?'selected':'' }}>{{ $p }} kişi</option>
            @endfor
        </select>
    </div>
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Ad Soyad</label>
        <input type="text" name="guest_name" value="{{ old('guest_name') }}" placeholder="Ad Soyad" required
               style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;">
    </div>
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Telefon</label>
        <input type="tel" name="guest_phone" value="{{ old('guest_phone') }}" placeholder="+90 5xx xxx xx xx" required
               style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;">
    </div>
    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:4px;">Notlar (opsiyonel)</label>
        <textarea name="notes" rows="2" placeholder="Özel istek, bütçe, detaylar..."
                  style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;resize:vertical;">{{ old('notes') }}</textarea>
    </div>
    <button type="submit" class="pc-cta"><i class="bi bi-send me-2"></i>Teklif / Talep Gönder</button>
</form>
@else
<div class="pc-qbox"><i class="bi bi-telephone-fill me-2" style="color:#1a3c6b;"></i>Bilgi almak için aşağıdaki formu doldurun.</div>
<form method="POST" action="{{ route('b2c.guest.booking.book', $item->slug) }}" style="margin:6px 0 4px;">
    @csrf
    <div style="margin-bottom:10px;">
        <input type="text" name="guest_name" value="{{ old('guest_name') }}" placeholder="Ad Soyad" required
               style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;margin-bottom:8px;">
        <input type="tel" name="guest_phone" value="{{ old('guest_phone') }}" placeholder="Telefon" required
               style="width:100%;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.9rem;">
        <input type="hidden" name="pax_count" value="1">
    </div>
    <button type="submit" class="pc-cta"><i class="bi bi-clipboard-plus me-2"></i>Talep Oluştur</button>
</form>
@endif

<div class="pc-div"></div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Ücretsiz iptal (24 saat öncesine kadar)</div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Anında onay</div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Güvenli ödeme</div>
<div class="pc-div"></div>
<div style="display:flex;align-items:center;gap:10px;">
@if($supplierLogo)
<img src="{{ $supplierLogo }}" alt="{{ $supplierName }}"
     style="width:36px;height:36px;border-radius:6px;object-fit:contain;border:1px solid #e5e5e5;background:#fff;flex-shrink:0;">
@else
<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#1a3c6b,#2d5282);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.82rem;font-weight:800;color:#fff;">{{ $supplierInitials }}</div>
@endif
<div>
<div style="font-size:.82rem;font-weight:700;color:#1a202c;">{{ $supplierName }}</div>
<div style="font-size:.75rem;color:#38a169;font-weight:600;"><i class="bi bi-patch-check-fill"></i> Doğrulanmış</div>
</div>
</div>
</div>
</div>

</div>
</div>

@if($relatedItems->isNotEmpty())
<section style="padding:3rem 0;background:#f8f9fc;border-top:1px solid #e5e5e5;">
<div style="max-width:1280px;margin:0 auto;padding:0 24px;">
<div class="gyg-section-head">
<div><h2 class="gr-section-title" style="color:#1a202c;">Benzer Deneyimler</h2><p class="gr-section-subtitle">Bunları da beğenebilirsiniz</p></div>
@if($item->category)
<a href="{{ route('b2c.catalog.category', $item->category->slug) }}" class="gyg-see-all">Tümünü Gör</a>
@endif
</div>
<div class="rel-grid">
@foreach($relatedItems as $rel)
@include('b2c.home._product-card', ['item' => $rel])
@endforeach
</div>
</div>
</section>
@endif

@if($item->hasLiveTransferPricing())
<script>
(function() {
    var _priceUrl   = '{{ route('b2c.transfer.price-query') }}';
    var _direction  = document.getElementById('tr-direction')?.value ?? '';

    function trChangePax(delta) {
        var inp = document.getElementById('tr-pax');
        var val = parseInt(inp.value) + delta;
        if (val < 1) val = 1;
        if (val > 100) val = 100;
        inp.value = val;
    }
    window.trChangePax = trChangePax;

    function trFetchPrices() {
        var airportId = document.getElementById('tr-airport-id').value;
        var zoneId    = document.getElementById('tr-zone-id').value;
        var direction = document.getElementById('tr-direction').value;
        var pax       = document.getElementById('tr-pax').value;
        var pickupAt  = document.getElementById('tr-pickup-at').value;
        var returnAt  = document.getElementById('tr-return-at')?.value ?? '';

        if (!pickupAt) {
            alert('Lütfen tarih seçin.');
            return;
        }

        var btn     = document.getElementById('tr-search-btn');
        var results = document.getElementById('tr-results');

        btn.disabled    = true;
        btn.textContent = 'Aranıyor…';
        results.innerHTML = '<div class="tr-spinner"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;display:inline-block;"></i> Müsait araçlar yükleniyor…</div>';

        var params = new URLSearchParams({
            airport_id: airportId,
            zone_id:    zoneId,
            direction:  direction,
            pax:        pax,
            pickup_at:  pickupAt + ' 10:00:00'
        });
        if (returnAt && direction === 'BOTH') {
            params.append('return_at', returnAt + ' 10:00:00');
        }

        fetch(_priceUrl + '?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled    = false;
            btn.innerHTML   = '<i class="bi bi-search me-1"></i> Fiyatları Gör';
            renderVehicles(data, pickupAt);
        })
        .catch(function(err) {
            btn.disabled    = false;
            btn.innerHTML   = '<i class="bi bi-search me-1"></i> Fiyatları Gör';
            results.innerHTML = '<div class="tr-error-box"><i class="bi bi-exclamation-triangle-fill me-2"></i>Fiyat bilgisi alınamadı. Lütfen tekrar deneyin.</div>';
        });
    }
    window.trFetchPrices = trFetchPrices;

    function renderVehicles(data, pickupAt) {
        var results = document.getElementById('tr-results');

        if (!data.options || data.options.length === 0) {
            results.innerHTML = '<div class="tr-error-box"><i class="bi bi-info-circle me-2"></i>' +
                (data.error ?? 'Bu tarihte uygun araç bulunamadı. Farklı bir tarih deneyin.') +
                '</div>';
            return;
        }

        var html = '<div class="tr-vehicle-list">';
        html += '<div style="font-size:.82rem;color:#718096;margin-bottom:8px;">' + data.options.length + ' araç tipi müsait · ' + formatDate(pickupAt) + '</div>';

        data.options.forEach(function(opt) {
            var photo = opt.vehicle_photos && opt.vehicle_photos.length > 0
                ? '<img class="tr-vehicle-img" src="' + opt.vehicle_photos[0] + '" alt="' + escHtml(opt.vehicle_type) + '">'
                : '<div class="tr-vehicle-img-placeholder"><i class="bi bi-car-front-fill" style="color:#1a3c6b;font-size:1.5rem;"></i></div>';

            var amenities = '';
            if (opt.vehicle_amenities && opt.vehicle_amenities.length > 0) {
                amenities = opt.vehicle_amenities.slice(0, 3).map(function(a) {
                    return '<span>' + escHtml(a.label ?? a.name ?? a) + '</span>';
                }).join(' · ');
            }

            var cancelInfo = opt.cancellation_policy
                ? '<span><i class="bi bi-arrow-counterclockwise"></i> ' + escHtml(opt.cancellation_policy.split('.')[0]) + '</span>'
                : '';

            html += '<div class="tr-vehicle-card">';
            html += photo;
            html += '<div class="tr-vehicle-info">';
            html += '<div class="tr-vehicle-name">' + escHtml(opt.vehicle_type) + '</div>';
            html += '<div class="tr-vehicle-meta">';
            html += '<span><i class="bi bi-people-fill"></i> Max ' + opt.vehicle_max_passengers + ' kişi</span>';
            if (opt.distance_km) html += '<span><i class="bi bi-geo"></i> ~' + Math.round(opt.distance_km) + ' km</span>';
            if (opt.duration_minutes) html += '<span><i class="bi bi-clock"></i> ~' + opt.duration_minutes + ' dk</span>';
            if (amenities) html += amenities;
            html += '</div>';
            if (cancelInfo) html += '<div style="font-size:.75rem;color:#48bb78;margin-top:4px;">' + cancelInfo + '</div>';
            html += '</div>';
            html += '<div class="tr-vehicle-price">';
            html += '<div class="amount">' + formatPrice(opt.total_price) + '</div>';
            html += '<div class="currency">' + escHtml(opt.currency) + '</div>';
            html += '<a href="' + escHtml(opt.booking_url) + '" class="tr-select-btn">Seç <i class="bi bi-arrow-right"></i></a>';
            html += '</div>';
            html += '</div>';
        });

        html += '</div>';
        results.innerHTML = html;
    }

    function formatPrice(num) {
        return Number(num).toLocaleString('tr-TR', {minimumFractionDigits:0, maximumFractionDigits:0});
    }

    function formatDate(dateStr) {
        var d = new Date(dateStr);
        return d.toLocaleDateString('tr-TR', {day:'2-digit', month:'long', year:'numeric'});
    }

    function escHtml(str) {
        if (str == null) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
<style>
@@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endif

@if(!$item->hasLiveTransferPricing() && $item->pricing_type === 'fixed' && $item->base_price)
<script>
var _pcUnitPrice  = {{ (float)$item->base_price }};
var _pcCurrency   = '{{ $item->currency }}';
var _pcIsGroup    = {{ $isGroupPrice ? 'true' : 'false' }};
function pcCalc(pax) {
    var n = parseInt(pax) || 1;
    var total = _pcIsGroup
        ? _pcUnitPrice.toLocaleString('tr-TR', {maximumFractionDigits:0}) + ' ' + _pcCurrency
        : (_pcUnitPrice * n).toLocaleString('tr-TR', {maximumFractionDigits:0}) + ' ' + _pcCurrency;
    var el = document.getElementById('pcTotal');
    if (el) el.textContent = total;
}
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('pcPax');
    if (sel) pcCalc(sel.value);
});
</script>
@endif

<script>
function grtShare(platform) {
    var url   = encodeURIComponent(window.location.href);
    var title = encodeURIComponent(document.title);
    var img   = encodeURIComponent(document.querySelector('meta[property="og:image"]')?.content || '');
    var map = {
        facebook:  'https://www.facebook.com/sharer/sharer.php?u=' + url,
        twitter:   'https://twitter.com/intent/tweet?url=' + url + '&text=' + title,
        linkedin:  'https://www.linkedin.com/sharing/share-offsite/?url=' + url,
        whatsapp:  'https://api.whatsapp.com/send?text=' + title + '%20' + url,
        telegram:  'https://t.me/share/url?url=' + url + '&text=' + title,
        pinterest: 'https://pinterest.com/pin/create/button/?url=' + url + '&media=' + img + '&description=' + title,
        email:     'mailto:?subject=' + title + '&body=' + url,
    };
    if (map[platform]) window.open(map[platform], '_blank', 'width=620,height=520,noopener,noreferrer');
}
function grtCopyLink() {
    var btn = document.getElementById('grtCopyBtn');
    navigator.clipboard.writeText(window.location.href).then(function() {
        btn.innerHTML = '<i class="bi bi-check2"></i>';
        btn.style.cssText += 'background:#dcfce7;color:#166534;';
        setTimeout(function(){ btn.innerHTML='<i class="bi bi-link-45deg"></i>'; btn.style.background=''; btn.style.color=''; }, 2200);
    }).catch(function(){ window.prompt('Linki kopyala:', window.location.href); });
}
</script>
<script>
(function(){
    var _imgs = @json($_imgs ?? []);
    var _idx  = 0;
    var lb    = document.getElementById('prdLb');
    var lbImg = document.getElementById('prdLbImg');
    var lbVid = document.getElementById('prdLbVid');
    var lbCnt = document.getElementById('prdLbCount');
    if (!lb) return;

    function prdLbShow(i) {
        var src = _imgs[i];
        var isVid = /\.(mp4|mov|webm)(\?|$)/i.test(src);
        if (isVid) {
            lbImg.style.display = 'none';
            lbVid.style.display = 'block';
            lbVid.src = src;
            lbVid.play();
        } else {
            if (lbVid) { lbVid.pause(); lbVid.src = ''; lbVid.style.display = 'none'; }
            lbImg.style.display = 'block';
            lbImg.src = src;
        }
        lbCnt.textContent = (i + 1) + ' / ' + _imgs.length;
    }
    window.prdLbOpen = function(i) {
        _idx = i;
        prdLbShow(_idx);
        lb.classList.add('open');
        document.body.style.overflow = 'hidden';
    };
    window.prdLbClose = function() {
        lb.classList.remove('open');
        document.body.style.overflow = '';
        if (lbVid) { lbVid.pause(); lbVid.src = ''; lbVid.style.display = 'none'; }
        lbImg.style.display = 'none';
    };
    window.prdLbMove = function(d) {
        _idx = (_idx + d + _imgs.length) % _imgs.length;
        prdLbShow(_idx);
    };
    lb.addEventListener('click', function(e){ if(e.target===lb) prdLbClose(); });
    document.addEventListener('keydown', function(e){
        if (!lb.classList.contains('open')) return;
        if (e.key==='Escape') prdLbClose();
        if (e.key==='ArrowLeft') prdLbMove(-1);
        if (e.key==='ArrowRight') prdLbMove(1);
    });
})();
</script>

@if($sessions->isNotEmpty())
<script>
function selectSession(btn) {
    document.querySelectorAll('.session-slot.selected').forEach(function(el){ el.classList.remove('selected'); });
    btn.classList.add('selected');
    document.getElementById('selectedSessionId').value   = btn.dataset.id;
    document.getElementById('selectedServiceDate').value = btn.dataset.date;
    var price = parseFloat(btn.dataset.price) || _pcUnitPrice;
    _pcUnitPrice = price;
    var paxEl = document.getElementById('pcPax');
    if (paxEl) pcCalc(paxEl.value); else {
        var tot = document.getElementById('pcTotal');
        if (tot) tot.textContent = price.toLocaleString('tr-TR',{maximumFractionDigits:0}) + ' ' + _pcCurrency;
        var pp  = document.getElementById('pcTotalPrice');
        if (pp) pp.textContent = price.toLocaleString('tr-TR',{maximumFractionDigits:0});
    }
    document.getElementById('noSessionWarning').style.display = 'none';
}
document.getElementById('bookForm').addEventListener('submit', function(e) {
    if (!document.getElementById('selectedSessionId').value) {
        e.preventDefault();
        document.getElementById('noSessionWarning').style.display = 'block';
    }
});
</script>
@endif

@push('scripts')
<script>
(function() {
    var PRD_LAT = {{ $item->venue_lat ?? 'null' }};
    var PRD_LNG = {{ $item->venue_lng ?? 'null' }};
    var uLat = parseFloat(localStorage.getItem('gr_user_lat') || '0');
    var uLng = parseFloat(localStorage.getItem('gr_user_lng') || '0');

    if (!uLat || !uLng) return;

    var label = 'Size Yakın';
    if (PRD_LAT && PRD_LNG) {
        var R = 6371, dLat = (PRD_LAT-uLat)*Math.PI/180, dLng = (PRD_LNG-uLng)*Math.PI/180;
        var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(uLat*Math.PI/180)*Math.cos(PRD_LAT*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
        var km = Math.round(R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a))*10)/10;
        label = 'Size ' + km + ' km yakın';
    }

    // Lightbox badge
    var lbText = document.getElementById('prdLbNearbyText');
    if (lbText) lbText.textContent = label;

    // Meta pill
    var pill = document.getElementById('prdNearbyPill');
    var pillText = document.getElementById('prdNearbyText');
    if (pill && pillText) { pillText.textContent = label; pill.style.display = 'inline-flex'; }
})();
</script>
@endpush

@endsection

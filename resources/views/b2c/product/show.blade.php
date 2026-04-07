@extends('b2c.layouts.app')
@section('title', $item->meta_title ?? $item->title)
@section('content')
<style>
.prd-hero{position:relative;height:400px;overflow:hidden;background:linear-gradient(135deg,#0f2444,#1a3c6b)}
.prd-hero img{width:100%;height:100%;object-fit:cover}
.prd-wrap{max-width:1280px;margin:0 auto;padding:32px 24px 64px;display:grid;grid-template-columns:1fr 340px;gap:40px}
.prd-title{font-size:1.9rem;font-weight:800;color:#1a202c;line-height:1.25;margin-bottom:12px}
.prd-badge{display:inline-flex;align-items:center;gap:6px;background:#eef2ff;color:#1a3c6b;font-size:.8rem;font-weight:600;padding:4px 12px;border-radius:50px;margin-bottom:10px}
.prd-meta{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;font-size:.88rem;color:#4a5568}
.prd-pill{display:flex;align-items:center;gap:5px;background:#f7f8fc;border-radius:50px;padding:4px 12px}
.prd-pill i{color:#1a3c6b}
.prd-stars{color:#f4a418}
.prd-sec{font-size:1.05rem;font-weight:700;color:#1a202c;margin:24px 0 10px;padding-bottom:8px;border-bottom:2px solid #e5e5e5}
.prd-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:8px}
.prd-item{display:flex;align-items:flex-start;gap:10px;background:#f8f9fc;border-radius:10px;padding:12px}
.prd-item i{font-size:1.1rem;color:#1a3c6b;flex-shrink:0;margin-top:2px}
.prd-item strong{font-size:.85rem;display:block;color:#1a202c}
.prd-item span{font-size:.8rem;color:#718096}
.pc{position:sticky;top:84px;background:#fff;border:1px solid #e5e5e5;border-radius:16px;padding:24px;box-shadow:0 4px 24px rgba(0,0,0,.08)}
.pc-price{font-size:2rem;font-weight:800;color:#1a202c;line-height:1;margin-bottom:4px}
.pc-label{font-size:.8rem;color:#718096;margin-bottom:4px}
.pc-per{font-size:.8rem;color:#718096;margin-bottom:16px}
.pc-cta{display:block;width:100%;background:#FF5533;color:#fff;font-weight:700;font-size:1rem;padding:14px;border-radius:10px;text-align:center;text-decoration:none;margin-top:12px}
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
.breadcrumb-bar a:hover{color:#1a3c6b}
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

<div class="prd-hero">
@if($item->cover_image)
<img src="{{ str_starts_with($item->cover_image,'http') ? $item->cover_image : rtrim(config('app.url'),'/').'/uploads/'.$item->cover_image }}" alt="{{ $item->title }}">
@else
@php
$_bg=['transfer'=>'#1a3c6b','charter'=>'#0c3547','leisure'=>'#0e4d6b','tour'=>'#1e4d1e','hotel'=>'#4d1e1e','visa'=>'#3d1a6b'];
$_ic=['transfer'=>'bi-car-front-fill','charter'=>'bi-airplane-fill','leisure'=>'bi-water','tour'=>'bi-map-fill','hotel'=>'bi-building','visa'=>'bi-passport'];
@endphp
<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:{{ $_bg[$item->product_type] ?? '#1a3c6b' }};">
<i class="bi {{ $_ic[$item->product_type] ?? 'bi-grid' }}" style="font-size:5rem;color:rgba(255,255,255,.3);"></i>
</div>
@endif
</div>

<div style="background:#fff;">
<div class="prd-wrap">

<div>
@if($item->category)
<div class="prd-badge"><i class="bi {{ $item->category->icon ?? 'bi-grid' }}"></i> {{ $item->category->name }}</div>
@endif

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
@if($item->duration_days)
<span class="prd-pill"><i class="bi bi-clock"></i> {{ $item->duration_days }} gün</span>
@elseif($item->duration_hours)
<span class="prd-pill"><i class="bi bi-clock"></i> {{ $item->duration_hours }} saat</span>
@endif
@if($item->min_pax)
<span class="prd-pill"><i class="bi bi-people-fill"></i> {{ $item->min_pax }}+ kisi</span>
@endif
</div>

@if($item->short_desc)
<p style="font-size:1rem;color:#4a5568;line-height:1.7;margin-bottom:0;">{{ $item->short_desc }}</p>
@endif

<div class="prd-sec">Bu Deneyimde Neler Var?</div>
<div class="prd-grid">
@if($item->duration_days || $item->duration_hours)
<div class="prd-item"><i class="bi bi-clock-fill"></i><div><strong>Sure</strong><span>{{ $item->duration_days ? $item->duration_days.' gun' : '' }} {{ $item->duration_hours ? $item->duration_hours.' saat' : '' }}</span></div></div>
@endif
@if($item->destination_city)
<div class="prd-item"><i class="bi bi-geo-alt-fill"></i><div><strong>Lokasyon</strong><span>{{ $item->destination_city }}</span></div></div>
@endif
<div class="prd-item"><i class="bi bi-translate"></i><div><strong>Dil</strong><span>Turkce, Ingilizce</span></div></div>
<div class="prd-item"><i class="bi bi-arrow-counterclockwise"></i><div><strong>Iptal</strong><span>24 saat oncesine kadar ucretsiz</span></div></div>
<div class="prd-item"><i class="bi bi-lightning-charge-fill"></i><div><strong>Onay</strong><span>Aninda onay</span></div></div>
<div class="prd-item"><i class="bi bi-shield-check"></i><div><strong>Guvenli Odeme</strong><span>256-bit SSL</span></div></div>
</div>

@if($item->full_desc)
<div class="prd-sec">Detayli Aciklama</div>
<div style="font-size:.95rem;color:#4a5568;line-height:1.8;">{!! nl2br(e($item->full_desc)) !!}</div>
@endif
</div>

<div>
@if($item->review_count > 10)
<div style="background:#fff7ed;border:1px solid #fbd38d;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:.82rem;">
<i class="bi bi-fire" style="color:#dd6b20;"></i> <strong>Populer:</strong> Bu hizmet bu ay {{ $item->review_count * 3 }}+ kez rezerve edildi.
</div>
@endif
<div class="pc">
@if($item->pricing_type === 'fixed' && $item->base_price)
<div class="pc-label">Baslangic fiyati</div>
<div class="pc-price">{{ number_format($item->base_price,0,',','.') }} <span style="font-size:1rem;">{{ $item->currency }}</span></div>
<div class="pc-per">kisi basi</div>
<a href="{{ route('b2c.auth.register') }}" class="pc-cta"><i class="bi bi-calendar-check me-2"></i>Rezervasyon Yap</a>
<a href="{{ route('b2c.iletisim') }}" class="pc-sec"><i class="bi bi-chat-dots me-2"></i>Soru Sor</a>
@elseif($item->pricing_type === 'quote')
<div class="pc-qbox"><i class="bi bi-info-circle-fill me-2" style="color:#1a3c6b;"></i>Kisiye ozel fiyatlandirma. 4 saat icinde size ozel fiyat iletilsin.</div>
<a href="{{ route('b2c.iletisim') }}" class="pc-cta"><i class="bi bi-send me-2"></i>Fiyat Al</a>
@else
<div class="pc-qbox"><i class="bi bi-telephone-fill me-2" style="color:#1a3c6b;"></i>Grup talebinizi olusturun.</div>
<a href="{{ route('b2c.iletisim') }}" class="pc-cta"><i class="bi bi-clipboard-plus me-2"></i>Talep Olustur</a>
@endif
<div class="pc-div"></div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Ucretsiz iptal (24 saat oncesine kadar)</div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Aninda onay</div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Guvenli odeme</div>
<div class="pc-div"></div>
<div style="display:flex;align-items:center;gap:10px;">
<div style="width:36px;height:36px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-building" style="color:#1a3c6b;"></i></div>
<div>
<div style="font-size:.82rem;font-weight:700;color:#1a202c;">{{ $item->supplier->name ?? 'Grup Rezervasyonlari' }}</div>
<div style="font-size:.75rem;color:#718096;"><i class="bi bi-patch-check-fill" style="color:#48bb78;"></i> Dogrulanmis Tedarikci</div>
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
<div><h2 class="gr-section-title" style="color:#1a202c;">Benzer Deneyimler</h2><p class="gr-section-subtitle">Bunlari da begenenebilirsiniz</p></div>
@if($item->category)
<a href="{{ route('b2c.catalog.category', $item->category->slug) }}" class="gyg-see-all">Tumunu Gor</a>
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

@endsection

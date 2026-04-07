@extends('b2c.layouts.app')

@section('title', $item->meta_title ?? $item->title)
@section('meta_description', $item->meta_description ?? $item->short_desc ?? $item->title)

@push('head_styles')
<style>
/* ── Breadcrumb ── */
.gyg-breadcrumb {
    background: #f8f9fc;
    border-bottom: 1px solid #e5e5e5;
    padding: 10px 0;
    font-size: .85rem;
    color: #718096;
}
.gyg-breadcrumb a { color: #718096; text-decoration: none; }
.gyg-breadcrumb a:hover { color: #1a3c6b; text-decoration: underline; }
.gyg-breadcrumb .sep { margin: 0 6px; }

/* ── Hero Görsel ── */
.product-hero {
    position: relative;
    height: 420px;
    overflow: hidden;
    background: linear-gradient(135deg, #0f2444, #1a3c6b);
}
.product-hero img { width: 100%; height: 100%; object-fit: cover; }
.product-hero .hero-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 5rem; color: rgba(255,255,255,.3);
}
@@media (max-width: 768px) { .product-hero { height: 240px; } }

/* ── Layout ── */
.product-layout {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 40px;
    padding-top: 32px;
    padding-bottom: 48px;
}
@@media (max-width: 1024px) { .product-layout { grid-template-columns: 1fr; } }

/* ── Sol: İçerik ── */
.product-category-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eef2ff;
    color: #1a3c6b;
    font-size: .8rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 50px;
    margin-bottom: 12px;
}
.product-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1a202c;
    line-height: 1.25;
    margin-bottom: 12px;
}
@@media (max-width: 768px) { .product-title { font-size: 1.5rem; } }

.product-meta-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
    font-size: .9rem;
    color: #4a5568;
}
.product-meta-row .stars { color: #f4a418; font-size: 1rem; }
.product-meta-row .rating-num { font-weight: 700; color: #1a202c; }
.product-meta-row .meta-pill {
    display: flex; align-items: center; gap: 5px;
    background: #f7f8fc; border-radius: 50px;
    padding: 4px 12px; font-size: .85rem;
}
.product-meta-row .meta-pill i { color: #1a3c6b; }

/* Bölüm başlıkları */
.product-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a202c;
    margin: 28px 0 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e5e5e5;
}
.product-desc { font-size: .95rem; color: #4a5568; line-height: 1.8; }

/* Özellikler grid */
.product-highlights {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 8px;
}
@@media (max-width: 480px) { .product-highlights { grid-template-columns: 1fr; } }

.product-highlight-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: #f8f9fc;
    border-radius: 10px;
    padding: 12px;
}
.product-highlight-item i {
    font-size: 1.2rem;
    color: #1a3c6b;
    flex-shrink: 0;
    margin-top: 1px;
}
.product-highlight-item strong { font-size: .88rem; display: block; color: #1a202c; }
.product-highlight-item span { font-size: .82rem; color: #718096; }

/* ── Sağ: Fiyat Kartı ── */
.price-card {
    position: sticky;
    top: 84px;
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
}
.price-card .pc-label { font-size: .82rem; color: #718096; margin-bottom: 4px; }
.price-card .pc-price {
    font-size: 2rem;
    font-weight: 800;
    color: #1a202c;
    line-height: 1;
    margin-bottom: 4px;
}
.price-card .pc-currency { font-size: 1rem; font-weight: 500; }
.price-card .pc-per { font-size: .82rem; color: #718096; }
.price-card .pc-cta {
    display: block;
    width: 100%;
    background: #FF5533;
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    padding: 14px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    margin-top: 16px;
    transition: background .15s;
}
.price-card .pc-cta:hover { background: #e04420; color: #fff; }
.price-card .pc-secondary {
    display: block;
    width: 100%;
    background: #fff;
    color: #1a3c6b;
    font-weight: 600;
    font-size: .95rem;
    padding: 12px;
    border-radius: 10px;
    border: 2px solid #1a3c6b;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    margin-top: 10px;
    transition: all .15s;
}
.price-card .pc-secondary:hover { background: #1a3c6b; color: #fff; }
.price-card .pc-trust {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .82rem;
    color: #718096;
    margin-top: 8px;
}
.price-card .pc-trust i { color: #48bb78; }
.price-card .pc-quote-box {
    background: #f8f9fc;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
    font-size: .88rem;
    color: #4a5568;
}
.price-card .pc-divider { height: 1px; background: #f0f0f0; margin: 16px 0; }

/* ── Benzer Ürünler ── */
.related-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
@@media (max-width: 768px) { .related-grid { grid-template-columns: repeat(2, 1fr); } }
@@media (max-width: 480px) { .related-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="gyg-breadcrumb">
    <div style="max-width:1280px;margin:0 auto;padding:0 24px;">
        <a href="{{ route('b2c.home') }}">Ana Sayfa</a>
        <span class="sep">›</span>
        @if($item->category)
        <a href="{{ route('b2c.catalog.category', $item->category->slug) }}">{{ $item->category->name }}</a>
        <span class="sep">›</span>
        @endif
        <span>{{ Str::limit($item->title, 50) }}</span>
    </div>
</div>

{{-- Hero Görsel --}}
<div class="product-hero">
    @if($item->cover_image)
        <img src="{{ str_starts_with($item->cover_image, 'http') ? $item->cover_image : rtrim(config('app.url'), '/') . '/uploads/' . $item->cover_image }}" alt="{{ $item->title }}">
    @else
        @php
            $typeColors = ['transfer'=>'linear-gradient(135deg,#1a3c6b,#2a5298)','charter'=>'linear-gradient(135deg,#0c3547,#1a6b8a)','leisure'=>'linear-gradient(135deg,#0e4d6b,#1a7a8a)','tour'=>'linear-gradient(135deg,#1e4d1e,#2d7a2d)','hotel'=>'linear-gradient(135deg,#4d1e1e,#8a2d2d)','visa'=>'linear-gradient(135deg,#3d1a6b,#6b2a8a)'];
            $typeIcons = ['transfer'=>'bi-car-front-fill','charter'=>'bi-airplane-fill','leisure'=>'bi-water','tour'=>'bi-map-fill','hotel'=>'bi-building','visa'=>'bi-passport'];
            $heroBg = $typeColors[$item->product_type] ?? 'linear-gradient(135deg,#0f2444,#1a3c6b)';
            $heroIcon = $typeIcons[$item->product_type] ?? 'bi-grid';
        @endphp
        <div class="hero-placeholder" style="background:{{ $heroBg }};">
            <i class="bi {{ $heroIcon }}"></i>
        </div>
    @endif
</div>

{{-- Ana Layout --}}
<div style="background:#fff;" class="product-mobile-pad">
    <div class="product-layout">

        {{-- SOL: İçerik --}}
        <div>
            {{-- Kategori badge --}}
            @if($item->category)
            <div class="product-category-badge">
                <i class="bi {{ $item->category->icon ?? 'bi-grid' }}"></i>
                {{ $item->category->name }}
            </div>
            @endif

            {{-- Başlık --}}
            <h1 class="product-title">{{ $item->title }}</h1>

            {{-- Meta satırı --}}
            <div class="product-meta-row">
                {{-- Puan --}}
                @if($item->rating_avg > 0)
                <div style="display:flex;align-items:center;gap:6px;">
                    <span class="stars">{!! str_repeat('★', (int)floor($item->rating_avg)) . ($item->rating_avg - floor($item->rating_avg) >= 0.5 ? '★' : '') . str_repeat('☆', 5 - (int)ceil($item->rating_avg)) !!}</span>
                    <span class="rating-num">{{ number_format($item->rating_avg, 1) }}</span>
                    @if($item->review_count > 0)
                    <span style="color:#718096;">({{ number_format($item->review_count, 0, ',', '.') }} değerlendirme)</span>
                    @endif
                </div>
                @endif

                {{-- Şehir --}}
                @if($item->destination_city)
                <div class="meta-pill">
                    <i class="bi bi-geo-alt-fill"></i>
                    {{ $item->destination_city }}@if($item->destination_country && $item->destination_country !== 'Türkiye'), {{ $item->destination_country }}@endif
                </div>
                @endif

                {{-- Süre --}}
                @if($item->duration_days || $item->duration_hours)
                <div class="meta-pill">
                    <i class="bi bi-clock"></i>
                    @if($item->duration_days){{ $item->duration_days }} gün@endif
                    @if($item->duration_days && $item->duration_hours) @endif
                    @if($item->duration_hours){{ $item->duration_hours }} saat@endif
                </div>
                @endif

                {{-- Kişi --}}
                @if($item->min_pax)
                <div class="meta-pill">
                    <i class="bi bi-people-fill"></i>
                    @if($item->max_pax){{ $item->min_pax }}–{{ $item->max_pax }} kişi
                    @else{{ $item->min_pax }}+ kişi
                    @endif
                </div>
                @endif
            </div>

            {{-- Kısa açıklama --}}
            @if($item->short_desc)
            <p style="font-size:1.05rem;color:#4a5568;line-height:1.7;margin-bottom:0;">
                {{ $item->short_desc }}
            </p>
            @endif

            {{-- Öne çıkan özellikler --}}
            <div class="product-section-title">Bu Deneyimde Neler Var?</div>
            <div class="product-highlights">
                @if($item->duration_hours || $item->duration_days)
                <div class="product-highlight-item">
                    <i class="bi bi-clock-fill"></i>
                    <div>
                        <strong>Süre</strong>
                        <span>@if($item->duration_days){{ $item->duration_days }} gün@endif @if($item->duration_hours){{ $item->duration_hours }} saat@endif</span>
                    </div>
                </div>
                @endif
                @if($item->destination_city)
                <div class="product-highlight-item">
                    <i class="bi bi-geo-alt-fill"></i>
                    <div>
                        <strong>Lokasyon</strong>
                        <span>{{ $item->destination_city }}</span>
                    </div>
                </div>
                @endif
                @if($item->min_pax)
                <div class="product-highlight-item">
                    <i class="bi bi-people-fill"></i>
                    <div>
                        <strong>Grup Büyüklüğü</strong>
                        <span>@if($item->max_pax){{ $item->min_pax }} ile {{ $item->max_pax }} kişi arası@else{{ $item->min_pax }} kişiden itibaren@endif</span>
                    </div>
                </div>
                @endif
                <div class="product-highlight-item">
                    <i class="bi bi-translate"></i>
                    <div>
                        <strong>Dil</strong>
                        <span>Türkçe, İngilizce</span>
                    </div>
                </div>
                <div class="product-highlight-item">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <div>
                        <strong>İptal Politikası</strong>
                        <span>24 saat öncesine kadar ücretsiz iptal</span>
                    </div>
                </div>
                <div class="product-highlight-item">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <div>
                        <strong>Onay</strong>
                        <span>Anında onay</span>
                    </div>
                </div>
            </div>

            {{-- Tam açıklama --}}
            @if($item->full_desc)
            <div class="product-section-title">Detaylı Açıklama</div>
            <div class="product-desc">{!! nl2br(e($item->full_desc)) !!}</div>
            @endif

            {{-- Neden biz --}}
            <div class="product-section-title">Neden Bizi Tercih Etmelisiniz?</div>
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach([
                    ['bi-shield-check','Güvenli Ödeme','256-bit SSL şifreleme ile koruma altında'],
                    ['bi-headset','7/24 Destek','Seyahatiniz boyunca her an yanınızdayız'],
                    ['bi-award','Doğrulanmış Tedarikçiler','Tüm hizmet sağlayıcılar denetlenmektedir'],
                ] as [$ico,$t,$s])
                <div style="display:flex;align-items:center;gap:12px;font-size:.9rem;">
                    <i class="bi {{ $ico }}" style="font-size:1.3rem;color:#1a3c6b;flex-shrink:0;"></i>
                    <div><strong style="color:#1a202c;">{{ $t }}</strong><br><span style="color:#718096;">{{ $s }}</span></div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- SAĞ: Fiyat Kartı --}}
        <div>
            {{-- Aciliyet sinyali --}}
            @if($item->review_count > 10)
            <div style="background:#fff7ed;border:1px solid #fbd38d;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:.83rem;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-fire" style="color:#dd6b20;font-size:1rem;"></i>
                <span style="color:#744210;"><strong>Popüler:</strong> Bu hizmet bu ay {{ $item->review_count * 3 }}+ kez rezerve edildi.</span>
            </div>
            @endif
            <div class="price-card">
                @if($item->pricing_type === 'fixed' && $item->base_price)
                    <div class="pc-label">Başlangıç fiyatı</div>
                    <div class="pc-price">
                        {{ number_format($item->base_price, 0, ',', '.') }}
                        <span class="pc-currency">{{ $item->currency }}</span>
                    </div>
                    <div class="pc-per">kişi başı</div>

                    <div class="pc-divider"></div>

                    <a href="{{ route('b2c.auth.register') }}" class="pc-cta">
                        <i class="bi bi-calendar-check me-2"></i>Rezervasyon Yap
                    </a>
                    <a href="{{ route('b2c.iletisim') }}" class="pc-secondary">
                        <i class="bi bi-chat-dots me-2"></i>Soru Sor
                    </a>

                @elseif($item->pricing_type === 'quote')
                    <div class="pc-quote-box">
                        <i class="bi bi-info-circle-fill me-2" style="color:#1a3c6b;"></i>
                        Bu hizmet için kişiye özel fiyatlandırma yapılmaktadır. Formu doldurun, 4 saat içinde size özel fiyat iletilsin.
                    </div>
                    <a href="{{ route('b2c.iletisim') }}" class="pc-cta">
                        <i class="bi bi-send me-2"></i>Fiyat Al
                    </a>

                @else
                    <div class="pc-quote-box">
                        <i class="bi bi-telephone-fill me-2" style="color:#1a3c6b;"></i>
                        Grup talebinizi oluşturun, ekibimiz en kısa sürede size ulaşsın.
                    </div>
                    <a href="{{ route('b2c.iletisim') }}" class="pc-cta">
                        <i class="bi bi-clipboard-plus me-2"></i>Talep Oluştur
                    </a>
                @endif

                <div class="pc-divider"></div>

                <div class="pc-trust">
                    <i class="bi bi-check-circle-fill"></i> Ücretsiz iptal (24 saat öncesine kadar)
                </div>
                <div class="pc-trust">
                    <i class="bi bi-check-circle-fill"></i> Anında onay
                </div>
                <div class="pc-trust">
                    <i class="bi bi-check-circle-fill"></i> Güvenli ödeme
                </div>

                <div class="pc-divider"></div>
                <div style="display:flex;align-items:center;gap:10px;padding:4px 0;">
                    <div style="width:38px;height:38px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-building" style="color:#1a3c6b;font-size:1rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.82rem;font-weight:700;color:#1a202c;">
                            {{ $item->supplier->name ?? 'Grup Rezervasyonları' }}
                        </div>
                        <div style="font-size:.75rem;color:#718096;display:flex;align-items:center;gap:4px;">
                            <i class="bi bi-patch-check-fill" style="color:#48bb78;"></i> Doğrulanmış Tedarikçi
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /product-layout --}}
</div>

{{-- MOBİL STICKY CTA BAR --}}
<div class="mobile-sticky-cta">
    @if($item->pricing_type === 'fixed' && $item->base_price)
        <div>
            <div class="msc-label">Başlangıç fiyatı</div>
            <div class="msc-price">{{ number_format($item->base_price, 0, ',', '.') }} {{ $item->currency }}</div>
        </div>
        <a href="{{ route('b2c.auth.register') }}" class="msc-btn">
            <i class="bi bi-calendar-check me-1"></i>Rezervasyon Yap
        </a>
    @else
        <div>
            <div class="msc-label">Kişiye özel fiyat</div>
            <div class="msc-price" style="font-size:.95rem;color:#718096;">Fiyat sorun</div>
        </div>
        <a href="{{ route('b2c.iletisim') }}" class="msc-btn">
            <i class="bi bi-send me-1"></i>Ücretsiz Teklif Al
        </a>
    @endif
</div>

{{-- Benzer Ürünler --}}
@if($relatedItems->isNotEmpty())
<section style="padding:3rem 0;background:#f8f9fc;border-top:1px solid #e5e5e5;">
    <div class="container" style="max-width:1280px;">
        <div class="gyg-section-head">
            <div>
                <h2 class="gr-section-title" style="color:#1a202c;">Benzer Deneyimler</h2>
                <p class="gr-section-subtitle">Bunları da beğenebilirsiniz</p>
            </div>
            @if($item->category)
            <a href="{{ route('b2c.catalog.category', $item->category->slug) }}" class="gyg-see-all">Tümünü Gör →</a>
            @endif
        </div>
        <div class="related-grid">
            @foreach($relatedItems as $rel)
            @include('b2c.home._product-card', ['item' => $rel])
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection

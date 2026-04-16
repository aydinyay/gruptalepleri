@extends('b2c.layouts.app')
@section('title', $item->meta_title ?? $item->title)
@section('content')
<style>
.prd-hero{position:relative;height:400px;overflow:hidden;background:linear-gradient(135deg,#0f2444,#1a3c6b)}
.prd-hero img{width:100%;height:100%;object-fit:cover}
.prd-wrap{max-width:1280px;margin:0 auto;padding:32px 24px 64px;display:grid;grid-template-columns:1fr 360px;gap:40px}
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
.tr-vehicle-price .amount{font-size:1.3rem;font-weight:800;color:#1a202c}
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
<span class="prd-pill"><i class="bi bi-people-fill"></i> {{ $item->min_pax }}+ kişi</span>
@endif
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
<div style="font-size:.95rem;color:#4a5568;line-height:1.8;">{!! nl2br(e($item->full_desc)) !!}</div>
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

{{-- Transfer değil veya canlı fiyat yok --}}
@elseif($item->pricing_type === 'fixed' && $item->base_price)
<div class="pc-label">Başlangıç fiyatı</div>
<div class="pc-price">{{ number_format($item->base_price,0,',','.') }} <span style="font-size:1rem;">{{ $item->currency }}</span></div>
<div class="pc-per">kişi başı</div>

<form method="POST" action="{{ route('b2c.cart.add') }}" style="margin:12px 0 4px;">
    @csrf
    <input type="hidden" name="catalog_item_id" value="{{ $item->id }}">
    <input type="hidden" name="checkout" value="1">

    <div style="margin-bottom:10px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:5px;">Hizmet Tarihi</label>
        <input type="date" name="service_date"
               min="{{ now()->addDay()->format('Y-m-d') }}"
               style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.9rem;color:#1a202c;background:#fafbfc;">
    </div>

    <div style="margin-bottom:14px;">
        <label style="display:block;font-size:.82rem;font-weight:600;color:#4a5568;margin-bottom:5px;">Kişi Sayısı</label>
        <div style="display:flex;align-items:center;gap:10px;">
            <button type="button"
                    onclick="var i=document.getElementById('show-pax');if(+i.value>1)i.value=+i.value-1;"
                    style="width:34px;height:34px;border-radius:50%;border:1px solid #e2e8f0;background:#f7f8fc;font-size:1.1rem;cursor:pointer;line-height:1;">−</button>
            <input type="number" name="pax_count" id="show-pax" value="2" min="1" max="500"
                   style="width:60px;text-align:center;padding:8px 4px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.95rem;" readonly>
            <button type="button"
                    onclick="var i=document.getElementById('show-pax');if(+i.value<500)i.value=+i.value+1;"
                    style="width:34px;height:34px;border-radius:50%;border:1px solid #e2e8f0;background:#f7f8fc;font-size:1.1rem;cursor:pointer;line-height:1;">+</button>
        </div>
    </div>

    <button type="submit" class="pc-cta"><i class="bi bi-calendar-check me-2"></i>Rezervasyon Yap</button>
</form>
<a href="{{ route('b2c.iletisim') }}" class="pc-sec"><i class="bi bi-chat-dots me-2"></i>Soru Sor</a>
@elseif($item->pricing_type === 'quote')
<div class="pc-qbox"><i class="bi bi-info-circle-fill me-2" style="color:#1a3c6b;"></i>Kişiye özel fiyatlandırma. 4 saat içinde size özel fiyat iletilsin.</div>
<a href="{{ route('b2c.iletisim') }}" class="pc-cta"><i class="bi bi-send me-2"></i>Fiyat Al</a>
@else
<div class="pc-qbox"><i class="bi bi-telephone-fill me-2" style="color:#1a3c6b;"></i>Grup talebinizi oluşturun.</div>
<a href="{{ route('b2c.iletisim') }}" class="pc-cta"><i class="bi bi-clipboard-plus me-2"></i>Talep Oluştur</a>
@endif

<div class="pc-div"></div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Ücretsiz iptal (24 saat öncesine kadar)</div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Anında onay</div>
<div class="pc-trust"><i class="bi bi-check-circle-fill"></i> Güvenli ödeme</div>
<div class="pc-div"></div>
<div style="display:flex;align-items:center;gap:10px;">
<div style="width:36px;height:36px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-building" style="color:#1a3c6b;"></i></div>
<div>
<div style="font-size:.82rem;font-weight:700;color:#1a202c;">{{ $item->supplier?->name ?? 'Grup Rezervasyonları' }}</div>
<div style="font-size:.75rem;color:#718096;"><i class="bi bi-patch-check-fill" style="color:#48bb78;"></i> Doğrulanmış Tedarikçi</div>
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

@endsection

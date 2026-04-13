<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $package->name_tr }} — Yat Charter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root{--gy:#ff5533;--gy-d:#e8411d;--txt:#1a1a1a;--muted:#595959;--brd:#e8e8e8;--bg:#f5f5f5;--card:#fff;--star:#f5a623;}
        html[data-theme="dark"]{--txt:#f0f0f0;--muted:#b0b0b0;--brd:#333;--bg:#0f1520;--card:#1a2235;}
        body{background:var(--bg);color:var(--txt);}
        /* breadcrumb */
        .yc-bc{background:var(--card);border-bottom:1px solid var(--brd);padding:.6rem 0;font-size:.82rem;color:var(--muted);}
        .yc-bc a{color:var(--muted);text-decoration:none;}.yc-bc a:hover{color:var(--gy);}
        /* gallery */
        .yc-gallery{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:1fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;background:#1a3a6e;}
        .yc-gallery-main{grid-row:1/3;overflow:hidden;}.yc-gallery-main img,.yc-gallery-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
        .yc-gallery-thumb{overflow:hidden;cursor:pointer;position:relative;}
        .yc-gallery-thumb:last-child .yc-gallery-more{position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;}
        @media(max-width:767px){.yc-gallery{grid-template-columns:1fr;grid-template-rows:260px;}.yc-gallery-thumb{display:none;}}
        /* layout */
        .yc-layout{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:2rem;align-items:start;padding:1.5rem 0 3rem;}
        @media(max-width:991px){.yc-layout{grid-template-columns:1fr;}}
        /* panel */
        .yc-panel{position:sticky;top:72px;background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:1.25rem;box-shadow:0 4px 24px rgba(0,0,0,.08);}
        .yc-panel-price{font-size:1.6rem;font-weight:800;color:var(--gy);}
        .yc-panel-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:.25rem;}
        .yc-panel-select,.yc-panel-input{width:100%;padding:.55rem .7rem;border-radius:8px;border:1px solid var(--brd);background:var(--card);color:var(--txt);font-size:.9rem;margin-bottom:.8rem;}
        .yc-total-row{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--brd);padding-top:.7rem;margin-top:.5rem;}
        .yc-total-label{font-size:.85rem;color:var(--muted);}
        .yc-total-amount{font-size:1.15rem;font-weight:800;color:var(--txt);}
        .yc-btn-book{width:100%;padding:.78rem;border-radius:999px;background:var(--gy);border:none;color:#fff;font-size:1rem;font-weight:800;cursor:pointer;transition:background .15s;margin-top:.5rem;}
        .yc-btn-book:hover{background:var(--gy-d);}
        /* sections */
        .yc-sec{margin-bottom:1.8rem;}
        .yc-sec-title{font-size:1.15rem;font-weight:800;margin-bottom:.8rem;padding-bottom:.5rem;border-bottom:1px solid var(--brd);}
        .yc-check-list{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:1fr 1fr;gap:.4rem .8rem;}
        @media(max-width:575px){.yc-check-list{grid-template-columns:1fr;}}
        .yc-check-list li{font-size:.9rem;display:flex;align-items:flex-start;gap:.4rem;}
        .yc-x-list{list-style:none;padding:0;margin:0;}
        .yc-x-list li{font-size:.9rem;color:var(--muted);display:flex;align-items:flex-start;gap:.4rem;margin-bottom:.3rem;}
        /* timeline */
        .yc-timeline{list-style:none;padding:0;margin:0;position:relative;}
        .yc-timeline::before{content:'';position:absolute;left:52px;top:0;bottom:0;width:2px;background:var(--brd);}
        .yc-timeline li{display:flex;gap:1rem;margin-bottom:1rem;position:relative;}
        .yc-tl-time{min-width:44px;text-align:right;font-weight:700;font-size:.82rem;color:var(--muted);padding-top:.1rem;}
        .yc-tl-dot{width:10px;height:10px;border-radius:50%;background:var(--gy);margin-top:.3rem;flex-shrink:0;position:relative;z-index:1;}
        .yc-tl-text{font-size:.9rem;}
        /* pkg tabs */
        .yc-pkg-tabs{display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap;}
        .yc-pkg-tab{padding:.4rem .9rem;border-radius:999px;border:1.5px solid var(--brd);font-size:.82rem;font-weight:700;cursor:pointer;background:var(--card);color:var(--txt);text-decoration:none;transition:all .15s;}
        .yc-pkg-tab.active,.yc-pkg-tab:hover{border-color:var(--gy);background:rgba(255,85,51,.08);color:var(--gy);}
        /* info tags */
        .yc-info-tag{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid var(--brd);padding:.45rem .7rem;font-size:.82rem;background:var(--card);}
        /* transfer collapse */
        .yc-transfer-section{border:1px solid var(--brd);border-radius:8px;overflow:hidden;}
        .yc-transfer-toggle{display:flex;align-items:center;gap:.5rem;padding:.7rem 1rem;cursor:pointer;font-weight:600;font-size:.88rem;background:var(--card);}
        .yc-transfer-body{padding:1rem;border-top:1px solid var(--brd);display:none;}
        .yc-transfer-body.show{display:block;}
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="yacht-charter" />

{{-- Breadcrumb --}}
<div class="yc-bc">
    <div class="container">
        <a href="{{ route('acente.yacht-charter.catalog') }}">Yat Charter</a>
        <span class="mx-1">/</span>
        <span>{{ $package->name_tr }}</span>
    </div>
</div>

@php
    $heroImg = $package->hero_image_url ?: 'https://images.pexels.com/photos/1001682/pexels-photo-1001682.jpeg?auto=compress&cs=tinysrgb&w=1200';
    $galleryImgs = $galleryPhotos->isNotEmpty()
        ? $galleryPhotos->take(3)->values()
        : $mediaAssets->where('media_type','photo')->take(3)->values();
    $timeline = is_array($package->timeline_tr) ? $package->timeline_tr : json_decode($package->timeline_tr ?? '[]', true);
    $importantNotes = is_array($package->important_notes_tr) ? $package->important_notes_tr : json_decode($package->important_notes_tr ?? '[]', true);
    $departureTimes = is_array($package->departure_times) ? $package->departure_times : json_decode($package->departure_times ?? '[]', true);
    $pricePerHour = (float)($package->base_price_per_person ?? 0);
    $currency = $package->currency ?: 'EUR';
@endphp

@php
    $lbItems = [['url' => $heroImg, 'type' => 'photo', 'alt' => $package->name_tr]];
    foreach ($galleryPhotos as $lbA) {
        $lbItems[] = ['url' => $lbA->resolvedUrl(), 'type' => $lbA->media_type, 'alt' => $lbA->title_tr ?? ''];
    }
@endphp

{{-- Lightbox --}}
<div id="ycLb" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.93);z-index:9999;align-items:center;justify-content:center;">
    <button onclick="ycLbClose()" style="position:fixed;top:14px;right:18px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;line-height:1;">✕</button>
    <button onclick="ycLbPrev()" style="position:fixed;left:10px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:2rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;">‹</button>
    <div id="ycLbMedia" style="max-width:92vw;max-height:88vh;display:flex;align-items:center;justify-content:center;"></div>
    <button onclick="ycLbNext()" style="position:fixed;right:10px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:2rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;">›</button>
    <div id="ycLbCount" style="position:fixed;bottom:14px;left:50%;transform:translateX(-50%);color:#fff;font-size:.82rem;background:rgba(0,0,0,.5);padding:.2rem .55rem;border-radius:999px;"></div>
</div>

<div class="container mt-3">
    {{-- Gallery --}}
    <div class="yc-gallery mb-3" style="cursor:pointer;">
        <div class="yc-gallery-main" onclick="ycLbOpen(0)">
            <img src="{{ $heroImg }}" alt="{{ $package->name_tr }}" loading="lazy">
        </div>
        @foreach($galleryImgs->take(2) as $i => $asset)
            <div class="yc-gallery-thumb" onclick="ycLbOpen({{ $i + 1 }})">
                <img src="{{ $asset->resolvedUrl() }}" alt="{{ $asset->title_tr }}" loading="lazy">
                @if($i === 1 && count($lbItems) > 3)
                    <div class="yc-gallery-more">+{{ count($lbItems) - 3 }} fotoğraf</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="yc-layout">
        {{-- Sol: İçerik --}}
        <div>
            {{-- Başlık --}}
            <div class="mb-3">
                @if($package->badge_text)
                    <span class="badge text-bg-warning mb-2" style="font-size:.75rem;">{{ $package->badge_text }}</span>
                @endif
                <h1 style="font-size:1.7rem;font-weight:800;line-height:1.2;margin:0 0 .5rem;">{{ $package->name_tr }}</h1>
                <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:.88rem;color:var(--muted);">
                    <span style="color:var(--star);">
                        @for($s=1;$s<=5;$s++)<i class="fas fa-star fa-xs"></i>@endfor
                    </span>
                    <strong style="color:var(--txt);">{{ $package->rating ?? '5.0' }}</strong>
                    <span>{{ number_format((int)($package->review_count ?? 46)) }} yorum</span>
                    @if($package->duration_hours)
                        <span><i class="fas fa-clock fa-xs me-1"></i>min {{ number_format((float)$package->duration_hours,0) }} saat</span>
                    @endif
                    @if($package->max_pax)
                        <span><i class="fas fa-users fa-xs me-1"></i>maks {{ $package->max_pax }} kişi</span>
                    @endif
                </div>
            </div>

            {{-- Öne çıkan bilgiler --}}
            <div class="d-flex gap-2 flex-wrap mb-4">
                <span class="yc-info-tag"><i class="fas fa-shield-alt fa-xs text-success"></i> Ücretsiz iptal</span>
                <span class="yc-info-tag"><i class="fas fa-car fa-xs text-primary"></i> Transfer mevcut</span>
                <span class="yc-info-tag"><i class="fas fa-anchor fa-xs text-primary"></i> Özel grup</span>
                <span class="yc-info-tag"><i class="fas fa-route fa-xs text-warning"></i> Esnek güzergah</span>
            </div>

            {{-- Paket seçici --}}
            <div class="yc-sec">
                <div class="yc-sec-title">Yat tipi seçin</div>
                <div class="yc-pkg-tabs">
                    @foreach($allPackages as $p)
                        <a href="{{ route('acente.yacht-charter.show-product', $p->code) }}"
                           class="yc-pkg-tab {{ $p->id === $package->id ? 'active' : '' }}">
                            {{ $p->name_tr }}
                        </a>
                    @endforeach
                </div>
                <div class="p-3 rounded-3" style="background:rgba(255,85,51,.06);border:1px solid rgba(255,85,51,.2);">
                    <div class="fw-bold mb-1">{{ $package->name_tr }}</div>
                    <div style="font-size:.88rem;color:var(--muted);">{{ $package->summary_tr }}</div>
                    <div class="mt-2 d-flex align-items-center gap-3 flex-wrap">
                        @if($package->original_price_per_person)
                            <span style="font-size:.75rem;color:var(--muted);">Önerilen satış: <strong>{{ number_format((float)$package->original_price_per_person,0,',','.') }} {{ $currency }}</strong>/saat</span>
                        @endif
                        <span style="font-size:1.15rem;font-weight:800;color:var(--gy);">{{ number_format($pricePerHour,0,',','.') }} {{ $currency }}<span style="font-size:.78rem;color:var(--muted);font-weight:400;">/saat (B2B)</span></span>
                    </div>
                </div>
            </div>

            {{-- Açıklama --}}
            @if($package->long_description_tr)
            <div class="yc-sec">
                <div class="yc-sec-title">Hakkında</div>
                <div style="font-size:.94rem;line-height:1.75;white-space:pre-line;">{{ $package->long_description_tr }}</div>
            </div>
            @endif

            {{-- Dahil olanlar --}}
            @if(!empty($package->includes_tr))
            <div class="yc-sec">
                <div class="yc-sec-title">Dahil olanlar</div>
                <ul class="yc-check-list">
                    @foreach($package->includes_tr as $inc)
                        <li><i class="fas fa-check text-success fa-xs" style="margin-top:.25rem;flex-shrink:0;"></i>{{ $inc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Hariç olanlar --}}
            @if(!empty($package->excludes_tr))
            <div class="yc-sec">
                <div class="yc-sec-title">Dahil olmayanlar</div>
                <ul class="yc-x-list">
                    @foreach($package->excludes_tr as $exc)
                        <li><i class="fas fa-times text-danger fa-xs" style="margin-top:.25rem;flex-shrink:0;"></i>{{ $exc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Program / Timeline --}}
            @if(!empty($timeline))
            <div class="yc-sec">
                <div class="yc-sec-title">Program</div>
                <ul class="yc-timeline">
                    @foreach($timeline as $item)
                        <li>
                            <div class="yc-tl-time">{{ $item['time'] ?? '' }}</div>
                            <div class="yc-tl-dot"></div>
                            <div class="yc-tl-text">
                                <div class="fw-bold" style="font-size:.9rem;">{{ $item['title'] ?? '' }}</div>
                                @if(!empty($item['desc']))<div style="font-size:.84rem;color:var(--muted);">{{ $item['desc'] }}</div>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Önemli bilgiler --}}
            @if(!empty($importantNotes) || $package->cancellation_policy_tr)
            <div class="yc-sec">
                <div class="yc-sec-title">Önemli bilgiler</div>
                @if(!empty($importantNotes))
                    <ul class="yc-x-list">
                        @foreach($importantNotes as $note)
                            <li><i class="fas fa-info-circle text-primary fa-xs" style="margin-top:.25rem;flex-shrink:0;"></i>{{ $note }}</li>
                        @endforeach
                    </ul>
                @endif
                @if($package->cancellation_policy_tr)
                    <div class="mt-2 p-3 rounded-3" style="background:rgba(18,163,84,.07);border:1px solid rgba(18,163,84,.2);font-size:.88rem;">
                        <i class="fas fa-check-circle text-success me-1"></i>{{ $package->cancellation_policy_tr }}
                    </div>
                @endif
            </div>
            @endif
        </div>

        {{-- Sağ: Rezervasyon paneli --}}
        <div>
            <div class="yc-panel">
                <div class="yc-panel-label">B2B fiyat</div>
                <div class="yc-panel-price" id="displayPrice">{{ number_format($pricePerHour,0,',','.') }} {{ $currency }}</div>
                <div style="font-size:.78rem;color:var(--muted);margin-bottom:1rem;">/ saat · grup başına</div>

                @if(session('success'))
                    <div class="alert alert-success py-2 px-3" style="font-size:.85rem;">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2 px-3" style="font-size:.85rem;">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger py-2 px-3" style="font-size:.82rem;">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('acente.yacht-charter.book', $package->code) }}">
                    @csrf

                    {{-- Tarih --}}
                    <div class="yc-panel-label">Tarih</div>
                    <input type="date" name="service_date" class="yc-panel-input"
                           min="{{ date('Y-m-d') }}" value="{{ old('service_date', date('Y-m-d', strtotime('+1 day'))) }}" required>

                    {{-- Kişi sayısı --}}
                    <div class="yc-panel-label">Kişi sayısı</div>
                    <input type="number" name="guest_count" class="yc-panel-input"
                           min="1" max="{{ $package->max_pax ?? 500 }}" value="{{ old('guest_count', 10) }}" required>

                    {{-- Süre --}}
                    <div class="yc-panel-label">Süre</div>
                    <select name="duration_hours" id="durationSelect" class="yc-panel-select" required>
                        @foreach([1,2,3,4,5,6,8] as $h)
                            <option value="{{ $h }}" {{ old('duration_hours','2') == $h ? 'selected' : '' }}>
                                {{ $h }} saat
                                ({{ number_format($pricePerHour * $h, 0, ',', '.') }} {{ $currency }})
                            </option>
                        @endforeach
                    </select>

                    {{-- Kalkış saati (opsiyonel) --}}
                    <div class="yc-panel-label">Kalkış saati <small style="font-weight:400;text-transform:none;">(opsiyonel)</small></div>
                    <input type="time" name="start_time" class="yc-panel-input" value="{{ old('start_time') }}">

                    {{-- Etkinlik tipi --}}
                    <div class="yc-panel-label">Etkinlik tipi <small style="font-weight:400;text-transform:none;">(opsiyonel)</small></div>
                    <select name="event_type" class="yc-panel-select">
                        <option value="">Belirtilmedi</option>
                        <option value="özel tur" @selected(old('event_type')=='özel tur')>Özel Tur</option>
                        <option value="doğum günü" @selected(old('event_type')=='doğum günü')>Doğum Günü</option>
                        <option value="evlilik teklifi" @selected(old('event_type')=='evlilik teklifi')>Evlilik Teklifi</option>
                        <option value="düğün" @selected(old('event_type')=='düğün')>Düğün / Nişan</option>
                        <option value="kurumsal" @selected(old('event_type')=='kurumsal')>Kurumsal Etkinlik</option>
                        <option value="aile turu" @selected(old('event_type')=='aile turu')>Aile Turu</option>
                        <option value="arkadaş turu" @selected(old('event_type')=='arkadaş turu')>Arkadaş Turu</option>
                    </select>

                    {{-- Yetkili bilgileri --}}
                    <div class="yc-panel-label">Yetkili adı</div>
                    <input type="text" name="guest_name" class="yc-panel-input" placeholder="Ad Soyad" value="{{ old('guest_name') }}" required>

                    <div class="yc-panel-label">Telefon / WhatsApp</div>
                    <input type="text" name="guest_phone" class="yc-panel-input" placeholder="+90 5xx xxx xx xx" value="{{ old('guest_phone') }}" required>

                    {{-- Transfer --}}
                    <div class="yc-transfer-section mb-3">
                        <label class="yc-transfer-toggle" id="transferToggle">
                            <input type="checkbox" name="transfer_required" value="1" id="transferCheck"
                                   {{ old('transfer_required') ? 'checked' : '' }}>
                            <span>Otel transferi ekle</span>
                            <i class="fas fa-chevron-down fa-xs ms-auto"></i>
                        </label>
                        <div class="yc-transfer-body {{ old('transfer_required') ? 'show' : '' }}" id="transferBody">
                            <input type="text" name="hotel_name" class="yc-panel-input" placeholder="Otel adı" value="{{ old('hotel_name') }}">
                            <select name="transfer_region" class="yc-panel-select">
                                <option value="">Bölge seçin</option>
                                @foreach(['Sultanahmet','Taksim','Şişli','Beşiktaş','Levent','Kadıköy','Diğer'] as $r)
                                    <option value="{{ $r }}" @selected(old('transfer_region')===$r)>{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Notlar --}}
                    <div class="yc-panel-label">Notlar <small style="font-weight:400;text-transform:none;">(opsiyonel)</small></div>
                    <textarea name="notes" class="yc-panel-input" rows="2" placeholder="Özel istek, güzergah tercihi...">{{ old('notes') }}</textarea>

                    {{-- Toplam --}}
                    <div class="yc-total-row">
                        <span class="yc-total-label">Toplam B2B</span>
                        <span class="yc-total-amount" id="totalDisplay">
                            {{ number_format($pricePerHour * 2, 0, ',', '.') }} {{ $currency }}
                        </span>
                    </div>
                    @if($package->original_price_per_person)
                        <div class="text-end" style="font-size:.74rem;color:var(--muted);margin-top:.2rem;">
                            Önerilen satış: <strong>{{ number_format((float)$package->original_price_per_person * 2, 0, ',', '.') }} {{ $currency }}</strong> (2 saat)
                        </div>
                    @endif

                    <button type="submit" class="yc-btn-book">
                        <i class="fas fa-anchor me-2"></i>Rezervasyon Oluştur
                    </button>
                    <div class="text-center mt-2" style="font-size:.74rem;color:var(--muted);">
                        <i class="fas fa-shield-alt me-1 text-success"></i>Güvenli ödeme · Ücretsiz iptal
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('acente.partials.theme-script')
<script>
// ── Lightbox ──────────────────────────────────────────────────────────
const ycLbImages = @json($lbItems);
let ycLbIdx = 0;
const ycLbEl    = document.getElementById('ycLb');
const ycLbMedia = document.getElementById('ycLbMedia');
const ycLbCount = document.getElementById('ycLbCount');

function ycLbOpen(i) {
    ycLbIdx = i;
    ycLbRender();
    ycLbEl.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function ycLbClose() {
    ycLbEl.style.display = 'none';
    document.body.style.overflow = '';
    ycLbMedia.innerHTML = '';
}
function ycLbRender() {
    const item = ycLbImages[ycLbIdx];
    if (item.type === 'video') {
        ycLbMedia.innerHTML = `<video src="${item.url}" controls autoplay playsinline style="max-width:92vw;max-height:88vh;border-radius:8px;"></video>`;
    } else {
        ycLbMedia.innerHTML = `<img src="${item.url}" alt="${item.alt}" style="max-width:92vw;max-height:88vh;object-fit:contain;border-radius:8px;">`;
    }
    ycLbCount.textContent = (ycLbIdx + 1) + ' / ' + ycLbImages.length;
}
function ycLbPrev() { ycLbIdx = (ycLbIdx - 1 + ycLbImages.length) % ycLbImages.length; ycLbRender(); }
function ycLbNext() { ycLbIdx = (ycLbIdx + 1) % ycLbImages.length; ycLbRender(); }
ycLbEl.addEventListener('click', e => { if (e.target === ycLbEl) ycLbClose(); });
document.addEventListener('keydown', e => {
    if (ycLbEl.style.display === 'none') return;
    if (e.key === 'Escape') ycLbClose();
    if (e.key === 'ArrowLeft') ycLbPrev();
    if (e.key === 'ArrowRight') ycLbNext();
});

// ── Fiyat hesap ───────────────────────────────────────────────────────
const pricePerHour = {{ $pricePerHour }};
const currency = '{{ $currency }}';

const durationSelect = document.getElementById('durationSelect');
const totalDisplay   = document.getElementById('totalDisplay');

function updateTotal() {
    const hours = parseInt(durationSelect.value) || 1;
    const total = pricePerHour * hours;
    totalDisplay.textContent = total.toLocaleString('tr-TR', {maximumFractionDigits:0}) + ' ' + currency;
}

durationSelect.addEventListener('change', updateTotal);
updateTotal();

// Transfer toggle
const transferCheck = document.getElementById('transferCheck');
const transferBody  = document.getElementById('transferBody');
transferCheck.addEventListener('change', () => {
    transferBody.classList.toggle('show', transferCheck.checked);
});
</script>
@include('acente.partials.leisure-footer')
</body>
</html>

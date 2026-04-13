<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $package->name_tr }} — Dinner Cruise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root{--gy:#ff5533;--gy-dark:#e8411d;--txt:#1a1a1a;--muted:#595959;--brd:#e8e8e8;--bg:#f5f5f5;--card:#fff;--star:#f5a623;}
        html[data-theme="dark"]{--txt:#f0f0f0;--muted:#b0b0b0;--brd:#333;--bg:#0f1520;--card:#1a2235;}
        body{background:var(--bg);color:var(--txt);}
        /* breadcrumb */
        .dc-breadcrumb{background:var(--card);border-bottom:1px solid var(--brd);padding:.6rem 0;font-size:.82rem;color:var(--muted);}
        .dc-breadcrumb a{color:var(--muted);text-decoration:none;}.dc-breadcrumb a:hover{color:var(--gy);}
        /* gallery */
        .dc-gallery{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:1fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;background:#ccc;}
        .dc-gallery-main{grid-row:1/3;overflow:hidden;}.dc-gallery-main img,.dc-gallery-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
        .dc-gallery-thumb{overflow:hidden;cursor:pointer;position:relative;}
        .dc-gallery-thumb:last-child .dc-gallery-more{position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;}
        @media(max-width:767px){.dc-gallery{grid-template-columns:1fr;grid-template-rows:260px;}.dc-gallery-thumb{display:none;}}
        /* layout */
        .dc-layout{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:2rem;align-items:start;padding:1.5rem 0 3rem;}
        @media(max-width:991px){.dc-layout{grid-template-columns:1fr;}}
        /* sticky panel */
        .dc-panel{position:sticky;top:72px;background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:1.25rem;box-shadow:0 4px 24px rgba(0,0,0,.08);}
        .dc-panel-price{font-size:1.6rem;font-weight:800;color:var(--gy);}
        .dc-panel-old{font-size:.9rem;color:var(--muted);text-decoration:line-through;}
        .dc-panel-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:.25rem;}
        .dc-panel-select{width:100%;padding:.55rem .7rem;border-radius:8px;border:1px solid var(--brd);background:var(--card);color:var(--txt);font-size:.9rem;margin-bottom:.8rem;}
        .dc-panel-input{width:100%;padding:.55rem .7rem;border-radius:8px;border:1px solid var(--brd);background:var(--card);color:var(--txt);font-size:.9rem;}
        .dc-pax-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;margin-bottom:.8rem;}
        .dc-pax-label{font-size:.7rem;color:var(--muted);margin-bottom:.18rem;font-weight:600;}
        .dc-btn-book{width:100%;padding:.78rem;border-radius:999px;background:var(--gy);border:none;color:#fff;font-size:1rem;font-weight:800;cursor:pointer;transition:background .15s;margin-top:.5rem;}
        .dc-btn-book:hover{background:var(--gy-dark);}
        .dc-total-row{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--brd);padding-top:.7rem;margin-top:.5rem;}
        .dc-total-label{font-size:.85rem;color:var(--muted);}
        .dc-total-amount{font-size:1.15rem;font-weight:800;color:var(--txt);}
        /* sections */
        .dc-sec{margin-bottom:1.8rem;}
        .dc-sec-title{font-size:1.15rem;font-weight:800;margin-bottom:.8rem;padding-bottom:.5rem;border-bottom:1px solid var(--brd);}
        .dc-check-list{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:1fr 1fr;gap:.4rem .8rem;}
        @media(max-width:575px){.dc-check-list{grid-template-columns:1fr;}}
        .dc-check-list li{font-size:.9rem;color:var(--txt);display:flex;align-items:flex-start;gap:.4rem;}
        .dc-check-list li .ico{margin-top:.1rem;flex-shrink:0;}
        .dc-x-list{list-style:none;padding:0;margin:0;}
        .dc-x-list li{font-size:.9rem;color:var(--muted);display:flex;align-items:flex-start;gap:.4rem;margin-bottom:.3rem;}
        /* timeline */
        .dc-timeline{list-style:none;padding:0;margin:0;position:relative;}
        .dc-timeline::before{content:'';position:absolute;left:52px;top:0;bottom:0;width:2px;background:var(--brd);}
        .dc-timeline li{display:flex;gap:1rem;margin-bottom:1rem;position:relative;}
        .dc-tl-time{min-width:44px;text-align:right;font-weight:700;font-size:.82rem;color:var(--muted);padding-top:.1rem;}
        .dc-tl-dot{width:10px;height:10px;border-radius:50%;background:var(--gy);margin-top:.3rem;flex-shrink:0;position:relative;z-index:1;}
        .dc-tl-text{font-size:.9rem;}
        /* pkg tabs */
        .dc-pkg-tabs{display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap;}
        .dc-pkg-tab{padding:.4rem .9rem;border-radius:999px;border:1.5px solid var(--brd);font-size:.82rem;font-weight:700;cursor:pointer;background:var(--card);color:var(--txt);transition:all .15s;}
        .dc-pkg-tab.active{border-color:var(--gy);background:rgba(255,85,51,.08);color:var(--gy);}
        /* rating */
        .dc-stars{color:var(--star);}
        .dc-rating-bar-row{display:flex;align-items:center;gap:.7rem;margin-bottom:.3rem;}
        .dc-rating-bar-bg{flex:1;height:8px;border-radius:4px;background:var(--brd);overflow:hidden;}
        .dc-rating-bar-fill{height:100%;background:var(--star);border-radius:4px;}
        /* info tags */
        .dc-info-tag{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid var(--brd);padding:.45rem .7rem;font-size:.82rem;background:var(--card);}
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="dinner-cruise" />

{{-- Breadcrumb --}}
<div class="dc-breadcrumb">
    <div class="container">
        <a href="{{ route('acente.dinner-cruise.catalog') }}">Dinner Cruise</a>
        <span class="mx-1">/</span>
        <span>{{ $package->name_tr }}</span>
    </div>
</div>

@php
    $heroImg = $package->hero_image_url ?: 'https://images.pexels.com/photos/3411083/pexels-photo-3411083.jpeg?auto=compress&cs=tinysrgb&w=1200';
    $galleryImgs = $galleryPhotos->isNotEmpty()
        ? $galleryPhotos->where('media_type','photo')->take(3)->values()
        : $mediaAssets->where('media_type','photo')->take(3)->values();
    $timeline = is_array($package->timeline_tr) ? $package->timeline_tr : json_decode($package->timeline_tr ?? '[]', true);
    $departureTimes = is_array($package->departure_times) ? $package->departure_times : json_decode($package->departure_times ?? '[]', true);
    $importantNotes = is_array($package->important_notes_tr) ? $package->important_notes_tr : json_decode($package->important_notes_tr ?? '[]', true);
    $dcLbItems = [['url' => $heroImg, 'type' => 'photo', 'alt' => $package->name_tr]];
    foreach ($galleryPhotos as $lbA) {
        $dcLbItems[] = ['url' => $lbA->resolvedUrl(), 'type' => $lbA->media_type, 'alt' => $lbA->title_tr ?? ''];
    }
@endphp

{{-- Lightbox --}}
<div id="dcLb" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.93);z-index:9999;align-items:center;justify-content:center;">
    <button onclick="dcLbClose()" style="position:fixed;top:14px;right:18px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;line-height:1;">✕</button>
    <button onclick="dcLbPrev()" style="position:fixed;left:10px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:2rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;">‹</button>
    <div id="dcLbMedia" style="max-width:92vw;max-height:88vh;display:flex;align-items:center;justify-content:center;"></div>
    <button onclick="dcLbNext()" style="position:fixed;right:10px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:2rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;">›</button>
    <div id="dcLbCount" style="position:fixed;bottom:14px;left:50%;transform:translateX(-50%);color:#fff;font-size:.82rem;background:rgba(0,0,0,.5);padding:.2rem .55rem;border-radius:999px;"></div>
</div>

<div class="container mt-3">
    {{-- Gallery --}}
    <div class="dc-gallery mb-3" style="cursor:pointer;">
        <div class="dc-gallery-main" onclick="dcLbOpen(0)">
            <img src="{{ $heroImg }}" alt="{{ $package->name_tr }}" loading="lazy">
        </div>
        @foreach($galleryImgs->take(2) as $i => $asset)
            <div class="dc-gallery-thumb" onclick="dcLbOpen({{ $i + 1 }})">
                <img src="{{ $asset->resolvedUrl() }}" alt="{{ $asset->title_tr }}" loading="lazy">
                @if($i === 1 && count($dcLbItems) > 3)
                    <div class="dc-gallery-more">+{{ count($dcLbItems) - 3 }} fotoğraf</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="dc-layout">
        {{-- ── Sol: İçerik ── --}}
        <div>
            {{-- Başlık --}}
            <div class="mb-3">
                @if($package->badge_text)
                    <span class="badge text-bg-warning mb-2" style="font-size:.75rem;">{{ $package->badge_text }}</span>
                @endif
                <h1 style="font-size:1.7rem;font-weight:800;line-height:1.2;margin:0 0 .5rem;">{{ $package->name_tr }}</h1>
                <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:.88rem;color:var(--muted);">
                    <span class="dc-stars">
                        @for($s=1;$s<=5;$s++)
                            <i class="fa{{ $s <= floor($package->rating ?? 4.6) ? 's' : ($s - ($package->rating ?? 4.6) < 1 ? 's fa-star-half-alt' : 'r') }} fa-star fa-xs"></i>
                        @endfor
                    </span>
                    <strong style="color:var(--txt);">{{ $package->rating ?? '4.6' }}</strong>
                    <span>{{ number_format((int)($package->review_count ?? 2053)) }} yorum</span>
                    @if($package->duration_hours)
                        <span><i class="fas fa-clock fa-xs me-1"></i>{{ number_format((float)$package->duration_hours, 0) }} saat</span>
                    @endif
                    @if($package->pier_name)
                        <span><i class="fas fa-ship fa-xs me-1"></i>{{ $package->pier_name }}</span>
                    @endif
                </div>
            </div>

            {{-- Öne çıkan bilgiler --}}
            <div class="d-flex gap-2 flex-wrap mb-4">
                <span class="dc-info-tag"><i class="fas fa-ban-smoking fa-xs text-success"></i> Sıra beklemeden giriş</span>
                <span class="dc-info-tag"><i class="fas fa-car fa-xs text-primary"></i> Araçla alma mevcut</span>
                <span class="dc-info-tag"><i class="fas fa-utensils fa-xs text-warning"></i> Yemek dahil</span>
                <span class="dc-info-tag"><i class="fas fa-shield-alt fa-xs text-success"></i> Ücretsiz iptal</span>
            </div>

            {{-- Paket seçici --}}
            <div class="dc-sec">
                <div class="dc-sec-title">4 mevsimsel seçenek seçin yap</div>
                <div class="dc-pkg-tabs">
                    @foreach($allPackages as $p)
                        <a href="{{ route('acente.dinner-cruise.show-product', $p->code) }}"
                           class="dc-pkg-tab {{ $p->id === $package->id ? 'active' : '' }}">
                            {{ $p->name_tr }}
                        </a>
                    @endforeach
                </div>
                <div class="p-3 rounded-3" style="background:rgba(255,85,51,.06);border:1px solid rgba(255,85,51,.2);">
                    <div class="fw-bold mb-1">{{ $package->name_tr }}</div>
                    <div style="font-size:.88rem;color:var(--muted);">{{ $package->summary_tr }}</div>
                    <div class="mt-2">
                        @if($package->original_price_per_person)
                            <div style="font-size:.75rem;color:var(--muted);margin-bottom:.15rem;">Önerilen satış: <strong>{{ number_format((float)$package->original_price_per_person,0,',','.') }} {{ $package->currency ?: 'EUR' }}</strong>/kişi</div>
                        @endif
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:1.25rem;font-weight:800;color:var(--gy);">{{ number_format((float)($package->base_price_per_person??0),0,',','.') }} {{ $package->currency ?: 'EUR' }}</span>
                            <span style="font-size:.78rem;color:var(--muted);">/ kişi (B2B)</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Açıklama --}}
            @if($package->long_description_tr)
            <div class="dc-sec">
                <div class="dc-sec-title">Hakkında</div>
                <div style="font-size:.94rem;line-height:1.75;white-space:pre-line;">{{ $package->long_description_tr }}</div>
            </div>
            @endif

            {{-- Dahil olanlar --}}
            @if(!empty($package->includes_tr))
            <div class="dc-sec">
                <div class="dc-sec-title">Dahil olanlar</div>
                <ul class="dc-check-list">
                    @foreach($package->includes_tr as $inc)
                        <li><span class="ico"><i class="fas fa-check-circle text-success"></i></span>{{ $inc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Hariç olanlar --}}
            @if(!empty($package->excludes_tr))
            <div class="dc-sec">
                <div class="dc-sec-title">Hariç olanlar</div>
                <ul class="dc-x-list">
                    @foreach($package->excludes_tr as $exc)
                        <li><i class="fas fa-times text-danger fa-xs mt-1"></i>{{ $exc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Program akışı --}}
            @if(!empty($timeline))
            <div class="dc-sec">
                <div class="dc-sec-title">Program akışı</div>
                <ul class="dc-timeline">
                    @foreach($timeline as $step)
                        <li>
                            <span class="dc-tl-time">{{ $step['time'] ?? '' }}</span>
                            <span class="dc-tl-dot"></span>
                            <span class="dc-tl-text">{{ $step['event'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Buluşma noktası --}}
            <div class="dc-sec">
                <div class="dc-sec-title">Buluşma noktası</div>
                <div class="d-flex gap-2 align-items-start p-3 rounded-3" style="background:var(--card);border:1px solid var(--brd);">
                    <i class="fas fa-map-marker-alt text-danger mt-1"></i>
                    <div>
                        <div class="fw-bold" style="font-size:.9rem;">{{ $package->pier_name ?? 'Kabataş İskelesi' }}</div>
                        <div style="font-size:.84rem;color:var(--muted);">{{ $package->meeting_point ?? 'Kabataş İskelesi D kapısı önünde rehber karşılama — kalkıştan 30 dk önce olunuz.' }}</div>
                    </div>
                </div>
            </div>

            {{-- Önemli bilgiler --}}
            @if(!empty($importantNotes))
            <div class="dc-sec">
                <div class="dc-sec-title">Önemli bilgiler</div>
                <ul class="dc-x-list">
                    @foreach($importantNotes as $note)
                        <li><i class="fas fa-info-circle text-primary fa-xs mt-1"></i><span>{{ $note }}</span></li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- İptal politikası --}}
            <div class="dc-sec">
                <div class="dc-sec-title">İptal politikası</div>
                <div class="d-flex gap-2 p-3 rounded-3" style="background:rgba(18,163,84,.07);border:1px solid rgba(18,163,84,.2);">
                    <i class="fas fa-shield-alt text-success mt-1"></i>
                    <span style="font-size:.9rem;">{{ $package->cancellation_policy_tr ?? 'Hizmetten 24 saat öncesine kadar ücretsiz iptal.' }}</span>
                </div>
            </div>

            {{-- Değerlendirmeler --}}
            <div class="dc-sec">
                <div class="dc-sec-title">Müşteri yorumları</div>
                <div class="d-flex gap-4 align-items-center mb-3">
                    <div class="text-center">
                        <div style="font-size:3rem;font-weight:800;line-height:1;">{{ $package->rating ?? '4.6' }}</div>
                        <div class="dc-stars mb-1"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                        <div style="font-size:.8rem;color:var(--muted);">{{ number_format((int)($package->review_count??2053)) }} yorum</div>
                    </div>
                    <div class="flex-1 w-100">
                        @foreach([5=>75, 4=>15, 3=>6, 2=>2, 1=>2] as $star => $pct)
                        <div class="dc-rating-bar-row">
                            <span style="font-size:.78rem;min-width:12px;">{{ $star }}</span>
                            <div class="dc-rating-bar-bg"><div class="dc-rating-bar-fill" style="width:{{ $pct }}%"></div></div>
                            <span style="font-size:.78rem;color:var(--muted);min-width:28px;">{{ $pct }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Sağ: Rezervasyon Paneli ── --}}
        <div>
            <div class="dc-panel">
                <div class="mb-1">
                    @if($package->original_price_per_person)
                        <div style="font-size:.75rem;color:var(--muted);">Önerilen satış: <strong>{{ number_format((float)$package->original_price_per_person,0,',','.') }} {{ $package->currency ?: 'EUR' }}</strong>/kişi</div>
                    @endif
                </div>
                <div class="dc-panel-price">{{ number_format((float)($package->base_price_per_person??0),0,',','.') }} {{ $package->currency ?: 'EUR' }}</div>
                <div style="font-size:.78rem;color:var(--muted);margin-bottom:1rem;">kişi başı (B2B fiyat)</div>

                <form id="bookForm" action="{{ route('acente.dinner-cruise.book', $package->code) }}" method="POST">
                    @csrf
                    {{-- Tarih --}}
                    <div class="dc-panel-label">Tarih</div>
                    <input type="date" name="service_date" class="dc-panel-input mb-3"
                           min="{{ now()->addDay()->format('Y-m-d') }}" required>

                    {{-- Kalkış saati --}}
                    @if(count($departureTimes) === 1)
                        <div class="dc-panel-label">Kalkış saati</div>
                        <div class="dc-panel-input" style="display:flex;align-items:center;font-weight:600;">{{ $departureTimes[0] }}</div>
                        <input type="hidden" name="departure_time" value="{{ $departureTimes[0] }}">
                    @elseif(count($departureTimes) > 1)
                        <div class="dc-panel-label">Kalkış saati</div>
                        <select name="departure_time" class="dc-panel-select" required>
                            <option value="">Seçin</option>
                            @foreach($departureTimes as $dt)
                                <option value="{{ $dt }}">{{ $dt }}</option>
                            @endforeach
                        </select>
                    @endif

                    {{-- Kişi sayısı --}}
                    <div class="dc-panel-label">Kişi sayısı</div>
                    <div class="dc-pax-row">
                        <div>
                            <div class="dc-pax-label">Yetişkin</div>
                            <input type="number" name="pax_adult" id="paxAdult" class="dc-panel-input" min="1" max="500" value="1" required>
                        </div>
                        <div>
                            <div class="dc-pax-label">Çocuk (2-12)</div>
                            <input type="number" name="pax_child" id="paxChild" class="dc-panel-input" min="0" max="500" value="0">
                        </div>
                        <div>
                            <div class="dc-pax-label">Bebek (0-2)</div>
                            <input type="number" name="pax_infant" id="paxInfant" class="dc-panel-input" min="0" max="100" value="0">
                        </div>
                    </div>

                    {{-- Ad Soyad + Tel --}}
                    <div class="dc-panel-label">Yetkili adı soyadı</div>
                    <input type="text" name="guest_name" class="dc-panel-input mb-3" placeholder="Ad Soyad" required>
                    <div class="dc-panel-label">Telefon</div>
                    <input type="text" name="guest_phone" class="dc-panel-input mb-3" placeholder="+90 5xx xxx xx xx" required>

                    {{-- Transfer --}}
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="transfer_required" value="1" id="chkTransfer">
                        <label class="form-check-label" style="font-size:.85rem;" for="chkTransfer">Otelden alınma transfer ekle</label>
                    </div>
                    <div id="transferFields" style="display:none;">
                        <input type="text" name="hotel_name" class="dc-panel-input mb-2" placeholder="Otel adı">
                        <input type="text" name="transfer_region" class="dc-panel-input mb-3" placeholder="Bölge (ör: Sultanahmet)">
                    </div>

                    <div class="dc-total-row">
                        <span class="dc-total-label">Tahmini toplam</span>
                        <span class="dc-total-amount" id="totalPrice">—</span>
                    </div>

                    <button type="submit" class="dc-btn-book">
                        <i class="fas fa-lock me-2"></i>Rezervasyon Yap &amp; Öde
                    </button>
                </form>

                <div class="text-center mt-2" style="font-size:.75rem;color:var(--muted);">
                    <i class="fas fa-shield-alt me-1 text-success"></i>Güvenli ödeme · 24s ücretsiz iptal
                </div>
            </div>

            {{-- Diğer paketler --}}
            <div class="mt-3 p-3 rounded-3" style="background:var(--card);border:1px solid var(--brd);">
                <div style="font-size:.8rem;font-weight:700;margin-bottom:.6rem;color:var(--muted);">DİĞER PAKETLER</div>
                @foreach($allPackages->where('id','!=',$package->id) as $p)
                    <a href="{{ route('acente.dinner-cruise.show-product', $p->code) }}" class="d-flex justify-content-between align-items-center text-decoration-none py-2" style="border-bottom:1px solid var(--brd);color:var(--txt);">
                        <span style="font-size:.85rem;">{{ $p->name_tr }}</span>
                        <span style="font-size:.88rem;font-weight:700;color:var(--gy);">{{ number_format((float)($p->base_price_per_person??0),0,',','.') }} {{ $p->currency ?: 'EUR' }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

@include('acente.partials.theme-script')
<script>
// ── Lightbox ──────────────────────────────────────────────────────────
const dcLbImages = @json($dcLbItems);
let dcLbIdx = 0;
const dcLbEl    = document.getElementById('dcLb');
const dcLbMedia = document.getElementById('dcLbMedia');
const dcLbCount = document.getElementById('dcLbCount');

function dcLbOpen(i) {
    dcLbIdx = i;
    dcLbRender();
    dcLbEl.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function dcLbClose() {
    dcLbEl.style.display = 'none';
    document.body.style.overflow = '';
    dcLbMedia.innerHTML = '';
}
function dcLbRender() {
    const item = dcLbImages[dcLbIdx];
    if (item.type === 'video') {
        dcLbMedia.innerHTML = `<video src="${item.url}" controls autoplay playsinline style="max-width:92vw;max-height:88vh;border-radius:8px;"></video>`;
    } else {
        dcLbMedia.innerHTML = `<img src="${item.url}" alt="${item.alt}" style="max-width:92vw;max-height:88vh;object-fit:contain;border-radius:8px;">`;
    }
    dcLbCount.textContent = (dcLbIdx + 1) + ' / ' + dcLbImages.length;
}
function dcLbPrev() { dcLbIdx = (dcLbIdx - 1 + dcLbImages.length) % dcLbImages.length; dcLbRender(); }
function dcLbNext() { dcLbIdx = (dcLbIdx + 1) % dcLbImages.length; dcLbRender(); }
dcLbEl.addEventListener('click', e => { if (e.target === dcLbEl) dcLbClose(); });
document.addEventListener('keydown', e => {
    if (dcLbEl.style.display === 'none') return;
    if (e.key === 'Escape') dcLbClose();
    if (e.key === 'ArrowLeft') dcLbPrev();
    if (e.key === 'ArrowRight') dcLbNext();
});

// ── Fiyat hesap ───────────────────────────────────────────────────────
const pricePerPerson = {{ (float)($package->base_price_per_person ?? 0) }};
const childRate = 0.5;
const currency = '{{ $package->currency ?: 'EUR' }}';

function calcTotal() {
    const adult  = parseInt(document.getElementById('paxAdult').value)  || 0;
    const child  = parseInt(document.getElementById('paxChild').value)   || 0;
    const total  = (adult * pricePerPerson) + (child * pricePerPerson * childRate);
    document.getElementById('totalPrice').textContent =
        total > 0 ? total.toLocaleString('tr-TR', {maximumFractionDigits:0}) + ' ' + currency : '—';
}

['paxAdult','paxChild','paxInfant'].forEach(id => {
    document.getElementById(id).addEventListener('input', calcTotal);
});
calcTotal();

document.getElementById('chkTransfer').addEventListener('change', function() {
    document.getElementById('transferFields').style.display = this.checked ? '' : 'none';
});
</script>
@include('acente.partials.leisure-footer')
</body>
</html>

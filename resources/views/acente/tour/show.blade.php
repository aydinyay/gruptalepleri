<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $package->name_tr }} — Tur Paketi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        :root{--gy:#1a7a4a;--gy-dark:#155f3a;--txt:#1a1a1a;--muted:#595959;--brd:#e8e8e8;--bg:#f5f5f5;--card:#fff;--star:#f5a623;}
        html[data-theme="dark"]{--txt:#f0f0f0;--muted:#b0b0b0;--brd:#333;--bg:#0f1520;--card:#1a2235;}
        body{background:var(--bg);color:var(--txt);}
        .dc-breadcrumb{background:var(--card);border-bottom:1px solid var(--brd);padding:.6rem 0;font-size:.82rem;color:var(--muted);}
        .dc-breadcrumb a{color:var(--muted);text-decoration:none;}.dc-breadcrumb a:hover{color:var(--gy);}
        .dc-gallery{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:1fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;background:#ccc;}
        .dc-gallery-main{grid-row:1/3;overflow:hidden;}.dc-gallery-main img,.dc-gallery-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
        .dc-gallery-thumb{overflow:hidden;cursor:pointer;}
        @media(max-width:767px){.dc-gallery{grid-template-columns:1fr;grid-template-rows:260px;}.dc-gallery-thumb{display:none;}}
        .dc-layout{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:2rem;align-items:start;padding:1.5rem 0 3rem;}
        @media(max-width:991px){.dc-layout{grid-template-columns:1fr;}}
        .dc-panel{position:sticky;top:72px;background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:1.25rem;box-shadow:0 4px 24px rgba(0,0,0,.08);}
        .dc-panel-price{font-size:1.6rem;font-weight:800;color:var(--gy);}
        .dc-panel-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);margin-bottom:.25rem;}
        .dc-panel-input{width:100%;padding:.55rem .7rem;border-radius:8px;border:1px solid var(--brd);background:var(--card);color:var(--txt);font-size:.9rem;margin-bottom:.8rem;}
        .dc-pax-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;margin-bottom:.8rem;}
        .dc-pax-label{font-size:.7rem;color:var(--muted);margin-bottom:.18rem;font-weight:600;}
        .dc-btn-book{width:100%;padding:.78rem;border-radius:999px;background:var(--gy);border:none;color:#fff;font-size:1rem;font-weight:800;cursor:pointer;transition:background .15s;margin-top:.5rem;}
        .dc-btn-book:hover{background:var(--gy-dark);}
        .dc-total-row{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--brd);padding-top:.7rem;margin-top:.5rem;}
        .dc-total-label{font-size:.85rem;color:var(--muted);}
        .dc-total-amount{font-size:1.15rem;font-weight:800;color:var(--txt);}
        .dc-sec{margin-bottom:1.8rem;}
        .dc-sec-title{font-size:1.15rem;font-weight:800;margin-bottom:.8rem;padding-bottom:.5rem;border-bottom:1px solid var(--brd);}
        .dc-check-list{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:1fr 1fr;gap:.4rem .8rem;}
        @media(max-width:575px){.dc-check-list{grid-template-columns:1fr;}}
        .dc-check-list li{font-size:.9rem;display:flex;align-items:flex-start;gap:.4rem;}
        .dc-x-list{list-style:none;padding:0;margin:0;}
        .dc-x-list li{font-size:.9rem;color:var(--muted);display:flex;align-items:flex-start;gap:.4rem;margin-bottom:.3rem;}
        .dc-info-tag{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid var(--brd);padding:.45rem .7rem;font-size:.82rem;background:var(--card);}
        .extra-card{border:1.5px solid var(--brd);border-radius:8px;padding:.7rem;cursor:pointer;transition:border-color .15s;}
        .extra-card:has(input:checked){border-color:var(--gy);background:rgba(26,122,74,.04);}
    </style>
</head>
<body class="theme-scope">
<x-navbar-acente active="tour" />

<div class="dc-breadcrumb">
    <div class="container">
        <a href="{{ route('acente.tour.catalog') }}">Tur Paketleri</a>
        <span class="mx-1">/</span>
        <span>{{ $package->name_tr }}</span>
    </div>
</div>

<div class="container">
    {{-- Galeri --}}
    <div class="dc-gallery mt-3">
        @php
            $heroImg = $package->hero_image_url ?: 'https://images.pexels.com/photos/2325446/pexels-photo-2325446.jpeg?auto=compress&cs=tinysrgb&w=800';
            $thumbs = $galleryPhotos->take(2);
        @endphp
        <div class="dc-gallery-main">
            <img src="{{ $heroImg }}" alt="{{ $package->name_tr }}">
        </div>
        @foreach($thumbs as $thumb)
            <div class="dc-gallery-thumb">
                <img src="{{ $thumb->resolvedUrl() }}" alt="">
            </div>
        @endforeach
        @for($i = $thumbs->count(); $i < 2; $i++)
            <div class="dc-gallery-thumb" style="background:#e8f5ec;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-map-location-dot fa-2x" style="color:#1a7a4a;opacity:.4;"></i>
            </div>
        @endfor
    </div>

    <div class="dc-layout">
        {{-- Sol: Detaylar --}}
        <div>
            <div class="d-flex flex-wrap gap-2 mb-3 mt-1">
                @if($package->duration_hours)
                    <span class="dc-info-tag"><i class="fas fa-clock text-success"></i> {{ number_format((float)$package->duration_hours, 0) }} saat</span>
                @endif
                @if($package->pier_name)
                    <span class="dc-info-tag"><i class="fas fa-map-marker-alt text-danger"></i> {{ $package->pier_name }}</span>
                @endif
                <span class="dc-info-tag"><i class="fas fa-users text-primary"></i> Grup turu</span>
            </div>

            <h1 style="font-size:clamp(1.4rem,3vw,2rem);font-weight:800;line-height:1.2;margin-bottom:.5rem;">{{ $package->name_tr }}</h1>

            @if($package->summary_tr)
                <p style="color:var(--muted);font-size:.95rem;margin-bottom:1.5rem;">{{ $package->summary_tr }}</p>
            @endif

            @if($package->includes_tr)
            <div class="dc-sec">
                <div class="dc-sec-title"><i class="fas fa-check-circle text-success me-2"></i>Dahil Olanlar</div>
                <ul class="dc-check-list">
                    @foreach($package->includes_tr as $inc)
                        <li><span class="ico"><i class="fas fa-check-circle text-success"></i></span> {{ $inc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($package->excludes_tr)
            <div class="dc-sec">
                <div class="dc-sec-title"><i class="fas fa-times-circle text-danger me-2"></i>Dahil Olmayanlar</div>
                <ul class="dc-x-list">
                    @foreach($package->excludes_tr as $exc)
                        <li><i class="fas fa-times text-danger"></i> {{ $exc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($package->description_tr)
            <div class="dc-sec">
                <div class="dc-sec-title">Tur Detayları</div>
                <div style="font-size:.92rem;line-height:1.7;">{!! nl2br(e($package->description_tr)) !!}</div>
            </div>
            @endif

            {{-- Diğer turlar --}}
            @if($allPackages->count() > 1)
            <div class="dc-sec">
                <div class="dc-sec-title">Diğer Tur Paketleri</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($allPackages as $otherPkg)
                        <a href="{{ route('acente.tour.show-product', $otherPkg->code) }}"
                           class="btn btn-sm {{ $otherPkg->id === $package->id ? 'btn-success' : 'btn-outline-secondary' }}">
                            {{ $otherPkg->name_tr }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Sağ: Rezervasyon paneli --}}
        <div>
            <div class="dc-panel">
                <div class="dc-panel-label">Kişi başı B2B fiyat</div>
                <div class="dc-panel-price">
                    {{ number_format((float)($package->base_price_per_person ?? 0), 0, ',', '.') }} {{ $package->currency ?: 'EUR' }}
                </div>
                @if($package->original_price_per_person)
                    <div style="font-size:.82rem;color:var(--muted);margin-bottom:.5rem;">
                        Önerilen satış: <s>{{ number_format((float)$package->original_price_per_person, 0, ',', '.') }}</s> {{ $package->currency ?: 'EUR' }}/kişi
                    </div>
                @endif

                <form method="POST" action="{{ route('acente.tour.book', $package->code) }}" id="tourBookForm">
                    @csrf

                    <div class="dc-panel-label">Tur Tarihi</div>
                    <input type="date" name="service_date" class="dc-panel-input" min="{{ date('Y-m-d') }}" required>

                    <div class="dc-panel-label">Kişi Sayısı</div>
                    <div class="dc-pax-row">
                        <div>
                            <div class="dc-pax-label">Yetişkin</div>
                            <input type="number" name="pax_adult" class="dc-panel-input mb-0" min="1" value="2" required id="paxAdult">
                        </div>
                        <div>
                            <div class="dc-pax-label">Çocuk (4-12)</div>
                            <input type="number" name="pax_child" class="dc-panel-input mb-0" min="0" value="0" id="paxChild">
                        </div>
                        <div>
                            <div class="dc-pax-label">Bebek (0-3)</div>
                            <input type="number" name="pax_infant" class="dc-panel-input mb-0" min="0" value="0">
                        </div>
                    </div>

                    <div class="dc-panel-label">Alınacak Yer / Otel</div>
                    <input type="text" name="pickup_location" class="dc-panel-input" placeholder="Otel adı veya adres (opsiyonel)">

                    <div class="dc-panel-label">Misafir Adı Soyadı</div>
                    <input type="text" name="guest_name" class="dc-panel-input" placeholder="Baş misafir adı" required>

                    <div class="dc-panel-label">Misafir Telefonu</div>
                    <input type="tel" name="guest_phone" class="dc-panel-input" placeholder="+90 555 000 00 00" required>

                    <div class="dc-panel-label">Uyruk (opsiyonel)</div>
                    <input type="text" name="nationality" class="dc-panel-input" placeholder="Türk, İngiliz, ...">

                    @if($extraOptions->count())
                    <div class="dc-panel-label">Ek Seçenekler</div>
                    <div style="display:flex;flex-direction:column;gap:.4rem;margin-bottom:.8rem;">
                        @foreach($extraOptions as $extra)
                        <label class="extra-card">
                            <input type="checkbox" name="extra_option_codes[]" value="{{ $extra->code }}" style="margin-right:.5rem;">
                            <span style="font-size:.85rem;font-weight:600;">{{ $extra->title_tr }}</span>
                            @if($extra->price)
                                <span style="font-size:.78rem;color:var(--muted);"> +{{ number_format((float)$extra->price, 0, ',', '.') }} {{ $extra->currency ?: 'EUR' }}/kişi</span>
                            @endif
                        </label>
                        @endforeach
                    </div>
                    @endif

                    <div class="dc-panel-label">Notlar (opsiyonel)</div>
                    <textarea name="notes" class="dc-panel-input" rows="2" placeholder="Özel istekler..." style="resize:none;"></textarea>

                    <div class="dc-total-row">
                        <span class="dc-total-label">Toplam B2B Tutar</span>
                        <span class="dc-total-amount" id="totalAmount">
                            {{ number_format((float)($package->base_price_per_person ?? 0) * 2, 0, ',', '.') }} {{ $package->currency ?: 'EUR' }}
                        </span>
                    </div>

                    <button type="submit" class="dc-btn-book">
                        <i class="fas fa-map-location-dot me-2"></i>Rezervasyon Yap
                    </button>
                    <p style="font-size:.72rem;color:var(--muted);text-align:center;margin-top:.5rem;">Ödeme sonraki adımda alınır.</p>
                </form>
            </div>
        </div>
    </div>
</div>

@include('acente.partials.leisure-footer')
@include('acente.partials.theme-script')
<script>
const pricePerPerson = {{ (float)($package->base_price_per_person ?? 0) }};
const currency = '{{ $package->currency ?: "EUR" }}';

function updateTotal() {
    const adult = parseInt(document.getElementById('paxAdult').value) || 0;
    const child = parseInt(document.getElementById('paxChild').value) || 0;
    const total = (adult * pricePerPerson) + (child * pricePerPerson * 0.5);
    document.getElementById('totalAmount').textContent =
        new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(total) + ' ' + currency;
}

document.getElementById('paxAdult').addEventListener('input', updateTotal);
document.getElementById('paxChild').addEventListener('input', updateTotal);
updateTotal();
</script>
</body>
</html>

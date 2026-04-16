@extends('b2c.layouts.app')
@section('title', $item->meta_title ?? $item->title)
@section('content')

@php
    // Görseller gruptalepleri.com sunucusunda — B2C'de doğru domain'i zorla
    $b2bAsset = function(?string $url): ?string {
        if (!$url) return null;
        if (str_starts_with($url, 'http')) {
            return str_replace(
                ['gruprezervasyonlari.com', config('b2c.domain', 'gruprezervasyonlari.com')],
                'gruptalepleri.com',
                $url
            );
        }
        return 'https://gruptalepleri.com/' . ltrim($url, '/');
    };

    $heroImg = $b2bAsset($package->hero_image_url)
        ?: 'https://images.pexels.com/photos/1001682/pexels-photo-1001682.jpeg?auto=compress&cs=tinysrgb&w=1200';

    $galleryImgs = $galleryPhotos->isNotEmpty()
        ? $galleryPhotos->take(3)->values()
        : $mediaAssets->where('media_type','photo')->take(3)->values();

    $timeline       = is_array($package->timeline_tr) ? $package->timeline_tr : json_decode($package->timeline_tr ?? '[]', true);
    $importantNotes = is_array($package->important_notes_tr) ? $package->important_notes_tr : json_decode($package->important_notes_tr ?? '[]', true);

    $lbItems = [['url' => $heroImg, 'type' => 'photo', 'alt' => $package->name_tr]];
    foreach ($galleryPhotos as $lbA) {
        $lbItems[] = ['url' => $b2bAsset($lbA->resolvedUrl()), 'type' => $lbA->media_type, 'alt' => $lbA->title_tr ?? ''];
    }
@endphp

<style>
:root{--gy:#FF5533;--gy-d:#e04420;--star:#f5a623;--txt:#1a202c;--muted:#718096;--brd:#e5e5e5;--bg:#f8f9fc;--card:#fff;}
body{background:var(--bg);color:var(--txt);}
/* breadcrumb */
.lp-bc{background:var(--card);border-bottom:1px solid var(--brd);padding:.6rem 0;font-size:.82rem;color:var(--muted);}
.lp-bc a{color:var(--muted);text-decoration:none;}.lp-bc a:hover{color:var(--gy);}
/* gallery */
.lp-gallery{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:1fr 1fr;gap:4px;height:420px;border-radius:12px;overflow:hidden;background:#1a3a6e;}
.lp-gallery-main{grid-row:1/3;overflow:hidden;}.lp-gallery-main img,.lp-gallery-thumb img{width:100%;height:100%;object-fit:cover;display:block;}
.lp-gallery-thumb{overflow:hidden;cursor:pointer;position:relative;}
.lp-gallery-thumb:last-child .lp-gallery-more{position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem;}
@media(max-width:767px){.lp-gallery{grid-template-columns:1fr;grid-template-rows:260px;}.lp-gallery-thumb{display:none;}}
/* layout */
.lp-layout{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:2rem;align-items:start;padding:1.5rem 0 3rem;}
@media(max-width:991px){.lp-layout{grid-template-columns:1fr;}}
/* panel */
.lp-panel{position:sticky;top:80px;background:var(--card);border:1px solid var(--brd);border-radius:12px;padding:1.25rem;box-shadow:0 4px 24px rgba(0,0,0,.08);}
.lp-panel-qbox{background:#f0f4ff;border:1px solid #c7d4f0;border-radius:10px;padding:.85rem;margin-bottom:1rem;font-size:.88rem;color:#1a3c6b;}
.lp-btn-cta{display:flex;justify-content:center;align-items:center;gap:.4rem;width:100%;padding:.78rem;border-radius:999px;background:var(--gy);border:none;color:#fff;font-size:1rem;font-weight:800;text-decoration:none;cursor:pointer;transition:background .15s;}
.lp-btn-cta:hover{background:var(--gy-d);color:#fff;}
.lp-btn-soft{display:flex;justify-content:center;align-items:center;gap:.4rem;width:100%;padding:.65rem;border-radius:999px;border:2px solid #1a3c6b;color:#1a3c6b;font-weight:600;font-size:.9rem;text-decoration:none;margin-top:.55rem;background:transparent;}
.lp-btn-soft:hover{background:#1a3c6b;color:#fff;}
.lp-trust{display:flex;align-items:center;gap:.45rem;font-size:.78rem;color:var(--muted);margin-top:.45rem;}
.lp-trust i{color:#48bb78;}
.lp-div{height:1px;background:#f0f0f0;margin:.85rem 0;}
/* sections */
.lp-sec{margin-bottom:1.8rem;}
.lp-sec-title{font-size:1.1rem;font-weight:800;margin-bottom:.8rem;padding-bottom:.5rem;border-bottom:2px solid var(--brd);}
.lp-check-list{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:1fr 1fr;gap:.4rem .8rem;}
@media(max-width:575px){.lp-check-list{grid-template-columns:1fr;}}
.lp-check-list li{font-size:.9rem;display:flex;align-items:flex-start;gap:.4rem;}
.lp-x-list{list-style:none;padding:0;margin:0;}
.lp-x-list li{font-size:.9rem;color:var(--muted);display:flex;align-items:flex-start;gap:.4rem;margin-bottom:.3rem;}
/* timeline */
.lp-timeline{list-style:none;padding:0;margin:0;position:relative;}
.lp-timeline::before{content:'';position:absolute;left:52px;top:0;bottom:0;width:2px;background:var(--brd);}
.lp-timeline li{display:flex;gap:1rem;margin-bottom:1rem;position:relative;}
.lp-tl-time{min-width:44px;text-align:right;font-weight:700;font-size:.82rem;color:var(--muted);padding-top:.1rem;}
.lp-tl-dot{width:10px;height:10px;border-radius:50%;background:var(--gy);margin-top:.3rem;flex-shrink:0;position:relative;z-index:1;}
.lp-tl-text{font-size:.9rem;}
/* pkg tabs */
.lp-pkg-tabs{display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap;}
.lp-pkg-tab{padding:.4rem .9rem;border-radius:999px;border:1.5px solid var(--brd);font-size:.82rem;font-weight:700;cursor:pointer;background:var(--card);color:var(--txt);text-decoration:none;transition:all .15s;}
.lp-pkg-tab.active,.lp-pkg-tab:hover{border-color:var(--gy);background:rgba(255,85,51,.08);color:var(--gy);}
/* info tags */
.lp-info-tag{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid var(--brd);padding:.45rem .7rem;font-size:.82rem;background:var(--card);}
/* related */
.lp-rel-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
@media(max-width:767px){.lp-rel-grid{grid-template-columns:1fr;}}
</style>

{{-- Lightbox --}}
<div id="lpLb" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.93);z-index:9999;align-items:center;justify-content:center;">
    <button onclick="lpLbClose()" style="position:fixed;top:14px;right:18px;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;line-height:1;">✕</button>
    <button onclick="lpLbPrev()" style="position:fixed;left:10px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:2rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;">‹</button>
    <div id="lpLbMedia" style="max-width:92vw;max-height:88vh;display:flex;align-items:center;justify-content:center;"></div>
    <button onclick="lpLbNext()" style="position:fixed;right:10px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.15);border:none;color:#fff;font-size:2rem;padding:.3rem .7rem;border-radius:8px;cursor:pointer;">›</button>
    <div id="lpLbCount" style="position:fixed;bottom:14px;left:50%;transform:translateX(-50%);color:#fff;font-size:.82rem;background:rgba(0,0,0,.5);padding:.2rem .55rem;border-radius:999px;"></div>
</div>

{{-- Breadcrumb --}}
<div class="lp-bc">
    <div class="container">
        <a href="{{ route('b2c.home') }}">Ana Sayfa</a>
        @if($item->category)
            <span class="mx-1">/</span>
            <a href="{{ route('b2c.catalog.category', $item->category->slug) }}">{{ $item->category->name }}</a>
        @endif
        <span class="mx-1">/</span>
        <span>{{ $package->name_tr }}</span>
    </div>
</div>

<div class="container mt-3">
    {{-- Gallery --}}
    <div class="lp-gallery mb-3" style="cursor:pointer;">
        <div class="lp-gallery-main" onclick="lpLbOpen(0)">
            <img src="{{ $heroImg }}" alt="{{ $package->name_tr }}" loading="lazy">
        </div>
        @foreach($galleryImgs->take(2) as $i => $asset)
            <div class="lp-gallery-thumb" onclick="lpLbOpen({{ $i + 1 }})">
                <img src="{{ $b2bAsset($asset->resolvedUrl()) }}" alt="{{ $asset->title_tr ?? '' }}" loading="lazy">
                @if($i === 1 && count($lbItems) > 3)
                    <div class="lp-gallery-more">+{{ count($lbItems) - 3 }} fotoğraf</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="lp-layout">
        {{-- Sol: İçerik --}}
        <div>
            {{-- Başlık --}}
            <div class="mb-3">
                @if($package->badge_text)
                    <span class="badge text-bg-warning mb-2" style="font-size:.75rem;">{{ $package->badge_text }}</span>
                @endif
                <h1 style="font-size:1.7rem;font-weight:800;line-height:1.2;margin:0 0 .5rem;">{{ $package->name_tr }}</h1>
                <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:.88rem;color:var(--muted);">
                    @if($package->rating)
                        <span style="color:var(--star);">
                            @for($s=1;$s<=5;$s++)<i class="fas fa-star fa-xs"></i>@endfor
                        </span>
                        <strong style="color:var(--txt);">{{ $package->rating }}</strong>
                        <span>({{ number_format((int)($package->review_count ?? 0)) }} değerlendirme)</span>
                    @endif
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
                <span class="lp-info-tag"><i class="fas fa-shield-alt fa-xs text-success"></i> Ücretsiz iptal</span>
                <span class="lp-info-tag"><i class="fas fa-car fa-xs text-primary"></i> Transfer mevcut</span>
                <span class="lp-info-tag"><i class="fas fa-anchor fa-xs text-primary"></i> Özel grup</span>
                <span class="lp-info-tag"><i class="fas fa-route fa-xs text-warning"></i> Esnek güzergah</span>
            </div>

            {{-- Paket seçici --}}
            @if($allPackages->count() > 1)
            <div class="lp-sec">
                <div class="lp-sec-title">Yat tipi seçin</div>
                <div class="lp-pkg-tabs">
                    @foreach($allPackages as $p)
                        @php
                            // B2C'de bu pakete ait CatalogItem slug'ını bul
                            $pItem = \App\Models\B2C\CatalogItem::published()
                                ->where('reference_type', $item->reference_type)
                                ->where('reference_id', $p->id)
                                ->first();
                            $pUrl = $pItem ? route('b2c.product.show', $pItem->slug) : '#';
                        @endphp
                        <a href="{{ $pUrl }}" class="lp-pkg-tab {{ $p->id === $package->id ? 'active' : '' }}">
                            {{ $p->name_tr }}
                        </a>
                    @endforeach
                </div>
                <div class="p-3 rounded-3" style="background:rgba(255,85,51,.06);border:1px solid rgba(255,85,51,.2);">
                    <div class="fw-bold mb-1">{{ $package->name_tr }}</div>
                    <div style="font-size:.88rem;color:var(--muted);">{{ $package->summary_tr }}</div>
                </div>
            </div>
            @endif

            {{-- Açıklama --}}
            @if($package->long_description_tr)
            <div class="lp-sec">
                <div class="lp-sec-title">Hakkında</div>
                <div style="font-size:.94rem;line-height:1.75;white-space:pre-line;">{{ $package->long_description_tr }}</div>
            </div>
            @endif

            {{-- Dahil olanlar --}}
            @if(!empty($package->includes_tr))
            <div class="lp-sec">
                <div class="lp-sec-title">Dahil olanlar</div>
                <ul class="lp-check-list">
                    @foreach($package->includes_tr as $inc)
                        <li><i class="fas fa-check text-success fa-xs" style="margin-top:.25rem;flex-shrink:0;"></i>{{ $inc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Hariç olanlar --}}
            @if(!empty($package->excludes_tr))
            <div class="lp-sec">
                <div class="lp-sec-title">Dahil olmayanlar</div>
                <ul class="lp-x-list">
                    @foreach($package->excludes_tr as $exc)
                        <li><i class="fas fa-times text-danger fa-xs" style="margin-top:.25rem;flex-shrink:0;"></i>{{ $exc }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Program / Timeline --}}
            @if(!empty($timeline))
            <div class="lp-sec">
                <div class="lp-sec-title">Program</div>
                <ul class="lp-timeline">
                    @foreach($timeline as $tl)
                        <li>
                            <div class="lp-tl-time">{{ $tl['time'] ?? '' }}</div>
                            <div class="lp-tl-dot"></div>
                            <div class="lp-tl-text">
                                <div class="fw-bold" style="font-size:.9rem;">{{ $tl['title'] ?? '' }}</div>
                                @if(!empty($tl['desc']))<div style="font-size:.84rem;color:var(--muted);">{{ $tl['desc'] }}</div>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Önemli bilgiler --}}
            @if(!empty($importantNotes) || $package->cancellation_policy_tr)
            <div class="lp-sec">
                <div class="lp-sec-title">Önemli bilgiler</div>
                @if(!empty($importantNotes))
                    <ul class="lp-x-list">
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

        {{-- Sağ: Fiyat paneli --}}
        <div>
            <div class="lp-panel">
                <div class="lp-panel-qbox">
                    <i class="fas fa-info-circle me-2"></i>
                    Kişiselleştirilmiş fiyatlandırma. Grup büyüklüğü ve tarihe göre size özel fiyat iletilir.
                </div>

                <a href="{{ route('b2c.iletisim') }}" class="lp-btn-cta">
                    <i class="fas fa-paper-plane me-1"></i> Fiyat Al
                </a>
                <a href="{{ route('b2c.iletisim') }}" class="lp-btn-soft">
                    <i class="fas fa-phone me-1"></i> İletişime Geç
                </a>

                <div class="lp-div"></div>

                <div class="lp-trust"><i class="fas fa-check-circle"></i> Ücretsiz iptal (24 saat öncesine kadar)</div>
                <div class="lp-trust"><i class="fas fa-check-circle"></i> 4 saat içinde teklif</div>
                <div class="lp-trust"><i class="fas fa-check-circle"></i> Özel kaptan dahil</div>
                <div class="lp-trust"><i class="fas fa-check-circle"></i> Güvenli ödeme · 256-bit SSL</div>

                @if($package->pier_name)
                <div class="lp-div"></div>
                <div style="font-size:.8rem;color:var(--muted);">
                    <i class="fas fa-anchor me-1"></i> {{ $package->pier_name }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Benzer ürünler --}}
    @if($relatedItems->isNotEmpty())
    <section style="padding:2.5rem 0;border-top:1px solid var(--brd);margin-bottom:2rem;">
        <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:1rem;">Benzer Deneyimler</h2>
        <div class="lp-rel-grid">
            @foreach($relatedItems as $rel)
                @include('b2c.home._product-card', ['item' => $rel])
            @endforeach
        </div>
    </section>
    @endif
</div>

<script>
const lpLbImages = @json($lbItems);
let lpLbIdx = 0;
const lpLbEl    = document.getElementById('lpLb');
const lpLbMedia = document.getElementById('lpLbMedia');
const lpLbCount = document.getElementById('lpLbCount');

function lpLbOpen(i) { lpLbIdx = i; lpLbRender(); lpLbEl.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
function lpLbClose() { lpLbEl.style.display = 'none'; document.body.style.overflow = ''; lpLbMedia.innerHTML = ''; }
function lpLbRender() {
    const item = lpLbImages[lpLbIdx];
    lpLbMedia.innerHTML = item.type === 'video'
        ? `<video src="${item.url}" controls autoplay playsinline style="max-width:92vw;max-height:88vh;border-radius:8px;"></video>`
        : `<img src="${item.url}" alt="${item.alt}" style="max-width:92vw;max-height:88vh;object-fit:contain;border-radius:8px;">`;
    lpLbCount.textContent = (lpLbIdx + 1) + ' / ' + lpLbImages.length;
}
function lpLbPrev() { lpLbIdx = (lpLbIdx - 1 + lpLbImages.length) % lpLbImages.length; lpLbRender(); }
function lpLbNext() { lpLbIdx = (lpLbIdx + 1) % lpLbImages.length; lpLbRender(); }
lpLbEl.addEventListener('click', e => { if (e.target === lpLbEl) lpLbClose(); });
document.addEventListener('keydown', e => {
    if (lpLbEl.style.display === 'none') return;
    if (e.key === 'Escape') lpLbClose();
    if (e.key === 'ArrowLeft') lpLbPrev();
    if (e.key === 'ArrowRight') lpLbNext();
});
</script>

@endsection

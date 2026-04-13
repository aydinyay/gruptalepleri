<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $productType === 'dinner_cruise' ? 'Dinner Cruise Koleksiyonu' : 'Yacht Charter Koleksiyonu' }} - GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    @include('acente.partials.theme-styles')
    <style>
        .gt-leisure-v2-page { --bg:#f3f6fb; --card:#fff; --line:#dbe4f0; --muted:#64748b; background:linear-gradient(180deg,#f3f6fb 0%,#eef3fa 48%,#f8fafc 100%); min-height:100vh; }
        .gt-leisure-v2-shell { padding:1.2rem .8rem 2.4rem; }
        .gt-leisure-v2-hero { display:grid; grid-template-columns:minmax(0,1fr) 330px; gap:1rem; margin-bottom:1rem; }
        .gt-leisure-v2-main,.gt-leisure-v2-aside,.gt-leisure-v2-card,.gt-leisure-v2-request,.gt-leisure-v2-media { border:1px solid var(--line); border-radius:20px; background:var(--card); box-shadow:0 18px 46px rgba(15,23,42,.08); }
        .gt-leisure-v2-main { padding:1.2rem; color:#f8fafc; background:radial-gradient(circle at top right,rgba(56,189,248,.15),transparent 30%),linear-gradient(135deg,#08152c 0%,#13335f 55%,#1f3f78 100%); }
        .gt-leisure-v2-eyebrow { display:inline-flex; gap:.4rem; align-items:center; border-radius:999px; border:1px solid rgba(255,255,255,.22); background:rgba(255,255,255,.1); padding:.3rem .7rem; font-size:.75rem; font-weight:700; }
        .gt-leisure-v2-title { margin:.8rem 0 .5rem; max-width:13ch; line-height:.95; letter-spacing:-.02em; font-family:Georgia,"Times New Roman",serif; font-size:clamp(2rem,4.5vw,3.1rem); }
        .gt-leisure-v2-copy { margin:0; max-width:60ch; color:rgba(226,232,240,.9); line-height:1.7; }
        .gt-leisure-v2-chip-row { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.9rem; }
        .gt-leisure-v2-chip { display:inline-flex; gap:.3rem; align-items:center; border-radius:999px; border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.1); padding:.32rem .62rem; font-size:.76rem; font-weight:700; }
        .gt-leisure-v2-gallery { margin-top:.95rem; display:grid; grid-template-columns:minmax(0,1fr) 180px; gap:.6rem; }
        .gt-leisure-v2-gallery img { width:100%; height:100%; object-fit:cover; display:block; }
        .gt-leisure-v2-gallery-main { min-height:250px; border-radius:14px; overflow:hidden; border:1px solid rgba(255,255,255,.2); }
        .gt-leisure-v2-gallery-side { display:grid; gap:.6rem; grid-template-rows:repeat(2,minmax(0,1fr)); }
        .gt-leisure-v2-gallery-side > div { min-height:122px; border-radius:14px; overflow:hidden; border:1px solid rgba(255,255,255,.2); background:rgba(255,255,255,.08); }
        .gt-leisure-v2-gallery-empty { min-height:250px; border-radius:14px; border:1px solid rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; text-align:center; color:rgba(226,232,240,.84); padding:1rem; background:rgba(255,255,255,.08); }
        .gt-leisure-v2-aside { position:sticky; top:86px; padding:1rem; }
        .gt-leisure-v2-aside h2 { margin:0; font-size:1.1rem; font-weight:800; color:#0f172a; }
        .gt-leisure-v2-aside p { margin:.45rem 0 .8rem; color:var(--muted); font-size:.9rem; line-height:1.6; }
        .gt-leisure-v2-label { display:block; margin-bottom:.35rem; text-transform:uppercase; letter-spacing:.05em; font-size:.76rem; font-weight:800; color:#334155; }
        .gt-leisure-v2-select { width:100%; padding:.58rem .68rem; border-radius:12px; border:1px solid #cbd6e4; font-size:.9rem; }
        .gt-leisure-v2-summary { margin-top:.7rem; min-height:76px; border-radius:12px; border:1px dashed #c4d2e4; padding:.62rem .7rem; color:#475569; font-size:.84rem; line-height:1.55; }
        .gt-leisure-v2-btn { width:100%; display:inline-flex; gap:.4rem; justify-content:center; align-items:center; margin-top:.72rem; border-radius:999px; text-decoration:none; font-weight:700; padding:.66rem .9rem; border:1px solid #d3ddeb; }
        .gt-leisure-v2-btn.primary { color:#fff; border-color:#ea580c; background:linear-gradient(135deg,#fb923c,#ea580c); box-shadow:0 14px 28px rgba(234,88,12,.26); }
        .gt-leisure-v2-btn.soft { color:#0f172a; background:#f8fafc; }
        .gt-leisure-v2-note { margin-top:.7rem; font-size:.78rem; text-align:center; color:#64748b; }
        .gt-leisure-v2-section { margin-bottom:1rem; }
        .gt-leisure-v2-head { display:flex; justify-content:space-between; align-items:end; margin-bottom:.6rem; gap:.5rem; }
        .gt-leisure-v2-head h3 { margin:0; font-size:clamp(1.35rem,2.2vw,1.95rem); font-family:Georgia,"Times New Roman",serif; letter-spacing:-.02em; color:#0f172a; }
        .gt-leisure-v2-head p { margin:0; max-width:54ch; color:#64748b; font-size:.9rem; }
        .gt-leisure-v2-grid-3 { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
        .gt-leisure-v2-grid-2 { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
        .gt-leisure-v2-card { padding:.85rem; }
        .gt-leisure-v2-card h4 { margin:0 0 .35rem; font-size:.9rem; font-weight:800; color:#0f172a; }
        .gt-leisure-v2-card ul { margin:0; padding-left:1rem; color:#475569; font-size:.86rem; line-height:1.55; }
        .gt-leisure-v2-variant { border:1px solid var(--line); border-radius:16px; background:var(--card); overflow:hidden; }
        .gt-leisure-v2-variant + .gt-leisure-v2-variant { margin-top:.6rem; }
        .gt-leisure-v2-variant > summary { list-style:none; cursor:pointer; display:flex; justify-content:space-between; align-items:center; padding:.82rem .9rem; font-weight:800; color:#0f172a; }
        .gt-leisure-v2-variant > summary::-webkit-details-marker { display:none; }
        .gt-leisure-v2-badge { display:inline-flex; border-radius:999px; padding:.26rem .56rem; font-size:.72rem; font-weight:700; background:rgba(37,99,235,.12); color:#1d4ed8; }
        .gt-leisure-v2-variant-body { border-top:1px dashed #c9d8e9; padding:.9rem; }
        .gt-leisure-v2-variant-body ul { margin:0; padding-left:1rem; color:#475569; font-size:.86rem; line-height:1.55; }
        .gt-leisure-v2-media { overflow:hidden; }
        .gt-leisure-v2-media img { width:100%; height:180px; object-fit:cover; display:block; }
        .gt-leisure-v2-media-body { padding:.62rem .7rem; }
        .gt-leisure-v2-media-title { margin:0; font-size:.88rem; font-weight:700; color:#0f172a; }
        .gt-leisure-v2-media-meta { margin-top:.2rem; font-size:.78rem; color:#64748b; }
        .gt-leisure-v2-pills { display:flex; flex-wrap:wrap; gap:.42rem; }
        .gt-leisure-v2-pill { display:inline-flex; align-items:center; gap:.32rem; border-radius:999px; border:1px solid #d1dbe8; background:#f8fafc; padding:.3rem .6rem; font-size:.78rem; font-weight:700; color:#0f172a; }
        .gt-leisure-v2-pill.ok { color:#047857; border-color:#9ddac5; background:rgba(16,185,129,.12); }
        .gt-leisure-v2-empty { border-radius:14px; border:1px dashed #c7d6e8; padding:.95rem; text-align:center; color:#64748b; background:#f8fafc; }
        .gt-leisure-v2-recent { border:1px solid var(--line); border-radius:16px; background:var(--card); overflow:hidden; }
        .gt-leisure-v2-recent > summary { list-style:none; cursor:pointer; padding:.85rem .95rem; font-weight:800; color:#0f172a; display:flex; justify-content:space-between; align-items:center; }
        .gt-leisure-v2-recent > summary::-webkit-details-marker { display:none; }
        .gt-leisure-v2-recent-body { border-top:1px dashed #c9d8e9; padding:.85rem; }
        .gt-leisure-v2-request { padding:.78rem; }
        .gt-leisure-v2-request-top { display:flex; justify-content:space-between; align-items:start; gap:.45rem; margin-bottom:.45rem; }
        .gt-leisure-v2-code { margin:0; font-size:1rem; font-weight:800; color:#0f172a; }
        .gt-leisure-v2-meta { margin:0; color:#64748b; font-size:.83rem; line-height:1.55; }
        .gt-leisure-v2-status { display:inline-flex; border-radius:999px; padding:.24rem .54rem; font-size:.72rem; font-weight:800; white-space:nowrap; }
        .gt-leisure-v2-actions { display:flex; gap:.5rem; margin-top:.65rem; }
        .gt-leisure-v2-link { display:inline-flex; gap:.28rem; align-items:center; border-radius:999px; border:1px solid #d2dbe7; padding:.35rem .62rem; text-decoration:none; font-size:.77rem; font-weight:700; color:#0f172a; background:#fff; }
        .gt-leisure-v2-link.primary { color:#fff; background:#0f172a; border-color:#0f172a; }
        @media (max-width:1199.98px){ .gt-leisure-v2-hero{grid-template-columns:1fr;} .gt-leisure-v2-aside{position:static;} .gt-leisure-v2-grid-3{grid-template-columns:repeat(2,minmax(0,1fr));} }
        @media (max-width:767.98px){ .gt-leisure-v2-shell{padding:1rem .45rem 2rem;} .gt-leisure-v2-gallery{grid-template-columns:1fr;} .gt-leisure-v2-gallery-side{grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:none;} .gt-leisure-v2-grid-3,.gt-leisure-v2-grid-2{grid-template-columns:1fr;} }
        html[data-theme="dark"] .gt-leisure-v2-page{background:linear-gradient(180deg,#08101d 0%,#0b1626 48%,#0a1220 100%);} 
        html[data-theme="dark"] .gt-leisure-v2-main{background:radial-gradient(circle at top right,rgba(56,189,248,.2),transparent 30%),linear-gradient(135deg,#08142b 0%,#12305a 55%,#1e3a6d 100%);} 
        html[data-theme="dark"] .gt-leisure-v2-aside,html[data-theme="dark"] .gt-leisure-v2-card,html[data-theme="dark"] .gt-leisure-v2-media,html[data-theme="dark"] .gt-leisure-v2-request,html[data-theme="dark"] .gt-leisure-v2-variant,html[data-theme="dark"] .gt-leisure-v2-recent{background:rgba(10,20,37,.92);border-color:rgba(96,165,250,.18);} 
        html[data-theme="dark"] .gt-leisure-v2-aside h2,html[data-theme="dark"] .gt-leisure-v2-head h3,html[data-theme="dark"] .gt-leisure-v2-card h4,html[data-theme="dark"] .gt-leisure-v2-variant>summary,html[data-theme="dark"] .gt-leisure-v2-media-title,html[data-theme="dark"] .gt-leisure-v2-recent>summary,html[data-theme="dark"] .gt-leisure-v2-code,html[data-theme="dark"] .gt-leisure-v2-label{color:#f8fafc;} 
        html[data-theme="dark"] .gt-leisure-v2-head p,html[data-theme="dark"] .gt-leisure-v2-meta,html[data-theme="dark"] .gt-leisure-v2-summary,html[data-theme="dark"] .gt-leisure-v2-media-meta{color:#9fb2d9;} 
        html[data-theme="dark"] .gt-leisure-v2-select,html[data-theme="dark"] .gt-leisure-v2-pill,html[data-theme="dark"] .gt-leisure-v2-link,html[data-theme="dark"] .gt-leisure-v2-btn.soft{background:rgba(15,23,42,.8);border-color:rgba(96,165,250,.26);color:#e2e8f0;} 
        html[data-theme="dark"] .gt-leisure-v2-pill.ok{color:#6ee7b7;background:rgba(6,78,59,.35);border-color:rgba(16,185,129,.35);} 
        html[data-theme="dark"] .gt-leisure-v2-empty{background:rgba(15,23,42,.65);border-color:rgba(96,165,250,.24);color:#9fb2d9;} 
    </style>
</head>
<body class="theme-scope gt-leisure-v2-page">
<x-navbar-acente :active="$productType === 'dinner_cruise' ? 'dinner-cruise' : 'yacht-charter'" />

@php
    $isDinner = $productType === 'dinner_cruise';
    $newRoute = route($routePrefix . '.create');
    $siblingRoute = $isDinner ? route('acente.yacht-charter.catalog') : route('acente.dinner-cruise.catalog');
    $siblingLabel = $isDinner ? 'Yacht koleksiyonunu gor' : 'Dinner cruise koleksiyonunu gor';

    $includedExtras = $extraOptions->where('default_included', true)->values();
    $optionalExtras = $extraOptions->where('default_included', false)->values();

    $requestedLevel = strtolower((string) request('package_level', ''));
    $selectedPackage = $packageTemplates->first(function ($package) use ($requestedLevel) {
        return strtolower((string) $package->level) === $requestedLevel;
    });

    if (! $selectedPackage) {
        $selectedPackage = $packageTemplates->first();
    }

    $ctaUrl = $selectedPackage
        ? route($routePrefix . '.create', ['package_level' => $selectedPackage->level])
        : $newRoute;

    $photos = $mediaAssets->where('media_type', 'photo')->values();
    $heroPhotos = $photos->take(3)->values();

    $highlightItems = collect($selectedPackage?->includes_tr ?? [])->filter()->take(4);
    if ($highlightItems->isEmpty()) {
        $highlightItems = $includedExtras->pluck('title_tr')->filter()->take(4);
    }

    $collectionTitle = $isDinner ? 'Bosphorus dinner cruise koleksiyonu' : 'Istanbul yacht charter koleksiyonu';
    $collectionCopy = $isDinner
        ? 'Tek sayfada urun ozeti, paket varyantlari ve medya vitrinini gorup dogrudan talep akisini baslatin.'
        : 'Yacht charter urunlerini sade bir vitrin deneyimiyle inceleyin, paket seviyesini secip hizli talep olusturun.';

    $statusMap = [
        'new' => ['label' => 'Yeni', 'bg' => 'rgba(148,163,184,.16)', 'color' => '#475569'],
        'offer_sent' => ['label' => 'Teklif Verildi', 'bg' => 'rgba(37,99,235,.12)', 'color' => '#1d4ed8'],
        'revised' => ['label' => 'Revize', 'bg' => 'rgba(249,115,22,.16)', 'color' => '#c2410c'],
        'approved' => ['label' => 'Onaylandi', 'bg' => 'rgba(16,185,129,.14)', 'color' => '#047857'],
        'in_operation' => ['label' => 'Operasyonda', 'bg' => 'rgba(168,85,247,.14)', 'color' => '#7c3aed'],
        'completed' => ['label' => 'Tamamlandi', 'bg' => 'rgba(34,197,94,.14)', 'color' => '#15803d'],
        'cancelled' => ['label' => 'Iptal', 'bg' => 'rgba(239,68,68,.14)', 'color' => '#b91c1c'],
    ];
@endphp

<div class="container gt-leisure-v2-shell">
    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    <section class="gt-leisure-v2-hero">
        <article class="gt-leisure-v2-main">
            <span class="gt-leisure-v2-eyebrow">
                <i class="fas {{ $isDinner ? 'fa-utensils' : 'fa-ship' }}" aria-hidden="true"></i>
                {{ $isDinner ? 'Dinner Cruise' : 'Yacht Charter' }} Collection
            </span>

            <h1 class="gt-leisure-v2-title">{{ $collectionTitle }}</h1>
            <p class="gt-leisure-v2-copy">{{ $collectionCopy }}</p>

            <div class="gt-leisure-v2-chip-row">
                @forelse($highlightItems as $item)
                    <span class="gt-leisure-v2-chip"><i class="fas fa-check-circle" aria-hidden="true"></i>{{ $item }}</span>
                @empty
                    <span class="gt-leisure-v2-chip"><i class="fas fa-circle-info" aria-hidden="true"></i>Aktif urun bilgisi eklendikce burada listelenir.</span>
                @endforelse
            </div>

            <div class="gt-leisure-v2-gallery">
                @if($heroPhotos->isNotEmpty())
                    <div class="gt-leisure-v2-gallery-main">
                        <img src="{{ $heroPhotos->first()->resolvedUrl() }}" alt="{{ $heroPhotos->first()->title_tr ?: 'Leisure gorseli' }}" loading="lazy">
                    </div>
                    <div class="gt-leisure-v2-gallery-side">
                        @foreach($heroPhotos->slice(1, 2) as $photo)
                            <div>
                                <img src="{{ $photo->resolvedUrl() }}" alt="{{ $photo->title_tr ?: 'Leisure gorseli' }}" loading="lazy">
                            </div>
                        @endforeach
                        @for($i = $heroPhotos->count(); $i < 3; $i++)
                            <div class="gt-leisure-v2-gallery-empty" style="min-height:122px;">Yeni medya eklendikce bu alan dolacak.</div>
                        @endfor
                    </div>
                @else
                    <div class="gt-leisure-v2-gallery-empty" style="grid-column:1/-1;">Bu urun icin aktif medya kaydi bulunmuyor. Leisure Ayarlari > Medya Kutuphanesi uzerinden ekleyebilirsiniz.</div>
                @endif
            </div>
        </article>

        <aside class="gt-leisure-v2-aside">
            <h2>Hizli teklif karti</h2>
            <p>Paket secimini yapin, tek tikla talep formuna gecin. Fiyatlandirma operasyon tarafinda teklif bazli ilerler.</p>

            <label for="gt-leisure-v2-package-select" class="gt-leisure-v2-label">Paket seviyesi</label>
            <select id="gt-leisure-v2-package-select" class="gt-leisure-v2-select">
                @forelse($packageTemplates as $package)
                    <option value="{{ strtolower((string) $package->level) }}" data-summary="{{ $package->summary_tr ?: 'Bu paket icin ozet bilgisi henuz girilmedi.' }}" @selected($selectedPackage && $selectedPackage->id === $package->id)>
                        {{ strtoupper((string) $package->level) }} · {{ $package->name_tr }}
                    </option>
                @empty
                    <option value="standard">Paket bulunmuyor</option>
                @endforelse
            </select>

            <div id="gt-leisure-v2-package-summary" class="gt-leisure-v2-summary">{{ $selectedPackage?->summary_tr ?: 'Aktif paket tanimi olmadigi icin varsayilan talep formuna yonlendirilirsiniz.' }}</div>

            <a id="gt-leisure-v2-cta-link" class="gt-leisure-v2-btn primary" href="{{ $ctaUrl }}" data-base-url="{{ $newRoute }}"><i class="fas fa-paper-plane" aria-hidden="true"></i>Hemen teklif al</a>
            <a href="{{ route($routePrefix . '.index') }}" class="gt-leisure-v2-btn soft"><i class="fas fa-layer-group" aria-hidden="true"></i>Bu koleksiyonda kal</a>
            <a href="{{ $siblingRoute }}" class="gt-leisure-v2-btn soft"><i class="fas fa-compass" aria-hidden="true"></i>{{ $siblingLabel }}</a>

            <div class="gt-leisure-v2-note">Fiyat bilgisi sabit degil; talebe gore operasyon ekibi tarafindan netlestirilir.</div>
        </aside>
    </section>
    <section class="gt-leisure-v2-section">
        <div class="gt-leisure-v2-head"><div><h3>One Cikanlar</h3><p>Secili paketin gercek icerigini ve servis siniflarini sade kart yapisinda inceleyin.</p></div></div>
        <div class="gt-leisure-v2-grid-3">
            <article class="gt-leisure-v2-card">
                <h4>Secili Paket</h4>
                <ul>
                    <li>{{ $selectedPackage?->name_tr ?: 'Paket tanimli degil' }}</li>
                    <li>Seviye: {{ strtoupper((string) ($selectedPackage?->level ?? 'standard')) }}</li>
                    <li>Fiyatlandirma: Teklif bazli</li>
                </ul>
            </article>
            <article class="gt-leisure-v2-card">
                <h4>Dahil Servisler</h4>
                <ul>
                    @forelse(collect($selectedPackage?->includes_tr ?? [])->filter()->take(4) as $line)
                        <li>{{ $line }}</li>
                    @empty
                        @forelse($includedExtras->pluck('title_tr')->take(4) as $line)
                            <li>{{ $line }}</li>
                        @empty
                            <li>Dahil servis verisi bulunmuyor.</li>
                        @endforelse
                    @endforelse
                </ul>
            </article>
            <article class="gt-leisure-v2-card">
                <h4>Opsiyonel Deneyim</h4>
                <ul>
                    @forelse($optionalExtras->pluck('title_tr')->take(4) as $line)
                        <li>{{ $line }}</li>
                    @empty
                        @forelse(collect($selectedPackage?->excludes_tr ?? [])->filter()->take(4) as $line)
                            <li>{{ $line }}</li>
                        @empty
                            <li>Opsiyonel servis verisi bulunmuyor.</li>
                        @endforelse
                    @endforelse
                </ul>
            </article>
        </div>
    </section>

    <section class="gt-leisure-v2-section" id="product-grid">
        <div class="gt-leisure-v2-head"><div><h3>Paket Varyantlari</h3><p>Her paketin dahil/haric kapsamini ac-kapa modelinde karsilastirin.</p></div></div>
        @if($packageTemplates->isNotEmpty())
            @foreach($packageTemplates as $package)
                @php $isSelected = $selectedPackage && $selectedPackage->id === $package->id; @endphp
                <details class="gt-leisure-v2-variant" @if($isSelected) open @endif>
                    <summary>
                        <span>{{ $package->name_tr }} · {{ strtoupper((string) $package->level) }}</span>
                        <span class="gt-leisure-v2-badge">{{ strtoupper((string) $package->code) }}</span>
                    </summary>
                    <div class="gt-leisure-v2-variant-body">
                        <p class="mb-2 text-muted">{{ $package->summary_tr ?: 'Paket ozeti eklenmedi.' }}</p>
                        <div class="gt-leisure-v2-grid-2">
                            <div>
                                <h5 style="margin:0 0 .35rem;font-size:.78rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#334155;">Dahil Olanlar</h5>
                                <ul>
                                    @forelse(collect($package->includes_tr ?? [])->filter() as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>Dahil listesi yok.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div>
                                <h5 style="margin:0 0 .35rem;font-size:.78rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#334155;">Haric Olanlar</h5>
                                <ul>
                                    @forelse(collect($package->excludes_tr ?? [])->filter() as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>Haric listesi yok.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <a href="{{ route($routePrefix . '.create', ['package_level' => strtolower((string) $package->level)]) }}" class="gt-leisure-v2-link primary mt-3"><i class="fas fa-plus" aria-hidden="true"></i>Bu paketle teklif al</a>
                    </div>
                </details>
            @endforeach
        @else
            <div class="gt-leisure-v2-empty">Henuz aktif paket tanimi bulunmuyor.</div>
        @endif
    </section>

    <section class="gt-leisure-v2-section" id="experience-grid">
        <div class="gt-leisure-v2-head"><div><h3>Medya Galerisi</h3><p>Yalnizca aktif medya kayitlari gosterilir; veri yoksa bos durum mesaji gelir.</p></div></div>
        @if($photos->isNotEmpty())
            <div class="gt-leisure-v2-grid-3">
                @foreach($photos->take(12) as $asset)
                    <article class="gt-leisure-v2-media">
                        <img src="{{ $asset->resolvedUrl() }}" alt="{{ $asset->title_tr ?: 'Leisure gorseli' }}" loading="lazy">
                        <div class="gt-leisure-v2-media-body">
                            <h4 class="gt-leisure-v2-media-title">{{ $asset->title_tr ?: 'Leisure gorseli' }}</h4>
                            <div class="gt-leisure-v2-media-meta">{{ $asset->category ?: 'kategori yok' }}</div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="gt-leisure-v2-empty">Henuz aktif medya kaydi yok.</div>
        @endif
    </section>

    <section class="gt-leisure-v2-section" id="service-grid">
        <div class="gt-leisure-v2-head"><div><h3>Dahil ve Opsiyonel Servisler</h3><p>Standart dahil hizmetler ile upsell alanlari tek bakista ayrisir.</p></div></div>
        <div class="gt-leisure-v2-grid-2">
            <article class="gt-leisure-v2-card">
                <h4>Varsayilan Dahil</h4>
                <div class="gt-leisure-v2-pills">
                    @forelse($includedExtras as $item)
                        <span class="gt-leisure-v2-pill ok"><i class="fas fa-check-circle" aria-hidden="true"></i>{{ $item->title_tr }}</span>
                    @empty
                        <span class="gt-leisure-v2-pill ok">Varsayilan dahil servis tanimi yok.</span>
                    @endforelse
                </div>
            </article>
            <article class="gt-leisure-v2-card">
                <h4>Opsiyonel Upsell</h4>
                <div class="gt-leisure-v2-pills">
                    @forelse($optionalExtras as $item)
                        <span class="gt-leisure-v2-pill"><i class="fas fa-plus-circle" aria-hidden="true"></i>{{ $item->title_tr }}</span>
                    @empty
                        <span class="gt-leisure-v2-pill">Opsiyonel servis tanimi yok.</span>
                    @endforelse
                </div>
            </article>
        </div>
    </section>

    <section class="gt-leisure-v2-section" id="recent-requests">
        <details class="gt-leisure-v2-recent">
            <summary><span>Son talepleriniz</span><span class="gt-leisure-v2-badge">{{ $requests->total() }}</span></summary>
            <div class="gt-leisure-v2-recent-body">
                @if($requests->count() === 0)
                    <div class="gt-leisure-v2-empty">Bu kategoride daha once talep acmadiniz.</div>
                @else
                    <div class="gt-leisure-v2-grid-2">
                        @foreach($requests as $requestItem)
                            @php
                                $detail = $requestItem->product_type === 'dinner_cruise' ? $requestItem->dinnerCruiseDetail : $requestItem->yachtDetail;
                                $status = $statusMap[$requestItem->status] ?? ['label' => $requestItem->status, 'bg' => 'rgba(148,163,184,.16)', 'color' => '#475569'];
                            @endphp
                            <article class="gt-leisure-v2-request">
                                <div class="gt-leisure-v2-request-top">
                                    <div>
                                        <h4 class="gt-leisure-v2-code">{{ $requestItem->gtpnr }}</h4>
                                        <p class="gt-leisure-v2-meta">{{ optional($requestItem->service_date)->format('d.m.Y') }} · {{ $requestItem->guest_count }} kisi</p>
                                    </div>
                                    <span class="gt-leisure-v2-status" style="background: {{ $status['bg'] }}; color: {{ $status['color'] }};">{{ $status['label'] }}</span>
                                </div>
                                <p class="gt-leisure-v2-meta">@if($isDinner){{ $detail?->session_time ?: 'Seans belirtilmedi' }} · {{ $detail?->pier_name ?: 'Iskele belirtilmedi' }}@else{{ $detail?->start_time ?: 'Saat belirtilmedi' }} · {{ $detail?->marina_name ?: 'Marina belirtilmedi' }}@endif</p>
                                <div class="gt-leisure-v2-actions">
                                    <a href="{{ route($routePrefix . '.show', $requestItem) }}" class="gt-leisure-v2-link primary"><i class="fas fa-arrow-right" aria-hidden="true"></i>Detay</a>
                                    <a href="{{ route($routePrefix . '.create', ['package_level' => strtolower((string) ($requestItem->package_level ?: 'standard'))]) }}" class="gt-leisure-v2-link"><i class="fas fa-copy" aria-hidden="true"></i>Benzer ac</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-3">{{ $requests->links() }}</div>
                @endif
            </div>
        </details>
    </section>
</div>

@include('acente.partials.theme-script')
<script>
(() => {
    const select = document.getElementById('gt-leisure-v2-package-select');
    const cta = document.getElementById('gt-leisure-v2-cta-link');
    const summary = document.getElementById('gt-leisure-v2-package-summary');
    if (!select || !cta || !summary) return;

    const baseUrl = cta.dataset.baseUrl || cta.getAttribute('href');
    const buildUrl = (level) => {
        if (!level) return baseUrl;
        const separator = baseUrl.includes('?') ? '&' : '?';
        return `${baseUrl}${separator}package_level=${encodeURIComponent(level)}`;
    };

    const sync = () => {
        const option = select.options[select.selectedIndex];
        if (!option) return;
        cta.setAttribute('href', buildUrl((option.value || 'standard').toLowerCase()));
        summary.textContent = option.dataset.summary || 'Bu paket icin ozet bilgisi henuz girilmedi.';
    };

    select.addEventListener('change', sync);
    sync();
})();
</script>
</body>
</html>

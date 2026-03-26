<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>Dinner Cruise Teklif Vitrini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gt-dc-showcase-page {
            background: radial-gradient(circle at top right, rgba(56, 189, 248, .14), transparent 32%), linear-gradient(180deg, #f3f6fb 0%, #eef2f8 48%, #f8fafc 100%);
            min-height: 100vh;
        }
        .gt-dc-hero {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: linear-gradient(130deg, #0b1730 0%, #123161 48%, #1e3a8a 100%);
            color: #f8fafc;
            box-shadow: 0 32px 80px rgba(15, 23, 42, .24);
        }
        .gt-dc-hero::after {
            content: "";
            position: absolute;
            right: -140px;
            top: -150px;
            width: 360px;
            height: 360px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 255, 255, .22), transparent 66%);
            pointer-events: none;
        }
        .gt-dc-hero .hero-content { position: relative; z-index: 1; }
        .gt-dc-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            padding: .45rem .75rem;
            font-size: .78rem;
            font-weight: 700;
            background: rgba(255, 255, 255, .12);
            color: rgba(248, 250, 252, .95);
            border: 1px solid rgba(255, 255, 255, .18);
        }
        .gt-dc-actions { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1.2rem; }
        .gt-dc-actions .btn { border-radius: 999px; padding: .68rem 1rem; font-weight: 700; }
        .gt-dc-stat-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
        .gt-dc-stat {
            border-radius: 18px;
            padding: .9rem;
            border: 1px solid rgba(255, 255, 255, .14);
            background: rgba(255, 255, 255, .08);
        }
        .gt-dc-stat .value { font-size: 1.8rem; font-weight: 800; line-height: 1; color: #fff; }
        .gt-dc-stat .label { font-size: .75rem; letter-spacing: .05em; text-transform: uppercase; color: rgba(226, 232, 240, .86); }

        .gt-dc-section-title {
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 700;
            letter-spacing: -.02em;
            margin: 0;
            color: #0f172a;
        }
        .gt-dc-section-copy { color: #64748b; margin: .4rem 0 0; }

        .gt-dc-package-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
        .gt-dc-package-card {
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        }
        .gt-dc-package-visual {
            position: relative;
            overflow: hidden;
            color: #fff;
            padding: 1rem;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-size: cover;
            background-position: center;
        }
        .gt-dc-package-visual::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15, 23, 42, .14), rgba(15, 23, 42, .58));
            pointer-events: none;
        }
        .gt-dc-package-visual > * {
            position: relative;
            z-index: 1;
        }
        .gt-dc-package-standard { background: linear-gradient(135deg, #0f766e, #0f172a 72%); }
        .gt-dc-package-vip { background: linear-gradient(135deg, #7c2d12, #1e293b 66%, #be123c); }
        .gt-dc-package-premium { background: linear-gradient(135deg, #312e81, #111827 66%, #0369a1); }
        .gt-dc-badge {
            display: inline-flex;
            border-radius: 999px;
            padding: .35rem .68rem;
            font-size: .73rem;
            font-weight: 700;
            background: rgba(255, 255, 255, .14);
        }
        .gt-dc-package-body { padding: 1rem; }
        .gt-dc-package-body h4 { margin: 0; font-size: 1.35rem; color: #0f172a; font-family: Georgia, "Times New Roman", serif; }
        .gt-dc-package-body p { margin: .45rem 0 0; color: #64748b; min-height: 52px; }
        .gt-dc-mini-list { margin: .75rem 0 0; padding-left: 1rem; color: #475569; font-size: .9rem; line-height: 1.6; }
        .gt-dc-mini-title { margin-top: .75rem; font-size: .74rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #334155; }

        .gt-dc-media-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; }
        .gt-dc-media-card {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .2);
            background: rgba(255, 255, 255, .9);
        }
        .gt-dc-media-card img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            display: block;
        }
        .gt-dc-video-fallback {
            height: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e293b, #334155);
            color: #cbd5e1;
            font-weight: 700;
            letter-spacing: .04em;
        }
        .gt-dc-media-body { padding: .75rem .8rem; }
        .gt-dc-media-title { margin: 0; font-size: .93rem; font-weight: 700; color: #0f172a; }
        .gt-dc-media-meta { margin-top: .25rem; font-size: .78rem; color: #64748b; }

        .gt-dc-extra-wrap {
            border-radius: 20px;
            border: 1px solid rgba(148, 163, 184, .2);
            background: rgba(255, 255, 255, .9);
            padding: 1rem;
        }
        .gt-dc-extra-title { margin: 0 0 .6rem; font-size: .95rem; font-weight: 800; color: #0f172a; }
        .gt-dc-pill-row { display: flex; flex-wrap: wrap; gap: .55rem; }
        .gt-dc-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .45rem .75rem;
            font-size: .8rem;
            font-weight: 700;
            border: 1px solid rgba(148, 163, 184, .25);
            color: #0f172a;
            background: #f8fafc;
        }
        .gt-dc-pill.included {
            background: rgba(16, 185, 129, .12);
            border-color: rgba(16, 185, 129, .25);
            color: #047857;
        }

        html[data-theme="dark"] .gt-dc-showcase-page {
            background: radial-gradient(circle at top right, rgba(56, 189, 248, .16), transparent 32%), linear-gradient(180deg, #07101d 0%, #0a1627 48%, #091120 100%);
        }
        html[data-theme="dark"] .gt-dc-section-title,
        html[data-theme="dark"] .gt-dc-package-body h4,
        html[data-theme="dark"] .gt-dc-media-title,
        html[data-theme="dark"] .gt-dc-extra-title { color: #f8fafc; }
        html[data-theme="dark"] .gt-dc-section-copy,
        html[data-theme="dark"] .gt-dc-package-body p,
        html[data-theme="dark"] .gt-dc-media-meta { color: #9fb2d9; }
        html[data-theme="dark"] .gt-dc-package-card,
        html[data-theme="dark"] .gt-dc-media-card,
        html[data-theme="dark"] .gt-dc-extra-wrap {
            border-color: rgba(96, 165, 250, .16);
            background: rgba(10, 20, 37, .9);
        }
        html[data-theme="dark"] .gt-dc-mini-list { color: #cbd5e1; }
        html[data-theme="dark"] .gt-dc-mini-title { color: #cbd5e1; }
        html[data-theme="dark"] .gt-dc-pill {
            color: #e2e8f0;
            background: rgba(15, 23, 42, .78);
            border-color: rgba(96, 165, 250, .18);
        }
        html[data-theme="dark"] .gt-dc-pill.included {
            background: rgba(6, 78, 59, .3);
            border-color: rgba(16, 185, 129, .28);
            color: #6ee7b7;
        }

        @media (max-width: 1199.98px) {
            .gt-dc-package-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .gt-dc-media-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 991.98px) {
            .gt-dc-stat-grid { grid-template-columns: 1fr; }
            .gt-dc-package-grid,
            .gt-dc-media-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="theme-scope gt-dc-showcase-page">
<x-navbar-superadmin active="dinner-cruise" />

@php
    $packageCount = $packages->count();
    $mediaCount = $mediaAssets->count();
    $includedCount = $includedExtras->count();
@endphp

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="gt-dc-hero p-4 p-lg-5 mb-4">
        <div class="row g-4 align-items-end hero-content">
            <div class="col-12 col-lg-8">
                <span class="gt-dc-chip"><i class="fas fa-sparkles"></i>Superadmin sunum vitrini</span>
                <h2 class="mt-3 mb-2 fw-bold" style="font-size:clamp(2rem,4vw,3rem);font-family:Georgia, 'Times New Roman', serif;">Dinner Cruise Teklif Vitrini</h2>
                <p class="mb-0 text-white-50" style="max-width:70ch;line-height:1.8;">
                    Superadmin tarafinda tanimlanan paket, medya ve servisleri tek bir premium vitrinde gorun.
                    Bu ekran "ne satildigini" operasyon tablosundan bagimsiz, katalog kalitesinde gostermek icin var.
                </p>
                <div class="gt-dc-actions">
                    <a href="{{ route('superadmin.leisure.settings.index') }}" class="btn btn-light text-dark"><i class="fas fa-sliders me-1"></i>Leisure Ayarlari</a>
                    <a href="{{ route('superadmin.dinner-cruise.index') }}" class="btn btn-outline-light"><i class="fas fa-list-check me-1"></i>Talep Listesi</a>
                    <a href="{{ route('acente.dinner-cruise.index') }}" class="btn btn-outline-light"><i class="fas fa-eye me-1"></i>Acenta Vitrinini Gor</a>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="gt-dc-stat-grid">
                    <div class="gt-dc-stat">
                        <div class="label">Aktif paket</div>
                        <div class="value">{{ $packageCount }}</div>
                    </div>
                    <div class="gt-dc-stat">
                        <div class="label">Aktif medya</div>
                        <div class="value">{{ $mediaCount }}</div>
                    </div>
                    <div class="gt-dc-stat">
                        <div class="label">Varsayilan servis</div>
                        <div class="value">{{ $includedCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h3 class="gt-dc-section-title">Paket Teklifleri</h3>
            <p class="gt-dc-section-copy">Leisure ayarlarindan eklediginiz dinner cruise paketleri canli vitrinde.</p>
        </div>
    </div>

    <div class="gt-dc-package-grid mb-4">
        @forelse($packages as $package)
            @php
                $levelClass = match(strtolower((string) $package->level)) {
                    'vip' => 'gt-dc-package-vip',
                    'premium' => 'gt-dc-package-premium',
                    default => 'gt-dc-package-standard',
                };
                $heroImage = trim((string) ($package->hero_image_url ?? ''));
                if ($heroImage !== '' && ! str_starts_with($heroImage, 'http://') && ! str_starts_with($heroImage, 'https://') && ! str_starts_with($heroImage, '/')) {
                    $heroImage = '/' . ltrim($heroImage, '/');
                }
                $includes = collect($package->includes_tr ?? [])->filter()->take(4)->values();
                $excludes = collect($package->excludes_tr ?? [])->filter()->take(3)->values();
            @endphp
            <article class="gt-dc-package-card">
                <div class="gt-dc-package-visual {{ $levelClass }}" @if($heroImage !== '') style="background-image: url('{{ $heroImage }}');" @endif>
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <span class="gt-dc-badge">Level: {{ strtoupper((string) $package->level) }}</span>
                        <span class="gt-dc-badge">{{ strtoupper((string) $package->code) }}</span>
                    </div>
                    <div>
                        <div class="small text-white-50 mb-1">Dinner Cruise Collection</div>
                        <div class="h5 mb-0 fw-bold">{{ $package->name_tr }}</div>
                    </div>
                </div>
                <div class="gt-dc-package-body">
                    <h4>{{ $package->name_en ?: $package->name_tr }}</h4>
                    <p>{{ $package->summary_tr ?: 'Paket ozeti eklenmediyse bu alan otomatik placeholder ile gorunur.' }}</p>

                    <div class="gt-dc-mini-title">Dahil Olanlar</div>
                    <ul class="gt-dc-mini-list">
                        @forelse($includes as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>Heniz dahil listesi girilmedi.</li>
                        @endforelse
                    </ul>

                    <div class="gt-dc-mini-title">Haric Olanlar</div>
                    <ul class="gt-dc-mini-list mb-0">
                        @forelse($excludes as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>Haric listesi girilmedi.</li>
                        @endforelse
                    </ul>
                </div>
            </article>
        @empty
            <div class="p-4 rounded-4 border bg-white text-muted" style="grid-column:1/-1;border-color:rgba(148,163,184,.2)!important;">
                Henuz aktif dinner cruise paketi bulunmuyor. Paket eklemek icin Leisure Ayarlari ekranini kullanabilirsiniz.
            </div>
        @endforelse
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h3 class="gt-dc-section-title">Medya Vitrini</h3>
            <p class="gt-dc-section-copy">Katalog kalitesinde galeri: aktif medya kutuphanesi otomatik yansir.</p>
        </div>
    </div>

    <div class="gt-dc-media-grid mb-4">
        @forelse($mediaAssets as $asset)
            <article class="gt-dc-media-card">
                @if($asset->media_type === 'photo' && $asset->resolvedUrl())
                    <img src="{{ $asset->resolvedUrl() }}" alt="{{ $asset->title_tr }}">
                @else
                    <div class="gt-dc-video-fallback">VIDEO</div>
                @endif
                <div class="gt-dc-media-body">
                    <h4 class="gt-dc-media-title">{{ $asset->title_tr ?: 'Leisure medya' }}</h4>
                    <div class="gt-dc-media-meta">{{ $asset->category ?: 'kategori yok' }} · {{ strtoupper($asset->media_type) }}</div>
                </div>
            </article>
        @empty
            <div class="p-4 rounded-4 border bg-white text-muted" style="grid-column:1/-1;border-color:rgba(148,163,184,.2)!important;">
                Henuz aktif medya kaydi yok. Leisure Ayarlari > Medya Kutuphanesi bolumunden ekleyebilirsiniz.
            </div>
        @endforelse
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="gt-dc-extra-wrap">
                <h4 class="gt-dc-extra-title">Varsayilan Dahil Servisler</h4>
                <div class="gt-dc-pill-row">
                    @forelse($includedExtras as $item)
                        <span class="gt-dc-pill included"><i class="fas fa-check-circle"></i>{{ $item->title_tr }}</span>
                    @empty
                        <span class="gt-dc-pill included">Varsayilan dahil servis tanimlanmadi.</span>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="gt-dc-extra-wrap">
                <h4 class="gt-dc-extra-title">Opsiyonel Upsell Servisleri</h4>
                <div class="gt-dc-pill-row">
                    @forelse($optionalExtras as $item)
                        <span class="gt-dc-pill"><i class="fas fa-plus-circle"></i>{{ $item->title_tr }}</span>
                    @empty
                        <span class="gt-dc-pill">Opsiyonel servis tanimlanmadi.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.theme-script')
</body>
</html>

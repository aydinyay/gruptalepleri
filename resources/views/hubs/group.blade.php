<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - GrupTalepleri</title>

    @if(in_array($role, ['admin', 'superadmin'], true))
        @include('admin.partials.theme-styles')
    @else
        @include('acente.partials.theme-styles')
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gt-hub-page {
            background: linear-gradient(180deg, #f6f8fc 0%, #eef2f8 52%, #f8fafc 100%);
            min-height: 100vh;
        }
        .gt-hub-shell {
            padding: 1.4rem 0 2.6rem;
        }
        .gt-hub-hero {
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 24px;
            padding: 1.3rem 1.3rem 1.1rem;
            background:
                radial-gradient(circle at top right, rgba(147, 197, 253, 0.2), transparent 38%),
                linear-gradient(145deg, #0f1a33 0%, #112346 52%, #193a6c 100%);
            color: #f8fafc;
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.24);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        .gt-hub-title {
            margin: 0;
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            line-height: 1.08;
            font-weight: 800;
        }
        .gt-hub-subtitle {
            margin: .65rem 0 0;
            max-width: 74ch;
            color: rgba(226, 232, 240, 0.9);
            font-size: .96rem;
            line-height: 1.75;
        }
        .gt-hub-top-links {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-top: 1rem;
        }
        .gt-hub-top-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            text-decoration: none;
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            padding: .48rem .85rem;
            font-size: .84rem;
            font-weight: 700;
        }
        .gt-hub-top-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.2);
        }
        .gt-hub-section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: .8rem;
            margin: 1.45rem 0 .85rem;
        }
        .gt-hub-section-title {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
        }
        .gt-hub-section-note {
            margin: 0;
            color: #64748b;
            font-size: .88rem;
        }
        .gt-hub-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .95rem;
        }
        .gt-hub-premium {
            margin-top: 1.65rem;
            border: 1px solid rgba(148, 163, 184, 0.26);
            border-radius: 24px;
            background: linear-gradient(180deg, #f8fbff 0%, #f2f6ff 100%);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.1);
            padding: 1.15rem;
        }
        .gt-hub-premium-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: .7rem;
            margin-bottom: .85rem;
        }
        .gt-hub-premium-title {
            margin: 0;
            font-size: 1.28rem;
            font-weight: 800;
            color: #0f172a;
        }
        .gt-hub-premium-note {
            margin: .25rem 0 0;
            color: #475569;
            font-size: .86rem;
        }
        .gt-hub-premium-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .95rem;
        }
        .gt-hub-premium-card {
            border: 1px solid rgba(148, 163, 184, 0.32);
            border-radius: 18px;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.1);
        }
        .gt-hub-premium-figure {
            position: relative;
            aspect-ratio: 16/9;
            overflow: hidden;
            background: #0f172a;
        }
        .gt-hub-premium-figure img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .gt-hub-premium-code {
            position: absolute;
            top: .6rem;
            left: .6rem;
            border-radius: 999px;
            padding: .25rem .55rem;
            background: rgba(15, 23, 42, 0.74);
            color: #e2e8f0;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .02em;
        }
        .gt-hub-premium-body {
            padding: .9rem .9rem .95rem;
            display: flex;
            flex-direction: column;
            gap: .65rem;
            flex: 1;
        }
        .gt-hub-premium-card-title {
            margin: 0;
            font-size: 1.03rem;
            line-height: 1.25;
            font-weight: 800;
            color: #0f172a;
        }
        .gt-hub-premium-card-text {
            margin: 0;
            color: #334155;
            font-size: .84rem;
            line-height: 1.5;
            min-height: 38px;
        }
        .gt-hub-premium-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .42rem;
        }
        .gt-hub-premium-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .42);
            background: #f8fafc;
            color: #0f172a;
            font-size: .73rem;
            font-weight: 700;
            padding: .28rem .56rem;
        }
        .gt-hub-premium-highlights {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-top: -.15rem;
        }
        .gt-hub-premium-highlight {
            font-size: .72rem;
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.3);
            background: rgba(59, 130, 246, 0.08);
            border-radius: 999px;
            padding: .2rem .48rem;
        }
        .gt-hub-premium-cta {
            margin-top: auto;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: .4rem;
            text-decoration: none;
            border-radius: 10px;
            background: #0f172a;
            color: #f8fafc;
            font-size: .85rem;
            font-weight: 700;
            padding: .56rem .8rem;
        }
        .gt-hub-premium-cta:hover {
            background: #1e293b;
            color: #fff;
        }
        .gt-hub-premium-empty {
            border: 1px dashed rgba(148, 163, 184, 0.5);
            border-radius: 14px;
            background: #fff;
            color: #475569;
            text-align: center;
            padding: 1rem;
            font-size: .88rem;
        }
        .gt-hub-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 245px;
            border-radius: 22px;
            padding: 1rem;
            text-decoration: none;
            color: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.24);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.22);
            position: relative;
            overflow: hidden;
        }
        .gt-hub-card::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            right: -60px;
            top: -70px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.24), transparent 72%);
            pointer-events: none;
        }
        .gt-hub-card.theme-indigo {
            background: linear-gradient(145deg, #1e1b4b 0%, #1e3a8a 50%, #0f172a 100%);
        }
        .gt-hub-card.theme-cyan {
            background: linear-gradient(145deg, #0f172a 0%, #0c4a6e 50%, #0369a1 100%);
        }
        .gt-hub-card.theme-purple {
            background: linear-gradient(145deg, #3b0764 0%, #4c1d95 48%, #1e293b 100%);
        }
        .gt-hub-card.theme-orange {
            background: linear-gradient(145deg, #7c2d12 0%, #9a3412 48%, #1f2937 100%);
        }
        .gt-hub-card-tag {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .72rem;
            border-radius: 999px;
            padding: .35rem .65rem;
            background: rgba(255, 255, 255, 0.18);
            font-weight: 700;
            width: fit-content;
        }
        .gt-hub-card-title {
            margin: .8rem 0 .45rem;
            font-size: 1.28rem;
            line-height: 1.15;
            font-weight: 800;
        }
        .gt-hub-card-text {
            margin: 0;
            color: rgba(226, 232, 240, 0.9);
            font-size: .9rem;
            line-height: 1.72;
            min-height: 72px;
        }
        .gt-hub-card-cta {
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .84rem;
            font-weight: 700;
            color: rgba(248, 250, 252, 0.96);
        }
        .gt-hub-card:hover {
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.28);
        }
        .gt-hub-card-icon {
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            font-size: 1.8rem;
            color: rgba(255, 255, 255, 0.5);
        }
        html[data-theme="dark"] .gt-hub-page {
            background: linear-gradient(180deg, #08111f 0%, #0c162a 50%, #0b1323 100%);
        }
        html[data-theme="dark"] .gt-hub-section-title {
            color: #f8fafc;
        }
        html[data-theme="dark"] .gt-hub-section-note {
            color: #9fb2d9;
        }
        html[data-theme="dark"] .gt-hub-premium {
            border-color: #2d4371;
            background: linear-gradient(180deg, #0e1e39 0%, #0b1a32 100%);
            box-shadow: 0 18px 34px rgba(2, 8, 23, 0.4);
        }
        html[data-theme="dark"] .gt-hub-premium-title {
            color: #dbeafe;
        }
        html[data-theme="dark"] .gt-hub-premium-note {
            color: #9fb2d9;
        }
        html[data-theme="dark"] .gt-hub-premium-card {
            border-color: #2d4371;
            background: #0f1e3d;
            box-shadow: 0 12px 24px rgba(2, 8, 23, 0.32);
        }
        html[data-theme="dark"] .gt-hub-premium-card-title {
            color: #e5e7eb;
        }
        html[data-theme="dark"] .gt-hub-premium-card-text {
            color: #aab8d8;
        }
        html[data-theme="dark"] .gt-hub-premium-chip {
            border-color: #335691;
            background: #12284c;
            color: #dbeafe;
        }
        html[data-theme="dark"] .gt-hub-premium-highlight {
            color: #9bc3ff;
            border-color: #335691;
            background: #1a325c;
        }
        html[data-theme="dark"] .gt-hub-premium-cta {
            background: #2563eb;
            color: #eff6ff;
        }
        html[data-theme="dark"] .gt-hub-premium-cta:hover {
            background: #1d4ed8;
        }
        html[data-theme="dark"] .gt-hub-premium-empty {
            border-color: #335691;
            background: #102246;
            color: #9fb2d9;
        }
        @media (max-width: 991.98px) {
            .gt-hub-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .gt-hub-premium-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 767.98px) {
            .gt-hub-grid {
                grid-template-columns: 1fr;
            }
            .gt-hub-premium {
                padding: .9rem;
            }
            .gt-hub-premium-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="theme-scope gt-hub-page">
<x-dynamic-component :component="$navbarComponent" :active="$active" />

<div class="container gt-hub-shell">
    <section class="gt-hub-hero">
        <h1 class="gt-hub-title">{{ $title }}</h1>
        <p class="gt-hub-subtitle">{{ $subtitle }}</p>

        @if(!empty($links))
            <div class="gt-hub-top-links">
                @foreach($links as $link)
                    <a href="{{ $link['url'] }}" class="gt-hub-top-link">
                        <i class="{{ $link['icon'] }}" aria-hidden="true"></i>{{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <div class="gt-hub-section-head">
            <h2 class="gt-hub-section-title">Vitrin Kartlari</h2>
            <p class="gt-hub-section-note">Grupla ilgili sayfalara kart yapisiyla hizli gecis.</p>
        </div>

        <div class="gt-hub-grid">
            @foreach($cards as $card)
                <a href="{{ $card['url'] }}" class="gt-hub-card {{ $card['theme'] }}">
                    <div>
                        <span class="gt-hub-card-tag"><i class="fas fa-sparkles" aria-hidden="true"></i>{{ $card['tag'] }}</span>
                        <h3 class="gt-hub-card-title">{{ $card['title'] }}</h3>
                        <p class="gt-hub-card-text">{{ $card['description'] }}</p>
                    </div>
                    <span class="gt-hub-card-cta">Sayfaya git <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                    <i class="{{ $card['icon'] }} gt-hub-card-icon" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    </section>

    @if(($showPremiumPackages ?? false) === true)
        <section class="gt-hub-premium">
            <div class="gt-hub-premium-head">
                <div>
                    <h2 class="gt-hub-premium-title">Premium Hazir Paketler</h2>
                    <p class="gt-hub-premium-note">Ustte ucak gorseli, altta rota/fiyat detaylari ve hizli rezervasyon akisi.</p>
                </div>
            </div>

            @if(!empty($premiumPackages))
                <div class="gt-hub-premium-grid">
                    @foreach($premiumPackages as $package)
                        <article class="gt-hub-premium-card">
                            <figure class="gt-hub-premium-figure mb-0">
                                <img src="{{ $package['hero_image_url'] }}" alt="{{ $package['title'] }} ucak gorseli" loading="lazy">
                                <span class="gt-hub-premium-code">{{ strtoupper($package['code']) }}</span>
                            </figure>
                            <div class="gt-hub-premium-body">
                                <h3 class="gt-hub-premium-card-title">{{ $package['title'] }}</h3>
                                <p class="gt-hub-premium-card-text">{{ $package['summary'] }}</p>

                                <div class="gt-hub-premium-meta">
                                    <span class="gt-hub-premium-chip"><i class="fas fa-route" aria-hidden="true"></i>{{ $package['from_iata'] }} -> {{ $package['to_iata'] }}</span>
                                    <span class="gt-hub-premium-chip"><i class="fas fa-users" aria-hidden="true"></i>{{ $package['pax'] }} PAX</span>
                                    <span class="gt-hub-premium-chip"><i class="fas fa-plane" aria-hidden="true"></i>{{ $package['aircraft_label'] }}</span>
                                    <span class="gt-hub-premium-chip"><i class="fas fa-tag" aria-hidden="true"></i>{{ number_format((float) $package['price'], 0, ',', '.') }} {{ $package['currency'] }}</span>
                                </div>

                                @if(!empty($package['highlights']))
                                    <div class="gt-hub-premium-highlights">
                                        @foreach($package['highlights'] as $highlight)
                                            <span class="gt-hub-premium-highlight">{{ $highlight }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <a href="{{ $package['cta_url'] }}" class="gt-hub-premium-cta">
                                    {{ $package['cta_label'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="gt-hub-premium-empty">
                    Su an aktif hazir paket bulunmuyor. Superadmin panelinden paket ekleyerek vitrine yansitabilirsiniz.
                </div>
            @endif
        </section>
    @endif
</div>

@if(in_array($role, ['admin', 'superadmin'], true))
    @include('admin.partials.theme-script')
@else
    @include('acente.partials.theme-script')
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
        :root {
            --bg: #f4f7fb;
            --ink: #12213f;
            --muted: #5b6785;
            --accent: #e94e77;
            --card: #ffffff;
            --line: #dce3ef;
            --ok: #0f766e;
        }
        body {
            background: var(--bg);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            line-height: 1.55;
        }
        /* Hero */
        .hub-hero {
            background: linear-gradient(125deg, #0f1f42 0%, #19356e 52%, #204993 100%);
            color: #fff;
            padding: 48px 20px 40px;
            margin-bottom: 32px;
        }
        .hub-container { max-width: 1120px; margin: 0 auto; }
        .hub-badge {
            display: inline-block;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 99px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 14px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .hub-hero h1 {
            margin: 0 0 12px;
            font-size: clamp(26px, 4vw, 44px);
            font-weight: 800;
            line-height: 1.12;
        }
        .hub-hero p {
            margin: 0;
            max-width: 680px;
            font-size: 16px;
            color: rgba(255,255,255,.85);
            line-height: 1.7;
        }
        .hub-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 20px;
        }
        .hub-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: #e2e8f0;
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 99px;
            background: rgba(255,255,255,.1);
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 700;
            transition: .15s;
        }
        .hub-link:hover { color: #fff; background: rgba(255,255,255,.2); }

        /* Section */
        .hub-section { padding: 0 20px 40px; }
        .hub-section-head { margin-bottom: 16px; }
        .hub-section-head h2 { margin: 0 0 4px; font-size: clamp(20px, 2.5vw, 28px); color: var(--ink); font-weight: 800; }
        .hub-section-head p { margin: 0; color: var(--muted); font-size: 14px; }

        /* Nav cards */
        .hub-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
        .hub-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 18px;
            padding: 20px;
            text-decoration: none;
            color: #f8fafc;
            border: 1px solid rgba(255,255,255,.1);
            box-shadow: 0 12px 32px rgba(15,23,42,.18);
            min-height: 200px;
            position: relative;
            overflow: hidden;
            transition: transform .15s, box-shadow .15s;
        }
        .hub-card::after {
            content: "";
            position: absolute;
            width: 160px; height: 160px;
            right: -50px; top: -60px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(255,255,255,.2), transparent 70%);
            pointer-events: none;
        }
        .hub-card:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 20px 44px rgba(15,23,42,.24); }
        .hub-card.theme-indigo { background: linear-gradient(145deg, #1e1b4b 0%, #1e3a8a 50%, #0f172a 100%); }
        .hub-card.theme-cyan   { background: linear-gradient(145deg, #0f172a 0%, #0c4a6e 50%, #0369a1 100%); }
        .hub-card.theme-purple { background: linear-gradient(145deg, #3b0764 0%, #4c1d95 48%, #1e293b 100%); }
        .hub-card.theme-orange { background: linear-gradient(145deg, #7c2d12 0%, #9a3412 48%, #1f2937 100%); }
        .hub-card.theme-green  { background: linear-gradient(145deg, #064e3b 0%, #065f46 50%, #0f172a 100%); }
        .hub-card.theme-slate  { background: linear-gradient(145deg, #1e293b 0%, #334155 50%, #0f172a 100%); }
        .hub-card-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            border-radius: 99px;
            padding: 4px 10px;
            background: rgba(255,255,255,.15);
            width: fit-content;
            margin-bottom: 10px;
        }
        .hub-card h3 { margin: 0 0 6px; font-size: 18px; font-weight: 800; line-height: 1.2; }
        .hub-card p  { margin: 0; color: rgba(226,232,240,.88); font-size: 13px; line-height: 1.65; }
        .hub-card-cta {
            margin-top: 16px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            font-weight: 700;
            color: rgba(248,250,252,.9);
        }
        .hub-card-icon {
            position: absolute;
            right: 16px; bottom: 16px;
            font-size: 28px;
            color: rgba(255,255,255,.18);
        }

        /* Premium packages (charter) */
        .hub-premium {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 20px;
            margin-top: 8px;
        }
        .hub-premium-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 14px;
        }
        .hub-premium-card {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--card);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 6px 18px rgba(15,23,42,.07);
        }
        .hub-premium-figure {
            position: relative;
            aspect-ratio: 16/9;
            overflow: hidden;
            background: #0f172a;
        }
        .hub-premium-figure img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .hub-premium-code {
            position: absolute;
            top: 8px; left: 8px;
            border-radius: 99px;
            padding: 3px 9px;
            background: rgba(15,23,42,.72);
            color: #e2e8f0;
            font-size: 11px;
            font-weight: 700;
        }
        .hub-premium-body { padding: 14px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .hub-premium-body h4 { margin: 0; font-size: 15px; font-weight: 800; color: var(--ink); }
        .hub-premium-body p  { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.5; }
        .hub-premium-meta { display: flex; flex-wrap: wrap; gap: 5px; }
        .hub-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 99px;
            border: 1px solid var(--line);
            background: #f8fafc;
            color: var(--ink);
            font-size: 12px;
            font-weight: 700;
            padding: 3px 9px;
        }
        .hub-highlights { display: flex; flex-wrap: wrap; gap: 4px; }
        .hub-highlight {
            font-size: 11px;
            color: #1d4ed8;
            border: 1px solid rgba(59,130,246,.3);
            background: rgba(59,130,246,.08);
            border-radius: 99px;
            padding: 2px 8px;
        }
        .hub-premium-cta {
            margin-top: auto;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            border-radius: 10px;
            background: var(--ink);
            color: #f8fafc;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 14px;
        }
        .hub-premium-cta:hover { background: #1e293b; color: #fff; }
        .hub-premium-empty {
            border: 1px dashed var(--line);
            border-radius: 12px;
            background: #f8fafc;
            color: var(--muted);
            text-align: center;
            padding: 16px;
            font-size: 13px;
        }

        /* Dark mode */
        html[data-theme="dark"] body { --bg: #07101d; --ink: #e2e8f0; --muted: #9fb2d9; --card: #0a1627; --line: rgba(96,165,250,.16); }
        html[data-theme="dark"] .hub-section-head h2 { color: #f8fafc; }
        html[data-theme="dark"] .hub-premium { border-color: rgba(96,165,250,.2); }
        html[data-theme="dark"] .hub-premium-card { background: #0f1e3d; border-color: rgba(96,165,250,.18); }
        html[data-theme="dark"] .hub-premium-body h4 { color: #f8fafc; }
        html[data-theme="dark"] .hub-chip { background: #12284c; border-color: #335691; color: #dbeafe; }
        html[data-theme="dark"] .hub-highlight { color: #9bc3ff; border-color: #335691; background: #1a325c; }
        html[data-theme="dark"] .hub-premium-cta { background: #2563eb; }
        html[data-theme="dark"] .hub-premium-empty { background: #102246; border-color: #335691; color: #9fb2d9; }

        @media (max-width: 991px) {
            .hub-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .hub-premium-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .hub-grid { grid-template-columns: 1fr; }
            .hub-premium-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="theme-scope">
<x-dynamic-component :component="$navbarComponent" :active="$active" />

<div class="hub-hero">
    <div class="hub-container">
        <span class="hub-badge"><i class="fas fa-layer-group me-1"></i>{{ $title }}</span>
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
        @if(!empty($links))
            <div class="hub-links">
                @foreach($links as $link)
                    <a href="{{ $link['url'] }}" class="hub-link">
                        <i class="{{ $link['icon'] }}"></i>{{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="hub-section">
    <div class="hub-container">
        <div class="hub-section-head">
            <h2>Hızlı Erişim</h2>
            <p>Bölümle ilgili sayfalara doğrudan geçiş.</p>
        </div>
        <div class="hub-grid">
            @foreach($cards as $card)
                <a href="{{ $card['url'] }}" class="hub-card {{ $card['theme'] }}">
                    <div>
                        <span class="hub-card-tag"><i class="fas fa-sparkles"></i>{{ $card['tag'] }}</span>
                        <h3>{{ $card['title'] }}</h3>
                        <p>{{ $card['description'] }}</p>
                    </div>
                    <span class="hub-card-cta">Sayfaya git <i class="fas fa-arrow-right"></i></span>
                    <i class="{{ $card['icon'] }} hub-card-icon"></i>
                </a>
            @endforeach
        </div>

        @if(($showPremiumPackages ?? false) === true)
            <div class="hub-premium mt-4">
                <div class="hub-section-head mb-0">
                    <h2>Premium Hazır Paketler</h2>
                    <p>Üstte uçak görseli, altta rota/fiyat detayları ve hızlı rezervasyon akışı.</p>
                </div>

                @if(!empty($premiumPackages))
                    <div class="hub-premium-grid">
                        @foreach($premiumPackages as $package)
                            <article class="hub-premium-card">
                                <figure class="hub-premium-figure mb-0">
                                    <img src="{{ $package['hero_image_url'] }}" alt="{{ $package['title'] }}" loading="lazy">
                                    <span class="hub-premium-code">{{ strtoupper($package['code']) }}</span>
                                </figure>
                                <div class="hub-premium-body">
                                    <h4>{{ $package['title'] }}</h4>
                                    <p>{{ $package['summary'] }}</p>
                                    <div class="hub-premium-meta">
                                        <span class="hub-chip"><i class="fas fa-route"></i>{{ $package['from_iata'] }} → {{ $package['to_iata'] }}</span>
                                        <span class="hub-chip"><i class="fas fa-users"></i>{{ $package['pax'] }} PAX</span>
                                        <span class="hub-chip"><i class="fas fa-plane"></i>{{ $package['aircraft_label'] }}</span>
                                        <span class="hub-chip"><i class="fas fa-tag"></i>{{ number_format((float) $package['price'], 0, ',', '.') }} {{ $package['currency'] }}</span>
                                    </div>
                                    @if(!empty($package['highlights']))
                                        <div class="hub-highlights">
                                            @foreach($package['highlights'] as $h)
                                                <span class="hub-highlight">{{ $h }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <a href="{{ $package['cta_url'] }}" class="hub-premium-cta">
                                        {{ $package['cta_label'] }} <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="hub-premium-empty mt-3">
                        Şu an aktif hazır paket bulunmuyor. Superadmin panelinden paket ekleyerek vitrini doldurun.
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

@if(in_array($role, ['admin', 'superadmin'], true))
    @include('admin.partials.theme-script')
@else
    @include('acente.partials.theme-script')
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

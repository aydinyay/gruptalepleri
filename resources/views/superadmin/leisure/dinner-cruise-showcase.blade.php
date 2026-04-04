<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>Bosphorus Dinner Cruise B2B | GrupTalepleri</title>
    <meta name="description" content="Acenteler için Bosphorus Dinner Cruise satış sayfası: net fiyatlar, kontenjan desteği, operasyon kolaylığı ve hızlı teklif süreci.">
    <style>
        :root {
            --bg: #f4f7fb;
            --ink: #12213f;
            --muted: #5b6785;
            --accent: #e94e77;
            --accent-dark: #cd3e63;
            --card: #ffffff;
            --line: #dce3ef;
            --ok: #0f766e;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: var(--bg);
            line-height: 1.55;
        }
        .hero {
            background: linear-gradient(125deg, #0f1f42 0%, #19356e 52%, #204993 100%);
            color: #fff;
            padding: 56px 20px 48px;
        }
        .showcase-container { max-width: 1120px; margin: 0 auto; }
        .badge-chip {
            display: inline-block;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.22);
            border-radius: 99px;
            padding: 6px 14px;
            font-size: 13px;
            margin-bottom: 14px;
        }
        .hero h1 {
            margin: 0 0 14px;
            font-size: clamp(28px, 4.5vw, 48px);
            line-height: 1.14;
            max-width: 820px;
        }
        .hero .lead {
            margin: 0;
            max-width: 700px;
            font-size: 17px;
            color: rgba(255,255,255,.88);
        }
        .hero-actions {
            display: flex;
            gap: 10px;
            margin-top: 22px;
            flex-wrap: wrap;
        }
        .btn-sc {
            text-decoration: none;
            font-weight: 700;
            border-radius: 10px;
            padding: 11px 18px;
            display: inline-block;
            font-size: 14px;
            transition: .15s ease;
        }
        .btn-sc-primary { background: var(--accent); color: #fff; }
        .btn-sc-primary:hover { background: var(--accent-dark); color: #fff; }
        .btn-sc-ghost { color: #fff; border: 1px solid rgba(255,255,255,.4); }
        .btn-sc-ghost:hover { border-color: rgba(255,255,255,.75); color: #fff; }
        .btn-sc-light { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.2); }
        .btn-sc-light:hover { background: rgba(255,255,255,.2); color: #fff; }

        .sc-section { padding: 44px 20px 8px; }
        .sc-section h2 { margin: 0 0 8px; font-size: clamp(22px, 3vw, 32px); color: var(--ink); }
        .sc-section .sub { color: var(--muted); margin: 0 0 18px; }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0,1fr));
            gap: 16px;
        }
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 14px;
        }
        .sc-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 20px;
        }
        .sc-card h3 { margin: 0 0 8px; font-size: 17px; color: var(--ink); }
        .sc-card p { margin: 0; color: var(--muted); font-size: 14px; }
        .sc-card ul { margin: 10px 0 0; padding-left: 18px; color: var(--muted); font-size: 14px; line-height: 1.7; }

        /* Package cards with gradient visuals */
        .pkg-card {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--line);
            background: var(--card);
            box-shadow: 0 8px 24px rgba(15,23,42,.07);
        }
        .pkg-visual {
            position: relative;
            padding: 16px;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-size: cover;
            background-position: center;
            color: #fff;
        }
        .pkg-visual::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(15,23,42,.18), rgba(15,23,42,.62));
            pointer-events: none;
        }
        .pkg-visual > * { position: relative; z-index: 1; }
        .pkg-standard { background: linear-gradient(135deg, #0f766e, #0f172a 72%); }
        .pkg-vip { background: linear-gradient(135deg, #7c2d12, #1e293b 66%, #be123c); }
        .pkg-premium { background: linear-gradient(135deg, #312e81, #111827 66%, #0369a1); }
        .pkg-default { background: linear-gradient(135deg, #1e3a5f, #0f172a); }
        .pkg-level-badge {
            display: inline-block;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 99px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 700;
        }
        .pkg-body { padding: 16px; }
        .pkg-body h4 { margin: 0 0 6px; font-size: 17px; color: var(--ink); }
        .pkg-body p { margin: 0 0 10px; color: var(--muted); font-size: 13px; min-height: 40px; }
        .pkg-body ul { margin: 0; padding-left: 16px; color: var(--muted); font-size: 13px; line-height: 1.7; }
        .pkg-section-label { font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #64748b; margin: 10px 0 4px; }

        /* Steps */
        .step-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
        }
        .step-card strong { display: block; color: var(--ok); font-size: 12px; font-weight: 800; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .05em; }
        .step-card p { margin: 0; color: var(--muted); font-size: 14px; }

        /* Extras pills */
        .pill-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 99px;
            padding: 5px 12px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--line);
            color: var(--ink);
            background: #f8fafc;
        }
        .pill.included {
            background: rgba(15,118,110,.1);
            border-color: rgba(15,118,110,.25);
            color: var(--ok);
        }

        /* CTA box */
        .cta-box {
            margin: 40px auto 56px;
            max-width: 1120px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 28px;
        }
        .cta-box h2 { margin: 0 0 8px; font-size: clamp(20px, 2.5vw, 28px); }
        .cta-box .sub { color: var(--muted); margin: 0 0 16px; }
        .contact-list { margin: 0; padding-left: 18px; color: var(--ink); }
        .contact-list li { margin-bottom: 8px; }
        .contact-list a { color: var(--accent); text-decoration: none; }
        .contact-list a:hover { text-decoration: underline; }

        .admin-bar {
            background: rgba(15,23,42,.06);
            border-bottom: 1px solid var(--line);
            padding: 8px 20px;
            font-size: 12px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .admin-bar a { color: var(--muted); text-decoration: none; font-weight: 600; }
        .admin-bar a:hover { color: var(--ink); }

        footer {
            padding: 20px;
            color: var(--muted);
            text-align: center;
            border-top: 1px solid var(--line);
            background: var(--card);
            font-size: 13px;
        }

        /* Dark mode */
        html[data-theme="dark"] body { --bg: #07101d; --ink: #e2e8f0; --muted: #9fb2d9; --card: #0a1627; --line: rgba(96,165,250,.16); }
        html[data-theme="dark"] .admin-bar { background: rgba(255,255,255,.04); }

        @media (max-width: 960px) {
            .grid-3 { grid-template-columns: 1fr 1fr; }
            .grid-4 { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .grid-3, .grid-4 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="theme-scope">
<x-navbar-superadmin active="dinner-cruise" />

{{-- Admin quick-links bar --}}
<div class="admin-bar">
    <span>🔧 Superadmin</span>
    <a href="{{ route('superadmin.leisure.settings.index') }}">⚙ Leisure Ayarları</a>
    <a href="{{ route('superadmin.dinner-cruise.index') }}">📋 Talep Listesi</a>
    <a href="{{ route('acente.dinner-cruise.index') }}">👁 Acente Vitrinini Gör</a>
    <span class="ms-auto">{{ $packages->count() }} paket · {{ $mediaAssets->count() }} medya · {{ $includedExtras->count() }} dahil servis</span>
</div>

<header class="hero">
    <div class="showcase-container">
        <span class="badge-chip">🚢 B2B | Bosphorus Dinner Cruise</span>
        <h1>Müşteriniz sorarken teklifiniz hazır olsun</h1>
        <p class="lead">
            Boğazda akşam yemeği, canlı müzik ve eğlence — sabit B2B fiyatlarla, açık kontenjanla.
            GrupTalepleri üzerinden talep oluşturun, aynı gün teklifinizi alın, müşterinize iletin.
        </p>
        <div class="hero-actions">
            <a class="btn-sc btn-sc-primary" href="{{ route('acente.dinner-cruise.create') }}">Talep Oluştur</a>
            <a class="btn-sc btn-sc-ghost" href="tel:{{ preg_replace('/[^0-9+]/', '', $sirket['telefon']) }}">📞 {{ $sirket['telefon'] }}</a>
            <a class="btn-sc btn-sc-light" href="https://wa.me/{{ $sirket['whatsapp'] }}" target="_blank" rel="noopener">💬 WhatsApp</a>
        </div>
    </div>
</header>

<section class="sc-section">
    <div class="showcase-container">
        <h2>Neden dinner cruise satmak bu kadar kolay?</h2>
        <p class="sub">Fiyatlar belli, yer neredeyse her zaman var. Sisteme gir, talep oluştur, teklifini al — dakikalar içinde müşterine sun.</p>
        <div class="grid-3">
            <article class="sc-card">
                <h3>🎯 Net B2B fiyatlama</h3>
                <p>Paket bazlı sabit fiyatlar — kişi başı hesaplama, dahil/hariç kalemler netdir. Marjınızı önceden planlarsınız.</p>
            </article>
            <article class="sc-card">
                <h3>✅ Her zaman yer garantisi</h3>
                <p>Kontenjan %99 açık. Yüksek sezon dahil neredeyse her tarih için müşterinize güvenle söz verebilirsiniz.</p>
            </article>
            <article class="sc-card">
                <h3>⚡ Tek noktadan operasyon</h3>
                <p>Talep, teklif, konfirmasyon ve operasyon takibi GrupTalepleri sistemi üzerinden — ekstra koordinasyon yok.</p>
            </article>
        </div>
    </div>
</section>

<section class="sc-section">
    <div class="showcase-container">
        <h2>Paket içerikleri</h2>
        <p class="sub">Aktif dinner cruise paketleri — Leisure Ayarları'ndan güncellenir.</p>
        <div class="grid-3">
            @forelse($packages as $package)
                @php
                    $levelClass = match(strtolower((string) $package->level)) {
                        'vip'     => 'pkg-vip',
                        'premium' => 'pkg-premium',
                        'standard'=> 'pkg-standard',
                        default   => 'pkg-default',
                    };
                    $heroImage = trim((string) ($package->hero_image_url ?? ''));
                    if ($heroImage !== '' && !str_starts_with($heroImage, 'http') && !str_starts_with($heroImage, '/')) {
                        $heroImage = '/' . ltrim($heroImage, '/');
                    }
                    $includes = collect($package->includes_tr ?? [])->filter()->take(5)->values();
                    $excludes = collect($package->excludes_tr ?? [])->filter()->take(3)->values();
                @endphp
                <article class="pkg-card">
                    <div class="pkg-visual {{ $levelClass }}" @if($heroImage !== '') style="background-image:url('{{ $heroImage }}');" @endif>
                        <span class="pkg-level-badge">{{ strtoupper((string) $package->level) }}</span>
                        <div>
                            <div style="font-size:11px;opacity:.7;margin-bottom:3px;">Bosphorus Dinner Cruise</div>
                            <div style="font-weight:700;font-size:16px;">{{ $package->name_tr }}</div>
                        </div>
                    </div>
                    <div class="pkg-body">
                        <h4>{{ $package->name_en ?: $package->name_tr }}</h4>
                        <p>{{ $package->summary_tr ?: 'Boğazda akşam yemeği, canlı müzik ve eğlence programı içeren tam kapsamlı gemi turu.' }}</p>
                        @if($includes->isNotEmpty())
                            <div class="pkg-section-label">Dahil olanlar</div>
                            <ul>
                                @foreach($includes as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        @endif
                        @if($excludes->isNotEmpty())
                            <div class="pkg-section-label">Hariç</div>
                            <ul>
                                @foreach($excludes as $item)<li>{{ $item }}</li>@endforeach
                            </ul>
                        @endif
                    </div>
                </article>
            @empty
                {{-- Statik fallback paketler --}}
                <article class="pkg-card">
                    <div class="pkg-visual pkg-standard">
                        <span class="pkg-level-badge">STANDARD</span>
                        <div style="font-weight:700;font-size:16px;">Classic Dinner Cruise</div>
                    </div>
                    <div class="pkg-body">
                        <h4>Classic Dinner Cruise</h4>
                        <p>Boğazda akşam yemeği eşliğinde canlı müzik ve eğlence programı.</p>
                        <div class="pkg-section-label">Dahil olanlar</div>
                        <ul>
                            <li>Fix menü akşam yemeği</li>
                            <li>Canlı müzik ve sahne programı</li>
                            <li>Merkezi iskelelerden kalkış</li>
                        </ul>
                    </div>
                </article>
                <article class="pkg-card">
                    <div class="pkg-visual pkg-vip">
                        <span class="pkg-level-badge">VIP</span>
                        <div style="font-weight:700;font-size:16px;">Premium Dinner Cruise</div>
                    </div>
                    <div class="pkg-body">
                        <h4>Premium Dinner Cruise</h4>
                        <p>VIP masa konumlaması ve geliştirilmiş menü alternatifleri ile özel gece deneyimi.</p>
                        <div class="pkg-section-label">Dahil olanlar</div>
                        <ul>
                            <li>VIP masa konumlaması seçeneği</li>
                            <li>Geliştirilmiş menü alternatifleri</li>
                            <li>Özel gün kutlama kurguları</li>
                        </ul>
                    </div>
                </article>
                <article class="pkg-card">
                    <div class="pkg-visual pkg-premium">
                        <span class="pkg-level-badge">GROUP</span>
                        <div style="font-weight:700;font-size:16px;">Group & Incentive</div>
                    </div>
                    <div class="pkg-body">
                        <h4>Group &amp; Incentive Çözümleri</h4>
                        <p>Kurumsal gruplar için transfer + cruise kombine satış ve markalı etkinlik desteği.</p>
                        <div class="pkg-section-label">Dahil olanlar</div>
                        <ul>
                            <li>Kurumsal grup rezervasyon yönetimi</li>
                            <li>Transfer + cruise kombine satış</li>
                            <li>Markalı özel etkinlik desteği</li>
                        </ul>
                    </div>
                </article>
            @endforelse
        </div>
    </div>
</section>

@if($includedExtras->isNotEmpty() || $optionalExtras->isNotEmpty())
<section class="sc-section">
    <div class="showcase-container">
        <h2>Servisler</h2>
        <p class="sub">Standart dahil ve opsiyonel ek hizmetler.</p>
        <div class="grid-3" style="grid-template-columns:1fr 1fr;">
            @if($includedExtras->isNotEmpty())
            <div class="sc-card">
                <h3>✅ Standart Dahil</h3>
                <div class="pill-wrap">
                    @foreach($includedExtras as $item)
                        <span class="pill included">{{ $item->title_tr }}</span>
                    @endforeach
                </div>
            </div>
            @endif
            @if($optionalExtras->isNotEmpty())
            <div class="sc-card">
                <h3>➕ Opsiyonel Ek Servisler</h3>
                <div class="pill-wrap">
                    @foreach($optionalExtras as $item)
                        <span class="pill">{{ $item->title_tr }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@endif

<section class="sc-section">
    <div class="showcase-container">
        <h2>Nasıl çalışır?</h2>
        <p class="sub">Sistemden talebi oluşturun, gerisini biz halledelim.</p>
        <div class="grid-4">
            <div class="step-card"><strong>Adım 1 — Talep</strong><p>GrupTalepleri sistemine girin, tarih + kişi sayısı + paket seçin, talebi gönderin.</p></div>
            <div class="step-card"><strong>Adım 2 — Teklif</strong><p>Aynı gün B2B fiyatlı teklifiniz hazır. Müşterinize direkt iletebilirsiniz.</p></div>
            <div class="step-card"><strong>Adım 3 — Konfirmasyon</strong><p>Müşteri onayı sonrası rezervasyon kesinleşir. Özel istek varsa bu aşamada netleştirilir.</p></div>
            <div class="step-card"><strong>Adım 4 — Operasyon</strong><p>Etkinlik gününe kadar tek ekip, tek iletişim hattı. Siz sadece müşterinizi gönderin.</p></div>
        </div>
    </div>
</section>

<div class="cta-box px-4 px-md-5">
    <h2>Hemen başlayın</h2>
    <p class="sub">Sistem üzerinden talep oluşturun ya da doğrudan ulaşın — aynı gün dönüş garantisi.</p>
    <ul class="contact-list">
        <li>🖥 <a href="{{ route('acente.dinner-cruise.create') }}">Sisteme gir ve talep oluştur →</a></li>
        <li>📧 <a href="mailto:{{ $sirket['eposta'] }}">{{ $sirket['eposta'] }}</a></li>
        <li>📞 <a href="tel:{{ preg_replace('/[^0-9+]/', '', $sirket['telefon']) }}">{{ $sirket['telefon'] }}</a></li>
        <li>💬 <a href="https://wa.me/{{ $sirket['whatsapp'] }}" target="_blank" rel="noopener">WhatsApp ile yaz →</a></li>
    </ul>
</div>

<footer>
    © {{ date('Y') }} {{ $sirket['unvan'] }} | TÜRSAB Belge No: {{ $sirket['tursab_no'] }}
</footer>

@include('admin.partials.theme-script')
</body>
</html>

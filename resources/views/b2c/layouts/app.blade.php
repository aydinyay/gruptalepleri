<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Grup Rezervasyonları') — Grup Rezervasyonları</title>
    <meta name="description" content="@yield('meta_description', 'Türkiye\'nin lider grup seyahat platformu. Transfer, charter, dinner cruise, yat kiralama, tur paketleri ve daha fazlası.')">

    @if(View::hasSection('canonical'))
        <link rel="canonical" href="@yield('canonical')">
    @endif

    <meta property="og:title" content="@yield('title', 'Grup Rezervasyonları')">
    <meta property="og:description" content="@yield('meta_description', 'Türkiye\'nin lider grup seyahat platformu.')">
    <meta property="og:type" content="website">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --gyg-red:       #FF5533;   /* GYG primary (bizim için accent) */
            --gr-primary:    #1a3c6b;   /* Marka laciverdimiz */
            --gr-accent:     #FF5533;   /* CTA turuncu-kırmızı (GYG tarzı) */
            --gr-light:      #f8f9fc;
            --gr-dark:       #0f2444;
            --gr-text:       #1a202c;
            --gr-muted:      #718096;
            --gr-border:     #e2e8f0;
            --nav-height:    64px;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--gr-text);
            background: #fff;
            margin: 0;
        }

        /* ═══════════════════════════════════════════════════════
           NAVBAR — GYG stili
        ═══════════════════════════════════════════════════════ */
        .gyg-navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #fff;
            border-bottom: 1px solid #e5e5e5;
            height: var(--nav-height);
        }
        .gyg-navbar .nav-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 0;
        }

        /* Logo */
        .gyg-logo {
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--gr-primary);
            text-decoration: none;
            line-height: 1.1;
            margin-right: 32px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .gyg-logo span { color: var(--gr-accent); }
        .gyg-logo img { height: 36px; }

        /* Ana nav linkleri */
        .gyg-nav-links {
            display: flex;
            align-items: stretch;
            height: 100%;
            flex: 1;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 0;
        }
        .gyg-nav-links > li {
            position: static;
            height: 100%;
            display: flex;
            align-items: center;
        }
        .gyg-nav-links > li > a {
            display: flex;
            align-items: center;
            gap: 4px;
            height: 100%;
            padding: 0 14px;
            font-size: .93rem;
            font-weight: 500;
            color: var(--gr-text);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
            transition: color .15s, border-color .15s;
        }
        .gyg-nav-links > li > a:hover,
        .gyg-nav-links > li.open > a {
            color: var(--gr-primary);
            border-bottom-color: var(--gr-primary);
        }
        .gyg-nav-links > li > a .caret {
            font-size: .65rem;
            transition: transform .2s;
        }
        .gyg-nav-links > li.open > a .caret { transform: rotate(180deg); }

        /* ── Mega Dropdown ── */
        .gyg-mega {
            position: absolute;
            top: var(--nav-height);
            left: 0;
            width: 100%;
            background: #fff;
            border-top: 1px solid #e5e5e5;
            box-shadow: 0 12px 32px rgba(0,0,0,.12);
            display: none;
            z-index: 999;
        }
        .gyg-nav-links > li.open .gyg-mega { display: block; }
        .gyg-mega-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 32px 24px;
            display: flex;
            gap: 48px;
        }

        /* Sol sidebar */
        .gyg-mega-sidebar {
            min-width: 180px;
            flex-shrink: 0;
        }
        .gyg-mega-sidebar .sidebar-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gr-text);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .gyg-mega-sidebar .sidebar-title::before {
            content: '';
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--gr-accent);
            flex-shrink: 0;
        }
        .gyg-mega-sidebar .sidebar-see-all {
            font-size: .88rem;
            color: var(--gr-muted);
            text-decoration: none;
            display: block;
            margin-bottom: 24px;
        }
        .gyg-mega-sidebar .sidebar-see-all:hover { color: var(--gr-primary); text-decoration: underline; }
        .gyg-mega-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .gyg-mega-sidebar ul li { margin-bottom: 2px; }
        .gyg-mega-sidebar ul a {
            font-size: .9rem;
            color: var(--gr-text);
            text-decoration: none;
            padding: 5px 0;
            display: block;
        }
        .gyg-mega-sidebar ul a:hover { color: var(--gr-primary); }
        .gyg-mega-sidebar ul a.active { font-weight: 600; color: var(--gr-primary); }

        /* Sağ içerik grid */
        .gyg-mega-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px 24px;
            flex: 1;
        }
        .gyg-mega-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--gr-text);
            transition: background .15s;
        }
        .gyg-mega-item:hover { background: var(--gr-light); color: var(--gr-primary); }
        .gyg-mega-item .thumb {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gr-primary), #2a5298);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2rem;
            overflow: hidden;
        }
        .gyg-mega-item .thumb img { width: 100%; height: 100%; object-fit: cover; }
        .gyg-mega-item .item-text {
            font-size: .88rem;
            font-weight: 500;
            line-height: 1.3;
        }

        /* Basit dropdown (mega olmayan) */
        .gyg-dropdown {
            position: absolute;
            top: calc(var(--nav-height) - 1px);
            left: 0;
            min-width: 220px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-top: none;
            box-shadow: 0 8px 24px rgba(0,0,0,.1);
            border-radius: 0 0 8px 8px;
            display: none;
            z-index: 999;
            padding: 8px 0;
        }
        .gyg-nav-links > li.open .gyg-dropdown { display: block; }
        .gyg-dropdown a {
            display: block;
            padding: 9px 20px;
            font-size: .9rem;
            color: var(--gr-text);
            text-decoration: none;
        }
        .gyg-dropdown a:hover { background: var(--gr-light); color: var(--gr-primary); }
        .gyg-dropdown .divider { height: 1px; background: #e5e5e5; margin: 6px 0; }

        /* ── Sağ Aksiyonlar ── */
        .gyg-nav-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-left: auto;
        }
        .gyg-icon-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--gr-text);
            font-size: .65rem;
            font-weight: 500;
            gap: 2px;
            border: none;
            background: none;
            cursor: pointer;
            transition: background .15s;
        }
        .gyg-icon-btn:hover { background: var(--gr-light); color: var(--gr-primary); }
        .gyg-icon-btn i { font-size: 1.2rem; }
        .gyg-cta-btn {
            background: var(--gr-accent);
            color: #fff !important;
            font-weight: 700;
            font-size: .88rem;
            padding: 9px 18px;
            border-radius: 6px;
            text-decoration: none;
            white-space: nowrap;
            margin-left: 8px;
            transition: background .15s;
        }
        .gyg-cta-btn:hover { background: #e04420; color: #fff !important; }

        /* ── Profil Dropdown (GYG stili) ── */
        .gyg-profile-wrap { position: relative; }
        .gyg-profile-panel {
            display: none;
            position: absolute;
            top: calc(var(--nav-height) - 4px);
            right: 0;
            width: 280px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,.15);
            border: 1px solid #e5e5e5;
            z-index: 1100;
            overflow: hidden;
        }
        .gyg-profile-wrap.open .gyg-profile-panel { display: block; }
        .gyg-profile-panel .pp-title {
            font-weight: 700;
            font-size: 1rem;
            padding: 16px 20px 8px;
            color: #1a202c;
        }
        .gyg-profile-panel .pp-login-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px 14px;
            border-bottom: 1px solid #f0f0f0;
            text-decoration: none;
            color: #1a202c;
            transition: background .15s;
        }
        .gyg-profile-panel .pp-login-row:hover { background: #f8f9fc; }
        .gyg-profile-panel .pp-login-row i { font-size: 1.3rem; color: #4a5568; }
        .gyg-profile-panel .pp-login-row span { font-size: .93rem; font-weight: 500; }
        .gyg-profile-panel .pp-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            font-size: .9rem;
            color: #1a202c;
            text-decoration: none;
            border-bottom: 1px solid #f7f7f7;
            cursor: pointer;
            transition: background .15s;
        }
        .gyg-profile-panel .pp-row:hover { background: #f8f9fc; }
        .gyg-profile-panel .pp-row .pp-left { display: flex; align-items: center; gap: 12px; }
        .gyg-profile-panel .pp-row i.pp-icon { font-size: 1.1rem; color: #4a5568; }
        .gyg-profile-panel .pp-row .pp-right { display: flex; align-items: center; gap: 6px; color: #718096; font-size: .82rem; }
        .gyg-profile-panel .pp-row .pp-right i { font-size: .75rem; }

        /* ── Mobil hamburger ── */
        .gyg-hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 8px;
            margin-left: auto;
            border: none;
            background: none;
        }
        .gyg-hamburger span {
            display: block;
            width: 24px;
            height: 2px;
            background: var(--gr-text);
            border-radius: 2px;
            transition: all .3s;
        }

        /* ─── Mobil menu ─── */
        .gyg-mobile-menu {
            display: none;
            position: fixed;
            top: var(--nav-height);
            left: 0;
            width: 100%;
            height: calc(100vh - var(--nav-height));
            background: #fff;
            overflow-y: auto;
            z-index: 999;
            border-top: 1px solid #e5e5e5;
        }
        .gyg-mobile-menu.open { display: block; }
        .gyg-mobile-section { border-bottom: 1px solid #f0f0f0; }
        .gyg-mobile-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            font-weight: 600;
            font-size: .95rem;
            cursor: pointer;
        }
        .gyg-mobile-section-body { padding: 0 20px 12px 28px; display: none; }
        .gyg-mobile-section-body.open { display: block; }
        .gyg-mobile-section-body a {
            display: block;
            padding: 8px 0;
            font-size: .9rem;
            color: var(--gr-text);
            text-decoration: none;
            border-bottom: 1px solid #f7f7f7;
        }

        /* ═══════════════════════════════════════════════════════
           BUTONLAR
        ═══════════════════════════════════════════════════════ */
        .btn-gr-primary  { background: var(--gr-primary); color: #fff; border: none; font-weight: 600; }
        .btn-gr-primary:hover  { background: var(--gr-dark); color: #fff; }
        .btn-gr-accent   { background: var(--gr-accent); color: #fff; border: none; font-weight: 600; }
        .btn-gr-accent:hover   { background: #e04420; color: #fff; }
        .btn-gr-outline  { border: 2px solid var(--gr-primary); color: var(--gr-primary); background: transparent; font-weight: 600; }
        .btn-gr-outline:hover  { background: var(--gr-primary); color: #fff; }

        /* ═══════════════════════════════════════════════════════
           KARTLAR
        ═══════════════════════════════════════════════════════ */
        .gr-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
            background: #fff;
        }
        .gr-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.12); }
        .gr-card .card-img-top { height: 220px; object-fit: cover; }
        .gr-card .price-value  { font-size: 1.1rem; font-weight: 700; color: var(--gr-text); }
        .gr-card .pricing-label { font-size: .78rem; color: var(--gr-muted); }

        /* ═══════════════════════════════════════════════════════
           SECTION BAŞLIKLARI
        ═══════════════════════════════════════════════════════ */
        .gr-section-title    { font-size: 1.5rem; font-weight: 700; color: var(--gr-text); margin-bottom: .3rem; }
        .gr-section-subtitle { color: var(--gr-muted); font-size: .95rem; margin-bottom: 1.5rem; }

        /* ═══════════════════════════════════════════════════════
           FOOTER
        ═══════════════════════════════════════════════════════ */
        .gr-footer { background: var(--gr-dark); color: rgba(255,255,255,.8); }
        .gr-footer a { color: rgba(255,255,255,.65); text-decoration: none; }
        .gr-footer a:hover { color: var(--gr-accent); }
        .gr-footer .footer-title { color: #fff; font-weight: 600; font-size: .92rem; margin-bottom: .75rem; }
        .gr-footer hr { border-color: rgba(255,255,255,.1); }

        section { padding: 4rem 0; }
        @@media (max-width: 991px) {
            .gyg-nav-links, .gyg-nav-actions { display: none !important; }
            .gyg-hamburger { display: flex; }
            section { padding: 2.5rem 0; }
        }
    </style>

    @stack('head_styles')
</head>
<body>

{{-- ════════════════════════════════════════════════════════════════════════
     NAVBAR — GetYourGuide stili
════════════════════════════════════════════════════════════════════════ --}}
<nav class="gyg-navbar">
    <div class="nav-inner">

        {{-- Logo --}}
        <a class="gyg-logo" href="{{ route('b2c.home') }}">
            @if(file_exists(public_path('images/logo-gruprezervasyonlari.png')))
                <img src="{{ asset('images/logo-gruprezervasyonlari.png') }}" alt="Grup Rezervasyonları">
            @else
                Grup<span>Rezervasyonları</span>
            @endif
        </a>

        {{-- Ana navigasyon linkleri --}}
        <ul class="gyg-nav-links" id="gygNavLinks">

            {{-- Aktiviteler & Turlar (MEGA) --}}
            <li data-mega="mega-aktiviteler">
                <a href="{{ route('b2c.catalog.index') }}">
                    Aktiviteler &amp; Turlar
                    <i class="bi bi-chevron-down caret"></i>
                </a>
                <div class="gyg-mega" id="mega-aktiviteler">
                    <div class="gyg-mega-inner">
                        <div class="gyg-mega-sidebar">
                            <div class="sidebar-title">Kategoriler</div>
                            <a href="{{ route('b2c.catalog.index') }}" class="sidebar-see-all">Tümünü keşfet →</a>
                            <ul>
                                <li><a href="{{ route('b2c.catalog.category', 'dinner-cruise') }}" class="{{ request()->route('slug') === 'dinner-cruise' ? 'active' : '' }}">Dinner Cruise</a></li>
                                <li><a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}" class="{{ request()->route('slug') === 'yat-kiralama' ? 'active' : '' }}">Yat Kiralama</a></li>
                                <li><a href="{{ route('b2c.catalog.category', 'yurt-ici-turlar') }}" class="{{ request()->route('slug') === 'yurt-ici-turlar' ? 'active' : '' }}">Yurt İçi Turlar</a></li>
                                <li><a href="{{ route('b2c.catalog.category', 'yurt-disi-turlar') }}" class="{{ request()->route('slug') === 'yurt-disi-turlar' ? 'active' : '' }}">Yurt Dışı Turlar</a></li>
                                <li><a href="{{ route('b2c.catalog.category', 'gunubirlik-turlar') }}" class="{{ request()->route('slug') === 'gunubirlik-turlar' ? 'active' : '' }}">Günübirlik Turlar</a></li>
                            </ul>
                        </div>
                        <div class="gyg-mega-grid">
                            @php
                                $megaAktiviteler = [
                                    ['icon'=>'bi-water',           'title'=>'Dinner Cruise',          'sub'=>'İstanbul Boğazı',     'slug'=>'dinner-cruise'],
                                    ['icon'=>'bi-tsunami',         'title'=>'Yat Kiralama',            'sub'=>'Ege & Akdeniz',       'slug'=>'yat-kiralama'],
                                    ['icon'=>'bi-map-fill',        'title'=>'Yurt İçi Turlar',         'sub'=>'Tüm Türkiye',         'slug'=>'yurt-ici-turlar'],
                                    ['icon'=>'bi-globe-americas',  'title'=>'Yurt Dışı Turlar',        'sub'=>'Dünya geneli',        'slug'=>'yurt-disi-turlar'],
                                    ['icon'=>'bi-sunrise-fill',    'title'=>'Günübirlik Turlar',       'sub'=>'Günü birlik geziler', 'slug'=>'gunubirlik-turlar'],
                                    ['icon'=>'bi-binoculars-fill', 'title'=>'Şehir Turları',           'sub'=>'Rehberli turlar',     'slug'=>'yurt-ici-turlar'],
                                    ['icon'=>'bi-camera-fill',     'title'=>'Fotoğraf Turları',        'sub'=>'Anı yakala',         'slug'=>'yurt-ici-turlar'],
                                    ['icon'=>'bi-stars',           'title'=>'VIP Deneyimler',          'sub'=>'Özel organizasyon',   'slug'=>'dinner-cruise'],
                                    ['icon'=>'bi-people-fill',     'title'=>'Grup Paketleri',          'sub'=>'10+ kişi',            'slug'=>'yurt-ici-turlar'],
                                ];
                            @endphp
                            @foreach($megaAktiviteler as $m)
                            <a href="{{ route('b2c.catalog.category', $m['slug']) }}" class="gyg-mega-item">
                                <div class="thumb"><i class="bi {{ $m['icon'] }}"></i></div>
                                <div class="item-text">
                                    {{ $m['title'] }}<br>
                                    <span style="font-weight:400;color:var(--gr-muted);font-size:.8rem;">{{ $m['sub'] }}</span>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </li>

            {{-- Destinasyonlar (MEGA) --}}
            <li data-mega="mega-destinasyonlar">
                <a href="{{ route('b2c.catalog.index') }}">
                    Destinasyonlar
                    <i class="bi bi-chevron-down caret"></i>
                </a>
                <div class="gyg-mega" id="mega-destinasyonlar">
                    <div class="gyg-mega-inner">
                        <div class="gyg-mega-sidebar">
                            <div class="sidebar-title">Bölgeler</div>
                            <a href="{{ route('b2c.catalog.index') }}" class="sidebar-see-all">Tüm destinasyonlar →</a>
                            <ul>
                                <li><a href="{{ route('b2c.catalog.index') }}?sehir=istanbul">İstanbul</a></li>
                                <li><a href="{{ route('b2c.catalog.index') }}?sehir=antalya">Antalya</a></li>
                                <li><a href="{{ route('b2c.catalog.index') }}?sehir=bodrum">Bodrum & Marmaris</a></li>
                                <li><a href="{{ route('b2c.catalog.index') }}?sehir=kapadokya">Kapadokya</a></li>
                                <li><a href="{{ route('b2c.catalog.index') }}?ulke=yurt-disi">Yurt Dışı</a></li>
                            </ul>
                        </div>
                        <div class="gyg-mega-grid">
                            @php
                                $megaDestinasyonlar = [
                                    ['icon'=>'bi-buildings-fill',  'title'=>'İstanbul',      'sub'=>'Şehir & Boğaz turları',    'sehir'=>'istanbul'],
                                    ['icon'=>'bi-sun-fill',        'title'=>'Antalya',       'sub'=>'Sahil & doğa',             'sehir'=>'antalya'],
                                    ['icon'=>'bi-water',           'title'=>'Bodrum',        'sub'=>'Yat & tekne',              'sehir'=>'bodrum'],
                                    ['icon'=>'bi-cloud-fill',      'title'=>'Kapadokya',     'sub'=>'Balon & tarihi turlar',    'sehir'=>'kapadokya'],
                                    ['icon'=>'bi-tree-fill',       'title'=>'Marmaris',      'sub'=>'Tekne turları',            'sehir'=>'marmaris'],
                                    ['icon'=>'bi-snow',            'title'=>'Uludağ',        'sub'=>'Kış sporları',             'sehir'=>'uludag'],
                                    ['icon'=>'bi-geo-alt-fill',    'title'=>'İzmir',         'sub'=>'Kültür & lezzet',          'sehir'=>'izmir'],
                                    ['icon'=>'bi-globe-europe-africa', 'title'=>'Dubai',     'sub'=>'Yurt dışı paketler',       'sehir'=>'dubai'],
                                    ['icon'=>'bi-globe-americas',  'title'=>'Diğer Ülkeler', 'sub'=>'Dünya geneli',             'sehir'=>'diger'],
                                ];
                            @endphp
                            @foreach($megaDestinasyonlar as $d)
                            <a href="{{ route('b2c.catalog.index') }}?sehir={{ $d['sehir'] }}" class="gyg-mega-item">
                                <div class="thumb"><i class="bi {{ $d['icon'] }}"></i></div>
                                <div class="item-text">
                                    {{ $d['title'] }}<br>
                                    <span style="font-weight:400;color:var(--gr-muted);font-size:.8rem;">{{ $d['sub'] }}</span>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </li>

            {{-- Ulaşım (MEGA) --}}
            <li data-mega="mega-ulasim">
                <a href="{{ route('b2c.catalog.category', 'transfer') }}">
                    Ulaşım
                    <i class="bi bi-chevron-down caret"></i>
                </a>
                <div class="gyg-mega" id="mega-ulasim">
                    <div class="gyg-mega-inner">
                        <div class="gyg-mega-sidebar">
                            <div class="sidebar-title">Ulaşım Hizmetleri</div>
                            <a href="{{ route('b2c.catalog.category', 'transfer') }}" class="sidebar-see-all">Tüm ulaşım seçenekleri →</a>
                            <ul>
                                <li><a href="{{ route('b2c.catalog.category', 'transfer') }}">Havalimanı Transferi</a></li>
                                <li><a href="{{ route('b2c.catalog.category', 'ozel-jet') }}">Özel Jet</a></li>
                                <li><a href="{{ route('b2c.catalog.category', 'helikopter') }}">Helikopter</a></li>
                            </ul>
                        </div>
                        <div class="gyg-mega-grid">
                            @php
                                $megaUlasim = [
                                    ['icon'=>'bi-car-front-fill',  'title'=>'Havalimanı Transferi', 'sub'=>'İstanbul, Antalya, İzmir',    'slug'=>'transfer'],
                                    ['icon'=>'bi-taxi-front-fill', 'title'=>'VIP Transfer',         'sub'=>'Özel şoför hizmeti',          'slug'=>'transfer'],
                                    ['icon'=>'bi-airplane-fill',   'title'=>'Özel Jet Kiralama',    'sub'=>'Konforlu & hızlı uçuş',      'slug'=>'ozel-jet'],
                                    ['icon'=>'bi-airplane-engines','title'=>'Charter Uçuşu',        'sub'=>'Grup için özel uçak',         'slug'=>'ozel-jet'],
                                    ['icon'=>'bi-helicopter',      'title'=>'Helikopter Turu',      'sub'=>'Havadan panoramik gezi',      'slug'=>'helikopter'],
                                    ['icon'=>'bi-bus-front-fill',  'title'=>'Grup Transferi',       'sub'=>'Otobüs & minibüs kiralama',   'slug'=>'transfer'],
                                ];
                            @endphp
                            @foreach($megaUlasim as $u)
                            <a href="{{ route('b2c.catalog.category', $u['slug']) }}" class="gyg-mega-item">
                                <div class="thumb"><i class="bi {{ $u['icon'] }}"></i></div>
                                <div class="item-text">
                                    {{ $u['title'] }}<br>
                                    <span style="font-weight:400;color:var(--gr-muted);font-size:.8rem;">{{ $u['sub'] }}</span>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </li>

            {{-- Gezi İlhamı (MEGA) — GYG'nin "Gezi İlhamı" menüsünün birebir uyarlaması --}}
            <li data-mega="mega-ilham">
                <a href="{{ route('b2c.blog.index') }}">
                    Gezi İlhamı
                    <i class="bi bi-chevron-down caret"></i>
                </a>
                <div class="gyg-mega" id="mega-ilham">
                    <div class="gyg-mega-inner">
                        <div class="gyg-mega-sidebar">
                            <div class="sidebar-title">Şehir Rehberleri</div>
                            <a href="{{ route('b2c.blog.index') }}" class="sidebar-see-all">Tümünü keşfet →</a>
                        </div>
                        <div class="gyg-mega-grid">
                            @php
                                $rehberler = [
                                    ['icon'=>'bi-buildings-fill',      'title'=>'İstanbul seyahat rehberi',    'sehir'=>'istanbul'],
                                    ['icon'=>'bi-sun-fill',            'title'=>'Antalya seyahat rehberi',     'sehir'=>'antalya'],
                                    ['icon'=>'bi-water',               'title'=>'Bodrum\'u keşfet: rehberi',   'sehir'=>'bodrum'],
                                    ['icon'=>'bi-cloud-fill',          'title'=>'Kapadokya seyahat rehberi',   'sehir'=>'kapadokya'],
                                    ['icon'=>'bi-tree-fill',           'title'=>'Marmaris keşif rehberi',      'sehir'=>'marmaris'],
                                    ['icon'=>'bi-geo-alt-fill',        'title'=>'İzmir\'i keşfet: rehberi',    'sehir'=>'izmir'],
                                    ['icon'=>'bi-globe-europe-africa', 'title'=>'Dubai seyahat rehberi',       'sehir'=>'dubai'],
                                    ['icon'=>'bi-mountain',            'title'=>'Uludağ kış rehberi',          'sehir'=>'uludag'],
                                    ['icon'=>'bi-compass-fill',        'title'=>'Ege\'yi keşfet: rehberi',     'sehir'=>'ege'],
                                ];
                            @endphp
                            @foreach($rehberler as $r)
                            <a href="{{ route('b2c.blog.index') }}?tag={{ $r['sehir'] }}" class="gyg-mega-item">
                                <div class="thumb" style="background:linear-gradient(135deg,#2d5282,#4299e1);">
                                    <i class="bi {{ $r['icon'] }}"></i>
                                </div>
                                <div class="item-text">{{ $r['title'] }}</div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </li>

        </ul>{{-- /gyg-nav-links --}}

        {{-- ── Sağ aksiyonlar ── --}}
        <div class="gyg-nav-actions">
            {{-- İstek Listesi --}}
            <a href="#" class="gyg-icon-btn" title="İstek Listesi">
                <i class="bi bi-heart"></i>
                <span>İstek Listesi</span>
            </a>
            {{-- Para Birimi --}}
            <button class="gyg-icon-btn" title="Para Birimi" onclick="">
                <i class="bi bi-globe"></i>
                <span>TR / TRY ₺</span>
            </button>
            {{-- Profil Dropdown (GYG stili) --}}
            <div class="gyg-profile-wrap" id="gygProfileWrap">
                <button class="gyg-icon-btn" id="gygProfileBtn" type="button">
                    @auth('b2c')
                        <i class="bi bi-person-circle"></i>
                    @else
                        <i class="bi bi-person"></i>
                    @endauth
                    <span>Profil</span>
                </button>
                <div class="gyg-profile-panel" id="gygProfilePanel">
                    <div class="pp-title">Profil</div>
                    @auth('b2c')
                        <a href="{{ route('b2c.account.index') }}" class="pp-login-row">
                            <i class="bi bi-person-circle"></i>
                            <div>
                                <div style="font-weight:600;font-size:.93rem;">{{ auth('b2c')->user()->name }}</div>
                                <div style="font-size:.8rem;color:#718096;">Hesabıma Git</div>
                            </div>
                        </a>
                        <a href="{{ route('b2c.account.orders.index') }}" class="pp-row">
                            <div class="pp-left"><i class="bi bi-bag pp-icon"></i> Siparişlerim</div>
                            <div class="pp-right"><i class="bi bi-chevron-right"></i></div>
                        </a>
                        <a href="{{ route('b2c.account.profile.edit') }}" class="pp-row">
                            <div class="pp-left"><i class="bi bi-pencil pp-icon"></i> Profil Düzenle</div>
                            <div class="pp-right"><i class="bi bi-chevron-right"></i></div>
                        </a>
                        <a href="{{ route('b2c.iletisim') }}" class="pp-row">
                            <div class="pp-left"><i class="bi bi-headset pp-icon"></i> Destek</div>
                            <div class="pp-right"><i class="bi bi-chevron-right"></i></div>
                        </a>
                        <form action="{{ route('b2c.auth.logout') }}" method="POST" style="padding:10px 20px 14px;border-top:1px solid #f0f0f0;">
                            @csrf
                            <button type="submit" style="background:none;border:none;color:#e53e3e;font-size:.9rem;cursor:pointer;padding:0;width:100%;text-align:left;display:flex;align-items:center;gap:10px;">
                                <i class="bi bi-box-arrow-right" style="font-size:1.1rem;"></i> Çıkış Yap
                            </button>
                        </form>
                    @else
                        <a href="{{ route('b2c.auth.login') }}" class="pp-login-row">
                            <i class="bi bi-person-circle"></i>
                            <div>
                                <div style="font-weight:600;font-size:.93rem;">Oturum açın veya kaydolun</div>
                                <div style="font-size:.8rem;color:#718096;">Rezervasyonlarınıza erişin</div>
                            </div>
                        </a>
                        <a href="#" class="pp-row">
                            <div class="pp-left"><i class="bi bi-bell pp-icon"></i> Güncellemeler</div>
                            <div class="pp-right"><i class="bi bi-chevron-right"></i></div>
                        </a>
                        <a href="#" class="pp-row">
                            <div class="pp-left"><i class="bi bi-sun pp-icon"></i> Görünüm</div>
                            <div class="pp-right"><span style="font-size:.8rem;">Her zaman aydınlık</span><i class="bi bi-chevron-right"></i></div>
                        </a>
                        <a href="{{ route('b2c.iletisim') }}" class="pp-row">
                            <div class="pp-left"><i class="bi bi-headset pp-icon"></i> Destek</div>
                            <div class="pp-right"><i class="bi bi-chevron-right"></i></div>
                        </a>
                        <a href="#" class="pp-row" style="border-bottom:none;">
                            <div class="pp-left"><i class="bi bi-phone pp-icon"></i> Uygulamayı İndir</div>
                            <div class="pp-right"><i class="bi bi-chevron-right"></i></div>
                        </a>
                    @endauth
                </div>
            </div>
            {{-- CTA --}}
            <a href="{{ route('b2c.catalog.index') }}" class="gyg-cta-btn">
                <i class="bi bi-search" style="margin-right:5px;"></i>Keşfet
            </a>
        </div>

        {{-- Mobil hamburger --}}
        <button class="gyg-hamburger" id="gygHamburger" aria-label="Menü">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

{{-- ── Mobil Menü --}}
<div class="gyg-mobile-menu" id="gygMobileMenu">
    <div class="gyg-mobile-section">
        <div class="gyg-mobile-section-head" onclick="toggleMobileSection(this)">
            Aktiviteler &amp; Turlar <i class="bi bi-chevron-down"></i>
        </div>
        <div class="gyg-mobile-section-body">
            <a href="{{ route('b2c.catalog.category', 'dinner-cruise') }}">Dinner Cruise</a>
            <a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}">Yat Kiralama</a>
            <a href="{{ route('b2c.catalog.category', 'yurt-ici-turlar') }}">Yurt İçi Turlar</a>
            <a href="{{ route('b2c.catalog.category', 'yurt-disi-turlar') }}">Yurt Dışı Turlar</a>
            <a href="{{ route('b2c.catalog.category', 'gunubirlik-turlar') }}">Günübirlik Turlar</a>
        </div>
    </div>
    <div class="gyg-mobile-section">
        <div class="gyg-mobile-section-head" onclick="toggleMobileSection(this)">
            Destinasyonlar <i class="bi bi-chevron-down"></i>
        </div>
        <div class="gyg-mobile-section-body">
            <a href="{{ route('b2c.catalog.index') }}?sehir=istanbul">İstanbul</a>
            <a href="{{ route('b2c.catalog.index') }}?sehir=antalya">Antalya</a>
            <a href="{{ route('b2c.catalog.index') }}?sehir=bodrum">Bodrum</a>
            <a href="{{ route('b2c.catalog.index') }}?sehir=kapadokya">Kapadokya</a>
        </div>
    </div>
    <div class="gyg-mobile-section">
        <div class="gyg-mobile-section-head" onclick="toggleMobileSection(this)">
            Ulaşım <i class="bi bi-chevron-down"></i>
        </div>
        <div class="gyg-mobile-section-body">
            <a href="{{ route('b2c.catalog.category', 'transfer') }}">Havalimanı Transferi</a>
            <a href="{{ route('b2c.catalog.category', 'ozel-jet') }}">Özel Jet Kiralama</a>
            <a href="{{ route('b2c.catalog.category', 'helikopter') }}">Helikopter</a>
        </div>
    </div>
    <div class="gyg-mobile-section">
        <a href="{{ route('b2c.blog.index') }}" style="display:block;padding:14px 20px;font-weight:600;font-size:.95rem;color:var(--gr-text);text-decoration:none;">Blog &amp; Rehberler</a>
    </div>
    <div style="padding:16px 20px;border-top:1px solid #f0f0f0;display:flex;flex-direction:column;gap:10px;">
        @auth('b2c')
            <a href="{{ route('b2c.account.index') }}" class="btn btn-gr-primary btn-sm rounded-pill">Hesabım</a>
        @else
            <a href="{{ route('b2c.auth.login') }}" class="btn btn-outline-secondary btn-sm rounded-pill">Giriş Yap</a>
            <a href="{{ route('b2c.auth.register') }}" class="btn btn-gr-accent btn-sm rounded-pill">Kayıt Ol</a>
        @endauth
        <a href="{{ route('b2c.catalog.index') }}" class="btn btn-gr-accent btn-sm rounded-pill">
            <i class="bi bi-search me-1"></i>Hizmetleri Keşfet
        </a>
    </div>
</div>

{{-- ── SAYFA İÇERİĞİ --}}
<main>
    @if(session('success'))
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show py-2">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show py-2">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @yield('content')
</main>

{{-- ════════════════════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════════════════ --}}
<footer class="gr-footer pt-5 pb-3 mt-5">
    <div class="container" style="max-width:1280px;">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-title mb-2" style="font-size:1.1rem;">Grup<span style="color:var(--gr-accent);">Rezervasyonları</span></div>
                <p style="font-size:.88rem;opacity:.75;max-width:280px;line-height:1.7;">
                    Türkiye'nin lider grup seyahat platformu. Transfer, charter, tur, dinner cruise ve daha fazlası tek çatı altında.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin fs-5"></i></a>
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="#" aria-label="YouTube"><i class="bi bi-youtube fs-5"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Hizmetler</div>
                <ul class="list-unstyled" style="font-size:.88rem;line-height:2;">
                    <li><a href="{{ route('b2c.catalog.category', 'transfer') }}">Havalimanı Transferi</a></li>
                    <li><a href="{{ route('b2c.catalog.category', 'ozel-jet') }}">Özel Jet</a></li>
                    <li><a href="{{ route('b2c.catalog.category', 'dinner-cruise') }}">Dinner Cruise</a></li>
                    <li><a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}">Yat Kiralama</a></li>
                    <li><a href="{{ route('b2c.catalog.category', 'yurt-ici-turlar') }}">Tur Paketleri</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Destinasyonlar</div>
                <ul class="list-unstyled" style="font-size:.88rem;line-height:2;">
                    <li><a href="{{ route('b2c.catalog.index') }}?sehir=istanbul">İstanbul</a></li>
                    <li><a href="{{ route('b2c.catalog.index') }}?sehir=antalya">Antalya</a></li>
                    <li><a href="{{ route('b2c.catalog.index') }}?sehir=bodrum">Bodrum</a></li>
                    <li><a href="{{ route('b2c.catalog.index') }}?sehir=kapadokya">Kapadokya</a></li>
                    <li><a href="{{ route('b2c.catalog.index') }}?sehir=izmir">İzmir</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Kurumsal</div>
                <ul class="list-unstyled" style="font-size:.88rem;line-height:2;">
                    <li><a href="{{ route('b2c.hakkimizda') }}">Hakkımızda</a></li>
                    <li><a href="{{ route('b2c.iletisim') }}">İletişim</a></li>
                    <li><a href="{{ route('b2c.blog.index') }}">Blog</a></li>
                    <li><a href="{{ route('b2c.supplier-apply.show') }}" style="color:var(--gr-accent);">Tedarikçi Ol</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Yasal</div>
                <ul class="list-unstyled" style="font-size:.88rem;line-height:2;">
                    <li><a href="{{ route('b2c.kvkk') }}">KVKK</a></li>
                    <li><a href="{{ route('b2c.gizlilik') }}">Gizlilik</a></li>
                    <li><a href="{{ route('b2c.mesafeli-satis') }}">Mesafeli Satış</a></li>
                    <li><a href="{{ route('b2c.iptal-iade') }}">İptal &amp; İade</a></li>
                    <li><a href="{{ route('b2c.on-bilgilendirme') }}">Ön Bilgilendirme</a></li>
                </ul>
            </div>
        </div>

        <hr class="mt-4">
        <div class="row align-items-center" style="font-size:.82rem;opacity:.6;">
            <div class="col-md-6">
                &copy; {{ date('Y') }} Grup Rezervasyonları. Tüm hakları saklıdır.
            </div>
            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <i class="bi bi-shield-check me-1"></i>Güvenli Ödeme &nbsp;|&nbsp;
                <i class="bi bi-headset me-1"></i>7/24 Destek &nbsp;|&nbsp;
                <i class="bi bi-star-fill me-1" style="color:#f4a418;"></i>Güvenilir Platform
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ── Mega dropdown hover + click logic ──────────────────────────────────
(function() {
    const navItems = document.querySelectorAll('.gyg-nav-links > li[data-mega]');
    let openItem = null;
    let closeTimer = null;

    function openMenu(li) {
        if (openItem && openItem !== li) {
            openItem.classList.remove('open');
        }
        li.classList.add('open');
        openItem = li;
        if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
    }

    function scheduleClose(li) {
        closeTimer = setTimeout(() => {
            li.classList.remove('open');
            if (openItem === li) openItem = null;
        }, 150);
    }

    navItems.forEach(li => {
        li.addEventListener('mouseenter', () => openMenu(li));
        li.addEventListener('mouseleave', () => scheduleClose(li));

        const mega = li.querySelector('.gyg-mega, .gyg-dropdown');
        if (mega) {
            mega.addEventListener('mouseenter', () => {
                if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
            });
            mega.addEventListener('mouseleave', () => scheduleClose(li));
        }
    });

    // Dışarı tıkla kapat
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.gyg-nav-links')) {
            navItems.forEach(li => li.classList.remove('open'));
            openItem = null;
        }
    });
})();

// ── Mobil hamburger ─────────────────────────────────────────────────────
document.getElementById('gygHamburger').addEventListener('click', function() {
    const menu = document.getElementById('gygMobileMenu');
    menu.classList.toggle('open');
    this.classList.toggle('active');
});

// ── Mobil bölüm aç/kapat ────────────────────────────────────────────────
function toggleMobileSection(head) {
    const body = head.nextElementSibling;
    const isOpen = body.classList.contains('open');
    // hepsini kapat
    document.querySelectorAll('.gyg-mobile-section-body').forEach(b => b.classList.remove('open'));
    if (!isOpen) body.classList.add('open');
}

// ── Profil dropdown ──────────────────────────────────────────────────────
(function() {
    const wrap = document.getElementById('gygProfileWrap');
    const btn  = document.getElementById('gygProfileBtn');
    if (!wrap || !btn) return;

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        wrap.classList.toggle('open');
    });

    document.addEventListener('click', function(e) {
        if (!wrap.contains(e.target)) {
            wrap.classList.remove('open');
        }
    });
})();
</script>
@stack('scripts')
</body>
</html>

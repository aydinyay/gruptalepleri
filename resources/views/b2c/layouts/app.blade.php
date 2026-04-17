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

        /* ═══════════════════════════════════════════════════════
           ÜRÜN KARTLARI (gyg-pcard) — global, tüm sayfalarda
        ═══════════════════════════════════════════════════════ */
        .gyg-products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        @@media (max-width: 1100px) { .gyg-products-grid { grid-template-columns: repeat(3, 1fr); } }
        @@media (max-width: 720px)  { .gyg-products-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }
        @@media (max-width: 480px)  { .gyg-products-grid { grid-template-columns: 1fr; } }

        .gyg-pcard {
            display: flex;
            flex-direction: column;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
            text-decoration: none;
            color: var(--gr-text);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .gyg-pcard:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,.14);
            color: var(--gr-text);
            text-decoration: none;
        }
        .gyg-pcard-img {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: #e2e8f0;
            flex-shrink: 0;
        }
        .gyg-pcard-img img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .4s ease;
        }
        .gyg-pcard:hover .gyg-pcard-img img { transform: scale(1.05); }
        .img-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: rgba(255,255,255,.5);
        }
        .gyg-pcard-heart {
            position: absolute; top: 10px; right: 10px;
            width: 32px; height: 32px; border-radius: 50%;
            background: rgba(255,255,255,.9); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem; color: #718096; cursor: pointer;
            transition: color .15s, background .15s;
            z-index: 2;
        }
        .gyg-pcard-heart:hover { color: #e53e3e; background: #fff; }
        .gyg-pcard-badge {
            position: absolute; bottom: 10px; left: 10px;
            background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
            color: #fff; font-size: .75rem; font-weight: 600;
            padding: 3px 10px; border-radius: 50px; z-index: 2;
        }
        .gyg-pcard-tag {
            position: absolute; top: 10px; left: 10px;
            font-size: .7rem; font-weight: 700; text-transform: uppercase;
            padding: 3px 10px; border-radius: 50px; z-index: 2;
        }
        .gyg-pcard-tag.popular  { background: #FF5533; color: #fff; }
        .gyg-pcard-tag.featured { background: #f4a418; color: #fff; }
        .gyg-pcard-body {
            padding: 14px 16px 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .gyg-pcard-cat {
            font-size: .75rem; font-weight: 600; color: var(--gr-muted);
            text-transform: uppercase; letter-spacing: .04em;
            margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .gyg-pcard-title {
            font-size: .93rem; font-weight: 600; color: var(--gr-text);
            line-height: 1.4; margin-bottom: 6px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .gyg-pcard-stars { color: #f4a418; font-size: .85rem; }
        .gyg-pcard-rating { font-weight: 700; font-size: .85rem; }
        .gyg-pcard-reviews { color: var(--gr-muted); font-size: .82rem; }
        .gyg-pcard-price-label { font-size: .75rem; color: var(--gr-muted); margin-top: auto; padding-top: 8px; }
        .gyg-pcard-price { font-size: 1.05rem; font-weight: 800; color: var(--gr-text); margin-bottom: 10px; }
        .gyg-pcard-cta {
            display: block; width: 100%;
            background: var(--gr-accent); color: #fff !important;
            font-weight: 700; font-size: .85rem;
            padding: 10px 0; border-radius: 8px;
            text-align: center; text-decoration: none !important;
            border: none; cursor: pointer;
            transition: background .15s;
            margin-top: auto;
        }
        .gyg-pcard-cta:hover { background: #e04420; }
        .gyg-pcard-cta.outline {
            background: var(--gr-primary);
            border: none;
            color: #fff !important;
        }
        .gyg-pcard-cta.outline:hover { background: #152f56; }

        /* Bölüm başlık + "tümünü gör" satırı */
        .gyg-section-head {
            display: flex; align-items: flex-end; justify-content: space-between;
            margin-bottom: 20px; flex-wrap: wrap; gap: 8px;
        }
        .gyg-section-head h2 { font-size: 1.5rem; font-weight: 800; color: var(--gr-text); margin: 0; }
        .gyg-section-head p  { color: var(--gr-muted); font-size: .9rem; margin: 4px 0 0; }
        .gyg-see-all {
            font-size: .88rem; font-weight: 600; color: var(--gr-primary);
            text-decoration: none; white-space: nowrap;
        }
        .gyg-see-all:hover { text-decoration: underline; }

        /* Breadcrumb (global) */
        .gyg-breadcrumb {
            background: #f8f9fc; border-bottom: 1px solid #e5e5e5;
            padding: 10px 0; font-size: .85rem; color: var(--gr-muted);
        }
        .gyg-breadcrumb a { color: var(--gr-muted); text-decoration: none; }
        .gyg-breadcrumb a:hover { color: var(--gr-primary); text-decoration: underline; }
        .gyg-breadcrumb .sep { margin: 0 6px; }

        /* Aktif filtre chip */
        .filter-chip {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--gr-primary); color: #fff;
            font-size: .8rem; font-weight: 600;
            padding: 4px 12px; border-radius: 50px;
            text-decoration: none;
        }
        .filter-chip .chip-x { cursor: pointer; opacity: .7; }
        .filter-chip .chip-x:hover { opacity: 1; }

        /* Güven şeridi */
        .gyg-trust-strip {
            background: #f0f5ff;
            border-top: 1px solid #dce8ff;
            border-bottom: 1px solid #dce8ff;
            padding: 14px 0;
        }
        .gyg-trust-strip .inner {
            max-width: 1280px; margin: 0 auto; padding: 0 24px;
            display: flex; align-items: center; justify-content: center;
            flex-wrap: wrap; gap: 8px 32px;
        }
        .gyg-trust-item {
            display: flex; align-items: center; gap: 8px;
            font-size: .85rem; font-weight: 500; color: #2d5282; white-space: nowrap;
        }
        .gyg-trust-item i { font-size: 1rem; color: #2b6cb0; }

        /* Nasıl çalışır */
        .gyg-how-it-works {
            background: var(--gr-light);
            padding: 3rem 0;
        }
        .gyg-steps {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            max-width: 900px;
            margin: 0 auto;
        }
        @@media (max-width: 640px) { .gyg-steps { grid-template-columns: 1fr; gap: 20px; } }
        .gyg-step {
            text-align: center; padding: 24px 16px;
        }
        .gyg-step-num {
            width: 52px; height: 52px; border-radius: 50%;
            background: var(--gr-primary); color: #fff;
            font-size: 1.2rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
        }
        .gyg-step h4 { font-size: 1rem; font-weight: 700; margin-bottom: 6px; }
        .gyg-step p  { font-size: .88rem; color: var(--gr-muted); line-height: 1.6; margin: 0; }

        /* Mobil sticky CTA bar */
        .mobile-sticky-cta {
            display: none;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #fff;
            border-top: 1px solid #e5e5e5;
            padding: 12px 16px;
            z-index: 900;
            box-shadow: 0 -4px 16px rgba(0,0,0,.1);
        }
        @@media (max-width: 1024px) { .mobile-sticky-cta { display: flex; align-items: center; gap: 12px; } }
        .mobile-sticky-cta .msc-price { font-size: 1.1rem; font-weight: 800; flex-shrink: 0; }
        .mobile-sticky-cta .msc-label { font-size: .72rem; color: var(--gr-muted); }
        .mobile-sticky-cta .msc-btn {
            flex: 1; background: var(--gr-accent); color: #fff !important;
            font-weight: 700; font-size: .95rem;
            padding: 13px; border-radius: 10px;
            text-align: center; text-decoration: none !important;
            border: none; cursor: pointer;
        }
        /* Ürün detay sayfasında mobil padding */
        .product-mobile-pad { padding-bottom: 0; }
        @@media (max-width: 1024px) { .product-mobile-pad { padding-bottom: 80px; } }
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
                                @foreach(($navCategories ?? collect())->take(6) as $cat)
                                <li><a href="{{ route('b2c.catalog.category', $cat->slug) }}" class="{{ request()->route('slug') === $cat->slug ? 'active' : '' }}">{{ $cat->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="gyg-mega-grid">
                            @php
                            $catIconMap = [
                                'dinner-cruise'     => 'bi-water',
                                'yat-kiralama'      => 'bi-tsunami',
                                'yurt-ici-turlar'   => 'bi-map-fill',
                                'yurt-disi-turlar'  => 'bi-globe-americas',
                                'gunubirlik-turlar' => 'bi-sunrise-fill',
                                'transfer'          => 'bi-car-front-fill',
                                'charter'           => 'bi-airplane-fill',
                                'ozel-jet'          => 'bi-airplane-fill',
                                'ozel-jet-charter'  => 'bi-airplane-fill',
                                'air-charter'       => 'bi-airplane-engines',
                                'helikopter'        => 'bi-helicopter',
                                'sehir-turlari'     => 'bi-binoculars-fill',
                                'fotograf-turlari'  => 'bi-camera-fill',
                                'vip-deneyimler'    => 'bi-stars',
                                'grup-paketleri'    => 'bi-people-fill',
                            ];
                            @endphp
                            @foreach(($navCategories ?? collect())->take(9) as $cat)
                            <a href="{{ route('b2c.catalog.category', $cat->slug) }}" class="gyg-mega-item">
                                <div class="thumb"><i class="bi {{ $catIconMap[$cat->slug] ?? 'bi-grid' }}"></i></div>
                                <div class="item-text">
                                    {{ $cat->name }}<br>
                                    <span style="font-weight:400;color:var(--gr-muted);font-size:.8rem;">{{ $cat->published_items_count }} deneyim</span>
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
                                @foreach(($navCities ?? collect())->take(6) as $city)
                                <li><a href="{{ route('b2c.catalog.index') }}?sehir={{ urlencode($city->slug) }}">{{ $city->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="gyg-mega-grid">
                            @php
                            $cityIconMap = [
                                'istanbul'   => 'bi-buildings-fill',
                                'antalya'    => 'bi-sun-fill',
                                'bodrum'     => 'bi-water',
                                'kapadokya'  => 'bi-cloud-fill',
                                'marmaris'   => 'bi-tree-fill',
                                'uludağ'     => 'bi-snow',
                                'izmir'      => 'bi-geo-alt-fill',
                                'ankara'     => 'bi-building',
                                'dubai'      => 'bi-globe-europe-africa',
                                'dubai'      => 'bi-globe-europe-africa',
                                'fethiye'    => 'bi-water',
                                'alanya'     => 'bi-sun-fill',
                                'trabzon'    => 'bi-mountain',
                            ];
                            @endphp
                            @foreach(($navCities ?? collect()) as $city)
                            <a href="{{ route('b2c.catalog.index') }}?sehir={{ urlencode($city->slug) }}" class="gyg-mega-item">
                                <div class="thumb"><i class="bi {{ $cityIconMap[$city->slug] ?? 'bi-geo-alt-fill' }}"></i></div>
                                <div class="item-text">
                                    {{ $city->name }}<br>
                                    <span style="font-weight:400;color:var(--gr-muted);font-size:.8rem;">{{ $city->cnt }} deneyim</span>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </li>

            {{-- Ulaşım (MEGA) --}}
            <li data-mega="mega-ulasim">
                <a href="{{ route('b2c.transfer.index') }}">
                    Ulaşım
                    <i class="bi bi-chevron-down caret"></i>
                </a>
                <div class="gyg-mega" id="mega-ulasim">
                    <div class="gyg-mega-inner">
                        <div class="gyg-mega-sidebar">
                            <div class="sidebar-title">Ulaşım Hizmetleri</div>
                            <a href="{{ route('b2c.transfer.index') }}" class="sidebar-see-all">Tüm ulaşım seçenekleri →</a>
                            <ul>
                                <li><a href="{{ route('b2c.transfer.index') }}">Havalimanı Transferi</a></li>
                                <li><a href="{{ route('b2c.catalog.category', 'ozel-jet') }}">Özel Jet</a></li>
                                <li><a href="{{ route('b2c.catalog.category', 'helikopter') }}">Helikopter</a></li>
                            </ul>
                        </div>
                        <div class="gyg-mega-grid">
                            @php
                                $megaUlasim = [
                                    ['icon'=>'bi-car-front-fill',  'title'=>'Havalimanı Transferi', 'sub'=>'İstanbul, Antalya, İzmir',    'url'=> route('b2c.transfer.index')],
                                    ['icon'=>'bi-taxi-front-fill', 'title'=>'VIP Transfer',         'sub'=>'Özel şoför hizmeti',          'url'=> route('b2c.transfer.index')],
                                    ['icon'=>'bi-airplane-fill',   'title'=>'Özel Jet Kiralama',    'sub'=>'Konforlu & hızlı uçuş',      'url'=> route('b2c.catalog.category', 'ozel-jet')],
                                    ['icon'=>'bi-airplane-engines','title'=>'Charter Uçuşu',        'sub'=>'Grup için özel uçak',         'url'=> route('b2c.catalog.category', 'ozel-jet')],
                                    ['icon'=>'bi-helicopter',      'title'=>'Helikopter Turu',      'sub'=>'Havadan panoramik gezi',      'url'=> route('b2c.catalog.category', 'helikopter')],
                                    ['icon'=>'bi-bus-front-fill',  'title'=>'Grup Transferi',       'sub'=>'Otobüs & minibüs kiralama',   'url'=> route('b2c.transfer.index')],
                                ];
                            @endphp
                            @foreach($megaUlasim as $u)
                            <a href="{{ $u['url'] }}" class="gyg-mega-item">
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
            <a href="{{ route('b2c.transfer.index') }}">Havalimanı Transferi</a>
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
                    <li><a href="{{ route('b2c.transfer.index') }}">Havalimanı Transferi</a></li>
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

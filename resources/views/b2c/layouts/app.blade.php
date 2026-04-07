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

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('title', 'Grup Rezervasyonları')">
    <meta property="og:description" content="@yield('meta_description', 'Türkiye\'nin lider grup seyahat platformu.')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-gruprezervasyonlari.jpg'))">
    <meta property="og:type" content="website">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --gr-primary:    #1a3c6b;   /* Koyu lacivert — ana marka rengi */
            --gr-accent:     #e8a020;   /* Altın sarısı — CTA ve vurgu */
            --gr-light:      #f8f9fc;   /* Açık arka plan */
            --gr-dark:       #0f2444;   /* Koyu footer */
            --gr-text:       #2d3748;
            --gr-muted:      #718096;
            --gr-border:     #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--gr-text);
            background: #fff;
        }

        /* ── Navbar ── */
        .gr-navbar {
            background: #fff;
            border-bottom: 1px solid var(--gr-border);
            box-shadow: 0 1px 8px rgba(0,0,0,.06);
        }
        .gr-navbar .navbar-brand img { height: 38px; }
        .gr-navbar .nav-link {
            font-weight: 500;
            color: var(--gr-text) !important;
            font-size: .93rem;
            padding: .5rem .9rem !important;
        }
        .gr-navbar .nav-link:hover { color: var(--gr-primary) !important; }
        .gr-navbar .btn-cta {
            background: var(--gr-accent);
            color: #fff !important;
            font-weight: 600;
            border-radius: 6px;
            padding: .45rem 1.1rem !important;
        }
        .gr-navbar .btn-cta:hover { background: #c98a15; }

        /* ── Butonlar ── */
        .btn-gr-primary {
            background: var(--gr-primary);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-gr-primary:hover { background: var(--gr-dark); color: #fff; }
        .btn-gr-accent {
            background: var(--gr-accent);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-gr-accent:hover { background: #c98a15; color: #fff; }
        .btn-gr-outline {
            border: 2px solid var(--gr-primary);
            color: var(--gr-primary);
            font-weight: 600;
            background: transparent;
        }
        .btn-gr-outline:hover { background: var(--gr-primary); color: #fff; }

        /* ── Kartlar ── */
        .gr-card {
            border: 1px solid var(--gr-border);
            border-radius: 12px;
            transition: transform .2s, box-shadow .2s;
            overflow: hidden;
        }
        .gr-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,.10);
        }
        .gr-card .card-img-top { height: 200px; object-fit: cover; }
        .gr-card .badge-type {
            background: var(--gr-primary);
            color: #fff;
            font-size: .72rem;
            font-weight: 600;
            padding: .25rem .6rem;
            border-radius: 4px;
        }
        .gr-card .pricing-label {
            font-size: .85rem;
            color: var(--gr-muted);
        }
        .gr-card .price-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gr-primary);
        }

        /* ── Kategori Kartı ── */
        .gr-category-card {
            border: 1px solid var(--gr-border);
            border-radius: 12px;
            padding: 1.5rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
            display: block;
            color: var(--gr-text);
        }
        .gr-category-card:hover {
            border-color: var(--gr-primary);
            background: var(--gr-light);
            color: var(--gr-primary);
            transform: translateY(-2px);
        }
        .gr-category-card .cat-icon {
            font-size: 2rem;
            color: var(--gr-primary);
            margin-bottom: .75rem;
        }
        .gr-category-card .cat-name {
            font-weight: 600;
            font-size: .95rem;
        }

        /* ── Güven Rozetleri ── */
        .trust-badge {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .88rem;
            color: var(--gr-muted);
        }
        .trust-badge i { color: var(--gr-accent); font-size: 1.1rem; }

        /* ── Section Başlıkları ── */
        .gr-section-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--gr-primary);
            margin-bottom: .4rem;
        }
        .gr-section-subtitle {
            color: var(--gr-muted);
            font-size: .97rem;
            margin-bottom: 2rem;
        }

        /* ── Footer ── */
        .gr-footer {
            background: var(--gr-dark);
            color: rgba(255,255,255,.8);
        }
        .gr-footer a { color: rgba(255,255,255,.65); text-decoration: none; }
        .gr-footer a:hover { color: var(--gr-accent); }
        .gr-footer .footer-title { color: #fff; font-weight: 600; font-size: .95rem; margin-bottom: .8rem; }
        .gr-footer hr { border-color: rgba(255,255,255,.12); }

        /* ── Genel ── */
        section { padding: 4rem 0; }
        section.py-small { padding: 2.5rem 0; }
        @media (max-width: 768px) { section { padding: 2.5rem 0; } }
    </style>

    @stack('head_styles')
</head>
<body>

{{-- ── NAVBAR ─────────────────────────────────────────────────────────── --}}
<nav class="gr-navbar navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('b2c.home') }}">
            {{-- Logo yoksa metin fallback --}}
            @if(file_exists(public_path('images/logo-gruprezervasyonlari.png')))
                <img src="{{ asset('images/logo-gruprezervasyonlari.png') }}" alt="Grup Rezervasyonları">
            @else
                <span style="font-weight:800;color:var(--gr-primary);font-size:1.1rem;">GrupRezervasyonları</span>
            @endif
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="{{ route('b2c.catalog.index') }}">Hizmetler</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Turlar &amp; Paketler</a>
                    <ul class="dropdown-menu shadow-sm border-0">
                        <li><a class="dropdown-item" href="{{ route('b2c.catalog.category', 'yurt-ici-turlar') }}">Yurt İçi Turlar</a></li>
                        <li><a class="dropdown-item" href="{{ route('b2c.catalog.category', 'yurt-disi-turlar') }}">Yurt Dışı Turlar</a></li>
                        <li><a class="dropdown-item" href="{{ route('b2c.catalog.category', 'gunubirlik-turlar') }}">Günübirlik Turlar</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Ulaşım</a>
                    <ul class="dropdown-menu shadow-sm border-0">
                        <li><a class="dropdown-item" href="{{ route('b2c.catalog.category', 'transfer') }}">Havalimanı Transferi</a></li>
                        <li><a class="dropdown-item" href="{{ route('b2c.catalog.category', 'ozel-jet') }}">Özel Jet Kiralama</a></li>
                        <li><a class="dropdown-item" href="{{ route('b2c.catalog.category', 'helikopter') }}">Helikopter</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Deniz &amp; Eğlence</a>
                    <ul class="dropdown-menu shadow-sm border-0">
                        <li><a class="dropdown-item" href="{{ route('b2c.catalog.category', 'dinner-cruise') }}">Dinner Cruise</a></li>
                        <li><a class="dropdown-item" href="{{ route('b2c.catalog.category', 'yat-kiralama') }}">Yat Kiralama</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="{{ route('b2c.blog.index') }}">Blog</a></li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                @auth('b2c')
                    <a href="{{ route('b2c.account.index') }}" class="nav-link">
                        <i class="bi bi-person-circle"></i> Hesabım
                    </a>
                    <form method="POST" action="{{ route('b2c.auth.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-gr-outline">Çıkış</button>
                    </form>
                @else
                    <a href="{{ route('b2c.auth.login') }}" class="nav-link">Giriş Yap</a>
                    <a href="{{ route('b2c.catalog.index') }}" class="btn btn-sm btn-gr-accent rounded-pill px-3">
                        <i class="bi bi-search me-1"></i>Hizmetleri Keşfet
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- ── SAYFA İÇERİĞİ ──────────────────────────────────────────────────── --}}
<main>
    @if(session('success'))
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @yield('content')
</main>

{{-- ── FOOTER ──────────────────────────────────────────────────────────── --}}
<footer class="gr-footer pt-5 pb-3 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-title mb-2">Grup Rezervasyonları</div>
                <p style="font-size:.88rem;opacity:.75;max-width:280px;">
                    Türkiye'nin lider grup seyahat platformu. Transfer, charter, tur, dinner cruise ve daha fazlası tek çatı altında.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin fs-5"></i></a>
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook fs-5"></i></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Hizmetler</div>
                <ul class="list-unstyled" style="font-size:.88rem;">
                    <li class="mb-1"><a href="{{ route('b2c.catalog.category', 'transfer') }}">Transfer</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.catalog.category', 'ozel-jet') }}">Özel Jet</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.catalog.category', 'dinner-cruise') }}">Dinner Cruise</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.catalog.category', 'yat-kiralama') }}">Yat Kiralama</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.catalog.category', 'turlar') }}">Tur Paketleri</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Kurumsal</div>
                <ul class="list-unstyled" style="font-size:.88rem;">
                    <li class="mb-1"><a href="{{ route('b2c.hakkimizda') }}">Hakkımızda</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.iletisim') }}">İletişim</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.blog.index') }}">Blog</a></li>
                    <li class="mb-1">
                        <a href="{{ route('b2c.supplier-apply.show') }}" style="color:var(--gr-accent);">
                            <i class="bi bi-building me-1"></i>Tedarikçi Ol
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Yasal</div>
                <ul class="list-unstyled" style="font-size:.88rem;">
                    <li class="mb-1"><a href="{{ route('b2c.kvkk') }}">KVKK</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.gizlilik') }}">Gizlilik Politikası</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.mesafeli-satis') }}">Mesafeli Satış Sözleşmesi</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.iptal-iade') }}">İptal &amp; İade</a></li>
                    <li class="mb-1"><a href="{{ route('b2c.on-bilgilendirme') }}">Ön Bilgilendirme</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <div class="footer-title">Hesabım</div>
                <ul class="list-unstyled" style="font-size:.88rem;">
                    @auth('b2c')
                        <li class="mb-1"><a href="{{ route('b2c.account.orders.index') }}">Siparişlerim</a></li>
                        <li class="mb-1"><a href="{{ route('b2c.account.profile.edit') }}">Profilim</a></li>
                    @else
                        <li class="mb-1"><a href="{{ route('b2c.auth.login') }}">Giriş Yap</a></li>
                        <li class="mb-1"><a href="{{ route('b2c.auth.register') }}">Kayıt Ol</a></li>
                    @endauth
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
                <i class="bi bi-headset me-1"></i>7/24 Destek
            </div>
        </div>
    </div>
</footer>

{{-- ── SCRIPTS ─────────────────────────────────────────────────────────── --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // CSRF token — AJAX istekleri için
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
</script>
@stack('scripts')
</body>
</html>

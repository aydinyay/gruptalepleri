<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GitHub Navbar Prototype - GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --gh-bg: #0d1117;
            --gh-border: #30363d;
            --gh-text: #c9d1d9;
            --gh-text-muted: #8b949e;
            --gh-accent: #2f81f7;
            --gh-surface: #161b22;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f6f8fa;
            color: #1f2328;
        }

        .gh-demo-nav {
            position: sticky;
            top: 0;
            z-index: 1040;
            background: var(--gh-bg);
            border-bottom: 1px solid var(--gh-border);
            color: var(--gh-text);
        }

        .gh-demo-wrap {
            max-width: 1320px;
            margin: 0 auto;
            padding: 0.55rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .gh-demo-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            white-space: nowrap;
        }

        .gh-demo-search {
            min-width: 260px;
            max-width: 420px;
            flex: 1 1 320px;
            position: relative;
        }

        .gh-demo-search input {
            width: 100%;
            background: #0d1117;
            border: 1px solid var(--gh-border);
            color: var(--gh-text);
            border-radius: 7px;
            padding: 0.38rem 0.65rem 0.38rem 2rem;
            font-size: 0.88rem;
        }

        .gh-demo-search i {
            position: absolute;
            left: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gh-text-muted);
            font-size: 0.8rem;
        }

        .gh-demo-links {
            display: flex;
            align-items: center;
            gap: 0.2rem;
            margin-left: 0.3rem;
            margin-right: auto;
        }

        .gh-demo-link {
            color: var(--gh-text);
            text-decoration: none;
            font-size: 0.88rem;
            padding: 0.45rem 0.65rem;
            border-radius: 6px;
            border: 1px solid transparent;
        }

        .gh-demo-link:hover {
            background: #21262d;
            color: #fff;
        }

        .gh-demo-link.is-active {
            color: #fff;
            border-color: var(--gh-border);
            background: #161b22;
        }

        .gh-demo-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .gh-demo-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 1px solid var(--gh-border);
            background: transparent;
            color: var(--gh-text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .gh-demo-icon-btn:hover {
            background: #21262d;
            color: #fff;
        }

        .gh-demo-mobile-toggle {
            display: none;
        }

        .gh-demo-mobile-panel {
            display: none;
            padding: 0.75rem 1rem 1rem;
            border-top: 1px solid var(--gh-border);
            background: var(--gh-bg);
        }

        .gh-demo-mobile-panel .gh-demo-link {
            display: block;
            margin-bottom: 0.4rem;
        }

        .gh-demo-container {
            max-width: 1320px;
            margin: 1.1rem auto;
            padding: 0 1rem;
        }

        .gh-demo-card {
            background: #fff;
            border: 1px solid #d0d7de;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.9rem;
        }

        .gh-demo-chip {
            display: inline-block;
            padding: 0.22rem 0.5rem;
            border-radius: 999px;
            border: 1px solid #d0d7de;
            font-size: 0.74rem;
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
            background: #f6f8fa;
            color: #57606a;
        }

        @media (max-width: 1050px) {
            .gh-demo-search {
                min-width: 200px;
            }

            .gh-demo-links {
                display: none;
            }

            .gh-demo-mobile-toggle {
                display: inline-flex;
            }
        }

        @media (max-width: 680px) {
            .gh-demo-search {
                display: none;
            }
        }
    </style>
</head>
<body>
@php
    $user = auth()->user();
    $role = $user->role ?? 'acente';

    $menuByRole = [
        'superadmin' => [
            ['label' => 'Panel', 'url' => route('superadmin.dashboard')],
            ['label' => 'Acenteler', 'url' => route('superadmin.acenteler')],
            ['label' => 'Site Ayarlari', 'url' => route('superadmin.site.ayarlar')],
            ['label' => 'SMS Raporlar', 'url' => route('superadmin.sms.raporlar')],
        ],
        'admin' => [
            ['label' => 'Panel', 'url' => route('admin.dashboard')],
            ['label' => 'Talepler', 'url' => route('admin.requests.index')],
            ['label' => 'Charter', 'url' => route('admin.charter.index')],
            ['label' => 'Duyuru', 'url' => route('admin.broadcast.create')],
        ],
        'acente' => [
            ['label' => 'Taleplerim', 'url' => route('acente.dashboard')],
            ['label' => 'Air Charter', 'url' => route('acente.charter.index')],
            ['label' => 'Yeni Talep', 'url' => route('acente.requests.create')],
            ['label' => 'Profil', 'url' => route('acente.profil')],
        ],
    ];

    $menus = $menuByRole[$role] ?? $menuByRole['acente'];
@endphp

<header class="gh-demo-nav">
    <div class="gh-demo-wrap">
        <a href="{{ route('dashboard') }}" class="gh-demo-brand">
            <i class="fa-solid fa-code-branch"></i>
            <span>GrupTalepleri</span>
        </a>

        <div class="gh-demo-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Hizli arama: talep, GTPNR, acente...">
        </div>

        <nav class="gh-demo-links" aria-label="Ana navigasyon">
            @foreach($menus as $index => $item)
                <a href="{{ $item['url'] }}" class="gh-demo-link {{ $index === 0 ? 'is-active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="gh-demo-actions">
            <button type="button" class="gh-demo-icon-btn gh-demo-mobile-toggle" id="ghDemoMobileToggle" aria-label="Menuyu ac/kapat">
                <i class="fa-solid fa-bars"></i>
            </button>
            <a class="gh-demo-icon-btn" href="#" title="Yeni"><i class="fa-solid fa-plus"></i></a>
            <a class="gh-demo-icon-btn" href="{{ route('bildirimler.liste') }}" title="Bildirim"><i class="fa-regular fa-bell"></i></a>
            <a class="gh-demo-icon-btn" href="{{ route('dashboard') }}" title="{{ $user->name }}"><i class="fa-regular fa-circle-user"></i></a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="gh-demo-icon-btn" title="Cikis">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="gh-demo-mobile-panel" id="ghDemoMobilePanel">
        @foreach($menus as $item)
            <a href="{{ $item['url'] }}" class="gh-demo-link">{{ $item['label'] }}</a>
        @endforeach
    </div>
</header>

<main class="gh-demo-container">
    <section class="gh-demo-card">
        <h5 class="mb-2">GitHub mantiginda navbar prototipi</h5>
        <p class="mb-2 text-secondary">
            Bu sayfa mevcut ekranlara dokunmadan hazirlandi. Sadece navbar davranisini denemek icin izole bir ornektir.
        </p>
        <span class="gh-demo-chip">Sticky header</span>
        <span class="gh-demo-chip">Role-based menu</span>
        <span class="gh-demo-chip">Global search alanı</span>
        <span class="gh-demo-chip">Action icon grubu</span>
        <span class="gh-demo-chip">Mobil acilir panel</span>
    </section>

    <section class="gh-demo-card">
        <h6 class="mb-2">Gecis plani (kayipsiz)</h6>
        <ol class="mb-0">
            <li>Ilk adimda sadece komponent seviyesinde yeni navbar olusturulur.</li>
            <li>Eski navbar bir surum daha korunur (fallback).</li>
            <li>Rol bazli tek tek gecis yapilir (once acente, sonra admin, sonra superadmin).</li>
            <li>Her rolde route ve yetki smoke testinden sonra kalici hale getirilir.</li>
        </ol>
    </section>
</main>

<script>
    (function () {
        const toggle = document.getElementById('ghDemoMobileToggle');
        const panel = document.getElementById('ghDemoMobilePanel');
        if (!toggle || !panel) return;

        toggle.addEventListener('click', function () {
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        });
    })();
</script>
</body>
</html>

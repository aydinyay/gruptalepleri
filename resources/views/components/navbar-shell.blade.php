@props([
    'brand' => 'GrupTalepleri',
    'brandRoute' => null,
    'roleBadge' => '',
    'active' => '',
    'groups' => [],
    'quickActions' => [],
    'showThemeToggle' => true,
    'themeToggleHandler' => 'window.siteToggleTheme && window.siteToggleTheme()',
    'showNotificationBell' => true,
    'showSupport' => false,
    'supportUrl' => null,
    'profileRoute' => null,
    'profileName' => null,
    'logoutRoute' => 'logout',
    'logoutLabel' => 'Cikis',
    'navId' => 'gt-nav',
    'logoIcon' => 'fas fa-plane-departure',
])

@php
    $profileName = $profileName ?: (auth()->user()->name ?? '');
    $profileRoute = $profileRoute ?: route('profile.edit');
    $brandHref = $brandRoute ?: '#';
    $drawerId = $navId . '-drawer';
    $overlayId = $navId . '-overlay';
    $desktopMenuPrefix = $navId . '-group-menu-';

    $normalizedGroups = collect($groups)->map(function (array $group) use ($active): array {
        $items = collect($group['items'] ?? [])->map(function (array $item) use ($active): array {
            $keys = $item['keys'] ?? ($item['key'] ?? []);
            $keyList = array_values(array_filter((array) $keys, fn ($key) => is_string($key) && $key !== ''));
            $isActive = (bool) ($item['is_active'] ?? false) || in_array($active, $keyList, true);

            return [
                'label' => (string) ($item['label'] ?? ''),
                'href' => (string) ($item['href'] ?? '#'),
                'icon' => (string) ($item['icon'] ?? 'fas fa-circle'),
                'target' => (string) ($item['target'] ?? ''),
                'is_active' => $isActive,
                'keys' => $keyList,
            ];
        })->filter(fn (array $item) => $item['label'] !== '')->values();

        $groupActive = (bool) ($group['is_active'] ?? false) || $items->contains(fn (array $item) => $item['is_active']);

        return [
            'id' => (string) ($group['id'] ?? 'group-' . substr(md5((string) ($group['label'] ?? '')), 0, 8)),
            'label' => (string) ($group['label'] ?? ''),
            'icon' => (string) ($group['icon'] ?? 'fas fa-folder-open'),
            'href' => (string) ($group['href'] ?? (string) ($items->first()['href'] ?? '#')),
            'is_active' => $groupActive,
            'items' => $items->all(),
        ];
    })->filter(fn (array $group) => $group['label'] !== '' && !empty($group['items']))->values();

    $activeGroupId = (string) ($normalizedGroups->first(fn (array $group) => $group['is_active'])['id'] ?? '');

    $normalizedQuickActions = collect($quickActions)->map(function (array $action): array {
        return [
            'label' => (string) ($action['label'] ?? ''),
            'href' => (string) ($action['href'] ?? '#'),
            'icon' => (string) ($action['icon'] ?? 'fas fa-link'),
            'class' => (string) ($action['class'] ?? 'btn btn-sm btn-outline-light'),
            'target' => (string) ($action['target'] ?? ''),
            'mobile_header' => (bool) ($action['mobile_header'] ?? false),
            'desktop_only' => (bool) ($action['desktop_only'] ?? false),
            'mobile_only' => (bool) ($action['mobile_only'] ?? false),
        ];
    })->filter(fn (array $action) => $action['label'] !== '')->values();

    $desktopQuickActions = $normalizedQuickActions
        ->filter(fn (array $action) => !$action['mobile_only'])
        ->values();

    $mobileHeaderQuickActions = $normalizedQuickActions
        ->filter(fn (array $action) => $action['mobile_header'])
        ->take(2)
        ->values();

    $mobilePanelQuickActions = $normalizedQuickActions
        ->filter(fn (array $action) => !$action['desktop_only'])
        ->values();

    $logoutAction = route($logoutRoute);
@endphp

@once
<style>
.gt-nav-shell {
    position: sticky;
    top: 0;
    z-index: 1030;
    background: #1a1a2e;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    overflow: visible !important;
}
.gt-nav-container {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .55rem 1rem;
    overflow: visible !important;
}
.gt-nav-brand {
    color: #e94560 !important;
    font-weight: 700;
    font-size: 1.1rem;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    min-width: 0;
}
.gt-nav-brand-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.gt-nav-role {
    font-size: .63rem;
    color: rgba(255, 255, 255, .55);
    font-weight: 600;
    letter-spacing: .05em;
    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 999px;
    padding: .15rem .45rem;
}
.gt-nav-desktop-only {
    display: none;
}
.gt-nav-mobile-only {
    display: flex;
}
.gt-nav-groups {
    align-items: center;
    gap: .35rem;
    min-width: 0;
    margin-left: .45rem;
}
.gt-nav-group {
    position: relative;
    overflow: visible !important;
}
.gt-nav-group::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 0;
    width: max(100%, 16rem);
    height: .4rem;
}
.gt-nav-group-btn {
    background: transparent;
    border: 1px solid transparent;
    color: rgba(255, 255, 255, .78);
    font-size: .86rem;
    border-radius: .6rem;
    padding: .4rem .62rem;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    white-space: nowrap;
    cursor: pointer;
    text-decoration: none;
}
.gt-nav-group-btn:hover,
.gt-nav-group-btn:focus,
.gt-nav-group-btn.is-active {
    color: #fff;
    background: rgba(255, 255, 255, .12);
    border-color: rgba(255, 255, 255, .2);
    outline: none;
}
.gt-nav-menu {
    position: absolute;
    top: calc(100% + .4rem);
    left: 0;
    display: none;
    min-width: 16rem;
    padding: .4rem;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: .7rem;
    background: #121a31;
    box-shadow: 0 20px 45px rgba(5, 8, 20, .42);
    z-index: 1200;
}
.gt-nav-group[open] .gt-nav-menu {
    display: block;
}
.gt-nav-group:hover .gt-nav-menu,
.gt-nav-group:focus-within .gt-nav-menu {
    display: block;
}
.gt-nav-group:hover .gt-nav-group-btn,
.gt-nav-group:focus-within .gt-nav-group-btn {
    color: #fff;
    background: rgba(255, 255, 255, .12);
    border-color: rgba(255, 255, 255, .2);
}
.gt-nav-menu-link {
    color: rgba(255, 255, 255, .86);
    border-radius: .5rem;
    padding: .5rem .65rem;
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .85rem;
    text-decoration: none;
    margin-bottom: .25rem;
}
.gt-nav-menu-link:last-child {
    margin-bottom: 0;
}
.gt-nav-menu-link:hover,
.gt-nav-menu-link:focus,
.gt-nav-menu-link.is-active {
    color: #fff;
    background: rgba(233, 69, 96, .2);
    outline: none;
}
.gt-nav-actions {
    align-items: center;
    gap: .45rem;
    margin-left: auto;
}
.gt-nav-action {
    color: #f8fafc;
    text-decoration: none;
    border: 1px solid rgba(255, 255, 255, .2);
    border-radius: .55rem;
    background: rgba(255, 255, 255, .08);
    padding: .38rem .62rem;
    font-size: .82rem;
    line-height: 1.1;
    display: inline-flex;
    align-items: center;
    gap: .36rem;
    white-space: nowrap;
}
.gt-nav-action:hover,
.gt-nav-action:focus {
    color: #fff;
    background: rgba(255, 255, 255, .18);
    border-color: rgba(255, 255, 255, .28);
    outline: none;
}
.gt-nav-action.btn-danger {
    background: #dc3545;
    border-color: #dc3545;
}
.gt-nav-action.btn-success {
    background: #198754;
    border-color: #198754;
}
.gt-nav-link {
    color: rgba(255, 255, 255, .8) !important;
    text-decoration: none !important;
    border: 1px solid transparent;
    border-radius: .55rem;
    padding: .4rem .62rem;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
}
.gt-nav-link:hover,
.gt-nav-link:focus,
.gt-nav-link.is-active {
    color: #fff !important;
    background: rgba(255, 255, 255, .12);
    border-color: rgba(255, 255, 255, .2);
    outline: none;
}
.gt-nav-profile {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.gt-nav-icon-btn {
    border: 1px solid rgba(255, 255, 255, .26);
    color: #f8fafc;
    background: rgba(255, 255, 255, .1);
    border-radius: .55rem;
    width: 2.1rem;
    height: 2.1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.gt-nav-icon-btn:hover,
.gt-nav-icon-btn:focus {
    background: rgba(255, 255, 255, .18);
    border-color: rgba(255, 255, 255, .4);
    outline: none;
}
.gt-nav-mobile-head {
    margin-left: auto;
    align-items: center;
    gap: .42rem;
}
.gt-nav-overlay {
    position: fixed;
    inset: 0;
    background: rgba(2, 8, 23, .58);
    opacity: 0;
    pointer-events: none;
    transition: opacity .22s ease;
    z-index: 1040;
}
.gt-nav-overlay.is-open {
    opacity: 1;
    pointer-events: auto;
}
.gt-nav-drawer {
    position: fixed;
    top: 0;
    right: 0;
    width: min(92vw, 390px);
    height: 100vh;
    background: #121a31;
    color: #eef2ff;
    transform: translateX(100%);
    transition: transform .24s ease;
    z-index: 1045;
    display: flex;
    flex-direction: column;
    border-left: 1px solid rgba(255, 255, 255, .1);
}
.gt-nav-drawer.is-open {
    transform: translateX(0);
}
.gt-nav-drawer-header {
    border-bottom: 1px solid rgba(255, 255, 255, .12);
    padding: .8rem .9rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
}
.gt-nav-drawer-title {
    margin: 0;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: .45rem;
}
.gt-nav-drawer-body {
    padding: .8rem .85rem 1rem;
    overflow-y: auto;
}
.gt-nav-mobile-group {
    border: 1px solid rgba(255, 255, 255, .14);
    background: rgba(255, 255, 255, .04);
    border-radius: .65rem;
    margin-bottom: .55rem;
    overflow: hidden;
}
.gt-nav-mobile-group summary {
    cursor: pointer;
    list-style: none;
    display: flex;
    align-items: center;
    gap: .45rem;
    font-size: .9rem;
    padding: .65rem .7rem;
    color: #eef2ff;
}
.gt-nav-mobile-group summary::-webkit-details-marker {
    display: none;
}
.gt-nav-mobile-group[open] summary {
    background: rgba(233, 69, 96, .14);
}
.gt-nav-mobile-links {
    padding: .2rem .5rem .55rem;
}
.gt-nav-mobile-link {
    display: flex;
    align-items: center;
    gap: .5rem;
    text-decoration: none;
    color: rgba(255, 255, 255, .86);
    border-radius: .5rem;
    padding: .45rem .55rem;
    font-size: .85rem;
    margin-bottom: .3rem;
}
.gt-nav-mobile-link:last-child {
    margin-bottom: 0;
}
.gt-nav-mobile-link:hover,
.gt-nav-mobile-link:focus,
.gt-nav-mobile-link.is-active {
    color: #fff;
    background: rgba(233, 69, 96, .2);
    outline: none;
}
.gt-nav-mobile-footer {
    border-top: 1px solid rgba(255, 255, 255, .12);
    margin-top: .85rem;
    padding-top: .85rem;
}
.gt-nav-mobile-user {
    display: block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.gt-nav-full {
    width: 100%;
    justify-content: center;
    margin-bottom: .5rem;
}
.gt-nav-full:last-child {
    margin-bottom: 0;
}
.gt-nav-lock-scroll {
    overflow: hidden !important;
}
@media (max-width: 991.98px) {
    .gt-nav-brand-text {
        max-width: 140px;
    }
}
@media (min-width: 992px) {
    .gt-nav-desktop-only {
        display: flex;
    }
    .gt-nav-mobile-only,
    .gt-nav-overlay,
    .gt-nav-drawer {
        display: none !important;
    }
}
</style>
@endonce

<nav
    id="{{ $navId }}"
    class="gt-nav-shell"
    data-gt-nav-shell="1"
    data-gt-role="{{ \Illuminate\Support\Str::slug($roleBadge ?: 'user') }}"
    data-gt-active-group="{{ $activeGroupId }}"
    data-gt-drawer-id="{{ $drawerId }}"
    data-gt-overlay-id="{{ $overlayId }}"
>
    <div class="gt-nav-container">
        <a href="{{ $brandHref }}" class="gt-nav-brand">
            <i class="{{ $logoIcon }}" aria-hidden="true"></i>
            <span class="gt-nav-brand-text">{{ $brand }}</span>
            @if($roleBadge !== '')
                <span class="gt-nav-role">{{ $roleBadge }}</span>
            @endif
        </a>

        <div class="gt-nav-desktop-only gt-nav-groups" role="navigation" aria-label="Urun gruplari">
            @foreach($normalizedGroups as $groupIndex => $group)
                @php
                    $menuId = $desktopMenuPrefix . $groupIndex;
                @endphp
                <div
                    class="gt-nav-group"
                    data-gt-nav-group="{{ $group['id'] }}"
                    data-gt-group-active="{{ $group['is_active'] ? '1' : '0' }}"
                >
                    <a href="{{ $group['href'] }}" class="gt-nav-group-btn {{ $group['is_active'] ? 'is-active' : '' }}" aria-haspopup="true" aria-controls="{{ $menuId }}">
                        <i class="{{ $group['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $group['label'] }}</span>
                    </a>
                    <div class="gt-nav-menu" id="{{ $menuId }}">
                        @foreach($group['items'] as $item)
                            <a
                                href="{{ $item['href'] }}"
                                class="gt-nav-menu-link {{ $item['is_active'] ? 'is-active' : '' }}"
                                @if($item['target'] !== '') target="{{ $item['target'] }}" @endif
                            >
                                <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="gt-nav-desktop-only gt-nav-actions">
            @foreach($desktopQuickActions as $action)
                <a
                    href="{{ $action['href'] }}"
                    class="gt-nav-action {{ $action['class'] }}"
                    @if($action['target'] !== '') target="{{ $action['target'] }}" @endif
                >
                    <i class="{{ $action['icon'] }}" aria-hidden="true"></i>{{ $action['label'] }}
                </a>
            @endforeach

            @if($showNotificationBell)
                <x-notification-bell />
            @endif

            @if($showThemeToggle)
                <button class="theme-toggle-btn" onclick="{{ $themeToggleHandler }}" title="Tema degistir">
                    <i id="themeToggleIcon" class="fas fa-moon"></i>
                </button>
            @endif

            <a href="{{ $profileRoute }}" class="gt-nav-link gt-nav-profile {{ in_array($active, ['profil', 'hesap'], true) ? 'is-active' : '' }}">
                <i class="fas fa-user-cog" aria-hidden="true"></i>{{ $profileName }}
            </a>

            @if($showSupport && $supportUrl)
                <a href="{{ $supportUrl }}" target="_blank" class="gt-nav-action btn-success">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i>Destek
                </a>
            @endif

            <form method="POST" action="{{ $logoutAction }}">
                @csrf
                <button class="gt-nav-action" type="submit" title="{{ $logoutLabel }}">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>{{ $logoutLabel }}
                </button>
            </form>
        </div>

        <div class="gt-nav-mobile-only gt-nav-mobile-head">
            @foreach($mobileHeaderQuickActions as $action)
                <a
                    href="{{ $action['href'] }}"
                    class="gt-nav-icon-btn"
                    data-gt-nav-close-link
                    @if($action['target'] !== '') target="{{ $action['target'] }}" @endif
                    title="{{ $action['label'] }}"
                >
                    <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                </a>
            @endforeach

            <button
                type="button"
                class="gt-nav-icon-btn"
                data-gt-nav-open="{{ $drawerId }}"
                aria-controls="{{ $drawerId }}"
                aria-expanded="false"
                aria-label="Menuyu ac"
            >
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</nav>

<div id="{{ $overlayId }}" class="gt-nav-overlay" data-gt-nav-overlay-for="{{ $drawerId }}"></div>

<aside id="{{ $drawerId }}" class="gt-nav-drawer" aria-hidden="true">
    <div class="gt-nav-drawer-header">
        <h5 class="gt-nav-drawer-title">
            <i class="{{ $logoIcon }}" aria-hidden="true"></i>{{ $brand }}
            @if($roleBadge !== '')
                <span class="gt-nav-role">{{ $roleBadge }}</span>
            @endif
        </h5>
        <button type="button" class="gt-nav-icon-btn" data-gt-nav-close aria-label="Kapat">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>
    <div class="gt-nav-drawer-body">
        @if($mobilePanelQuickActions->isNotEmpty())
            <div>
                @foreach($mobilePanelQuickActions as $action)
                    <a
                        href="{{ $action['href'] }}"
                        class="gt-nav-action gt-nav-full {{ $action['class'] }}"
                        data-gt-nav-close-link
                        @if($action['target'] !== '') target="{{ $action['target'] }}" @endif
                    >
                        <i class="{{ $action['icon'] }}" aria-hidden="true"></i>{{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        @foreach($normalizedGroups as $group)
            <details class="gt-nav-mobile-group" {{ $group['is_active'] ? 'open' : '' }}>
                <summary>
                    <i class="{{ $group['icon'] }}" aria-hidden="true"></i>{{ $group['label'] }}
                </summary>
                <div class="gt-nav-mobile-links">
                    @if($group['href'] !== '#')
                        <a href="{{ $group['href'] }}" class="gt-nav-mobile-link" data-gt-nav-close-link>
                            <i class="fas fa-compass" aria-hidden="true"></i>
                            <span>{{ $group['label'] }} Merkezi</span>
                        </a>
                    @endif
                    @foreach($group['items'] as $item)
                        <a
                            href="{{ $item['href'] }}"
                            class="gt-nav-mobile-link {{ $item['is_active'] ? 'is-active' : '' }}"
                            data-gt-nav-close-link
                            @if($item['target'] !== '') target="{{ $item['target'] }}" @endif
                        >
                            <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </details>
        @endforeach

        <div class="gt-nav-mobile-footer">
            @if($showNotificationBell)
                <div class="mb-2">
                    <x-notification-bell />
                </div>
            @endif

            @if($showThemeToggle)
                <button class="gt-nav-action gt-nav-full" type="button" onclick="{{ $themeToggleHandler }}">
                    <i class="fas fa-adjust" aria-hidden="true"></i>Tema Degistir
                </button>
            @endif

            <a href="{{ $profileRoute }}" class="gt-nav-action gt-nav-full" data-gt-nav-close-link>
                <i class="fas fa-user-cog" aria-hidden="true"></i>
                <span class="gt-nav-mobile-user">{{ $profileName }}</span>
            </a>

            @if($showSupport && $supportUrl)
                <a href="{{ $supportUrl }}" target="_blank" class="gt-nav-action gt-nav-full btn-success">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i>WhatsApp Destek
                </a>
            @endif

            <form method="POST" action="{{ $logoutAction }}">
                @csrf
                <button class="gt-nav-action gt-nav-full" type="submit">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>{{ $logoutLabel }}
                </button>
            </form>
        </div>
    </div>
</aside>

@once
<script>
(function () {
    if (window.__gtNavShellInit === true) {
        return;
    }

    window.__gtNavShellInit = true;

    const initOne = (navRoot) => {
        if (!navRoot || navRoot.dataset.gtNavReady === '1') {
            return;
        }

        navRoot.dataset.gtNavReady = '1';

        const drawerId = navRoot.dataset.gtDrawerId;
        const overlayId = navRoot.dataset.gtOverlayId;
        const drawer = drawerId ? document.getElementById(drawerId) : null;
        const overlay = overlayId ? document.getElementById(overlayId) : null;
        const openButton = drawerId ? navRoot.querySelector('[data-gt-nav-open="' + drawerId + '"]') : null;
        const closeButtons = drawer ? drawer.querySelectorAll('[data-gt-nav-close], [data-gt-nav-close-link]') : [];

        const openDrawer = () => {
            if (!drawer || !overlay) {
                return;
            }
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            overlay.classList.add('is-open');
            if (openButton) {
                openButton.setAttribute('aria-expanded', 'true');
            }
            document.body.classList.add('gt-nav-lock-scroll');
        };

        const closeDrawer = () => {
            if (!drawer || !overlay) {
                return;
            }
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            overlay.classList.remove('is-open');
            if (openButton) {
                openButton.setAttribute('aria-expanded', 'false');
            }
            document.body.classList.remove('gt-nav-lock-scroll');
        };

        if (openButton) {
            openButton.addEventListener('click', (event) => {
                event.preventDefault();
                openDrawer();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeDrawer);
        }

        closeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                closeDrawer();
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                if (navRoot.contains(document.activeElement) && document.activeElement instanceof HTMLElement) {
                    document.activeElement.blur();
                }
                closeDrawer();
            }
        });
    };

    const initAll = () => {
        document.querySelectorAll('[data-gt-nav-shell="1"]').forEach((navRoot) => initOne(navRoot));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
</script>
@endonce

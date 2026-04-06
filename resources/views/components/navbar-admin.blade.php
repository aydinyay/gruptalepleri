@props([
    'active' => '',
    'showCelebrationWidget' => true,
])

@php
    $user = auth()->user();
    $communicationItems = [
        [
            'label' => 'Bekleyen Bildirimler',
            'href' => route('admin.bekleyen.bildirimler'),
            'icon' => 'fas fa-bell',
            'keys' => ['bekleyen-bildirimler'],
        ],
        [
            'label' => 'Hizli Yanit',
            'href' => route('admin.quick-reply.index'),
            'icon' => 'fas fa-reply',
            'keys' => ['quick-reply'],
        ],
    ];

    if ($user && $user->can_send_broadcast) {
        $communicationItems[] = [
            'label' => 'Duyuru',
            'href' => route('admin.broadcast.create'),
            'icon' => 'fas fa-bullhorn',
            'keys' => ['broadcast'],
        ];
    }

    $groups = [
        [
            'id' => 'talepler',
            'label' => 'Talepler',
            'icon' => 'fas fa-list',
            'href' => route('admin.talepler.hub'),
            'items' => [
                [
                    'label' => 'Panel',
                    'href' => route('admin.dashboard'),
                    'icon' => 'fas fa-home',
                    'keys' => ['dashboard'],
                ],
                [
                    'label' => 'Grup Talepleri',
                    'href' => route('admin.requests.index'),
                    'icon' => 'fas fa-clipboard-list',
                    'keys' => ['talepler'],
                ],
                [
                    'label' => 'Eski Sistem Arsiv',
                    'href' => route('admin.eski-sistem'),
                    'icon' => 'fas fa-box-archive',
                    'keys' => ['eski-sistem'],
                ],
            ],
        ],
        [
            'id' => 'air-charter',
            'label' => 'Air Charter',
            'icon' => 'fas fa-helicopter',
            'href' => route('admin.charter.hub'),
            'items' => [
                [
                    'label' => 'Charter Talepleri',
                    'href' => route('admin.charter.index'),
                    'icon' => 'fas fa-plane-departure',
                    'keys' => ['charter'],
                ],
            ],
        ],
        [
            'id' => 'transfer',
            'label' => 'Transfer',
            'icon' => 'fas fa-shuttle-van',
            'href' => route('admin.transfer.index'),
            'items' => [
                [
                    'label' => 'Transfer Arama',
                    'href' => route('admin.transfer.index'),
                    'icon' => 'fas fa-route',
                    'keys' => ['transfer'],
                ],
            ],
        ],
        [
            'id' => 'leisure',
            'label' => 'Leisure',
            'icon' => 'fas fa-compass',
            'href' => route('admin.leisure.hub'),
            'items' => [
                [
                    'label' => 'Dinner Cruise',
                    'href' => route('admin.dinner-cruise.index'),
                    'icon' => 'fas fa-utensils',
                    'keys' => ['dinner-cruise'],
                ],
                [
                    'label' => 'Yacht Charter',
                    'href' => route('admin.yacht-charter.index'),
                    'icon' => 'fas fa-ship',
                    'keys' => ['yacht-charter'],
                ],
            ],
        ],
        [
            'id' => 'finans',
            'label' => 'Finans',
            'icon' => 'fas fa-wallet',
            'href' => route('admin.finance.hub'),
            'items' => [
                [
                    'label' => 'Finans',
                    'href' => route('admin.finance.index'),
                    'icon' => 'fas fa-coins',
                    'keys' => ['finance'],
                ],
                [
                    'label' => 'Dekontlar',
                    'href'  => route('admin.finance.receipts.index'),
                    'icon'  => 'fas fa-file-invoice',
                    'keys'  => ['finance-receipts'],
                ],
            ],
        ],
        [
            'id' => 'iletisim',
            'label' => 'Iletisim',
            'icon' => 'fas fa-comments',
            'href' => route('admin.iletisim.hub'),
            'items' => $communicationItems,
        ],
        [
            'id' => 'hesap',
            'label' => 'Hesap',
            'icon' => 'fas fa-user-cog',
            'href' => route('admin.hesap.hub'),
            'items' => [
                [
                    'label' => 'Profil',
                    'href' => route('profile.edit'),
                    'icon' => 'fas fa-id-card',
                    'keys' => ['hesap', 'profil'],
                ],
            ],
        ],
    ];
@endphp

<x-navbar-shell
    brand="GrupTalepleri"
    :brand-route="route('admin.dashboard')"
    role-badge="ADMIN"
    :active="$active"
    :groups="$groups"
    :profile-route="route('profile.edit')"
    :profile-name="$user?->name"
    :show-theme-toggle="true"
    theme-toggle-handler="window.adminToggleTheme && window.adminToggleTheme()"
    :show-notification-bell="true"
    nav-id="gt-nav-admin"
/>

@if($showCelebrationWidget)
<x-ai-kutlama-widget />
@endif

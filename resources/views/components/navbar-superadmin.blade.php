@props([
    'active' => '',
    'showCelebrationWidget' => true,
])

@php
    $user = auth()->user();

    $groups = [
        [
            'id' => 'yonetim',
            'label' => 'Yonetim',
            'icon' => 'fas fa-sitemap',
            'href' => route('superadmin.yonetim.hub'),
            'items' => [
                [
                    'label' => 'Panel',
                    'href' => route('superadmin.dashboard'),
                    'icon' => 'fas fa-home',
                    'keys' => ['dashboard'],
                ],
                [
                    'label' => 'Acenteler',
                    'href' => route('superadmin.acenteler'),
                    'icon' => 'fas fa-building',
                    'keys' => ['acenteler'],
                ],
                [
                    'label' => 'TURSAB Kampanyasi',
                    'href' => route('superadmin.tursab.kampanya'),
                    'icon' => 'fas fa-envelope-open-text',
                    'keys' => ['tursab-kampanya'],
                ],
                [
                    'label' => 'TURAi',
                    'href' => route('superadmin.acente.ai'),
                    'icon' => 'fas fa-robot',
                    'keys' => ['acente-ai'],
                ],
                [
                    'label' => 'Sosyal Medya',
                    'href'  => route('superadmin.sosyal.medya'),
                    'icon'  => 'fas fa-share-nodes',
                    'keys'  => ['sosyal-medya'],
                ],
                [
                    'label' => 'Acente İstatistikleri',
                    'href' => route('superadmin.acenteler.istatistik'),
                    'icon' => 'fas fa-chart-bar',
                    'keys' => ['acenteler-istatistik'],
                ],
            ],
        ],
        [
            'id' => 'air-charter',
            'label' => 'Air Charter',
            'icon' => 'fas fa-helicopter',
            'href' => route('superadmin.charter.hub'),
            'items' => [
                [
                    'label' => 'Charter Talepleri',
                    'href' => route('superadmin.charter.index'),
                    'icon' => 'fas fa-plane-departure',
                    'keys' => ['charter'],
                ],
                [
                    'label' => 'Hazir Paketler',
                    'href' => route('superadmin.charter.packages.index'),
                    'icon' => 'fas fa-box-open',
                    'keys' => ['charter-packages'],
                ],
                [
                    'label' => 'RFQ Tedarikciler',
                    'href' => route('superadmin.charter.rfq-suppliers.index'),
                    'icon' => 'fas fa-paper-plane',
                    'keys' => ['charter-rfq-suppliers'],
                ],
            ],
        ],
        [
            'id' => 'transfer',
            'label' => 'Transfer',
            'icon' => 'fas fa-shuttle-van',
            'href' => route('superadmin.transfer.index'),
            'items' => [
                [
                    'label' => 'Transfer Arama',
                    'href' => route('superadmin.transfer.index'),
                    'icon' => 'fas fa-route',
                    'keys' => ['transfer'],
                ],
                [
                    'label' => 'Transfer Operasyon',
                    'href' => route('superadmin.transfer.ops.index'),
                    'icon' => 'fas fa-sliders',
                    'keys' => ['transfer-ops'],
                ],
            ],
        ],
        [
            'id' => 'leisure',
            'label' => 'Leisure',
            'icon' => 'fas fa-compass',
            'href' => route('superadmin.leisure.hub'),
            'items' => [
                [
                    'label' => 'Dinner Cruise',
                    'href' => route('superadmin.dinner-cruise.index'),
                    'icon' => 'fas fa-utensils',
                    'keys' => ['dinner-cruise'],
                ],
                [
                    'label' => 'Yacht Charter',
                    'href' => route('superadmin.yacht-charter.index'),
                    'icon' => 'fas fa-ship',
                    'keys' => ['yacht-charter'],
                ],
                [
                    'label' => 'Leisure Ayarlari',
                    'href' => route('superadmin.leisure.settings.index'),
                    'icon' => 'fas fa-sliders',
                    'keys' => ['leisure-settings'],
                ],
            ],
        ],
        [
            'id' => 'finans',
            'label' => 'Finans',
            'icon' => 'fas fa-wallet',
            'href' => route('superadmin.finance.hub'),
            'items' => [
                [
                    'label' => 'Finans',
                    'href' => route('superadmin.finance.index'),
                    'icon' => 'fas fa-coins',
                    'keys' => ['finance'],
                ],
            ],
        ],
        [
            'id' => 'iletisim',
            'label' => 'Iletisim',
            'icon' => 'fas fa-comments',
            'href' => route('superadmin.iletisim.hub'),
            'items' => [
                [
                    'label' => 'Hizli Yanit',
                    'href' => route('superadmin.quick-reply.index'),
                    'icon' => 'fas fa-reply',
                    'keys' => ['quick-reply'],
                ],
                [
                    'label' => 'Broadcast Gecmisi',
                    'href' => route('superadmin.broadcast.gecmisi'),
                    'icon' => 'fas fa-bullhorn',
                    'keys' => ['broadcast'],
                ],
                [
                    'label' => 'SMS Raporlar',
                    'href' => route('superadmin.sms.raporlar'),
                    'icon' => 'fas fa-chart-line',
                    'keys' => ['sms-raporlar'],
                ],
            ],
        ],
        [
            'id' => 'sistem',
            'label' => 'Sistem',
            'icon' => 'fas fa-cogs',
            'href' => route('superadmin.sistem.hub'),
            'items' => [
                [
                    'label' => 'Site Ayarlari',
                    'href' => route('superadmin.site.ayarlar'),
                    'icon' => 'fas fa-sliders-h',
                    'keys' => ['site-ayarlar'],
                ],
                [
                    'label' => 'SMS Ayarlari',
                    'href' => route('superadmin.sms.ayarlar'),
                    'icon' => 'fas fa-sms',
                    'keys' => ['sms-ayarlar'],
                ],
                [
                    'label' => 'AI Kutlama',
                    'href' => route('superadmin.site.ayarlar', ['sekme' => 'ai']),
                    'icon' => 'fas fa-wand-magic-sparkles',
                    'keys' => ['ai-kutlama'],
                ],
            ],
        ],
    ];
@endphp

<x-navbar-shell
    brand="GrupTalepleri"
    :brand-route="route('superadmin.dashboard')"
    role-badge="SUPERADMIN"
    :active="$active"
    :groups="$groups"
    :profile-route="route('profile.edit')"
    :profile-name="$user?->name"
    :show-theme-toggle="true"
    theme-toggle-handler="window.adminToggleTheme && window.adminToggleTheme()"
    :show-notification-bell="true"
    nav-id="gt-nav-superadmin"
/>

@if($showCelebrationWidget)
<x-ai-kutlama-widget />
@endif

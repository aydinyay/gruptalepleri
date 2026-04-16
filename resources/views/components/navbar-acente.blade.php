@props([
    'active' => '',
    'showCelebrationWidget' => true,
])

@php
    $calisanlarFeatureReady = \Illuminate\Support\Facades\Schema::hasColumn('users', 'parent_agency_id');
    $isAcenteOwner = $calisanlarFeatureReady && auth()->user()?->isAcenteOwner();
    $canFinans = !$calisanlarFeatureReady || auth()->user()?->canDo('finans') || $isAcenteOwner;
    $transferSupplierFeatureReady = \Illuminate\Support\Facades\Schema::hasTable('transfer_suppliers');
    $transferSupplier = null;
    $transferTermsVersion = 1;
    $isTransferSupplierApproved = false;
    $hasAcceptedCurrentTerms = false;
    $showTransferSupplierTermsBanner = false;

    if ($transferSupplierFeatureReady) {
        $transferSupplier = \App\Models\TransferSupplier::query()
            ->where('user_id', auth()->id())
            ->first();
        $transferTermsVersion = \App\Models\SistemAyar::transferSupplierTermsVersion();
        $isTransferSupplierApproved = (bool) ($transferSupplier?->is_approved);
        $hasAcceptedCurrentTerms = $transferSupplier?->hasAcceptedVersion($transferTermsVersion) ?? false;
        $showTransferSupplierTermsBanner = $isTransferSupplierApproved && ! $hasAcceptedCurrentTerms;
    }

    $adminPhone = $_adminTelefon ?? '905324262630';
    $quickActions = [
        [
            'label' => 'Yeni Talep',
            'href' => route('acente.requests.create'),
            'icon' => 'fas fa-plus',
            'class' => 'btn btn-sm btn-danger',
            'mobile_header' => true,
        ],
    ];

    $groups = [
        [
            'id' => 'talepler',
            'label' => 'Talepler',
            'icon' => 'fas fa-list',
            'href' => route('acente.talepler.hub'),
            'items' => [
                [
                    'label' => 'Taleplerim',
                    'href' => route('acente.dashboard'),
                    'icon' => 'fas fa-clipboard-list',
                    'keys' => ['dashboard'],
                ],
                [
                    'label' => 'Klasik Talep',
                    'href' => route('acente.requests.create'),
                    'icon' => 'fas fa-file-circle-plus',
                    'keys' => ['create', 'show', 'requests'],
                ],
            ],
        ],
        [
            'id' => 'air-charter',
            'label' => 'Air Charter',
            'icon' => 'fas fa-helicopter',
            'href' => route('acente.charter.hub'),
            'items' => [
                [
                    'label' => 'Charter Talepleri',
                    'href' => route('acente.charter.index'),
                    'icon' => 'fas fa-plane-departure',
                    'keys' => ['charter'],
                ],
                [
                    'label' => 'Yeni Charter Talebi',
                    'href' => route('acente.charter.create'),
                    'icon' => 'fas fa-plus-circle',
                    'keys' => ['charter-create'],
                ],
            ],
        ],
        [
            'id' => 'transfer',
            'label' => 'Transfer',
            'icon' => 'fas fa-shuttle-van',
            'href' => route('acente.transfer.index'),
            'items' => [
                [
                    'label' => 'Transfer Arama',
                    'href' => route('acente.transfer.index'),
                    'icon' => 'fas fa-route',
                    'keys' => ['transfer'],
                ],
                ...(
                    $transferSupplierFeatureReady && $isTransferSupplierApproved && $hasAcceptedCurrentTerms
                    ? [[
                        'label' => 'Tedarikci Paneli',
                        'href' => route('acente.transfer.supplier.index'),
                        'icon' => 'fas fa-briefcase',
                        'keys' => ['transfer-supplier'],
                    ]]
                    : []
                ),
                ...(
                    $transferSupplierFeatureReady && $showTransferSupplierTermsBanner
                    ? [[
                        'label' => 'Tedarikci Sozlesme Onayi',
                        'href' => route('acente.transfer.supplier.terms.show'),
                        'icon' => 'fas fa-file-signature',
                        'keys' => ['transfer-supplier-terms'],
                    ]]
                    : []
                ),
            ],
        ],
        [
            'id' => 'leisure',
            'label' => 'Leisure',
            'icon' => 'fas fa-compass',
            'href' => route('acente.leisure.hub'),
            'items' => [
                [
                    'label' => 'Dinner Cruise',
                    'href' => route('acente.dinner-cruise.catalog'),
                    'icon' => 'fas fa-utensils',
                    'keys' => ['dinner-cruise'],
                ],
                [
                    'label' => 'Yacht Charter',
                    'href' => route('acente.yacht-charter.catalog'),
                    'icon' => 'fas fa-ship',
                    'keys' => ['yacht-charter'],
                ],
                [
                    'label' => 'Günübirlik Turlar',
                    'href' => route('acente.tour.catalog'),
                    'icon' => 'fas fa-map-location-dot',
                    'keys' => ['tour'],
                ],
            ],
        ],
        [
            'id' => 'rezervasyonlarim',
            'label' => 'Rezervasyonlarım',
            'icon' => 'fas fa-calendar-check',
            'href' => route('acente.rezervasyonlarim.index'),
            'items' => [
                [
                    'label' => 'Tüm Rezervasyonlarım',
                    'href' => route('acente.rezervasyonlarim.index'),
                    'icon' => 'fas fa-list-check',
                    'keys' => ['rezervasyonlarim'],
                ],
            ],
        ],
        [
            'id' => 'b2c',
            'label' => 'B2C',
            'icon' => 'fas fa-store',
            'href' => route('acente.b2c.index'),
            'items' => [
                [
                    'label' => 'GrupRezervasyonları',
                    'href'  => route('acente.b2c.index'),
                    'icon'  => 'fas fa-globe',
                    'keys'  => ['b2c'],
                ],
            ],
        ],
        ...($canFinans ? [[
            'id' => 'finans',
            'label' => 'Finans',
            'icon' => 'fas fa-wallet',
            'href' => route('acente.finance.hub'),
            'items' => [
                [
                    'label' => 'Finans',
                    'href' => route('acente.finance.index'),
                    'icon' => 'fas fa-coins',
                    'keys' => ['finance'],
                ],
            ],
        ]] : []),
        [
            'id' => 'hesap',
            'label' => 'Hesap',
            'icon' => 'fas fa-user-cog',
            'href' => route('acente.hesap.hub'),
            'items' => [
                [
                    'label' => 'Profil',
                    'href' => route('acente.profil'),
                    'icon' => 'fas fa-id-card',
                    'keys' => ['profil', 'hesap'],
                ],
                ...($isAcenteOwner ? [[
                    'label' => 'Çalışanlar',
                    'href'  => route('acente.calisanlar.index'),
                    'icon'  => 'fas fa-users',
                    'keys'  => ['calisanlar'],
                ]] : []),
            ],
        ],
    ];
@endphp

<x-navbar-shell
    brand="GrupTalepleri"
    :brand-route="route('acente.dashboard')"
    role-badge="ACENTE"
    :active="$active"
    :groups="$groups"
    :quick-actions="$quickActions"
    :profile-route="route('acente.profil')"
    :profile-name="auth()->user()->name"
    :show-theme-toggle="true"
    theme-toggle-handler="window.siteToggleTheme && window.siteToggleTheme()"
    :show-notification-bell="true"
    :show-support="true"
    :support-url="'https://wa.me/' . $adminPhone"
    nav-id="gt-nav-acente"
/>

@if($showTransferSupplierTermsBanner)
<div class="container-fluid px-4 py-2 border-bottom bg-warning-subtle">
    <div class="d-flex flex-wrap align-items-center gap-2 small">
        <span class="fw-bold text-dark">Transfer Tedarikci Yetkisi Aktif</span>
        <span class="text-muted">Calismaya baslamak icin guncel sozlesmeyi onaylamaniz gerekiyor.</span>
        <a href="{{ route('acente.transfer.supplier.terms.show') }}" class="btn btn-sm btn-warning ms-auto">Sozlesmeyi Onayla</a>
    </div>
</div>
@endif

@if($_acentePreviewMode ?? false)
<div class="container-fluid px-4 py-2 border-bottom" style="background:#fff3cd;">
    <div class="d-flex flex-wrap align-items-center gap-2 small">
        <span class="fw-bold text-dark">Admin Onizleme Modu</span>
        <span class="text-muted">
            Giris: <strong>{{ auth()->user()->name }}</strong>
            <span class="mx-1">|</span>
            Onizlenen acente: <strong>{{ $_acentePreviewUser?->name }}</strong>
        </span>
        <form method="POST" action="{{ route('acente.preview.stop') }}" class="ms-auto">
            @csrf
            <button class="btn btn-sm btn-outline-dark">Onizlemeyi Kapat</button>
        </form>
    </div>
</div>
@endif

@if($showCelebrationWidget)
<x-ai-kutlama-widget />
@endif

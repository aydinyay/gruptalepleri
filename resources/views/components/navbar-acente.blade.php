@props(['active' => ''])
@once
<style>
.nav-lc{color:rgba(255,255,255,0.7)!important;font-size:0.875rem;padding:0.45rem 0.9rem;border-radius:6px;transition:all 0.2s;text-decoration:none!important;white-space:nowrap;}
.nav-lc:hover,.nav-lc-active{color:#fff!important;background:rgba(255,255,255,0.1);}
.acente-navbar .navbar-toggler { border-color: rgba(255,255,255,0.35); }
.acente-navbar .navbar-toggler:focus { box-shadow: none; }
@media (max-width: 767.98px) {
    .acente-navbar .navbar-brand { margin-right: .25rem; }
    .acente-navbar .brand-text { display: none; }
    .acente-navbar .nav-mobile-quick { display: flex; align-items: center; gap: .4rem; }
    .acente-navbar .nav-collapse-panel {
        margin-top: .65rem;
        padding: .5rem;
        border-radius: 10px;
        background: rgba(255,255,255,0.06);
    }
    .acente-navbar .nav-lc {
        white-space: normal;
        width: 100%;
    }
    .acente-navbar .nav-collapse-panel .btn,
    .acente-navbar .nav-collapse-panel .theme-toggle-btn,
    .acente-navbar .nav-collapse-panel form {
        width: 100%;
    }
    .acente-navbar .nav-collapse-panel form .btn { width: 100%; }
}
</style>
@endonce

<nav style="background:#1a1a2e;" class="navbar navbar-expand-md navbar-dark mb-0 acente-navbar">
    <div class="container-fluid px-3 px-md-4">
        <a href="{{ route('acente.dashboard') }}"
           style="color:#e94560!important;font-weight:700;font-size:1.2rem;text-decoration:none;"
           class="navbar-brand">
            <i class="fas fa-plane-departure me-1" aria-hidden="true"></i><span class="brand-text">GrupTalepleri</span>
        </a>

        @php($oturumKullanici = auth()->user())

        <div class="ms-auto nav-mobile-quick d-md-none">
            <a href="{{ route('acente.requests.create') }}" class="btn btn-sm btn-danger px-2" title="Yeni Talep">
                <i class="fas fa-plus"></i>
            </a>
            <button class="navbar-toggler p-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#acenteNavbarMenu" aria-controls="acenteNavbarMenu" aria-expanded="false" aria-label="Menüyü aç/kapat">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <button class="navbar-toggler d-none d-md-inline-block ms-auto p-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#acenteNavbarMenu" aria-controls="acenteNavbarMenu" aria-expanded="false" aria-label="Menüyü aç/kapat">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="acenteNavbarMenu">
            <div class="nav-collapse-panel d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 ms-md-auto">
                <a href="{{ route('acente.dashboard') }}"
                   class="nav-lc {{ $active === 'dashboard' ? 'nav-lc-active' : '' }}">
                    <i class="fas fa-list me-1"></i>Taleplerim
                </a>
                <a href="{{ route('acente.requests.create') }}" class="btn btn-sm btn-danger px-3 d-none d-md-inline-block">
                    <i class="fas fa-plus me-1"></i>Yeni Talep
                </a>
                <x-notification-bell />
                <button class="theme-toggle-btn" onclick="window.siteToggleTheme && window.siteToggleTheme()" title="Tema değiştir">
                    <i id="themeToggleIcon" class="fas fa-moon"></i>
                </button>
                <a href="{{ route('acente.profil') }}"
                   class="nav-lc {{ $active === 'profil' ? 'nav-lc-active' : '' }}">
                    <i class="fas fa-user-cog me-1"></i>{{ $oturumKullanici->name }}
                </a>
                <a href="https://wa.me/{{ $_adminTelefon ?? '905324262630' }}" target="_blank"
                   class="btn btn-sm btn-success px-2" title="WhatsApp Destek">
                    <i class="fab fa-whatsapp me-1"></i><span class="d-md-none">WhatsApp Destek</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline ms-md-1">
                    @csrf
                    <button class="btn btn-sm btn-outline-light" title="Çıkış">
                        <i class="fas fa-sign-out-alt me-1"></i><span class="d-md-none">Çıkış</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

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

<x-ai-kutlama-widget />

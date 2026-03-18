@props(['active' => ''])
@once
<style>
.nav-lc { color: rgba(255,255,255,0.76) !important; font-size: 0.875rem; padding: 0.45rem 0.8rem; border-radius: 6px; transition: all 0.2s; text-decoration: none !important; white-space: nowrap; border: 1px solid transparent; }
.nav-lc:hover, .nav-lc-active { color: #fff !important; background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.14); }
.gt-role-navbar { background: #1a1a2e; border-bottom: 1px solid rgba(255,255,255,0.08); }
.gt-role-navbar .navbar-toggler { border-color: rgba(255,255,255,0.35); }
.gt-role-navbar .navbar-toggler:focus { box-shadow: none; }
.gt-role-navbar .nav-icon-btn { width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.28); color: rgba(255,255,255,0.86); text-decoration: none; background: transparent; }
.gt-role-navbar .nav-icon-btn:hover { color: #fff; background: rgba(255,255,255,0.12); }
.gt-role-navbar .brand-text { color: #e94560; font-weight: 700; font-size: 1.2rem; text-decoration: none; }
.gt-role-navbar .brand-role { font-size: 0.64rem; color: rgba(255,255,255,0.5); font-weight: 500; }
.gt-role-navbar .gt-search { min-width: 220px; max-width: 360px; width: 100%; }
.gt-role-navbar .gt-search .form-control { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.2); color: #fff; }
.gt-role-navbar .gt-search .form-control::placeholder { color: rgba(255,255,255,0.58); }
.gt-role-navbar .dropdown-menu { min-width: 240px; }
@media (max-width: 991.98px) {
    .gt-role-navbar .brand-word { display: none; }
    .gt-role-navbar .gt-nav-panel { margin-top: .65rem; padding-top: .55rem; border-top: 1px solid rgba(255,255,255,0.12); }
    .gt-role-navbar .gt-search { max-width: none; }
    .gt-role-navbar .nav-lc { display: block; width: 100%; white-space: normal; }
}
</style>
@endonce

@php($oturumKullanici = auth()->user())

<nav class="navbar navbar-expand-lg navbar-dark mb-0 gt-role-navbar">
    <div class="container-fluid px-3 px-lg-4">
        <a href="{{ route('admin.dashboard') }}" class="navbar-brand d-flex align-items-center gap-2 mb-0">
            <i class="fas fa-plane-departure text-danger" aria-hidden="true"></i>
            <span class="brand-text"><span class="brand-word">GrupTalepleri</span></span>
            <span class="brand-role">ADMIN</span>
        </a>

        <div class="d-flex align-items-center gap-2 ms-auto me-2 me-lg-0">
            <x-notification-bell />
            <button class="navbar-toggler p-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbarMenu" aria-controls="adminNavbarMenu" aria-expanded="false" aria-label="Menuyu ac/kapat">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="adminNavbarMenu">
            <div class="gt-nav-panel d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 ms-lg-auto w-100 w-lg-auto">
                <form class="gt-search d-flex me-lg-1" role="search" method="GET" action="{{ route('admin.requests.index') }}">
                    <input class="form-control form-control-sm" type="search" name="q" placeholder="GTPNR veya acente ara..." value="{{ request('q') }}">
                </form>

                <a href="{{ route('admin.dashboard') }}" class="nav-lc {{ $active === 'dashboard' ? 'nav-lc-active' : '' }}">
                    <i class="fas fa-home me-1"></i>Panel
                </a>
                <a href="{{ route('admin.requests.index') }}" class="nav-lc {{ $active === 'talepler' ? 'nav-lc-active' : '' }}">
                    <i class="fas fa-list me-1"></i>Talepler
                </a>
                <a href="{{ route('admin.charter.index') }}" class="nav-lc {{ $active === 'charter' ? 'nav-lc-active' : '' }}">
                    <i class="fas fa-helicopter me-1"></i>Charter
                </a>
                @if(auth()->user()->can_send_broadcast)
                    <a href="{{ route('admin.broadcast.create') }}" class="nav-lc {{ $active === 'broadcast' ? 'nav-lc-active' : '' }}">
                        <i class="fas fa-bullhorn me-1"></i>Duyuru
                    </a>
                @endif

                <div class="dropdown">
                    <button class="nav-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Hesap ve Ayarlar">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ $oturumKullanici->name }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user-cog me-2"></i>Profil</a></li>
                        <li>
                            <button type="button" class="dropdown-item" onclick="window.adminToggleTheme && window.adminToggleTheme()">
                                <i class="fas fa-circle-half-stroke me-2"></i>Gorunum: Acik/Koyu
                            </button>
                        </li>
                        <li><a class="dropdown-item" href="{{ route('admin.quick-reply.index') }}"><i class="fas fa-bolt me-2"></i>Hizli Yanitla</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cikis
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<x-ai-kutlama-widget />

@props(['active' => ''])
@once
<style>
.nav-lc{color:rgba(255,255,255,0.7)!important;font-size:0.875rem;padding:0.45rem 0.9rem;border-radius:6px;transition:all 0.2s;text-decoration:none!important;white-space:nowrap;}
.nav-lc:hover,.nav-lc-active{color:#fff!important;background:rgba(255,255,255,0.1);}
</style>
@endonce

<nav style="background:#1a1a2e;" class="navbar navbar-dark mb-0">
    <div class="container-fluid px-4">
        <a href="{{ route('superadmin.dashboard') }}"
           style="color:#e94560!important;font-weight:700;font-size:1.2rem;text-decoration:none;"
           class="navbar-brand">
            GrupTalepleri
            <span style="font-size:0.65rem;color:rgba(255,255,255,0.4);font-weight:400;margin-left:4px;">SUPERADMIN</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('superadmin.dashboard') }}"
               class="nav-lc {{ $active === 'dashboard' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-home me-1"></i>Panel
            </a>
            <a href="{{ route('superadmin.acenteler') }}"
               class="nav-lc {{ $active === 'acenteler' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-building me-1"></i>Acenteler
            </a>
            <a href="{{ route('superadmin.charter.index') }}"
               class="nav-lc {{ $active === 'charter' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-helicopter me-1"></i>Air Charter
            </a>
            <a href="{{ route('superadmin.charter.rfq-suppliers.index') }}"
               class="nav-lc {{ $active === 'charter-rfq-suppliers' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-paper-plane me-1"></i>RFQ Tedarikciler
            </a>
            <a href="{{ route('superadmin.site.ayarlar') }}"
               class="nav-lc {{ in_array($active, ['site-ayarlar', 'sms-ayarlar'], true) ? 'nav-lc-active' : '' }}">
                <i class="fas fa-cogs me-1"></i>Site Ayarlari
            </a>
            <x-notification-bell />
            <a href="{{ route('profile.edit') }}"
               class="nav-lc d-none d-md-inline border-start border-secondary ps-3 ms-1">
                <i class="fas fa-user-cog me-1"></i>{{ auth()->user()->name }}
            </a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline ms-1">
                @csrf
                <button class="btn btn-sm btn-outline-light" title="Cikis">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

<x-ai-kutlama-widget />

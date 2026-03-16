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
            ✈️ GrupTalepleri
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
            <a href="{{ route('superadmin.sms.ayarlar') }}"
               class="nav-lc {{ $active === 'sms-ayarlar' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-sms me-1"></i>SMS Ayarları
            </a>
            <a href="{{ route('superadmin.sms.raporlar') }}"
               class="nav-lc {{ $active === 'sms-raporlar' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-chart-bar me-1"></i>SMS Raporlar
            </a>
            <a href="{{ route('superadmin.broadcast.gecmisi') }}"
               class="nav-lc {{ $active === 'broadcast' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-bullhorn me-1"></i>Duyuru
            </a>
            <x-notification-bell />
            <a href="{{ route('profile.edit') }}"
               class="nav-lc d-none d-md-inline border-start border-secondary ps-3 ms-1">
                <i class="fas fa-user-cog me-1"></i>{{ auth()->user()->name }}
            </a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline ms-1">
                @csrf
                <button class="btn btn-sm btn-outline-light" title="Çıkış">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

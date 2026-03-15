@props(['active' => ''])
@once
<style>
.nav-lc{color:rgba(255,255,255,0.7)!important;font-size:0.875rem;padding:0.45rem 0.9rem;border-radius:6px;transition:all 0.2s;text-decoration:none!important;white-space:nowrap;}
.nav-lc:hover,.nav-lc-active{color:#fff!important;background:rgba(255,255,255,0.1);}
</style>
@endonce

<nav style="background:#1a1a2e;" class="navbar navbar-dark mb-0">
    <div class="container-fluid px-4">
        <a href="{{ route('acente.dashboard') }}"
           style="color:#e94560!important;font-weight:700;font-size:1.2rem;text-decoration:none;"
           class="navbar-brand">
            ✈️ GrupTalepleri
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('acente.dashboard') }}"
               class="nav-lc {{ $active === 'dashboard' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-list me-1"></i>Taleplerim
            </a>
            <a href="{{ route('acente.requests.create') }}" class="btn btn-sm btn-danger px-3">
                <i class="fas fa-plus me-1"></i>Yeni Talep
            </a>
            <x-notification-bell />
            <a href="{{ route('acente.profil') }}"
               class="nav-lc d-none d-md-inline {{ $active === 'profil' ? 'nav-lc-active' : '' }}">
                <i class="fas fa-user-cog me-1"></i>{{ auth()->user()->name }}
            </a>
            <a href="https://wa.me/905324262630" target="_blank"
               class="btn btn-sm btn-success px-2" title="WhatsApp Destek">
                <i class="fab fa-whatsapp"></i>
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

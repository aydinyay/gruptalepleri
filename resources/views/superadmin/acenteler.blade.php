<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acenteler — Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1a1a2e !important; }
        .navbar-brand { color: #e94560 !important; font-weight: 700; }
        .nav-link-custom { color: rgba(255,255,255,0.7) !important; font-size: 0.875rem; padding: 0.5rem 1rem; border-radius: 6px; transition: all 0.2s; }
        .nav-link-custom:hover, .nav-link-custom.active { color: #fff !important; background: rgba(255,255,255,0.08); }
        .page-header { background: #1a1a2e; padding: 1.2rem 0; margin-bottom: 1.5rem; }
        .page-header h5 { color: #fff; font-weight: 700; margin: 0; }
        .page-header p { color: rgba(255,255,255,0.5); font-size: 0.82rem; margin: 0; }
        .table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600; }
        .table td { vertical-align: middle; font-size: 0.875rem; }
        .role-badge { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
        .role-superadmin { background: #1a1a2e; color: #e94560; }
        .role-admin { background: #084298; color: #fff; }
        .role-acente { background: #e9ecef; color: #495057; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="{{ route('superadmin.dashboard') }}">✈️ GrupTalepleri <span style="font-size:0.7rem;color:rgba(255,255,255,0.4);font-weight:400;">SUPERADMIN</span></a>
        <div class="d-flex align-items-center gap-1">
            <a href="{{ route('superadmin.dashboard') }}" class="nav-link-custom">Dashboard</a>
            <a href="{{ route('superadmin.acenteler') }}" class="nav-link-custom active">Acenteler</a>
            <a href="{{ route('superadmin.sms.ayarlar') }}" class="nav-link-custom">SMS Ayarları</a>
            <a href="{{ route('superadmin.sms.raporlar') }}" class="nav-link-custom">SMS Raporlar</a>
            <x-notification-bell />
            <a href="{{ route('profile.edit') }}" class="nav-link-custom border-start border-secondary ps-3 ms-1" title="Profil Ayarları">
                <i class="fas fa-user-cog me-1"></i>{{ auth()->user()->name }}
            </a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i></button>
            </form>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-building me-2" style="color:#e94560;"></i>Acente Yönetimi</h5>
        <p>{{ $acenteler->count() }} acente kayıtlı</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Firma</th>
                        <th>Yetkili</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th>TURSAB</th>
                        <th>Kayıt</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($acenteler as $acente)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $acente->company_title }}</div>
                            @if($acente->tourism_title)
                                <div class="text-muted small">{{ $acente->tourism_title }}</div>
                            @endif
                        </td>
                        <td>{{ $acente->contact_name }}</td>
                        <td>{{ $acente->phone }}</td>
                        <td class="text-muted">{{ $acente->email }}</td>
                        <td>{{ $acente->tursab_no ?? '—' }}</td>
                        <td class="text-muted">{{ $acente->created_at->format('d.m.Y') }}</td>
                        <td>
                            <span class="role-badge role-{{ $acente->user?->role ?? 'acente' }}">
                                {{ strtoupper($acente->user?->role ?? 'acente') }}
                            </span>
                        </td>
                        <td>
                            @if($acente->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Pasif</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                {{-- Aktif/Pasif toggle --}}
                                <form method="POST" action="{{ route('superadmin.acenteler.toggle', $acente) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $acente->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $acente->is_active ? 'Pasif yap' : 'Aktif yap' }}">
                                        <i class="fas {{ $acente->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                    </button>
                                </form>

                                {{-- Rol değiştir --}}
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Rol değiştir">
                                        <i class="fas fa-user-tag"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @foreach(['acente','admin','superadmin'] as $rol)
                                        <li>
                                            <form method="POST" action="{{ route('superadmin.acenteler.rol', $acente) }}">
                                                @csrf
                                                <input type="hidden" name="role" value="{{ $rol }}">
                                                <button type="submit" class="dropdown-item {{ $acente->user?->role === $rol ? 'fw-bold' : '' }}">
                                                    {{ strtoupper($rol) }}
                                                    @if($acente->user?->role === $rol) <i class="fas fa-check ms-1 text-success"></i> @endif
                                                </button>
                                            </form>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>

                                {{-- Sil --}}
                                <form method="POST" action="{{ route('superadmin.acenteler.sil', $acente) }}"
                                      onsubmit="return confirm('{{ $acente->company_title }} acentesini ve kullanıcısını silmek istediğinize emin misiniz?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Henüz acente yok.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

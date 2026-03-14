<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Raporlar — Superadmin</title>
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
        .table td { vertical-align: middle; font-size: 0.82rem; }
        .msg-cell { max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand" href="{{ route('superadmin.dashboard') }}">✈️ GrupTalepleri <span style="font-size:0.7rem;color:rgba(255,255,255,0.4);font-weight:400;">SUPERADMIN</span></a>
        <div class="d-flex align-items-center gap-1">
            <a href="{{ route('superadmin.dashboard') }}" class="nav-link-custom">Dashboard</a>
            <a href="{{ route('superadmin.acenteler') }}" class="nav-link-custom">Acenteler</a>
            <a href="{{ route('superadmin.sms.ayarlar') }}" class="nav-link-custom">SMS Ayarları</a>
            <a href="{{ route('superadmin.sms.raporlar') }}" class="nav-link-custom active">SMS Raporlar</a>
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
        <h5><i class="fas fa-chart-bar me-2" style="color:#e94560;"></i>SMS Gönderim Raporu</h5>
        <p>Sistemden gönderilen tüm SMS'lerin kaydı</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    {{-- FİLTRELER --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Alıcı</label>
                    <select name="recipient" class="form-select form-select-sm">
                        <option value="">Tümü</option>
                        <option value="admin" {{ request('recipient') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="acente" {{ request('recipient') === 'acente' ? 'selected' : '' }}>Acente</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Durum</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tümü</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Gönderildi</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Başarısız</option>
                        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Zamanlandı</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Bekliyor</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Tarih</label>
                    <input type="date" name="tarih" class="form-control form-control-sm" value="{{ request('tarih') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Filtrele</button>
                    <a href="{{ route('superadmin.sms.raporlar') }}" class="btn btn-sm btn-outline-secondary ms-1">Temizle</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-sms me-1"></i> SMS Kayıtları</span>
            <span class="badge bg-secondary">Toplam: {{ $logs->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Talep</th>
                        <th>Alıcı</th>
                        <th>Numara</th>
                        <th>Mesaj</th>
                        <th>Durum</th>
                        <th>Gönderim Zamanı</th>
                        <th>API Yanıtı</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-muted">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if($log->request)
                                <a href="{{ route('admin.requests.show', $log->request->gtpnr) }}" class="fw-bold text-decoration-none">
                                    {{ $log->request->gtpnr }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($log->recipient === 'admin')
                                <span class="badge bg-dark">Admin</span>
                            @else
                                <span class="badge bg-primary">Acente</span>
                            @endif
                            <div class="text-muted" style="font-size:0.75rem;">{{ $log->recipient_name }}</div>
                        </td>
                        <td>{{ $log->phone }}</td>
                        <td class="msg-cell" title="{{ $log->message }}">{{ $log->message }}</td>
                        <td>
                            @if($log->status === 'sent')
                                <span class="badge bg-success">Gönderildi</span>
                            @elseif($log->status === 'failed')
                                <span class="badge bg-danger">Başarısız</span>
                            @elseif($log->status === 'scheduled')
                                <span class="badge bg-warning text-dark">Zamanlandı</span>
                            @else
                                <span class="badge bg-secondary">Bekliyor</span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:0.75rem;">
                            @if($log->scheduled_for && $log->status === 'scheduled')
                                <span class="text-warning">⏰ {{ $log->scheduled_for->format('d.m H:i') }}</span>
                            @elseif($log->sent_at)
                                {{ $log->sent_at->format('d.m H:i') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:0.75rem;">{{ $log->provider_code ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

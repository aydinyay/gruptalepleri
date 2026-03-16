<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Raporları — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .page-header { background: #1a1a2e; padding: 1.2rem 0; margin-bottom: 1.5rem; }
        .page-header h5 { color: #fff; font-weight: 700; margin: 0; }
        .page-header p { color: rgba(255,255,255,0.5); font-size: 0.82rem; margin: 0; }
        .table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600; }
        .table td { vertical-align: middle; font-size: 0.82rem; }
        .msg-cell { max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
</head>
<body>

<x-navbar-superadmin active="sms-raporlar" />

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
            <span class="fw-bold"><i class="fas fa-sms me-1"></i> SMS / E-posta Kayıtları</span>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary">Toplam: {{ $logs->total() }}</span>
                @if($logs->total() > 0)
                <form method="POST" action="{{ route('superadmin.sms.log.hepsini-sil') }}"
                      onsubmit="return confirm('Tüm {{ $logs->total() }} log kaydı silinecek. Emin misiniz?')">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger py-0 px-2">
                        <i class="fas fa-trash me-1"></i>Tümünü Sil
                    </button>
                </form>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tarih</th>
                        <th>Talep</th>
                        <th>Kanal</th>
                        <th>Alıcı</th>
                        <th>Numara/E-posta</th>
                        <th>Mesaj</th>
                        <th>Durum</th>
                        <th>Gönderim</th>
                        <th></th>
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
                            @if($log->channel === 'email')
                                <span class="badge bg-info text-dark">E-posta</span>
                            @else
                                <span class="badge bg-success">SMS</span>
                            @endif
                        </td>
                        <td>
                            @if($log->recipient === 'admin' || $log->recipient === 'superadmin')
                                <span class="badge bg-dark">{{ ucfirst($log->recipient) }}</span>
                            @else
                                <span class="badge bg-primary">Acente</span>
                            @endif
                            <div class="text-muted" style="font-size:0.75rem;">{{ $log->recipient_name }}</div>
                        </td>
                        <td>{{ $log->phone ?? $log->subject ?? '—' }}</td>
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
                        <td>
                            <form method="POST" action="{{ route('superadmin.sms.log.sil', $log->id) }}"
                                  onsubmit="return confirm('Bu log silinsin mi?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Sil">
                                    <i class="fas fa-times" style="font-size:0.7rem;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
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

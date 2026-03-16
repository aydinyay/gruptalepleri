<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iletisim Raporlari - Superadmin</title>
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
        .channel-tabs .btn { min-width: 145px; }
    </style>
</head>
<body>
@php
    $activeChannel = $channel ?? request('channel', 'all');
    $commonQuery = array_filter([
        'recipient' => request('recipient'),
        'status' => request('status'),
        'tarih' => request('tarih'),
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<x-navbar-superadmin active="sms-raporlar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-chart-bar me-2" style="color:#e94560;"></i>Iletisim Gonderim Raporu</h5>
        <p>Sistemden gonderilen tum SMS ve E-posta kayitlari</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2 channel-tabs">
            <a href="{{ route('superadmin.sms.raporlar', array_merge($commonQuery, ['channel' => 'all'])) }}"
               class="btn btn-sm {{ $activeChannel === 'all' ? 'btn-dark' : 'btn-outline-dark' }}">
                Tumu
                <span class="badge {{ $activeChannel === 'all' ? 'bg-light text-dark' : 'bg-dark' }}">{{ $channelCounts['all'] ?? 0 }}</span>
            </a>
            <a href="{{ route('superadmin.sms.raporlar', array_merge($commonQuery, ['channel' => 'sms'])) }}"
               class="btn btn-sm {{ $activeChannel === 'sms' ? 'btn-success' : 'btn-outline-success' }}">
                SMS
                <span class="badge {{ $activeChannel === 'sms' ? 'bg-light text-success' : 'bg-success' }}">{{ $channelCounts['sms'] ?? 0 }}</span>
            </a>
            <a href="{{ route('superadmin.sms.raporlar', array_merge($commonQuery, ['channel' => 'email'])) }}"
               class="btn btn-sm {{ $activeChannel === 'email' ? 'btn-info text-dark' : 'btn-outline-info' }}">
                E-posta
                <span class="badge {{ $activeChannel === 'email' ? 'bg-light text-info' : 'bg-info text-dark' }}">{{ $channelCounts['email'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-semibold">
                <i class="fas fa-wallet text-success me-1"></i>SMS Kalan Bakiye
            </div>
            <div class="text-end">
                @if(($smsBalance['available'] ?? false) && isset($smsBalance['balance']))
                    <div class="h5 mb-0">{{ number_format((float) $smsBalance['balance'], 2, ',', '.') }}</div>
                    <small class="text-muted">kredi</small>
                @else
                    <div class="text-muted small">{{ $smsBalance['message'] ?? 'Bakiye bilgisi alinamadi.' }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="channel" value="{{ $activeChannel }}">
                <div class="col-auto">
                    <label class="form-label small mb-1">Alici</label>
                    <select name="recipient" class="form-select form-select-sm">
                        <option value="">Tumu</option>
                        <option value="admin" {{ request('recipient') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="superadmin" {{ request('recipient') === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                        <option value="acente" {{ request('recipient') === 'acente' ? 'selected' : '' }}>Acente</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Durum</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tumu</option>
                        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Gonderildi</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Basarisiz</option>
                        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Zamanlandi</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Bekliyor</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Tarih</label>
                    <input type="date" name="tarih" class="form-control form-control-sm" value="{{ request('tarih') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Filtrele</button>
                    <a href="{{ route('superadmin.sms.raporlar', ['channel' => $activeChannel]) }}" class="btn btn-sm btn-outline-secondary ms-1">Temizle</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-sms me-1"></i>SMS / E-posta Kayitlari</span>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary">Toplam: {{ $logs->total() }}</span>
                @if($logs->total() > 0)
                <form method="POST" action="{{ route('superadmin.sms.log.hepsini-sil') }}"
                      onsubmit="return confirm('Tum {{ $logs->total() }} log kaydi silinecek. Emin misiniz?')">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger py-0 px-2">
                        <i class="fas fa-trash me-1"></i>Tumunu Sil
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
                        <th>Alici</th>
                        <th>Numara/E-posta</th>
                        <th>Mesaj</th>
                        <th>Durum</th>
                        <th>Gonderim</th>
                        <th>Teslim/Okundu</th>
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
                                <span class="text-muted">-</span>
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
                        <td>{{ $log->phone ?? $log->subject ?? '-' }}</td>
                        <td class="msg-cell" title="{{ $log->message }}">{{ $log->message }}</td>
                        <td>
                            @if($log->status === 'sent')
                                <span class="badge bg-success">Gonderildi</span>
                            @elseif($log->status === 'failed')
                                <span class="badge bg-danger">Basarisiz</span>
                            @elseif($log->status === 'scheduled')
                                <span class="badge bg-warning text-dark">Zamanlandi</span>
                            @else
                                <span class="badge bg-secondary">Bekliyor</span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:0.75rem;">
                            @if($log->scheduled_for && $log->status === 'scheduled')
                                <span class="text-warning"><i class="fas fa-clock me-1"></i>{{ $log->scheduled_for->format('d.m H:i') }}</span>
                            @elseif($log->sent_at)
                                {{ $log->sent_at->format('d.m H:i') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="font-size:0.75rem;">
                            @if($log->channel === 'sms')
                                @if($log->delivery_status === 'delivered')
                                    <span class="badge bg-success">Iletildi</span>
                                    @if($log->delivered_at)
                                        <div class="text-muted mt-1">{{ $log->delivered_at->format('d.m H:i') }}</div>
                                    @endif
                                @elseif($log->delivery_status === 'undelivered')
                                    <span class="badge bg-danger">Iletilemedi</span>
                                @elseif($log->delivery_status)
                                    <span class="badge bg-secondary">{{ $log->delivery_status }}</span>
                                @elseif($log->status === 'sent')
                                    <span class="badge bg-warning text-dark">Takip bekleniyor</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            @else
                                @if($log->delivery_status === 'delivered')
                                    <span class="badge bg-success">Iletildi</span>
                                @elseif($log->delivery_status)
                                    <span class="badge bg-secondary">{{ $log->delivery_status }}</span>
                                @else
                                    <span class="text-muted">Okundu/Goruldu verisi yok</span>
                                @endif
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
                        <td colspan="10" class="text-center text-muted py-4">Kayit bulunamadi.</td>
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

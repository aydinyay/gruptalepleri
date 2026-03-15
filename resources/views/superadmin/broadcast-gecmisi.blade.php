<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Broadcast Geçmişi — Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,0.5); font-size:0.82rem; margin:0; }
    </style>
</head>
<body>

<x-navbar-superadmin active="broadcast" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-bullhorn me-2" style="color:#e94560;"></i>Broadcast Duyuru Geçmişi</h5>
        <p>Tüm adminlerin gönderdiği duyurular</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    {{-- Admin yetki paneli --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-semibold bg-white">
            <i class="fas fa-key me-2 text-warning"></i>Duyuru Gönderme Yetkileri
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Admin</th>
                        <th>E-posta</th>
                        <th class="text-center">Duyuru Yetkisi</th>
                        <th>Gönderilen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($adminler as $admin)
                    @php
                        $gonderilenSayi = \App\Models\BroadcastNotification::where('sender_id', $admin->id)->count();
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $admin->name }}</div>
                            <div class="text-muted small">{{ strtoupper($admin->role) }}</div>
                        </td>
                        <td class="text-muted small">{{ $admin->email }}</td>
                        <td class="text-center">
                            @if($admin->role !== 'superadmin')
                            <form method="POST" action="{{ route('superadmin.broadcast.yetki', $admin->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $admin->can_send_broadcast ? 'btn-success' : 'btn-outline-secondary' }}">
                                    @if($admin->can_send_broadcast)
                                        <i class="fas fa-check-circle me-1"></i>Yetkili
                                    @else
                                        <i class="fas fa-times-circle me-1"></i>Yetkisiz
                                    @endif
                                </button>
                            </form>
                            @else
                                <span class="badge bg-dark">Superadmin</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $gonderilenSayi }} duyuru</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tüm duyurular --}}
    <div class="card shadow-sm">
        <div class="card-header fw-semibold bg-white">
            <i class="fas fa-history me-2 text-secondary"></i>Tüm Gönderimler
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Gönderen</th>
                        <th>Duyuru</th>
                        <th>Hedef</th>
                        <th>Kanallar</th>
                        <th class="text-center">Alıcı</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($duyurular as $d)
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $d->sender?->name ?? '—' }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ strtoupper($d->sender?->role ?? '') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">
                                @if($d->emoji)<span class="me-1">{{ $d->emoji }}</span>@endif
                                {{ $d->title }}
                            </div>
                            <div class="text-muted small" style="max-width:280px;">{{ Str::limit($d->message, 70) }}</div>
                        </td>
                        <td><span class="badge bg-secondary">{{ $d->targetLabel() }}</span></td>
                        <td>
                            @foreach($d->channelLabels() as $lbl)
                                <span class="badge bg-light text-dark border me-1" style="font-size:0.7rem;">{{ $lbl }}</span>
                            @endforeach
                        </td>
                        <td class="text-center fw-bold">{{ $d->sent_count }}</td>
                        <td>
                            <span class="badge" style="background:{{ $d->statusColor() }};">{{ $d->statusLabel() }}</span>
                            @if($d->scheduled_at && $d->status === 'scheduled')
                                <div class="text-muted" style="font-size:0.7rem;">{{ $d->scheduled_at->format('d.m.Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $d->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-bullhorn fa-2x mb-2 d-block opacity-25"></i>
                            Henüz duyuru gönderilmedi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $duyurular->links() }}</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

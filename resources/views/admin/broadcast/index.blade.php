<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Duyuru Geçmişi — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }</style>
</head>
<body>

<x-navbar-admin active="broadcast" />

<div class="container-fluid py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">📢 Duyurularım</h4>
        @if(auth()->user()->can_send_broadcast)
            <a href="{{ route('admin.broadcast.create') }}" class="btn btn-danger btn-sm">
                <i class="fas fa-plus me-1"></i>Yeni Duyuru
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!auth()->user()->can_send_broadcast)
        <div class="alert alert-warning">
            <i class="fas fa-lock me-2"></i>Duyuru gönderme yetkiniz bulunmamaktadır.
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Duyuru</th>
                        <th>Hedef</th>
                        <th>Kanallar</th>
                        <th class="text-center">Gönderildi</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($duyurular as $d)
                    <tr>
                        <td>
                            <div class="fw-semibold">
                                @if($d->emoji)<span class="me-1">{{ $d->emoji }}</span>@endif
                                {{ $d->title }}
                            </div>
                            <div class="text-muted small" style="max-width:320px;">{{ Str::limit($d->message, 80) }}</div>
                        </td>
                        <td><span class="badge bg-secondary">{{ $d->targetLabel() }}</span></td>
                        <td>
                            @foreach($d->channelLabels() as $lbl)
                                <span class="badge bg-light text-dark border me-1" style="font-size:0.72rem;">{{ $lbl }}</span>
                            @endforeach
                        </td>
                        <td class="text-center fw-bold">{{ $d->sent_count }}</td>
                        <td>
                            <span class="badge" style="background:{{ $d->statusColor() }};">
                                {{ $d->statusLabel() }}
                            </span>
                            @if($d->scheduled_at && $d->status === 'scheduled')
                                <div class="text-muted" style="font-size:0.72rem;">
                                    {{ $d->scheduled_at->format('d.m.Y H:i') }}
                                </div>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $d->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            @if(in_array($d->status, ['scheduled','draft']))
                            <form method="POST" action="{{ route('admin.broadcast.destroy', $d) }}"
                                  onsubmit="return confirm('Bu duyuruyu silmek istediğinize emin misiniz?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
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

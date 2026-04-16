<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tedarikçi Başvuruları — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p  { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .badge-pending   { background:#fff3cd; color:#856404; }
        .badge-reviewing { background:#cff4fc; color:#055160; }
        .badge-approved  { background:#d1e7dd; color:#0a3622; }
        .badge-rejected  { background:#f8d7da; color:#842029; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-store me-2" style="color:#e8a020;"></i>Tedarikçi Başvuruları (Genel)</h5>
        <p>gruprezervasyonlari.com üzerinden "Tedarikçi Ol" formu ile yapılan başvurular</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Başvuran / Şirket</th>
                            <th>İletişim</th>
                            <th>Hizmetler</th>
                            <th>Notlar</th>
                            <th>Başvuru Tarihi</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($apps as $app)
                        <tr>
                            <td class="text-muted small">{{ $app->id }}</td>
                            <td>
                                <strong>{{ $app->applicant_name }}</strong>
                                @if($app->company_name)
                                <small class="text-muted d-block">{{ $app->company_name }}</small>
                                @endif
                                @if($app->reviewer)
                                <small class="text-info d-block mt-1">
                                    <i class="fas fa-user-check me-1"></i>{{ $app->reviewer->name }}
                                    @if($app->reviewed_at)— {{ $app->reviewed_at->format('d.m.Y') }}@endif
                                </small>
                                @endif
                            </td>
                            <td>
                                <div class="small">{{ $app->email }}</div>
                                @if($app->phone)<div class="small text-muted">{{ $app->phone }}</div>@endif
                            </td>
                            <td>
                                @php
                                    $serviceLabels = [
                                        'transfer' => ['icon'=>'fa-car',   'lbl'=>'Transfer'],
                                        'leisure'  => ['icon'=>'fa-water', 'lbl'=>'Leisure'],
                                        'charter'  => ['icon'=>'fa-plane', 'lbl'=>'Charter'],
                                        'tour'     => ['icon'=>'fa-map',   'lbl'=>'Tur'],
                                    ];
                                    $types = $app->service_types_json ?? [];
                                @endphp
                                @foreach($types as $t)
                                    <span class="badge bg-primary me-1">
                                        <i class="fas {{ $serviceLabels[$t]['icon'] ?? 'fa-tag' }} me-1"></i>{{ $serviceLabels[$t]['lbl'] ?? $t }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="small text-muted" style="max-width:200px;">
                                {{ Str::limit($app->notes, 80) }}
                            </td>
                            <td class="small text-muted">{{ $app->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                @php
                                    $statusLabels = ['pending'=>'Bekliyor','reviewing'=>'İnceleniyor','approved'=>'Onaylı','rejected'=>'Reddedildi'];
                                @endphp
                                <span class="badge badge-{{ $app->status }} px-2 py-1">{{ $statusLabels[$app->status] ?? $app->status }}</span>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($app->status !== 'reviewing')
                                    <form method="POST" action="{{ route('superadmin.b2c.supplier-apps.update', $app) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="reviewing">
                                        <button type="submit" class="btn btn-info btn-sm" title="İncelemeye Al">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </form>
                                    @endif

                                    @if($app->status !== 'approved')
                                    <form method="POST" action="{{ route('superadmin.b2c.supplier-apps.update', $app) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-success btn-sm" title="Onayla">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    @endif

                                    @if($app->status !== 'rejected')
                                    <form method="POST" action="{{ route('superadmin.b2c.supplier-apps.update', $app) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Reddet">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                                Henüz başvuru yok.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($apps->hasPages())
        <div class="card-footer bg-transparent">
            {{ $apps->links() }}
        </div>
        @endif
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

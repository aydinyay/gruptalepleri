{{-- B2C Acente Başvuruları — v2 --}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>B2C Acente Başvuruları — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .stat-card { background:#fff; border-radius:10px; padding:1rem 1.25rem; border-left:4px solid #1a3c6b; }
        .stat-card .num { font-size:1.6rem; font-weight:800; color:#1a1a2e; }
        .stat-card .lbl { font-size:.78rem; color:#6c757d; font-weight:500; }
        .badge-pending   { background:#fff3cd; color:#856404; }
        .badge-approved  { background:#d1e7dd; color:#0a3622; }
        .badge-rejected  { background:#f8d7da; color:#842029; }
        .badge-suspended { background:#e2e3e5; color:#41464b; }
        .action-form { display:inline; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4 d-flex justify-content-between align-items-center">
        <div>
            <h5><i class="fas fa-building me-2" style="color:#e8a020;"></i>B2C Acente Başvuruları</h5>
            <p>gruprezervasyonlari.com'a katılmak isteyen acentelerin başvuruları</p>
        </div>
        <button class="btn btn-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modal-direct-approve">
            <i class="fas fa-plus-circle me-1"></i> Direkt Ekle & Onayla
        </button>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Sayaçlar --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card border-warning">
                <div class="num text-warning">{{ $counts['pending'] }}</div>
                <div class="lbl">Bekleyen</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card border-success">
                <div class="num text-success">{{ $counts['approved'] }}</div>
                <div class="lbl">Onaylı</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card border-danger">
                <div class="num text-danger">{{ $counts['rejected'] }}</div>
                <div class="lbl">Reddedilen</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="num text-secondary">{{ $counts['suspended'] }}</div>
                <div class="lbl">Askıya Alınan</div>
            </div>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3 d-flex gap-2 flex-wrap">
            @foreach(['pending' => 'Bekleyen', 'approved' => 'Onaylı', 'rejected' => 'Reddedilen', 'suspended' => 'Askıya Alınan', 'all' => 'Tümü'] as $val => $lbl)
                <a href="{{ route('superadmin.b2c.agencies', ['status' => $val]) }}"
                   class="btn btn-sm {{ $status === $val ? 'btn-dark' : 'btn-outline-secondary' }}">
                    {{ $lbl }}
                    @if($val !== 'all' && isset($counts[$val]))
                        <span class="badge bg-white text-dark ms-1">{{ $counts[$val] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    {{-- Tablo --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Acente</th>
                            <th>Hizmetler</th>
                            <th>Transfer Tedarikçi</th>
                            <th>Başvuru Tarihi</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($subs as $sub)
                        <tr>
                            <td class="text-muted small">{{ $sub->id }}</td>
                            <td>
                                <strong>{{ $sub->agency->name ?? '—' }}</strong>
                                <small class="text-muted d-block">{{ $sub->agency->email ?? '' }}</small>
                            </td>
                            <td>
                                @php
                                    $serviceLabels = [
                                        'transfer' => 'Transfer',
                                        'leisure'  => 'Leisure',
                                        'charter'  => 'Charter',
                                        'tour'     => 'Tur',
                                    ];
                                    $types = $sub->service_types_json ?? [];
                                @endphp
                                @foreach($types as $t)
                                    <span class="badge bg-primary me-1">{{ $serviceLabels[$t] ?? $t }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($sub->transferSupplier)
                                    <span class="text-success small">
                                        <i class="fas fa-check-circle me-1"></i>{{ $sub->transferSupplier->company_name }}
                                    </span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $sub->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <span class="badge px-2 py-1 badge-{{ $sub->status }}">
                                    @php
                                        $statusLabels = [
                                            'pending'   => 'Bekliyor',
                                            'approved'  => 'Onaylı',
                                            'rejected'  => 'Reddedildi',
                                            'suspended' => 'Askıda',
                                        ];
                                    @endphp
                                    {{ $statusLabels[$sub->status] ?? $sub->status }}
                                </span>
                                @if($sub->rejection_reason)
                                    <small class="text-danger d-block mt-1">{{ Str::limit($sub->rejection_reason, 40) }}</small>
                                @endif
                                @if($sub->admin_note)
                                    <small class="text-muted d-block mt-1">Not: {{ Str::limit($sub->admin_note, 40) }}</small>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    {{-- Onayla --}}
                                    @if($sub->status !== 'approved')
                                    <button class="btn btn-success btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal-approve-{{ $sub->id }}">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    @endif

                                    {{-- Reddet --}}
                                    @if($sub->status !== 'rejected')
                                    <button class="btn btn-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal-reject-{{ $sub->id }}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    @endif

                                    {{-- Askıya Al --}}
                                    @if($sub->status === 'approved')
                                    <button class="btn btn-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal-suspend-{{ $sub->id }}">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        {{-- Onayla Modal --}}
                        <div class="modal fade" id="modal-approve-{{ $sub->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('superadmin.b2c.agencies.approve', $sub) }}">
                                        @csrf @method('PATCH')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Başvuruyu Onayla</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-3">
                                                <strong>{{ $sub->agency->name ?? '' }}</strong> acentesinin B2C başvurusunu onaylıyorsunuz.
                                            </p>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Komisyon Oranı (%) — opsiyonel</label>
                                                <input type="number" name="commission_pct" class="form-control form-control-sm"
                                                       step="0.01" min="0" max="50"
                                                       placeholder="Boş bırakılırsa sistem varsayılanı kullanılır"
                                                       value="{{ $sub->commission_pct }}">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Admin Notu — opsiyonel</label>
                                                <textarea name="admin_note" class="form-control form-control-sm" rows="2"
                                                          placeholder="İç not...">{{ $sub->admin_note }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>Onayla
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Reddet Modal --}}
                        <div class="modal fade" id="modal-reject-{{ $sub->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('superadmin.b2c.agencies.reject', $sub) }}">
                                        @csrf @method('PATCH')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Başvuruyu Reddet</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Red Nedeni <span class="text-danger">*</span></label>
                                                <textarea name="rejection_reason" class="form-control form-control-sm" rows="3"
                                                          placeholder="Acente'ye iletilecek red nedeni..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times me-1"></i>Reddet
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Askıya Al Modal --}}
                        @if($sub->status === 'approved')
                        <div class="modal fade" id="modal-suspend-{{ $sub->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('superadmin.b2c.agencies.suspend', $sub) }}">
                                        @csrf @method('PATCH')
                                        <div class="modal-header">
                                            <h5 class="modal-title">B2C Erişimini Askıya Al</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Admin Notu — opsiyonel</label>
                                                <textarea name="admin_note" class="form-control form-control-sm" rows="2"
                                                          placeholder="İç not..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="fas fa-pause me-1"></i>Askıya Al
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif

                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Bu durumda başvuru bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($subs->hasPages())
        <div class="card-footer bg-transparent">
            {{ $subs->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Direkt Ekle & Onayla Modal --}}
<div class="modal fade" id="modal-direct-approve" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.b2c.agencies.direct-approve') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-warning"></i>Direkt B2C Onayı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Başvuru beklemeden onaylı bir transfer tedarikçisini B2C platformuna doğrudan ekler.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Transfer Tedarikçisi <span class="text-danger">*</span></label>
                        <select name="transfer_supplier_id" class="form-select form-select-sm" required>
                            <option value="">— Seçin —</option>
                            @foreach($transferSuppliers as $sup)
                                <option value="{{ $sup->id }}">
                                    {{ $sup->company_name }}
                                    @if($sup->user) ({{ $sup->user->name }}) @endif
                                    @if($sup->b2cSubscription) ✓ Zaten kayıtlı @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Sadece onaylı + aktif tedarikçiler listelenir.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Hizmet Tipleri <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(['transfer' => 'Transfer', 'leisure' => 'Leisure', 'charter' => 'Charter', 'tour' => 'Tur'] as $val => $lbl)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="service_types[]"
                                           value="{{ $val }}" id="stype_{{ $val }}"
                                           {{ $val === 'transfer' ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="stype_{{ $val }}">{{ $lbl }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Komisyon Oranı (%) — opsiyonel</label>
                        <input type="number" name="commission_pct" class="form-control form-control-sm"
                               step="0.01" min="0" max="50"
                               placeholder="Boş = sistem varsayılanı">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Admin Notu — opsiyonel</label>
                        <input type="text" name="admin_note" class="form-control form-control-sm"
                               placeholder="İç not (acenteye gösterilmez)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold">
                        <i class="fas fa-check me-1"></i>Onayla
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

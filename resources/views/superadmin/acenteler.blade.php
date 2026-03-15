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
        .page-header { background: #1a1a2e; padding: 1.2rem 0; margin-bottom: 1.5rem; }
        .page-header h5 { color: #fff; font-weight: 700; margin: 0; }
        .page-header p { color: rgba(255,255,255,0.5); font-size: 0.82rem; margin: 0; }
        .table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600; }
        .table td { vertical-align: middle; font-size: 0.875rem; }
        .role-badge { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; }
        .role-superadmin { background: #1a1a2e; color: #e94560; }
        .role-admin { background: #084298; color: #fff; }
        .role-acente { background: #e9ecef; color: #495057; }
        #searchInput { max-width: 360px; }
        tr.hidden-row { display: none; }
    </style>
</head>
<body>

<x-navbar-superadmin active="acenteler" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-building me-2" style="color:#e94560;"></i>Acente Yönetimi</h5>
        <p id="headerCount">{{ $acenteler->count() }} acente kayıtlı</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- ARAMA ÇUBUĞU --}}
    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <div class="input-group" style="max-width:360px;">
            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control"
                   placeholder="Firma adı, yetkili, telefon, e-posta, TURSAB...">
            <button class="btn btn-outline-secondary" id="clearSearch" title="Temizle" style="display:none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <span id="searchResult" class="text-muted small"></span>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="acenteTable">
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
                    <tr data-search="{{ strtolower($acente->company_title . ' ' . $acente->tourism_title . ' ' . $acente->contact_name . ' ' . $acente->phone . ' ' . $acente->email . ' ' . $acente->tursab_no) }}">
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
                            <div class="d-flex gap-1 flex-wrap">

                                {{-- Düzenle --}}
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    title="Düzenle"
                                    onclick="acenteDuzenle({{ $acente->id }},
                                        {{ json_encode($acente->company_title) }},
                                        {{ json_encode($acente->tourism_title) }},
                                        {{ json_encode($acente->contact_name) }},
                                        {{ json_encode($acente->phone) }},
                                        {{ json_encode($acente->email) }},
                                        {{ json_encode($acente->tax_number) }},
                                        {{ json_encode($acente->tax_office) }},
                                        {{ json_encode($acente->address) }},
                                        {{ json_encode($acente->tursab_no) }}
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>

                                {{-- Aktif/Pasif toggle --}}
                                <form method="POST" action="{{ route('superadmin.acenteler.toggle', $acente) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $acente->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            title="{{ $acente->is_active ? 'Pasif yap' : 'Aktif yap' }}">
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

                                {{-- İade Badge Toggle --}}
                                <form method="POST" action="{{ route('superadmin.acenteler.iade-badge', $acente) }}">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-sm {{ $acente->user?->show_iade_badge ? 'btn-warning' : 'btn-outline-secondary' }}"
                                        title="{{ $acente->user?->show_iade_badge ? 'İade badge açık — kapat' : 'İade badge kapalı — aç' }}">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>

                                {{-- Broadcast Yetki Toggle --}}
                                <form method="POST" action="{{ route('superadmin.acenteler.broadcast-yetki', $acente) }}">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-sm {{ $acente->user?->can_send_broadcast ? 'btn-danger' : 'btn-outline-secondary' }}"
                                        title="{{ $acente->user?->can_send_broadcast ? 'Duyuru yetkisi var — kaldır' : 'Duyuru yetkisi yok — ver' }}">
                                        <i class="fas fa-bullhorn"></i>
                                    </button>
                                </form>

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

{{-- DÜZENLEME MODAL --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editForm">
                @csrf
                @method('PATCH')
                <div class="modal-header" style="background:#1a1a2e;">
                    <h5 class="modal-title text-white"><i class="fas fa-edit me-2" style="color:#e94560;"></i>Acente Düzenle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Firma Ünvanı <span class="text-danger">*</span></label>
                            <input type="text" name="company_title" id="e_company_title" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Turizm Ünvanı</label>
                            <input type="text" name="tourism_title" id="e_tourism_title" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Yetkili Adı</label>
                            <input type="text" name="contact_name" id="e_contact_name" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Telefon</label>
                            <input type="text" name="phone" id="e_phone" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">E-posta</label>
                            <input type="email" name="email" id="e_email" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">TURSAB No</label>
                            <input type="text" name="tursab_no" id="e_tursab_no" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vergi No</label>
                            <input type="text" name="tax_number" id="e_tax_number" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Vergi Dairesi</label>
                            <input type="text" name="tax_office" id="e_tax_office" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Adres</label>
                            <textarea name="address" id="e_address" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-sm text-white fw-bold" style="background:#e94560;">
                        <i class="fas fa-save me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── ARAMA ──────────────────────────────────────────────────────────────────
const searchInput = document.getElementById('searchInput');
const clearBtn    = document.getElementById('clearSearch');
const resultEl    = document.getElementById('searchResult');
const headerCount = document.getElementById('headerCount');
const rows        = document.querySelectorAll('#acenteTable tbody tr[data-search]');
const total       = rows.length;

searchInput.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    clearBtn.style.display = q ? '' : 'none';
    let visible = 0;
    rows.forEach(row => {
        const match = row.dataset.search.includes(q);
        row.classList.toggle('hidden-row', !match);
        if (match) visible++;
    });
    if (q) {
        resultEl.textContent = visible + ' / ' + total + ' sonuç';
    } else {
        resultEl.textContent = '';
    }
});

clearBtn.addEventListener('click', function () {
    searchInput.value = '';
    this.style.display = 'none';
    resultEl.textContent = '';
    rows.forEach(r => r.classList.remove('hidden-row'));
    searchInput.focus();
});

// ── DÜZENLEME MODAL ─────────────────────────────────────────────────────────
const editModal = new bootstrap.Modal(document.getElementById('editModal'));
const editForm  = document.getElementById('editForm');
const baseUrl   = '/superadmin/acenteler/';

function acenteDuzenle(id, company_title, tourism_title, contact_name, phone, email, tax_number, tax_office, address, tursab_no) {
    editForm.action = baseUrl + id;
    document.getElementById('e_company_title').value = company_title  || '';
    document.getElementById('e_tourism_title').value = tourism_title  || '';
    document.getElementById('e_contact_name').value  = contact_name   || '';
    document.getElementById('e_phone').value         = phone          || '';
    document.getElementById('e_email').value         = email          || '';
    document.getElementById('e_tax_number').value    = tax_number     || '';
    document.getElementById('e_tax_office').value    = tax_office     || '';
    document.getElementById('e_address').value       = address        || '';
    document.getElementById('e_tursab_no').value     = tursab_no      || '';
    editModal.show();
}
</script>
</body>
</html>

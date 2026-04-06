<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acenteler — Süperadmin</title>
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
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- TÜRSAB LİSTESİNDEN ARA --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex align-items-center gap-2 py-2" style="background:#1a1a2e;">
            <i class="fas fa-search" style="color:#e94560;"></i>
            <span class="fw-bold text-white small">TÜRSAB Listesinden Acente Ara & Davet Et</span>
            <span class="badge bg-secondary ms-auto" id="tursab-total-badge" style="display:none;"></span>
        </div>
        <div class="card-body pb-2">
            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <input type="text" id="tursab-q" class="form-control form-control-sm"
                           placeholder="Acente adı, TÜRSAB belge no veya e-posta...">
                </div>
                <div class="col-md-3">
                    <input type="text" id="tursab-il" class="form-control form-control-sm"
                           placeholder="İl (ör: İSTANBUL)">
                </div>
                <div class="col-md-2">
                    <select id="tursab-grup" class="form-select form-select-sm">
                        <option value="">Tüm Gruplar</option>
                        <option value="A">A Grubu</option>
                        <option value="B">B Grubu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm w-100" style="background:#e94560;color:#fff;"
                            onclick="tursabAra()">
                        <i class="fas fa-search me-1"></i>Ara
                    </button>
                </div>
            </div>
            <div id="tursab-results-wrap" style="display:none;">
                <div class="table-responsive" style="max-height:320px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0" style="font-size:0.8rem;">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>Belge No</th>
                                <th>Acente Adı</th>
                                <th>Ticari Ünvan</th>
                                <th>Grup</th>
                                <th>İl / İlçe</th>
                                <th>Telefon</th>
                                <th>E-posta</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tursab-results-body"></tbody>
                    </table>
                </div>
                <div id="tursab-msg" class="small text-muted mt-1"></div>
            </div>
        </div>
    </div>

    {{-- ARAMA + FİLTRE ÇUBUĞU --}}
    <form method="GET" action="{{ route('superadmin.acenteler') }}" class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <div class="input-group" style="max-width:320px;">
            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control"
                   placeholder="Firma adı, yetkili, telefon, e-posta, TURSAB...">
            <button class="btn btn-outline-secondary" id="clearSearch" title="Temizle" type="button" style="display:none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <select name="giris_filtre" class="form-select form-select-sm" style="max-width:190px;" onchange="this.form.submit()">
            <option value="" {{ request('giris_filtre') === '' || !request('giris_filtre') ? 'selected' : '' }}>Tüm Acenteler</option>
            <option value="30" {{ request('giris_filtre') == '30' ? 'selected' : '' }}>30+ gün giriş yok</option>
            <option value="60" {{ request('giris_filtre') == '60' ? 'selected' : '' }}>60+ gün giriş yok</option>
            <option value="90" {{ request('giris_filtre') == '90' ? 'selected' : '' }}>90+ gün giriş yok</option>
            <option value="hic" {{ request('giris_filtre') === 'hic' ? 'selected' : '' }}>Hiç giriş yapmadı</option>
        </select>
        <select name="talep_filtre" class="form-select form-select-sm" style="max-width:160px;" onchange="this.form.submit()">
            <option value="" {{ !request('talep_filtre') ? 'selected' : '' }}>Tüm Acenteler</option>
            <option value="0" {{ request('talep_filtre') === '0' ? 'selected' : '' }}>Hiç talep yok</option>
            <option value="1" {{ request('talep_filtre') === '1' ? 'selected' : '' }}>En az 1 talep</option>
        </select>
        @if(request('giris_filtre') || request('talep_filtre'))
            <a href="{{ route('superadmin.acenteler') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Filtreyi Temizle
            </a>
        @endif
        <span id="searchResult" class="text-muted small"></span>
    </form>
    <div class="d-flex align-items-center gap-2 mb-3">
        <button class="btn btn-sm btn-outline-primary" id="tumunuSec" type="button">
            <i class="fas fa-check-square me-1"></i>Tümünü Seç
        </button>
        <button class="btn btn-sm btn-primary" id="mesajGonderBtn" type="button"
                data-bs-toggle="modal" data-bs-target="#topluMesajModal" style="display:none;">
            <i class="fas fa-paper-plane me-1"></i>Seçililere Mesaj Gönder
            (<span id="secilenSayi">0</span>)
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="acenteTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                        <th>Firma</th>
                        <th>Yetkili</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th>TURSAB</th>
                        <th>Kayıt</th>
                        <th>Son Giriş</th>
                        <th>Talep</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Transfer</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($acenteler as $acente)
                    <tr data-search="{{ strtolower($acente->company_title . ' ' . $acente->tourism_title . ' ' . $acente->contact_name . ' ' . $acente->phone . ' ' . $acente->email . ' ' . $acente->tursab_no) }}">
                        <td>
                            @if($acente->user_id)
                                <input type="checkbox" class="form-check-input acente-check"
                                       value="{{ $acente->user_id }}">
                            @endif
                        </td>
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
                        <td class="text-muted small">
                            @if($acente->user?->last_login_at)
                                <span title="{{ $acente->user->last_login_at->format('d.m.Y H:i') }}">
                                    {{ $acente->user->last_login_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-danger">Hiç girmedi</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $acente->requests_count ?? 0 }}</span>
                        </td>
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
                            @php
                                $transferSupplier = $acente->transferSupplier;
                            @endphp
                            @if($transferSupplier && $transferSupplier->is_approved)
                                <span class="badge bg-success">Tedarikçi</span>
                            @elseif($transferSupplier)
                                <span class="badge bg-secondary">Kapalı</span>
                            @else
                                <span class="badge bg-light text-dark border">Yok</span>
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

                                {{-- Transfer Tedarikçi Yetki Toggle --}}
                                <form method="POST" action="{{ route('superadmin.acenteler.transfer-supplier-toggle', $acente) }}">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-sm {{ ($acente->transferSupplier?->is_approved ?? false) ? 'btn-info text-white' : 'btn-outline-info' }}"
                                        title="{{ ($acente->transferSupplier?->is_approved ?? false) ? 'Transfer tedarikçi yetkisini kaldır' : 'Transfer tedarikçi yetkisi ver' }}">
                                        <i class="fas fa-shuttle-van"></i>
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
                        <td colspan="13" class="text-center text-muted py-4">Henüz acente yok.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- TOPLU MESAJ MODAL --}}
<div class="modal fade" id="topluMesajModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.acenteler.toplu-mesaj') }}" id="topluMesajForm">
                @csrf
                <div class="modal-header" style="background:#1a1a2e;">
                    <h5 class="modal-title text-white"><i class="fas fa-paper-plane me-2" style="color:#e94560;"></i>Seçili Acentelere Mesaj Gönder</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="secilenListeInfo" class="alert alert-info py-2 small mb-3"></div>

                    {{-- Şablondan yükle --}}
                    @if($sablonlar->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Şablondan Yükle <span class="text-muted">(opsiyonel)</span></label>
                        <select id="sablonSec" class="form-select form-select-sm">
                            <option value="">— Şablon seç —</option>
                            @foreach($sablonlar as $s)
                                <option value="{{ $s->id }}"
                                        data-konu="{{ $s->email_konu }}"
                                        data-mesaj="{{ $s->email_govde ?: $s->sms_govde }}"
                                        data-kanallar="{{ implode(',', $s->kanallar ?? []) }}">
                                    {{ $s->sablon_adi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Konu <span class="text-muted">(email için)</span></label>
                            <input type="text" name="konu" id="tm_konu" class="form-control form-control-sm" placeholder="Email konu satırı">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Mesaj <span class="text-danger">*</span></label>
                            <textarea name="mesaj" id="tm_mesaj" class="form-control form-control-sm" rows="6"
                                      required placeholder="Mesaj içeriği..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Kanallar <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kanallar[]" value="email" id="tm-email" checked>
                                    <label class="form-check-label" for="tm-email"><i class="fas fa-envelope text-primary me-1"></i>Email</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kanallar[]" value="sms" id="tm-sms">
                                    <label class="form-check-label" for="tm-sms"><i class="fas fa-comment-sms text-success me-1"></i>SMS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kanallar[]" value="push" id="tm-push">
                                    <label class="form-check-label" for="tm-push"><i class="fas fa-bell text-warning me-1"></i>Push</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Seçili user_id'ler buraya JS ile eklenecek --}}
                    <div id="secilenHiddenInputs"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-paper-plane me-1"></i>Gönder
                    </button>
                </div>
            </form>
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

// ── TÜRSAB ARAMA ────────────────────────────────────────────────────────────
async function tursabAra() {
    const q    = document.getElementById('tursab-q').value.trim();
    const il   = document.getElementById('tursab-il').value.trim();
    const grup = document.getElementById('tursab-grup').value;
    const msg  = document.getElementById('tursab-msg');
    const wrap = document.getElementById('tursab-results-wrap');
    const body = document.getElementById('tursab-results-body');

    if (q.length < 2 && !il) { msg.style.display='block'; msg.textContent='En az 2 karakter girin.'; wrap.style.display='none'; return; }

    msg.textContent = 'Aranıyor...'; wrap.style.display='none';

    try {
        const params = new URLSearchParams({ q, il, grup });
        const res  = await fetch('/superadmin/tursab-ara?' + params.toString());
        const data = await res.json();

        document.getElementById('tursab-total-badge').style.display = 'inline';
        document.getElementById('tursab-total-badge').textContent   = data.total + ' sonuç';

        if (!data.results.length) {
            msg.textContent = 'Sonuç bulunamadı.'; wrap.style.display='none'; return;
        }

        body.innerHTML = data.results.map((r,i) => `
            <tr>
                <td><span class="badge bg-secondary">${escH(r.belge_no||'')}</span></td>
                <td>${escH(r.acente_unvani)}</td>
                <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escH(r.ticari_unvan)}</td>
                <td><span class="badge bg-primary">${escH(r.grup||'')}</span></td>
                <td>${escH(r.il_ilce||r.il||'')}</td>
                <td>${escH(r.telefon||'—')}</td>
                <td>${escH(r.eposta||'—')}</td>
                <td>
                    ${r.eposta
                        ? `<button class="btn btn-sm py-0 px-2 davet-btn" data-idx="${i}" style="background:#e94560;color:#fff;font-size:0.7rem;">
                            <i class="fas fa-envelope me-1"></i>Davet
                           </button>`
                        : '<span class="text-muted small">—</span>'}
                </td>
            </tr>
        `).join('');

        // data-idx ile tursabResults dizisinde bul — onclick içinde JSON sorununu bypass eder
        window._tursabResults = data.results;
        body.querySelectorAll('.davet-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const r = window._tursabResults[this.dataset.idx];
                tursabDavetGonder(r.eposta, r.acente_unvani, r.belge_no||'', this);
            });
        });

        msg.textContent = data.total > 50 ? `İlk 50 sonuç gösteriliyor (toplam ${data.total})` : '';
        wrap.style.display = 'block';
    } catch(e) {
        msg.textContent = 'Hata: ' + e.message;
    }
}

function escH(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

async function tursabDavetGonder(eposta, acenteUnvani, belgeNo, btn) {
    if (!confirm(`"${acenteUnvani}" acentesine davet emaili gönderilsin mi?\n${eposta}`)) return;

    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    try {
        const res = await fetch('/superadmin/tursab-davet', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ eposta, acente_unvani: acenteUnvani, belge_no: belgeNo })
        });
        const data = await res.json();
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Gönderildi';
            btn.style.background = '#198754';
        } else {
            alert('Hata: ' + data.message);
            btn.disabled = false; btn.innerHTML = orig;
        }
    } catch(e) {
        alert('Hata: ' + e.message);
        btn.disabled = false; btn.innerHTML = orig;
    }
}

// Enter tuşu ile arama
document.getElementById('tursab-q').addEventListener('keydown', e => { if(e.key==='Enter') tursabAra(); });
document.getElementById('tursab-il').addEventListener('keydown', e => { if(e.key==='Enter') tursabAra(); });

// ── CHECKBOX SEÇİM & TOPLU MESAJ ────────────────────────────────────────────
const mesajBtn      = document.getElementById('mesajGonderBtn');
const secilenSayi   = document.getElementById('secilenSayi');
const checkAll      = document.getElementById('checkAll');
const tumunuSecBtn  = document.getElementById('tumunuSec');

function updateSecilenCount() {
    const checks = document.querySelectorAll('.acente-check:checked');
    const n = checks.length;
    secilenSayi.textContent = n;
    mesajBtn.style.display = n > 0 ? '' : 'none';
}

document.querySelectorAll('.acente-check').forEach(cb => {
    cb.addEventListener('change', updateSecilenCount);
});

checkAll.addEventListener('change', function() {
    document.querySelectorAll('.acente-check').forEach(cb => cb.checked = this.checked);
    updateSecilenCount();
});

tumunuSecBtn.addEventListener('click', function() {
    const all = document.querySelectorAll('.acente-check');
    const anyUnchecked = Array.from(all).some(cb => !cb.checked);
    all.forEach(cb => cb.checked = anyUnchecked);
    checkAll.checked = anyUnchecked;
    updateSecilenCount();
});

// Modal açılınca seçilenleri hidden input'lara ekle
document.getElementById('topluMesajModal').addEventListener('show.bs.modal', function() {
    const checks = document.querySelectorAll('.acente-check:checked');
    const hiddenDiv = document.getElementById('secilenHiddenInputs');
    const infoDiv   = document.getElementById('secilenListeInfo');

    hiddenDiv.innerHTML = '';
    checks.forEach(cb => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'secilen[]';
        inp.value = cb.value;
        hiddenDiv.appendChild(inp);
    });
    infoDiv.textContent = checks.length + ' acente seçili.';
});

// Şablondan yükle
const sablonSec = document.getElementById('sablonSec');
if (sablonSec) {
    sablonSec.addEventListener('change', function() {
        const opt = this.selectedOptions[0];
        if (!opt || !opt.value) return;

        const konu  = opt.dataset.konu  || '';
        const mesaj = opt.dataset.mesaj || '';
        const kanallar = (opt.dataset.kanallar || '').split(',').filter(Boolean);

        document.getElementById('tm_konu').value  = konu;
        document.getElementById('tm_mesaj').value = mesaj;

        ['email', 'sms', 'push'].forEach(k => {
            const cb = document.getElementById('tm-' + k);
            if (cb) cb.checked = kanallar.includes(k);
        });
    });
}
</script>
</body>
</html>

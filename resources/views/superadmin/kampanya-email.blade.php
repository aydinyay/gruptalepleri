<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Email Kampanyası — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.page-header p  { color:rgba(255,255,255,0.5); font-size:0.82rem; margin:0; }
.limit-bar { height:10px; border-radius:5px; background:#e9ecef; overflow:hidden; }
.limit-bar-fill { height:100%; background:#e94560; transition:width .3s; border-radius:5px; }
.acente-row td { vertical-align:middle; font-size:0.82rem; }
.acente-row.selected { background:#fff3cd !important; }
.badge-il { background:#e9ecef; color:#495057; font-size:0.7rem; padding:2px 7px; border-radius:10px; }
.sticky-action { position:sticky; top:0; z-index:100; background:#fff; border-bottom:1px solid #dee2e6; padding:10px 0; }
.table th { font-size:0.72rem; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; }
.gecmis-sent   { color:#198754; }
.gecmis-failed { color:#dc3545; }
</style>
</head>
<body>

<x-navbar-superadmin active="kampanya-email" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-envelope-open-text me-2" style="color:#e94560;"></i>Email Kampanyası</h5>
                <p>Bakanlık listesindeki acentelere toplu davet e-postası gönder — günlük limit: 50</p>
            </div>
            <a href="{{ route('tursab.kampanya') }}" class="btn btn-sm btn-outline-light">
                ← Kampanya Hub
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- GÜNLÜK DURUM --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold" style="font-size:0.85rem;">Bugünkü Kullanım</span>
                        <span class="fw-bold {{ $kalanHak > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $bugunGonderilen }} / 50
                        </span>
                    </div>
                    <div class="limit-bar">
                        <div class="limit-bar-fill" style="width:{{ min(100, $bugunGonderilen * 2) }}%"></div>
                    </div>
                    <div class="mt-2 small text-muted">
                        @if($kalanHak > 0)
                            Bugün <strong>{{ $kalanHak }}</strong> email daha gönderebilirsiniz
                        @else
                            <span class="text-danger">Günlük limit doldu. Yarın 00:00'da yenilenir.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="fw-bold mb-1" style="font-size:0.85rem;">Toplam Davet Gönderildi</div>
                    <div style="font-size:2rem;font-weight:800;color:#1a1a2e;">
                        {{ \App\Models\TursabDavet::where('status','sent')->count() }}
                    </div>
                    <div class="small text-muted">{{ \App\Models\TursabDavet::count() }} deneme toplam</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="fw-bold mb-1" style="font-size:0.85rem;">Davet Edilmemiş (e-postalı)</div>
                    <div style="font-size:2rem;font-weight:800;color:#e94560;">
                        {{ \App\Models\Acenteler::whereNotNull('eposta')->where('eposta','!=','')->count() - \App\Models\TursabDavet::distinct('eposta')->count('eposta') }}
                    </div>
                    <div class="small text-muted">e-postası olan acente</div>
                </div>
            </div>
        </div>
    </div>

    {{-- FİLTRELER --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('superadmin.kampanya.email') }}" id="filterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-sm-3">
                        <label class="form-label small mb-1">Arama</label>
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Unvan / Belge No" value="{{ $q }}">
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small mb-1">İl</label>
                        <select name="il" class="form-select form-select-sm" id="ilSelect">
                            <option value="">Tüm İller</option>
                            @foreach($iller as $i)
                                <option value="{{ $i }}" @selected($il === $i)>{{ $i }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small mb-1">İlçe</label>
                        <select name="ilce" class="form-select form-select-sm" id="ilceSelect">
                            <option value="">Tüm İlçeler</option>
                            @if($ilce)
                                <option value="{{ $ilce }}" selected>{{ $ilce }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small mb-1">Grup</label>
                        <select name="grup" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <option value="A" @selected($grup === 'A')>A Grubu</option>
                            <option value="B" @selected($grup === 'B')>B Grubu</option>
                            <option value="C" @selected($grup === 'C')>C Grubu</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-check form-check-sm mt-4">
                            <input type="hidden" name="sadece_yeni" value="0">
                            <input class="form-check-input" type="checkbox" name="sadece_yeni" value="1" id="sadece_yeni" @checked($sadeceDavetEdilmemis)>
                            <label class="form-check-label small" for="sadece_yeni">Sadece davet edilmemiş</label>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filtrele</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ACENTE LİSTESİ --}}
    <form method="POST" action="{{ route('tursab.toplu-davet') }}" id="davetForm">
        @csrf
        <div class="sticky-action shadow-sm mb-3">
            <div class="container-fluid px-4">
                <div class="row align-items-center g-2">
                    <div class="col-auto">
                        <input type="checkbox" id="selectAll" class="form-check-input" title="Tümünü seç">
                        <label for="selectAll" class="form-check-label small ms-1">Tümünü Seç</label>
                        <span class="badge bg-secondary ms-2" id="secilenSayi">0</span> seçildi
                    </div>
                    <div class="col-auto">
                        <select name="sablon" class="form-select form-select-sm" style="width:220px;">
                            <option value="emails.tursab_davet">Standart Davet</option>
                            <option value="emails.tursab_davet_yeni_acente">Yeni Acente Tebrik</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-danger btn-sm" id="gonderBtn"
                            onclick="return onGonderClick()"
                            @if($kalanHak <= 0) disabled @endif>
                            <i class="fas fa-paper-plane me-1"></i>
                            Seçilenlere Email Gönder
                            @if($kalanHak <= 0) (Limit Doldu) @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold" style="font-size:0.85rem;">
                    Acenteler
                    <span class="text-muted fw-normal">({{ $acenteler->total() }} kayıt)</span>
                </span>
                <div class="d-flex gap-2 align-items-center">
                    <select name="per_page_hidden" class="form-select form-select-sm" style="width:80px;"
                        onchange="window.location='{{ route('superadmin.kampanya.email') }}?{{ http_build_query(array_merge(request()->except(['per_page','page']), [])) }}&per_page='+this.value">
                        @foreach([25,50,100,200] as $pp)
                            <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="30"></th>
                            <th>Belge No</th>
                            <th>Acente Unvanı</th>
                            <th>Grup</th>
                            <th>İl / İlçe</th>
                            <th>E-posta</th>
                            <th>Telefon</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($acenteler as $a)
                            @php
                                $val = implode('||', [$a->eposta, $a->acente_unvani, $a->belge_no, $a->il, $a->telefon ?? '']);
                            @endphp
                            <tr class="acente-row">
                                <td><input type="checkbox" name="secilen[]" value="{{ $val }}" class="form-check-input row-check"></td>
                                <td class="text-muted">{{ $a->belge_no }}</td>
                                <td>
                                    <div class="fw-semibold" style="font-size:0.82rem;">{{ $a->acente_unvani }}</div>
                                    @if($a->ticari_unvan && $a->ticari_unvan !== $a->acente_unvani)
                                        <div class="text-muted" style="font-size:0.72rem;">{{ $a->ticari_unvan }}</div>
                                    @endif
                                </td>
                                <td><span class="badge bg-secondary">{{ $a->grup }}</span></td>
                                <td>
                                    <span class="badge-il">{{ $a->il }}</span>
                                    @if($a->il_ilce) <span class="badge-il mt-1 d-block" style="font-size:0.65rem;">{{ $a->il_ilce }}</span> @endif
                                </td>
                                <td style="font-size:0.78rem;">{{ $a->eposta }}</td>
                                <td style="font-size:0.78rem;">{{ $a->telefon }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($acenteler->hasPages())
                <div class="card-footer">{{ $acenteler->links() }}</div>
            @endif
        </div>
    </form>

    {{-- GÖNDERIM GEÇMİŞİ --}}
    @if($gecmis->isNotEmpty())
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-bold py-2" style="font-size:0.85rem;">Gönderim Geçmişi (Son 100)</div>
        <div class="table-responsive" style="max-height:300px;overflow-y:auto;">
            <table class="table table-sm mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Tarih</th><th>Acente</th><th>E-posta</th><th>İl</th><th>Durum</th><th>Gönderen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gecmis as $g)
                        <tr>
                            <td class="text-muted small">{{ $g->created_at->format('d.m H:i') }}</td>
                            <td class="small">{{ $g->acente_unvani }}</td>
                            <td class="small">{{ $g->eposta }}</td>
                            <td class="small">{{ $g->il }}</td>
                            <td class="{{ $g->status === 'sent' ? 'gecmis-sent' : 'gecmis-failed' }} small fw-bold">
                                {{ $g->status === 'sent' ? '✓' : '✗' }}
                            </td>
                            <td class="small text-muted">{{ $g->gonderen?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ilSelect   = document.getElementById('ilSelect');
const ilceSelect = document.getElementById('ilceSelect');
const currentIlce = @json($ilce);

if (ilSelect) {
    ilSelect.addEventListener('change', function () {
        loadIlceler(this.value);
    });
    // Sayfa yüklendiğinde il seçiliyse ilçeleri yükle
    if (ilSelect.value) loadIlceler(ilSelect.value, currentIlce);
}

function loadIlceler(il, selected = '') {
    ilceSelect.innerHTML = '<option value="">Tüm İlçeler</option>';
    if (!il) return;
    fetch('/superadmin/tursab-ilceler?il=' + encodeURIComponent(il), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        data.forEach(function (ilce) {
            const opt = document.createElement('option');
            opt.value = ilce;
            opt.textContent = ilce;
            if (ilce === selected) opt.selected = true;
            ilceSelect.appendChild(opt);
        });
    });
}

// Checkbox logic
const selectAll = document.getElementById('selectAll');
const sayi      = document.getElementById('secilenSayi');

function updateCount() {
    const n = document.querySelectorAll('.row-check:checked').length;
    sayi.textContent = n;
}

selectAll.addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = this.checked;
        cb.closest('tr').classList.toggle('selected', this.checked);
    });
    updateCount();
});

document.querySelectorAll('.row-check').forEach(cb => {
    cb.addEventListener('change', function () {
        this.closest('tr').classList.toggle('selected', this.checked);
        updateCount();
    });
});

function onGonderClick() {
    const n = document.querySelectorAll('.row-check:checked').length;
    if (n === 0) { alert('Lütfen en az bir acente seçin.'); return false; }
    return confirm(n + ' acenteye email gönderilecek. Onaylıyor musunuz?');
}
</script>
</body>
</html>

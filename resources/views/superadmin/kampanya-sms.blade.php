<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SMS Kampanyası — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.page-header p  { color:rgba(255,255,255,0.5); font-size:0.82rem; margin:0; }
.acente-row td { vertical-align:middle; font-size:0.82rem; }
.acente-row.selected { background:#d1fae5 !important; }
.badge-il { background:#e9ecef; color:#495057; font-size:0.7rem; padding:2px 7px; border-radius:10px; }
.sticky-action { position:sticky; top:0; z-index:100; background:#fff; border-bottom:1px solid #dee2e6; padding:10px 0; }
.table th { font-size:0.72rem; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; }
</style>
</head>
<body>

<x-navbar-superadmin active="kampanya-sms" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-sms me-2" style="color:#0dcaf0;"></i>SMS Kampanyası</h5>
                <p>Bakanlık listesindeki acentelere toplu SMS gönder</p>
            </div>
            <a href="{{ route('tursab.kampanya') }}" class="btn btn-sm btn-outline-light">← Kampanya Hub</a>
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

    {{-- FİLTRELER --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('kampanya.sms') }}" id="filterForm">
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
                            <input type="hidden" name="sadece_cep" value="0">
                            <input class="form-check-input" type="checkbox" name="sadece_cep" value="1" id="sadece_cep" @checked($sadeceCep)>
                            <label class="form-check-label small" for="sadece_cep">Sadece cep numaralı</label>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filtrele</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- SMS METNİ + ACENTE LİSTESİ --}}
    <form method="POST" action="{{ route('tursab.toplu-sms') }}" id="smsForm">
        @csrf

        <div class="sticky-action shadow-sm mb-3">
            <div class="container-fluid px-4">
                <div class="row align-items-start g-2">
                    <div class="col-auto align-self-center">
                        <input type="checkbox" id="selectAll" class="form-check-input" title="Tümünü seç">
                        <label for="selectAll" class="form-check-label small ms-1">Tümünü Seç</label>
                        <span class="badge bg-secondary ms-2" id="secilenSayi">0</span> seçildi
                    </div>
                    <div class="col-sm-5">
                        <textarea name="sms_mesaj" class="form-control form-control-sm" rows="2"
                            maxlength="160" id="smsMesaj"
                            placeholder="SMS metni (max 160 karakter)..."></textarea>
                        <div class="small text-muted mt-1">
                            <span id="smsChar">0</span>/160 karakter
                        </div>
                    </div>
                    <div class="col-auto align-self-start">
                        <button type="submit" class="btn btn-info btn-sm text-white" id="gonderBtn"
                            onclick="return onGonderClick()">
                            <i class="fas fa-sms me-1"></i>Seçilenlere SMS Gönder
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
                <select class="form-select form-select-sm" style="width:80px;"
                    onchange="window.location='{{ route('kampanya.sms') }}?{{ http_build_query(array_merge(request()->except(['per_page','page']), [])) }}&per_page='+this.value">
                    @foreach([25,50,100,200] as $pp)
                        <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
                    @endforeach
                </select>
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
                            <th>Telefon</th>
                            <th>E-posta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($acenteler as $a)
                            @php
                                $val = implode('||', [$a->eposta ?? '', $a->acente_unvani, $a->belge_no, $a->il, $a->telefon ?? '']);
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
                                <td style="font-size:0.78rem;" class="{{ $a->telefon ? '' : 'text-muted' }}">
                                    {{ $a->telefon ?: '—' }}
                                </td>
                                <td style="font-size:0.78rem;">{{ $a->eposta }}</td>
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

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ilSelect   = document.getElementById('ilSelect');
const ilceSelect = document.getElementById('ilceSelect');
const currentIlce = @json($ilce);

if (ilSelect) {
    ilSelect.addEventListener('change', function () { loadIlceler(this.value); });
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

// Karakter sayacı
const smsMesaj = document.getElementById('smsMesaj');
const smsChar  = document.getElementById('smsChar');
smsMesaj.addEventListener('input', function () { smsChar.textContent = this.value.length; });

// Checkbox logic
const selectAll = document.getElementById('selectAll');
const sayi      = document.getElementById('secilenSayi');

function updateCount() {
    sayi.textContent = document.querySelectorAll('.row-check:checked').length;
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
    const mesaj = smsMesaj.value.trim();
    if (!mesaj) { alert('SMS metni boş olamaz.'); return false; }
    return confirm(n + ' acenteye SMS gönderilecek. Onaylıyor musunuz?');
}
</script>
</body>
</html>

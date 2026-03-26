<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Davet Kampanyası — Süperadmin</title>
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
.tab-content { padding-top:1rem; }
.table th { font-size:0.72rem; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; }
.gecmis-sent   { color:#198754; }
.gecmis-failed { color:#dc3545; }
</style>
</head>
<body>

<x-navbar-superadmin active="tursab-kampanya" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-envelope-open-text me-2" style="color:#e94560;"></i>Davet Kampanyası</h5>
        <p>TÜRSAB listesindeki acentelere toplu davet e-postası gönder — günlük limit: 50</p>
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
        <div class="alert alert-danger alert-dismissible fade show py-2">
            {{ session('error') }}
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
                    <div class="fw-bold mb-1" style="font-size:0.85rem;">Davet Edilmemiş Acenteler</div>
                    <div style="font-size:2rem;font-weight:800;color:#e94560;">
                        {{ \App\Models\Acenteler::whereNotNull('eposta')->where('eposta','!=','')->count() - \App\Models\TursabDavet::distinct('eposta')->count('eposta') }}
                    </div>
                    <div class="small text-muted">e-posta adresi olan acentelerden</div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB'LAR --}}
    <ul class="nav nav-tabs" id="mainTab">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-gonder">
                <i class="fas fa-paper-plane me-1"></i>Gönder
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-gecmis">
                <i class="fas fa-history me-1"></i>Geçmiş
                <span class="badge bg-secondary ms-1">{{ $gecmis->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">

        {{-- GÖNDER TAB --}}
        <div class="tab-pane fade show active" id="tab-gonder">

            {{-- FİLTRE --}}
            <form method="GET" action="{{ route('superadmin.tursab.kampanya') }}" class="card shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Ara</label>
                            <input type="text" name="q" class="form-control form-control-sm"
                                   value="{{ $q }}" placeholder="Acente adı veya belge no...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">İl</label>
                            <select name="il" class="form-select form-select-sm">
                                <option value="">Tüm İller</option>
                                @foreach($iller as $i)
                                    <option value="{{ $i }}" {{ $il === $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Grup</label>
                            <select name="grup" class="form-select form-select-sm">
                                <option value="">Tümü</option>
                                <option value="A" {{ $grup==='A'?'selected':'' }}>A Grubu</option>
                                <option value="B" {{ $grup==='B'?'selected':'' }}>B Grubu</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="sadece_yeni" value="1" id="sadece_yeni"
                                       {{ $sadeceDavetEdilmemis ? 'checked' : '' }}>
                                <label class="form-check-label small" for="sadece_yeni">Davet edilmemişler</label>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-sm btn-dark w-100">Filtrele</button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- TOPLU GÖNDER FORMU --}}
            <form method="POST" action="{{ route('superadmin.tursab.toplu-davet') }}" id="davetForm">
                @csrf

                {{-- STICKY ACTION BAR --}}
                <div class="sticky-action mb-3 px-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="tumunuSec()">
                                <i class="fas fa-check-square me-1"></i>Tümünü Seç
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="secimiBozYap()">
                                <i class="fas fa-square me-1"></i>Seçimi Boz
                            </button>
                        </div>
                        <div class="fw-bold small" id="secimSayaci">0 acente seçili</div>
                        @if($kalanHak > 0)
                        <button type="submit" class="btn btn-sm ms-auto" style="background:#e94560;color:#fff;"
                                id="gonderBtn" onclick="return davetOnayla()">
                            <i class="fas fa-paper-plane me-1"></i>Seçililere Davet Gönder
                        </button>
                        @else
                        <span class="ms-auto text-danger small fw-bold">Günlük limit doldu</span>
                        @endif
                    </div>
                </div>

                {{-- LİSTE --}}
                <div class="card shadow-sm">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold">
                            {{ $acenteler->total() }} acente listelendi
                            (sayfa {{ $acenteler->currentPage() }}/{{ $acenteler->lastPage() }})
                        </span>
                        <span class="small text-muted">Sayfa başına 100 kayıt</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th width="30"><input type="checkbox" id="hepsiniSec" onchange="hepsiniToggle(this)"></th>
                                    <th>Acente Adı</th>
                                    <th>Belge No</th>
                                    <th>Grup</th>
                                    <th>İl / İlçe</th>
                                    <th>E-posta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($acenteler as $a)
                                <tr class="acente-row" id="row-{{ $a->id }}">
                                    <td>
                                        <input type="checkbox" name="secilen[]" class="acente-cb"
                                               value="{{ $a->eposta }}||{{ $a->acente_unvani }}||{{ $a->belge_no }}||{{ $a->il }}"
                                               onchange="secimGuncelle(this)">
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:0.82rem;">{{ $a->acente_unvani }}</div>
                                        @if($a->ticari_unvan && $a->ticari_unvan !== $a->acente_unvani)
                                            <div class="text-muted" style="font-size:0.72rem;">{{ Str::limit($a->ticari_unvan, 50) }}</div>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $a->belge_no }}</span></td>
                                    <td><span class="badge bg-primary">{{ $a->grup }}</span></td>
                                    <td><span class="badge-il">{{ $a->il_ilce ?: $a->il }}</span></td>
                                    <td style="font-size:0.8rem;">{{ $a->eposta }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Sonuç bulunamadı.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($acenteler->hasPages())
                    <div class="card-footer">
                        {{ $acenteler->links() }}
                    </div>
                    @endif
                </div>
            </form>
        </div>

        {{-- GEÇMİŞ TAB --}}
        <div class="tab-pane fade" id="tab-gecmis">
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <span class="small fw-bold">Son 200 davet kaydı</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Acente</th>
                                <th>E-posta</th>
                                <th>İl</th>
                                <th>Belge No</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gecmis as $d)
                            <tr>
                                <td class="text-nowrap">{{ $d->created_at->format('d.m.Y H:i') }}</td>
                                <td>{{ Str::limit($d->acente_unvani, 40) }}</td>
                                <td style="font-size:0.78rem;">{{ $d->eposta }}</td>
                                <td>{{ $d->il }}</td>
                                <td><span class="badge bg-secondary">{{ $d->belge_no }}</span></td>
                                <td>
                                    @if($d->status === 'sent')
                                        <span class="gecmis-sent"><i class="fas fa-check-circle me-1"></i>Gönderildi</span>
                                    @else
                                        <span class="gecmis-failed" title="{{ $d->hata }}">
                                            <i class="fas fa-times-circle me-1"></i>Hata
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Henüz davet gönderilmedi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- /tab-content --}}

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const KALAN_HAK = {{ $kalanHak }};

function secimGuncelle(cb) {
    const row = cb.closest('tr');
    row.classList.toggle('selected', cb.checked);
    const sayi = document.querySelectorAll('.acente-cb:checked').length;
    document.getElementById('secimSayaci').textContent = sayi + ' acente seçili';
}

function hepsiniToggle(master) {
    document.querySelectorAll('.acente-cb').forEach(cb => {
        cb.checked = master.checked;
        cb.closest('tr').classList.toggle('selected', master.checked);
    });
    const sayi = document.querySelectorAll('.acente-cb:checked').length;
    document.getElementById('secimSayaci').textContent = sayi + ' acente seçili';
}

function tumunuSec() {
    document.querySelectorAll('.acente-cb').forEach(cb => { cb.checked = true; cb.closest('tr').classList.add('selected'); });
    const sayi = document.querySelectorAll('.acente-cb:checked').length;
    document.getElementById('secimSayaci').textContent = sayi + ' acente seçili';
    document.getElementById('hepsiniSec').checked = true;
}

function secimiBozYap() {
    document.querySelectorAll('.acente-cb').forEach(cb => { cb.checked = false; cb.closest('tr').classList.remove('selected'); });
    document.getElementById('secimSayaci').textContent = '0 acente seçili';
    document.getElementById('hepsiniSec').checked = false;
}

function davetOnayla() {
    const sayi = document.querySelectorAll('.acente-cb:checked').length;
    if (sayi === 0) { alert('Lütfen en az bir acente seçin.'); return false; }

    const gonderilecek = Math.min(sayi, KALAN_HAK);
    const msg = sayi > KALAN_HAK
        ? `${sayi} acente seçtiniz ancak bugün sadece ${KALAN_HAK} email hakkınız var.\n\nİlk ${KALAN_HAK} acenteye davet gönderilecek. Devam edilsin mi?`
        : `${sayi} acenteye davet emaili gönderilecek. Devam edilsin mi?`;

    return confirm(msg);
}
</script>
</body>
</html>

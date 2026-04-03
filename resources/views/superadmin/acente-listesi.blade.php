<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acente Listesi — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.page-header p  { color:rgba(255,255,255,0.5); font-size:0.82rem; margin:0; }
.table th { font-size:0.72rem; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; }
.table td { font-size:0.82rem; vertical-align:middle; }
.badge-il { background:#e9ecef; color:#495057; font-size:0.7rem; padding:2px 7px; border-radius:10px; }
.st-ok  { color:#198754; font-weight:600; }
.st-no  { color:#adb5bd; }
.st-na  { color:#dee2e6; }
</style>
</head>
<body>

<x-navbar-superadmin active="kampanya-acenteler" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-list me-2" style="color:#e94560;"></i>Acente Listesi</h5>
                <p>Tüm acenteler — email ve SMS davet durumu ile</p>
            </div>
            <a href="{{ route('superadmin.tursab.kampanya') }}" class="btn btn-sm btn-outline-light">← Kampanya Hub</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    {{-- Filtreler --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('superadmin.kampanya.acenteler') }}" id="filterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Unvan / Belge No">
                    </div>
                    <div class="col-md-2">
                        <select name="il" class="form-select form-select-sm" id="ilSelect">
                            <option value="">Tüm İller</option>
                            @foreach($iller as $il_opt)
                                <option value="{{ $il_opt }}" @selected($il === $il_opt)>{{ $il_opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="ilce" class="form-select form-select-sm" id="ilceSelect">
                            <option value="">Tüm İlçeler</option>
                            @if($ilce)
                                <option value="{{ $ilce }}" selected>{{ $ilce }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="grup" class="form-select form-select-sm">
                            <option value="">Tümü</option>
                            <option value="A" @selected($grup === 'A')>A Grubu</option>
                            <option value="B" @selected($grup === 'B')>B Grubu</option>
                            <option value="C" @selected($grup === 'C')>C Grubu</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filtrele</button>
                    </div>
                    @if($q || $il || $ilce || $grup)
                    <div class="col-md-1">
                        <a href="{{ route('superadmin.kampanya.acenteler') }}" class="btn btn-outline-secondary btn-sm w-100">Temizle</a>
                    </div>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Tablo --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold" style="font-size:0.85rem;">
                Acenteler <span class="text-muted">({{ number_format($acenteler->total()) }} kayıt)</span>
            </span>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted me-1" style="font-size:0.75rem;">Sayfa başı:</span>
                <select class="form-select form-select-sm" style="width:80px;" onchange="window.location='{{ route('superadmin.kampanya.acenteler') }}?{{ http_build_query(array_merge(request()->except(['per_page','page']), [])) }}&per_page='+this.value">
                    @foreach([25,50,100,200] as $pp)
                        <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Belge No</th>
                        <th>Acente Unvanı</th>
                        <th>Grup</th>
                        <th>İl / İlçe</th>
                        <th>E-posta</th>
                        <th>Telefon</th>
                        <th class="text-center">Email Davet</th>
                        <th class="text-center">SMS Davet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($acenteler as $a)
                        @php
                            $eposta = strtolower(trim($a->eposta ?? ''));
                            $emailOk = $eposta && isset($emailDavetler[$eposta]);
                            $smsOk   = $eposta && isset($smsDavetler[$eposta]);
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $a->belge_no }}</td>
                            <td>{{ $a->acente_unvani }}</td>
                            <td>
                                @if($a->grup)
                                    <span class="badge bg-secondary">{{ $a->grup }}</span>
                                @endif
                            </td>
                            <td>
                                @if($a->il)
                                    <span class="badge-il">{{ $a->il }}</span>
                                    @if($a->il_ilce)
                                        <br><small class="text-muted">{{ $a->il_ilce }}</small>
                                    @endif
                                @endif
                            </td>
                            <td class="text-muted">{{ $a->eposta ?: '-' }}</td>
                            <td class="text-muted">{{ $a->telefon ?: '-' }}</td>
                            <td class="text-center">
                                @if(!$eposta)
                                    <span class="st-na" title="E-posta yok">—</span>
                                @elseif($emailOk)
                                    <span class="st-ok" title="Davet gönderildi"><i class="fas fa-check"></i></span>
                                @else
                                    <span class="st-no" title="Henüz gönderilmedi"><i class="fas fa-minus"></i></span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$eposta)
                                    <span class="st-na" title="E-posta yok">—</span>
                                @elseif($smsOk)
                                    <span class="st-ok" title="SMS gönderildi"><i class="fas fa-check"></i></span>
                                @else
                                    <span class="st-no" title="Henüz gönderilmedi"><i class="fas fa-minus"></i></span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
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

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('ilSelect').addEventListener('change', function() {
    const il = this.value;
    const ilceSelect = document.getElementById('ilceSelect');
    ilceSelect.innerHTML = '<option value="">Tüm İlçeler</option>';
    if (!il) return;
    fetch('{{ route('superadmin.tursab.ilceler') }}?il=' + encodeURIComponent(il))
        .then(r => r.json())
        .then(data => {
            data.forEach(ilce => {
                const opt = document.createElement('option');
                opt.value = ilce; opt.textContent = ilce;
                ilceSelect.appendChild(opt);
            });
        });
});
</script>
</body>
</html>

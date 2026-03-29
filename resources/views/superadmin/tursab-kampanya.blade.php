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
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-envelope-open-text me-2" style="color:#e94560;"></i>Davet Kampanyası</h5>
                <p>TÜRSAB listesindeki acentelere toplu davet e-postası gönder — günlük limit: 50</p>
            </div>
            <a href="{{ route('superadmin.acenteler.istatistik') }}" class="btn btn-sm btn-outline-light">
                <i class="fas fa-chart-bar me-1"></i>İstatistikler
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
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-scrape">
                <i class="fas fa-satellite-dish me-1"></i>Veri Güncelleme
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sync">
                <i class="fas fa-sync-alt me-1"></i>Senkronizasyon
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-manuel">
                <i class="fas fa-plus-circle me-1"></i>Manuel Ekle
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

        {{-- VERİ GÜNCELLEME TAB --}}
        <div class="tab-pane fade" id="tab-scrape">

            {{-- KAYNAK SEÇİCİ --}}
            <div class="d-flex gap-2 mb-3 align-items-center">
                <span class="small fw-bold text-muted me-1">Kaynak:</span>
                <button id="btnKaynakTursab" class="btn btn-sm btn-primary" onclick="kaynakSec('tursab')">
                    <i class="fas fa-flag me-1"></i>TÜRSAB
                </button>
                <button id="btnKaynakBakanlik" class="btn btn-sm btn-outline-secondary" onclick="kaynakSec('bakanlik')">
                    <i class="fas fa-landmark me-1"></i>Turizm Bakanlığı
                </button>
            </div>

            {{-- TÜRSAB PANEL --}}
            <div id="panelTursab" class="card shadow-sm">
                <div class="card-header py-2" style="background:#1a1a2e;color:#fff;">
                    <i class="fas fa-satellite-dish me-2" style="color:#e94560;"></i>
                    <span class="fw-bold">TÜRSAB Veri Güncelleme</span>
                    <span class="float-end small text-white-50">DB'de: <strong id="scrDbTotal" class="text-white">—</strong> kayıt</span>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
                        <span class="small">Durum: <strong id="scrStatus">—</strong></span>
                        <span class="small">Son belge no: <strong id="scrLastNo">—</strong></span>
                        <span class="small">Hedef: <strong id="scrEndNo">—</strong></span>
                        <span class="small">Toplam bulunan: <strong id="scrFound">—</strong></span>
                        <span class="small text-muted" id="scrAt"></span>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span id="scrProgressLabel">—</span>
                            <span id="scrPercent">—</span>
                        </div>
                        <div class="progress" style="height:16px;border-radius:8px;">
                            <div id="scrProgressBar" class="progress-bar" role="progressbar"
                                 style="width:0%;background:linear-gradient(90deg,#e94560,#c0392b);border-radius:8px;transition:width .4s;"></div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Başlangıç No</label>
                            <input type="number" id="scrStart" class="form-control form-control-sm" placeholder="Kaldığı yerden" min="1" max="99999">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Bitiş No</label>
                            <input type="number" id="scrEnd" class="form-control form-control-sm" value="18804" min="1" max="99999">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Batch (No/istek)</label>
                            <input type="number" id="scrBatch" class="form-control form-control-sm" value="50" min="1" max="200">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2 flex-wrap">
                            <div class="form-check mt-1 me-2">
                                <input class="form-check-input" type="checkbox" id="scrBeyond">
                                <label class="form-check-label small" for="scrBeyond">18804+ yeni tarama</label>
                            </div>
                            <button class="btn btn-sm btn-success" id="scrStartBtn" onclick="scrapeBaslat()">
                                <i class="fas fa-play me-1"></i>Başlat
                            </button>
                            <button class="btn btn-sm btn-danger d-none" id="scrStopBtn" onclick="scrapeDur()">
                                <i class="fas fa-stop me-1"></i>Durdur
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="scrapeSifirla()">
                                <i class="fas fa-redo me-1"></i>Sıfırla
                            </button>
                        </div>
                    </div>
                    <div id="scrLog" class="border rounded p-2" style="background:#f8f9fa;font-size:0.78rem;font-family:monospace;max-height:220px;overflow-y:auto;">
                        <span class="text-muted">Log burada görünecek…</span>
                    </div>
                </div>
            </div>

            {{-- BAKANLIK PANEL --}}
            <div id="panelBakanlik" class="card shadow-sm d-none">
                <div class="card-header py-2" style="background:#1a1a2e;color:#fff;">
                    <i class="fas fa-landmark me-2" style="color:#e8a020;"></i>
                    <span class="fw-bold">Turizm Bakanlığı Veri Güncelleme</span>
                    <span class="float-end small text-white-50">DB'de: <strong id="bkDbTotal" class="text-white">—</strong> kayıt</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Yalnızca <strong>GEÇERLİ</strong> belgeler indirilir. İptal olanlar atlanır.
                        Belge no bazlı tarama yapar — her batch N belge no işler.
                    </div>
                    <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
                        <span class="small">Durum: <strong id="bkStatus">—</strong></span>
                        <span class="small">Belge No: <strong id="bkCurrentNo">—</strong> / <strong id="bkEndNo">—</strong></span>
                        <span class="small">Toplam bulunan: <strong id="bkFound">—</strong></span>
                        <span class="small text-muted" id="bkAt"></span>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span id="bkProgressLabel">—</span>
                            <span id="bkPercent">—</span>
                        </div>
                        <div class="progress" style="height:16px;border-radius:8px;">
                            <div id="bkProgressBar" class="progress-bar" role="progressbar"
                                 style="width:0%;background:linear-gradient(90deg,#e8a020,#c07a10);border-radius:8px;transition:width .4s;"></div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Başlangıç No (boş = kaldığı yer)</label>
                            <input type="number" id="bkStartNo" class="form-control form-control-sm" placeholder="örn. 705" min="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Batch (No/istek)</label>
                            <input type="number" id="bkBatch" class="form-control form-control-sm" value="20" min="1" max="100">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2 flex-wrap">
                            <button class="btn btn-sm btn-success" id="bkStartBtn" onclick="bkBaslat()">
                                <i class="fas fa-play me-1"></i>Başlat
                            </button>
                            <button class="btn btn-sm btn-danger d-none" id="bkStopBtn" onclick="bkDur()">
                                <i class="fas fa-stop me-1"></i>Durdur
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="bkSifirla()">
                                <i class="fas fa-redo me-1"></i>Sıfırla
                            </button>
                        </div>
                    </div>
                    <div id="bkLog" class="border rounded p-2" style="background:#f8f9fa;font-size:0.78rem;font-family:monospace;max-height:220px;overflow-y:auto;">
                        <span class="text-muted">Log burada görünecek…</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- TAM SENKRONİZASYON TAB --}}
        <div class="tab-pane fade" id="tab-sync">
        <div class="card shadow-sm mt-2 border-primary">
            <div class="card-header py-2 bg-primary text-white">
                <strong><i class="fas fa-sync-alt me-1"></i>Bakanlık Tam Senkronizasyon</strong>
                <span class="small ms-2 opacity-75">GEÇERLİ + İPTAL — updateOrInsert — haftalık otomatik</span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3 text-center">
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-success" id="syncGecerli">—</div>
                        <div class="small text-muted">GEÇERLİ</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-danger" id="syncIptal">—</div>
                        <div class="small text-muted">İPTAL</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-warning" id="syncTursab">—</div>
                        <div class="small text-muted">TÜRSAB (silinecek)</div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span id="syncGecisLabel">—</span>
                        <span id="syncPercent">—</span>
                    </div>
                    <div class="progress" style="height:14px;border-radius:7px;">
                        <div id="syncProgressBar" class="progress-bar bg-primary" role="progressbar"
                             style="width:0%;border-radius:7px;transition:width .4s;"></div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <span class="small text-muted">Son: <span id="syncAt">—</span></span>
                    <span class="badge" id="syncStatusBadge">—</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" id="syncStartBtn" onclick="syncBaslat()">
                        <i class="fas fa-sync-alt me-1"></i>Şimdi Senkronize Et
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="syncDurdur()">
                        <i class="fas fa-pause me-1"></i>Durdur
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="syncDurumYukle()">
                        <i class="fas fa-refresh me-1"></i>Durum Yenile
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="syncSifirla()">
                        <i class="fas fa-redo me-1"></i>Baştan Başlat
                    </button>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="syncSkipCleanup">
                    <label class="form-check-label small text-muted" for="syncSkipCleanup">
                        TÜRSAB satırlarını silme (test için)
                    </label>
                </div>
            </div>
        </div>
        </div>{{-- /tab-sync --}}

        {{-- MANUEL EKLE TAB --}}
        <div class="tab-pane fade" id="tab-manuel">
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <strong><i class="fas fa-plus-circle me-1 text-primary"></i>El ile Acente Ekle / Güncelle</strong>
                    <span class="text-muted small ms-2">Belge no üzerinden: varsa günceller, yoksa yeni ekler.</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.tursab.manuel-ekle') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">TÜRSAB Belge No <span class="text-danger">*</span></label>
                                <input type="text" name="belge_no" class="form-control form-control-sm @error('belge_no') is-invalid @enderror"
                                       value="{{ old('belge_no') }}" placeholder="örn. 18801" required>
                                @error('belge_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Acente Ünvanı <span class="text-danger">*</span></label>
                                <input type="text" name="acente_unvani" class="form-control form-control-sm @error('acente_unvani') is-invalid @enderror"
                                       value="{{ old('acente_unvani') }}" placeholder="CTG ANCIENT MODERN TOUR" required>
                                @error('acente_unvani')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Grup</label>
                                <select name="grup" class="form-select form-select-sm">
                                    <option value="">—</option>
                                    <option value="A" {{ old('grup') === 'A' ? 'selected' : '' }}>A</option>
                                    <option value="B" {{ old('grup') === 'B' ? 'selected' : '' }}>B</option>
                                    <option value="C" {{ old('grup') === 'C' ? 'selected' : '' }}>C</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">İl</label>
                                <input type="text" name="il" class="form-control form-control-sm"
                                       value="{{ old('il') }}" placeholder="İstanbul">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Telefon</label>
                                <input type="text" name="telefon" class="form-control form-control-sm"
                                       value="{{ old('telefon') }}" placeholder="+90 212 000 00 00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">E-posta</label>
                                <input type="email" name="eposta" class="form-control form-control-sm @error('eposta') is-invalid @enderror"
                                       value="{{ old('eposta') }}" placeholder="info@acente.com">
                                @error('eposta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">BTK</label>
                                <input type="text" name="btk" class="form-control form-control-sm"
                                       value="{{ old('btk') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Adres</label>
                                <input type="text" name="adres" class="form-control form-control-sm"
                                       value="{{ old('adres') }}" placeholder="Tam adres (isteğe bağlı)">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-save me-1"></i>Kaydet
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>{{-- /tab-content --}}

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const KALAN_HAK = {{ $kalanHak }};

// Manuel Ekle sekmesinde hata varsa otomatik aç
@if($errors->any() && old('belge_no') !== null)
document.addEventListener('DOMContentLoaded', function() {
    var tab = new bootstrap.Tab(document.querySelector('[data-bs-target="#tab-manuel"]'));
    tab.show();
});
@endif

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

/* ── TÜRSAB Scraper ─────────────────────────────────────── */
const SCRAPE_URL   = '{{ route("superadmin.tursab.scrape.start") }}';
const STATUS_URL   = '{{ route("superadmin.tursab.scrape.status") }}';
const CSRF_TOKEN   = document.querySelector('meta[name="csrf-token"]').content;
let   scrRunning   = false;
let   scrStopFlag  = false;

document.addEventListener('DOMContentLoaded', () => scrapeStatusYukle());

function scrapeStatusYukle() {
    fetch(STATUS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json()).then(d => scrapeGoster(d));
}

function scrapeGoster(d) {
    const statusMap = { running:'Çalışıyor', idle:'Hazır', paused:'Duraklatıldı', error:'Hata' };
    document.getElementById('scrStatus').textContent   = statusMap[d.status] || d.status;
    document.getElementById('scrStatus').className     = d.status === 'running' ? 'text-success' : (d.status === 'error' ? 'text-danger' : 'text-secondary');
    document.getElementById('scrLastNo').textContent   = d.last_no || '—';
    document.getElementById('scrEndNo').textContent    = d.end_no  || '—';
    document.getElementById('scrFound').textContent    = d.found   || '0';
    document.getElementById('scrDbTotal').textContent  = d.db_total || '—';
    document.getElementById('scrAt').textContent       = d.at ? 'Son çalışma: ' + d.at : '';
    const pct = d.percent || 0;
    document.getElementById('scrProgressBar').style.width = pct + '%';
    document.getElementById('scrPercent').textContent  = pct + '%';
    document.getElementById('scrProgressLabel').textContent = (d.last_no || 0) + ' / ' + (d.end_no || '?') + ' belge no işlendi';
}

function scrapeLog(msg, cls) {
    const log = document.getElementById('scrLog');
    const line = document.createElement('div');
    line.className = cls || '';
    line.textContent = new Date().toLocaleTimeString('tr') + ' — ' + msg;
    log.appendChild(line);
    log.scrollTop = log.scrollHeight;
}

async function scrapeBaslat() {
    if (scrRunning) return;
    scrRunning  = true;
    scrStopFlag = false;
    document.getElementById('scrStartBtn').classList.add('d-none');
    document.getElementById('scrStopBtn').classList.remove('d-none');

    const startVal  = document.getElementById('scrStart').value;
    const endVal    = document.getElementById('scrEnd').value;
    const batchVal  = document.getElementById('scrBatch').value;
    const beyondVal = document.getElementById('scrBeyond').checked;

    document.getElementById('scrLog').innerHTML = '';
    scrapeLog('Tarama başlatıldı…');

    let ilkIstek = true;

    while (!scrStopFlag) {
        const body = new URLSearchParams({
            _token: CSRF_TOKEN,
            end:    endVal,
            batch:  batchVal,
        });
        if (ilkIstek && startVal) body.append('start', startVal);
        if (beyondVal)             body.append('beyond', '1');
        ilkIstek = false;

        try {
            const res  = await fetch(SCRAPE_URL, { method: 'POST', body });
            if (!res.ok) { scrapeLog('HTTP hatası: ' + res.status, 'text-danger'); break; }
            const d    = await res.json();
            scrapeGoster(d);
            scrapeLog('Batch bitti — Son no: ' + d.last_no + ' | Bulunan: ' + d.found + ' | DB: ' + d.db_total);

            if (d.done || d.status === 'idle') {
                scrapeLog('Tarama tamamlandı.', 'text-success fw-bold');
                break;
            }
        } catch (e) {
            scrapeLog('İstek hatası: ' + e.message, 'text-danger');
            break;
        }

        await new Promise(r => setTimeout(r, 600)); // kısa nefes
    }

    scrRunning = false;
    document.getElementById('scrStartBtn').classList.remove('d-none');
    document.getElementById('scrStopBtn').classList.add('d-none');
    if (scrStopFlag) scrapeLog('Kullanıcı tarafından durduruldu.', 'text-warning');
}

function scrapeDur() {
    scrStopFlag = true;
    scrapeLog('Durdurma isteği gönderildi…', 'text-warning');
}

function scrapeSifirla() {
    if (!confirm('İlerleme sıfırlansın mı? (Veri silinmez, sadece sayaç sıfırlanır)')) return;
    fetch(SCRAPE_URL, {
        method: 'POST',
        body: new URLSearchParams({ _token: CSRF_TOKEN, reset: '1', batch: '1' })
    }).then(r => r.json()).then(d => { scrapeGoster(d); scrapeLog('İlerleme sıfırlandı.', 'text-warning'); });
}

// ── KAYNAK SEÇİCİ ────────────────────────────────────────────────────────
function kaynakSec(kaynak) {
    const isTursab = kaynak === 'tursab';
    document.getElementById('panelTursab').classList.toggle('d-none', !isTursab);
    document.getElementById('panelBakanlik').classList.toggle('d-none', isTursab);
    document.getElementById('btnKaynakTursab').className   = isTursab ? 'btn btn-sm btn-primary'          : 'btn btn-sm btn-outline-secondary';
    document.getElementById('btnKaynakBakanlik').className = isTursab ? 'btn btn-sm btn-outline-secondary' : 'btn btn-sm btn-warning';
    if (!isTursab) bkStatusYukle();
}

// ── TAM SENKRONİZASYON ───────────────────────────────────────────────────
const SYNC_START_URL  = '/superadmin/acente-sync-start';
const SYNC_STATUS_URL = '/superadmin/acente-sync-status';
let syncPolling = null;

async function syncDurumYukle() {
    try {
        const res = await fetch(SYNC_STATUS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const d   = await res.json();
        syncGoster(d);
    } catch(e) {}
}

function syncGoster(d) {
    document.getElementById('syncGecerli').textContent  = (d.gecerli_count ?? '—').toLocaleString();
    document.getElementById('syncIptal').textContent    = (d.iptal_count   ?? '—').toLocaleString();
    document.getElementById('syncTursab').textContent   = (d.tursab_count  ?? '—').toLocaleString();
    document.getElementById('syncGecisLabel').textContent = d.gecis_label ?? '—';
    document.getElementById('syncPercent').textContent  = (d.percent ?? 0) + '%';
    document.getElementById('syncProgressBar').style.width = (d.percent ?? 0) + '%';
    document.getElementById('syncAt').textContent       = d.at || '—';

    const badge = document.getElementById('syncStatusBadge');
    const statusMap = {
        idle: ['secondary', 'Bekliyor'],
        running: ['primary', 'Çalışıyor…'],
        paused: ['warning', 'Duraklatıldı'],
        done: ['success', '✅ Tamamlandı'],
        error: ['danger', '❌ Hata'],
    };
    const [cls, lbl] = statusMap[d.status] || ['secondary', d.status];
    badge.className = `badge bg-${cls}`;
    badge.textContent = lbl;

    const btn = document.getElementById('syncStartBtn');
    if (d.status === 'running') {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Çalışıyor…';
        if (!syncPolling) syncPolling = setInterval(syncDurumYukle, 4000);
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>' + (d.status === 'paused' ? 'Devam Et' : 'Şimdi Senkronize Et');
        if (syncPolling) { clearInterval(syncPolling); syncPolling = null; }
    }
}

let syncDurdurildi = false;

async function syncBaslat(reset = false) {
    syncDurdurildi = false;
    const btn = document.getElementById('syncStartBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Çalışıyor…';

    while (!syncDurdurildi) {
        const body = new FormData();
        body.append('_token', CSRF_TOKEN);
        body.append('batch', '30');
        if (reset) { body.append('reset', '1'); reset = false; }
        if (document.getElementById('syncSkipCleanup').checked) body.append('skip_cleanup', '1');

        try {
            const res = await fetch(SYNC_START_URL, { method: 'POST', body });
            const d   = await res.json();
            syncGoster(d);

            if (d.status === 'done' || d.status === 'error') break;
            // Geçiş 1 bitti, geçiş 2'ye devam
            // paused da olsa devam et — kaldığı yerden sürer
            await new Promise(r => setTimeout(r, 800)); // sunucuyu soluklandır
        } catch(e) {
            document.getElementById('syncStatusBadge').textContent = 'Bağlantı hatası';
            break;
        }
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Şimdi Senkronize Et';
}

function syncDurdur() {
    syncDurdurildi = true;
}

async function syncSifirla() {
    if (!confirm('Senkronizasyon baştan başlayacak. Emin misiniz?')) return;
    await syncBaslat(true);
}

document.addEventListener('DOMContentLoaded', syncDurumYukle);

// ── BAKANLIK SCRAPER ─────────────────────────────────────────────────────
const BK_SCRAPE_URL  = '{{ route("superadmin.bakanlik.scrape.start") }}';
const BK_STATUS_URL  = '{{ route("superadmin.bakanlik.scrape.status") }}';
let   bkRunning      = false;
let   bkStopFlag     = false;

function bkStatusYukle() {
    fetch(BK_STATUS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json()).then(d => bkGoster(d));
}

function bkGoster(d) {
    const statusMap = { running:'Çalışıyor', idle:'Hazır', paused:'Duraklatıldı', error:'Hata' };
    document.getElementById('bkStatus').textContent    = statusMap[d.status] || d.status;
    document.getElementById('bkStatus').className      = d.status === 'running' ? 'text-success' : (d.status === 'error' ? 'text-danger' : 'text-secondary');
    document.getElementById('bkCurrentNo').textContent = d.current_no || '1';
    document.getElementById('bkEndNo').textContent     = d.end_no    || '—';
    document.getElementById('bkFound').textContent     = d.found     || '0';
    document.getElementById('bkDbTotal').textContent   = d.db_total  || '—';
    document.getElementById('bkAt').textContent        = d.at ? 'Son çalışma: ' + d.at : '';
    const pct = d.percent || 0;
    document.getElementById('bkProgressBar').style.width = pct + '%';
    document.getElementById('bkPercent').textContent   = pct + '%';
    document.getElementById('bkProgressLabel').textContent = (d.current_no || 1) + ' / ' + (d.end_no || '?') + ' belge no tarandı';
}

function bkLog(msg, cls) {
    const log = document.getElementById('bkLog');
    const line = document.createElement('div');
    line.className = cls || '';
    line.textContent = new Date().toLocaleTimeString('tr') + ' — ' + msg;
    log.appendChild(line);
    log.scrollTop = log.scrollHeight;
}

async function bkBaslat() {
    if (bkRunning) return;
    bkRunning  = true;
    bkStopFlag = false;
    document.getElementById('bkStartBtn').classList.add('d-none');
    document.getElementById('bkStopBtn').classList.remove('d-none');

    const batchVal  = document.getElementById('bkBatch').value;
    const startVal  = document.getElementById('bkStartNo').value;
    document.getElementById('bkLog').innerHTML = '';
    bkLog('Tarama başlatıldı… (her istek ' + batchVal + ' belge no işler)');

    let firstReq = true;
    while (!bkStopFlag) {
        try {
            const params = { _token: CSRF_TOKEN, batch: batchVal };
            if (firstReq && startVal) params.start = startVal;
            firstReq = false;
            const res = await fetch(BK_SCRAPE_URL, {
                method: 'POST',
                body: new URLSearchParams(params)
            });
            if (!res.ok) { bkLog('HTTP hatası: ' + res.status, 'text-danger'); break; }
            const d = await res.json();
            bkGoster(d);
            bkLog('Batch bitti — No: ' + d.current_no + '/' + d.end_no + ' | Bulunan: ' + d.found + ' | DB: ' + d.db_total);

            if (d.done || d.status === 'idle') {
                bkLog('Tarama tamamlandı.', 'text-success fw-bold');
                break;
            }
        } catch (e) {
            bkLog('İstek hatası: ' + e.message, 'text-danger');
            break;
        }

        await new Promise(r => setTimeout(r, 800));
    }

    bkRunning = false;
    document.getElementById('bkStartBtn').classList.remove('d-none');
    document.getElementById('bkStopBtn').classList.add('d-none');
    if (bkStopFlag) bkLog('Kullanıcı tarafından durduruldu.', 'text-warning');
}

function bkDur() {
    bkStopFlag = true;
    bkLog('Durdurma isteği gönderildi…', 'text-warning');
}

function bkSifirla() {
    const startVal = document.getElementById('bkStartNo').value || '1';
    if (!confirm('Tarama sıfırlansın mı? (' + startVal + '. belge nodan baştan tarar, veri silinmez)')) return;
    fetch(BK_SCRAPE_URL, {
        method: 'POST',
        body: new URLSearchParams({ _token: CSRF_TOKEN, reset: '1', start: startVal, batch: '1' })
    }).then(r => r.json()).then(d => { bkGoster(d); bkLog('Sıfırlandı — ' + startVal + "'den başlayacak.", 'text-warning'); });
}
</script>
</body>
</html>

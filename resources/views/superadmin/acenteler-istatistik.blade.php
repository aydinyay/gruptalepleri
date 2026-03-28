<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acente İstatistikleri — GrupTalepleri</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
.page-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: #fff; padding: 28px 32px 24px;
}
.page-header h1 { font-size: 1.6rem; font-weight: 700; margin: 0; }
.page-header p { margin: 4px 0 0; color: rgba(255,255,255,.65); font-size: .9rem; }
.kpi-card {
    background: #fff; border-radius: 12px; padding: 20px 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
    border-left: 4px solid #e94560;
    height: 100%;
}
.kpi-card .kpi-icon { font-size: 2rem; margin-bottom: 8px; }
.kpi-card .kpi-val  { font-size: 2.2rem; font-weight: 800; line-height: 1; }
.kpi-card .kpi-label { font-size: .78rem; text-transform: uppercase; letter-spacing: .06em; color: #6c757d; margin-top: 4px; }
.kpi-card .kpi-sub  { font-size: .82rem; color: #6c757d; margin-top: 6px; }
.section-card {
    background: #fff; border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
    margin-bottom: 24px;
}
.section-card .card-header-custom {
    padding: 16px 20px 12px;
    border-bottom: 1px solid #f0f2f5;
    font-weight: 700; font-size: 1rem;
}
.section-card .card-body-custom { padding: 20px; }
.chart-wrap { position: relative; }
.insight-box {
    background: #f8f9fa; border-radius: 8px;
    padding: 12px 16px; font-size: .83rem;
    color: #495057; margin-top: 14px;
    border-left: 3px solid #0d6efd;
}
.insight-box strong { color: #1a1a2e; }
.table-sm td, .table-sm th { font-size: .82rem; vertical-align: middle; }
.pct-bar { height: 6px; border-radius: 3px; background: #dee2e6; }
.pct-fill { height: 100%; border-radius: 3px; background: #e94560; }
.badge-kaynak-tursab    { background: #0d6efd22; color: #0d6efd; border-radius: 6px; padding: 2px 8px; font-size:.75rem; }
.badge-kaynak-bakanlik  { background: #19875422; color: #198754; border-radius: 6px; padding: 2px 8px; font-size:.75rem; }
.badge-kaynak-manuel    { background: #6f42c122; color: #6f42c1; border-radius: 6px; padding: 2px 8px; font-size:.75rem; }
</style>
</head>
<body>

{{-- HEADER --}}
<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1><i class="fas fa-chart-bar me-2" style="color:#e94560;"></i>Acente İstatistikleri</h1>
                <p>TÜRSAB &amp; Turizm Bakanlığı verisi — toplam {{ number_format($toplam) }} kayıt</p>
            </div>
            <a href="{{ route('superadmin.tursab.kampanya') }}" class="btn btn-sm btn-outline-light">
                <i class="fas fa-arrow-left me-1"></i>Kampanya Sayfası
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 py-4">

{{-- ── 1. KPI KARTLARI ───────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @php
        $epostaPct  = $toplam ? round($epostaVar  / $toplam * 100, 1) : 0;
        $telefonPct = $toplam ? round($telefonVar / $toplam * 100, 1) : 0;
        $subePct    = $toplam ? round($subeCount  / $toplam * 100, 1) : 0;

        $tursabRow   = $kaynaklar->firstWhere('kaynak', 'tursab');
        $bakanlikRow = $kaynaklar->firstWhere('kaynak', 'bakanlik');
        $manuelRow   = $kaynaklar->firstWhere('kaynak', 'manuel');
    @endphp

    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card" style="border-color:#e94560;">
            <div class="kpi-icon" style="color:#e94560;"><i class="fas fa-building"></i></div>
            <div class="kpi-val">{{ number_format($toplam) }}</div>
            <div class="kpi-label">Toplam Acente</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card" style="border-color:#0d6efd;">
            <div class="kpi-icon" style="color:#0d6efd;"><i class="fas fa-flag"></i></div>
            <div class="kpi-val">{{ number_format($tursabRow?->toplam ?? 0) }}</div>
            <div class="kpi-label">TÜRSAB</div>
            <div class="kpi-sub">{{ $toplam ? round(($tursabRow?->toplam ?? 0) / $toplam * 100, 1) : 0 }}% toplam</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card" style="border-color:#198754;">
            <div class="kpi-icon" style="color:#198754;"><i class="fas fa-landmark"></i></div>
            <div class="kpi-val">{{ number_format($bakanlikRow?->toplam ?? 0) }}</div>
            <div class="kpi-label">Bakanlık</div>
            <div class="kpi-sub">{{ $toplam ? round(($bakanlikRow?->toplam ?? 0) / $toplam * 100, 1) : 0 }}% toplam</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card" style="border-color:#fd7e14;">
            <div class="kpi-icon" style="color:#fd7e14;"><i class="fas fa-envelope"></i></div>
            <div class="kpi-val">{{ number_format($epostaVar) }}</div>
            <div class="kpi-label">E-posta Var</div>
            <div class="kpi-sub">{{ $epostaPct }}% kapsam</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card" style="border-color:#6f42c1;">
            <div class="kpi-icon" style="color:#6f42c1;"><i class="fas fa-phone"></i></div>
            <div class="kpi-val">{{ number_format($telefonVar) }}</div>
            <div class="kpi-label">Telefon Var</div>
            <div class="kpi-sub">{{ $telefonPct }}% kapsam</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="kpi-card" style="border-color:#20c997;">
            <div class="kpi-icon" style="color:#20c997;"><i class="fas fa-code-branch"></i></div>
            <div class="kpi-val">{{ number_format($subeCount) }}</div>
            <div class="kpi-label">Şube</div>
            <div class="kpi-sub">{{ number_format($anaMerkez) }} ana merkez</div>
        </div>
    </div>
</div>

{{-- ── 2. KAYNAK DAĞILIMI ────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-database me-2 text-primary"></i>Kaynak Dağılımı</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:220px;">
                    <canvas id="chartKaynak"></canvas>
                </div>
                <div class="insight-box mt-3">
                    @if($tursabRow && $bakanlikRow)
                        <strong>Çakışma analizi:</strong>
                        TÜRSAB {{ number_format($tursabRow->toplam) }} acente, Bakanlık {{ number_format($bakanlikRow->toplam) }} acente içeriyor.
                        İki kaynak birbirini tamamlar — TÜRSAB üye belgeli, Bakanlık lisanslı firmaları kapsar.
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-table me-2 text-primary"></i>Kaynak Bazında Veri Tamlığı</div>
            <div class="card-body-custom p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kaynak</th>
                            <th class="text-end">Toplam</th>
                            <th class="text-end">E-posta</th>
                            <th class="text-end">Telefon</th>
                            <th class="text-end">Adres</th>
                            <th class="text-end">İl</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kaynaklar as $row)
                        <tr>
                            <td>
                                <span class="badge-kaynak-{{ $row->kaynak }}">{{ strtoupper($row->kaynak) }}</span>
                            </td>
                            <td class="text-end fw-bold">{{ number_format($row->toplam) }}</td>
                            <td class="text-end">
                                {{ number_format($row->eposta_var) }}
                                <small class="text-muted">({{ $row->toplam ? round($row->eposta_var/$row->toplam*100) : 0 }}%)</small>
                            </td>
                            <td class="text-end">
                                {{ number_format($row->telefon_var) }}
                                <small class="text-muted">({{ $row->toplam ? round($row->telefon_var/$row->toplam*100) : 0 }}%)</small>
                            </td>
                            <td class="text-end">
                                {{ number_format($row->adres_var) }}
                                <small class="text-muted">({{ $row->toplam ? round($row->adres_var/$row->toplam*100) : 0 }}%)</small>
                            </td>
                            <td class="text-end">
                                {{ number_format($row->il_var) }}
                                <small class="text-muted">({{ $row->toplam ? round($row->il_var/$row->toplam*100) : 0 }}%)</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── 3. İL DAĞILIMI ───────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-map-marker-alt me-2 text-danger"></i>İl Bazlı Dağılım (Top 20)</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:420px;">
                    <canvas id="chartIl"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-city me-2 text-danger"></i>İstanbul / Ankara / İzmir</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:200px;">
                    <canvas id="chartBuyukSehir"></canvas>
                </div>
                <div class="insight-box mt-3">
                    @php $buyuk3Pct = $toplam ? round($buyuk3Toplam / $toplam * 100, 1) : 0; @endphp
                    <strong>3 büyük şehir</strong> tüm acentelerin <strong>{{ $buyuk3Pct }}%</strong>'ini barındırıyor
                    ({{ number_format($buyuk3Toplam) }} acente). Geri kalan {{ number_format($digerIlToplam) }} acente
                    diğer {{ $ilDagilim->count() - 3 }}+ ilde dağılmış durumda.
                </div>
                <hr>
                <div class="small">
                    @foreach($ilDagilim->take(10) as $il)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>{{ $il->il }}</span>
                        <span class="fw-bold">{{ number_format($il->toplam) }}</span>
                    </div>
                    <div class="pct-bar mb-2">
                        <div class="pct-fill" style="width:{{ $ilDagilim->first()->toplam ? round($il->toplam/$ilDagilim->first()->toplam*100) : 0 }}%;"></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 4. GRUP DAĞILIMI ─────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-layer-group me-2 text-warning"></i>TÜRSAB Grup Dağılımı</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:220px;">
                    <canvas id="chartGrup"></canvas>
                </div>
                <div class="insight-box mt-3">
                    <strong>TÜRSAB Grupları:</strong><br>
                    <span class="text-muted">A</span> = Uluslararası &amp; yurt içi &nbsp;
                    <span class="text-muted">B</span> = Yurt içi &nbsp;
                    <span class="text-muted">C</span> = Incoming &nbsp;
                    <span class="text-muted">D</span> = Kruvaziyer
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-envelope-open-text me-2 text-warning"></i>Gruba Göre E-posta Kapsamı</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:260px;">
                    <canvas id="chartGrupEposta"></canvas>
                </div>
                <div class="insight-box mt-3">
                    @php
                        $enIyiGrup = $grupDagilim->filter(fn($g) => $g->toplam >= 20)->sortByDesc(fn($g) => $g->toplam ? $g->eposta_var/$g->toplam : 0)->first();
                    @endphp
                    @if($enIyiGrup)
                    <strong>En yüksek e-posta oranı:</strong> Grup {{ $enIyiGrup->grup }} —
                    {{ $enIyiGrup->toplam ? round($enIyiGrup->eposta_var/$enIyiGrup->toplam*100, 1) : 0 }}%
                    ({{ number_format($enIyiGrup->eposta_var) }} / {{ number_format($enIyiGrup->toplam) }})
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 5. E-POSTA ANALİZİ ───────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-at me-2 text-success"></i>İl Bazında E-posta Doluluk Oranı (en yüksek 20)</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:360px;">
                    <canvas id="chartIlEposta"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-paper-plane me-2 text-success"></i>Davet Kampanyası</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:200px;">
                    <canvas id="chartDavet"></canvas>
                </div>
                <div class="row g-2 mt-3 text-center">
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-primary">{{ number_format($epostaVar) }}</div>
                        <div class="small text-muted">E-posta var</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-success">{{ number_format($davetBasarili) }}</div>
                        <div class="small text-muted">Davet gönderildi</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-danger">{{ number_format($epostaVar - $davetBasarili) }}</div>
                        <div class="small text-muted">Henüz davet edilmedi</div>
                    </div>
                </div>
                <div class="insight-box mt-3">
                    @php $davetPct = $epostaVar ? round($davetBasarili / $epostaVar * 100, 1) : 0; @endphp
                    E-posta olan acentelerin yalnızca <strong>{{ $davetPct }}%</strong>'ine davet gönderildi.
                    Kalan <strong>{{ number_format($epostaVar - $davetBasarili) }}</strong> acente hala bekliyor.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 6. VERİ KALİTESİ RADAR ──────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-spider me-2" style="color:#6f42c1;"></i>Veri Kalitesi — Kaynak Karşılaştırması</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:280px;">
                    <canvas id="chartRadar"></canvas>
                </div>
                <div class="insight-box mt-3">
                    Her alan için kaynaklara göre doluluk yüzdesi gösterilmektedir.
                    Yüksek alan → o kaynaktan daha kaliteli veri demek.
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-th me-2" style="color:#6f42c1;"></i>Veri Kalitesi Tablosu (%)</div>
            <div class="card-body-custom p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kaynak</th>
                            <th class="text-center">E-posta</th>
                            <th class="text-center">Telefon</th>
                            <th class="text-center">Adres</th>
                            <th class="text-center">İl</th>
                            <th class="text-center">Grup</th>
                            <th class="text-center">Ticari Ünvan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($veriKalitesi as $vk)
                        <tr>
                            <td><span class="badge-kaynak-{{ $vk->kaynak }}">{{ strtoupper($vk->kaynak) }}</span></td>
                            @foreach(['eposta_pct','telefon_pct','adres_pct','il_pct','grup_pct','ticari_pct'] as $field)
                            <td class="text-center">
                                @php $val = $vk->$field ?? 0; @endphp
                                <span class="badge" style="background:{{ $val >= 70 ? '#19875420' : ($val >= 40 ? '#ffc10720' : '#dc354520') }};
                                    color:{{ $val >= 70 ? '#198754' : ($val >= 40 ? '#856404' : '#dc3545') }};">
                                    {{ $val }}%
                                </span>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-3">
                    <span class="badge me-1" style="background:#19875420;color:#198754;">≥70% İyi</span>
                    <span class="badge me-1" style="background:#ffc10720;color:#856404;">40-69% Orta</span>
                    <span class="badge" style="background:#dc354520;color:#dc3545;">&lt;40% Eksik</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 7. BELGE NO HİSTOGRAM ────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-hashtag me-2 text-info"></i>Belge No Aralığına Göre Dağılım</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:260px;">
                    <canvas id="chartHistogram"></canvas>
                </div>
                <div class="insight-box mt-3">
                    Her çubuk 1.000 belge no aralığını temsil eder.
                    Yüksek yoğunluklu aralıklar o dönemde çok sayıda acente kurulduğuna işaret eder.
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-code-branch me-2 text-info"></i>Şube Sayısına Göre Dağılım</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:200px;">
                    <canvas id="chartSube"></canvas>
                </div>
                <div class="insight-box mt-3">
                    Şube sırası 0 olan acenteler tek ofisli firmalar.
                    Yüksek şube sırasına sahip firmalar ulusal zincirler.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 7b. TARİHİ BÜYÜME ────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="section-card">
            <div class="card-header-custom"><i class="fas fa-chart-line me-2" style="color:#0d6efd;"></i>Türkiye Seyahat Acentası Sayısı — Yıllara Göre Büyüme (2000–2023)</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:280px;">
                    <canvas id="chartTarihiBuyume"></canvas>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="insight-box" style="border-color:#198754;">
                            <strong>23 yılda %284 büyüme</strong> — 2000'de 4.077 olan acente sayısı 2023'te 15.678'e ulaştı
                            (2023/2000 oranı: %384,54). Yıllık ortalama ~490 acente artış.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="insight-box" style="border-color:#ffc107;">
                            <strong>Rekor yıl 2022:</strong> Tek yılda +1.856 acente (12.649→14.505).
                            Pandemi sonrası toparlanma ve turizm patlamasının yansıması.
                            Kaynak: TÜRSAB + turizmgazetesi.com (19 Şubat 2024)
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="insight-box" style="border-color:#e94560;">
                            <strong>Avrupa'nın tersi:</strong> Aynı dönemde 26 Avrupa ülkesinde toplam acente sayısı
                            107.208'den 78.743'e düştü (−30.465). Türkiye ise 4.825'ten 12.649'a çıktı (+7.824).
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── 8b. AVRUPA KARŞILAŞTIRMASI ──────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-globe-europe me-2" style="color:#0d6efd;"></i>Türkiye vs Avrupa — Acente Sayısı (2005→2021)</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:420px;">
                    <canvas id="chartAvrupa"></canvas>
                </div>
                <div class="insight-box mt-3" style="border-color:#198754;">
                    <strong>Türkiye tek büyüyen ülke:</strong> 26 Avrupa ülkesinde toplam acente sayısı
                    107.208'den 78.743'e geriledi (−%26). Türkiye bu tabloda tek pozitif değişim gösteren ülke:
                    4.825 → 12.649 (+%162). Kaynak: ECTAA / turizmgazetesi.com, 2024
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-table me-2" style="color:#0d6efd;"></i>Ülke Karşılaştırma Tablosu</div>
            <div class="card-body-custom p-0" style="max-height:520px;overflow-y:auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>Ülke</th>
                            <th class="text-end">2005</th>
                            <th class="text-end">2021</th>
                            <th class="text-end">Değişim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $avrupaVerisi = [
                            ['İtalya',      13981, 11124],
                            ['İspanya',     13757,  8373],
                            ['Almanya',     10181,  8829],
                            ['Fransa',       8840,  5020],
                            ['İngiltere',    9212,  7819],
                            ['Polonya',      7451,  5373],
                            ['Hollanda',     6482,  2579],
                            ['Çek Cum.',     5728,  6515],
                            ['Portekiz',     4116,  2210],
                            ['Yunanistan',   3783,  3277],
                            ['İsveç',        3212,  2798],
                            ['Romanya',      3114,  1990],
                            ['Macaristan',   2523,  1813],
                            ['Finlandiya',   2321,  1040],
                            ['Avusturya',    2263,  1515],
                            ['Belçika',      1820,  1553],
                            ['Bulgaristan',  1685,  1367],
                            ['Slovakya',     1357,   426],
                            ['İrlanda',      1180,   346],
                            ['Litvanya',     1032,   635],
                            ['Slovenya',      917,   404],
                            ['Letonya',       715,   358],
                            ['Danimarka',     571,   633],
                            ['K.Kıbrıs',      443,   508],
                            ['Estonya',       439,   310],
                            ['Lüksemburg',     85,    99],
                            ['🇹🇷 TÜRKİYE',  4825, 12649],
                        ];
                        usort($avrupaVerisi, fn($a,$b) => ($b[2]-$b[1]) <=> ($a[2]-$a[1]));
                        @endphp
                        @foreach($avrupaVerisi as $row)
                        @php
                            $degisim = $row[2] - $row[1];
                            $pct = $row[1] > 0 ? round($degisim / $row[1] * 100, 1) : 0;
                            $isTurkiye = str_contains($row[0], 'TÜRKİYE');
                        @endphp
                        <tr @if($isTurkiye) style="background:#19875415;font-weight:700;" @endif>
                            <td>{{ $row[0] }}</td>
                            <td class="text-end">{{ number_format($row[1]) }}</td>
                            <td class="text-end">{{ number_format($row[2]) }}</td>
                            <td class="text-end">
                                <span style="color:{{ $degisim >= 0 ? '#198754' : '#dc3545' }};">
                                    {{ $degisim >= 0 ? '+' : '' }}{{ number_format($degisim) }}
                                    <small>({{ $pct >= 0 ? '+' : '' }}{{ $pct }}%)</small>
                                </span>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="table-dark fw-bold">
                            <td>26 Ülke Toplam</td>
                            <td class="text-end">107.208</td>
                            <td class="text-end">78.743</td>
                            <td class="text-end text-danger">−28.465</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── 8. İLÇE DAĞILIMI ─────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-map-pin me-2 text-secondary"></i>En Fazla Acenteli 15 İlçe</div>
            <div class="card-body-custom">
                <div class="chart-wrap" style="height:340px;">
                    <canvas id="chartIlce"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="section-card h-100">
            <div class="card-header-custom"><i class="fas fa-sitemap me-2 text-secondary"></i>Top 5 İl → İlçe Dağılımı</div>
            <div class="card-body-custom p-0">
                <div class="accordion" id="accordionIlce">
                    @foreach($top5Iller as $i => $il)
                    @php $ilceler = $ilceDrilldown[$il] ?? collect(); @endphp
                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }} py-2 small fw-bold" type="button"
                                data-bs-toggle="collapse" data-bs-target="#ilce{{ $i }}">
                                <i class="fas fa-map-marker-alt me-2 text-danger"></i>{{ $il }}
                                <span class="badge bg-secondary ms-2">{{ $ilceler->count() }} ilçe</span>
                            </button>
                        </h2>
                        <div id="ilce{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#accordionIlce">
                            <div class="accordion-body p-2">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        @foreach($ilceler->take(10) as $ilce)
                                        <tr>
                                            <td class="small">{{ $ilce->il_ilce }}</td>
                                            <td class="text-end small fw-bold">{{ number_format($ilce->toplam) }}</td>
                                            <td style="width:100px;">
                                                <div class="pct-bar">
                                                    <div class="pct-fill" style="width:{{ $ilceler->first()->toplam ? round($ilce->toplam/$ilceler->first()->toplam*100) : 0 }}%;"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

</div>{{-- /container --}}

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', sans-serif";
Chart.defaults.font.size   = 12;

const COLORS = ['#e94560','#0d6efd','#198754','#ffc107','#6f42c1','#fd7e14','#20c997','#0dcaf0','#dc3545','#6c757d'];

// ── 1. Kaynak Donut ──────────────────────────────────────────────────────────
new Chart(document.getElementById('chartKaynak'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($kaynaklar->pluck('kaynak')->map(fn($k) => strtoupper($k))) !!},
        datasets: [{
            data: {!! json_encode($kaynaklar->pluck('toplam')) !!},
            backgroundColor: COLORS,
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '60%', maintainAspectRatio: false }
});

// ── 2. İl Yatay Bar ──────────────────────────────────────────────────────────
new Chart(document.getElementById('chartIl'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($ilDagilim->pluck('il')) !!},
        datasets: [
            {
                label: 'E-posta Var',
                data: {!! json_encode($ilDagilim->pluck('eposta_var')) !!},
                backgroundColor: '#0d6efd99',
            },
            {
                label: 'E-posta Yok',
                data: {!! json_encode($ilDagilim->map(fn($r) => $r->toplam - $r->eposta_var)->values()) !!},
                backgroundColor: '#e9456044',
            }
        ]
    },
    options: {
        indexAxis: 'y',
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: true, grid: { color: '#f0f2f5' } }, y: { stacked: true } }
    }
});

// ── 3. Büyük şehir Donut ─────────────────────────────────────────────────────
@php
    $istanbul = $ilDagilim->firstWhere('il','İstanbul')?->toplam ?? 0;
    $ankara   = $ilDagilim->firstWhere('il','Ankara')?->toplam   ?? 0;
    $izmir    = $ilDagilim->firstWhere('il','İzmir')?->toplam    ?? 0;
@endphp
new Chart(document.getElementById('chartBuyukSehir'), {
    type: 'doughnut',
    data: {
        labels: ['İstanbul', 'Ankara', 'İzmir', 'Diğer'],
        datasets: [{
            data: [{{ $istanbul }}, {{ $ankara }}, {{ $izmir }}, {{ $digerIlToplam }}],
            backgroundColor: ['#e94560','#0d6efd','#198754','#dee2e6'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '55%', maintainAspectRatio: false }
});

// ── 4. Grup Pie ───────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartGrup'), {
    type: 'pie',
    data: {
        labels: {!! json_encode($grupDagilim->pluck('grup')) !!},
        datasets: [{
            data: {!! json_encode($grupDagilim->pluck('toplam')) !!},
            backgroundColor: COLORS,
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, maintainAspectRatio: false }
});

// ── 5. Grup E-posta Stacked ───────────────────────────────────────────────────
new Chart(document.getElementById('chartGrupEposta'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($grupDagilim->pluck('grup')) !!},
        datasets: [
            {
                label: 'E-posta Var',
                data: {!! json_encode($grupDagilim->pluck('eposta_var')) !!},
                backgroundColor: '#19875499',
            },
            {
                label: 'E-posta Yok',
                data: {!! json_encode($grupDagilim->map(fn($r) => $r->toplam - $r->eposta_var)->values()) !!},
                backgroundColor: '#dc354544',
            }
        ]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: true }, y: { stacked: true, grid: { color: '#f0f2f5' } } }
    }
});

// ── 6. İl E-posta Oranı Bar ──────────────────────────────────────────────────
new Chart(document.getElementById('chartIlEposta'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($ilEpostaOran->pluck('il')) !!},
        datasets: [{
            label: 'E-posta Doluluk Oranı (%)',
            data: {!! json_encode($ilEpostaOran->pluck('oran')) !!},
            backgroundColor: COLORS.map((c,i) => c + '99'),
        }]
    },
    options: {
        indexAxis: 'y',
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { min: 0, max: 100, grid: { color: '#f0f2f5' }, ticks: { callback: v => v + '%' } },
            y: {}
        }
    }
});

// ── 7. Davet Donut ───────────────────────────────────────────────────────────
new Chart(document.getElementById('chartDavet'), {
    type: 'doughnut',
    data: {
        labels: ['Davet Gönderildi', 'Henüz Gönderilmedi', 'E-posta Yok'],
        datasets: [{
            data: [{{ $davetBasarili }}, {{ $epostaVar - $davetBasarili }}, {{ $toplam - $epostaVar }}],
            backgroundColor: ['#198754','#ffc107','#dee2e6'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '55%', maintainAspectRatio: false }
});

// ── 8. Radar ─────────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartRadar'), {
    type: 'radar',
    data: {
        labels: ['E-posta', 'Telefon', 'Adres', 'İl', 'Grup', 'Ticari Ünvan'],
        datasets: [
            @foreach($veriKalitesi as $i => $vk)
            {
                label: '{{ strtoupper($vk->kaynak) }}',
                data: [{{ $vk->eposta_pct }}, {{ $vk->telefon_pct }}, {{ $vk->adres_pct }}, {{ $vk->il_pct }}, {{ $vk->grup_pct }}, {{ $vk->ticari_pct }}],
                backgroundColor: '{{ ['#e9456022','#0d6efd22','#19875422'][$i % 3] }}',
                borderColor: '{{ ['#e94560','#0d6efd','#198754'][$i % 3] }}',
                borderWidth: 2, pointRadius: 3,
            },
            @endforeach
        ]
    },
    options: {
        maintainAspectRatio: false,
        scales: { r: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%', stepSize: 25 } } },
        plugins: { legend: { position: 'bottom' } }
    }
});

// ── Tarihi Büyüme Line ───────────────────────────────────────────────────────
// Kaynak: TÜRSAB resmi verisi (turizmgazetesi.com, 19 Şubat 2024 doğrulaması)
// 2021=12649 makale ile doğrulandı; 2022=14505 (rekor +1856); 2023=15678 (TÜRSAB)
const tarihiYillar = [2000,2001,2002,2003,2004,2005,2006,2007,2008,2009,2010,2011,2012,2013,2014,2015,2016,2017,2018,2019,2020,2021,2022,2023];
const tarihiSayilar = [4077,4209,4344,4515,4643,4825,5050,5268,5519,5787,6107,6452,6959,7377,7987,8717,9316,9795,10305,11410,12269,12649,14505,15678];
new Chart(document.getElementById('chartTarihiBuyume'), {
    type: 'line',
    data: {
        labels: tarihiYillar,
        datasets: [{
            label: 'Acente Sayısı',
            data: tarihiSayilar,
            borderColor: '#0d6efd',
            backgroundColor: '#0d6efd18',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#0d6efd',
            borderWidth: 2,
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y.toLocaleString('tr') + ' acente' } }
        },
        scales: {
            x: { grid: { color: '#f0f2f5' } },
            y: { grid: { color: '#f0f2f5' }, ticks: { callback: v => v.toLocaleString('tr') } }
        }
    }
});

// ── Avrupa Karşılaştırma Grouped Bar ─────────────────────────────────────────
const avrupaUlkeler = ['İtalya','İspanya','Almanya','İngiltere','Fransa','Polonya','Hollanda','Çek Cum.','Yunanistan','Portekiz','🇹🇷 Türkiye'];
const avrupa2005    = [13981,13757,10181,9212,8840,7451,6482,5728,3783,4116,4825];
const avrupa2021    = [11124,8373,8829,7819,5020,5373,2579,6515,3277,2210,12649];
new Chart(document.getElementById('chartAvrupa'), {
    type: 'bar',
    data: {
        labels: avrupaUlkeler,
        datasets: [
            { label: '2005', data: avrupa2005, backgroundColor: '#0d6efd55', borderColor: '#0d6efd', borderWidth: 1 },
            { label: '2021', data: avrupa2021, backgroundColor: avrupa2021.map((v,i) => v > avrupa2005[i] ? '#19875499' : '#e9456055'), borderColor: avrupa2021.map((v,i) => v > avrupa2005[i] ? '#198754' : '#e94560'), borderWidth: 1 },
        ]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('tr') } }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { grid: { color: '#f0f2f5' }, ticks: { callback: v => v.toLocaleString('tr') } }
        }
    }
});

// ── 9. Histogram ─────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartHistogram'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($belgeNoHistogram->map(fn($r) => $r->aralik . '–' . ($r->aralik + 999))->values()) !!},
        datasets: [{
            label: 'Acente Sayısı',
            data: {!! json_encode($belgeNoHistogram->pluck('toplam')) !!},
            backgroundColor: '#6f42c199',
            borderColor: '#6f42c1',
            borderWidth: 1,
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } },
            y: { grid: { color: '#f0f2f5' } }
        }
    }
});

// ── 10. Şube Bar ─────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartSube'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($subeDagilim->map(fn($r) => $r->sube_sira == 0 ? 'Ana Merkez' : $r->sube_sira.'. Şube')->values()) !!},
        datasets: [{
            label: 'Acente',
            data: {!! json_encode($subeDagilim->pluck('toplam')) !!},
            backgroundColor: '#20c99799',
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { grid: { color: '#f0f2f5' } }
        }
    }
});

// ── 11. İlçe Bar ─────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartIlce'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($ilceDagilim->map(fn($r) => $r->il_ilce . ' (' . $r->il . ')')->values()) !!},
        datasets: [{
            label: 'Acente',
            data: {!! json_encode($ilceDagilim->pluck('toplam')) !!},
            backgroundColor: COLORS.map((c,i) => c + '99'),
        }]
    },
    options: {
        indexAxis: 'y',
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: '#f0f2f5' } },
            y: { ticks: { font: { size: 11 } } }
        }
    }
});
</script>
</body>
</html>

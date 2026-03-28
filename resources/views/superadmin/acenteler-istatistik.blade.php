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
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%); color:#fff; padding:24px 32px 20px; }
.page-header h1 { font-size:1.6rem; font-weight:700; margin:0; }
.page-header p  { margin:4px 0 0; color:rgba(255,255,255,.6); font-size:.88rem; }
.nav-tabs .nav-link { color:#495057; font-weight:600; border:none; border-bottom:3px solid transparent; padding:10px 20px; }
.nav-tabs .nav-link.active { color:#e94560; border-bottom-color:#e94560; background:none; }
.nav-tabs { border-bottom:2px solid #dee2e6; background:#fff; padding:0 24px; }
.kpi-card { background:#fff; border-radius:12px; padding:18px 20px; box-shadow:0 2px 8px rgba(0,0,0,.07); border-left:4px solid #e94560; height:100%; }
.kpi-val  { font-size:2rem; font-weight:800; line-height:1.1; }
.kpi-label{ font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; color:#6c757d; margin-top:4px; }
.kpi-sub  { font-size:.8rem; color:#6c757d; margin-top:5px; }
.section-card { background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.07); margin-bottom:24px; }
.card-hdr { padding:14px 20px 10px; border-bottom:1px solid #f0f2f5; font-weight:700; font-size:.95rem; }
.card-bdy { padding:18px 20px; }
.insight-box { background:#f8f9fa; border-radius:8px; padding:10px 14px; font-size:.82rem; color:#495057; border-left:3px solid #0d6efd; }
.pct-bar  { height:6px; border-radius:3px; background:#dee2e6; }
.pct-fill { height:100%; border-radius:3px; background:#e94560; }
.bolge-badge { display:inline-block; padding:2px 9px; border-radius:12px; font-size:.72rem; font-weight:600; }
.table-sm td,.table-sm th { font-size:.81rem; vertical-align:middle; }
.badge-kaynak-tursab   { background:#0d6efd22; color:#0d6efd; border-radius:6px; padding:2px 8px; font-size:.74rem; }
.badge-kaynak-bakanlik { background:#19875422; color:#198754; border-radius:6px; padding:2px 8px; font-size:.74rem; }
.badge-kaynak-manuel   { background:#6f42c122; color:#6f42c1; border-radius:6px; padding:2px 8px; font-size:.74rem; }
.page-header { background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%); color:#fff; padding:16px 32px 14px; }
.page-header h1 { font-size:1.3rem; font-weight:700; margin:0; }
.page-header p  { margin:3px 0 0; color:rgba(255,255,255,.6); font-size:.82rem; }
</style>
</head>
<body>

<x-navbar-superadmin active="acenteler-istatistik" />

<div class="page-header">
    <div class="container-fluid px-2">
        <h1><i class="fas fa-chart-bar me-2" style="color:#e94560;"></i>Acente İstatistikleri</h1>
        <p>Türkiye seyahat acentesi veritabanı — {{ number_format($toplam) }} kayıt</p>
    </div>
</div>

{{-- SEKMELER --}}
<ul class="nav nav-tabs" id="mainTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-turizm"><i class="fas fa-globe me-1"></i>Turizm İstatistikleri</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-veri"><i class="fas fa-database me-1"></i>Veri & Kaynak Analizi</a></li>
</ul>

<div class="tab-content">
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SEKME 1: TURİZM İSTATİSTİKLERİ --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade show active" id="tab-turizm">
<div class="container-fluid px-4 py-4">

@php
    $istanbulSayi = $ilDagilim->firstWhere('il','İstanbul')?->toplam ?? 0;
    $antalyaSayi  = $ilDagilim->firstWhere('il','Antalya')?->toplam  ?? 0;
    $muglasSayi   = $ilDagilim->firstWhere('il','Muğla')?->toplam    ?? 0;
    $en1Il        = $ilDagilim->first();
    $enAz1Il      = $enAzIller->first();
@endphp

{{-- KPI KARTLARI --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card" style="border-color:#e94560;">
            <div class="kpi-val" style="color:#e94560;">{{ number_format($toplam) }}</div>
            <div class="kpi-label">Toplam Acente</div>
            <div class="kpi-sub">Türkiye geneli</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card" style="border-color:#0d6efd;">
            <div class="kpi-val" style="color:#0d6efd;">{{ number_format($nufusBasinaAcente) }}</div>
            <div class="kpi-label">Kişiye 1 Acente</div>
            <div class="kpi-sub">85.3M nüfus bazında</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card" style="border-color:#198754;">
            <div class="kpi-val" style="color:#198754;">{{ $istanbulSayi > 0 ? round($istanbulSayi/$toplam*100, 1) : 0 }}%</div>
            <div class="kpi-label">İstanbul Payı</div>
            <div class="kpi-sub">{{ number_format($istanbulSayi) }} acente</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card" style="border-color:#ffc107;">
            <div class="kpi-val" style="color:#ffc107;">{{ $anaMerkez > 0 ? number_format($anaMerkez) : 0 }}</div>
            <div class="kpi-label">Bağımsız Merkez</div>
            <div class="kpi-sub">{{ number_format($subeCount) }} şube ayrı</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card" style="border-color:#fd7e14;">
            <div class="kpi-val" style="color:#fd7e14;">+{{ round((15678-4077)/4077*100) }}%</div>
            <div class="kpi-label">23 Yıllık Büyüme</div>
            <div class="kpi-sub">2000→2023</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl-2">
        <div class="kpi-card" style="border-color:#6f42c1;">
            <div class="kpi-val" style="color:#6f42c1;">{{ $ilDagilimTumu->count() }}</div>
            <div class="kpi-label">Acente Olan İl</div>
            <div class="kpi-sub">81 ilin {{ $ilDagilimTumu->count() }}'inde</div>
        </div>
    </div>
</div>

{{-- 1. TARİHİ BÜYÜME --}}
<div class="section-card">
    <div class="card-hdr"><i class="fas fa-chart-line me-2" style="color:#0d6efd;"></i>Türkiye Seyahat Acentası Sayısı — Yıllara Göre Büyüme (2000–2023)</div>
    <div class="card-bdy">
        <div style="height:260px;"><canvas id="chartTarihiBuyume"></canvas></div>
        <div class="row g-2 mt-3">
            <div class="col-md-4"><div class="insight-box" style="border-color:#198754;">
                <strong>23 yılda 3.8 kat büyüme</strong> — 4.077'den 15.678'e. Artış oranı %284. Yıllık ortalama ~490 yeni acente.
            </div></div>
            <div class="col-md-4"><div class="insight-box" style="border-color:#ffc107;">
                <strong>Rekor yıl 2022:</strong> Tek yılda +1.856 yeni acente (12.649→14.505). Pandemi sonrası turizm patlamasının yansıması.
            </div></div>
            <div class="col-md-4"><div class="insight-box" style="border-color:#e94560;">
                <strong>Avrupa'nın tersi:</strong> Aynı dönemde 26 Avrupa ülkesinde toplam −28.465 acente. Türkiye tek büyüyen ülke.
            </div></div>
        </div>
    </div>
</div>

{{-- 2. AVRUPA KARŞILAŞTIRMASI --}}
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-globe-europe me-2" style="color:#0d6efd;"></i>Türkiye vs Avrupa — Acente Sayısı (2005 → 2021)</div>
            <div class="card-bdy">
                <div style="height:380px;"><canvas id="chartAvrupa"></canvas></div>
                <div class="insight-box mt-3" style="border-color:#198754;">
                    <strong>Türkiye tek büyüyen:</strong> 26 ülkede toplam 107.208 → 78.743 (−%26). Türkiye 4.825 → 12.649 (+%162). Kaynak: ECTAA / turizmgazetesi.com 2024
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-table me-2"></i>Ülke Sıralaması (Değişime Göre)</div>
            <div class="card-bdy p-0" style="max-height:500px;overflow-y:auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top"><tr><th>Ülke</th><th class="text-end">2005</th><th class="text-end">2021</th><th class="text-end">Değişim</th></tr></thead>
                    <tbody>
                        @php
                        $avrupaVerisi = [
                            ['İtalya',13981,11124],['İspanya',13757,8373],['Almanya',10181,8829],
                            ['İngiltere',9212,7819],['Fransa',8840,5020],['Polonya',7451,5373],
                            ['Hollanda',6482,2579],['Çek Cum.',5728,6515],['Portekiz',4116,2210],
                            ['Yunanistan',3783,3277],['İsveç',3212,2798],['Romanya',3114,1990],
                            ['Macaristan',2523,1813],['Finlandiya',2321,1040],['Avusturya',2263,1515],
                            ['Belçika',1820,1553],['Bulgaristan',1685,1367],['Slovakya',1357,426],
                            ['İrlanda',1180,346],['Litvanya',1032,635],['Slovenya',917,404],
                            ['Letonya',715,358],['Danimarka',571,633],['K.Kıbrıs',443,508],
                            ['Estonya',439,310],['Lüksemburg',85,99],
                            ['🇹🇷 TÜRKİYE',4825,12649],
                        ];
                        usort($avrupaVerisi, fn($a,$b) => ($b[2]-$b[1]) <=> ($a[2]-$a[1]));
                        @endphp
                        @foreach($avrupaVerisi as $r)
                        @php $d = $r[2]-$r[1]; $p = $r[1]>0 ? round($d/$r[1]*100,1) : 0; $isTR = str_contains($r[0],'TÜRKİYE'); @endphp
                        <tr @if($isTR) style="background:#19875415;font-weight:700;" @endif>
                            <td>{{ $r[0] }}</td>
                            <td class="text-end">{{ number_format($r[1]) }}</td>
                            <td class="text-end">{{ number_format($r[2]) }}</td>
                            <td class="text-end" style="color:{{ $d>=0?'#198754':'#dc3545' }};">{{ $d>=0?'+':'' }}{{ number_format($d) }} <small>({{ $p>=0?'+':'' }}{{ $p }}%)</small></td>
                        </tr>
                        @endforeach
                        <tr class="table-dark fw-bold"><td>26 Ülke Toplam</td><td class="text-end">107.208</td><td class="text-end">78.743</td><td class="text-end text-danger">−28.465</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 3. BÖLGE DAĞILIMI --}}
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-map me-2 text-warning"></i>7 Coğrafi Bölge Dağılımı</div>
            <div class="card-bdy">
                <div style="height:260px;"><canvas id="chartBolge"></canvas></div>
                <div class="mt-3">
                    @php
                    $bolgeRenkler = ['#e94560','#0d6efd','#198754','#ffc107','#6f42c1','#fd7e14','#20c997'];
                    @endphp
                    @foreach($bolgeVerisi as $i => $b)
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small"><span style="display:inline-block;width:10px;height:10px;background:{{ $bolgeRenkler[$i%7] }};border-radius:2px;margin-right:6px;"></span>{{ $b['bolge'] }}</span>
                        <span class="small fw-bold">{{ number_format($b['toplam']) }} <span class="text-muted">({{ $toplam ? round($b['toplam']/$toplam*100,1) : 0 }}%)</span></span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-sort-amount-down me-2 text-warning"></i>İl Bazlı Dağılım — Top 20</div>
            <div class="card-bdy">
                <div style="height:380px;"><canvas id="chartIl"></canvas></div>
            </div>
        </div>
    </div>
</div>

{{-- 4. EN ÇOK / EN AZ + DESTİNASYONLAR --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-trophy me-2" style="color:#ffc107;"></i>En Çok / En Az Acenteli Şehirler</div>
            <div class="card-bdy p-0">
                <div class="p-3 pb-1"><small class="text-muted fw-bold">EN ÇOK</small></div>
                @foreach($ilDagilim->take(5) as $il)
                <div class="px-3 py-1">
                    <div class="d-flex justify-content-between"><span class="small">{{ $il->il }}</span><span class="small fw-bold">{{ number_format($il->toplam) }}</span></div>
                    <div class="pct-bar"><div class="pct-fill" style="width:{{ $ilDagilim->first()->toplam ? round($il->toplam/$ilDagilim->first()->toplam*100) : 0 }}%;background:#e94560;"></div></div>
                </div>
                @endforeach
                <hr class="my-2">
                <div class="p-3 pb-1"><small class="text-muted fw-bold">EN AZ</small></div>
                @foreach($enAzIller->take(8) as $il)
                <div class="px-3 py-1">
                    <div class="d-flex justify-content-between"><span class="small">{{ $il->il }}</span><span class="small fw-bold text-muted">{{ number_format($il->toplam) }}</span></div>
                    <div class="pct-bar"><div class="pct-fill" style="width:{{ $ilDagilim->first()->toplam ? round($il->toplam/$ilDagilim->first()->toplam*100) : 0 }}%;background:#6c757d;"></div></div>
                </div>
                @endforeach
                <div class="px-3 py-2">
                    <div class="insight-box" style="border-color:#ffc107;">
                        <strong>{{ $en1Il?->il }}</strong> tek başına {{ $toplam ? round($en1Il->toplam/$toplam*100,1) : 0 }}% payıyla birinci. En az acenteli il <strong>{{ $enAz1Il?->il }}</strong> ({{ $enAz1Il?->toplam }} acente).
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-umbrella-beach me-2 text-info"></i>Önemli Turizm Destinasyonları</div>
            <div class="card-bdy">
                <div style="height:280px;"><canvas id="chartDestinasyon"></canvas></div>
                <div class="insight-box mt-3" style="border-color:#0dcaf0;">
                    <strong>Antalya + Muğla:</strong> Türkiye'nin iki büyük kıyı destinasyonu toplamda
                    {{ number_format(($destinasyonlar->firstWhere('il','Antalya')?->toplam ?? 0) + ($destinasyonlar->firstWhere('il','Muğla')?->toplam ?? 0)) }} acente barındırıyor.
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-city me-2" style="color:#6f42c1;"></i>İstanbul / Ankara / İzmir vs Türkiye</div>
            <div class="card-bdy">
                <div style="height:200px;"><canvas id="chartBuyukSehir"></canvas></div>
                <div class="row g-2 mt-2 text-center">
                    @foreach([['İstanbul','#e94560'],['Ankara','#0d6efd'],['İzmir','#198754']] as [$ilAdi,$renk])
                    @php $s = $ilDagilim->firstWhere('il',$ilAdi)?->toplam ?? 0; @endphp
                    <div class="col-4">
                        <div class="fw-bold" style="color:{{ $renk }};">{{ number_format($s) }}</div>
                        <div class="small text-muted">{{ $ilAdi }}</div>
                        <div class="small text-muted">{{ $toplam ? round($s/$toplam*100,1) : 0 }}%</div>
                    </div>
                    @endforeach
                </div>
                <div class="insight-box mt-3" style="border-color:#6f42c1;">
                    3 büyük şehir toplamın <strong>{{ $toplam ? round($buyuk3Toplam/$toplam*100,1) : 0 }}%</strong>'ini oluşturuyor ({{ number_format($buyuk3Toplam) }} acente).
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 5. İLÇE + GRUP --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-map-pin me-2 text-secondary"></i>En Fazla Acenteli 15 İlçe</div>
            <div class="card-bdy">
                <div style="height:340px;"><canvas id="chartIlce"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-layer-group me-2 text-warning"></i>TÜRSAB Grup Dağılımı + Açıklama</div>
            <div class="card-bdy">
                <div style="height:200px;"><canvas id="chartGrup"></canvas></div>
                <table class="table table-sm mt-3 mb-0">
                    <thead class="table-light"><tr><th>Grup</th><th>Açıklama</th><th class="text-end">Sayı</th><th class="text-end">Pay</th></tr></thead>
                    <tbody>
                        @php
                        $grupAciklama = ['A'=>'Uluslararası + yurt içi','B'=>'Yurt içi turizm','C'=>'Incoming (gelen turizm)','D'=>'Kruvaziyer','E'=>'Serbest satış'];
                        $tursabToplam = $grupDagilim->where('grup','!=','Belirtilmemiş')->sum('toplam');
                        @endphp
                        @foreach($grupDagilim->where('grup','!=','Belirtilmemiş') as $g)
                        <tr>
                            <td><span class="badge bg-primary">{{ $g->grup }}</span></td>
                            <td class="small text-muted">{{ $grupAciklama[$g->grup] ?? '—' }}</td>
                            <td class="text-end fw-bold">{{ number_format($g->toplam) }}</td>
                            <td class="text-end small">{{ $tursabToplam ? round($g->toplam/$tursabToplam*100,1) : 0 }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 6. TOP 5 İL → İLÇE DRILLDOWN --}}
<div class="section-card mb-4">
    <div class="card-hdr"><i class="fas fa-sitemap me-2 text-secondary"></i>Top 5 İl → İlçe Dağılımı</div>
    <div class="card-bdy">
        <div class="row g-3">
            @foreach($top5Iller as $i => $il)
            @php $ilceler = $ilceDrilldown[$il] ?? collect(); @endphp
            <div class="col-md-{{ $i < 2 ? 4 : 3 }}">
                <div class="fw-bold mb-2" style="color:#e94560;"><i class="fas fa-map-marker-alt me-1"></i>{{ $il }}</div>
                @foreach($ilceler->take(8) as $ilce)
                <div class="d-flex justify-content-between mb-1">
                    <span class="small text-truncate" style="max-width:140px;">{{ $ilce->il_ilce }}</span>
                    <span class="small fw-bold">{{ number_format($ilce->toplam) }}</span>
                </div>
                <div class="pct-bar mb-1"><div class="pct-fill" style="width:{{ $ilceler->first()->toplam ? round($ilce->toplam/$ilceler->first()->toplam*100) : 0 }}%;"></div></div>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- 7. BELGE NO TARİHSEL --}}
<div class="section-card mb-4">
    <div class="card-hdr"><i class="fas fa-history me-2 text-info"></i>Belge No Dağılımı — Hangi Dönemde Kaç Acente Kuruldu?</div>
    <div class="card-bdy">
        <div style="height:220px;"><canvas id="chartHistogram"></canvas></div>
        <div class="insight-box mt-3">
            Her çubuk 1.000 belge no aralığını temsil eder. Yüksek yoğunluklu aralıklar o dönemde çok sayıda acente kurulduğuna işaret eder — özellikle 2010 sonrası turizm patlaması görünür.
        </div>
    </div>
</div>

{{-- 8. ŞUBE ANALİZİ --}}
<div class="row g-3 mb-4">
    {{-- En çok şubeli acenteler --}}
    <div class="col-md-5">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-code-branch me-2" style="color:#0d6efd;"></i>En Çok Şubeli Acenteler (Top 15)</div>
            <div class="card-bdy p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Acente Adı</th><th>İl</th><th class="text-end">Şube</th></tr></thead>
                    <tbody>
                        @foreach($enCokSubeliAcenteler as $i => $a)
                        <tr @if($i===0) style="background:#0d6efd10;font-weight:700;" @endif>
                            <td class="text-muted small">{{ $i+1 }}</td>
                            <td class="small">{{ $a->unvan ?? '(Bilinmiyor)' }}</td>
                            <td class="small text-muted">{{ $a->il ?? '—' }}</td>
                            <td class="text-end">
                                <span class="badge" style="background:#0d6efd22;color:#0d6efd;">{{ $a->sube_sayisi }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($enCokSubeliAcenteler->count() > 0)
                <div class="p-3">
                    <div class="insight-box" style="border-color:#0d6efd;">
                        En çok şubesi olan acente: <strong>{{ $enCokSubeliAcenteler->first()?->unvan ?? '—' }}</strong>
                        — <strong>{{ $enCokSubeliAcenteler->first()?->sube_sayisi }}</strong> şube ile listede birinci.
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- İl ve ilçe bazlı şube yoğunluğu --}}
    <div class="col-md-7">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-map-marker-alt me-2" style="color:#e94560;"></i>Şube Yoğunluğu — İl & İlçe</div>
            <div class="card-bdy">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small fw-bold text-muted mb-2 text-uppercase">En Çok Şube Olan İller</div>
                        @foreach($ilSubeYogunluk->take(10) as $row)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-truncate" style="max-width:130px;">{{ $row->il }}</span>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:70px;height:6px;border-radius:3px;background:#dee2e6;">
                                    <div style="width:{{ $ilSubeYogunluk->first()->toplam_sube ? round($row->toplam_sube/$ilSubeYogunluk->first()->toplam_sube*100) : 0 }}%;height:100%;border-radius:3px;background:#e94560;"></div>
                                </div>
                                <span class="small fw-bold" style="min-width:28px;text-align:right;">{{ $row->toplam_sube }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        <div class="small fw-bold text-muted mb-2 text-uppercase">En Çok Şube Olan İlçeler</div>
                        @foreach($ilceSubeYogunluk->take(10) as $row)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-truncate" style="max-width:130px;" title="{{ $row->il_ilce }} ({{ $row->il }})">{{ $row->il_ilce }} <span class="text-muted">({{ $row->il }})</span></span>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:70px;height:6px;border-radius:3px;background:#dee2e6;">
                                    <div style="width:{{ $ilceSubeYogunluk->first()->toplam_sube ? round($row->toplam_sube/$ilceSubeYogunluk->first()->toplam_sube*100) : 0 }}%;height:100%;border-radius:3px;background:#0d6efd;"></div>
                                </div>
                                <span class="small fw-bold" style="min-width:28px;text-align:right;">{{ $row->toplam_sube }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="row g-2 mt-3">
                    <div class="col-6">
                        <div class="insight-box" style="border-color:#e94560;">
                            Şube yoğunluğu en yüksek il: <strong>{{ $ilSubeYogunluk->first()?->il ?? '—' }}</strong>
                            ({{ $ilSubeYogunluk->first()?->toplam_sube }} şube)
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="insight-box" style="border-color:#0d6efd;">
                            En çok şube barındıran ilçe: <strong>{{ $ilceSubeYogunluk->first()?->il_ilce ?? '—' }}</strong>
                            ({{ $ilceSubeYogunluk->first()?->toplam_sube }} şube)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SEKME 2: VERİ & KAYNAK ANALİZİ --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="tab-pane fade" id="tab-veri">
<div class="container-fluid px-4 py-4">

{{-- KPI --}}
<div class="row g-3 mb-4">
    @php
        $tursabRow   = $kaynaklar->firstWhere('kaynak', 'tursab');
        $bakanlikRow = $kaynaklar->firstWhere('kaynak', 'bakanlik');
        $manuelRow   = $kaynaklar->firstWhere('kaynak', 'manuel');
        $epostaPct   = $toplam ? round($epostaVar/$toplam*100,1) : 0;
        $telefonPct  = $toplam ? round($telefonVar/$toplam*100,1) : 0;
    @endphp
    <div class="col-6 col-md-2"><div class="kpi-card" style="border-color:#0d6efd;"><div class="kpi-val" style="color:#0d6efd;">{{ number_format($tursabRow?->toplam??0) }}</div><div class="kpi-label">TÜRSAB</div></div></div>
    <div class="col-6 col-md-2"><div class="kpi-card" style="border-color:#198754;"><div class="kpi-val" style="color:#198754;">{{ number_format($bakanlikRow?->toplam??0) }}</div><div class="kpi-label">Bakanlık</div></div></div>
    <div class="col-6 col-md-2"><div class="kpi-card" style="border-color:#6f42c1;"><div class="kpi-val" style="color:#6f42c1;">{{ number_format($manuelRow?->toplam??0) }}</div><div class="kpi-label">Manuel</div></div></div>
    <div class="col-6 col-md-2"><div class="kpi-card" style="border-color:#fd7e14;"><div class="kpi-val" style="color:#fd7e14;">{{ number_format($epostaVar) }}</div><div class="kpi-label">E-posta Var</div><div class="kpi-sub">{{ $epostaPct }}%</div></div></div>
    <div class="col-6 col-md-2"><div class="kpi-card" style="border-color:#20c997;"><div class="kpi-val" style="color:#20c997;">{{ number_format($telefonVar) }}</div><div class="kpi-label">Telefon Var</div><div class="kpi-sub">{{ $telefonPct }}%</div></div></div>
    <div class="col-6 col-md-2"><div class="kpi-card" style="border-color:#e94560;"><div class="kpi-val" style="color:#e94560;">{{ number_format($davetBasarili) }}</div><div class="kpi-label">Davet Gönderildi</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-database me-2 text-primary"></i>Kaynak Dağılımı</div>
            <div class="card-bdy"><div style="height:220px;"><canvas id="chartKaynak"></canvas></div></div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-table me-2 text-primary"></i>Kaynak Bazında Veri Tamlığı</div>
            <div class="card-bdy p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Kaynak</th><th class="text-end">Toplam</th><th class="text-end">E-posta</th><th class="text-end">Telefon</th><th class="text-end">Adres</th><th class="text-end">İl</th></tr></thead>
                    <tbody>
                        @foreach($kaynaklar as $row)
                        <tr>
                            <td><span class="badge-kaynak-{{ $row->kaynak }}">{{ strtoupper($row->kaynak) }}</span></td>
                            <td class="text-end fw-bold">{{ number_format($row->toplam) }}</td>
                            <td class="text-end">{{ number_format($row->eposta_var) }} <small class="text-muted">({{ $row->toplam ? round($row->eposta_var/$row->toplam*100) : 0 }}%)</small></td>
                            <td class="text-end">{{ number_format($row->telefon_var) }} <small class="text-muted">({{ $row->toplam ? round($row->telefon_var/$row->toplam*100) : 0 }}%)</small></td>
                            <td class="text-end">{{ number_format($row->adres_var) }} <small class="text-muted">({{ $row->toplam ? round($row->adres_var/$row->toplam*100) : 0 }}%)</small></td>
                            <td class="text-end">{{ number_format($row->il_var) }} <small class="text-muted">({{ $row->toplam ? round($row->il_var/$row->toplam*100) : 0 }}%)</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-spider me-2" style="color:#6f42c1;"></i>Veri Kalitesi — Kaynak Karşılaştırması</div>
            <div class="card-bdy"><div style="height:280px;"><canvas id="chartRadar"></canvas></div></div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-th me-2" style="color:#6f42c1;"></i>Alan Doluluk Yüzdeleri</div>
            <div class="card-bdy p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Kaynak</th><th class="text-center">E-posta</th><th class="text-center">Telefon</th><th class="text-center">Adres</th><th class="text-center">İl</th><th class="text-center">Grup</th><th class="text-center">Ticari Ünvan</th></tr></thead>
                    <tbody>
                        @foreach($veriKalitesi as $vk)
                        <tr>
                            <td><span class="badge-kaynak-{{ $vk->kaynak }}">{{ strtoupper($vk->kaynak) }}</span></td>
                            @foreach(['eposta_pct','telefon_pct','adres_pct','il_pct','grup_pct','ticari_pct'] as $f)
                            <td class="text-center">@php $v=$vk->$f??0; @endphp
                                <span class="badge" style="background:{{ $v>=70?'#19875420':($v>=40?'#ffc10720':'#dc354520') }};color:{{ $v>=70?'#198754':($v>=40?'#856404':'#dc3545') }};">{{ $v }}%</span>
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

<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-at me-2 text-success"></i>İl Bazında E-posta Doluluk Oranı (En Yüksek 20)</div>
            <div class="card-bdy"><div style="height:340px;"><canvas id="chartIlEposta"></canvas></div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="section-card h-100">
            <div class="card-hdr"><i class="fas fa-code-branch me-2 text-info"></i>Şube Dağılımı</div>
            <div class="card-bdy"><div style="height:200px;"><canvas id="chartSube"></canvas></div>
                <div class="insight-box mt-3">Şube sırası 0 = tek ofisli firma. Yüksek şube sıralı firmalar ulusal zincirler.</div>
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>{{-- /tab-content --}}

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
Chart.defaults.font.family = "'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
const C = ['#e94560','#0d6efd','#198754','#ffc107','#6f42c1','#fd7e14','#20c997','#0dcaf0','#dc3545','#6c757d'];

// ── Tarihi Büyüme ─────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartTarihiBuyume'), {
    type:'line',
    data:{
        labels:[2000,2001,2002,2003,2004,2005,2006,2007,2008,2009,2010,2011,2012,2013,2014,2015,2016,2017,2018,2019,2020,2021,2022,2023],
        datasets:[{
            label:'Acente Sayısı',
            data:[4077,4209,4344,4515,4643,4825,5050,5268,5519,5787,6107,6452,6959,7377,7987,8717,9316,9795,10305,11410,12269,12649,14505,15678],
            borderColor:'#0d6efd', backgroundColor:'#0d6efd18', fill:true, tension:0.3, pointRadius:3, borderWidth:2,
        }]
    },
    options:{maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' '+c.parsed.y.toLocaleString('tr')+' acente'}}},scales:{x:{grid:{color:'#f0f2f5'}},y:{grid:{color:'#f0f2f5'},ticks:{callback:v=>v.toLocaleString('tr')}}}}
});

// ── Avrupa Grouped Bar ────────────────────────────────────────────────────────
const avUlke=['İtalya','İspanya','Almanya','İngiltere','Fransa','Polonya','Hollanda','Çek Cum.','Yunanistan','Portekiz','🇹🇷 Türkiye'];
const av2005=[13981,13757,10181,9212,8840,7451,6482,5728,3783,4116,4825];
const av2021=[11124,8373,8829,7819,5020,5373,2579,6515,3277,2210,12649];
new Chart(document.getElementById('chartAvrupa'),{
    type:'bar',
    data:{labels:avUlke,datasets:[
        {label:'2005',data:av2005,backgroundColor:'#0d6efd55',borderColor:'#0d6efd',borderWidth:1},
        {label:'2021',data:av2021,backgroundColor:av2021.map((v,i)=>v>av2005[i]?'#19875499':'#e9456055'),borderColor:av2021.map((v,i)=>v>av2005[i]?'#198754':'#e94560'),borderWidth:1},
    ]},
    options:{maintainAspectRatio:false,plugins:{legend:{position:'top'},tooltip:{callbacks:{label:c=>' '+c.dataset.label+': '+c.parsed.y.toLocaleString('tr')}}},scales:{x:{grid:{display:false},ticks:{font:{size:10}}},y:{grid:{color:'#f0f2f5'},ticks:{callback:v=>v.toLocaleString('tr')}}}}
});

// ── Bölge Donut ───────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartBolge'),{
    type:'doughnut',
    data:{
        labels:{!! json_encode(array_column($bolgeVerisi,'bolge')) !!},
        datasets:[{data:{!! json_encode(array_column($bolgeVerisi,'toplam')) !!},backgroundColor:C,borderWidth:2,borderColor:'#fff'}]
    },
    options:{maintainAspectRatio:false,cutout:'60%',plugins:{legend:{display:false}}}
});

// ── İl Bar (yatay) ────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartIl'),{
    type:'bar',
    data:{
        labels:{!! json_encode($ilDagilim->pluck('il')) !!},
        datasets:[{label:'Acente Sayısı',data:{!! json_encode($ilDagilim->pluck('toplam')) !!},backgroundColor:C.map(c=>c+'99')}]
    },
    options:{indexAxis:'y',maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'#f0f2f5'}},y:{ticks:{font:{size:11}}}}}
});

// ── Destinasyon Bar ───────────────────────────────────────────────────────────
new Chart(document.getElementById('chartDestinasyon'),{
    type:'bar',
    data:{
        labels:{!! json_encode($destinasyonlar->pluck('il')) !!},
        datasets:[{data:{!! json_encode($destinasyonlar->pluck('toplam')) !!},backgroundColor:C.map(c=>c+'bb')}]
    },
    options:{maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{size:10}}},y:{grid:{color:'#f0f2f5'}}}}
});

// ── 3 Büyük Şehir Donut ──────────────────────────────────────────────────────
new Chart(document.getElementById('chartBuyukSehir'),{
    type:'doughnut',
    data:{
        labels:['İstanbul','Ankara','İzmir','Diğer'],
        datasets:[{data:[{{ $ilDagilim->firstWhere('il','İstanbul')?->toplam??0 }},{{ $ilDagilim->firstWhere('il','Ankara')?->toplam??0 }},{{ $ilDagilim->firstWhere('il','İzmir')?->toplam??0 }},{{ $digerIlToplam }}],backgroundColor:['#e94560','#0d6efd','#198754','#dee2e6'],borderWidth:2,borderColor:'#fff'}]
    },
    options:{maintainAspectRatio:false,cutout:'55%',plugins:{legend:{position:'bottom',labels:{boxWidth:10}}}}
});

// ── İlçe Bar ─────────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartIlce'),{
    type:'bar',
    data:{
        labels:{!! json_encode($ilceDagilim->map(fn($r)=>$r->il_ilce.' ('.$r->il.')')->values()) !!},
        datasets:[{data:{!! json_encode($ilceDagilim->pluck('toplam')) !!},backgroundColor:C.map(c=>c+'99')}]
    },
    options:{indexAxis:'y',maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'#f0f2f5'}},y:{ticks:{font:{size:10}}}}}
});

// ── Grup Pie ─────────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartGrup'),{
    type:'pie',
    data:{
        labels:{!! json_encode($grupDagilim->where('grup','!=','Belirtilmemiş')->pluck('grup')) !!},
        datasets:[{data:{!! json_encode($grupDagilim->where('grup','!=','Belirtilmemiş')->pluck('toplam')->values()) !!},backgroundColor:C,borderWidth:2,borderColor:'#fff'}]
    },
    options:{maintainAspectRatio:false,plugins:{legend:{position:'right',labels:{boxWidth:12}}}}
});

// ── Histogram ─────────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartHistogram'),{
    type:'bar',
    data:{
        labels:{!! json_encode($belgeNoHistogram->map(fn($r)=>$r->aralik.'-'.($r->aralik+999))->values()) !!},
        datasets:[{data:{!! json_encode($belgeNoHistogram->pluck('toplam')) !!},backgroundColor:'#6f42c199',borderColor:'#6f42c1',borderWidth:1}]
    },
    options:{maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{maxRotation:45,font:{size:10}}},y:{grid:{color:'#f0f2f5'}}}}
});

// ── Kaynak Donut (sekme 2) ────────────────────────────────────────────────────
new Chart(document.getElementById('chartKaynak'),{
    type:'doughnut',
    data:{
        labels:{!! json_encode($kaynaklar->pluck('kaynak')->map(fn($k)=>strtoupper($k))) !!},
        datasets:[{data:{!! json_encode($kaynaklar->pluck('toplam')) !!},backgroundColor:C,borderWidth:2,borderColor:'#fff'}]
    },
    options:{maintainAspectRatio:false,cutout:'60%',plugins:{legend:{position:'bottom'}}}
});

// ── Radar (sekme 2) ───────────────────────────────────────────────────────────
new Chart(document.getElementById('chartRadar'),{
    type:'radar',
    data:{
        labels:['E-posta','Telefon','Adres','İl','Grup','Ticari Ünvan'],
        datasets:[
            @foreach($veriKalitesi as $i => $vk)
            {label:'{{ strtoupper($vk->kaynak) }}',data:[{{ $vk->eposta_pct }},{{ $vk->telefon_pct }},{{ $vk->adres_pct }},{{ $vk->il_pct }},{{ $vk->grup_pct }},{{ $vk->ticari_pct }}],backgroundColor:'{{ ["#e9456022","#0d6efd22","#19875422"][$i%3] }}',borderColor:'{{ ["#e94560","#0d6efd","#198754"][$i%3] }}',borderWidth:2,pointRadius:3},
            @endforeach
        ]
    },
    options:{maintainAspectRatio:false,scales:{r:{beginAtZero:true,max:100,ticks:{callback:v=>v+'%',stepSize:25}}},plugins:{legend:{position:'bottom'}}}
});

// ── İl E-posta Oranı (sekme 2) ────────────────────────────────────────────────
new Chart(document.getElementById('chartIlEposta'),{
    type:'bar',
    data:{
        labels:{!! json_encode($ilEpostaOran->pluck('il')) !!},
        datasets:[{label:'E-posta Doluluk (%)',data:{!! json_encode($ilEpostaOran->pluck('oran')) !!},backgroundColor:C.map(c=>c+'99')}]
    },
    options:{indexAxis:'y',maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{min:0,max:100,ticks:{callback:v=>v+'%'}},y:{}}}
});

// ── Şube (sekme 2) ────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartSube'),{
    type:'bar',
    data:{
        labels:{!! json_encode($subeDagilim->map(fn($r)=>$r->sube_sira==0?'Ana Merkez':$r->sube_sira.'. Şube')->values()) !!},
        datasets:[{data:{!! json_encode($subeDagilim->pluck('toplam')) !!},backgroundColor:'#20c99799'}]
    },
    options:{maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{grid:{color:'#f0f2f5'}}}}
});
</script>
</body>
</html>

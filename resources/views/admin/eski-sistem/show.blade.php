<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <title>Eski Sistem: {{ strtoupper($talep->gtpnr) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { font-family:'Segoe UI',sans-serif; }
        .table th { font-size:0.78rem; }
        .table td { font-size:0.875rem; vertical-align:middle; }
        pre { font-family:'Segoe UI',sans-serif; margin:0; white-space:pre-wrap; }
        /* Karşılaştırma satır renkleri — her iki temada çalışır */
        html[data-theme="dark"]  .esle-ok   { background:#0a3622 !important; }
        html[data-theme="dark"]  .esle-fark { background:#3d1a1a !important; }
        html[data-theme="dark"]  .esle-yok  { background:#2d2d00 !important; }
        html[data-theme="light"] .esle-ok   { background:#d1e7dd !important; }
        html[data-theme="light"] .esle-fark { background:#f8d7da !important; }
        html[data-theme="light"] .esle-yok  { background:#fff3cd !important; }
    </style>
</head>
<body>

<x-navbar-admin active="" />

@php
$durumlar = [
    '0'=>['label'=>'BEKLEMEDE','class'=>'bg-warning text-dark'],
    '1'=>['label'=>'İŞLEMDE','class'=>'bg-info text-dark'],
    '2'=>['label'=>'FİYATLANDIRILDI','class'=>'bg-primary'],
    '3'=>['label'=>'İPTAL','class'=>'bg-danger'],
    '4'=>['label'=>'BİLETLENDİ','class'=>'bg-success'],
    '5'=>['label'=>'DEPOZİTODA','class'=>'bg-danger'],
];
$d = $durumlar[$talep->islemdurumu] ?? ['label'=>$talep->islemdurumu,'class'=>'bg-secondary'];

$talepTipleri = ['UGT'=>'UÇAK GRUP','TGT'=>'TEKNE GRUP','TKT'=>'TEKNE KİRALAMA','UKT'=>'UÇAK KİRALAMA','OGT'=>'OTEL GRUP'];
$talepTipi = $talepTipleri[$talep->taleptipi] ?? $talep->taleptipi;
$yonler = ['1'=>'TEK YÖN','2'=>'GİDİŞ DÖNÜŞ'];
$yon = $yonler[$talep->transfertipi1] ?? '—';

$opsiyonDt = null;
if (!empty($talep->opsiyontarihi) && !empty($talep->opsiyonsaati)) {
    try { $opsiyonDt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $talep->opsiyontarihi.' '.$talep->opsiyonsaati); } catch(\Throwable $e){}
}

$pax = (!empty($talep->pax) && $talep->pax != $talep->kisisayisi) ? $talep->pax : $talep->kisisayisi;
$toplamFiyat = ($talep->toplamodeme ?? 0) + ($talep->kazanc ?? 0);
$kisiBasi = $pax > 0 ? ceil($toplamFiyat / $pax) : 0;
@endphp

<div class="container-fluid py-3 px-4">

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a href="{{ route('admin.eski-sistem') }}" class="btn btn-outline-secondary btn-sm">← Liste</a>
        <h4 class="mb-0 fw-bold text-warning">🗂 {{ strtoupper($talep->gtpnr) }}</h4>
        <span class="badge {{ $d['class'] }} fs-6 px-3">{{ $d['label'] }}</span>
        <span class="badge bg-secondary">{{ $talepTipi }}</span>
        <span class="badge bg-dark border">{{ $yon }}</span>
        @if($yeniTalep)
            <span class="badge bg-success">✅ Yeni sistemde var</span>
        @else
            <span class="badge bg-danger">⚠️ Yeni sistemde YOK</span>
        @endif

        {{-- Yeni sistemde varsa linki --}}
        @if($yeniTalep)
        <a href="{{ route('admin.requests.show', $talep->gtpnr) }}" target="_blank" class="btn btn-outline-warning btn-sm ms-auto">
            Yeni Sistem Detayı →
        </a>
        @endif
    </div>

    {{-- KARŞILAŞTIRMA BÖLÜMÜ --}}
    <div class="card mb-4 border-warning">
        <div class="card-header fw-bold text-warning py-2">
            🔍 Karşılaştırma: Eski Sistem vs Yeni Sistem
        </div>
        <div class="card-body p-0">
            @if(!$yeniTalep)
                <div class="alert alert-danger m-3">
                    ⚠️ Bu GTPNR yeni sistemde <strong>bulunamadı</strong>. Talep yeni sisteme aktarılmamış olabilir.
                </div>
            @else
            @php
            $yeniDurumMap = [
                'beklemede'=>'0','islemde'=>'1','fiyatlandirıldi'=>'2','olumsuz'=>'3','biletlendi'=>'4','depozitoda'=>'5','iade'=>'3'
            ];
            $eskiDurumAdi = $durumlar[$talep->islemdurumu]['label'] ?? $talep->islemdurumu;
            $yeniDurumAdi = strtoupper($yeniTalep->status ?? '—');

            $karsilastirmalar = [
                ['alan'=>'Acenta Adı',    'eski'=>strtoupper($talep->acentaadi),       'yeni'=>strtoupper($yeniTalep->agency_name ?? '—')],
                ['alan'=>'Durum',          'eski'=>$eskiDurumAdi,                        'yeni'=>$yeniDurumAdi],
                ['alan'=>'Kişi Sayısı',   'eski'=>(string)$pax,                         'yeni'=>(string)($yeniTalep->pax_total ?? '—')],
                ['alan'=>'Telefon',        'eski'=>$talep->telefon,                      'yeni'=>$yeniTalep->phone ?? '—'],
                ['alan'=>'E-posta',        'eski'=>strtolower($talep->email),            'yeni'=>strtolower($yeniTalep->email ?? '—')],
                ['alan'=>'Gidiş Kalkış',  'eski'=>strtoupper(substr($talep->gidiskalkishavalimani??'',0,3)), 'yeni'=>'—'],
                ['alan'=>'Gidiş Varış',   'eski'=>strtoupper(substr($talep->gidisvarishavalimani??'',0,3)),  'yeni'=>'—'],
            ];

            // Yeni sistemdeki ilk segment
            if($yeniTalep && isset($yeniTeklifler)) {
                $yeniSegmentler = DB::table('flight_segments')->where('request_id', $yeniTalep->id)->orderBy('id')->get();
                if($yeniSegmentler->isNotEmpty()) {
                    $ilkSeg = $yeniSegmentler->first();
                    $karsilastirmalar[5]['yeni'] = $ilkSeg->from_iata ?? '—';
                    $karsilastirmalar[6]['yeni'] = $ilkSeg->to_iata ?? '—';
                }
            }

            // Fiyat karşılaştırması
            $yeniToplamFiyat = '—';
            if($yeniTeklifler->isNotEmpty()) {
                $kabul = $yeniTeklifler->firstWhere('is_accepted', 1) ?? $yeniTeklifler->first();
                $yeniToplamFiyat = number_format($kabul->total_price ?? 0).' '.($kabul->currency ?? 'TRY');
            }
            $karsilastirmalar[] = ['alan'=>'Toplam Fiyat', 'eski'=>number_format($toplamFiyat).' '.($talep->parabirimi??'TL'), 'yeni'=>$yeniToplamFiyat];
            @endphp
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr style="background:#0d1b2a;">
                            <th class="ps-3" width="25%">Alan</th>
                            <th width="35%">Eski Sistem</th>
                            <th width="35%">Yeni Sistem</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($karsilastirmalar as $k)
                    @php
                        $esit = strtolower(trim($k['eski'])) === strtolower(trim($k['yeni']));
                        $yeniYok = $k['yeni'] === '—' || $k['yeni'] === '';
                        $rowClass = $yeniYok ? 'esle-yok' : ($esit ? 'esle-ok' : 'esle-fark');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="ps-3 text-muted">{{ $k['alan'] }}</td>
                        <td>{{ $k['eski'] }}</td>
                        <td>{{ $k['yeni'] }}</td>
                        <td class="text-center">
                            @if($yeniYok) <span title="Yeni sistemde girilmemiş">⚠️</span>
                            @elseif($esit) <span title="Eşleşiyor">✅</span>
                            @else <span title="Farklı">❌</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 small text-muted">
                <span class="me-3">✅ Eşleşiyor</span>
                <span class="me-3">❌ Farklı</span>
                <span>⚠️ Yeni sistemde girilmemiş</span>
            </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        {{-- Sol --}}
        <div class="col-lg-6">

            <div class="card mb-3">
                <div class="card-header fw-bold py-2">📋 Eski Sistem — Talep Bilgileri</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tr><th class="ps-3" width="38%">Acenta</th><td>{{ strtoupper($talep->acentaadi ?? '—') }}</td></tr>
                        <tr><th class="ps-3">E-posta</th><td><a href="mailto:{{ $talep->email }}">{{ $talep->email }}</a></td></tr>
                        <tr><th class="ps-3">Telefon</th><td>{{ $talep->telefon }}
                            @if($talep->telefon)
                            <a href="https://api.whatsapp.com/send?phone={{ $talep->telefon }}" target="_blank" class="ms-2 text-success"><i class="bi bi-whatsapp"></i></a>
                            @endif
                        </td></tr>
                        <tr><th class="ps-3">Grup Firma</th><td>{{ strtoupper($talep->grupfirmabilgisi ?? '—') }}</td></tr>
                        <tr><th class="ps-3">Uçuş Amacı</th><td>{{ $talep->ucusamaci ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Kişi Sayısı</th><td>
                            @if(!empty($talep->pax) && $talep->pax != $talep->kisisayisi)
                                <s class="text-danger">{{ $talep->kisisayisi }}</s> → <strong>{{ $talep->pax }}</strong>
                            @else {{ $talep->kisisayisi }} @endif
                        </td></tr>
                        <tr><th class="ps-3">İşlemi Yapan</th><td>{{ $talep->mesajiyazan ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Talep Tarihi</th><td>{{ $talep->islemtarihi ? \Carbon\Carbon::parse($talep->islemtarihi)->format('d.m.Y H:i') : '—' }}</td></tr>
                        <tr><th class="ps-3">Son Güncelleme</th><td>{{ $talep->updated_at ? \Carbon\Carbon::parse($talep->updated_at)->format('d.m.Y H:i') : '—' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header fw-bold py-2">✈️ Uçuş Bilgileri</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tr><th class="ps-3" width="38%">Gidiş Tarihi</th><td>{{ $talep->gidiszamani ? \Carbon\Carbon::parse($talep->gidiszamani)->format('d.m.Y') : '—' }}</td></tr>
                        <tr><th class="ps-3">Gidiş Saatleri</th><td>{{ $talep->gidissaat1 }}:00 — {{ $talep->gidissaat2 }}:00</td></tr>
                        <tr><th class="ps-3">Gidiş Parkur</th><td>{{ strtoupper(substr($talep->gidiskalkishavalimani??'',0,3)) }} → {{ strtoupper(substr($talep->gidisvarishavalimani??'',0,3)) }}<br><small class="text-muted">{{ $talep->gidiskalkishavalimani }} — {{ $talep->gidisvarishavalimani }}</small></td></tr>
                        @if($yon === 'GİDİŞ DÖNÜŞ')
                        <tr><th class="ps-3">Dönüş Tarihi</th><td>{{ $talep->donuszamani ? \Carbon\Carbon::parse($talep->donuszamani)->format('d.m.Y') : '—' }}</td></tr>
                        <tr><th class="ps-3">Dönüş Saatleri</th><td>{{ $talep->donussaat1 }}:00 — {{ $talep->donussaat2 }}:00</td></tr>
                        <tr><th class="ps-3">Dönüş Parkur</th><td>{{ strtoupper(substr($talep->donuskalkishavalimani??'',0,3)) }} → {{ strtoupper(substr($talep->donusvarishavalimani??'',0,3)) }}<br><small class="text-muted">{{ $talep->donuskalkishavalimani }} — {{ $talep->donusvarishavalimani }}</small></td></tr>
                        @endif
                        <tr><th class="ps-3">Havayolu Tercihi</th><td>{{ $talep->hangihavayolu ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Notlar</th><td>{{ $talep->notlar ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

        </div>

        {{-- Sağ --}}
        <div class="col-lg-6">

            <div class="card mb-3">
                <div class="card-header fw-bold py-2">💰 Fiyat & Opsiyon</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tr><th class="ps-3" width="42%">Para Birimi</th><td>{{ $talep->parabirimi ?? 'TL' }}</td></tr>
                        <tr><th class="ps-3">Toplam Fiyat</th><td><strong>{{ number_format($toplamFiyat) }} {{ $talep->parabirimi }}</strong></td></tr>
                        <tr><th class="ps-3">GT Kazancı</th><td>{{ number_format($talep->kazanc ?? 0) }} {{ $talep->parabirimi }}</td></tr>
                        <tr><th class="ps-3">Kişi Başı Ort.</th><td>{{ number_format($kisiBasi) }} {{ $talep->parabirimi }}</td></tr>
                        <tr><th class="ps-3">Depozito Oranı</th><td>%{{ $talep->depozitorani ?? 0 }}</td></tr>
                        <tr><th class="ps-3">Depozito Tutarı</th><td>{{ number_format($talep->depozitotutari ?? 0) }} {{ $talep->parabirimi }}</td></tr>
                        <tr><th class="ps-3">Opsiyon</th><td>
                            @if($opsiyonDt)
                                @if($opsiyonDt->isPast())
                                    <span class="text-danger fw-bold">{{ $opsiyonDt->format('d.m.Y H:i') }} ⚠️ GEÇTİ</span>
                                @else
                                    <span class="text-warning fw-bold">{{ $opsiyonDt->format('d.m.Y H:i') }}</span>
                                @endif
                            @else — @endif
                        </td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-3 border-primary">
                <div class="card-header fw-bold py-2 bg-primary text-white">📝 Havayolu Cevabı / Admin Notu</div>
                <div class="card-body p-0">
                    @if(!empty($talep->cevapmetni))
                        <div class="p-3" style="font-size:0.85rem; max-height:420px; overflow-y:auto; background:#0d1b2a; border-radius:0 0 .375rem .375rem;">
                            <pre>{{ strip_tags(html_entity_decode($talep->cevapmetni)) }}</pre>
                        </div>
                    @else
                        <p class="text-muted p-3 mb-0">Cevap metni yok.</p>
                    @endif
                </div>
            </div>

            @if(!empty(trim($talep->ozelnot ?? '')))
            <div class="card mb-3 border-danger">
                <div class="card-header fw-bold py-2 bg-danger text-white">🔒 Özel Not</div>
                <div class="card-body" style="background:#1a0000;">
                    <pre class="text-warning" style="font-size:0.85rem;">{{ $talep->ozelnot }}</pre>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Yeni sistemdeki teklifler --}}
    @if($yeniTalep && $yeniTeklifler->isNotEmpty())
    <div class="card mb-3 border-success">
        <div class="card-header fw-bold py-2 bg-success text-white">📊 Yeni Sistemdeki Teklifler</div>
        <div class="table-responsive">
            <table class="table table-sm table-dark mb-0">
                <thead><tr>
                    <th class="ps-3">Havayolu</th><th>PNR</th><th>Sefer</th>
                    <th>Kalkış–Varış</th><th>Pax</th><th>Kişi Başı</th>
                    <th>Toplam</th><th>Opsiyon</th><th>Durum</th>
                </tr></thead>
                <tbody>
                @foreach($yeniTeklifler as $t)
                <tr>
                    <td class="ps-3">{{ $t->airline ?? '—' }}</td>
                    <td><span class="badge bg-primary">{{ $t->airline_pnr ?? '—' }}</span></td>
                    <td>{{ $t->flight_number ?? '—' }}</td>
                    <td>{{ $t->flight_departure_time ?? '—' }} → {{ $t->flight_arrival_time ?? '—' }}</td>
                    <td>{{ $t->pax_confirmed ?? '—' }}</td>
                    <td>{{ $t->price_per_pax ? number_format($t->price_per_pax).' '.$t->currency : '—' }}</td>
                    <td>{{ $t->total_price ? number_format($t->total_price).' '.$t->currency : '—' }}</td>
                    <td>{{ $t->option_date ?? '—' }}</td>
                    <td>
                        @if($t->is_accepted) <span class="badge bg-success">Kabul</span>
                        @elseif(!$t->is_visible) <span class="badge bg-secondary">Gizli</span>
                        @else <span class="badge bg-secondary">Açık</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Yeni sistemdeki ödemeler --}}
    @if($yeniTalep && $yeniOdemeler->isNotEmpty())
    <div class="card mb-3 border-warning">
        <div class="card-header fw-bold py-2 bg-warning text-dark">💳 Yeni Sistemdeki Ödemeler</div>
        <div class="table-responsive">
            <table class="table table-sm table-dark mb-0">
                <thead><tr>
                    <th class="ps-3">Sıra</th><th>Tip</th><th>Yöntem</th>
                    <th>Banka</th><th>Tutar</th><th>Tarih</th><th>Durum</th>
                </tr></thead>
                <tbody>
                @foreach($yeniOdemeler as $o)
                <tr>
                    <td class="ps-3">{{ $o->sequence }}</td>
                    <td>{{ strtoupper($o->payment_type ?? '—') }}</td>
                    <td>{{ $o->payment_method ?? '—' }}</td>
                    <td>{{ $o->bank_name ?? '—' }}</td>
                    <td><strong>{{ number_format($o->amount) }} {{ $o->currency }}</strong></td>
                    <td>{{ $o->payment_date ?? '—' }}</td>
                    <td><span class="badge {{ $o->status==='alindi'?'bg-success':'bg-warning text-dark' }}">{{ $o->status }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Yeni sistemde ara --}}
    <div class="pb-4 mt-2">
        <form id="ara-form" class="d-flex gap-2" style="max-width:360px;">
            <input type="text" id="ara-gtpnr" class="form-control text-uppercase bg-dark text-white border-secondary" placeholder="Başka GTPNR ara..." autocomplete="off">
            <button class="btn btn-warning px-4" type="submit">Ara</button>
        </form>
    </div>
</div>

<script>
document.getElementById('ara-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const val = document.getElementById('ara-gtpnr').value.trim().toUpperCase();
    if (val) window.location.href = '{{ url("admin/eski-sistem") }}/' + encodeURIComponent(val);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('admin.partials.theme-script')
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eski Sistem: {{ strtoupper($talep->gtpnr) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .table th { font-size: 0.78rem; color: #6c757d; font-weight: 600; }
        .table td { font-size: 0.875rem; vertical-align: middle; }
        pre { font-family: 'Segoe UI', sans-serif; margin: 0; }
    </style>
</head>
<body>

<x-navbar-admin active="" />

@php
$durumlar = [
    '0' => ['label' => 'BEKLEMEDE',       'class' => 'bg-warning text-dark'],
    '1' => ['label' => 'İŞLEMDE',         'class' => 'bg-info text-dark'],
    '2' => ['label' => 'FİYATLANDIRILDI','class' => 'bg-primary'],
    '3' => ['label' => 'İPTAL',            'class' => 'bg-danger'],
    '4' => ['label' => 'BİLETLENDİ',      'class' => 'bg-success'],
    '5' => ['label' => 'DEPOZİTODA',      'class' => 'bg-secondary'],
];
$d = $durumlar[$talep->islemdurumu] ?? ['label' => $talep->islemdurumu, 'class' => 'bg-secondary'];

$talepTipleri = ['UGT'=>'UÇAK GRUP','TGT'=>'TEKNE GRUP','TKT'=>'TEKNE KİRALAMA','UKT'=>'UÇAK KİRALAMA','OGT'=>'OTEL GRUP'];
$talepTipi = $talepTipleri[$talep->taleptipi] ?? $talep->taleptipi;

$yonler = ['1'=>'TEK YÖN','2'=>'GİDİŞ DÖNÜŞ'];
$yon = $yonler[$talep->transfertipi1] ?? '—';

$opsiyonDt = null;
if (!empty($talep->opsiyontarihi) && !empty($talep->opsiyonsaati)) {
    try { $opsiyonDt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $talep->opsiyontarihi . ' ' . $talep->opsiyonsaati); } catch(\Throwable $e){}
}

$pax = (!empty($talep->pax) && $talep->pax != $talep->kisisayisi) ? $talep->pax : $talep->kisisayisi;
$toplamFiyat = ($talep->toplamodeme ?? 0) + ($talep->kazanc ?? 0);
$kisiBasi = $pax > 0 ? ceil($toplamFiyat / $pax) : 0;
@endphp

<div class="container-fluid py-3 px-4">

    {{-- Başlık --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
        <a href="{{ route('admin.eski-sistem') }}" class="btn btn-outline-secondary btn-sm">← Geri</a>
        <h4 class="mb-0 fw-bold">🗂 {{ strtoupper($talep->gtpnr) }}</h4>
        <span class="badge {{ $d['class'] }} fs-6 px-3">{{ $d['label'] }}</span>
        <span class="badge bg-dark">{{ $talepTipi }}</span>
        <span class="badge bg-secondary">{{ $yon }}</span>
        <span class="badge bg-light text-dark border">ID: {{ $talep->id }}</span>
    </div>

    <div class="row g-3">

        {{-- Sol --}}
        <div class="col-lg-6">

            <div class="card mb-3 shadow-sm">
                <div class="card-header fw-bold py-2">📋 Talep Bilgileri</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tr><th class="ps-3" width="38%">Acenta</th><td>{{ strtoupper($talep->acentaadi ?? '—') }}</td></tr>
                        <tr><th class="ps-3">E-posta</th><td><a href="mailto:{{ $talep->email }}">{{ $talep->email }}</a></td></tr>
                        <tr><th class="ps-3">Telefon</th><td>
                            {{ $talep->telefon }}
                            @if($talep->telefon)
                            <a href="https://api.whatsapp.com/send?phone={{ $talep->telefon }}" target="_blank" class="ms-2 text-success"><i class="bi bi-whatsapp"></i></a>
                            @endif
                        </td></tr>
                        <tr><th class="ps-3">Grup Firma</th><td>{{ strtoupper($talep->grupfirmabilgisi ?? '—') }}</td></tr>
                        <tr><th class="ps-3">Uçuş Amacı</th><td>{{ $talep->ucusamaci ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Kişi Sayısı</th><td>
                            @if(!empty($talep->pax) && $talep->pax != $talep->kisisayisi)
                                <s class="text-danger">{{ $talep->kisisayisi }}</s> → <strong>{{ $talep->pax }}</strong>
                            @else
                                {{ $talep->kisisayisi }}
                            @endif
                        </td></tr>
                        <tr><th class="ps-3">İşlemi Yapan</th><td>{{ $talep->mesajiyazan ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Talep Tarihi</th><td>{{ $talep->islemtarihi ? \Carbon\Carbon::parse($talep->islemtarihi)->format('d.m.Y H:i') : '—' }}</td></tr>
                        <tr><th class="ps-3">Son Güncelleme</th><td>{{ $talep->updated_at ? \Carbon\Carbon::parse($talep->updated_at)->format('d.m.Y H:i') : '—' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-3 shadow-sm">
                <div class="card-header fw-bold py-2">✈️ Uçuş Bilgileri</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tr><th class="ps-3" width="38%">Gidiş Tarihi</th><td>{{ $talep->gidiszamani ? \Carbon\Carbon::parse($talep->gidiszamani)->format('d.m.Y') : '—' }}</td></tr>
                        <tr><th class="ps-3">Gidiş Saatleri</th><td>{{ $talep->gidissaat1 }} — {{ $talep->gidissaat2 }}</td></tr>
                        <tr><th class="ps-3">Gidiş Kalkış</th><td>{{ $talep->gidiskalkishavalimani ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Gidiş Varış</th><td>{{ $talep->gidisvarishavalimani ?? '—' }}</td></tr>
                        @if($yon === 'GİDİŞ DÖNÜŞ')
                        <tr><th class="ps-3">Dönüş Tarihi</th><td>{{ $talep->donuszamani ? \Carbon\Carbon::parse($talep->donuszamani)->format('d.m.Y') : '—' }}</td></tr>
                        <tr><th class="ps-3">Dönüş Saatleri</th><td>{{ $talep->donussaat1 }} — {{ $talep->donussaat2 }}</td></tr>
                        <tr><th class="ps-3">Dönüş Kalkış</th><td>{{ $talep->donuskalkishavalimani ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Dönüş Varış</th><td>{{ $talep->donusvarishavalimani ?? '—' }}</td></tr>
                        @endif
                        <tr><th class="ps-3">Havayolu Tercihi</th><td>{{ $talep->hangihavayolu ?? '—' }}</td></tr>
                        <tr><th class="ps-3">Notlar</th><td>{{ $talep->notlar ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

        </div>

        {{-- Sağ --}}
        <div class="col-lg-6">

            <div class="card mb-3 shadow-sm">
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
                                    <span class="text-success fw-bold">{{ $opsiyonDt->format('d.m.Y H:i') }}</span>
                                @endif
                            @else —
                            @endif
                        </td></tr>
                    </table>
                </div>
            </div>

            {{-- Cevap Metni --}}
            <div class="card mb-3 shadow-sm border-primary">
                <div class="card-header fw-bold py-2 bg-primary text-white">📝 Havayolu Cevabı / Admin Notu</div>
                <div class="card-body p-0">
                    @if(!empty($talep->cevapmetni))
                        <div class="bg-dark text-white p-3" style="font-size:0.85rem; white-space:pre-wrap; max-height:420px; overflow-y:auto; border-radius:0 0 .375rem .375rem;">{{ strip_tags(html_entity_decode($talep->cevapmetni)) }}</div>
                    @else
                        <p class="text-muted p-3 mb-0">Cevap metni yok.</p>
                    @endif
                </div>
            </div>

            {{-- Özel Not --}}
            @if(!empty(trim($talep->ozelnot ?? '')))
            <div class="card mb-3 shadow-sm border-danger">
                <div class="card-header fw-bold py-2 bg-danger text-white">🔒 Özel Not (Ekip İçi)</div>
                <div class="card-body bg-dark" style="border-radius:0 0 .375rem .375rem;">
                    <pre class="text-warning mb-0" style="font-size:0.85rem; white-space:pre-wrap;">{{ $talep->ozelnot }}</pre>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Yeni arama --}}
    <div class="mt-2 pb-4">
        <form id="yeni-ara-form" class="d-flex gap-2" style="max-width:360px;">
            <input type="text" id="yeni-gtpnr" class="form-control text-uppercase" placeholder="Başka GTPNR ara..." autocomplete="off">
            <button class="btn btn-warning px-4" type="submit">Ara</button>
        </form>
    </div>

</div>

<script>
document.getElementById('yeni-ara-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const val = document.getElementById('yeni-gtpnr').value.trim().toUpperCase();
    if (val) window.location.href = '{{ url("admin/eski-sistem") }}/' + encodeURIComponent(val);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

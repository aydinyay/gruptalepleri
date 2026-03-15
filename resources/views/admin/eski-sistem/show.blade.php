@extends('layouts.admin')

@section('content')
@php
$durumlar = [
    '0' => ['label' => 'BEKLEMEDE',      'class' => 'bg-warning text-dark'],
    '1' => ['label' => 'İŞLEMDE',        'class' => 'bg-info text-dark'],
    '2' => ['label' => 'FİYATLANDIRILDI','class' => 'bg-primary'],
    '3' => ['label' => 'İPTAL',           'class' => 'bg-danger'],
    '4' => ['label' => 'BİLETLENDİ',     'class' => 'bg-success'],
    '5' => ['label' => 'DEPOZİTODA',     'class' => 'bg-purple text-white'],
];
$d = $durumlar[$talep->islemdurumu] ?? ['label' => $talep->islemdurumu, 'class' => 'bg-secondary'];

$talepTipleri = [
    'UGT' => 'UÇAK GRUP',
    'TGT' => 'TEKNE GRUP',
    'TKT' => 'TEKNE KİRALAMA',
    'UKT' => 'UÇAK KİRALAMA',
    'OGT' => 'OTEL GRUP',
];
$talepTipi = $talepTipleri[$talep->taleptipi] ?? $talep->taleptipi;

$yonler = ['1' => 'TEK YÖN', '2' => 'GİDİŞ DÖNÜŞ'];
$yon = $yonler[$talep->transfertipi1] ?? '—';

$opsiyonTarihSaat = null;
if ($talep->opsiyontarihi && $talep->opsiyonsaati) {
    try {
        $opsiyonTarihSaat = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $talep->opsiyontarihi . ' ' . $talep->opsiyonsaati);
    } catch (\Throwable $e) {}
}

$pax = (!empty($talep->pax) && $talep->pax != $talep->kisisayisi) ? $talep->pax : $talep->kisisayisi;
$toplamFiyat = ($talep->toplamodeme ?? 0) + ($talep->kazanc ?? 0);
$kisiBasi = $pax > 0 ? ceil($toplamFiyat / $pax) : 0;
@endphp

<div class="container-fluid py-3">

    {{-- Üst başlık --}}
    <div class="d-flex align-items-center gap-3 mb-3">
        <a href="{{ route('admin.eski-sistem') }}" class="btn btn-outline-secondary btn-sm">← Geri</a>
        <h4 class="mb-0 fw-bold">🗂 {{ strtoupper($talep->gtpnr) }}</h4>
        <span class="badge {{ $d['class'] }} fs-6">{{ $d['label'] }}</span>
        <span class="badge bg-secondary">{{ $talepTipi }}</span>
        <span class="badge bg-dark">{{ $yon }}</span>
    </div>

    <div class="row g-3">

        {{-- Sol kolon --}}
        <div class="col-lg-6">

            {{-- Talep bilgileri --}}
            <div class="card mb-3">
                <div class="card-header fw-bold py-2">📋 Talep Bilgileri</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tr><th class="text-muted ps-3" width="40%">Acenta</th><td>{{ strtoupper($talep->acentaadi ?? '—') }}</td></tr>
                        <tr><th class="text-muted ps-3">E-posta</th><td>
                            <a href="mailto:{{ $talep->email }}">{{ $talep->email }}</a>
                        </td></tr>
                        <tr><th class="text-muted ps-3">Telefon</th><td>
                            {{ $talep->telefon }}
                            @if($talep->telefon)
                            <a href="https://api.whatsapp.com/send?phone={{ $talep->telefon }}" target="_blank" class="ms-2 text-success"><i class="bi bi-whatsapp"></i></a>
                            @endif
                        </td></tr>
                        <tr><th class="text-muted ps-3">Grup Firma</th><td>{{ strtoupper($talep->grupfirmabilgisi ?? '—') }}</td></tr>
                        <tr><th class="text-muted ps-3">Uçuş Amacı</th><td>{{ $talep->ucusamaci ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-3">Kişi Sayısı</th><td>
                            @if(!empty($talep->pax) && $talep->pax != $talep->kisisayisi)
                                <s class="text-danger">{{ $talep->kisisayisi }}</s> → <strong>{{ $talep->pax }}</strong>
                            @else
                                {{ $talep->kisisayisi }}
                            @endif
                        </td></tr>
                        <tr><th class="text-muted ps-3">İşlemi Yapan</th><td>{{ $talep->mesajiyazan ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-3">Talep Tarihi</th><td>{{ $talep->islemtarihi ? \Carbon\Carbon::parse($talep->islemtarihi)->format('d.m.Y H:i') : '—' }}</td></tr>
                        <tr><th class="text-muted ps-3">Son Güncelleme</th><td>{{ $talep->updated_at ? \Carbon\Carbon::parse($talep->updated_at)->format('d.m.Y H:i') : '—' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Uçuş bilgileri --}}
            <div class="card mb-3">
                <div class="card-header fw-bold py-2">✈️ Uçuş Bilgileri</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tr><th class="text-muted ps-3" width="40%">Gidiş Tarihi</th><td>{{ $talep->gidiszamani ? \Carbon\Carbon::parse($talep->gidiszamani)->format('d.m.Y') : '—' }}</td></tr>
                        <tr><th class="text-muted ps-3">Gidiş Saat Aralığı</th><td>{{ $talep->gidissaat1 }} — {{ $talep->gidissaat2 }}</td></tr>
                        <tr><th class="text-muted ps-3">Gidiş Kalkış</th><td>{{ $talep->gidiskalkishavalimani ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-3">Gidiş Varış</th><td>{{ $talep->gidisvarishavalimani ?? '—' }}</td></tr>
                        @if($yon === 'GİDİŞ DÖNÜŞ')
                        <tr><th class="text-muted ps-3">Dönüş Tarihi</th><td>{{ $talep->donuszamani ? \Carbon\Carbon::parse($talep->donuszamani)->format('d.m.Y') : '—' }}</td></tr>
                        <tr><th class="text-muted ps-3">Dönüş Saat Aralığı</th><td>{{ $talep->donussaat1 }} — {{ $talep->donussaat2 }}</td></tr>
                        <tr><th class="text-muted ps-3">Dönüş Kalkış</th><td>{{ $talep->donuskalkishavalimani ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-3">Dönüş Varış</th><td>{{ $talep->donusvarishavalimani ?? '—' }}</td></tr>
                        @endif
                        <tr><th class="text-muted ps-3">Havayolu Tercihi</th><td>{{ $talep->hangihavayolu ?? '—' }}</td></tr>
                        <tr><th class="text-muted ps-3">Notlar</th><td>{{ $talep->notlar ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

        </div>

        {{-- Sağ kolon --}}
        <div class="col-lg-6">

            {{-- Fiyat & Opsiyon --}}
            <div class="card mb-3">
                <div class="card-header fw-bold py-2">💰 Fiyat & Opsiyon</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <tr><th class="text-muted ps-3" width="45%">Para Birimi</th><td>{{ $talep->parabirimi ?? 'TL' }}</td></tr>
                        <tr><th class="text-muted ps-3">Toplam Fiyat</th><td><strong>{{ number_format($toplamFiyat) }} {{ $talep->parabirimi }}</strong></td></tr>
                        <tr><th class="text-muted ps-3">GT Kazancı</th><td>{{ number_format($talep->kazanc ?? 0) }} {{ $talep->parabirimi }}</td></tr>
                        <tr><th class="text-muted ps-3">Kişi Başı Ort.</th><td>{{ number_format($kisiBasi) }} {{ $talep->parabirimi }}</td></tr>
                        <tr><th class="text-muted ps-3">Depozito Oranı</th><td>%{{ $talep->depozitorani ?? 0 }}</td></tr>
                        <tr><th class="text-muted ps-3">Depozito Tutarı</th><td>{{ number_format($talep->depozitotutari ?? 0) }} {{ $talep->parabirimi }}</td></tr>
                        <tr>
                            <th class="text-muted ps-3">Opsiyon Tarihi</th>
                            <td>
                                @if($opsiyonTarihSaat)
                                    @if($opsiyonTarihSaat->isPast())
                                        <span class="text-danger fw-bold">{{ $opsiyonTarihSaat->format('d.m.Y H:i') }} ⚠️ GEÇTİ</span>
                                    @else
                                        <span class="text-success">{{ $opsiyonTarihSaat->format('d.m.Y H:i') }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Cevap Metni --}}
            <div class="card mb-3 border-primary">
                <div class="card-header fw-bold py-2 bg-primary text-white">📝 Havayolu Cevabı / Admin Notu</div>
                <div class="card-body">
                    @if(!empty($talep->cevapmetni))
                        <div class="bg-dark text-white rounded p-3" style="font-size:0.85rem; white-space:pre-wrap; max-height:400px; overflow-y:auto;">{{ strip_tags($talep->cevapmetni) }}</div>
                    @else
                        <p class="text-muted mb-0">Cevap metni yok.</p>
                    @endif
                </div>
            </div>

            {{-- Özel Not --}}
            @if(!empty($talep->ozelnot))
            <div class="card mb-3 border-danger">
                <div class="card-header fw-bold py-2 bg-danger text-white">🔒 Özel Not (Ekip İçi)</div>
                <div class="card-body bg-dark">
                    <pre class="text-warning mb-0" style="font-size:0.85rem; white-space:pre-wrap;">{{ $talep->ozelnot }}</pre>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Başka GTPNR ara --}}
    <div class="mt-2">
        <form id="yeni-ara-form" class="d-flex gap-2" style="max-width:400px;">
            <input type="text" id="yeni-gtpnr" class="form-control text-uppercase" placeholder="Başka GTPNR ara..." autocomplete="off">
            <button class="btn btn-warning" type="submit">Ara</button>
        </form>
    </div>
</div>

<script>
document.getElementById('yeni-ara-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const val = document.getElementById('yeni-gtpnr').value.trim().toUpperCase();
    if (val) window.location.href = '{{ url("admin/eski-sistem") }}/' + val;
});
</script>
@endsection

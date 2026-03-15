<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eski Sistem Arşivi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#1a1a2e; color:#fff; font-family:'Segoe UI',sans-serif; }
        .tablo td, .tablo th { font-size:0.78rem; vertical-align:middle; }
        .tablo tbody tr { cursor:pointer; }
        .tablo tbody tr:hover { background:#2a2a4e !important; }
        .opsiyon-var { color:#FFFF00; font-weight:700; }
        .opsiyon-bitti { color:#FF0000; font-weight:700; }
        .badge-durum-0 { background:#F0AD4E; color:#000; }
        .badge-durum-1 { background:#FFFF00; color:#000; }
        .badge-durum-2 { background:#33CCFF; color:#000; }
        .badge-durum-3 { background:#FF0000; color:#fff; }
        .badge-durum-4 { background:#5cb85c; color:#fff; }
        .badge-durum-5 { background:#d63384; color:#fff; }
        .ichat  { background:#ffc107; color:#000; font-size:0.65rem; padding:1px 5px; border-radius:3px; }
        .dishat { background:#198754; color:#fff; font-size:0.65rem; padding:1px 5px; border-radius:3px; }
        .filter-btn { font-size:0.75rem; }
    </style>
</head>
<body>

<x-navbar-admin active="" />

<div class="container-fluid py-3 px-3">

    <div class="d-flex align-items-center gap-3 mb-3">
        <h5 class="mb-0 text-warning fw-bold">🗂 Eski Sistem Arşivi</h5>
        <form class="d-flex gap-2 ms-auto" method="GET" action="{{ route('admin.eski-sistem') }}">
            <input type="hidden" name="opsis" value="{{ $opsis ?? 'guncel' }}">
            <input type="text" name="q" value="{{ $q ?? '' }}"
                   class="form-control form-control-sm bg-dark text-white border-secondary"
                   placeholder="GTPNR, acenta, email ara..." style="width:260px;">
            <button class="btn btn-warning btn-sm">Ara</button>
        </form>
    </div>

    @if(session('hata') || !empty($hata))
        <div class="alert alert-danger">{{ session('hata') ?? $hata }}</div>
    @endif

    @if(isset($counts))
    {{-- Filtre butonları --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @php
        $filters = [
            'guncel'         => ['label'=>'Güncel',          'class'=>'btn-secondary',   'count'=>$counts['guncel']],
            'hepsi'          => ['label'=>'Tümü',            'class'=>'btn-light text-dark','count'=>$counts['toplam']],
            'islemealinmamis'=> ['label'=>'Beklemede',       'class'=>'btn-warning',     'count'=>$counts['beklemede']],
            'beklemede'      => ['label'=>'İşlemde',         'class'=>'btn-info text-dark','count'=>$counts['islemde']],
            'bugunvesonrasi' => ['label'=>'Fiyatlandırıldı', 'class'=>'btn-primary',     'count'=>$counts['fiyatlandirıldi']],
            'depozito'       => ['label'=>'Depozitoda',      'class'=>'btn-danger',      'count'=>$counts['depozito']],
            'ok'             => ['label'=>'Biletlendi',      'class'=>'btn-success',     'count'=>$counts['biletlendi']],
            'olumsuz'        => ['label'=>'Olumsuz/İptal',   'class'=>'btn-dark',        'count'=>$counts['olumsuz']],
            'opsiyonbitmis'  => ['label'=>'Opsiyonu Bitti',  'class'=>'btn-outline-danger','count'=>$counts['opsiyonbitmis']],
        ];
        @endphp
        @foreach($filters as $key => $f)
        <a href="{{ route('admin.eski-sistem', ['opsis'=>$key, 'q'=>$q??'']) }}"
           class="btn btn-sm filter-btn {{ $f['class'] }} {{ ($opsis??'guncel')===$key ? 'opacity-100 fw-bold' : 'opacity-75' }} position-relative">
            {{ $f['label'] }}
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                {{ $f['count'] }}
            </span>
        </a>
        @endforeach
    </div>

    {{-- Tablo --}}
    @if($talepler->count() > 0)
    <div class="table-responsive">
        <table class="table table-dark table-sm table-hover tablo mb-2">
            <thead style="background:#0d6efd; font-size:0.72rem; text-transform:uppercase; letter-spacing:1px;">
                <tr>
                    <th>No/ID</th>
                    <th>GTPNR</th>
                    <th>Tür</th>
                    <th>Acenta</th>
                    <th>Yön</th>
                    <th>Pax</th>
                    <th>Gidiş Tarihi</th>
                    <th>Gidiş Parkur</th>
                    <th>Dönüş Tarihi</th>
                    <th>Dönüş Parkur</th>
                    <th>Opsiyon</th>
                    <th class="text-center">Durum</th>
                </tr>
            </thead>
            <tbody>
            @php $i = ($talepler->currentPage()-1)*$talepler->perPage()+1; @endphp
            @foreach($talepler as $r)
            @php
                $opsiyonHtml = '';
                if (!empty($r->opsiyontarihi) && strlen($r->opsiyontarihi) >= 10) {
                    try {
                        $saat = !empty($r->opsiyonsaati) ? $r->opsiyonsaati : '23:59';
                        $optDt = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $r->opsiyontarihi . ' ' . $saat);
                        if ($optDt->isFuture()) {
                            $diff = now()->diff($optDt);
                            $parts = [];
                            if ($diff->m) $parts[] = $diff->m.' ay';
                            if ($diff->d) $parts[] = $diff->d.' gün';
                            if ($diff->h) $parts[] = $diff->h.' sa';
                            $opsiyonHtml = '<span class="opsiyon-var">'.(implode(' ',$parts)?:'<1 sa').' kaldı</span><br><small class="text-muted">'.$optDt->format('d.m.Y H:i').'</small>';
                        } else {
                            $opsiyonHtml = '<span class="opsiyon-bitti">OPSİYON BİTTİ</span><br><small class="text-muted">'.$optDt->format('d.m.Y H:i').'</small>';
                        }
                    } catch(\Throwable $e) {}
                } elseif ($r->islemdurumu == '0') {
                    $opsiyonHtml = '<span class="text-warning">BEKLEMEDE</span>';
                } elseif ($r->islemdurumu == '4') {
                    $opsiyonHtml = '<span style="color:#5cb85c;">BİLETLENDİ</span>';
                }

                $pax = (!empty($r->pax) && $r->pax != $r->kisisayisi)
                    ? '<s style="color:#FF0000">'.$r->kisisayisi.'</s><br>'.$r->pax
                    : $r->kisisayisi;

                $isIchat = strpos($r->gidiskalkishavalimani,'Türkiye') !== false
                        && strpos($r->gidisvarishavalimani,'Türkiye') !== false;

                $talepTipleri = ['UGT'=>'UÇAK GRUP','TGT'=>'TEKNE GRUP','TKT'=>'TEKNE KİR.','UKT'=>'UÇAK KİR.','OGT'=>'OTEL GRUP'];
                $tur = $talepTipleri[$r->taleptipi] ?? $r->taleptipi;

                $yonler = ['1'=>'TEK YÖN','2'=>'GİDİŞ-DÖNÜŞ'];
                $yon = $yonler[$r->transfertipi1] ?? '—';

                $durumlar = ['0'=>'BEKLEMEDE','1'=>'İŞLEMDE','2'=>'FİYATLANDIRILDI','3'=>'İPTAL','4'=>'BİLETLENDİ','5'=>'DEPOZİTODA'];
                $durum = $durumlar[$r->islemdurumu] ?? $r->islemdurumu;
            @endphp
            <tr onclick="window.location='{{ route('admin.eski-sistem.show', $r->gtpnr) }}'">
                <td class="text-muted">{{ $i++ }}<br><small>{{ $r->id }}</small></td>
                <td><strong class="font-monospace text-warning">{{ strtoupper($r->gtpnr) }}</strong></td>
                <td>
                    {{ $tur }}<br>
                    <span class="{{ $isIchat ? 'ichat' : 'dishat' }}">{{ $isIchat ? 'İÇHAT' : 'DIŞHAT' }}</span>
                </td>
                <td>
                    <strong>{{ strtoupper($r->acentaadi) }}</strong><br>
                    <small class="text-muted">{{ strtolower($r->email) }}</small>
                </td>
                <td><small>{{ $yon }}</small></td>
                <td>{!! $pax !!}</td>
                <td>
                    <small>{{ $r->gidiszamani }}<br>{{ $r->gidissaat1 }}:00–{{ $r->gidissaat2 }}:00</small>
                </td>
                <td>
                    <strong>{{ strtoupper(substr($r->gidiskalkishavalimani,0,3)) }}–{{ strtoupper(substr($r->gidisvarishavalimani,0,3)) }}</strong>
                </td>
                <td>
                    @if($r->transfertipi1 == 2)
                        <small>{{ $r->donuszamani }}<br>{{ $r->donussaat1 }}:00–{{ $r->donussaat2 }}:00</small>
                    @endif
                </td>
                <td>
                    @if($r->transfertipi1 == 2)
                        <strong>{{ strtoupper(substr($r->donuskalkishavalimani,0,3)) }}–{{ strtoupper(substr($r->donusvarishavalimani,0,3)) }}</strong>
                    @endif
                </td>
                <td>{!! $opsiyonHtml !!}</td>
                <td class="text-center">
                    <span class="badge badge-durum-{{ $r->islemdurumu }}">{{ $durum }}</span>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">Toplam {{ $talepler->total() }} kayıt</small>
        {{ $talepler->links('pagination::bootstrap-5') }}
    </div>
    @else
        <div class="alert alert-warning">Sonuç bulunamadı.</div>
    @endif

    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <title>Bekleyen Bildirimler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>

@if(auth()->user()->role === 'superadmin')
    <x-navbar-superadmin active="bekleyen-bildirimler" />
@else
    <x-navbar-admin active="bekleyen-bildirimler" />
@endif

<div class="container-fluid py-3 px-3">

    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0 fw-bold"><i class="fas fa-bell me-2 text-warning"></i>Bekleyen Bildirimler</h5>
        <span class="text-muted small ms-1">{{ $simdi->format('d.m.Y H:i') }} itibarıyla · {{ $liste->count() }} kayıt</span>
        <a href="{{ request()->url() }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="fas fa-sync me-1"></i>Yenile
        </a>
    </div>

    @if($liste->isEmpty())
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Bekleyen bildirim yok.
        </div>
    @else
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size:.82rem;">
                <thead class="table-dark">
                    <tr>
                        <th style="width:110px;">Tarih</th>
                        <th style="width:100px;">Saat</th>
                        <th style="width:80px;">Kalan</th>
                        <th style="width:80px;">Tip</th>
                        <th style="width:110px;">GTPNR</th>
                        <th>Acente</th>
                        <th>Tutar</th>
                        <th>Gönderilecek</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($liste as $r)
                    @php
                        $gecti    = $r['tarih']->isPast();
                        $saatKaldi = (int) now()->diffInHours($r['tarih'], false);
                        $dakikaKaldi = (int) now()->diffInMinutes($r['tarih'], false);
                        $kritik   = !$gecti && $saatKaldi <= 6;
                        $yakin    = !$gecti && $saatKaldi <= 24;

                        $rowClass = match(true) {
                            $gecti           => 'table-danger',
                            $kritik          => 'table-warning',
                            $yakin           => 'table-info',
                            default          => '',
                        };

                        $kalanMetin = match(true) {
                            $gecti           => abs($saatKaldi) . 's geçti',
                            $saatKaldi < 1   => $dakikaKaldi . ' dk',
                            $saatKaldi < 24  => $saatKaldi . ' sa',
                            default          => floor($saatKaldi / 24) . ' gün',
                        };

                        $tipBadge = match($r['tip']) {
                            'opsiyon'  => '<span class="badge bg-info text-dark">⏳ Opsiyon</span>',
                            'odeme'    => '<span class="badge bg-success">💳 Ödeme</span>',
                            'gecikti'  => '<span class="badge bg-danger">⚠️ Gecikti</span>',
                            default    => '<span class="badge bg-secondary">' . $r['etiket'] . '</span>',
                        };

                        $adminUrl = route('admin.requests.show', $r['gtpnr'] ?? '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="fw-semibold">{{ $r['tarih']->format('d.m.Y') }}</td>
                        <td>{{ $r['tarih']->format('H:i') }}</td>
                        <td>
                            <span class="fw-bold {{ $gecti ? 'text-danger' : ($kritik ? 'text-warning' : '') }}">
                                {{ $kalanMetin }}
                            </span>
                        </td>
                        <td>{!! $tipBadge !!}</td>
                        <td>
                            @if($r['gtpnr'])
                                <a href="{{ $adminUrl }}" class="fw-bold text-decoration-none">{{ $r['gtpnr'] }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $r['acente'] ?? '—' }}</td>
                        <td class="fw-semibold">{{ $r['detay'] }}</td>
                        <td class="text-muted">{{ $r['gidecek'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="mt-3 p-3 bg-light rounded small text-muted">
        <strong>Açıklama:</strong>
        <span class="badge bg-info text-dark me-1">⏳ Opsiyon</span> Acente kararını bekliyor, vade öncesi SMS+Email gider. &nbsp;
        <span class="badge bg-success me-1">💳 Ödeme</span> Ödeme vadesi, vade öncesi otomatik SMS+Email+Push gider. &nbsp;
        <span class="badge bg-danger me-1">⚠️ Gecikti</span> Vade geçmiş, manuel müdahale gerekli.
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('admin.partials.theme-script')
</body>
</html>

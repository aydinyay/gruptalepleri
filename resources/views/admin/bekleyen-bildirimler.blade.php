<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <title>Bekleyen Bildirimler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .bildirim-satir { display:flex; align-items:center; gap:6px; white-space:nowrap; margin-bottom:4px; }
        .bildirim-satir:last-child { margin-bottom:0; }
        .bildirim-saat  { font-size:.80rem; min-width:80px; font-variant-numeric:tabular-nums; }
        .kanal-badge    { display:inline-flex; align-items:center; gap:3px; font-size:.75rem; font-weight:600; padding:2px 7px; border-radius:4px; letter-spacing:.02em; }
        .kanal-aktif-sms   { background:#198754; color:#fff; }
        .kanal-aktif-email { background:#0d6efd; color:#fff; }
        .kanal-aktif-push  { background:#fd7e14; color:#fff; }
        .kanal-pasif       { background:#dee2e6; color:#adb5bd; }
        .gecti-saat        { color:#dc3545; text-decoration:line-through; opacity:.65; }
        .countdown-cell    { font-variant-numeric: tabular-nums; }
    </style>
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
        <span class="text-muted small ms-1" id="simdi-label">{{ $simdi->format('d.m.Y H:i') }} itibarıyla · {{ $liste->count() }} kayıt</span>
        <span class="ms-auto d-flex align-items-center gap-2">
            <span class="text-muted small"><i class="fas fa-circle-notch fa-spin fa-xs me-1 text-success"></i>Otomatik yenileme: <span id="reload-countdown">60</span>s</span>
            <a href="{{ request()->url() }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sync me-1"></i>Yenile
            </a>
        </span>
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
                        <th style="width:100px;">Vade Tarihi</th>
                        <th style="width:55px;">Saat</th>
                        <th style="width:75px;" class="countdown-cell">Kalan</th>
                        <th style="width:80px;">Tip</th>
                        <th style="width:110px;">GTPNR</th>
                        <th>Acente</th>
                        <th style="width:130px;">Tutar</th>
                        <th>Bildirim Zamanları &amp; Kanallar</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($liste as $r)
                    @php
                        $gecti       = $r['tarih']->isPast();
                        $saatKaldi   = (int) now()->diffInHours($r['tarih'], false);
                        $dakikaKaldi = (int) now()->diffInMinutes($r['tarih'], false);
                        $kritik      = !$gecti && $saatKaldi <= 6;
                        $yakin       = !$gecti && $saatKaldi <= 24;

                        $rowClass = match(true) {
                            $gecti  => 'table-danger',
                            $kritik => 'table-warning',
                            $yakin  => 'table-info',
                            default => '',
                        };

                        $kalanMetin = match(true) {
                            $gecti          => abs($saatKaldi) . 's geçti',
                            $saatKaldi < 1  => $dakikaKaldi . ' dk',
                            $saatKaldi < 24 => $saatKaldi . ' sa',
                            default         => floor($saatKaldi / 24) . ' gün',
                        };

                        $tipBadge = match($r['tip']) {
                            'opsiyon' => '<span class="badge bg-info text-dark">⏳ Opsiyon</span>',
                            'odeme'   => '<span class="badge bg-success">💳 Ödeme</span>',
                            'gecikti' => '<span class="badge bg-danger">⚠️ Gecikti</span>',
                            default   => '<span class="badge bg-secondary">' . e($r['etiket']) . '</span>',
                        };

                        $adminUrl = route('admin.requests.show', $r['gtpnr'] ?? '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="fw-semibold">{{ $r['tarih']->format('d.m.Y') }}</td>
                        <td>{{ $r['tarih']->format('H:i') }}</td>
                        <td class="countdown-cell">
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
                        <td>
                            @if($r['bildirimler']->isEmpty())
                                <span class="text-muted">—</span>
                            @else
                                @foreach($r['bildirimler'] as $b)
                                    <div class="bildirim-satir">
                                        {{-- Gönderim saati --}}
                                        <span class="bildirim-saat {{ $b['gecti'] ? 'gecti-saat' : 'fw-semibold' }}" title="{{ $b['label'] }}">
                                            {{ $b['saat']->format('d.m H:i') }}
                                        </span>
                                        {{-- Kanal rozetleri --}}
                                        @if($b['email'])
                                            <span class="kanal-badge kanal-aktif-email"><i class="fas fa-envelope"></i> Email</span>
                                        @endif
                                        @if($b['sms'])
                                            <span class="kanal-badge kanal-aktif-sms"><i class="fas fa-comment-sms"></i> SMS</span>
                                        @endif
                                        @if($b['push'])
                                            <span class="kanal-badge kanal-aktif-push"><i class="fas fa-bell"></i> Push</span>
                                        @endif
                                        @if(!$b['email'] && !$b['sms'] && !$b['push'])
                                            <span class="kanal-badge kanal-pasif">kanal yok</span>
                                        @endif
                                        @if($b['gecti'])
                                            <i class="fas fa-check fa-xs text-success ms-1" title="Gönderildi / geçti"></i>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="mt-3 p-3 bg-light rounded small text-muted">
        <strong>Açıklama:</strong>
        <span class="badge bg-info text-dark me-1">⏳ Opsiyon</span> Acente kararını bekliyor. &nbsp;
        <span class="badge bg-success me-1">💳 Ödeme</span> Ödeme vadesi. &nbsp;
        <span class="badge bg-danger me-1">⚠️ Gecikti</span> Vade geçmiş, manuel müdahale gerekli. &nbsp;&nbsp;
        Kanallar:
        <span class="kanal-badge kanal-aktif-email ms-1"><i class="fas fa-envelope"></i> Email</span>
        <span class="kanal-badge kanal-aktif-sms ms-1"><i class="fas fa-comment-sms"></i> SMS</span>
        <span class="kanal-badge kanal-aktif-push ms-1"><i class="fas fa-bell"></i> Push</span>
        — Opsiyon Uyarı Ayarları'ndan yönetilir.
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('admin.partials.theme-script')
<script>
    // 60 saniyede bir otomatik yenile
    let countdown = 60;
    const el = document.getElementById('reload-countdown');
    setInterval(() => {
        countdown--;
        if (el) el.textContent = countdown;
        if (countdown <= 0) {
            window.location.reload();
        }
    }, 1000);
</script>
</body>
</html>

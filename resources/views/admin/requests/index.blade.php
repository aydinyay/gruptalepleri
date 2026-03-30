<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Talepler — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ─── ORTAK ─── */
        body { font-family: 'Segoe UI', sans-serif; transition: background 0.2s, color 0.2s; }
        .tablo td, .tablo th { font-size: 0.79rem; vertical-align: middle; }
        .filter-btn { font-size: 0.75rem; padding: 3px 10px; }
        .ichat-badge  { background: #ffc107; color: #000; font-size: 0.63rem; padding: 1px 5px; border-radius: 3px; font-weight: 700; }
        .dishat-badge { background: #198754; color: #fff; font-size: 0.63rem; padding: 1px 5px; border-radius: 3px; font-weight: 700; }
        .opsiyon-var  { color: #FFFF00; font-weight: 700; }
        .opsiyon-bitti { color: #FF0000; font-weight: 700; }
        [data-theme="light"] .opsiyon-var { color: #d4a000; }
        .badge-beklemede       { background: #6c757d; color: #fff; }
        .badge-islemde         { background: #0d6efd; color: #fff; }
        .badge-fiyatlandirildi { background: #ffc107; color: #000; }
        .badge-depozitoda      { background: #6f42c1; color: #fff; }
        .badge-biletlendi      { background: #198754; color: #fff; }
        .badge-iade            { background: #dc3545; color: #fff; }
        .badge-olumsuz         { background: #343a40; color: #fff; }
        .sayfa-card { border-radius: 8px; overflow: hidden; }
        .filtre-bar { border-radius: 8px; padding: 10px 14px; }
    </style>
</head>
<body>

<x-navbar-admin active="talepler" />

<div class="container-fluid py-3 px-3">

    {{-- BAŞLIK + TEMA TOGGLE --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0 fw-bold" style="color:#0d6efd;">📋 Grup Talepleri</h5>
        <span class="text-muted small ms-1">{{ $talepler->total() }} sonuç</span>
        <a href="{{ route('admin.requests.create') }}" class="btn btn-success btn-sm ms-2">
            <i class="fas fa-plus me-1"></i>Yeni Talep
        </a>
        <div class="ms-auto d-flex gap-2 align-items-center">
            {{-- Arama --}}
            <form method="GET" action="{{ route('admin.requests.index') }}" class="d-flex gap-1" id="searchForm">
                <input type="hidden" name="durum" value="{{ request('durum','') }}">
                <input type="hidden" name="tarih_baslangic" value="{{ request('tarih_baslangic','') }}">
                <input type="hidden" name="tarih_bitis" value="{{ request('tarih_bitis','') }}">
                <input type="hidden" name="teklif" value="{{ request('teklif','') }}">
                <input type="hidden" name="opsiyon" value="{{ request('opsiyon','') }}">
                <input type="text" name="q" value="{{ request('q') }}"
                       class="form-control form-control-sm search-input"
                       placeholder="GTPNR, acente, tel ara..." style="width:230px;">
                <button class="btn btn-warning btn-sm">Ara</button>
                @if(request()->hasAny(['q','durum','tarih_baslangic','tarih_bitis','teklif','opsiyon']))
                <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-outline-secondary" title="Filtreyi temizle">
                    <i class="fas fa-times"></i>
                </a>
                @endif
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 mb-2">
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- FİLTRE BUTONLARI (hızlı — eski sistem stili) --}}
    @php
        $durumlar = [
            ''               => ['etiket' => 'Aktif',           'class' => 'btn-secondary',      'sayi' => $aktifSayisi],
            'tumu'           => ['etiket' => 'Tümü',            'class' => 'btn-light text-dark', 'sayi' => array_sum($durumSayilari->toArray())],
            'beklemede'      => ['etiket' => 'Beklemede',       'class' => 'btn-secondary',       'sayi' => $durumSayilari['beklemede'] ?? 0],
            'islemde'        => ['etiket' => 'İşlemde',         'class' => 'btn-info text-dark',  'sayi' => $durumSayilari['islemde'] ?? 0],
            'fiyatlandirildi'=> ['etiket' => 'Fiyatlandırıldı', 'class' => 'btn-warning text-dark','sayi'=> $durumSayilari['fiyatlandirildi'] ?? 0],
            'depozitoda'     => ['etiket' => 'Depozitoda',      'class' => 'btn-primary',         'sayi' => $durumSayilari['depozitoda'] ?? 0],
            'biletlendi'     => ['etiket' => 'Biletlendi',      'class' => 'btn-success',         'sayi' => $durumSayilari['biletlendi'] ?? 0],
            'iade'           => ['etiket' => 'İade',            'class' => 'btn-danger',          'sayi' => $durumSayilari['iade'] ?? 0],
            'olumsuz'        => ['etiket' => 'Olumsuz/İptal',   'class' => 'btn-dark',            'sayi' => $durumSayilari['olumsuz'] ?? 0],
        ];
        $mevcutDurum = request('durum', '');
    @endphp
    <div class="d-flex flex-wrap gap-2 mb-2">
        @foreach($durumlar as $key => $d)
        <a href="{{ route('admin.requests.index', array_filter([
            'durum' => $key,
            'q' => request('q'),
            'teklif' => request('teklif'),
            'opsiyon' => request('opsiyon'),
        ])) }}"
           class="btn btn-sm filter-btn {{ $d['class'] }} position-relative {{ $mevcutDurum === $key ? 'opacity-100 fw-bold' : 'opacity-75' }}">
            {{ $d['etiket'] }}
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.58rem;">
                {{ $d['sayi'] }}
            </span>
        </a>
        @endforeach

        {{-- Tarih filtresi --}}
        <form method="GET" action="{{ route('admin.requests.index') }}" class="d-flex gap-1 ms-auto align-items-center">
            <input type="hidden" name="durum" value="{{ request('durum','') }}">
            <input type="hidden" name="q" value="{{ request('q','') }}">
            <input type="hidden" name="teklif" value="{{ request('teklif','') }}">
            <input type="hidden" name="opsiyon" value="{{ request('opsiyon','') }}">
            <input type="date" name="tarih_baslangic" class="form-control form-control-sm search-input" value="{{ request('tarih_baslangic') }}" style="width:130px;" title="Başlangıç">
            <input type="date" name="tarih_bitis" class="form-control form-control-sm search-input" value="{{ request('tarih_bitis') }}" style="width:130px;" title="Bitiş">
            <button class="btn btn-sm btn-outline-secondary filter-btn"><i class="fas fa-calendar-alt"></i></button>
        </form>
    </div>

    {{-- TABLO --}}
    @php
        $allIatas = $talepler->getCollection()
            ->flatMap(fn($t) => $t->segments->flatMap(fn($s) => [$s->from_iata, $s->to_iata]))
            ->filter()->unique()->values();
        $turkishIatas = \App\Models\Airport::whereIn('iata', $allIatas)
            ->where('country_code', 'TR')->pluck('iata')->flip();
    @endphp

    @if($talepler->count() > 0)
    <div class="sayfa-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover tablo mb-0">
                <thead style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.8px;">
                    <tr>
                        <th>GTPNR</th>
                        <th>Acente</th>
                        <th>PAX</th>
                        <th>Gidiş</th>
                        <th>Dönüş</th>
                        <th>Opsiyon</th>
                        <th class="text-center">Durum</th>
                        <th>Talep Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                @php $i = ($talepler->currentPage()-1)*$talepler->perPage()+1; @endphp
                @foreach($talepler as $talep)
                @php
                    $segs = $talep->segments->sortBy('order');
                    $gidis = $segs->first();
                    $donus = $segs->count() > 1 ? $segs->last() : null;

                    // İç/dış hat
                    $allSegIatas = $segs->flatMap(fn($s) => [$s->from_iata, $s->to_iata])->filter(fn($i) => !empty($i))->unique();
                    $hasIatas = $allSegIatas->isNotEmpty();
                    $isIchat  = $hasIatas && $allSegIatas->every(fn($ii) => isset($turkishIatas[$ii]));

                    // Opsiyon geri sayım
                    $opsOffer = $talep->offers->firstWhere('is_accepted', true)
                        ?? $talep->offers->whereNotNull('option_date')->sortBy('option_date')->first();
                    $opsiyonHtml = '';
                    if ($opsOffer?->option_date) {
                        $optDt = \Carbon\Carbon::parse($opsOffer->option_date . ' ' . ($opsOffer->option_time ?? '23:59'));
                        if ($optDt->isFuture()) {
                            $diff  = now()->diff($optDt);
                            $parts = [];
                            if ($diff->m) $parts[] = $diff->m . ' ay';
                            if ($diff->d) $parts[] = $diff->d . ' gün';
                            if ($diff->h) $parts[] = $diff->h . ' sa';
                            $opsiyonHtml = '<span class="opsiyon-var">' . (implode(' ', $parts) ?: '<1 sa') . ' kaldı</span><br>'
                                . '<small class="text-muted">' . $optDt->format('d.m.Y H:i') . '</small>';
                        } else {
                            $opsiyonHtml = '<span class="opsiyon-bitti">OPSİYON BİTTİ</span><br>'
                                . '<small class="text-muted">' . $optDt->format('d.m.Y H:i') . '</small>';
                        }
                    }

                    // Durum
                    $durumEtiket = $durumlar[$talep->status]['etiket'] ?? $talep->status;
                    $durumClass  = 'badge-' . $talep->status;

                    // Gidiş parkur
                    $gidisStr = $gidis ? strtoupper($gidis->from_iata) . '–' . strtoupper($gidis->to_iata) : '—';
                    $gidisTarih = $gidis?->departure_date ? \Carbon\Carbon::parse($gidis->departure_date)->format('d.m.Y') : '';

                    // Dönüş parkur
                    $donusStr   = $donus ? strtoupper($donus->from_iata) . '–' . strtoupper($donus->to_iata) : '';
                    $donusTarih = $donus?->departure_date ? \Carbon\Carbon::parse($donus->departure_date)->format('d.m.Y') : '';

                    // Çok segmentli (2'den fazla)
                    if ($segs->count() > 2) {
                        $donusStr = $segs->count() . ' segment';
                        $donusTarih = '';
                    }
                @endphp
                <tr onclick="window.location='{{ route('admin.requests.show', $talep->gtpnr) }}'">
                    <td>
                        <strong class="font-monospace" style="color:#4db8ff;">{{ $talep->gtpnr }}</strong>
                    </td>
                    <td>
                        <strong>{{ $talep->agency_name }}</strong><br>
                        <small class="text-muted">{{ $talep->phone }}</small>
                    </td>
                    <td class="text-center fw-bold">{{ $talep->pax_total }}</td>
                    <td>
                        <strong>{{ $gidisStr }}</strong><br>
                        <small class="text-muted">{{ $gidisTarih }}</small>
                        @if($hasIatas)
                            <br><span class="{{ $isIchat ? 'ichat-badge' : 'dishat-badge' }}">{{ $isIchat ? 'İÇHAT' : 'DIŞHAT' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($donusStr)
                            <strong>{{ $donusStr }}</strong><br>
                            <small class="text-muted">{{ $donusTarih }}</small>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{!! $opsiyonHtml ?: '<span class="text-muted">—</span>' !!}</td>
                    <td class="text-center">
                        <span class="badge {{ $durumClass }}">{{ $durumEtiket }}</span>
                        <x-iade-badge :talep="$talep" />
                    </td>
                    <td class="text-muted">{{ $talep->created_at->format('d.m.Y') }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
        <small class="text-muted">Toplam {{ $talepler->total() }} kayıt</small>
        {{ $talepler->links('pagination::bootstrap-5') }}
    </div>

    @else
        <div class="alert alert-warning mt-2">Sonuç bulunamadı.</div>
    @endif

</div>

{{-- PUSH: Yeni talep toast bildirimleri --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999" id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('admin.partials.theme-script')
<script>
/* ─── PUSH: Yeni talep bildirimleri ─── */
(function() {
    let lastTs = new Date().toISOString();

    function pushToast(gtpnr, acente) {
        const id  = 'toast-' + Date.now();
        const html = `
        <div id="${id}" class="toast align-items-center text-bg-success border-0 mb-2" role="alert" data-bs-delay="8000">
            <div class="d-flex">
                <div class="toast-body">
                    🆕 Yeni talep: <strong>${gtpnr}</strong><br><small>${acente}</small>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
        document.getElementById('toast-container').insertAdjacentHTML('beforeend', html);
        const el = document.getElementById(id);
        new bootstrap.Toast(el).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    function pollYeniTalepler() {
        fetch('{{ route("admin.push.yeni-talepler") }}?since=' + encodeURIComponent(lastTs))
            .then(r => r.json())
            .then(data => {
                if (data.talepler && data.talepler.length > 0) {
                    data.talepler.forEach(t => pushToast(t.gtpnr, t.agency_name));
                }
                lastTs = data.ts;
            })
            .catch(() => {});
    }

    setInterval(pollYeniTalepler, 30000);
})();
</script>
</body>
</html>

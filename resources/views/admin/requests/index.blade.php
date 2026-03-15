<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Talepler — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .stat-card { border-left: 4px solid; border-radius: 6px; cursor: pointer; transition: transform 0.1s; }
        .stat-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body>

<x-navbar-admin active="talepler" />

<div class="container-fluid py-4 px-4">

    {{-- BAŞLIK --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">📋 Talepler</h4>
        <span class="text-muted small">{{ $talepler->total() }} sonuç</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- DURUM KARTLARI --}}
    @php
        $durumlar = [
            'beklemede'      => ['renk' => '#6c757d', 'etiket' => 'Beklemede',      'ikon' => 'fa-hourglass-half'],
            'islemde'        => ['renk' => '#0d6efd', 'etiket' => 'İşlemde',        'ikon' => 'fa-spinner'],
            'fiyatlandirıldi'=> ['renk' => '#ffc107', 'etiket' => 'Fiyatlandırıldı','ikon' => 'fa-tag'],
            'depozitoda'     => ['renk' => '#6f42c1', 'etiket' => 'Depozitoda',     'ikon' => 'fa-coins'],
            'biletlendi'     => ['renk' => '#198754', 'etiket' => 'Biletlendi',     'ikon' => 'fa-ticket-alt'],
            'iade'           => ['renk' => '#dc3545', 'etiket' => 'İade',           'ikon' => 'fa-undo'],
            'olumsuz'        => ['renk' => '#343a40', 'etiket' => 'Olumsuz',        'ikon' => 'fa-times-circle'],
        ];
    @endphp
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.requests.index') }}" class="text-decoration-none">
                <div class="card stat-card p-2 text-center h-100 {{ !request()->has('durum') ? 'bg-light' : '' }}" style="border-left-color:#0d6efd;">
                    <div class="small text-muted"><i class="fas fa-bolt me-1" style="color:#0d6efd;"></i>Aktif</div>
                    <div class="fw-bold fs-5">{{ $aktifSayisi }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.requests.index', ['durum' => 'tumu']) }}" class="text-decoration-none">
                <div class="card stat-card p-2 text-center h-100 {{ request('durum') === 'tumu' ? 'bg-light' : '' }}" style="border-left-color:#adb5bd;">
                    <div class="small text-muted"><i class="fas fa-archive me-1" style="color:#adb5bd;"></i>Tüm Arşiv</div>
                    <div class="fw-bold fs-5">{{ array_sum($durumSayilari->toArray()) }}</div>
                </div>
            </a>
        </div>
        @foreach($durumlar as $key => $d)
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.requests.index', array_merge(request()->query(), ['durum' => $key])) }}" class="text-decoration-none">
                <div class="card stat-card p-2 text-center h-100 {{ request('durum') === $key ? 'bg-light' : '' }}" style="border-left-color:{{ $d['renk'] }};">
                    <div class="small text-muted"><i class="fas {{ $d['ikon'] }} me-1" style="color:{{ $d['renk'] }};"></i>{{ $d['etiket'] }}</div>
                    <div class="fw-bold fs-5">{{ $durumSayilari[$key] ?? 0 }}</div>
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- FİLTRE ÇUBUĞU --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.requests.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">GTPNR / Acente / Telefon</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Ara..." value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Durum</label>
                    <select name="durum" class="form-select form-select-sm">
                        <option value="" {{ request('durum') === '' && !request()->has('durum') ? '' : '' }}>Aktif Talepler</option>
                        @foreach($durumlar as $key => $d)
                        <option value="{{ $key }}" {{ request('durum') === $key ? 'selected' : '' }}>{{ $d['etiket'] }}</option>
                        @endforeach
                        <option value="tumu" {{ request('durum') === 'tumu' ? 'selected' : '' }}>── Tümü (Arşiv dahil)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Başlangıç</label>
                    <input type="date" name="tarih_baslangic" class="form-control form-control-sm" value="{{ request('tarih_baslangic') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Bitiş</label>
                    <input type="date" name="tarih_bitis" class="form-control form-control-sm" value="{{ request('tarih_bitis') }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-filter me-1"></i>Filtrele
                    </button>
                    @if(request()->hasAny(['q','durum','tarih_baslangic','tarih_bitis']))
                    <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- TABLO --}}
    @php
        // Türk havalimanı IATA kodlarını tek sorguda al (iç hat tespiti için)
        $allIatas = $talepler->getCollection()
            ->flatMap(fn($t) => $t->segments->flatMap(fn($s) => [$s->from_iata, $s->to_iata]))
            ->filter()->unique()->values();
        $turkishIatas = \App\Models\Airport::whereIn('iata', $allIatas)
            ->where('country_code', 'TR')->pluck('iata')->flip();
    @endphp
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>GTPNR</th>
                        <th>Acente</th>
                        <th>Rota</th>
                        <th class="text-center">PAX</th>
                        <th>Durum</th>
                        <th>Opsiyon</th>
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($talepler as $talep)
                    @php
                        $renk = $durumlar[$talep->status]['renk'] ?? '#6c757d';

                        // İç hat / dış hat tespiti — tüm segmentlerdeki dolu IATA'lara bak
                        $firstSeg = $talep->segments->first();
                        $allSegIatas = $talep->segments
                            ->flatMap(fn($s) => [$s->from_iata, $s->to_iata])
                            ->filter(fn($i) => !empty($i))
                            ->unique()->values();
                        $hasIatas = $allSegIatas->isNotEmpty();
                        $isIchat = $hasIatas && $allSegIatas->every(fn($i) => isset($turkishIatas[$i]));

                        // Opsiyon geri sayım — kabul edilmiş teklif önce, sonra en son option_date'li
                        $opsOffer = $talep->offers->firstWhere('is_accepted', true)
                            ?? $talep->offers->whereNotNull('option_date')->sortByDesc('option_date')->first();
                        $opsCountdown = null;
                        if ($opsOffer?->option_date) {
                            $optDt = \Carbon\Carbon::parse(
                                $opsOffer->option_date . ' ' . ($opsOffer->option_time ?? '23:59')
                            );
                            $now = now();
                            if ($optDt->isFuture()) {
                                $diff = $now->diff($optDt);
                                $parts = [];
                                if ($diff->m) $parts[] = $diff->m . ' ay';
                                if ($diff->d) $parts[] = $diff->d . ' gün';
                                if ($diff->h) $parts[] = $diff->h . ' sa';
                                $opsCountdown = ['renk' => '#ffc107', 'text' => implode(' ', $parts ?: ['<1 sa']) . ' kaldı'];
                            } else {
                                $opsCountdown = ['renk' => '#dc3545', 'text' => 'OPSİYON BİTTİ'];
                            }
                        }
                    @endphp
                    <tr>
                        <td>
                            <strong class="font-monospace">{{ $talep->gtpnr }}</strong>
                        </td>
                        <td>
                            <div>
                                @if($talep->agency_name === 'MÜNFERİT')
                                    <span class="badge" style="background:#20c997;color:#fff;">MÜNFERİT</span>
                                @else
                                    {{ $talep->agency_name }}
                                @endif
                            </div>
                            @if($talep->phone)<div class="text-muted small">{{ $talep->phone }}</div>@endif
                        </td>
                        <td>
                            @foreach($talep->segments as $s)
                                <span class="badge bg-light text-dark border me-1">{{ $s->from_iata }}→{{ $s->to_iata }}</span>
                            @endforeach
                            <div class="d-flex align-items-center gap-1 mt-1">
                                @if($hasIatas)
                                    @if($isIchat)
                                        <span class="badge" style="background:#ffc107;color:#000;font-size:0.65rem;">İÇHAT</span>
                                    @else
                                        <span class="badge bg-success" style="font-size:0.65rem;">DIŞHAT</span>
                                    @endif
                                @endif
                                @if($firstSeg?->departure_date)
                                    <span class="text-muted" style="font-size:0.72rem;">{{ \Carbon\Carbon::parse($firstSeg->departure_date)->format('d M Y') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-center">{{ $talep->pax_total }}</td>
                        <td>
                            <span class="badge" style="background-color:{{ $renk }}; {{ $talep->status === 'fiyatlandirıldi' ? 'color:#000;' : '' }}">
                                {{ $durumlar[$talep->status]['etiket'] ?? $talep->status }}
                            </span>
                            <x-iade-badge :talep="$talep" />
                        </td>
                        <td style="min-width:100px;">
                            @if($opsCountdown)
                                <span class="fw-bold" style="color:{{ $opsCountdown['renk'] }};font-size:0.78rem;">
                                    {{ $opsCountdown['text'] }}
                                </span>
                                <div class="text-muted" style="font-size:0.68rem;">
                                    {{ \Carbon\Carbon::parse($opsOffer->option_date)->format('d.m.Y') }}
                                    @if($opsOffer->option_time) {{ substr($opsOffer->option_time,0,5) }}@endif
                                </div>
                            @else
                                <span class="text-muted" style="font-size:0.75rem;">—</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $talep->created_at->format('d.m.Y') }}</td>
                        <td>
                            <a href="{{ route('admin.requests.show', $talep->gtpnr) }}" class="btn btn-sm btn-outline-primary">
                                Detay <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            Sonuç bulunamadı.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $talepler->links() }}
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

{{-- PUSH: Yeni talep bildirimleri --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999" id="toast-container"></div>
<script>
(function() {
    let lastTs = new Date().toISOString();

    function pushToast(gtpnr, acente) {
        const id = 'toast-' + Date.now();
        const html = `
        <div id="${id}" class="toast align-items-center text-bg-success border-0 mb-2" role="alert" aria-live="assertive" data-bs-delay="8000">
            <div class="d-flex">
                <div class="toast-body">
                    🆕 Yeni talep: <strong>${gtpnr}</strong><br>
                    <small>${acente}</small>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
        document.getElementById('toast-container').insertAdjacentHTML('beforeend', html);
        const toastEl = document.getElementById(id);
        new bootstrap.Toast(toastEl).show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
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

    // Her 30 saniyede kontrol et
    setInterval(pollYeniTalepler, 30000);
})();
</script>
</body>
</html>

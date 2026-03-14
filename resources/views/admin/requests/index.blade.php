<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Talepler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .stat-card { border-left: 4px solid; border-radius: 6px; cursor: pointer; transition: transform 0.1s; }
        .stat-card:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
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
            'olumsuz'        => ['renk' => '#dc3545', 'etiket' => 'Olumsuz',        'ikon' => 'fa-times-circle'],
        ];
    @endphp
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <a href="{{ route('admin.requests.index') }}" class="text-decoration-none">
                <div class="card stat-card p-2 text-center h-100" style="border-left-color:#0d6efd;">
                    <div class="small text-muted">Toplam</div>
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
                        <option value="">Tümü</option>
                        @foreach($durumlar as $key => $d)
                        <option value="{{ $key }}" {{ request('durum') === $key ? 'selected' : '' }}>{{ $d['etiket'] }}</option>
                        @endforeach
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
                        <th>Tarih</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($talepler as $talep)
                    @php $renk = $durumlar[$talep->status]['renk'] ?? '#6c757d'; @endphp
                    <tr>
                        <td>
                            <strong class="font-monospace">{{ $talep->gtpnr }}</strong>
                        </td>
                        <td>
                            <div>{{ $talep->agency_name }}</div>
                            @if($talep->phone)<div class="text-muted small">{{ $talep->phone }}</div>@endif
                        </td>
                        <td>
                            @foreach($talep->segments as $s)
                                <span class="badge bg-light text-dark border me-1">{{ $s->from_iata }}→{{ $s->to_iata }}</span>
                            @endforeach
                            @if($talep->segments->first()?->departure_date)
                                <div class="text-muted small">{{ \Carbon\Carbon::parse($talep->segments->first()->departure_date)->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $talep->pax_total }}</td>
                        <td>
                            <span class="badge" style="background-color:{{ $renk }}; {{ $talep->status === 'fiyatlandirıldi' ? 'color:#000;' : '' }}">
                                {{ $durumlar[$talep->status]['etiket'] ?? $talep->status }}
                            </span>
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
                        <td colspan="7" class="text-center py-5 text-muted">
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

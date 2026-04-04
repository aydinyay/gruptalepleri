<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kampanyalar — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.durum-taslak    { background:#6c757d; }
.durum-aktif     { background:#198754; }
.durum-durduruldu{ background:#dc3545; }
.durum-tamamlandi{ background:#0d6efd; }
</style>
</head>
<body>
<x-navbar-superadmin active="kampanyalar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-layer-group me-2" style="color:#ffc107;"></i>Kampanyalar</h5>
                <p>Tüm email ve SMS kampanyaları</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('superadmin.sablonlar.index') }}" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-file-code me-1"></i>Şablonlar
                </a>
                <a href="{{ route('superadmin.kampanyalar.create') }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-plus me-1"></i>Yeni Kampanya
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if($kampanyalar->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="fas fa-layer-group fa-2x mb-2 d-block"></i>
            Henüz kampanya yok. <a href="{{ route('superadmin.kampanyalar.create') }}">İlk kampanyayı oluştur</a>
        </div>
    @else
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0" style="font-size:0.83rem;">
                <thead class="table-light">
                    <tr>
                        <th>Kampanya</th>
                        <th>Tip</th>
                        <th>Şablon</th>
                        <th>Durum</th>
                        <th class="text-center">Gönderilen</th>
                        <th class="text-center">Tıklama</th>
                        <th>Zamanlama</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kampanyalar as $k)
                    @php $ist = $istatistik[$k->id] ?? []; @endphp
                    <tr>
                        <td>
                            <a href="{{ route('superadmin.kampanyalar.show', $k) }}" class="fw-semibold text-decoration-none">
                                {{ $k->ad }}
                            </a>
                            <div class="text-muted" style="font-size:0.72rem;">{{ $k->etiket }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $k->tip === 'email' ? 'bg-danger' : 'bg-info text-dark' }}">
                                {{ $k->tip === 'email' ? '📧 Email' : '📱 SMS' }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $k->sablon?->ad ?? '—' }}</td>
                        <td>
                            <span class="badge durum-{{ $k->durum }}">{{ ucfirst($k->durum) }}</span>
                        </td>
                        <td class="text-center">{{ $ist['basarili'] ?? 0 }}</td>
                        <td class="text-center">
                            @if(($ist['basarili'] ?? 0) > 0)
                                {{ $ist['tiklanan'] ?? 0 }}
                                <span class="text-muted" style="font-size:0.75rem;">
                                    ({{ round((($ist['tiklanan'] ?? 0) / $ist['basarili']) * 100) }}%)
                                </span>
                            @else —
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:0.78rem;">
                            @php $z = $k->zamanlama ?? []; @endphp
                            {{ $z['baslangic'] ?? '' ?: '∞' }} → {{ $z['bitis'] ?? '' ?: '∞' }}<br>
                            @foreach(($z['slotlar'] ?? []) as $s)
                                <span class="badge bg-light text-dark border">{{ $s['saat'] }} / {{ $s['adet'] }}</span>
                            @endforeach
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                @if($k->durum === 'taslak' || $k->durum === 'durduruldu')
                                <form method="POST" action="{{ route('superadmin.kampanyalar.aktif', $k) }}">
                                    @csrf
                                    <button class="btn btn-success btn-sm py-0 px-2" title="Aktif et">▶</button>
                                </form>
                                @elseif($k->durum === 'aktif')
                                <form method="POST" action="{{ route('superadmin.kampanyalar.durdur', $k) }}">
                                    @csrf
                                    <button class="btn btn-warning btn-sm py-0 px-2" title="Durdur">⏸</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('superadmin.kampanyalar.destroy', $k) }}"
                                      onsubmit="return confirm('«{{ $k->ad }}» silinsin mi?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm py-0 px-2"><i class="fas fa-trash-alt fa-xs"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

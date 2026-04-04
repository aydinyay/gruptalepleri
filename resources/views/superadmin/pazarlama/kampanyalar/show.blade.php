<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $kampanya->ad }} — Kampanya</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.stat-card { border-radius:12px; padding:1rem 1.2rem; color:#fff; }
</style>
</head>
<body>
<x-navbar-superadmin active="kampanyalar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-layer-group me-2" style="color:#ffc107;"></i>{{ $kampanya->ad }}</h5>
                <p>{{ $kampanya->etiket }} · {{ $kampanya->tip === 'email' ? '📧 Email' : '📱 SMS' }}</p>
            </div>
            <div class="d-flex gap-2">
                @if($kampanya->durum === 'taslak' || $kampanya->durum === 'durduruldu')
                <form method="POST" action="{{ route('superadmin.kampanyalar.aktif', $kampanya) }}">
                    @csrf
                    <button class="btn btn-success btn-sm">▶ Aktif Et</button>
                </form>
                @elseif($kampanya->durum === 'aktif')
                <form method="POST" action="{{ route('superadmin.kampanyalar.durdur', $kampanya) }}">
                    @csrf
                    <button class="btn btn-warning btn-sm">⏸ Durdur</button>
                </form>
                @endif
                <a href="{{ route('superadmin.kampanyalar.index') }}" class="btn btn-sm btn-outline-light">← Geri</a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    {{-- İstatistik kartları --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card" style="background:#0d6efd;">
                <div style="font-size:1.8rem; font-weight:700;">{{ $istatistik['basarili'] }}</div>
                <div class="small">Gönderildi</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="background:#dc3545;">
                <div style="font-size:1.8rem; font-weight:700;">{{ $istatistik['basarisiz'] }}</div>
                <div class="small">Başarısız</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="background:#ff6b35;">
                <div style="font-size:1.8rem; font-weight:700;">{{ $istatistik['tiklanan'] }}</div>
                <div class="small">Link Tıklama</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="background:#198754;">
                <div style="font-size:1.8rem; font-weight:700;">
                    @if($istatistik['basarili'] > 0)
                        %{{ round(($istatistik['tiklanan'] / $istatistik['basarili']) * 100, 1) }}
                    @else — @endif
                </div>
                <div class="small">Tıklama Oranı (CTR)</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Kampanya detayları --}}
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header py-2 fw-semibold small">Kampanya Detayları</div>
                <div class="card-body py-2 small">
                    @php $z = $kampanya->zamanlama ?? []; $h = $kampanya->hedef ?? []; @endphp
                    <div class="mb-1"><strong>Şablon:</strong> {{ $kampanya->sablon?->ad ?? '—' }}</div>
                    <div class="mb-1"><strong>Durum:</strong>
                        <span class="badge bg-{{ match($kampanya->durum) { 'aktif'=>'success','taslak'=>'secondary','durduruldu'=>'danger','tamamlandi'=>'primary', default=>'secondary' } }}">
                            {{ ucfirst($kampanya->durum) }}
                        </span>
                    </div>
                    <div class="mb-1"><strong>Tarih:</strong>
                        {{ $z['baslangic'] ?? '—' ?: '∞' }} → {{ $z['bitis'] ?? '—' ?: '∞' }}
                    </div>
                    <div class="mb-1"><strong>Slotlar:</strong>
                        @foreach($z['slotlar'] ?? [] as $s)
                            <span class="badge bg-light text-dark border">{{ $s['saat'] }} / {{ $s['adet'] }}</span>
                        @endforeach
                    </div>
                    <div class="mb-1"><strong>İl filtresi:</strong> {{ $h['il'] ?: 'Tümü' }}</div>
                    <div class="mb-1"><strong>Grup filtresi:</strong> {{ $h['grup'] ?: 'Tümü' }}</div>
                    <div><strong>Sadece yeni:</strong> {{ ($h['sadece_yeni'] ?? true) ? 'Evet' : 'Hayır' }}</div>
                </div>
            </div>
        </div>

        {{-- Gönderim logu --}}
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header py-2 fw-semibold small">
                    Son Gönderimler ({{ $gonderilenler->total() }} toplam)
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" style="font-size:0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Acente</th>
                                <th>Email</th>
                                <th>Durum</th>
                                <th>Tıklama</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gonderilenler as $g)
                            <tr>
                                <td>{{ $g->acente_unvani }}</td>
                                <td class="text-muted">{{ $g->eposta }}</td>
                                <td>
                                    @if($g->status === 'sent')
                                        <span class="text-success">✓</span>
                                    @else
                                        <span class="text-danger" title="{{ $g->hata }}">✗</span>
                                    @endif
                                </td>
                                <td>
                                    @if($g->tiklanma_at)
                                        <span class="badge bg-danger">{{ $g->tiklanma_sayisi }}x</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $g->created_at->format('d.m H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-3 py-2">{{ $gonderilenler->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


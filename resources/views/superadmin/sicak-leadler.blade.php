<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sıcak Leadler — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.page-header p  { color:rgba(255,255,255,0.5); font-size:0.82rem; margin:0; }
</style>
</head>
<body>

<x-navbar-superadmin active="sicak-leadler" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-fire me-2" style="color:#ff6b35;"></i>Sıcak Leadler</h5>
                <p>Email linkine tıkladı ama henüz kayıt olmadı</p>
            </div>
            <a href="{{ route('superadmin.tursab.kampanya') }}" class="btn btn-sm btn-outline-light">← Kampanya Hub</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    <div class="card shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <span class="fw-bold small"><i class="fas fa-fire text-danger me-2"></i>Toplam: {{ $leadler->total() }} lead</span>
            <span class="text-muted small">Tıkladı ama kayıt olmadı — en güncel önce</span>
        </div>
        <div class="card-body p-0">
            @if($leadler->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    Henüz sıcak lead yok. Email kampanyası çalışınca burada görünecek.
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:0.83rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Acente</th>
                            <th>İl</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Kampanya</th>
                            <th>Email Tarihi</th>
                            <th>Tıklama</th>
                            <th>Tıklama Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leadler as $l)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $l->acente_unvani }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $l->belge_no }}</div>
                            </td>
                            <td>{{ $l->il ?? '—' }}</td>
                            <td>
                                <a href="mailto:{{ $l->eposta }}" class="text-decoration-none">{{ $l->eposta }}</a>
                            </td>
                            <td>
                                @if($l->telefon)
                                    <a href="tel:{{ $l->telefon }}" class="text-decoration-none">{{ $l->telefon }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $l->kampanya_etiket ?? '—' }}</span>
                            </td>
                            <td class="text-muted">{{ $l->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-danger">{{ $l->tiklanma_sayisi }}x</span>
                            </td>
                            <td class="text-muted">
                                {{ $l->tiklanma_at ? \Carbon\Carbon::parse($l->tiklanma_at)->timezone('Europe/Istanbul')->format('d.m.Y H:i') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2">
                {{ $leadler->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

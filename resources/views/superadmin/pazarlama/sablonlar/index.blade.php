<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Şablon Kütüphanesi — Süperadmin</title>
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
<x-navbar-superadmin active="sablonlar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-file-code me-2" style="color:#ffc107;"></i>Şablon Kütüphanesi</h5>
                <p>Email ve SMS şablonlarını yönet</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('superadmin.kampanyalar.index') }}" class="btn btn-sm btn-outline-light">Kampanyalar</a>
                <a href="{{ route('superadmin.sablonlar.create') }}" class="btn btn-sm btn-warning">
                    <i class="fas fa-plus me-1"></i>Yeni Şablon
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">
        @forelse($sablonlar as $s)
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div>
                        <span class="fw-semibold">{{ $s->ad }}</span>
                        <span class="badge ms-2 {{ $s->tip === 'email' ? 'bg-danger' : 'bg-info text-dark' }}">
                            {{ $s->tip === 'email' ? '📧 Email' : '📱 SMS' }}
                        </span>
                    </div>
                    <span class="badge {{ $s->aktif ? 'bg-success' : 'bg-secondary' }}">
                        {{ $s->aktif ? 'Aktif' : 'Pasif' }}
                    </span>
                </div>
                <div class="card-body py-2">
                    @if($s->konu)
                        <div class="small text-muted mb-1"><strong>Konu:</strong> {{ $s->konu }}</div>
                    @endif
                    @if($s->tip === 'sms' && $s->sms_icerik)
                        <div class="small text-muted">{{ Str::limit($s->sms_icerik, 100) }}</div>
                    @elseif($s->html_icerik)
                        <div class="small text-muted">HTML şablon — {{ number_format(strlen($s->html_icerik)) }} karakter</div>
                    @endif
                    <div class="small text-muted mt-1">{{ $s->created_at->format('d.m.Y') }}</div>
                </div>
                <div class="card-footer py-2 d-flex gap-2">
                    @if($s->tip === 'email')
                    <a href="{{ route('superadmin.sablonlar.preview', $s) }}" target="_blank"
                       class="btn btn-outline-secondary btn-sm"><i class="fas fa-eye me-1"></i>Önizle</a>
                    @endif
                    <a href="{{ route('superadmin.sablonlar.edit', $s) }}"
                       class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i>Düzenle</a>
                    <form method="POST" action="{{ route('superadmin.sablonlar.destroy', $s) }}"
                          onsubmit="return confirm('«{{ $s->ad }}» silinsin mi?')" class="ms-auto">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-code fa-2x mb-2 d-block"></i>
                Henüz şablon yok. <a href="{{ route('superadmin.sablonlar.create') }}">İlk şablonu oluştur</a>
            </div>
        </div>
        @endforelse
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


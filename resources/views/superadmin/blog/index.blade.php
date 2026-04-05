<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@include('admin.partials.theme-styles')
<title>Blog Yazıları — Superadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<x-navbar-superadmin active="blog" />
<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-newspaper me-2 text-primary"></i>Blog Yazıları</h4>
            <small class="text-muted">SEO içerikleri yönetin</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('superadmin.blog.kategoriler') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-tags me-1"></i>Kategoriler
            </a>
            <a href="{{ route('superadmin.blog.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Yeni Yazı
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Başlık</th>
                        <th>Kategori</th>
                        <th>Durum</th>
                        <th>Yayın Tarihi</th>
                        <th>Görüntüleme</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($yaziler as $yazi)
                <tr>
                    <td>
                        <div class="fw-semibold" style="font-size:.9rem;">{{ $yazi->baslik }}</div>
                        <small class="text-muted">/blog/{{ $yazi->slug }}</small>
                    </td>
                    <td><span class="badge bg-secondary">{{ $yazi->kategori?->ad ?? '—' }}</span></td>
                    <td>
                        @if($yazi->durum === 'yayinda')
                            <span class="badge bg-success">Yayında</span>
                        @else
                            <span class="badge bg-warning text-dark">Taslak</span>
                        @endif
                    </td>
                    <td>{{ $yazi->yayinlanma_tarihi?->format('d.m.Y') ?? '—' }}</td>
                    <td>{{ number_format($yazi->goruntuleme) }}</td>
                    <td class="text-end">
                        <a href="/blog/{{ $yazi->slug }}" target="_blank" class="btn btn-sm btn-outline-secondary me-1">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('superadmin.blog.edit', $yazi) }}" class="btn btn-sm btn-outline-primary me-1">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('superadmin.blog.destroy', $yazi) }}" class="d-inline"
                              onsubmit="return confirm('Yazıyı sil?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Henüz yazı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($yaziler->hasPages())
        <div class="card-footer">{{ $yaziler->links() }}</div>
        @endif
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

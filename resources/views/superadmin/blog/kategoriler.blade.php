<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@include('admin.partials.theme-styles')
<title>Blog Kategorileri</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<x-navbar-superadmin active="blog" />
<div class="container py-4" style="max-width:600px;">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('superadmin.blog.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Yazılara Dön
        </a>
        <h4 class="mb-0 fw-bold"><i class="fas fa-tags me-2"></i>Kategoriler</h4>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">Yeni Kategori</div>
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.blog.kategori.store') }}" class="d-flex gap-2">
                @csrf
                <input type="text" name="ad" class="form-control" placeholder="Kategori adı" required>
                <button type="submit" class="btn btn-primary px-4">Ekle</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <ul class="list-group list-group-flush">
        @forelse($kategoriler as $k)
        <li class="list-group-item d-flex align-items-center justify-content-between">
            <div>
                <span class="fw-semibold">{{ $k->ad }}</span>
                <small class="text-muted ms-2">{{ $k->yaziler_count }} yazı · /blog/kategori/{{ $k->slug }}</small>
            </div>
            <form method="POST" action="{{ route('superadmin.blog.kategori.destroy', $k) }}"
                  onsubmit="return confirm('Kategoriyi sil?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
            </form>
        </li>
        @empty
        <li class="list-group-item text-muted text-center py-3">Henüz kategori yok.</li>
        @endforelse
        </ul>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

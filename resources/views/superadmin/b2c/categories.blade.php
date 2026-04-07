<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>B2C Kategoriler — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .table th { font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#6c757d; font-weight:600; }
        .table td { vertical-align:middle; font-size:.875rem; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-tags me-2" style="color:#e8a020;"></i>B2C Kategori Yönetimi</h5>
        <p>Vitrin kategori ağacı</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('superadmin.b2c.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
        <a href="{{ route('superadmin.b2c.categories.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Yeni Kategori
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr>
                    <th>Kategori</th>
                    <th>Slug</th>
                    <th>İkon</th>
                    <th>Ürün</th>
                    <th>Sıra</th>
                    <th>Durum</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td>
                        <strong>{{ $cat->name }}</strong>
                        @foreach($cat->children as $child)
                        <div class="ps-3 text-muted" style="font-size:.82rem;">
                            <i class="fas fa-level-up-alt fa-rotate-90 me-1"></i>{{ $child->name }}
                        </div>
                        @endforeach
                    </td>
                    <td><code style="font-size:.8rem;">{{ $cat->slug }}</code></td>
                    <td><i class="bi {{ $cat->icon }}" style="font-size:1.2rem;color:#1a3c6b;"></i> <small class="text-muted">{{ $cat->icon }}</small></td>
                    <td>
                        <span class="badge bg-primary">{{ $cat->items_count ?? 0 }}</span>
                        <small class="text-muted">({{ $cat->published_items_count ?? 0 }} yayında)</small>
                    </td>
                    <td>{{ $cat->sort_order }}</td>
                    <td>
                        @if($cat->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Pasif</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('superadmin.b2c.categories.edit', $cat) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('superadmin.b2c.categories.destroy', $cat) }}"
                                  onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-5">Henüz kategori eklenmemiş.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

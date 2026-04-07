<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>B2C Katalog — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .table th { font-size:.75rem; text-transform:uppercase; letter-spacing:1px; color:#6c757d; font-weight:600; }
        .table td { vertical-align:middle; font-size:.875rem; }
        .thumb { width:48px; height:36px; object-fit:cover; border-radius:4px; }
        .thumb-placeholder { width:48px; height:36px; background:linear-gradient(135deg,#1a3c6b,#2a5298); border-radius:4px; display:inline-flex; align-items:center; justify-content:center; color:rgba(255,255,255,.5); font-size:.8rem; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-th-list me-2" style="color:#e8a020;"></i>B2C Ürün Kataloğu</h5>
        <p>Vitrin ürün ve hizmet yönetimi</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filtreler --}}
    <form method="GET" class="row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <select name="kategori" class="form-select form-select-sm">
                <option value="">Tüm Kategoriler</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('kategori') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="tip" class="form-select form-select-sm">
                <option value="">Tüm Tipler</option>
                @foreach(['transfer','charter','leisure','tour','hotel','visa','other'] as $t)
                <option value="{{ $t }}" {{ request('tip') == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="durum" class="form-select form-select-sm">
                <option value="">Tüm Durumlar</option>
                <option value="published" {{ request('durum')=='published' ? 'selected' : '' }}>Yayında</option>
                <option value="unpublished" {{ request('durum')=='unpublished' ? 'selected' : '' }}>Taslak</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-secondary">
                <i class="fas fa-filter me-1"></i>Filtrele
            </button>
            <a href="{{ route('superadmin.b2c.catalog') }}" class="btn btn-sm btn-outline-secondary ms-1">Temizle</a>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('superadmin.b2c.catalog.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Yeni Ürün Ekle
            </a>
        </div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr>
                    <th width="50"></th>
                    <th>Ürün / Başlık</th>
                    <th>Kategori</th>
                    <th>Tip</th>
                    <th>Fiyat</th>
                    <th>Puan</th>
                    <th>Durum</th>
                    <th width="120">İşlemler</th>
                </tr></thead>
                <tbody>
                @forelse($items as $item)
                <tr>
                    <td>
                        @if($item->cover_image)
                            <img src="{{ str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/'.$item->cover_image) }}" class="thumb" alt="">
                        @else
                            <div class="thumb-placeholder"><i class="fas fa-image"></i></div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-600">{{ Str::limit($item->title, 55) }}</div>
                        <small class="text-muted">
                            <code>{{ $item->slug }}</code>
                            @if($item->destination_city) · {{ $item->destination_city }} @endif
                        </small>
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ $item->category->name ?? '—' }}</span></td>
                    <td><span class="badge bg-secondary">{{ $item->product_type }}</span></td>
                    <td>
                        @if($item->pricing_type === 'fixed' && $item->base_price)
                            {{ number_format($item->base_price,0,',','.') }} {{ $item->currency }}
                        @elseif($item->pricing_type === 'quote')
                            <span class="text-primary">Teklif</span>
                        @else
                            <span class="text-muted">Talep</span>
                        @endif
                    </td>
                    <td>
                        @if($item->rating_avg > 0)
                        <span style="color:#f4a418;">★</span>
                        {{ number_format($item->rating_avg,1) }}
                        <small class="text-muted">({{ $item->review_count }})</small>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($item->is_published)
                            <span class="badge bg-success">Yayında</span>
                        @else
                            <span class="badge bg-warning text-dark">Taslak</span>
                        @endif
                        @if($item->is_featured)
                            <span class="badge bg-primary ms-1" title="Öne Çıkan"><i class="fas fa-star"></i></span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('superadmin.b2c.catalog.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('superadmin.b2c.catalog.toggle-publish', $item) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $item->is_published ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $item->is_published ? 'Yayından Al' : 'Yayına Al' }}">
                                    <i class="fas {{ $item->is_published ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                </button>
                            </form>
                            <a href="{{ route('b2c.product.show', $item->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Sitede Gör">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-5">
                    Henüz ürün eklenmemiş.
                    <a href="{{ route('superadmin.b2c.catalog.create') }}">İlk ürünü ekleyin →</a>
                </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="card-footer bg-white">
            {{ $items->links() }}
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

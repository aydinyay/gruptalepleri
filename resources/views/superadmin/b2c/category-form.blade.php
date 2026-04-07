<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($category) ? 'Kategori Düzenle' : 'Yeni Kategori' }} — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .icon-preview { font-size:2rem; color:#1a3c6b; min-width:50px; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-tags me-2" style="color:#e8a020;"></i>
            {{ isset($category) ? 'Kategori Düzenle: ' . $category->name : 'Yeni Kategori Oluştur' }}
        </h5>
        <p>B2C vitrin kategori yönetimi</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="mb-3">
                <a href="{{ route('superadmin.b2c.categories') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Kategorilere Dön
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ isset($category) ? route('superadmin.b2c.categories.update', $category) : route('superadmin.b2c.categories.store') }}">
                        @csrf
                        @if(isset($category)) @method('PUT') @endif

                        @if($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-600">Kategori Adı *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $category->name ?? '') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600">Slug (URL)</label>
                            <input type="text" name="slug" class="form-control"
                                   value="{{ old('slug', $category->slug ?? '') }}"
                                   placeholder="bos-birakilirsa-otomatik-olusturulur">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600">Üst Kategori</label>
                            <select name="parent_id" class="form-select">
                                <option value="">— Kök Kategori —</option>
                                @foreach($parentCategories as $parent)
                                <option value="{{ $parent->id }}"
                                    {{ old('parent_id', $category->parent_id ?? '') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600">Bootstrap Icon Sınıfı</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i id="iconPreview" class="bi {{ old('icon', $category->icon ?? 'bi-grid') }}" style="font-size:1.3rem;"></i>
                                </span>
                                <input type="text" name="icon" id="iconInput" class="form-control"
                                       value="{{ old('icon', $category->icon ?? 'bi-grid') }}"
                                       placeholder="bi-car-front-fill">
                            </div>
                            <div class="form-text">
                                Örnekler: bi-car-front-fill, bi-airplane-fill, bi-water, bi-map-fill, bi-building, bi-passport
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-600">Açıklama</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description ?? '') }}</textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-600">Sıralama</label>
                                <input type="number" name="sort_order" class="form-control"
                                       value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check ms-2 pb-2">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input"
                                           id="isActive" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Aktif</label>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-700 mb-3">SEO</h6>

                        <div class="mb-3">
                            <label class="form-label">Meta Başlık</label>
                            <input type="text" name="meta_title" class="form-control"
                                   value="{{ old('meta_title', $category->meta_title ?? '') }}" maxlength="120">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Açıklama</label>
                            <textarea name="meta_description" class="form-control" rows="2" maxlength="250">{{ old('meta_description', $category->meta_description ?? '') }}</textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i>{{ isset($category) ? 'Güncelle' : 'Kaydet' }}
                            </button>
                            <a href="{{ route('superadmin.b2c.categories') }}" class="btn btn-outline-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('iconInput').addEventListener('input', function() {
    document.getElementById('iconPreview').className = 'bi ' + this.value;
});
</script>
</body>
</html>

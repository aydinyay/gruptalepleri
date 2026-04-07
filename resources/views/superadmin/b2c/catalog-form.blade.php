<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($item) ? 'Ürün Düzenle' : 'Yeni Ürün' }} — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .section-title { font-weight:700; font-size:.9rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; border-bottom:2px solid #e9ecef; padding-bottom:.5rem; margin-bottom:1rem; margin-top:1.5rem; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-box me-2" style="color:#e8a020;"></i>
            {{ isset($item) ? 'Ürün Düzenle: ' . Str::limit($item->title, 50) : 'Yeni B2C Ürünü Ekle' }}
        </h5>
        <p>Vitrin ürün yönetimi</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    <div class="row justify-content-center">
        <div class="col-xl-9">

            <div class="mb-3">
                <a href="{{ route('superadmin.b2c.catalog') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Kataloga Dön
                </a>
            </div>

            <form method="POST" action="{{ isset($item) ? route('superadmin.b2c.catalog.update', $item) : route('superadmin.b2c.catalog.store') }}"
                  enctype="multipart/form-data">
                @csrf
                @if(isset($item)) @method('PUT') @endif

                @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
                @endif

                <div class="row g-4">
                    {{-- Sol Kolon --}}
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm p-4">

                            <div class="section-title">Temel Bilgiler</div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Başlık *</label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $item->title ?? '') }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Slug (URL)</label>
                                <input type="text" name="slug" class="form-control"
                                       value="{{ old('slug', $item->slug ?? '') }}"
                                       placeholder="bos-birakilirsa-baslıktan-olusturulur">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Kısa Açıklama</label>
                                <textarea name="short_desc" class="form-control" rows="2" maxlength="300">{{ old('short_desc', $item->short_desc ?? '') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Tam Açıklama</label>
                                <textarea name="full_desc" class="form-control" rows="6">{{ old('full_desc', $item->full_desc ?? '') }}</textarea>
                            </div>

                            <div class="section-title">Fiyatlandırma</div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Fiyat Tipi *</label>
                                    <select name="pricing_type" id="pricingType" class="form-select" required>
                                        @foreach(['fixed'=>'Sabit Fiyat','quote'=>'Teklif İste','request'=>'Talep Oluştur'] as $v => $l)
                                        <option value="{{ $v }}" {{ old('pricing_type', $item->pricing_type ?? '') == $v ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4" id="priceFields">
                                    <label class="form-label fw-600">Baz Fiyat</label>
                                    <input type="number" name="base_price" class="form-control" step="0.01" min="0"
                                           value="{{ old('base_price', $item->base_price ?? '') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Para Birimi</label>
                                    <select name="currency" class="form-select">
                                        @foreach(['TRY','USD','EUR','GBP'] as $c)
                                        <option value="{{ $c }}" {{ old('currency', $item->currency ?? 'TRY') == $c ? 'selected' : '' }}>{{ $c }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="section-title">Destinasyon & Süre</div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-600">Şehir</label>
                                    <input type="text" name="destination_city" class="form-control"
                                           value="{{ old('destination_city', $item->destination_city ?? '') }}"
                                           placeholder="İstanbul">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-600">Ülke</label>
                                    <input type="text" name="destination_country" class="form-control"
                                           value="{{ old('destination_country', $item->destination_country ?? 'Türkiye') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-600">Süre (gün)</label>
                                    <input type="number" name="duration_days" class="form-control" min="0"
                                           value="{{ old('duration_days', $item->duration_days ?? '') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-600">Süre (saat)</label>
                                    <input type="number" name="duration_hours" class="form-control" min="0"
                                           value="{{ old('duration_hours', $item->duration_hours ?? '') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-600">Min Kişi</label>
                                    <input type="number" name="min_pax" class="form-control" min="1"
                                           value="{{ old('min_pax', $item->min_pax ?? 1) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-600">Max Kişi</label>
                                    <input type="number" name="max_pax" class="form-control" min="0"
                                           value="{{ old('max_pax', $item->max_pax ?? '') }}"
                                           placeholder="Sınırsız">
                                </div>
                            </div>

                            <div class="section-title">SEO</div>
                            <div class="mb-3">
                                <label class="form-label">Meta Başlık</label>
                                <input type="text" name="meta_title" class="form-control" maxlength="120"
                                       value="{{ old('meta_title', $item->meta_title ?? '') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta Açıklama</label>
                                <textarea name="meta_description" class="form-control" rows="2" maxlength="250">{{ old('meta_description', $item->meta_description ?? '') }}</textarea>
                            </div>

                        </div>
                    </div>

                    {{-- Sağ Kolon --}}
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm p-4 mb-3">
                            <div class="section-title mt-0">Sınıflandırma</div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Kategori *</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">— Seçin —</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $item->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Ürün Tipi *</label>
                                <select name="product_type" class="form-select" required>
                                    @foreach(['transfer'=>'Transfer','charter'=>'Charter & Uçuş','leisure'=>'Deniz & Eğlence','tour'=>'Tur','hotel'=>'Otel','visa'=>'Vize','other'=>'Diğer'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('product_type', $item->product_type ?? '') == $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Sahip Tipi</label>
                                <select name="owner_type" class="form-select">
                                    <option value="platform" {{ old('owner_type', $item->owner_type ?? 'platform') == 'platform' ? 'selected' : '' }}>Platform</option>
                                    <option value="supplier" {{ old('owner_type', $item->owner_type ?? '') == 'supplier' ? 'selected' : '' }}>Tedarikçi</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Sıralama</label>
                                <input type="number" name="sort_order" class="form-control" min="0"
                                       value="{{ old('sort_order', $item->sort_order ?? 0) }}">
                            </div>

                            <div class="mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive"
                                           {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isActive">Aktif</label>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="isFeatured"
                                           {{ old('is_featured', $item->is_featured ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isFeatured"><i class="fas fa-star text-warning me-1"></i>Öne Çıkan</label>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="is_published" value="1" class="form-check-input" id="isPublished"
                                           {{ old('is_published', $item->is_published ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="isPublished"><i class="fas fa-eye text-success me-1"></i>Yayında</label>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm p-4 mb-3">
                            <div class="section-title mt-0">Kapak Görseli</div>
                            @if(isset($item) && $item->cover_image)
                            <img src="{{ str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('storage/'.$item->cover_image) }}" class="img-fluid rounded mb-2" style="max-height:180px;object-fit:cover;" alt="Kapak">
                            @endif

                            <div class="mb-2">
                                <label class="form-label fw-600 form-label-sm">Dosya Yükle</label>
                                <input type="file" name="cover_image_file" class="form-control form-control-sm" accept="image/*">
                                <div class="form-text">JPG/PNG/WEBP, max 4MB. Yüklenirse URL alanını geçersiz kılar.</div>
                            </div>
                            <div>
                                <label class="form-label fw-600 form-label-sm">veya URL</label>
                                <input type="text" name="cover_image" class="form-control form-control-sm"
                                       value="{{ old('cover_image', $item->cover_image ?? '') }}"
                                       placeholder="catalog/gorsel.jpg">
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm p-4">
                            <div class="section-title mt-0">Puanlama (Manuel)</div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label form-label-sm">Puan Avg</label>
                                    <input type="number" name="rating_avg" class="form-control form-control-sm" step="0.1" min="0" max="5"
                                           value="{{ old('rating_avg', $item->rating_avg ?? 0) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label form-label-sm">Yorum Sayısı</label>
                                    <input type="number" name="review_count" class="form-control form-control-sm" min="0"
                                           value="{{ old('review_count', $item->review_count ?? 0) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-5">
                        <i class="fas fa-save me-1"></i>{{ isset($item) ? 'Güncelle' : 'Ürünü Kaydet' }}
                    </button>
                    <a href="{{ route('superadmin.b2c.catalog') }}" class="btn btn-outline-secondary">İptal</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fiyat tipi değişince fiyat alanını gizle/göster
document.getElementById('pricingType').addEventListener('change', function() {
    document.getElementById('priceFields').style.display = this.value === 'fixed' ? '' : 'none';
});
document.getElementById('pricingType').dispatchEvent(new Event('change'));

// Başlıktan slug otomatik
document.querySelector('[name=title]').addEventListener('blur', function() {
    const slugField = document.querySelector('[name=slug]');
    if (!slugField.value) {
        slugField.value = this.value
            .toLowerCase()
            .replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s').replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c')
            .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    }
});
</script>
</body>
</html>

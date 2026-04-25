<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($item) ? 'Ürün Düzenle' : 'Yeni Ürün' }} — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .section-title { font-weight:700; font-size:.9rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; border-bottom:2px solid #e9ecef; padding-bottom:.5rem; margin-bottom:1rem; margin-top:1.5rem; }
        .form-control::placeholder { color:#bbc3ce; font-style:italic; }
        .form-control::-webkit-input-placeholder { color:#bbc3ce; font-style:italic; }
        .form-control::-moz-placeholder { color:#bbc3ce; font-style:italic; }
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

            <div class="mb-3 d-flex gap-2">
                <a href="{{ route('superadmin.b2c.catalog') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Kataloga Dön
                </a>
                @isset($item)
                <a href="{{ route('superadmin.b2c.sessions.index', $item) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-calendar-alt me-1"></i>Seans Yönetimi
                </a>
                @endisset
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
                                <textarea name="full_desc" id="fullDesc" class="form-control" rows="6">{{ old('full_desc', $item->full_desc ?? '') }}</textarea>
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <button type="button" id="aiFillBtn"
                                            onclick="aiFillFields()"
                                            class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                            title="Tam açıklamadan diğer alanları otomatik doldur">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                                        <span id="aiFillBtnText">AI ile Doldur</span>
                                    </button>
                                    <span id="aiStatus" class="text-muted" style="font-size:.8rem;"></span>
                                </div>
                            </div>

                            <div class="section-title">Fiyatlandırma</div>

                            <div class="row g-3 mb-3">
                                {{-- Satır 1: Fiyat tipi + Para birimi + Fiyat birimi --}}
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Fiyat Tipi *</label>
                                    <select name="pricing_type" id="pricingType" class="form-select" required>
                                        @foreach(['fixed'=>'Sabit Fiyat','quote'=>'Teklif İste','request'=>'Talep Oluştur'] as $v => $l)
                                        <option value="{{ $v }}" {{ old('pricing_type', $item->pricing_type ?? '') == $v ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Para Birimi</label>
                                    <select name="currency" class="form-select">
                                        @foreach(['TRY','USD','EUR','GBP'] as $c)
                                        <option value="{{ $c }}" {{ old('currency', $item->currency ?? 'TRY') == $c ? 'selected' : '' }}>{{ $c }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Fiyat Birimi <small class="text-muted">(Kartta görünür)</small></label>
                                    @php
                                    $pricingUnits = [
                                        ''               => '— Otomatik (alttüre göre) —',
                                        'kişi başına'    => 'Kişi başına',
                                        'grup başına'    => 'Grup başına',
                                        'saatlik'        => 'Saatlik',
                                        'saatlik · grup başına' => 'Saatlik · grup başına',
                                        'günlük'         => 'Günlük',
                                        'günlük · grup başına'  => 'Günlük · grup başına',
                                        'araç başına'    => 'Araç başına',
                                        'gecelik'        => 'Gecelik',
                                        'sefer başına'   => 'Sefer başına',
                                        'başvuru başına' => 'Başvuru başına',
                                    ];
                                    @endphp
                                    <select name="pricing_unit" class="form-select">
                                        @foreach($pricingUnits as $v => $l)
                                        <option value="{{ $v }}" {{ old('pricing_unit', $item->pricing_unit ?? '') == $v ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Boş bırakırsan ürün alttürüne göre otomatik seçilir.</div>
                                </div>

                                {{-- Satır 2: Maliyet → GT → GR (mantıksal hiyerarşi) --}}
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Maliyet Fiyatı <small class="text-muted">(İç kullanım)</small></label>
                                    <input type="number" name="cost_price" class="form-control" step="0.01" min="0"
                                           value="{{ old('cost_price', $item->cost_price ?? '') }}"
                                           placeholder="Tedarikçi maliyeti">
                                </div>
                                <div class="col-md-4" id="priceFields">
                                    <label class="form-label fw-600">GT Satış Fiyatı <small class="text-muted">(Acente / B2B)</small></label>
                                    <input type="number" name="gt_price" class="form-control" step="0.01" min="0"
                                           value="{{ old('gt_price', $item->gt_price ?? '') }}"
                                           placeholder="Acente fiyatı">
                                </div>
                                <div class="col-md-4" id="grPriceField">
                                    <label class="form-label fw-600">GR Satış Fiyatı <small class="text-muted">(Müşteri / B2C)</small></label>
                                    <input type="number" name="base_price" class="form-control" step="0.01" min="0"
                                           value="{{ old('base_price', $item->base_price ?? '') }}"
                                           placeholder="Müşteri fiyatı">
                                </div>
                            </div>

                            <div class="section-title">Destinasyon & Süre</div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Şehir / İl</label>
                                    <input type="text" name="destination_city" id="destinationCity" class="form-control"
                                           value="{{ old('destination_city', $item->destination_city ?? '') }}"
                                           placeholder="İzmir">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600">İlçe</label>
                                    <input type="text" name="destination_district" id="destinationDistrict" class="form-control"
                                           value="{{ old('destination_district', $item->destination_district ?? '') }}"
                                           placeholder="Urla">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Belde / Bölge</label>
                                    <input type="text" name="destination_area" id="destinationArea" class="form-control"
                                           value="{{ old('destination_area', $item->destination_area ?? '') }}"
                                           placeholder="Zeytinalanı">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-600">Ülke</label>
                                    <input type="text" name="destination_country" class="form-control"
                                           value="{{ old('destination_country', $item->destination_country ?? 'Türkiye') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-600">Mekan Adresi <small class="text-muted fw-normal">(Mesafe hesabı için — girilirse koordinat otomatik bulunur)</small></label>
                                    <div class="input-group">
                                        <input type="text" id="venueAddressInput" name="venue_address" class="form-control"
                                               value="{{ old('venue_address', $item->venue_address ?? '') }}"
                                               placeholder="Örn: Kordon, Alsancak, İzmir">
                                        <button type="button" class="btn btn-outline-secondary" onclick="grGeocode()">
                                            <i class="bi bi-geo-alt"></i> Konumu Bul
                                        </button>
                                    </div>
                                    <div id="venueGeoStatus" class="form-text"></div>
                                    <input type="hidden" name="venue_lat" id="venueLat" value="{{ old('venue_lat', $item->venue_lat ?? '') }}">
                                    <input type="hidden" name="venue_lng" id="venueLng" value="{{ old('venue_lng', $item->venue_lng ?? '') }}">
                                    @if(!empty($item->venue_lat))
                                    <div class="form-text text-success"><i class="bi bi-check-circle"></i> Kayıtlı: {{ $item->venue_lat }}, {{ $item->venue_lng }}</div>
                                    @endif
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
                                <select name="product_type" id="productType" class="form-select" required>
                                    @foreach(['transfer'=>'Transfer','charter'=>'Charter & Uçuş','leisure'=>'Deniz & Eğlence','tour'=>'Tur','hotel'=>'Otel','visa'=>'Vize','sigorta'=>'Sigorta','other'=>'Diğer'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('product_type', $item->product_type ?? '') == $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Ürün Alt Tipi</label>
                                <select name="product_subtype" id="productSubtype" class="form-select">
                                    <option value="">— Otomatik / Seçin —</option>
                                </select>
                                <div class="form-text">Fiyat birimi ve rezervasyon formunu belirler.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Sahip Tipi</label>
                                <select name="owner_type" class="form-select">
                                    <option value="platform" {{ old('owner_type', $item->owner_type ?? 'platform') == 'platform' ? 'selected' : '' }}>Platform (Grup Rezervasyonları)</option>
                                    <option value="supplier" {{ old('owner_type', $item->owner_type ?? '') == 'supplier' ? 'selected' : '' }}>Tedarikçi / Acente</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Sağlayıcı Acente <span class="text-muted fw-normal">(B2B sistemindeki acente)</span></label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">— Seç —</option>
                                    @foreach($supplierUsers ?? [] as $su)
                                    <option value="{{ $su['id'] }}" {{ old('supplier_id', $item->supplier_id ?? '') == $su['id'] ? 'selected' : '' }}>
                                        {{ $su['label'] }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Acenteyi seçince adı otomatik gösterilir.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Sağlayıcı Adı <span class="text-muted fw-normal">(override — sistemde yoksa yazın)</span></label>
                                <input type="text" name="supplier_name" class="form-control"
                                       placeholder="örn: Bestaway Tour"
                                       value="{{ old('supplier_name', $item->supplier_name ?? '') }}">
                                <div class="form-text">Dolu olursa yukarıdaki acente seçiminin önüne geçer.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-600">Sağlayıcı Logo URL <span class="text-muted fw-normal">(isteğe bağlı)</span></label>
                                <input type="text" name="supplier_logo_url" class="form-control"
                                       placeholder="https://..."
                                       value="{{ old('supplier_logo_url', $item->supplier_logo_url ?? '') }}">
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
                                <label class="form-label fw-600 form-label-sm">Rozet / Etiket</label>
                                <select name="badge_label" class="form-select form-select-sm">
                                    <option value="">— Yok —</option>
                                    @foreach(['Vizyon','Popüler','Yeni','Son Fırsat','İndirim','Sınırlı','Çok Satan','Sıradışı','Hızlı Tükeniyor','Klasik','Efsane','Özel Teklif','Erken Rezervasyon','Gastronomi','Gurme','Lezzetler'] as $bl)
                                        <option value="{{ $bl }}" {{ old('badge_label', $item->badge_label ?? '') === $bl ? 'selected' : '' }}>{{ $bl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Yayın Durumu</label>
                                @php $ps = old('publish_status', $item->publish_status ?? 'draft'); @endphp
                                <div class="d-flex flex-column gap-2 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="publish_status" id="ps_draft" value="draft" {{ $ps === 'draft' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ps_draft">
                                            <span class="badge bg-secondary me-1">Taslak</span>
                                            <small class="text-muted">Hiçbir yerde görünmez</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="publish_status" id="ps_b2b" value="b2b" {{ $ps === 'b2b' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ps_b2b">
                                            <span class="badge me-1" style="background:#1a3c6b;">GT Yayında</span>
                                            <small class="text-muted">Sadece acente kataloğunda</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="publish_status" id="ps_b2c" value="b2c" {{ $ps === 'b2c' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="ps_b2c">
                                            <span class="badge bg-success me-1">GR Yayında</span>
                                            <small class="text-muted">GR vitrin + acente kataloğu</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm p-4 mb-3">
                            <div class="section-title mt-0">Galeri Görselleri</div>

                            @php
                            $galList = isset($item) && $item->gallery_json
                                ? array_values(array_filter((array) $item->gallery_json))
                                : [];
                            @endphp

                            {{-- Mevcut galeri (silinebilir) --}}
                            <input type="hidden" name="gallery_keep_json" id="galKeepJson"
                                   value="{{ json_encode($galList) }}">

                            @if(count($galList))
                            <div id="galPreview" class="d-flex flex-wrap gap-2 mb-3">
                                @foreach($galList as $gUrl)
                                @php
                                $gExt = strtolower(pathinfo($gUrl, PATHINFO_EXTENSION));
                                $gIsVid = in_array($gExt, ['mp4','mov','webm']);
                                $gFull = str_starts_with($gUrl,'http') ? $gUrl : asset('uploads/'.$gUrl);
                                @endphp
                                <div class="gal-item position-relative" style="width:100px;" data-url="{{ $gUrl }}">
                                    @if($gIsVid)
                                    <div style="height:70px;width:100px;background:#0d1117;border-radius:6px;border:1px solid #dee2e6;display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-play-circle" style="font-size:1.6rem;color:#e8a020;"></i>
                                    </div>
                                    <div style="font-size:.65rem;color:#6c757d;text-align:center;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $gExt }}</div>
                                    @else
                                    <img src="{{ $gFull }}" alt=""
                                         style="height:70px;width:100px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;">
                                    @endif
                                    <button type="button" title="Galeriden sil"
                                            onclick="galRemove(this)"
                                            style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;background:#dc3545;border:none;color:#fff;font-size:.65rem;line-height:20px;text-align:center;padding:0;cursor:pointer;">✕</button>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div id="galPreview" class="d-flex flex-wrap gap-2 mb-3"></div>
                            @endif

                            {{-- Yeni dosya yükle --}}
                            <div class="mb-3">
                                <label class="form-label fw-600 form-label-sm">Yeni Dosya Ekle</label>
                                <input type="file" name="gallery_files[]" id="galleryFilesInput" multiple
                                       accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm"
                                       class="form-control form-control-sm">
                                <div class="form-text">JPG/PNG/WEBP/GIF · MP4/MOV/WEBM · Her biri maks. 50MB.</div>
                                <div id="galFileList" class="d-flex flex-wrap gap-1 mt-1"></div>
                            </div>

                            {{-- Yeni URL ekle --}}
                            <label class="form-label fw-600 form-label-sm">veya Yeni URL Ekle <small class="text-muted fw-normal">(her satıra bir URL — mevcut galeriye eklenir)</small></label>
                            <textarea name="gallery_urls" class="form-control form-control-sm" rows="3"
                                      placeholder="https://...">{{ old('gallery_urls', '') }}</textarea>
                        </div>

                        <div class="card border-0 shadow-sm p-4 mb-3">
                            <div class="section-title mt-0">Kapak Görseli</div>
                            @if(isset($item) && $item->cover_image)
                            <img src="{{ str_starts_with($item->cover_image, 'http') ? $item->cover_image : asset('uploads/'.$item->cover_image) }}" class="img-fluid rounded mb-2" style="max-height:180px;object-fit:cover;" alt="Kapak">
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

                        <div class="card border-0 shadow-sm p-4 mb-3">
                            <div class="section-title mt-0">Konum Etiketleri</div>
                            <div class="form-text mb-2">Bir ürün birden fazla lokasyonda görünebilir (belde, ilçe, il, bölge, ülke).</div>
                            <input type="hidden" name="locations_json" id="locationsJson"
                                   value="{{ json_encode(isset($item) ? $item->locations->map(fn($l) => ['type'=>$l->type,'name'=>$l->name])->values()->toArray() : []) }}">
                            <div id="locationTags" class="d-flex flex-wrap gap-1 mb-2"></div>
                            <div class="d-flex gap-1">
                                <select id="locType" class="form-select form-select-sm" style="width:120px;flex-shrink:0;">
                                    <option value="belde">Belde</option>
                                    <option value="ilce">İlçe</option>
                                    <option value="il" selected>İl / Şehir</option>
                                    <option value="bolge">Bölge</option>
                                    <option value="ulke">Ülke</option>
                                </select>
                                <input type="text" id="locName" class="form-control form-control-sm" placeholder="Ör: İstanbul">
                                <button type="button" class="btn btn-sm btn-outline-primary px-3" onclick="addLocation()">Ekle</button>
                            </div>
                            <script>
                            (function(){
                                const typeLabels = {belde:'Belde',ilce:'İlçe',il:'İl',bolge:'Bölge',ulke:'Ülke'};
                                let locs = JSON.parse(document.getElementById('locationsJson').value || '[]');
                                function render(){
                                    const c = document.getElementById('locationTags');
                                    c.innerHTML = locs.map((l,i) =>
                                        `<span class="badge bg-secondary d-inline-flex align-items-center gap-1" style="font-size:.78rem;font-weight:500;">
                                            <span style="opacity:.75;font-size:.7rem;">${typeLabels[l.type]??l.type}</span> ${l.name}
                                            <button type="button" onclick="removeLocation(${i})" class="btn-close btn-close-white ms-1" style="font-size:.55rem;"></button>
                                        </span>`
                                    ).join('');
                                    document.getElementById('locationsJson').value = JSON.stringify(locs);
                                }
                                window.addLocation = function(){
                                    const name = document.getElementById('locName').value.trim();
                                    const type = document.getElementById('locType').value;
                                    if(!name) return;
                                    locs.push({type,name});
                                    document.getElementById('locName').value = '';
                                    render();
                                };
                                window.removeLocation = function(i){ locs.splice(i,1); render(); };
                                document.getElementById('locName').addEventListener('keydown', e => { if(e.key==='Enter'){e.preventDefault();addLocation();} });
                                render();
                            })();
                            </script>
                        </div>

                        <div class="card border-0 shadow-sm p-4">
                            <div class="section-title mt-0">Puanlama (Manuel)</div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label form-label-sm">Puan Avg</label>
                                    <input type="number" name="rating_avg" class="form-control form-control-sm" step="0.01" min="0" max="5"
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

// Ürün tipi → alt tip filtrele
const subtypeMap = {
    transfer: [['airport_transfer','Havalimanı Transferi'],['intercity_transfer','Şehirlerarası Transfer']],
    charter:  [['private_jet','Özel Jet'],['helicopter_tour','Helikopter Turu']],
    leisure:  [['dinner_cruise','Dinner Cruise'],['evening_show','Akşam Gösterisi'],['yacht_charter','Yat Kiralama']],
    tour:     [['day_tour','Günübirlik Tur'],['multi_day_tour','Çok Günlük Tur'],['activity_tour','Aktivite Turu']],
    hotel:    [['hotel_room','Otel Odası'],['apart_rental','Apart Kiralama']],
    visa:     [['visa_service','Vize Hizmeti']],
    sigorta:  [['seyahat_tc','Yurtdışı Sigorta (TC Kimlikli)'],['seyahat_pasaport','Yurtdışı Sigorta (Pasaportlu)'],['seyahat_toplu','Toplu Grup Sigortası']],
    other:    [['corporate_event','Kurumsal Etkinlik'],['event_ticket','Etkinlik Bileti'],['admission_ticket','Müze / Giriş Bileti'],['timed_experience','Deneyim Turu (Tadım, Workshop vb.)']],
};
const currentSubtype = '{{ old('product_subtype', $item->product_subtype ?? '') }}';
const subtypeSel = document.getElementById('productSubtype');
const productTypeSel = document.getElementById('productType');

function updateSubtypes() {
    const type = productTypeSel.value;
    const options = subtypeMap[type] || [];
    subtypeSel.innerHTML = '<option value="">— Otomatik / Seçin —</option>';
    options.forEach(([val, label]) => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = label;
        if (val === currentSubtype) opt.selected = true;
        subtypeSel.appendChild(opt);
    });
    if (options.length === 1 && !currentSubtype) subtypeSel.value = options[0][0];
}
productTypeSel.addEventListener('change', updateSubtypes);
updateSubtypes();

// ── Galeri Yönetimi ────────────────────────────────────────────────────────
window.galRemove = function(btn) {
    const item = btn.closest('.gal-item');
    const url  = item.dataset.url;
    const inp  = document.getElementById('galKeepJson');
    let list = JSON.parse(inp.value || '[]');
    list = list.filter(u => u !== url);
    inp.value = JSON.stringify(list);
    item.remove();
};

document.getElementById('galleryFilesInput')?.addEventListener('change', function() {
    const box = document.getElementById('galFileList');
    box.innerHTML = '';
    Array.from(this.files).forEach(f => {
        const sp = document.createElement('span');
        sp.className = 'badge bg-secondary text-truncate d-inline-block';
        sp.style.maxWidth = '200px';
        sp.title = f.name;
        sp.textContent = f.name + ' (' + (f.size/1048576).toFixed(1) + ' MB)';
        box.appendChild(sp);
    });
});

// ── AI Doldur ──────────────────────────────────────────────────────────────
async function aiFillFields() {
    const text = document.getElementById('fullDesc').value.trim();
    if (!text) {
        alert('Önce Tam Açıklama alanına ürün metnini yapıştırın.');
        return;
    }

    const btn  = document.getElementById('aiFillBtn');
    const stat = document.getElementById('aiStatus');
    btn.disabled = true;
    document.getElementById('aiFillBtnText').textContent = 'Analiz ediliyor…';
    stat.textContent = '';

    try {
        const resp = await fetch('{{ route('superadmin.b2c.catalog.ai-fill') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
                    || '{{ csrf_token() }}',
            },
            body: JSON.stringify({ text }),
        });

        const data = await resp.json();

        if (data.error) {
            stat.textContent = '⚠ ' + data.error;
            stat.style.color = '#dc3545';
            return;
        }

        let filled = 0;

        function fill(selector, value) {
            if (value === null || value === undefined || value === '') return;
            const el = document.querySelector(selector);
            if (!el) return;
            el.value = value;
            el.classList.add('ai-filled');
            setTimeout(() => el.classList.remove('ai-filled'), 3000);
            filled++;
        }

        fill('[name=title]',               data.title);
        fill('[name=short_desc]',          data.short_desc);
        fill('#fullDesc',                  data.full_desc);
        fill('[name=destination_city]',    data.destination_city);
        fill('[name=destination_country]', data.destination_country);
        fill('[name=duration_days]',       data.duration_days);
        fill('[name=duration_hours]',      data.duration_hours);
        fill('[name=min_pax]',             data.min_pax);
        fill('[name=max_pax]',             data.max_pax);
        fill('[name=meta_title]',          data.meta_title);
        fill('[name=meta_description]',    data.meta_description);

        if (data.base_price) fill('[name=base_price]', data.base_price);
        if (data.currency)   fill('[name=currency]',   data.currency);

        // Slug yeniden üret (başlık değiştiyse)
        const slugField = document.querySelector('[name=slug]');
        if (data.title && slugField && !slugField.value) {
            slugField.value = data.title
                .toLowerCase()
                .replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s')
                .replace(/ı/g,'i').replace(/ö/g,'o').replace(/ç/g,'c')
                .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
        }

        stat.textContent = `✓ ${filled} alan dolduruldu — kontrol edip kaydet`;
        stat.style.color = '#198754';

    } catch(e) {
        stat.textContent = '⚠ Bağlantı hatası: ' + e.message;
        stat.style.color = '#dc3545';
    } finally {
        btn.disabled = false;
        document.getElementById('aiFillBtnText').textContent = 'AI ile Doldur';
    }
}
</script>

<script>
function grGeocode() {
    var address = document.getElementById('venueAddressInput').value.trim();
    if (!address) { alert('Lütfen adres girin.'); return; }
    var status = document.getElementById('venueGeoStatus');
    status.textContent = 'Konum aranıyor...';
    status.style.color = '#6c757d';
    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ address: address, region: 'tr' }, function(results, gStatus) {
        if (gStatus !== 'OK' || !results || !results[0]) {
            status.textContent = '⚠ Konum bulunamadı: ' + gStatus;
            status.style.color = '#dc3545';
            return;
        }
        var loc = results[0].geometry.location;
        document.getElementById('venueLat').value = loc.lat().toFixed(7);
        document.getElementById('venueLng').value = loc.lng().toFixed(7);

        // Şehir / İlçe / Bölge otomatik doldur
        var comps = results[0].address_components;
        var city = '', district = '', area = '';
        comps.forEach(function(c) {
            if (c.types.indexOf('administrative_area_level_1') !== -1)
                city = c.long_name.replace(/\s+İli$/i, '').replace(/\s+Ili$/i, '');
            if (c.types.indexOf('administrative_area_level_2') !== -1)
                district = c.long_name.replace(/\s+İlçesi$/i, '').replace(/\s+Ilcesi$/i, '');
            if (c.types.indexOf('administrative_area_level_4') !== -1 || c.types.indexOf('sublocality_level_1') !== -1 || c.types.indexOf('neighborhood') !== -1)
                area = c.long_name;
        });
        if (city)     { var el = document.getElementById('destinationCity');     if (el && !el.value) el.value = city; }
        if (district) { var el = document.getElementById('destinationDistrict'); if (el && !el.value) el.value = district; }
        if (area)     { var el = document.getElementById('destinationArea');     if (el && !el.value) el.value = area; }

        status.innerHTML = '<span style="color:#198754"><i class="bi bi-check-circle"></i> Bulundu: ' + loc.lat().toFixed(5) + ', ' + loc.lng().toFixed(5) + ' — <em>' + results[0].formatted_address + '</em></span>';
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA4CoEHudF9V3Zn4h6udx6Ftr3u6h51EXo&language=tr" async defer></script>

<style>
.ai-filled { transition: background .3s; background: #fffbe6 !important; }
</style>
</body>
</html>

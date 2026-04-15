<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>Leisure Ayarlari</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .leisure-settings .shell-card{border-radius:18px;border:1px solid rgba(148,163,184,.2)}
        .leisure-settings .code-badge{font-size:.72rem;padding:.25rem .5rem;border-radius:999px;background:rgba(37,99,235,.12);color:#1d4ed8;font-weight:700}
        .leisure-settings .edit-surface{border-top:1px dashed rgba(148,163,184,.35);background:rgba(248,250,252,.55)}
        .leisure-settings .thumb-preview{width:68px;height:50px;object-fit:cover;border-radius:10px;border:1px solid rgba(148,163,184,.25)}
        html[data-theme="dark"] .leisure-settings .shell-card{border-color:#2d4371}
        html[data-theme="dark"] .leisure-settings .edit-surface{border-top-color:#2d4371;background:rgba(15,29,54,.55)}
        html[data-theme="dark"] .leisure-settings .thumb-preview{border-color:#2d4371}
    </style>
</head>
<body class="theme-scope leisure-settings">
<x-navbar-superadmin active="leisure-settings" />

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Leisure Ayarlari</h3>
            <div class="text-muted small">Dinner Cruise ve Yacht Charter icin paket, ekstra ve medya kutuphanesini yonetin.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('superadmin.dinner-cruise.showcase') }}" class="btn btn-primary btn-sm">Dinner Vitrin</a>
            <a href="{{ route('superadmin.dinner-cruise.index') }}" class="btn btn-outline-primary btn-sm">Dinner Talepleri</a>
            <a href="{{ route('superadmin.yacht-charter.index') }}" class="btn btn-outline-secondary btn-sm">Yacht Talepleri</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @php
        $bosphorusPackageSample = [
            'product_type' => 'dinner_cruise',
            'code' => 'bosphorus_dinner_cruise',
            'level' => 'premium',
            'sort_order' => 15,
            'name_tr' => 'Bosphorus Dinner Cruise',
            'name_en' => 'Bosphorus Dinner Cruise',
            'summary_tr' => 'Bogaz hattinda premium masa, show ve transfer dahil aksam deneyimi.',
            'summary_en' => 'Evening Bosphorus dinner cruise with premium seating, show and transfer support.',
            'hero_image_url' => '/uploads/leisure-media/seed-bosphorus/bosphorus-exterior-night-blue.jpg',
            'includes_tr_text' => "Shuttle transfer\nPremium menu\nBogaz manzarali premium masa\nCanli show programi",
            'includes_en_text' => "Shuttle transfer\nPremium menu\nPremium Bosphorus view table\nLive show program",
            'excludes_tr_text' => "Private yacht kapama\nOzel foto-video cekimi",
            'excludes_en_text' => "Private yacht buyout\nPrivate photo-video production",
            'is_active' => true,
        ];
    @endphp

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card shell-card shadow-sm mb-4">
                <div class="card-header fw-bold d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span>Paket Sablonu Ekle</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="fillBosphorusPackageBtn">Bosphorus Ornegini Doldur</button>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.leisure.settings.packages.store') }}" enctype="multipart/form-data" class="row g-3" id="leisurePackageCreateForm" data-bosphorus-sample='@json($bosphorusPackageSample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)'>
                        @csrf
                        <div class="col-12 col-md-6"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="dinner_cruise">Dinner Cruise</option><option value="yacht">Yacht Charter</option><option value="tour">Tur Paketi</option></select></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kod</label><input type="text" name="code" class="form-control" placeholder="standard"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Seviye</label><input type="text" name="level" class="form-control" placeholder="standard"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="100"></div>
                        <div class="col-12"><label class="form-label">TR Ad</label><input type="text" name="name_tr" class="form-control"></div>
                        <div class="col-12"><label class="form-label">EN Ad</label><input type="text" name="name_en" class="form-control"></div>
                        <div class="col-12"><label class="form-label">TR Ozet</label><input type="text" name="summary_tr" class="form-control"></div>
                        <div class="col-12"><label class="form-label">EN Ozet</label><input type="text" name="summary_en" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Hero Gorsel URL / Path</label><input type="text" name="hero_image_url" class="form-control" placeholder="/uploads/leisure-media/... veya https://..."></div>
                        <div class="col-12"><label class="form-label">Hero Gorsel Dosyasi (opsiyonel)</label><input type="file" name="hero_image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif"><div class="form-text">Dosya secilirse URL yerine bu dosya kullanilir.</div></div>
                        <div class="col-12"><label class="form-label">TR Dahil Olanlar</label><textarea name="includes_tr_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">EN Dahil Olanlar</label><textarea name="includes_en_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">TR Haric Olanlar</label><textarea name="excludes_tr_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">EN Haric Olanlar</label><textarea name="excludes_en_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold text-uppercase">Fiyat & Katalog Bilgileri</small></div>
                        <div class="col-12 col-md-6"><label class="form-label">B2B Fiyat (kisi basi)</label><input type="number" step="0.01" name="base_price_per_person" class="form-control" placeholder="850.00"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Önerilen Satış Fiyatı</label><input type="number" step="0.01" name="original_price_per_person" class="form-control" placeholder="1060.00"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Para Birimi</label><input type="text" name="currency" class="form-control" value="TRY" maxlength="3"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Sure (saat)</label><input type="number" step="0.5" name="duration_hours" class="form-control" placeholder="3"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Max Kisi</label><input type="number" name="max_pax" class="form-control" placeholder="300"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Iskele</label><input type="text" name="pier_name" class="form-control" placeholder="Kabatas"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Rozet Metni</label><input type="text" name="badge_text" class="form-control" placeholder="En Cok Tercih Edilen"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Puan (0-5)</label><input type="number" step="0.1" name="rating" class="form-control" placeholder="4.6"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Yorum Sayisi</label><input type="number" name="review_count" class="form-control" placeholder="2053"></div>
                        <div class="col-12"><label class="form-label">Kalkis Saatleri <small class="text-muted">(her satira bir saat)</small></label><textarea name="departure_times_text" class="form-control" rows="2" placeholder="19:30&#10;22:00"></textarea></div>
                        <div class="col-12"><label class="form-label">Bulusma Noktasi</label><input type="text" name="meeting_point" class="form-control"></div>
                        <div class="col-12"><label class="form-label">TR Uzun Aciklama</label><textarea name="long_description_tr" class="form-control" rows="4"></textarea></div>
                        <div class="col-12"><label class="form-label">EN Uzun Aciklama</label><textarea name="long_description_en" class="form-control" rows="4"></textarea></div>
                        <div class="col-12"><label class="form-label">Timeline JSON <small class="text-muted">[{"time":"...","title":"...","desc":"..."}]</small></label><textarea name="timeline_tr_json" class="form-control font-monospace" rows="5" spellcheck="false"></textarea></div>
                        <div class="col-12"><label class="form-label">Iptal Politikasi (TR)</label><textarea name="cancellation_policy_tr" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">Onemli Notlar (TR) <small class="text-muted">(her satira bir madde)</small></label><textarea name="important_notes_tr_text" class="form-control" rows="3"></textarea></div>
                        <div class="col-12 form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div>
                        <div class="col-12"><button class="btn btn-primary">Paket Ekle</button></div>
                    </form>
                </div>
            </div>

            <div class="card shell-card shadow-sm">
                <div class="card-header fw-bold">Ekstra Secenegi Ekle</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.leisure.settings.extras.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12 col-md-6"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="">Tum urunler</option><option value="dinner_cruise">Dinner Cruise</option><option value="yacht">Yacht Charter</option><option value="tour">Tur Paketi</option></select></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" placeholder="transfer"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kod</label><input type="text" name="code" class="form-control" placeholder="vip_transfer"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="100"></div>
                        <div class="col-12"><label class="form-label">TR Baslik</label><input type="text" name="title_tr" class="form-control"></div>
                        <div class="col-12"><label class="form-label">EN Baslik</label><input type="text" name="title_en" class="form-control"></div>
                        <div class="col-12"><label class="form-label">TR Aciklama</label><input type="text" name="description_tr" class="form-control"></div>
                        <div class="col-12"><label class="form-label">EN Aciklama</label><input type="text" name="description_en" class="form-control"></div>
                        <div class="col-12 d-flex flex-wrap gap-3">
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="default_included" value="1"><label class="form-check-label">Varsayilan dahil</label></div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div>
                        </div>
                        <div class="col-12"><button class="btn btn-primary">Ekstra Ekle</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card shell-card shadow-sm mb-4">
                <div class="card-header fw-bold">Medya Kutuphanesi</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.leisure.settings.media.store') }}" enctype="multipart/form-data" class="row g-3 mb-4">
                        @csrf
                        <div class="col-12 col-md-4"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="">Tum urunler</option><option value="dinner_cruise">Dinner Cruise</option><option value="yacht">Yacht Charter</option><option value="tour">Tur Paketi</option></select></div>
                        <div class="col-12 col-md-4"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" placeholder="ambiyans"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Medya Tipi</label><select name="media_type" class="form-select"><option value="photo">Foto</option><option value="video">Video</option></select></div>
                        <div class="col-12 col-md-4"><label class="form-label">Kaynak Tipi</label><select name="source_type" class="form-select"><option value="upload">Upload</option><option value="link">Link</option></select></div>
                        <div class="col-12 col-md-4"><label class="form-label">TR Baslik</label><input type="text" name="title_tr" class="form-control"></div>
                        <div class="col-12 col-md-4"><label class="form-label">EN Baslik</label><input type="text" name="title_en" class="form-control"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Link</label><input type="url" name="external_url" class="form-control" placeholder="https://..."></div>
                        <div class="col-12 col-md-6"><label class="form-label">Dosya</label><input type="file" name="upload_file" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Etiketler</label><textarea name="tags_text" class="form-control" rows="2"></textarea></div>
                        <div class="col-12 col-md-3"><label class="form-label">Min kapasite</label><input type="number" name="capacity_min" class="form-control"></div>
                        <div class="col-12 col-md-3"><label class="form-label">Max kapasite</label><input type="number" name="capacity_max" class="form-control"></div>
                        <div class="col-12 col-md-3"><label class="form-label">Luks seviyesi</label><input type="text" name="luxury_level" class="form-control" placeholder="vip"></div>
                        <div class="col-12 col-md-3"><label class="form-label">Kullanim tipi</label><input type="text" name="usage_type" class="form-control" placeholder="shared/private"></div>
                        <div class="col-12 col-md-3"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="100"></div>
                        <div class="col-12 col-md-3 form-check form-switch align-self-end"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div>
                        <div class="col-12"><button class="btn btn-primary">Medya Ekle</button></div>
                    </form>

                    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Medya</th><th>Urun</th><th>Kategori</th><th>Tip</th><th>Etiket</th><th>Durum</th><th class="text-end">Islem</th></tr></thead>
                        <tbody>
                        @forelse($mediaAssets as $asset)
                            <tr>
                                <td><div class="d-flex align-items-center gap-2">@if($asset->media_type==='photo')<img src="{{ $asset->resolvedUrl() }}" alt="{{ $asset->title_tr }}" class="thumb-preview">@else<div class="thumb-preview d-flex align-items-center justify-content-center bg-body-secondary small fw-bold">VIDEO</div>@endif<div><div class="fw-semibold">{{ $asset->title_tr }}</div>@if($asset->title_en)<div class="small text-muted">{{ $asset->title_en }}</div>@endif</div></div></td>
                                <td>{{ $asset->product_type ?: 'Tum urunler' }}</td><td>{{ $asset->category ?: '-' }}</td><td>{{ strtoupper($asset->media_type) }}</td>
                                <td>@foreach(($asset->tags_json ?? []) as $tag)<span class="code-badge">{{ $tag }}</span>@endforeach</td>
                                <td>{{ $asset->is_active ? 'Aktif' : 'Pasif' }}</td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#media-edit-{{ $asset->id }}">Duzenle</button></td>
                            </tr>
                            <tr class="collapse" id="media-edit-{{ $asset->id }}"><td colspan="7" class="edit-surface p-0">
                                <form method="POST" action="{{ route('superadmin.leisure.settings.media.update', $asset) }}" enctype="multipart/form-data" class="row g-3 p-3">
                                    @csrf @method('PATCH')
                                    <div class="col-12 col-md-3"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="" @selected($asset->product_type===null)>Tum urunler</option><option value="dinner_cruise" @selected($asset->product_type==='dinner_cruise')>Dinner Cruise</option><option value="yacht" @selected($asset->product_type==='yacht')>Yacht Charter</option><option value="tour" @selected($asset->product_type==='tour')>Tur Paketi</option></select></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" value="{{ $asset->category }}"></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Medya Tipi</label><select name="media_type" class="form-select"><option value="photo" @selected($asset->media_type==='photo')>Foto</option><option value="video" @selected($asset->media_type==='video')>Video</option></select></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Kaynak Tipi</label><select name="source_type" class="form-select"><option value="upload" @selected($asset->source_type==='upload')>Upload</option><option value="link" @selected($asset->source_type==='link')>Link</option></select></div>
                                    <div class="col-12 col-md-6"><label class="form-label">TR Baslik</label><input type="text" name="title_tr" class="form-control" value="{{ $asset->title_tr }}"></div>
                                    <div class="col-12 col-md-6"><label class="form-label">EN Baslik</label><input type="text" name="title_en" class="form-control" value="{{ $asset->title_en }}"></div>
                                    <div class="col-12 col-md-6"><label class="form-label">Link</label><input type="url" name="external_url" class="form-control" value="{{ $asset->external_url }}"></div>
                                    <div class="col-12 col-md-6"><label class="form-label">Dosya (opsiyonel)</label><input type="file" name="upload_file" class="form-control"></div>
                                    <div class="col-12"><label class="form-label">Etiketler</label><textarea name="tags_text" class="form-control" rows="2">{{ implode(', ', $asset->tags_json ?? []) }}</textarea></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Min kapasite</label><input type="number" name="capacity_min" class="form-control" value="{{ $asset->capacity_min }}"></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Max kapasite</label><input type="number" name="capacity_max" class="form-control" value="{{ $asset->capacity_max }}"></div>
                                    <div class="col-12 col-md-2"><label class="form-label">Luks</label><input type="text" name="luxury_level" class="form-control" value="{{ $asset->luxury_level }}"></div>
                                    <div class="col-12 col-md-2"><label class="form-label">Kullanim</label><input type="text" name="usage_type" class="form-control" value="{{ $asset->usage_type }}"></div>
                                    <div class="col-12 col-md-2"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="{{ $asset->sort_order }}"></div>
                                    <div class="col-12 d-flex flex-wrap gap-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($asset->is_active)><label class="form-check-label">Aktif</label></div></div>
                                    <div class="col-12"><button class="btn btn-primary btn-sm">Kaydi Guncelle</button></div>
                                </form>
                            </td></tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Henuz medya eklenmedi.</td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                </div>
            </div>

            <div class="card shell-card shadow-sm mb-4">
                <div class="card-header fw-bold">Paketler</div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Urun</th><th>Kod</th><th>Hero</th><th>TR Ad</th><th>EN Ad</th><th>Durum</th><th class="text-end">Islem</th></tr></thead>
                    <tbody>
                    @forelse($packages as $package)
                        <tr>
                            <td>{{ $package->product_type }}</td>
                            <td><span class="code-badge">{{ $package->code }}</span></td>
                            <td>
                                @if($package->hero_image_url)
                                    <img src="{{ $package->hero_image_url }}" alt="{{ $package->name_tr }}" class="thumb-preview">
                                @else
                                    <span class="text-muted small">Yok</span>
                                @endif
                            </td>
                            <td>{{ $package->name_tr }}</td><td>{{ $package->name_en }}</td><td>{{ $package->is_active ? 'Aktif' : 'Pasif' }}</td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#package-edit-{{ $package->id }}">Duzenle</button></td>
                        </tr>
                        <tr class="collapse" id="package-edit-{{ $package->id }}"><td colspan="7" class="edit-surface p-0">

                            {{-- ── Galeri Medya (foto + video) ── --}}
                            @php $pkgGallery = $galleryAssets[$package->code] ?? collect(); @endphp
                            <div class="p-3 border-bottom">
                                <div class="fw-semibold mb-2 small text-uppercase text-muted">Galeri ({{ $pkgGallery->count() }}/6) — foto veya video</div>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    @forelse($pkgGallery as $gItem)
                                        <div class="position-relative" style="width:100px;">
                                            @if($gItem->media_type === 'video')
                                                <div style="width:100px;height:72px;border-radius:6px;border:1px solid #dee2e6;background:#111;display:flex;align-items:center;justify-content:center;">
                                                    <span style="color:#fff;font-size:.65rem;font-weight:700;">&#9654; VIDEO</span>
                                                </div>
                                            @else
                                                <img src="{{ $gItem->resolvedUrl() }}" alt="{{ $gItem->title_tr }}" style="width:100px;height:72px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;">
                                            @endif
                                            <form method="POST" action="{{ route('superadmin.leisure.settings.gallery.delete', $gItem) }}" style="position:absolute;top:2px;right:2px;" onsubmit="return confirm('Silinsin mi?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm p-0" style="width:20px;height:20px;font-size:10px;line-height:1;">✕</button>
                                            </form>
                                        </div>
                                    @empty
                                        <span class="text-muted small">Henuz galeri medyasi yok.</span>
                                    @endforelse
                                </div>
                                @if($pkgGallery->count() < 6)
                                <form method="POST" action="{{ route('superadmin.leisure.settings.gallery.store', $package) }}" enctype="multipart/form-data" class="d-flex gap-2 align-items-end flex-wrap">
                                    @csrf
                                    <div>
                                        <label class="form-label small mb-1">Dosyalar — maks {{ 6 - $pkgGallery->count() }} adet (foto maks 10MB, video maks 50MB)</label>
                                        <input type="file" name="gallery_photos[]" class="form-control form-control-sm"
                                               accept=".jpg,.jpeg,.png,.webp,.avif,.mp4,.webm,.mov"
                                               multiple required style="width:320px;">
                                    </div>
                                    <div>
                                        <label class="form-label small mb-1">Baslik (opsiyonel)</label>
                                        <input type="text" name="gallery_title" class="form-control form-control-sm" placeholder="{{ $package->name_tr }}" style="width:160px;">
                                    </div>
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Yukle</button>
                                </form>
                                @else
                                    <div class="text-muted small">Maksimum 6 medya yuklenebilir. Yer acmak icin birini silin.</div>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('superadmin.leisure.settings.packages.update', $package) }}" enctype="multipart/form-data" class="row g-3 p-3">
                                @csrf @method('PATCH')
                                <div class="col-12 col-md-3"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="dinner_cruise" @selected($package->product_type==='dinner_cruise')>Dinner Cruise</option><option value="yacht" @selected($package->product_type==='yacht')>Yacht Charter</option><option value="tour" @selected($package->product_type==='tour')>Tur Paketi</option></select></div>
                                <div class="col-12 col-md-3"><label class="form-label">Kod</label><input type="text" name="code" class="form-control" value="{{ $package->code }}"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Seviye</label><input type="text" name="level" class="form-control" value="{{ $package->level }}"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="{{ $package->sort_order }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Ad</label><input type="text" name="name_tr" class="form-control" value="{{ $package->name_tr }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Ad</label><input type="text" name="name_en" class="form-control" value="{{ $package->name_en }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Ozet</label><input type="text" name="summary_tr" class="form-control" value="{{ $package->summary_tr }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Ozet</label><input type="text" name="summary_en" class="form-control" value="{{ $package->summary_en }}"></div>
                                <div class="col-12"><label class="form-label">Hero Gorsel URL / Path</label><input type="text" name="hero_image_url" class="form-control" value="{{ $package->hero_image_url }}" placeholder="/uploads/leisure-media/... veya https://..."></div>
                                <div class="col-12"><label class="form-label">Hero Gorsel Dosyasi (opsiyonel)</label><input type="file" name="hero_image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.avif"><div class="form-text">Yeni dosya secersen mevcut URL/path yerine bu dosya kullanilir.</div></div>
                                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="clear_hero_image" value="1" id="clear-hero-image-{{ $package->id }}"><label class="form-check-label" for="clear-hero-image-{{ $package->id }}">Mevcut hero gorseli kaldir</label></div></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Dahil Olanlar</label><textarea name="includes_tr_text" class="form-control" rows="3">{{ implode(PHP_EOL, $package->includes_tr ?? []) }}</textarea></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Dahil Olanlar</label><textarea name="includes_en_text" class="form-control" rows="3">{{ implode(PHP_EOL, $package->includes_en ?? []) }}</textarea></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Haric Olanlar</label><textarea name="excludes_tr_text" class="form-control" rows="3">{{ implode(PHP_EOL, $package->excludes_tr ?? []) }}</textarea></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Haric Olanlar</label><textarea name="excludes_en_text" class="form-control" rows="3">{{ implode(PHP_EOL, $package->excludes_en ?? []) }}</textarea></div>

                                {{-- Katalog / Fiyat Alanlari --}}
                                <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold text-uppercase">Fiyat & Katalog Bilgileri</small></div>
                                <div class="col-12 col-md-3"><label class="form-label">B2B Fiyat (kisi basi)</label><input type="number" step="0.01" name="base_price_per_person" class="form-control" value="{{ $package->base_price_per_person }}" placeholder="850.00"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Önerilen Satış Fiyatı</label><input type="number" step="0.01" name="original_price_per_person" class="form-control" value="{{ $package->original_price_per_person }}" placeholder="1060.00"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Para Birimi</label><input type="text" name="currency" class="form-control" value="{{ $package->currency ?? 'TRY' }}" placeholder="TRY" maxlength="3"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Sure (saat)</label><input type="number" step="0.5" name="duration_hours" class="form-control" value="{{ $package->duration_hours }}" placeholder="3"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Max Kisi</label><input type="number" name="max_pax" class="form-control" value="{{ $package->max_pax }}" placeholder="300"></div>

                                <div class="col-12 col-md-4"><label class="form-label">Iskele / Kalkis Noktasi</label><input type="text" name="pier_name" class="form-control" value="{{ $package->pier_name }}" placeholder="Kabatas"></div>
                                <div class="col-12 col-md-4"><label class="form-label">Rozet Metni</label><input type="text" name="badge_text" class="form-control" value="{{ $package->badge_text }}" placeholder="En Cok Tercih Edilen"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Puan (0-5)</label><input type="number" step="0.1" name="rating" class="form-control" value="{{ $package->rating }}" placeholder="4.6"></div>
                                <div class="col-12 col-md-2"><label class="form-label">Yorum Sayisi</label><input type="number" name="review_count" class="form-control" value="{{ $package->review_count }}" placeholder="2053"></div>

                                <div class="col-12"><label class="form-label">Kalkis Saatleri <small class="text-muted">(her satira bir saat, ornek: 19:30)</small></label><textarea name="departure_times_text" class="form-control" rows="2">{{ implode(PHP_EOL, $package->departure_times ?? []) }}</textarea></div>
                                <div class="col-12"><label class="form-label">Bulusma Noktasi</label><input type="text" name="meeting_point" class="form-control" value="{{ $package->meeting_point }}"></div>

                                <div class="col-12"><label class="form-label">TR Uzun Aciklama</label><textarea name="long_description_tr" class="form-control" rows="5">{{ $package->long_description_tr }}</textarea></div>
                                <div class="col-12"><label class="form-label">EN Uzun Aciklama</label><textarea name="long_description_en" class="form-control" rows="5">{{ $package->long_description_en }}</textarea></div>

                                <div class="col-12"><label class="form-label">Program / Timeline (JSON) <small class="text-muted">[{"time":"19:00","title":"...","desc":"..."}]</small></label><textarea name="timeline_tr_json" class="form-control font-monospace" rows="6" spellcheck="false">{{ $package->timeline_tr ? json_encode($package->timeline_tr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '' }}</textarea></div>

                                <div class="col-12"><label class="form-label">Iptal Politikasi (TR)</label><textarea name="cancellation_policy_tr" class="form-control" rows="3">{{ $package->cancellation_policy_tr }}</textarea></div>
                                <div class="col-12"><label class="form-label">Onemli Notlar (TR) <small class="text-muted">(her satira bir madde)</small></label><textarea name="important_notes_tr_text" class="form-control" rows="4">{{ implode(PHP_EOL, $package->important_notes_tr ?? []) }}</textarea></div>

                                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($package->is_active)><label class="form-check-label">Aktif</label></div></div>
                                <div class="col-12"><button class="btn btn-primary btn-sm">Kaydi Guncelle</button></div>
                            </form>
                        </td></tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Paket kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table></div></div>
            </div>

            <div class="card shell-card shadow-sm">
                <div class="card-header fw-bold">Ekstralar</div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Urun</th><th>Kod</th><th>TR Baslik</th><th>Varsayilan</th><th>Durum</th><th class="text-end">Islem</th></tr></thead>
                    <tbody>
                    @forelse($extras as $extra)
                        <tr>
                            <td>{{ $extra->product_type ?: 'Tum urunler' }}</td><td><span class="code-badge">{{ $extra->code }}</span></td><td>{{ $extra->title_tr }}</td><td>{{ $extra->default_included ? 'Evet' : 'Hayir' }}</td><td>{{ $extra->is_active ? 'Aktif' : 'Pasif' }}</td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#extra-edit-{{ $extra->id }}">Duzenle</button></td>
                        </tr>
                        <tr class="collapse" id="extra-edit-{{ $extra->id }}"><td colspan="6" class="edit-surface p-0">
                            <form method="POST" action="{{ route('superadmin.leisure.settings.extras.update', $extra) }}" class="row g-3 p-3">
                                @csrf @method('PATCH')
                                <div class="col-12 col-md-3"><label class="form-label">Urun</label><select name="product_type" class="form-select"><option value="" @selected($extra->product_type===null)>Tum urunler</option><option value="dinner_cruise" @selected($extra->product_type==='dinner_cruise')>Dinner Cruise</option><option value="yacht" @selected($extra->product_type==='yacht')>Yacht Charter</option><option value="tour" @selected($extra->product_type==='tour')>Tur Paketi</option></select></div>
                                <div class="col-12 col-md-3"><label class="form-label">Kategori</label><input type="text" name="category" class="form-control" value="{{ $extra->category }}"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Kod</label><input type="text" name="code" class="form-control" value="{{ $extra->code }}"></div>
                                <div class="col-12 col-md-3"><label class="form-label">Sira</label><input type="number" name="sort_order" class="form-control" value="{{ $extra->sort_order }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Baslik</label><input type="text" name="title_tr" class="form-control" value="{{ $extra->title_tr }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Baslik</label><input type="text" name="title_en" class="form-control" value="{{ $extra->title_en }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">TR Aciklama</label><input type="text" name="description_tr" class="form-control" value="{{ $extra->description_tr }}"></div>
                                <div class="col-12 col-md-6"><label class="form-label">EN Aciklama</label><input type="text" name="description_en" class="form-control" value="{{ $extra->description_en }}"></div>
                                <div class="col-12 d-flex flex-wrap gap-3">
                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="default_included" value="1" @checked($extra->default_included)><label class="form-check-label">Varsayilan dahil</label></div>
                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($extra->is_active)><label class="form-check-label">Aktif</label></div>
                                </div>
                                <div class="col-12"><button class="btn btn-primary btn-sm">Kaydi Guncelle</button></div>
                            </form>
                        </td></tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Ekstra kaydi yok.</td></tr>
                    @endforelse
                    </tbody>
                </table></div></div>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const form = document.getElementById('leisurePackageCreateForm');
    const fillBtn = document.getElementById('fillBosphorusPackageBtn');
    if (!form || !fillBtn) return;

    const raw = form.getAttribute('data-bosphorus-sample') || '{}';
    let sample = {};
    try {
        sample = JSON.parse(raw);
    } catch (_) {
        sample = {};
    }

    const setValue = (name, value) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;
        field.value = value ?? '';
    };

    fillBtn.addEventListener('click', () => {
        setValue('product_type', sample.product_type || 'dinner_cruise');
        setValue('code', sample.code || 'bosphorus_dinner_cruise');
        setValue('level', sample.level || 'premium');
        setValue('sort_order', sample.sort_order || 15);
        setValue('name_tr', sample.name_tr || 'Bosphorus Dinner Cruise');
        setValue('name_en', sample.name_en || 'Bosphorus Dinner Cruise');
        setValue('summary_tr', sample.summary_tr || '');
        setValue('summary_en', sample.summary_en || '');
        setValue('hero_image_url', sample.hero_image_url || '');
        setValue('includes_tr_text', sample.includes_tr_text || '');
        setValue('includes_en_text', sample.includes_en_text || '');
        setValue('excludes_tr_text', sample.excludes_tr_text || '');
        setValue('excludes_en_text', sample.excludes_en_text || '');

        const activeCheckbox = form.querySelector('input[name="is_active"]');
        if (activeCheckbox) {
            activeCheckbox.checked = Boolean(sample.is_active);
        }
    });
})();
</script>
</body>
</html>

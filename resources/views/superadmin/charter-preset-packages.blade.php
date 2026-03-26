<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>Air Charter Hazir Paketler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .charter-package-page .shell-card { border-radius: 16px; border: 1px solid rgba(148, 163, 184, 0.22); }
        .charter-package-page .code-badge { font-size: .72rem; border-radius: 999px; padding: .2rem .55rem; border: 1px solid rgba(59, 130, 246, .3); background: rgba(59, 130, 246, .1); color: #1d4ed8; font-weight: 700; }
        .charter-package-page .package-thumb { width: 92px; height: 54px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(148, 163, 184, .32); background: #f8fafc; }
        .charter-package-page .edit-surface { border-top: 1px dashed rgba(148, 163, 184, .35); background: rgba(248, 250, 252, .6); }
        html[data-theme="dark"] .charter-package-page .shell-card { border-color: #2d4371; }
        html[data-theme="dark"] .charter-package-page .package-thumb { border-color: #2d4371; background: #0f1d36; }
        html[data-theme="dark"] .charter-package-page .edit-surface { border-top-color: #2d4371; background: rgba(15, 29, 54, .6); }
        html[data-theme="dark"] .charter-package-page .code-badge { border-color: #355ea8; background: rgba(53, 94, 168, .22); color: #9bc3ff; }
    </style>
</head>
<body class="theme-scope charter-package-page">
<x-navbar-superadmin active="charter-packages" />

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Air Charter Hazir Paketler</h3>
            <div class="text-muted small">Acentelerin Air Charter ekraninda gorecegi hazir ucak + rota kartlarini yonetin.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('superadmin.charter.index') }}" class="btn btn-outline-primary btn-sm">Air Charter Talepler</a>
            <a href="{{ route('superadmin.charter.rfq-suppliers.index') }}" class="btn btn-outline-secondary btn-sm">RFQ Tedarikciler</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(!($usesDatabase ?? false))
        <div class="alert alert-warning">
            Paketler su an sistem ayari uzerinden tutuluyor. Tablo olustugunda otomatik olarak DB tarafina gecilebilir.
        </div>
    @endif
    @php
        $heroImageColumnMissing = ($usesDatabase ?? false) && !($heroImageFeatureReady ?? true);
    @endphp
    @if($heroImageColumnMissing)
        <div class="alert alert-warning" data-hero-image-feature-warning="1">
            Hero gorsel kolonu eksik gorunuyor (<code>charter_preset_packages.hero_image_url</code>).
            Kayit aninda otomatik duzeltme denenecek; yine de kalici cozum icin <code>php artisan migrate --force</code> onerilir.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card shell-card shadow-sm">
                <div class="card-header fw-bold">Hazir Paket Ekle</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.charter.packages.store') }}" class="row g-3" enctype="multipart/form-data">
                        @csrf
                        <div class="col-12 col-md-6"><label class="form-label">Kod</label><input name="code" class="form-control" placeholder="ist-ayt-economy-jet-6" required></div>
                        <div class="col-12 col-md-6"><label class="form-label">Ucus Turu</label><select name="transport_type" class="form-select"><option value="jet">Jet</option><option value="helicopter">Helikopter</option><option value="airliner">Charter Ucak</option></select></div>
                        <div class="col-12"><label class="form-label">Paket Basligi</label><input name="title" class="form-control" placeholder="Istanbul - Antalya Ekonomik Jet" required></div>
                        <div class="col-12"><label class="form-label">Kisa Ozet</label><input name="summary" class="form-control" placeholder="6 kisiye kadar kisa/orta mesafe jet paketi."></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kalkis IATA</label><input name="from_iata" class="form-control text-uppercase" placeholder="IST" required></div>
                        <div class="col-12 col-md-6"><label class="form-label">Varis IATA</label><input name="to_iata" class="form-control text-uppercase" placeholder="AYT" required></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kalkis Etiketi</label><input name="from_label" class="form-control" placeholder="Istanbul Airport"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Varis Etiketi</label><input name="to_label" class="form-control" placeholder="Antalya Airport"></div>
                        <div class="col-12"><label class="form-label">Ucak Model Etiketi</label><input name="aircraft_label" class="form-control" placeholder="Cessna Citation CJ2 veya benzeri"></div>
                        <div class="col-12 col-md-4"><label class="form-label">PAX</label><input type="number" min="1" max="400" name="suggested_pax" class="form-control" value="6" required></div>
                        <div class="col-12 col-md-4"><label class="form-label">Trip Type</label><input name="trip_type" class="form-control" value="Tek Yon"></div>
                        <div class="col-12 col-md-4"><label class="form-label">Sort</label><input type="number" min="0" max="9999" name="sort_order" class="form-control" value="100"></div>
                        <div class="col-12 col-md-6"><label class="form-label">Grup Tipi</label><input name="group_type" class="form-control" placeholder="Kurumsal, VIP Tatil..."></div>
                        <div class="col-12 col-md-6"><label class="form-label">Kabin Tercihi</label><select name="cabin_preference" class="form-select"><option value="">Bos</option><option value="ekonomik_jet">Ekonomik Jet</option><option value="vip_jet">VIP Jet</option><option value="farketmez">Farketmez</option></select></div>
                        <div class="col-12 col-md-6"><label class="form-label">Fiyat</label><input type="number" step="0.01" min="0" name="price" class="form-control" value="0" required></div>
                        <div class="col-12 col-md-6"><label class="form-label">Para Birimi</label><input name="currency" class="form-control text-uppercase" value="EUR" required></div>
                        <div class="col-12 col-md-7"><label class="form-label">Hero Gorsel URL / Path</label><input name="hero_image_url" class="form-control" placeholder="https://... veya /storage/..."></div>
                        <div class="col-12 col-md-5"><label class="form-label">Hero Gorsel Yukle</label><input type="file" name="hero_image_file" class="form-control" accept="image/png,image/jpeg,image/webp"></div>
                        @if($heroImageColumnMissing)
                            <div class="col-12"><div class="form-text text-warning">Ilk kayitta kolon otomatik acilacak; sorun devam ederse migration calistirin.</div></div>
                        @endif
                        <div class="col-12"><label class="form-label">Highlightlar (satir satir)</label><textarea name="highlights_text" rows="4" class="form-control" placeholder="Hizli onay sureci&#10;Kabinde ikram dahil"></textarea></div>
                        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Aktif</label></div></div>
                        <div class="col-12"><button class="btn btn-primary">Paketi Ekle</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card shell-card shadow-sm">
                <div class="card-header fw-bold">Mevcut Paketler</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Kod</th>
                                <th>Paket</th>
                                <th>Gorsel</th>
                                <th>Rota</th>
                                <th>PAX</th>
                                <th>Fiyat</th>
                                <th>Durum</th>
                                <th class="text-end">Islem</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($packages as $package)
                                <tr>
                                    <td><span class="code-badge">{{ $package['code'] }}</span></td>
                                    <td>
                                        <div class="fw-semibold">{{ $package['title'] }}</div>
                                        <div class="small text-muted">{{ strtoupper($package['transport_type']) }} · {{ $package['aircraft_label'] ?: '-' }}</div>
                                    </td>
                                    <td>
                                        @if(!empty($package['hero_image_url']))
                                            <img src="{{ $package['hero_image_url'] }}" alt="{{ $package['title'] }} hero gorseli" class="package-thumb" loading="lazy">
                                        @else
                                            <span class="small text-muted">Gorsel yok</span>
                                        @endif
                                    </td>
                                    <td>{{ strtoupper($package['from_iata']) }} - {{ strtoupper($package['to_iata']) }}</td>
                                    <td>{{ $package['suggested_pax'] }}</td>
                                    <td>{{ number_format((float) $package['price'], 0, ',', '.') }} {{ $package['currency'] }}</td>
                                    <td>{{ $package['is_active'] ? 'Aktif' : 'Pasif' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#package-edit-{{ $package['code'] }}">
                                            Duzenle
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="package-edit-{{ $package['code'] }}">
                                    <td colspan="8" class="edit-surface p-0">
                                        <form method="POST" action="{{ route('superadmin.charter.packages.update', ['packageCode' => $package['code']]) }}" class="row g-3 p-3" enctype="multipart/form-data">
                                            @csrf
                                            @method('PATCH')
                                            <div class="col-12 col-md-4"><label class="form-label">Kod</label><input name="code" class="form-control" value="{{ $package['code'] }}" required></div>
                                            <div class="col-12 col-md-4"><label class="form-label">Ucus Turu</label><select name="transport_type" class="form-select"><option value="jet" @selected($package['transport_type']==='jet')>Jet</option><option value="helicopter" @selected($package['transport_type']==='helicopter')>Helikopter</option><option value="airliner" @selected($package['transport_type']==='airliner')>Charter Ucak</option></select></div>
                                            <div class="col-12 col-md-4"><label class="form-label">Sort</label><input type="number" min="0" max="9999" name="sort_order" class="form-control" value="{{ $package['sort_order'] }}"></div>
                                            <div class="col-12"><label class="form-label">Baslik</label><input name="title" class="form-control" value="{{ $package['title'] }}" required></div>
                                            <div class="col-12"><label class="form-label">Ozet</label><input name="summary" class="form-control" value="{{ $package['summary'] }}"></div>
                                            <div class="col-12 col-md-3"><label class="form-label">Kalkis IATA</label><input name="from_iata" class="form-control text-uppercase" value="{{ $package['from_iata'] }}" required></div>
                                            <div class="col-12 col-md-3"><label class="form-label">Varis IATA</label><input name="to_iata" class="form-control text-uppercase" value="{{ $package['to_iata'] }}" required></div>
                                            <div class="col-12 col-md-3"><label class="form-label">PAX</label><input type="number" min="1" max="400" name="suggested_pax" class="form-control" value="{{ $package['suggested_pax'] }}" required></div>
                                            <div class="col-12 col-md-3"><label class="form-label">Trip Type</label><input name="trip_type" class="form-control" value="{{ $package['trip_type'] }}"></div>
                                            <div class="col-12 col-md-6"><label class="form-label">Kalkis Etiketi</label><input name="from_label" class="form-control" value="{{ $package['from_label'] }}"></div>
                                            <div class="col-12 col-md-6"><label class="form-label">Varis Etiketi</label><input name="to_label" class="form-control" value="{{ $package['to_label'] }}"></div>
                                            <div class="col-12"><label class="form-label">Ucak Model Etiketi</label><input name="aircraft_label" class="form-control" value="{{ $package['aircraft_label'] }}"></div>
                                            <div class="col-12 col-md-6"><label class="form-label">Grup Tipi</label><input name="group_type" class="form-control" value="{{ $package['group_type'] }}"></div>
                                            <div class="col-12 col-md-6"><label class="form-label">Kabin Tercihi</label><select name="cabin_preference" class="form-select"><option value="" @selected(($package['cabin_preference'] ?? '')==='')>Bos</option><option value="ekonomik_jet" @selected(($package['cabin_preference'] ?? '')==='ekonomik_jet')>Ekonomik Jet</option><option value="vip_jet" @selected(($package['cabin_preference'] ?? '')==='vip_jet')>VIP Jet</option><option value="farketmez" @selected(($package['cabin_preference'] ?? '')==='farketmez')>Farketmez</option></select></div>
                                            <div class="col-12 col-md-6"><label class="form-label">Fiyat</label><input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ $package['price'] }}" required></div>
                                            <div class="col-12 col-md-6"><label class="form-label">Para Birimi</label><input name="currency" class="form-control text-uppercase" value="{{ $package['currency'] }}" required></div>
                                            <div class="col-12 col-md-7"><label class="form-label">Hero Gorsel URL / Path</label><input name="hero_image_url" class="form-control" value="{{ $package['hero_image_url'] ?? '' }}" placeholder="https://... veya /storage/..."></div>
                                            <div class="col-12 col-md-5"><label class="form-label">Hero Gorsel Yukle</label><input type="file" name="hero_image_file" class="form-control" accept="image/png,image/jpeg,image/webp"></div>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="hero_image_remove" value="1" id="heroImageRemove-{{ $package['code'] }}">
                                                    <label class="form-check-label" for="heroImageRemove-{{ $package['code'] }}">
                                                        Mevcut hero gorseli kaldir (URL alanini temizler)
                                                    </label>
                                                </div>
                                            </div>
                                            @if($heroImageColumnMissing)
                                                <div class="col-12"><div class="form-text text-warning">Ilk kayitta kolon otomatik acilacak; sorun devam ederse migration calistirin.</div></div>
                                            @endif
                                            <div class="col-12"><label class="form-label">Highlightlar</label><textarea name="highlights_text" rows="3" class="form-control">{{ implode(PHP_EOL, $package['highlights_json'] ?? []) }}</textarea></div>
                                            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($package['is_active'])><label class="form-check-label">Aktif</label></div>
                                                <button class="btn btn-primary btn-sm">Kaydi Guncelle</button>
                                            </div>
                                        </form>
                                        <div class="px-3 pb-3">
                                            <form method="POST" action="{{ route('superadmin.charter.packages.destroy', ['packageCode' => $package['code']]) }}" onsubmit="return confirm('Bu hazir paketi silmek istiyor musunuz?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">Sil</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Henuz hazir paket yok.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

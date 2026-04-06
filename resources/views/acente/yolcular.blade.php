<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('acente.partials.theme-styles')
    <title>Yolcu Listesi — {{ $talep->gtpnr }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<x-navbar-acente active="requests" />

<div class="container-fluid px-4 py-4" style="max-width: 960px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="mb-1"><i class="fas fa-users me-2 text-primary"></i>Yolcu Listesi</h4>
            <p class="text-muted mb-0 small">
                <a href="{{ route('acente.requests.show', $talep->gtpnr) }}" class="text-decoration-none">← {{ $talep->gtpnr }}</a>
                · {{ $talep->agency_name }} · {{ $talep->pax_total }} PAX
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('yolcular.sablon') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-download me-1"></i>CSV Şablon İndir
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Mevcut yolcular --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span>Yolcu Listesi ({{ $yolcular->count() }} / {{ $talep->pax_total }})</span>
            @if($yolcular->count() > 0)
                <span class="badge {{ $yolcular->count() >= $talep->pax_total ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ $yolcular->count() >= $talep->pax_total ? 'Tamamlandı' : $talep->pax_total - $yolcular->count() . ' eksik' }}
                </span>
            @endif
        </div>
        <div class="card-body p-0">
            @if($yolcular->isEmpty())
                <p class="text-muted p-3 mb-0 small">Henüz yolcu eklenmedi. Aşağıdan tek tek ekleyebilir veya CSV yükleyebilirsiniz.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px">#</th>
                                <th>Ad Soyad</th>
                                <th>Tür</th>
                                <th>Kimlik No</th>
                                <th>Doğum Tarihi</th>
                                <th>Uyruk</th>
                                <th>Cinsiyet</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($yolcular as $y)
                            <tr>
                                <td class="text-muted small">{{ $y->sira }}</td>
                                <td class="fw-semibold">{{ $y->ad }} {{ $y->soyad }}</td>
                                <td><span class="badge bg-{{ $y->tur === 'yetiskin' ? 'primary' : ($y->tur === 'cocuk' ? 'info' : 'warning text-dark') }}">{{ ucfirst($y->tur) }}</span></td>
                                <td class="small">{{ $y->kimlik_no ? $y->kimlik_no . ' (' . strtoupper($y->kimlik_tipi) . ')' : '—' }}</td>
                                <td class="small">{{ $y->dogum_tarihi ? \Carbon\Carbon::parse($y->dogum_tarihi)->format('d.m.Y') : '—' }}</td>
                                <td class="small">{{ $y->uyruk ?: '—' }}</td>
                                <td class="small">{{ $y->cinsiyet ? ucfirst($y->cinsiyet) : '—' }}</td>
                                <td>
                                    <form method="post" action="{{ route('yolcular.destroy', [$talep->gtpnr, $y->id]) }}" onsubmit="return confirm('Yolcuyu silmek istediğinizden emin misiniz?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger py-0 px-2">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        {{-- Tek yolcu ekleme --}}
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Yolcu Ekle (Tek Tek)</div>
                <div class="card-body">
                    <form method="post" action="{{ route('yolcular.store', $talep->gtpnr) }}" class="row g-2">
                        @csrf
                        <div class="col-6">
                            <label class="form-label mb-1">Ad <span class="text-danger">*</span></label>
                            <input type="text" name="ad" class="form-control form-control-sm" required placeholder="AHMET" value="{{ old('ad') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label mb-1">Soyad <span class="text-danger">*</span></label>
                            <input type="text" name="soyad" class="form-control form-control-sm" required placeholder="YILMAZ" value="{{ old('soyad') }}">
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1">Tür</label>
                            <select name="tur" class="form-select form-select-sm">
                                <option value="yetiskin" @selected(old('tur','yetiskin')==='yetiskin')>Yetişkin</option>
                                <option value="cocuk" @selected(old('tur')==='cocuk')>Çocuk</option>
                                <option value="infant" @selected(old('tur')==='infant')>Bebek</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1">Cinsiyet</label>
                            <select name="cinsiyet" class="form-select form-select-sm">
                                <option value="">Seçiniz</option>
                                <option value="erkek" @selected(old('cinsiyet')==='erkek')>Erkek</option>
                                <option value="kadin" @selected(old('cinsiyet')==='kadin')>Kadın</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1">Uyruk</label>
                            <input type="text" name="uyruk" maxlength="3" class="form-control form-control-sm" placeholder="TR" value="{{ old('uyruk', 'TR') }}">
                        </div>
                        <div class="col-5">
                            <label class="form-label mb-1">Kimlik / Pasaport No</label>
                            <input type="text" name="kimlik_no" class="form-control form-control-sm" placeholder="12345678901" value="{{ old('kimlik_no') }}">
                        </div>
                        <div class="col-3">
                            <label class="form-label mb-1">Kimlik Tipi</label>
                            <select name="kimlik_tipi" class="form-select form-select-sm">
                                <option value="tc" @selected(old('kimlik_tipi','tc')==='tc')>TC</option>
                                <option value="pasaport" @selected(old('kimlik_tipi')==='pasaport')>Pasaport</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label mb-1">Doğum Tarihi</label>
                            <input type="date" name="dogum_tarihi" class="form-control form-control-sm" value="{{ old('dogum_tarihi') }}">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-sm btn-primary w-100"><i class="fas fa-plus me-1"></i>Yolcu Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- CSV yükleme --}}
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Toplu Yükleme (CSV)</div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        CSV şablonu indirin, doldurun ve yükleyin. Noktalı virgül (;) ayraç olarak kullanılır.
                        Ad ve soyad büyük harfe dönüştürülür.
                    </p>
                    <a href="{{ route('yolcular.sablon') }}" class="btn btn-sm btn-outline-secondary w-100 mb-3">
                        <i class="fas fa-download me-1"></i>CSV Şablon İndir
                    </a>
                    <form method="post" action="{{ route('yolcular.csv', $talep->gtpnr) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label mb-1">CSV Dosyası</label>
                            <input type="file" name="csv_dosya" accept=".csv,.txt" class="form-control form-control-sm" required>
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-upload me-1"></i>Yükle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('acente.partials.theme-script')
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seans Yönetimi — {{ Str::limit($item->title, 40) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .section-title { font-weight:700; font-size:.85rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; border-bottom:2px solid #e9ecef; padding-bottom:.4rem; margin-bottom:1rem; }
        .badge-avail { background:#d1fae5; color:#065f46; }
        .badge-full  { background:#fee2e2; color:#991b1b; }
    </style>
</head>
<body>
<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-calendar-alt me-2" style="color:#e8a020;"></i>Seans Yönetimi</h5>
        <p>{{ $item->title }}</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('superadmin.b2c.catalog.edit', $item) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Ürüne Dön
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="row g-4">

        {{-- Sol: Seans Listesi --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4">
                <div class="section-title">Mevcut Seanslar</div>

                @if($sessions->isEmpty())
                <p class="text-muted">Henüz seans eklenmemiş.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Kapasite</th>
                                <th>Doluluk</th>
                                <th>Fiyat</th>
                                <th>Etiket</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($sessions as $s)
                        <tr class="{{ $s->session_date->isPast() ? 'opacity-50' : '' }}">
                            <td>{{ $s->session_date->translatedFormat('d M Y, D') }}</td>
                            <td>{{ $s->session_time ? substr($s->session_time, 0, 5) : '<span class="text-muted">—</span>' }}</td>
                            <td>
                                @if($s->capacity)
                                    @php $rem = $s->remainingCapacity(); @endphp
                                    <span class="badge {{ $s->isFull() ? 'badge-full' : 'badge-avail' }}">
                                        {{ $s->booked_count }}/{{ $s->capacity }}
                                    </span>
                                @else
                                    <span class="text-muted">Sınırsız</span>
                                @endif
                            </td>
                            <td>{{ $s->booked_count }}</td>
                            <td>
                                @if($s->price_override)
                                    {{ number_format($s->price_override, 0, ',', '.') }} {{ $item->currency }}
                                @else
                                    <span class="text-muted">Ürün fiyatı</span>
                                @endif
                            </td>
                            <td>{{ $s->label ?? '—' }}</td>
                            <td>
                                @if($s->booked_count == 0)
                                <form method="POST" action="{{ route('superadmin.b2c.sessions.destroy', [$item, $s]) }}"
                                      onsubmit="return confirm('Seans silinsin mi?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $sessions->links() }}
                @endif
            </div>
        </div>

        {{-- Sağ: Seans Ekle --}}
        <div class="col-lg-4">

            {{-- Tekli Seans --}}
            <div class="card border-0 shadow-sm p-4 mb-3">
                <div class="section-title">Tekli Seans Ekle</div>
                <form method="POST" action="{{ route('superadmin.b2c.sessions.store', $item) }}">
                    @csrf
                    @if($errors->any())
                    <div class="alert alert-danger py-2 mb-2" style="font-size:.82rem;">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                    @endif
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-600">Tarih *</label>
                        <input type="date" name="session_date" class="form-control form-control-sm"
                               min="{{ today()->toDateString() }}" required
                               value="{{ old('session_date') }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Saat</label>
                        <input type="time" name="session_time" class="form-control form-control-sm"
                               value="{{ old('session_time') }}">
                        <div class="form-text">Boş bırakılırsa tüm gün.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Kapasite</label>
                        <input type="number" name="capacity" class="form-control form-control-sm" min="1"
                               value="{{ old('capacity', $item->max_pax ?? '') }}"
                               placeholder="Sınırsız">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Fiyat (boş = ürün fiyatı)</label>
                        <input type="number" name="price_override" class="form-control form-control-sm"
                               step="0.01" min="0" value="{{ old('price_override') }}"
                               placeholder="{{ $item->base_price ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Etiket</label>
                        <input type="text" name="label" class="form-control form-control-sm"
                               value="{{ old('label') }}" placeholder="Türkçe, VIP, vb.">
                    </div>
                    <button class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-plus me-1"></i>Seans Ekle
                    </button>
                </form>
            </div>

            {{-- Tekrarlayan Seans --}}
            <div class="card border-0 shadow-sm p-4">
                <div class="section-title">Tekrarlayan Seans Oluştur</div>
                <form method="POST" action="{{ route('superadmin.b2c.sessions.bulk', $item) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-600">Başlangıç</label>
                        <input type="date" name="start_date" class="form-control form-control-sm"
                               min="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-600">Bitiş</label>
                        <input type="date" name="end_date" class="form-control form-control-sm"
                               min="{{ today()->toDateString() }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm fw-600">Günler *</label>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach([0=>'Paz',1=>'Pzt',2=>'Sal',3=>'Çar',4=>'Per',5=>'Cum',6=>'Cmt'] as $d => $l)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="weekdays[]"
                                       value="{{ $d }}" id="wd{{ $d }}" checked>
                                <label class="form-check-label" for="wd{{ $d }}">{{ $l }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Saat</label>
                        <input type="time" name="session_time" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Kapasite</label>
                        <input type="number" name="capacity" class="form-control form-control-sm" min="1"
                               value="{{ $item->max_pax ?? '' }}" placeholder="Sınırsız">
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Fiyat (boş = ürün fiyatı)</label>
                        <input type="number" name="price_override" class="form-control form-control-sm"
                               step="0.01" placeholder="{{ $item->base_price ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-sm">Etiket</label>
                        <input type="text" name="label" class="form-control form-control-sm"
                               placeholder="Akşam Seansı">
                    </div>
                    <button class="btn btn-success btn-sm w-100">
                        <i class="fas fa-calendar-plus me-1"></i>Seansları Oluştur
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

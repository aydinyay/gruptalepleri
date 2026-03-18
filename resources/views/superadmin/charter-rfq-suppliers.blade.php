<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RFQ Tedarikciler</title>
    @include('admin.partials.theme-styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .card-box { border-radius: 12px; border: 1px solid rgba(0, 0, 0, 0.08); }
        .service-chip { font-size: 0.72rem; border: 1px solid rgba(0,0,0,.15); border-radius: 999px; padding: 2px 8px; }
    </style>
</head>
<body class="theme-scope">
<x-navbar-superadmin active="charter-rfq-suppliers" />

<div class="container-fluid py-4 px-3 px-md-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-paper-plane me-2 text-danger"></i>RFQ Tedarikciler</h4>
            <div class="text-muted small">RFQ dagitiminda kullanilacak alicilari buradan yonetin.</div>
        </div>
        <a href="{{ route('superadmin.charter.index') }}" class="btn btn-outline-secondary btn-sm">Charter Listeye Don</a>
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

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-4">
            <div class="card card-box shadow-sm h-100">
                <div class="card-header fw-bold">RFQ Alicisi Ekle</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.charter.rfq-suppliers.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Tedarikci Adi</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">E-posta</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Telefon (opsiyonel)</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold d-block">Servis Tipleri</label>
                            @foreach($serviceTypeOptions as $key => $label)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="service_types[]" value="{{ $key }}" id="new-{{ $key }}">
                                    <label class="form-check-label" for="new-{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Not (opsiyonel)</label>
                            <input type="text" name="notes" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new-active" checked>
                                <label class="form-check-label" for="new-active">Aktif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100">RFQ Alicisi Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card card-box shadow-sm mb-3">
                <div class="card-header fw-bold">RFQ Dagitim Limiti</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.charter.rfq-suppliers.max') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold">Maksimum Alici (mail)</label>
                            <input type="number" min="1" max="100" name="max_suppliers" class="form-control" value="{{ $maxSuppliers }}" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <button class="btn btn-outline-primary">Limiti Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-box shadow-sm">
                <div class="card-header fw-bold">Mevcut Tedarikciler</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Tedarikci</th>
                                <th>E-posta</th>
                                <th>Servis</th>
                                <th>Durum</th>
                                <th class="text-end">Islem</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($suppliers as $supplier)
                                <tr>
                                    <td>{{ $supplier->id }}</td>
                                    <td>{{ $supplier->name }}</td>
                                    <td>{{ $supplier->email }}</td>
                                    <td>
                                        @foreach(($supplier->service_types ?? []) as $type)
                                            <span class="service-chip me-1">{{ strtoupper($type) }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($supplier->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Pasif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-{{ $supplier->id }}">Duzenle</button>
                                        <form method="POST" action="{{ route('superadmin.charter.rfq-suppliers.destroy', $supplier) }}" class="d-inline" onsubmit="return confirm('Kayit silinsin mi?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Sil</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="edit-{{ $supplier->id }}">
                                    <td colspan="6">
                                        <form method="POST" action="{{ route('superadmin.charter.rfq-suppliers.update', $supplier) }}" class="row g-2 p-2">
                                            @csrf
                                            @method('PATCH')
                                            <div class="col-12 col-md-3"><input type="text" name="name" class="form-control form-control-sm" value="{{ $supplier->name }}" required></div>
                                            <div class="col-12 col-md-3"><input type="email" name="email" class="form-control form-control-sm" value="{{ $supplier->email }}" required></div>
                                            <div class="col-12 col-md-2"><input type="text" name="phone" class="form-control form-control-sm" value="{{ $supplier->phone }}"></div>
                                            <div class="col-12 col-md-4">
                                                @foreach($serviceTypeOptions as $key => $label)
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="checkbox" name="service_types[]" value="{{ $key }}" id="s-{{ $supplier->id }}-{{ $key }}" @checked(in_array($key, $supplier->service_types ?? [], true))>
                                                        <label class="form-check-label small" for="s-{{ $supplier->id }}-{{ $key }}">{{ $label }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="col-12 col-md-8"><input type="text" name="notes" class="form-control form-control-sm" value="{{ $supplier->notes }}" placeholder="Not"></div>
                                            <div class="col-12 col-md-2 d-flex align-items-center">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active-{{ $supplier->id }}" @checked($supplier->is_active)>
                                                    <label class="form-check-label small" for="active-{{ $supplier->id }}">Aktif</label>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-2 text-end"><button class="btn btn-sm btn-primary">Kaydet</button></div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted p-3">Kayitli RFQ alicisi yok.</td></tr>
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

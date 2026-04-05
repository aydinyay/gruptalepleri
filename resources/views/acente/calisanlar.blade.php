<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <title>Çalışanlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<x-navbar-acente active="calisanlar" />

<div class="container-fluid px-4 py-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-users me-2 text-primary"></i>Çalışanlar</h4>
            <p class="text-muted mb-0 small">Acente hesabınıza çalışan davet edin ve yetkilerini yönetin.</p>
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

    {{-- Mevcut çalışanlar --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">Mevcut Çalışanlar ({{ $calisanlar->count() }})</div>
        <div class="card-body p-0">
            @if($calisanlar->isEmpty())
                <p class="text-muted p-3 mb-0">Henüz çalışan yok. Aşağıdan davet gönderin.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>İsim / Email</th>
                                <th>Rol</th>
                                <th>Durum</th>
                                <th>Son Giriş</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calisanlar as $c)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $c->davet_token ? '(Davet Bekleniyor)' : $c->name }}</div>
                                    <div class="text-muted small">{{ $c->email }}</div>
                                </td>
                                <td>
                                    <form method="post" action="{{ route('acente.calisanlar.yetki', $c->id) }}" class="d-flex gap-1 align-items-center">
                                        @csrf @method('PATCH')
                                        <select name="acente_rolu" class="form-select form-select-sm" style="min-width: 130px;">
                                            @foreach(['tam' => 'Tam Erişim', 'operasyon' => 'Operasyon', 'muhasebe' => 'Muhasebe'] as $val => $lbl)
                                                <option value="{{ $val }}" @selected($c->acente_rolu === $val)>{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary">Güncelle</button>
                                    </form>
                                </td>
                                <td>
                                    @if($c->davet_token)
                                        <span class="badge bg-warning text-dark">Davet Açık</span>
                                    @else
                                        <span class="badge bg-success">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $c->last_login_at ? \Carbon\Carbon::parse($c->last_login_at)->diffForHumans() : '—' }}</td>
                                <td>
                                    <form method="post" action="{{ route('acente.calisanlar.sil', $c->id) }}" onsubmit="return confirm('Bu çalışanı silmek istediğinizden emin misiniz?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Sil</button>
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

    {{-- Yeni davet formu --}}
    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Yeni Çalışan Davet Et</div>
        <div class="card-body">
            <form method="post" action="{{ route('acente.calisanlar.davet') }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label mb-1">Email Adresi</label>
                    <input type="email" name="email" class="form-control" required placeholder="calisan@firma.com" value="{{ old('email') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">Yetki Paketi</label>
                    <select name="acente_rolu" class="form-select" required>
                        <option value="tam">Tam Erişim — Talep + Teklif + Ödeme + Finans</option>
                        <option value="operasyon">Operasyon — Talep + Teklif</option>
                        <option value="muhasebe">Muhasebe — Finans + Ödeme</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Davet Gönder</button>
                </div>
            </form>

            <div class="mt-3 p-3 bg-light rounded small">
                <strong>Yetki Paketleri:</strong>
                <ul class="mb-0 mt-1">
                    <li><strong>Tam Erişim:</strong> Talep oluşturma, teklif kabul, ödeme yapma, finans görüntüleme</li>
                    <li><strong>Operasyon:</strong> Talep oluşturma ve teklif kabul etme</li>
                    <li><strong>Muhasebe:</strong> Finans ve ödeme sayfalarına erişim</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.theme-script')
</body>
</html>

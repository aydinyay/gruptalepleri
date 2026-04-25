{{-- B2C Hızlı Leadler --}}
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2C Hızlı Leadler — Süperadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
        .page-header { background:#1a1a2e; padding:1.2rem 0; margin-bottom:1.5rem; }
        .page-header h5 { color:#fff; font-weight:700; margin:0; }
        .page-header p  { color:rgba(255,255,255,.5); font-size:.82rem; margin:0; }
        .badge-b2c { background:#fef3c7; color:#92400e; font-weight:700; font-size:.72rem; border-radius:6px; padding:2px 8px; }
        .lead-card { background:#fff; border-radius:10px; padding:1rem 1.25rem; border-left:4px solid #e74c3c; }
        .tbl th { font-size:.78rem; color:#6c757d; font-weight:600; text-transform:uppercase; border-bottom:2px solid #e5e7eb; }
        .tbl td { vertical-align:middle; font-size:.88rem; }
        .phone-link { color:#e74c3c; font-weight:700; text-decoration:none; }
        .phone-link:hover { text-decoration:underline; }
    </style>
</head>
<body>

<x-navbar-superadmin active="b2c" />

<div class="page-header">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5><span class="badge-b2c me-2">B2C</span> Hızlı Teklif Talepleri</h5>
                <p>GrupRezervasyonlari.com ana sayfası "Ücretsiz Teklif Al" formu</p>
            </div>
            <span class="badge bg-danger fs-6">{{ $leads->total() }} toplam</span>
        </div>
    </div>
</div>

<div class="container-fluid">

    {{-- Filtre --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="İsim, telefon veya e-posta ara…" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <select name="hizmet" class="form-select form-select-sm">
                        <option value="">Tüm Hizmetler</option>
                        <option value="transfer" @selected(request('hizmet')==='transfer')>Havalimanı Transferi</option>
                        <option value="yat" @selected(request('hizmet')==='yat')>Yat Kiralama</option>
                        <option value="dinner_cruise" @selected(request('hizmet')==='dinner_cruise')>Dinner Cruise</option>
                        <option value="tur" @selected(request('hizmet')==='tur')>Günübirlik Tur</option>
                        <option value="charter" @selected(request('hizmet')==='charter')>Özel Jet / Charter</option>
                        <option value="sigorta" @selected(request('hizmet')==='sigorta')>Seyahat Sigortası</option>
                        <option value="diger" @selected(request('hizmet')==='diger')>Diğer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Filtrele</button>
                </div>
                @if(request()->hasAny(['q','hizmet']))
                <div class="col-md-2">
                    <a href="{{ route('superadmin.b2c.quick-leads.index') }}" class="btn btn-sm btn-outline-secondary w-100">Temizle</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    @if($leads->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3 d-block opacity-25"></i>
            Henüz lead yok.
        </div>
    @else
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table tbl mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Ad Soyad</th>
                            <th>Telefon</th>
                            <th>E-posta</th>
                            <th>Hizmet</th>
                            <th>Not</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leads as $lead)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $lead->name }}</td>
                            <td><a href="tel:{{ $lead->phone }}" class="phone-link">{{ $lead->phone }}</a></td>
                            <td>
                                @if($lead->email)
                                    <a href="mailto:{{ $lead->email }}" class="text-decoration-none text-secondary">{{ $lead->email }}</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($lead->service_type)
                                    <span class="badge bg-light text-dark border">{{ $lead->service_type }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted" style="max-width:220px;">
                                {{ $lead->notes ? \Illuminate\Support\Str::limit($lead->notes, 60) : '—' }}
                            </td>
                            <td class="text-muted text-nowrap">
                                {{ \Carbon\Carbon::parse($lead->created_at)->format('d.m.Y H:i') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $leads->links() }}
    </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

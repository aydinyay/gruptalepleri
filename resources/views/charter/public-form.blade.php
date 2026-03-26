<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} - GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f5f7fb; font-family:'Segoe UI',sans-serif; }
        .hero { background:#1a1a2e; color:#fff; }
        .nav-brand { color:#e94560; font-weight:700; text-decoration:none; }
        .card-box { border-radius:14px; border:1px solid #e5e7eb; }
        .type-link { text-decoration:none; font-weight:600; }
    </style>
</head>
<body>

<div class="hero py-3">
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
        <a href="{{ url('/') }}" class="nav-brand">
            <i class="fas fa-plane-departure me-1"></i>GrupTalepleri
        </a>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <a href="{{ route('charter.public.jet') }}" class="type-link text-white-50">Ozel Jet</a>
            <a href="{{ route('charter.public.helicopter') }}" class="type-link text-white-50">Helikopter</a>
            <a href="{{ route('charter.public.airliner') }}" class="type-link text-white-50">Charter Ucak</a>
            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">Giris Yap</a>
        </div>
    </div>
</div>

<div class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <div class="card card-box shadow-sm">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-1">{{ $pageTitle }}</h4>
                    <p class="text-muted mb-4">Hizli talep birak, operasyon ekibi sana donsun.</p>

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

                    <form method="POST" action="{{ route('charter.public.store') }}">
                        @csrf
                        <input type="hidden" name="transport_type" value="{{ $transportType }}">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Ad Soyad</label>
                                <input name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Telefon</label>
                                <input name="phone" class="form-control" value="{{ old('phone') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">E-posta</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">From (IATA)</label>
                                <input name="from_iata" class="form-control text-uppercase" value="{{ old('from_iata') }}" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label">To (IATA)</label>
                                <input name="to_iata" class="form-control text-uppercase" value="{{ old('to_iata') }}" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" name="departure_date" class="form-control" value="{{ old('departure_date') }}" required>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">PAX</label>
                                <input type="number" min="1" max="400" name="pax" class="form-control" value="{{ old('pax') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Not (opsiyonel)</label>
                                <textarea name="notes" rows="4" class="form-control">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <button class="btn btn-danger px-4">
                                <i class="fas fa-paper-plane me-1"></i>Talep Gonder
                            </button>
                            <a href="{{ url('/') }}" class="btn btn-outline-secondary">Ana Sayfa</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim — GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .navbar { background: #1a1a2e !important; }
        .navbar-brand { color: #e94560 !important; font-weight: 700; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); }
        .card-header { background: #1a1a2e; color: white; border-radius: 12px 12px 0 0 !important; font-weight: 600; }
        .section-icon { color: #e94560; }
        .btn-save { background: #e94560; border: none; color: white; font-weight: 600; padding: 10px 28px; border-radius: 8px; }
        .btn-save:hover { background: #c73652; color: white; }
        .form-label { font-weight: 500; font-size: 0.875rem; color: #444; }
        .form-control:focus { border-color: #e94560; box-shadow: 0 0 0 0.2rem rgba(233,69,96,0.15); }
        .alert-success-custom { background: #d1edda; border: 1px solid #a3d9b1; color: #1a5c2a; border-radius: 8px; padding: 12px 16px; }
        .avatar-circle {
            width: 72px; height: 72px;
            background: #e94560;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: 700; color: white;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-0">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('acente.dashboard') }}">✈️ GrupTalepleri</a>
        <a href="{{ route('acente.dashboard') }}" class="btn btn-outline-light btn-sm">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>
</nav>

<div class="container py-4" style="max-width:780px;">

    {{-- Başlık --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="avatar-circle">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h4 class="fw-bold mb-0">{{ $user->name }}</h4>
            <div class="text-muted small">{{ $user->email }}</div>
            <span class="badge bg-secondary mt-1">{{ ucfirst($user->role) }}</span>
        </div>
    </div>

    {{-- Başarı mesajları --}}
    @if(session('success'))
        <div class="alert-success-custom mb-3">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('success_sifre'))
        <div class="alert-success-custom mb-3">
            <i class="fas fa-check-circle me-2"></i>{{ session('success_sifre') }}
        </div>
    @endif

    {{-- KİŞİSEL BİLGİLER --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user section-icon me-2"></i> Kişisel Bilgiler
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('acente.profil.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Ad Soyad *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-posta *</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $acente?->phone) }}" placeholder="05xx xxx xx xx">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Yetkili Kişi</label>
                        <input type="text" name="contact_name" class="form-control"
                               value="{{ old('contact_name', $acente?->contact_name) }}" placeholder="Ad Soyad">
                    </div>
                </div>

                <hr class="my-4">
                <div class="fw-semibold mb-3 text-muted small text-uppercase">
                    <i class="fas fa-building section-icon me-1"></i> Acente / Şirket Bilgileri
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Turizm Ünvanı</label>
                        <input type="text" name="tourism_title" class="form-control"
                               value="{{ old('tourism_title', $acente?->tourism_title) }}" placeholder="ABC Turizm Ltd. Şti.">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Şirket Ünvanı</label>
                        <input type="text" name="company_title" class="form-control"
                               value="{{ old('company_title', $acente?->company_title) }}" placeholder="ABC Tic. A.Ş.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vergi No</label>
                        <input type="text" name="tax_number" class="form-control"
                               value="{{ old('tax_number', $acente?->tax_number) }}" placeholder="1234567890">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" name="tax_office" class="form-control"
                               value="{{ old('tax_office', $acente?->tax_office) }}" placeholder="Kadıköy">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">TURSAB No</label>
                        <input type="text" name="tursab_no" class="form-control"
                               value="{{ old('tursab_no', $acente?->tursab_no) }}" placeholder="12345">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adres</label>
                        <textarea name="address" class="form-control" rows="2"
                                  placeholder="Şirket adresi">{{ old('address', $acente?->address) }}</textarea>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save me-2"></i>Bilgileri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ŞİFRE DEĞİŞTİR --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-lock section-icon me-2"></i> Şifre Değiştir
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('acente.profil.sifre') }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Mevcut Şifre *</label>
                        <input type="password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Yeni Şifre *</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Yeni Şifre (Tekrar) *</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-key me-2"></i>Şifremi Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Hesap Bilgisi --}}
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="row text-center g-3">
                <div class="col-4">
                    <div class="text-muted small">Kayıt Tarihi</div>
                    <div class="fw-bold">{{ $user->created_at->format('d.m.Y') }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Toplam Talep</div>
                    <div class="fw-bold text-primary">{{ $user->requests()->count() }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Hesap Durumu</div>
                    <div class="fw-bold text-success">Aktif</div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

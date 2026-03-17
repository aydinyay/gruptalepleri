<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="googlebot" content="noindex, nofollow, noarchive">
    <meta name="description" content="GrupTalepleri uyelik kayit ekrani.">
    <title>Üye Ol — GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Barlow', sans-serif;
            background: #1a1a2e;
            min-height: 100vh;
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(233,69,96,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        .register-wrapper { width: 100%; max-width: 580px; margin: 0 auto; position: relative; z-index: 2; }
        .logo-area { text-align: center; margin-bottom: 1.5rem; }
        .logo { font-family: 'Barlow Condensed', sans-serif; font-size: 2rem; font-weight: 800; color: #e94560; letter-spacing: 1px; text-decoration: none; display: inline-block; }
        .logo span { color: #ffffff; }
        .logo-sub { font-size: 0.78rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px; margin-top: 4px; }
        .register-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 2rem; }
        .register-card h2 { font-family: 'Barlow Condensed', sans-serif; font-size: 1.5rem; font-weight: 800; color: #ffffff; margin-bottom: 0.3rem; }
        .register-card p { font-size: 0.875rem; color: rgba(255,255,255,0.45); margin-bottom: 1.5rem; }
        .section-divider { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 2px; display: flex; align-items: center; gap: 10px; margin: 1.2rem 0 1rem; }
        .section-divider::before, .section-divider::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.1); }
        .form-label { font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; display: block; }
        .optional { color: rgba(255,255,255,0.25); font-weight: 400; text-transform: none; letter-spacing: 0; }
        .form-control { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #ffffff; padding: 10px 14px; font-size: 0.9rem; transition: all 0.2s; width: 100%; }
        .form-control:focus { background: rgba(255,255,255,0.1); border-color: #e94560; box-shadow: 0 0 0 3px rgba(233,69,96,0.15); color: #ffffff; outline: none; }
        .form-control::placeholder { color: rgba(255,255,255,0.2); }
        .input-icon-wrap { position: relative; }
        .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.25); font-size: 0.85rem; pointer-events: none; }
        .input-icon-wrap .form-control { padding-left: 36px; }
        .is-invalid { border-color: #e94560 !important; }
        .invalid-feedback { color: #ff8a9a; font-size: 0.78rem; display: block; margin-top: 4px; }
        .btn-register { background: #e94560; color: #ffffff; border: none; border-radius: 8px; padding: 13px; font-size: 0.95rem; font-weight: 600; width: 100%; transition: all 0.2s; cursor: pointer; }
        .btn-register:hover { background: #c73652; transform: translateY(-1px); }
        .login-link { text-align: center; margin-top: 1.2rem; font-size: 0.875rem; color: rgba(255,255,255,0.4); }
        .login-link a { color: #e94560; text-decoration: none; font-weight: 600; }
        .back-link { text-align: center; margin-top: 1rem; }
        .back-link a { font-size: 0.8rem; color: rgba(255,255,255,0.3); text-decoration: none; }
        .back-link a:hover { color: rgba(255,255,255,0.6); }
        .alert-danger-custom { background: rgba(233,69,96,0.15); border: 1px solid rgba(233,69,96,0.3); color: #ff8a9a; border-radius: 8px; padding: 10px 14px; font-size: 0.875rem; margin-bottom: 1.2rem; }
        .row { display: flex; flex-wrap: wrap; margin: 0 -8px; }
        .col-12 { width: 100%; padding: 0 8px; }
        .col-md-6 { width: 50%; padding: 0 8px; }
        @media(max-width:576px){ .col-md-6 { width: 100%; } }
        .g-3 > * { margin-bottom: 16px; }
        .mt-4 { margin-top: 1.5rem; }
    </style>
</head>
<body>
<div class="register-wrapper">

    <div class="logo-area">
        <a href="/" class="logo">✈ Grup<span>Talepleri</span></a>
        <div class="logo-sub">B2B Grup Uçuş Platformu</div>
    </div>

    <div class="register-card">
        <h2>Acente Kaydı</h2>
        <p>Hesabınızı oluşturun, dakikalar içinde aktif olun</p>

        @if ($errors->any())
        <div class="alert-danger-custom">
            <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="section-divider">Kişisel Bilgiler</div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ad Soyad *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Adınız Soyadınız" required autofocus>
                    </div>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefon *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone') }}" placeholder="05xx xxx xx xx" required>
                    </div>
                    @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">E-posta *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="ornek@firma.com" required>
                    </div>
                    @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Şifre *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               placeholder="En az 8 karakter" required autocomplete="new-password">
                    </div>
                    @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Şifre Tekrar *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Şifreyi tekrar girin" required>
                    </div>
                </div>
            </div>

            <div class="section-divider">Acente / Şirket Bilgileri</div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Şirket Ünvanı *</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-building input-icon"></i>
                        <input type="text" name="company_title" class="form-control @error('company_title') is-invalid @enderror"
                               value="{{ old('company_title') }}" placeholder="ABC Turizm Ltd. Şti." required>
                    </div>
                    @error('company_title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Turizm Ünvanı <span class="optional">(opsiyonel)</span></label>
                    <input type="text" name="tourism_title" class="form-control"
                           value="{{ old('tourism_title') }}" placeholder="ABC Seyahat Acentesi">
                </div>
                <div class="col-md-6">
                    <label class="form-label">TURSAB No <span class="optional">(opsiyonel)</span></label>
                    <input type="text" name="tursab_no" class="form-control"
                           value="{{ old('tursab_no') }}" placeholder="12345">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vergi No <span class="optional">(opsiyonel)</span></label>
                    <input type="text" name="tax_number" class="form-control"
                           value="{{ old('tax_number') }}" placeholder="1234567890">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vergi Dairesi <span class="optional">(opsiyonel)</span></label>
                    <input type="text" name="tax_office" class="form-control"
                           value="{{ old('tax_office') }}" placeholder="Kadıköy">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus me-2"></i>Hesabımı Oluştur
                </button>
            </div>
        </form>

        <div class="login-link">
            Zaten hesabınız var mı? <a href="{{ route('login') }}">Giriş Yapın</a>
        </div>
    </div>

    <div class="back-link">
        <a href="/"><i class="fas fa-arrow-left me-1"></i>Ana Sayfaya Dön</a>
    </div>

</div>
</body>
</html>

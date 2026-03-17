<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="googlebot" content="noindex, nofollow, noarchive">
    <meta name="description" content="GrupTalepleri kullanıcı giriş ekranı.">
    <title>Giriş Yap — GrupTalepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Barlow', sans-serif;
            background: #1a1a2e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: -200px; right: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(233,69,96,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            bottom: -200px; left: -200px;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(15,52,96,0.5) 0%, transparent 70%);
            pointer-events: none;
        }
        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 1.5rem;
            position: relative;
            z-index: 2;
        }
        .logo-area {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: #e94560;
            letter-spacing: 1px;
        }
        .logo span { color: #ffffff; }
        .logo-sub {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 4px;
        }
        .login-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 2.5rem;
            backdrop-filter: blur(10px);
        }
        .login-card h2 {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.3rem;
        }
        .login-card p {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.45);
            margin-bottom: 1.8rem;
        }
        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .form-control {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #ffffff;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            background: rgba(255,255,255,0.1);
            border-color: #e94560;
            box-shadow: 0 0 0 3px rgba(233,69,96,0.15);
            color: #ffffff;
            outline: none;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.25); }
        .input-icon-wrap { position: relative; }
        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            font-size: 0.9rem;
            pointer-events: none;
        }
        .input-icon-wrap .form-control { padding-left: 40px; }
        .form-check-input {
            background-color: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.25);
        }
        .form-check-input:checked {
            background-color: #e94560;
            border-color: #e94560;
        }
        .form-check-label {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.55);
        }
        .btn-login {
            background: #e94560;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 13px;
            font-size: 0.95rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-login:hover {
            background: #c73652;
            transform: translateY(-1px);
        }
        .forgot-link {
            font-size: 0.825rem;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: #e94560; }
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.4);
        }
        .register-link a {
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover { text-decoration: underline; }
        .alert-danger-custom {
            background: rgba(233,69,96,0.15);
            border: 1px solid rgba(233,69,96,0.3);
            color: #ff8a9a;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.875rem;
            margin-bottom: 1.2rem;
        }
        .alert-success-custom {
            background: rgba(40,167,69,0.15);
            border: 1px solid rgba(40,167,69,0.3);
            color: #6fcf97;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.875rem;
            margin-bottom: 1.2rem;
        }
        .back-link {
            text-align: center;
            margin-top: 1.2rem;
        }
        .back-link a {
            font-size: 0.825rem;
            color: rgba(255,255,255,0.3);
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link a:hover { color: rgba(255,255,255,0.6); }
    </style>
</head>
<body>

<div class="login-wrapper">

    {{-- Logo --}}
    <div class="logo-area">
        <a href="/" style="text-decoration:none;">
            <div class="logo">✈ Grup<span>Talepleri</span></div>
        </a>
        <div class="logo-sub">B2B Grup Uçuş Platformu</div>
    </div>

    <div class="login-card">
        <h2>Hoş Geldiniz</h2>
        <p>Hesabınıza giriş yapın</p>

        {{-- Session status --}}
        @if (session('status'))
            <div class="alert-success-custom">
                <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
            </div>
        @endif

        {{-- Hata mesajları --}}
        @if ($errors->any())
            <div class="alert-danger-custom">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            {{-- E-posta --}}
            <div class="mb-3">
                <label class="form-label">E-posta</label>
                <div class="input-icon-wrap">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}"
                           placeholder="ornek@firma.com"
                           required autofocus autocomplete="username">
                </div>
            </div>

            {{-- Şifre --}}
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Şifre</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">Şifremi unuttum</a>
                    @endif
                </div>
                <div class="input-icon-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" class="form-control"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                </div>
            </div>

            {{-- Beni hatırla --}}
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                    <label class="form-check-label" for="remember_me">Beni hatırla</label>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
            </button>
        </form>

        @if (Route::has('register'))
        <div class="register-link">
            Hesabınız yok mu? <a href="{{ route('register') }}">Üye Olun</a>
        </div>
        @endif
    </div>

    <div class="back-link">
        <a href="/"><i class="fas fa-arrow-left me-1"></i>Ana Sayfaya Dön</a>
    </div>

</div>

</body>
</html>

@extends('b2c.layouts.app')
@section('title', 'Giriş Yap — Grup Rezervasyonları')

@push('head_styles')
<style>
.auth-wrap {
    min-height: calc(100vh - 64px);
    display: grid;
    grid-template-columns: 1fr 1fr;
}
@@media (max-width: 768px) {
    .auth-wrap { grid-template-columns: 1fr; }
    .auth-brand-panel { display: none; }
}
.auth-brand-panel {
    background: linear-gradient(150deg, #0f2444 0%, #1a3c6b 60%, #1e5ba8 100%);
    display: flex; flex-direction: column; justify-content: center;
    padding: 3rem 3.5rem;
    position: relative; overflow: hidden;
}
.auth-brand-panel::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.auth-brand-logo {
    font-size: 1.4rem; font-weight: 900; color: #fff;
    text-decoration: none; margin-bottom: 2.5rem; display: inline-block;
    position: relative;
}
.auth-brand-logo span { color: var(--gr-accent, #f4a418); }
.auth-brand-headline {
    font-size: clamp(1.5rem, 2.5vw, 2rem);
    font-weight: 800; color: #fff; margin-bottom: 1rem; line-height: 1.25;
    position: relative;
}
.auth-brand-sub {
    color: rgba(255,255,255,.7); font-size: .95rem; margin-bottom: 2rem;
    line-height: 1.6; position: relative;
}
.auth-benefit {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.1);
    color: rgba(255,255,255,.85); font-size: .9rem; position: relative;
}
.auth-benefit:last-child { border-bottom: none; }
.auth-benefit i { color: var(--gr-accent, #f4a418); font-size: 1rem; flex-shrink: 0; }

.auth-form-panel {
    background: #fff;
    display: flex; flex-direction: column; justify-content: center;
    padding: 3rem;
}
@@media (max-width: 480px) {
    .auth-form-panel { padding: 2rem 1.5rem; }
}
.auth-form-inner { max-width: 420px; margin: 0 auto; width: 100%; }
.auth-form-title { font-size: 1.6rem; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.auth-form-sub { font-size: .9rem; color: #718096; margin-bottom: 1.75rem; }

.auth-input {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .95rem; color: #1a202c; outline: none;
    transition: border-color .15s, box-shadow .15s;
    background: #fafbfc;
}
.auth-input:focus {
    border-color: #1a3c6b;
    box-shadow: 0 0 0 3px rgba(26,60,107,.1);
    background: #fff;
}
.auth-input.is-invalid { border-color: #e53e3e; }
.auth-label {
    display: block; font-size: .85rem; font-weight: 600;
    color: #2d3748; margin-bottom: 6px;
}
.auth-submit {
    width: 100%; padding: 13px;
    background: #1a3c6b; color: #fff;
    border: none; border-radius: 10px;
    font-size: 1rem; font-weight: 700;
    cursor: pointer; transition: background .15s, transform .1s;
    margin-top: 4px;
}
.auth-submit:hover { background: #152f56; }
.auth-submit:active { transform: scale(.99); }
.auth-divider {
    text-align: center; margin: 20px 0;
    position: relative; color: #a0aec0; font-size: .85rem;
}
.auth-divider::before, .auth-divider::after {
    content: ''; position: absolute; top: 50%; width: calc(50% - 28px);
    height: 1px; background: #e2e8f0;
}
.auth-divider::before { left: 0; }
.auth-divider::after { right: 0; }
</style>
@endpush

@section('content')
<div class="auth-wrap">

    {{-- Sol panel: marka --}}
    <div class="auth-brand-panel">
        <a href="{{ lroute('b2c.home') }}" class="auth-brand-logo">
            Grup<span>Rezervasyonları</span>
        </a>
        <div class="auth-brand-headline">
            Seyahat planlarınız<br>bir tık uzağınızda.
        </div>
        <p class="auth-brand-sub">
            Transfer'den charter'a, dinner cruise'dan tur paketlerine — Türkiye'nin en kapsamlı grup seyahat platformuna hoş geldiniz.
        </p>
        <div class="auth-benefit"><i class="bi bi-shield-check-fill"></i><div><div style="font-weight:600;">Güvenli Ödeme</div><div style="font-size:.82rem;opacity:.7;">3D Secure altyapısı ile korumalı işlemler</div></div></div>
        <div class="auth-benefit"><i class="bi bi-clock-fill"></i><div><div style="font-weight:600;">7/24 Destek</div><div style="font-size:.82rem;opacity:.7;">Operasyon ekibimiz her zaman yanınızda</div></div></div>
        <div class="auth-benefit"><i class="bi bi-star-fill"></i><div><div style="font-weight:600;">4.8/5 Müşteri Puanı</div><div style="font-size:.82rem;opacity:.7;">14.000+ memnun müşteri değerlendirmesi</div></div></div>
        <div class="auth-benefit"><i class="bi bi-arrow-counterclockwise"></i><div><div style="font-weight:600;">Ücretsiz İptal</div><div style="font-size:.82rem;opacity:.7;">Koşullara göre esnek iptal seçenekleri</div></div></div>
    </div>

    {{-- Sağ panel: form --}}
    <div class="auth-form-panel">
        <div class="auth-form-inner">
            <div class="auth-form-title">Tekrar Hoş Geldiniz</div>
            <div class="auth-form-sub">Hesabınıza giriş yapın</div>

            @if(session('status'))
            <div style="background:#ebfbee;border:1px solid #9ae6b4;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:.9rem;color:#276749;">
                <i class="bi bi-info-circle me-1"></i>{{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ lroute('b2c.auth.login.post') }}">
                @csrf

                <div style="margin-bottom:16px;">
                    <label class="auth-label" for="email">E-posta adresi</label>
                    <input type="email" id="email" name="email"
                           class="auth-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           value="{{ old('email') }}" required autofocus autocomplete="email"
                           placeholder="ornek@email.com">
                    @error('email')
                    <div style="color:#e53e3e;font-size:.82rem;margin-top:5px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom:20px;">
                    <label class="auth-label" for="password" style="display:flex;justify-content:space-between;align-items:center;">
                        Şifre
                        <a href="{{ lroute('b2c.auth.forgot') }}" style="font-weight:400;font-size:.82rem;color:#1a3c6b;">Şifremi unuttum</a>
                    </label>
                    <input type="password" id="password" name="password"
                           class="auth-input" required autocomplete="current-password"
                           placeholder="••••••••">
                </div>

                <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                    <input type="checkbox" name="remember" id="remember" style="width:16px;height:16px;accent-color:#1a3c6b;">
                    <label for="remember" style="font-size:.88rem;color:#4a5568;cursor:pointer;">Beni hatırla</label>
                </div>

                <button type="submit" class="auth-submit">Giriş Yap</button>
            </form>

            <div class="auth-divider">veya</div>

            <p style="text-align:center;font-size:.9rem;color:#4a5568;margin:0;">
                Henüz hesabınız yok mu?
                <a href="{{ lroute('b2c.auth.register') }}" style="color:#1a3c6b;font-weight:700;">Ücretsiz Kayıt Olun</a>
            </p>
        </div>
    </div>

</div>
@endsection

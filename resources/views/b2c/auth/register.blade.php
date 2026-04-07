@extends('b2c.layouts.app')
@section('title', 'Ücretsiz Kayıt Ol — Grup Rezervasyonları')

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
    font-size: clamp(1.4rem, 2vw, 1.8rem);
    font-weight: 800; color: #fff; margin-bottom: 1rem; line-height: 1.3;
    position: relative;
}
.auth-brand-sub {
    color: rgba(255,255,255,.7); font-size: .93rem; margin-bottom: 2rem;
    line-height: 1.6; position: relative;
}
.auth-benefit {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.1);
    color: rgba(255,255,255,.85); font-size: .88rem; position: relative;
}
.auth-benefit:last-child { border-bottom: none; }
.auth-benefit i { color: var(--gr-accent, #f4a418); font-size: 1rem; flex-shrink: 0; margin-top: 2px; }

.auth-form-panel {
    background: #fff;
    display: flex; flex-direction: column; justify-content: center;
    padding: 3rem;
    overflow-y: auto;
}
@@media (max-width: 480px) {
    .auth-form-panel { padding: 2rem 1.5rem; }
}
.auth-form-inner { max-width: 440px; margin: 0 auto; width: 100%; }
.auth-form-title { font-size: 1.5rem; font-weight: 800; color: #1a202c; margin-bottom: 4px; }
.auth-form-sub { font-size: .9rem; color: #718096; margin-bottom: 1.5rem; }

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
.auth-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
@@media (max-width: 420px) { .auth-row { grid-template-columns: 1fr; } }
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
    text-align: center; margin: 16px 0;
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
        <a href="{{ route('b2c.home') }}" class="auth-brand-logo">
            Grup<span>Rezervasyonları</span>
        </a>
        <div class="auth-brand-headline">
            Ücretsiz hesap açın,<br>anında keşfetmeye başlayın.
        </div>
        <p class="auth-brand-sub">
            Kayıt olmak 1 dakikadan az sürer. Sipariş takibi, özel teklifler ve erken erişim fırsatları için hemen katılın.
        </p>
        <div class="auth-benefit"><i class="bi bi-gift-fill"></i><div><div style="font-weight:600;">Hoş Geldin Avantajı</div><div style="opacity:.7;">İlk rezervasyonunuzda özel fiyat fırsatı</div></div></div>
        <div class="auth-benefit"><i class="bi bi-bag-check-fill"></i><div><div style="font-weight:600;">Sipariş Takibi</div><div style="opacity:.7;">Tüm rezervasyonlarınızı tek yerden yönetin</div></div></div>
        <div class="auth-benefit"><i class="bi bi-bell-fill"></i><div><div style="font-weight:600;">Erken Erişim</div><div style="opacity:.7;">Kampanyaları ve yeni hizmetleri ilk siz görün</div></div></div>
        <div class="auth-benefit"><i class="bi bi-shield-check-fill"></i><div><div style="font-weight:600;">Güvenli & Korumalı</div><div style="opacity:.7;">Kişisel verileriniz KVKK kapsamında korunur</div></div></div>
    </div>

    {{-- Sağ panel: form --}}
    <div class="auth-form-panel">
        <div class="auth-form-inner">
            <div class="auth-form-title">Hesap Oluşturun</div>
            <div class="auth-form-sub">Ücretsiz — Kredi kartı gerekmez</div>

            <form method="POST" action="{{ route('b2c.auth.register.post') }}">
                @csrf

                <div class="auth-row" style="margin-bottom:14px;">
                    <div>
                        <label class="auth-label" for="name">Ad Soyad</label>
                        <input type="text" id="name" name="name"
                               class="auth-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                               value="{{ old('name') }}" required autofocus
                               placeholder="Adınız Soyadınız">
                        @error('name')
                        <div style="color:#e53e3e;font-size:.8rem;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="auth-label" for="phone">
                            Telefon <span style="font-weight:400;color:#a0aec0;">(isteğe bağlı)</span>
                        </label>
                        <input type="tel" id="phone" name="phone"
                               class="auth-input"
                               value="{{ old('phone') }}"
                               placeholder="+90 5XX XXX XX XX">
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="auth-label" for="email">E-posta adresi</label>
                    <input type="email" id="email" name="email"
                           class="auth-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           value="{{ old('email') }}" required autocomplete="email"
                           placeholder="ornek@email.com">
                    @error('email')
                    <div style="color:#e53e3e;font-size:.8rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-row" style="margin-bottom:20px;">
                    <div>
                        <label class="auth-label" for="password">Şifre</label>
                        <input type="password" id="password" name="password"
                               class="auth-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               required autocomplete="new-password"
                               placeholder="En az 8 karakter">
                        @error('password')
                        <div style="color:#e53e3e;font-size:.8rem;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="auth-label" for="password_confirmation">Şifre Tekrar</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="auth-input" required autocomplete="new-password"
                               placeholder="Tekrar girin">
                    </div>
                </div>

                <button type="submit" class="auth-submit">Ücretsiz Kayıt Ol</button>

                <p style="text-align:center;font-size:.78rem;color:#a0aec0;margin:12px 0 0;">
                    Kayıt olarak <a href="{{ route('b2c.kvkk') }}" target="_blank" style="color:#718096;">Gizlilik Politikası</a>'nı
                    ve <a href="{{ route('b2c.mesafeli-satis') }}" target="_blank" style="color:#718096;">Kullanım Koşulları</a>'nı kabul etmiş olursunuz.
                </p>
            </form>

            <div class="auth-divider">veya</div>

            <p style="text-align:center;font-size:.9rem;color:#4a5568;margin:0;">
                Zaten hesabınız var mı?
                <a href="{{ route('b2c.auth.login') }}" style="color:#1a3c6b;font-weight:700;">Giriş Yapın</a>
            </p>
        </div>
    </div>

</div>
@endsection

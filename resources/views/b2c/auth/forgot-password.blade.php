@extends('b2c.layouts.app')
@section('title', 'Şifremi Unuttum — Grup Rezervasyonları')

@push('head_styles')
<style>
.auth-center-wrap {
    min-height: calc(100vh - 64px);
    display: flex; align-items: center; justify-content: center;
    background: #f7f9fc; padding: 2rem;
}
.auth-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
    padding: 2.5rem; width: 100%; max-width: 440px;
}
.auth-input {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .95rem; color: #1a202c; outline: none;
    transition: border-color .15s, box-shadow .15s; background: #fafbfc;
}
.auth-input:focus { border-color: #1a3c6b; box-shadow: 0 0 0 3px rgba(26,60,107,.1); background: #fff; }
.auth-input.is-invalid { border-color: #e53e3e; }
.auth-submit {
    width: 100%; padding: 13px; background: #1a3c6b; color: #fff;
    border: none; border-radius: 10px; font-size: 1rem; font-weight: 700;
    cursor: pointer; transition: background .15s;
}
.auth-submit:hover { background: #152f56; }
</style>
@endpush

@section('content')
<div class="auth-center-wrap">
    <div class="auth-card">
        <div style="text-align:center;margin-bottom:1.5rem;">
            <div style="width:56px;height:56px;background:#ebf0fb;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                <i class="bi bi-lock-fill" style="font-size:1.5rem;color:#1a3c6b;"></i>
            </div>
            <div style="font-size:1.4rem;font-weight:800;color:#1a202c;">Şifremi Unuttum</div>
            <div style="font-size:.9rem;color:#718096;margin-top:4px;">E-posta adresinize sıfırlama bağlantısı gönderilecek.</div>
        </div>

        @if(session('status'))
        <div style="background:#ebfbee;border:1px solid #9ae6b4;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:.9rem;color:#276749;text-align:center;">
            <i class="bi bi-check-circle me-1"></i>{{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ lroute('b2c.auth.forgot.post') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:.85rem;font-weight:600;color:#2d3748;margin-bottom:6px;" for="email">
                    E-posta adresi
                </label>
                <input type="email" id="email" name="email"
                       class="auth-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                       value="{{ old('email') }}" required autofocus
                       placeholder="ornek@email.com">
                @error('email')
                <div style="color:#e53e3e;font-size:.82rem;margin-top:5px;">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="auth-submit">Sıfırlama Bağlantısı Gönder</button>
        </form>

        <div style="text-align:center;margin-top:20px;">
            <a href="{{ lroute('b2c.auth.login') }}" style="font-size:.88rem;color:#718096;text-decoration:none;">
                <i class="bi bi-arrow-left me-1"></i>Giriş sayfasına dön
            </a>
        </div>
    </div>
</div>
@endsection

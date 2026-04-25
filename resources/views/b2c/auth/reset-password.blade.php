@extends('b2c.layouts.app')
@section('title', 'Yeni Şifre Belirle — Grup Rezervasyonları')

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
                <i class="bi bi-key-fill" style="font-size:1.5rem;color:#1a3c6b;"></i>
            </div>
            <div style="font-size:1.4rem;font-weight:800;color:#1a202c;">Yeni Şifre Belirle</div>
            <div style="font-size:.9rem;color:#718096;margin-top:4px;">Hesabınız için güçlü bir şifre seçin.</div>
        </div>

        <form method="POST" action="{{ lroute('b2c.auth.reset.post') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:.85rem;font-weight:600;color:#2d3748;margin-bottom:6px;">E-posta</label>
                <input type="email" name="email"
                       class="auth-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                       value="{{ old('email') }}" required placeholder="ornek@email.com">
                @error('email')
                <div style="color:#e53e3e;font-size:.82rem;margin-top:5px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:.85rem;font-weight:600;color:#2d3748;margin-bottom:6px;">Yeni Şifre</label>
                <input type="password" name="password"
                       class="auth-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                       required placeholder="En az 8 karakter">
                @error('password')
                <div style="color:#e53e3e;font-size:.82rem;margin-top:5px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:.85rem;font-weight:600;color:#2d3748;margin-bottom:6px;">Şifre Tekrar</label>
                <input type="password" name="password_confirmation"
                       class="auth-input" required placeholder="Tekrar girin">
            </div>

            <button type="submit" class="auth-submit">Şifremi Sıfırla</button>
        </form>
    </div>
</div>
@endsection

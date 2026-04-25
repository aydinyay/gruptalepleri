@extends('b2c.layouts.app')
@section('title', 'Profilim — Grup Rezervasyonları')

@section('content')
<style>
.account-wrap {
    max-width: 1100px; margin: 0 auto; padding: 32px 24px 60px;
    display: grid; grid-template-columns: 240px 1fr; gap: 28px;
}
@@media (max-width: 768px) {
    .account-wrap { grid-template-columns: 1fr; padding: 20px 16px 48px; }
}
.account-sidebar {
    background: #fff; border-radius: 14px;
    border: 1px solid #e8eef5;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 0; overflow: hidden; align-self: start;
}
.account-user-header {
    background: linear-gradient(135deg, #0f2444, #1a3c6b);
    padding: 20px; text-align: center;
}
.account-user-avatar {
    width: 60px; height: 60px; border-radius: 50%;
    background: rgba(255,255,255,.15); border: 2px solid rgba(255,255,255,.3);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 8px;
}
.account-user-name { font-size: .95rem; font-weight: 700; color: #fff; margin-bottom: 2px; }
.account-user-email { font-size: .78rem; color: rgba(255,255,255,.6); }
.account-nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 18px; font-size: .9rem; font-weight: 500;
    color: #4a5568; text-decoration: none;
    border-bottom: 1px solid #f0f4f8;
    transition: background .12s, color .12s;
}
.account-nav-item:last-child { border-bottom: none; }
.account-nav-item:hover { background: #f7faff; color: #1a3c6b; }
.account-nav-item.active { background: #ebf0fb; color: #1a3c6b; font-weight: 700; }
.account-nav-item i { font-size: 1rem; width: 18px; text-align: center; }
.profile-card {
    background: #fff; border-radius: 14px;
    border: 1px solid #e8eef5;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
    padding: 24px; margin-bottom: 20px;
}
.profile-card-title {
    font-size: 1rem; font-weight: 700; color: #1a202c;
    margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    padding-bottom: 12px; border-bottom: 1px solid #f0f4f8;
}
.profile-card-title i { color: #1a3c6b; }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: .85rem; font-weight: 600; color: #2d3748; margin-bottom: 6px; }
.form-input {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .95rem; color: #1a202c; outline: none;
    transition: border-color .15s, box-shadow .15s;
    background: #fafbfc; box-sizing: border-box;
}
.form-input:focus { border-color: #1a3c6b; box-shadow: 0 0 0 3px rgba(26,60,107,.1); background: #fff; }
.form-input.is-invalid { border-color: #e53e3e; }
.form-error { color: #e53e3e; font-size: .8rem; margin-top: 4px; }
.btn-save {
    padding: 11px 28px; background: #1a3c6b; color: #fff;
    border: none; border-radius: 10px; font-size: .95rem; font-weight: 700;
    cursor: pointer; transition: background .15s;
}
.btn-save:hover { background: #152f56; }
</style>

<div class="account-wrap">
    {{-- Sidebar --}}
    <aside class="account-sidebar">
        <div class="account-user-header">
            <div class="account-user-avatar">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="account-user-name">{{ $user->name }}</div>
            <div class="account-user-email">{{ $user->email }}</div>
        </div>
        <nav>
            <a href="{{ route('b2c.account.index') }}" class="account-nav-item">
                <i class="bi bi-house-fill"></i> Genel Bakış
            </a>
            <a href="{{ route('b2c.account.orders.index') }}" class="account-nav-item">
                <i class="bi bi-bag-fill"></i> Siparişlerim
            </a>
            <a href="{{ route('b2c.account.profile.edit') }}" class="account-nav-item active">
                <i class="bi bi-person-fill"></i> Profilim
            </a>
            <a href="{{ route('b2c.sigorta.policelerim') }}" class="account-nav-item">
                <i class="bi bi-shield-fill-check"></i> Poliçelerim
            </a>
            <a href="{{ route('b2c.catalog.index') }}" class="account-nav-item">
                <i class="bi bi-grid-fill"></i> Hizmetleri Keşfet
            </a>
            <form method="POST" action="{{ route('b2c.auth.logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="account-nav-item" style="width:100%;background:none;border:none;cursor:pointer;text-align:left;border-radius:0;">
                    <i class="bi bi-box-arrow-right" style="color:#e53e3e;"></i>
                    <span style="color:#e53e3e;">Çıkış Yap</span>
                </button>
            </form>
        </nav>
    </aside>

    {{-- Main --}}
    <main>
        @if(session('success'))
        <div style="background:#ebfbee;border:1px solid #9ae6b4;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:.9rem;color:#276749;">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
        @endif

        {{-- Kişisel bilgiler --}}
        <div class="profile-card">
            <div class="profile-card-title">
                <i class="bi bi-person-fill"></i> Kişisel Bilgiler
            </div>
            <form method="POST" action="{{ route('b2c.account.profile.update') }}">
                @csrf
                @method('PUT')

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="name">Ad Soyad</label>
                        <input type="text" id="name" name="name"
                               class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone"
                               class="form-input"
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="+90 5XX XXX XX XX">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">E-posta adresi</label>
                    <input type="email" class="form-input" value="{{ $user->email }}" disabled
                           style="background:#f7f9fc;color:#718096;cursor:not-allowed;">
                    <div style="font-size:.78rem;color:#a0aec0;margin-top:4px;">E-posta adresi değiştirilemez.</div>
                </div>

                <button type="submit" class="btn-save">Bilgileri Kaydet</button>
            </form>
        </div>

        {{-- Şifre değiştir --}}
        <div class="profile-card">
            <div class="profile-card-title">
                <i class="bi bi-lock-fill"></i> Şifre Değiştir
            </div>
            <form method="POST" action="{{ route('b2c.account.profile.update') }}">
                @csrf
                @method('PUT')
                {{-- Mevcut name/phone'u hidden olarak gönderiyoruz ki validation geçsin --}}
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="phone" value="{{ $user->phone }}">

                <div class="form-group">
                    <label class="form-label" for="current_password">Mevcut Şifre</label>
                    <input type="password" id="current_password" name="current_password"
                           class="form-input {{ $errors->has('current_password') ? 'is-invalid' : '' }}"
                           placeholder="Mevcut şifreniz">
                    @error('current_password')<div class="form-error">{{ $message }}</div>@enderror
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label class="form-label" for="password">Yeni Şifre</label>
                        <input type="password" id="password" name="password"
                               class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                               placeholder="En az 8 karakter">
                        @error('password')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password_confirmation">Şifre Tekrar</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-input" placeholder="Tekrar girin">
                    </div>
                </div>

                <button type="submit" class="btn-save">Şifreyi Güncelle</button>
            </form>
        </div>
    </main>
</div>
@endsection

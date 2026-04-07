@extends('b2c.layouts.app')
@section('title', 'Kayıt Ol')

@section('content')
<section style="background:var(--gr-light);min-height:70vh;display:flex;align-items:center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="rounded-3 p-4 shadow-sm bg-white">
                    <h2 class="fw-800 mb-1" style="color:var(--gr-primary);">Hesap Oluştur</h2>
                    <p class="mb-4" style="font-size:.9rem;color:var(--gr-muted);">Ücretsiz kayıt olun.</p>

                    <form method="POST" action="{{ route('b2c.auth.register.post') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-600">Ad Soyad</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required autofocus>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600">E-posta</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600">Telefon (isteğe bağlı)</label>
                            <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600">Şifre</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-600">Şifre Tekrar</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-gr-primary btn-lg w-100">Kayıt Ol</button>
                    </form>

                    <hr>
                    <p class="text-center mb-0" style="font-size:.9rem;">
                        Hesabınız var mı?
                        <a href="{{ route('b2c.auth.login') }}" style="color:var(--gr-primary);font-weight:600;">Giriş Yapın</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

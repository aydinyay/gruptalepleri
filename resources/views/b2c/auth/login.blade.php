@extends('b2c.layouts.app')
@section('title', 'Giriş Yap')

@section('content')
<section style="background:var(--gr-light);min-height:70vh;display:flex;align-items:center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="rounded-3 p-4 shadow-sm bg-white">
                    <h2 class="fw-800 mb-1" style="color:var(--gr-primary);">Giriş Yap</h2>
                    <p class="mb-4" style="font-size:.9rem;color:var(--gr-muted);">Hesabınızla giriş yapın.</p>

                    @if(session('status'))
                    <div class="alert alert-info">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('b2c.auth.login.post') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-600">E-posta</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required autofocus>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600 d-flex justify-content-between">
                                Şifre
                                <a href="{{ route('b2c.auth.forgot') }}" style="font-weight:400;font-size:.85rem;">Şifremi unuttum</a>
                            </label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Beni hatırla</label>
                        </div>
                        <button type="submit" class="btn btn-gr-primary btn-lg w-100">Giriş Yap</button>
                    </form>

                    <hr>
                    <p class="text-center mb-0" style="font-size:.9rem;">
                        Hesabınız yok mu?
                        <a href="{{ route('b2c.auth.register') }}" style="color:var(--gr-primary);font-weight:600;">Kayıt Olun</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

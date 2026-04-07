@extends('b2c.layouts.app')
@section('title', 'Şifremi Unuttum')

@section('content')
<section style="background:var(--gr-light);min-height:60vh;display:flex;align-items:center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="rounded-3 p-4 shadow-sm bg-white">
                    <h2 class="fw-800 mb-1" style="color:var(--gr-primary);">Şifremi Unuttum</h2>
                    <p class="mb-4" style="font-size:.9rem;color:var(--gr-muted);">E-posta adresinize sıfırlama bağlantısı göndereceğiz.</p>
                    @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    <form method="POST" action="{{ route('b2c.auth.forgot.post') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-600">E-posta</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required autofocus>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-gr-primary btn-lg w-100">Bağlantı Gönder</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="{{ route('b2c.auth.login') }}" style="font-size:.88rem;color:var(--gr-muted);">← Giriş sayfasına dön</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@extends('b2c.layouts.app')
@section('title', 'Şifre Sıfırla')

@section('content')
<section style="background:var(--gr-light);min-height:60vh;display:flex;align-items:center;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="rounded-3 p-4 shadow-sm bg-white">
                    <h2 class="fw-800 mb-4" style="color:var(--gr-primary);">Yeni Şifre Belirle</h2>
                    <form method="POST" action="{{ route('b2c.auth.reset.post') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <div class="mb-3">
                            <label class="form-label fw-600">E-posta</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600">Yeni Şifre</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-600">Şifre Tekrar</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-gr-primary btn-lg w-100">Şifremi Sıfırla</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

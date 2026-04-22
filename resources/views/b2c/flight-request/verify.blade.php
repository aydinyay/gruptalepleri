@extends('b2c.layouts.app')

@section('title', 'Kimlik Doğrulama — ' . $talep->gtpnr)
@section('meta_description', 'Grup uçuş talebinizi görüntülemek için doğrulama yapın.')

@push('head_styles')
<style>
.verify-page {
    min-height: calc(100vh - var(--nav-height));
    background: linear-gradient(160deg, #0f2444 0%, #1a3c6b 60%, #1e4d8c 100%);
    padding: 48px 0 60px;
    display: flex; align-items: flex-start;
}
.verify-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    max-width: 480px;
    margin: 0 auto;
    overflow: hidden;
}
.verify-header {
    background: linear-gradient(135deg, #0f2444, #1a3c6b);
    color: #fff;
    padding: 32px 32px 28px;
    text-align: center;
}
.verify-icon {
    width: 60px; height: 60px;
    background: rgba(255,255,255,.15);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 14px;
}
.verify-header h1 { font-size: 1.2rem; font-weight: 700; margin: 0 0 5px; }
.verify-header p  { margin: 0; opacity: .75; font-size: .83rem; }
.verify-body { padding: 28px 32px 32px; }
.gtpnr-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f0f4ff;
    border: 1px solid #dde4f5;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: .88rem;
    margin-bottom: 22px;
}
.gtpnr-pill .code { font-weight: 800; color: #1a3c6b; font-family: monospace; letter-spacing: 1.5px; }
.v-label {
    display: block;
    font-size: .75rem;
    font-weight: 600;
    color: #6b7a99;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-bottom: 6px;
}
.v-input {
    width: 100%;
    border: 1.5px solid #dde4f5;
    border-radius: 10px;
    padding: 13px 14px;
    font-size: .95rem;
    color: #1a202c;
    transition: border-color .18s;
}
.v-input:focus { outline: none; border-color: #1a3c6b; box-shadow: 0 0 0 3px rgba(26,60,107,.08); }
.v-input.is-invalid { border-color: #dc3545; }
.v-error { color: #dc3545; font-size: .78rem; margin-top: 5px; }
.btn-verify {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 10px;
    background: var(--gr-accent);
    color: #fff;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .18s;
    margin-top: 18px;
}
.btn-verify:hover { background: #e0401e; transform: translateY(-1px); }
.help-text {
    font-size: .78rem;
    color: #718096;
    text-align: center;
    margin-top: 14px;
    line-height: 1.5;
}
@@media (max-width: 576px) {
    .verify-card { border-radius: 0; }
    .verify-header, .verify-body { padding-left: 20px; padding-right: 20px; }
}
</style>
@endpush

@section('content')
<div class="verify-page">
    <div class="container px-3 w-100">
        <div class="verify-card">

            <div class="verify-header">
                <div class="verify-icon">🔒</div>
                <h1>Kimlik Doğrulama</h1>
                <p>Talebinizi görüntülemek için kısa bir doğrulama yapın.</p>
            </div>

            <div class="verify-body">

                <div>
                    <span class="text-muted" style="font-size:.75rem;">Talep No</span>
                    <div class="gtpnr-pill">
                        <i class="bi bi-ticket-perforated" style="color:#1a3c6b;"></i>
                        <span class="code">{{ $talep->gtpnr }}</span>
                    </div>
                </div>

                @if($errors->any())
                <div class="alert alert-danger py-2 mb-3" style="font-size:.83rem;border-radius:10px;">
                    {{ $errors->first('credential') }}
                </div>
                @endif

                <form method="POST" action="{{ route('b2c.flight.verify.post', $talep->gtpnr) }}">
                    @csrf
                    <label class="v-label" for="inp-cred">Telefon Numarası veya E-posta Adresi</label>
                    <input type="text"
                           id="inp-cred"
                           name="credential"
                           class="v-input {{ $errors->has('credential') ? 'is-invalid' : '' }}"
                           placeholder="05XX XXX XX XX veya ornek@email.com"
                           autocomplete="off"
                           autofocus
                           required>
                    <button type="submit" class="btn-verify">
                        <i class="bi bi-shield-check me-2"></i>Doğrula ve Devam Et
                    </button>
                </form>

                <p class="help-text">
                    Talep sırasında girdiğiniz telefon numaranızı veya e-posta adresinizi girin.<br>
                    <a href="{{ route('b2c.flight.create') }}" style="color:#1a3c6b;">Yeni talep oluştur →</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

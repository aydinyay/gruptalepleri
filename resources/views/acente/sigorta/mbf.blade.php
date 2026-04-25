@extends('layouts.app')

@section('title', 'MBF — Mesleki Bilgi Formu')

@section('content')
<div class="container py-4" style="max-width:700px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('acente.sigorta.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">MBF — Mesleki Sorumluluk Bilgi Formu</h4>
    </div>

    @if(!$aktif)
    <div class="alert alert-warning">
        <i class="fas fa-clock me-2"></i>
        Sigorta modülü henüz aktif değil.
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('acente.sigorta.mbf-gonder') }}">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-bold">Sigorta Türü <span class="text-danger">*</span></label>
                    <div class="d-flex gap-3">
                        @foreach([1 => 'Yurtdışı', 2 => 'Yurtiçi', 3 => 'Kıbrıs'] as $val => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="trap_type"
                                id="trap{{ $val }}" value="{{ $val }}"
                                @checked(old('trap_type') == $val || ($val == 1 && !old('trap_type')))>
                            <label class="form-check-label" for="trap{{ $val }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Acente Adı / Unvanı</label>
                    <input type="text" name="acente_adi" value="{{ old('acente_adi') }}"
                        class="form-control" placeholder="Şirket unvanı">
                </div>

                <div class="mb-3">
                    <label class="form-label">Vergi No / TC</label>
                    <input type="text" name="vergi_no" value="{{ old('vergi_no') }}"
                        class="form-control" placeholder="Vergi numarası">
                </div>

                <div class="mb-3">
                    <label class="form-label">İletişim Telefonu</label>
                    <input type="text" name="telefon" value="{{ old('telefon') }}"
                        class="form-control" placeholder="+90 5XX XXX XX XX">
                </div>

                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-control" placeholder="acente@ornek.com">
                </div>

                <div class="mb-4">
                    <label class="form-label">Ek Notlar</label>
                    <textarea name="notlar" rows="3" class="form-control" placeholder="Varsa ek bilgi">{{ old('notlar') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" @if(!$aktif) disabled @endif>
                    <i class="fas fa-paper-plane me-1"></i> MBF Gönder
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

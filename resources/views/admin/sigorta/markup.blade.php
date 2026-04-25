@extends('layouts.admin-sigorta')

@section('title', 'Sigorta Markup Ayarları')

@section('content')
<div class="container py-4" style="max-width:600px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.sigorta.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">Sigorta Markup Ayarları</h4>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.sigorta.markup-guncelle') }}">
                @csrf
                @method('PATCH')

                <div class="mb-4">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" id="aktif" name="aktif"
                            value="1" @checked(($ayarlar['aktif'] ?? '0') === '1')>
                        <label class="form-check-label fw-bold" for="aktif">Sigorta Modülü Aktif</label>
                    </div>
                    <div class="form-text text-muted">PAO-Net API key yokken kapalı tutun.</div>
                </div>

                <hr class="my-4">

                <div class="mb-4">
                    <label class="form-label fw-bold">B2B Markup (%)</label>
                    <div class="input-group" style="max-width:200px">
                        <input type="number" name="b2b_markup_yuzde" step="0.5" min="0" max="200"
                            value="{{ $ayarlar['b2b_markup_yuzde'] ?? 20 }}" class="form-control">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Acenteye uygulanan ek kazanç oranı.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">B2C Markup (%)</label>
                    <div class="input-group" style="max-width:200px">
                        <input type="number" name="b2c_markup_yuzde" step="0.5" min="0" max="200"
                            value="{{ $ayarlar['b2c_markup_yuzde'] ?? 50 }}" class="form-control">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Tüketici kanalına uygulanan ek kazanç oranı.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Kur Tamponu (%)</label>
                    <div class="input-group" style="max-width:200px">
                        <input type="number" name="kur_tamponu_yuzde" step="0.5" min="0" max="50"
                            value="{{ $ayarlar['kur_tamponu_yuzde'] ?? 5 }}" class="form-control">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text text-muted">
                        Kur riski için ek tampon. Nihai fiyat:
                        <code>(Bprim × Kur) × (1 + (Markup + Tampon) / 100)</code>
                    </div>
                </div>

                <div class="alert alert-info small">
                    <strong>Örnek (B2B, şu anki ayarlarla):</strong><br>
                    @php
                        $b2b   = floatval($ayarlar['b2b_markup_yuzde'] ?? 20);
                        $b2c   = floatval($ayarlar['b2c_markup_yuzde'] ?? 50);
                        $tmpon = floatval($ayarlar['kur_tamponu_yuzde'] ?? 5);
                    @endphp
                    API fiyatı 10 USD × 38 TL kur = 380 TL maliyet<br>
                    B2B satış: 380 × (1 + {{ $b2b + $tmpon }}/100) = <strong>{{ number_format(380 * (1 + ($b2b + $tmpon) / 100), 2) }} ₺</strong><br>
                    B2C satış: 380 × (1 + {{ $b2c + $tmpon }}/100) = <strong>{{ number_format(380 * (1 + ($b2c + $tmpon) / 100), 2) }} ₺</strong>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Kaydet
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

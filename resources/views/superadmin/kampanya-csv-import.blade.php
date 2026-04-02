@extends('layouts.app')

@section('title', 'CSV Import — Bakanlık Veritabanı')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('tursab.kampanya') }}" class="btn btn-outline-secondary btn-sm">← Kampanya Hub</a>
        <h4 class="mb-0">📂 Bakanlık CSV Import</h4>
    </div>

    @if(session('success'))
        <div class="alert alert-success"><pre class="mb-0" style="white-space:pre-wrap;font-size:0.85rem;">{{ session('success') }}</pre></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        {{-- Import Formu --}}
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white fw-bold">
                    ⚠️ TRUNCATE + CSV Import (Tüm Veriyi Sil, Yeniden Yükle)
                </div>
                <div class="card-body">
                    <p class="text-muted small">Acenteler tablosu <strong>tamamen silinir</strong> ve CSV'den yeniden yüklenir. Mevcut tüm veriler (TÜRSAB dahil) kaybolur.</p>
                    <form method="POST" action="{{ route('kampanya.csv-import.yukle') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="no_truncate" value="0">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">CSV Dosyası</label>
                            <input type="file" name="csv_dosya" class="form-control" accept=".csv,.txt" required>
                            <div class="form-text">Max 50MB. UTF-8 veya UTF-8 BOM ile kaydedilmiş CSV olmalı.</div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100"
                            onclick="return confirm('Acenteler tablosu TAMAMEN silinecek ve CSV'den yeniden yüklenecek. Emin misiniz?')">
                            🗑️ Tabloyu Sıfırla ve İçe Aktar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- UpdateOrCreate --}}
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white fw-bold">
                    ✅ UpdateOrCreate (Mevcut Kayıtları Güncelle)
                </div>
                <div class="card-body">
                    <p class="text-muted small">Tablo <strong>silinmez</strong>. CSV'deki belge_no'ya göre mevcut kayıtlar güncellenir, yeni olanlar eklenir.</p>
                    <form method="POST" action="{{ route('kampanya.csv-import.yukle') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="no_truncate" value="1">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">CSV Dosyası</label>
                            <input type="file" name="csv_dosya" class="form-control" accept=".csv,.txt" required>
                            <div class="form-text">Max 50MB. Sadece CSV'deki kayıtlar güncellenir/eklenir.</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            📥 UpdateOrCreate ile İçe Aktar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Mevcut Durum --}}
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Mevcut Durum</h6>
            <div class="row g-3 text-center">
                <div class="col-sm-4">
                    <div class="border rounded p-3">
                        <div class="fs-2 fw-bold text-primary">{{ number_format($toplam) }}</div>
                        <div class="text-muted small">Toplam Acente</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Talimatlar --}}
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-bold">📋 CSV Format Rehberi</div>
        <div class="card-body">
            <p class="text-muted small mb-2">Desteklenen Excel/CSV kolon başlıkları:</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered small">
                    <thead class="table-light">
                        <tr><th>CSV Kolonu</th><th>DB Kolonu</th><th>Not</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>belgeNo</td><td>belge_no</td><td>Primary key</td></tr>
                        <tr><td>Detay_Unvan / unvan</td><td>acente_unvani</td><td>Detay varsa o öncelikli</td></tr>
                        <tr><td>Detay_TicariUnvan / ticariUnvan</td><td>ticari_unvan</td><td></td></tr>
                        <tr><td>grup</td><td>grup</td><td>A / B / C</td></tr>
                        <tr><td>_Il / ilAd</td><td>il</td><td>_Il öncelikli</td></tr>
                        <tr><td>Il_Ilce</td><td>il_ilce</td><td></td></tr>
                        <tr><td>Detay_Telefon / telefon</td><td>telefon</td><td>Detay öncelikli</td></tr>
                        <tr><td>E-posta</td><td>eposta</td><td></td></tr>
                        <tr><td>Faks</td><td>faks</td><td></td></tr>
                        <tr><td>Adres</td><td>adres</td><td></td></tr>
                        <tr><td>Harita</td><td>harita</td><td>Google Maps URL</td></tr>
                        <tr><td>internalId</td><td>internal_id</td><td></td></tr>
                        <tr><td>_Durum</td><td>durum</td><td>GEÇERLİ / İPTAL</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

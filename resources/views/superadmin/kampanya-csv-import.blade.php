<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>CSV Import — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.page-header p  { color:rgba(255,255,255,0.5); font-size:0.82rem; margin:0; }
</style>
</head>
<body>

<x-navbar-superadmin active="kampanya-csv-import" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-file-csv me-2" style="color:#198754;"></i>Bakanlık CSV Import</h5>
                <p>Bakanlık acente veritabanını sisteme yükle</p>
            </div>
            <a href="{{ route('superadmin.tursab.kampanya') }}" class="btn btn-sm btn-outline-light">← Kampanya Hub</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        @php $lines = explode("\n", session('success')); @endphp
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="fas fa-check-circle me-2"></i>
            @foreach($lines as $line)
                @if(str_starts_with(trim($line), 'http'))
                    <br><a href="{{ trim($line) }}" class="btn btn-sm btn-success mt-2" target="_blank">
                        ▶️ Import'u Başlat (deploy-run.php)
                    </a>
                @else
                    <div>{{ $line }}</div>
                @endif
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        {{-- TRUNCATE + Import --}}
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white fw-bold">
                    ⚠️ TRUNCATE + Import (Tüm Veriyi Sil, Yeniden Yükle)
                </div>
                <div class="card-body">
                    <p class="text-muted small">Acenteler tablosu <strong>tamamen silinir</strong> ve CSV'den yeniden yüklenir.</p>
                    <form method="POST" action="{{ route('superadmin.kampanya.csv-import.yukle') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="no_truncate" value="0">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">CSV Dosyası</label>
                            <input type="file" name="csv_dosya" class="form-control" accept=".csv,.txt" required>
                            <div class="form-text">Max 50MB. UTF-8 BOM ile kaydedilmiş CSV olmalı.</div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100"
                            onclick="return confirm('Tüm acenteler SİLİNECEK ve CSV\'den yeniden yüklenecek. Emin misiniz?')">
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
                    <form method="POST" action="{{ route('superadmin.kampanya.csv-import.yukle') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="no_truncate" value="1">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">CSV Dosyası</label>
                            <input type="file" name="csv_dosya" class="form-control" accept=".csv,.txt" required>
                            <div class="form-text">Max 50MB.</div>
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

    {{-- Format Rehberi --}}
    <div class="card shadow-sm mt-4">
        <div class="card-header fw-bold">📋 CSV Format Rehberi</div>
        <div class="card-body">
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
                        <tr><td>_Il / ilAd</td><td>il</td><td></td></tr>
                        <tr><td>Il_Ilce</td><td>il_ilce</td><td></td></tr>
                        <tr><td>Detay_Telefon / telefon</td><td>telefon</td><td></td></tr>
                        <tr><td>E-posta</td><td>eposta</td><td></td></tr>
                        <tr><td>Faks</td><td>faks</td><td></td></tr>
                        <tr><td>Adres</td><td>adres</td><td></td></tr>
                        <tr><td>Harita</td><td>harita</td><td></td></tr>
                        <tr><td>internalId</td><td>internal_id</td><td></td></tr>
                        <tr><td>_Durum</td><td>durum</td><td>GEÇERLİ / İPTAL</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

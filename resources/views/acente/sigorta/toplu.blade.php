@extends('layouts.app')

@section('title', 'Toplu Sigorta')

@section('content')
<div class="container py-4" style="max-width:960px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('acente.sigorta.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">Toplu Seyahat Sigortası</h4>
    </div>

    @if(!$aktif)
    <div class="alert alert-warning">
        <i class="fas fa-clock me-2"></i>
        Sigorta modülü henüz aktif değil. PAO-Net entegrasyonu tamamlandığında kullanıma açılacak.
    </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            {{-- Genel Bilgiler --}}
            <h6 class="fw-bold text-muted mb-3">Genel Seyahat Bilgileri</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">İşlem Adı</label>
                    <input type="text" id="islem_adi" class="form-control" placeholder="Örn: Almanya Grubu Haziran">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Başlangıç <span class="text-danger">*</span></label>
                    <input type="date" id="baslangic_tarihi" class="form-control" min="{{ today()->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bitiş <span class="text-danger">*</span></label>
                    <input type="date" id="bitis_tarihi" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Ülke <span class="text-danger">*</span></label>
                    <input type="text" id="ulke" class="form-control" placeholder="Almanya">
                </div>
            </div>

            <hr class="my-3">

            {{-- Yolcu Tablosu --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-muted mb-0">Yolcu Listesi</h6>
                <button type="button" id="btn-satir-ekle" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-plus me-1"></i> Satır Ekle
                </button>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered table-sm" id="yolcu-tablo">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>TC / Pasaport</th>
                            <th>Adı</th>
                            <th>Soyadı</th>
                            <th>Doğum Tarihi</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="yolcu-tbody">
                        {{-- JS tarafından doldurulur --}}
                    </tbody>
                </table>
            </div>

            <div id="hata-kutusu" class="alert alert-danger d-none"></div>

            <button type="button" id="btn-basla" class="btn btn-primary" @if(!$aktif) disabled @endif>
                <i class="fas fa-play me-1"></i> Sigortaları Başlat
            </button>
        </div>
    </div>

    {{-- İlerleme --}}
    <div id="ilerleme-panel" class="card shadow-sm d-none">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <h6 class="fw-bold mb-0">İlerleme</h6>
                <span id="ilerleme-yuzde" class="badge bg-primary">0%</span>
            </div>
            <div class="progress mb-3" style="height:20px">
                <div id="ilerleme-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                    role="progressbar" style="width:0%"></div>
            </div>
            <p id="ilerleme-metin" class="text-muted small mb-0">Başlatılıyor...</p>

            {{-- Hatalı kayıtlar --}}
            <div id="hatali-panel" class="d-none mt-4">
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-danger mb-0">Hatalı Kayıtlar</h6>
                    <button type="button" id="btn-retry" class="btn btn-sm btn-warning">
                        <i class="fas fa-redo me-1"></i> Tekrar Dene
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>TC / Pasaport</th><th>Ad Soyad</th><th>Hata</th></tr></thead>
                        <tbody id="hatali-tbody"></tbody>
                    </table>
                </div>
            </div>

            <div id="bitti-panel" class="d-none mt-3">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    İşlem tamamlandı! <a href="{{ route('acente.sigorta.index') }}" class="alert-link">Poliçe listesine git →</a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let batchId  = null;
let satirSay = 0;

// Başlangıçta 5 satır ekle
for (let i = 0; i < 5; i++) satirEkle();

document.getElementById('btn-satir-ekle').addEventListener('click', satirEkle);

function satirEkle() {
    satirSay++;
    const tbody = document.getElementById('yolcu-tbody');
    const tr = document.createElement('tr');
    tr.dataset.idx = satirSay;
    tr.innerHTML = `
        <td class="text-muted text-center">${satirSay}</td>
        <td><input type="text" class="form-control form-control-sm kimlik-input" placeholder="TC / Pasaport"></td>
        <td><input type="text" class="form-control form-control-sm" name="adi" placeholder="Adı"></td>
        <td><input type="text" class="form-control form-control-sm" name="soyadi" placeholder="Soyadı"></td>
        <td><input type="date" class="form-control form-control-sm" name="dogum_tarihi"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-sil" title="Sil">×</button></td>
    `;
    tbody.appendChild(tr);
    tr.querySelector('.btn-sil').addEventListener('click', () => tr.remove());
}

document.getElementById('btn-basla').addEventListener('click', async function () {
    const satirlar = [];
    document.querySelectorAll('#yolcu-tbody tr').forEach(tr => {
        const inputs = tr.querySelectorAll('input');
        const kimlik = inputs[0].value.trim();
        const adi    = inputs[1].value.trim();
        const soyadi = inputs[2].value.trim();
        const dogum  = inputs[3].value;
        if (kimlik && adi && soyadi && dogum) {
            satirlar.push({ kimlik, adi, soyadi, dogum_tarihi: dogum });
        }
    });

    if (satirlar.length === 0) { showHata('En az 1 yolcu girmelisiniz.'); return; }

    const bas = document.getElementById('baslangic_tarihi').value;
    const bit = document.getElementById('bitis_tarihi').value;
    const ulke = document.getElementById('ulke').value.trim();

    if (!bas || !bit || !ulke) { showHata('Başlangıç tarihi, bitiş tarihi ve ülke zorunludur.'); return; }

    this.disabled = true;
    document.getElementById('hata-kutusu').classList.add('d-none');

    const res  = await fetch('{{ route("acente.sigorta.toplu-basla") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({
            islem_adi: document.getElementById('islem_adi').value,
            baslangic_tarihi: bas,
            bitis_tarihi: bit,
            ulke,
            satirlar,
        }),
    });
    const data = await res.json();

    if (!res.ok || data.error) { showHata(data.error || 'Başlatılamadı.'); this.disabled = false; return; }

    batchId = data.batch_id;
    document.getElementById('ilerleme-panel').classList.remove('d-none');
    pollBatch();
});

async function pollBatch() {
    const res  = await fetch(`{{ url('/acente/sigorta/toplu') }}/${batchId}/poll`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    const data = await res.json();

    const pct = data.toplam > 0 ? Math.round(((data.tamamlanan + data.basarisiz) / data.toplam) * 100) : 0;
    document.getElementById('ilerleme-bar').style.width = pct + '%';
    document.getElementById('ilerleme-yuzde').textContent = pct + '%';
    document.getElementById('ilerleme-metin').textContent =
        `${data.tamamlanan} başarılı / ${data.basarisiz} hatalı / ${data.toplam} toplam`;

    if (data.hatali && data.hatali.length > 0) {
        const tbody = document.getElementById('hatali-tbody');
        tbody.innerHTML = '';
        data.hatali.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="fw-mono">${s.kimlik}</td><td>${s.adi} ${s.soyadi}</td><td class="text-danger small">${s.hata || '—'}</td>`;
            tbody.appendChild(tr);
        });
        document.getElementById('hatali-panel').classList.remove('d-none');
    }

    if (data.tamamlandi) {
        document.getElementById('ilerleme-bar').classList.remove('progress-bar-animated');
        document.getElementById('bitti-panel').classList.remove('d-none');
    } else {
        setTimeout(pollBatch, 2000);
    }
}

document.getElementById('btn-retry').addEventListener('click', async function () {
    await fetch(`{{ url('/acente/sigorta/toplu') }}/${batchId}/retry`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf },
    });
    document.getElementById('hatali-tbody').innerHTML = '';
    document.getElementById('bitti-panel').classList.add('d-none');
    document.getElementById('ilerleme-bar').classList.add('progress-bar-animated');
    pollBatch();
});

function showHata(msg) {
    const el = document.getElementById('hata-kutusu');
    el.textContent = msg;
    el.classList.remove('d-none');
}
</script>
@endpush
@endsection

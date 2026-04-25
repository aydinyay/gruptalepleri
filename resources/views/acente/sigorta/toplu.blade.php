@extends('layouts.acente-sigorta')

@section('title', 'Toplu Sigorta')

@section('content')
<div class="container py-4" style="max-width:1100px">
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

    {{-- Genel Seyahat Bilgileri --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h6 class="fw-bold text-muted mb-3">Genel Seyahat Bilgileri</h6>
            <div class="row g-3">
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
        </div>
    </div>

    {{-- Giriş Yöntemi Tabs --}}
    <div class="card shadow-sm mb-3">
        <div class="card-header p-0 border-0">
            <ul class="nav nav-tabs px-3 pt-2" id="giris-tabs">
                <li class="nav-item">
                    <button class="nav-link active" data-tab="manuel">
                        <i class="fas fa-keyboard me-1"></i> Manuel Giriş
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="excel">
                        <i class="fas fa-file-excel me-1 text-success"></i> Excel/CSV Yükle
                    </button>
                </li>
                <li class="nav-item ms-auto align-self-center pe-2">
                    <a href="{{ route('acente.sigorta.toplu-sablon') }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-download me-1"></i> Şablon İndir (.csv)
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            {{-- Manuel Giriş Tab --}}
            <div id="tab-manuel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">TC veya pasaport girildiğinde gerekli alanlar otomatik açılır.</span>
                    <button type="button" id="btn-satir-ekle" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-plus me-1"></i> Satır Ekle
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="yolcu-tablo">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px">#</th>
                                <th style="min-width:150px">TC / Pasaport</th>
                                <th style="min-width:100px">Adı</th>
                                <th style="min-width:100px">Soyadı</th>
                                <th style="min-width:110px">Doğum Tarihi</th>
                                <th style="width:80px">Tip</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="yolcu-tbody"></tbody>
                    </table>
                </div>
            </div>

            {{-- Excel Tab --}}
            <div id="tab-excel" class="d-none">
                <div class="alert alert-info small mb-3">
                    <strong>Desteklenen format:</strong> CSV (virgülle ayrılmış). Excel'den "Farklı Kaydet → CSV" ile kaydedebilirsiniz.<br>
                    <strong>Sütun sırası:</strong> kimlik, adi, soyadi, baba_adi, dogum_tarihi (YYYY-MM-DD), dogum_yeri, cinsiyet (E/K), uyruk, boy, kilo, il, ilce, adres
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">CSV Dosyası Seç</label>
                    <input type="file" id="csv-input" class="form-control" accept=".csv,.txt">
                </div>
                <div id="csv-preview" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold">Önizleme — <span id="csv-adet">0</span> yolcu</span>
                        <button type="button" id="btn-csv-temizle" class="btn btn-sm btn-outline-danger">Temizle</button>
                    </div>
                    <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th><th>Kimlik</th><th>Adı</th><th>Soyadı</th>
                                    <th>Doğum</th><th>Tip</th><th>Ülke/Uyruk</th>
                                </tr>
                            </thead>
                            <tbody id="csv-tbody"></tbody>
                        </table>
                    </div>
                    <button type="button" id="btn-csv-onayla" class="btn btn-success mt-3">
                        <i class="fas fa-check me-1"></i> Listeyi Onayla ve Manuel Tabloya Aktar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Hata & Başlat --}}
    <div id="hata-kutusu" class="alert alert-danger d-none"></div>
    <button type="button" id="btn-basla" class="btn btn-primary btn-lg" @if(!$aktif) disabled @endif>
        <i class="fas fa-play me-2"></i> Sigortaları Başlat
    </button>

    {{-- İlerleme Paneli --}}
    <div id="ilerleme-panel" class="card shadow-sm mt-4 d-none">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <h6 class="fw-bold mb-0">İlerleme</h6>
                <span id="ilerleme-yuzde" class="badge bg-primary">0%</span>
            </div>
            <div class="progress mb-2" style="height:22px">
                <div id="ilerleme-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                    role="progressbar" style="width:0%"></div>
            </div>
            <p id="ilerleme-metin" class="text-muted small mb-0">Başlatılıyor...</p>

            {{-- Hatalı Kayıtlar --}}
            <div id="hatali-panel" class="d-none mt-4">
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Hatalı Kayıtlar — <span id="hatali-adet">0</span> kişi
                    </h6>
                    <button type="button" id="btn-retry" class="btn btn-sm btn-warning">
                        <i class="fas fa-redo me-1"></i> Tekrar Dene
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>TC/Pasaport</th><th>Ad Soyad</th><th>Hata</th></tr>
                        </thead>
                        <tbody id="hatali-tbody"></tbody>
                    </table>
                </div>
            </div>

            <div id="bitti-panel" class="d-none mt-3">
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    İşlem tamamlandı!
                    <a href="{{ route('acente.sigorta.index') }}" class="alert-link ms-2">Poliçe listesine git →</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- NPN220 Ek Alan Modalı --}}
<div class="modal fade" id="pasaportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Pasaport Detayları — <span id="modal-kimlik-goster"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Baba Adı</label>
                        <input type="text" id="pm-baba-adi" class="form-control" placeholder="Baba adı">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Doğum Yeri <span class="text-danger">*</span></label>
                        <input type="text" id="pm-dogum-yeri" class="form-control" placeholder="Şehir">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cinsiyet <span class="text-danger">*</span></label>
                        <select id="pm-cinsiyet" class="form-select">
                            <option value="E">Erkek</option>
                            <option value="K">Kadın</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Uyruk <span class="text-danger">*</span></label>
                        <input type="text" id="pm-uyruk" class="form-control" placeholder="Rus / Alman">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Boy (cm)</label>
                        <input type="number" id="pm-boy" class="form-control" placeholder="175" min="50" max="250">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kilo (kg)</label>
                        <input type="number" id="pm-kilo" class="form-control" placeholder="70" min="10" max="300">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">İl <span class="text-danger">*</span></label>
                        <input type="text" id="pm-il" class="form-control" placeholder="İstanbul">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">İlçe <span class="text-danger">*</span></label>
                        <input type="text" id="pm-ilce" class="form-control" placeholder="Şişli">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Açık Adres <span class="text-danger">*</span></label>
                        <input type="text" id="pm-adres" class="form-control" placeholder="Mahalle, sokak, no">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-modal-kaydet">
                    <i class="fas fa-save me-1"></i> Kaydet
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">İptal</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let batchId = null;
let satirSay = 0;
let pasaportModalIdx = null; // hangi satırın modalı açık

// ── Tab değiştirme ─────────────────────────────────────────────────────────
document.querySelectorAll('[data-tab]').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('[data-tab]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-manuel').classList.toggle('d-none', this.dataset.tab !== 'manuel');
        document.getElementById('tab-excel').classList.toggle('d-none', this.dataset.tab !== 'excel');
    });
});

// ── Manuel Satır Ekleme ────────────────────────────────────────────────────
for (let i = 0; i < 5; i++) satirEkle();
document.getElementById('btn-satir-ekle').addEventListener('click', satirEkle);

function satirEkle(veri = {}) {
    satirSay++;
    const idx = satirSay;
    const tbody = document.getElementById('yolcu-tbody');
    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.dataset.pasaport = JSON.stringify({});
    tr.innerHTML = `
        <td class="text-center text-muted small">${idx}</td>
        <td>
            <input type="text" class="form-control form-control-sm kimlik-inp"
                value="${veri.kimlik || ''}" placeholder="TC / Pasaport" maxlength="20">
        </td>
        <td><input type="text" class="form-control form-control-sm" data-alan="adi"
            value="${veri.adi || ''}" placeholder="Adı"></td>
        <td><input type="text" class="form-control form-control-sm" data-alan="soyadi"
            value="${veri.soyadi || ''}" placeholder="Soyadı"></td>
        <td><input type="date" class="form-control form-control-sm" data-alan="dogum_tarihi"
            value="${veri.dogum_tarihi || ''}"></td>
        <td class="text-center">
            <span class="badge bg-secondary kimlik-tip-badge">TC</span>
            <button type="button" class="btn btn-xs btn-outline-primary ms-1 btn-pasaport d-none"
                title="Pasaport Detayları" style="padding:1px 5px;font-size:11px">
                <i class="fas fa-edit"></i>
            </button>
        </td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-sil">×</button></td>
    `;
    tbody.appendChild(tr);

    // CSV'den gelen pasaport verileri
    if (veri.kimlik && !/^\d{11}$/.test(veri.kimlik.trim())) {
        const pData = {
            baba_adi:  veri.baba_adi  || '',
            dogum_yeri:veri.dogum_yeri|| '',
            cinsiyet:  veri.cinsiyet  || 'E',
            uyruk:     veri.uyruk     || '',
            boy:       veri.boy       || '',
            kilo:      veri.kilo      || '',
            il:        veri.il        || '',
            ilce:      veri.ilce      || '',
            adres:     veri.adres     || '',
        };
        tr.dataset.pasaport = JSON.stringify(pData);
        tr.querySelector('.kimlik-tip-badge').textContent = 'Pasaport';
        tr.querySelector('.kimlik-tip-badge').className = 'badge bg-info kimlik-tip-badge';
        tr.querySelector('.btn-pasaport').classList.remove('d-none');
    }

    // Kimlik tipi algılama
    tr.querySelector('.kimlik-inp').addEventListener('input', function () {
        const v = this.value.trim();
        const isTC = /^\d{11}$/.test(v);
        const badge = tr.querySelector('.kimlik-tip-badge');
        const btnP  = tr.querySelector('.btn-pasaport');
        if (isTC) {
            badge.textContent = 'TC';
            badge.className = 'badge bg-secondary kimlik-tip-badge';
            btnP.classList.add('d-none');
        } else if (v.length > 0) {
            badge.textContent = 'Pasaport';
            badge.className = 'badge bg-info kimlik-tip-badge';
            btnP.classList.remove('d-none');
        } else {
            badge.textContent = 'TC';
            badge.className = 'badge bg-secondary kimlik-tip-badge';
            btnP.classList.add('d-none');
        }
    });

    // Pasaport modal aç
    tr.querySelector('.btn-pasaport').addEventListener('click', function () {
        pasaportModalIdx = idx;
        const pData = JSON.parse(tr.dataset.pasaport || '{}');
        document.getElementById('modal-kimlik-goster').textContent = tr.querySelector('.kimlik-inp').value;
        document.getElementById('pm-baba-adi').value  = pData.baba_adi  || '';
        document.getElementById('pm-dogum-yeri').value= pData.dogum_yeri|| '';
        document.getElementById('pm-cinsiyet').value  = pData.cinsiyet  || 'E';
        document.getElementById('pm-uyruk').value     = pData.uyruk     || '';
        document.getElementById('pm-boy').value       = pData.boy       || '';
        document.getElementById('pm-kilo').value      = pData.kilo      || '';
        document.getElementById('pm-il').value        = pData.il        || '';
        document.getElementById('pm-ilce').value      = pData.ilce      || '';
        document.getElementById('pm-adres').value     = pData.adres     || '';
        new bootstrap.Modal(document.getElementById('pasaportModal')).show();
    });

    tr.querySelector('.btn-sil').addEventListener('click', () => tr.remove());
}

// ── Pasaport Modal Kaydet ──────────────────────────────────────────────────
document.getElementById('btn-modal-kaydet').addEventListener('click', function () {
    const tr = document.querySelector(`tr[data-idx="${pasaportModalIdx}"]`);
    if (!tr) return;
    tr.dataset.pasaport = JSON.stringify({
        baba_adi:  document.getElementById('pm-baba-adi').value,
        dogum_yeri:document.getElementById('pm-dogum-yeri').value,
        cinsiyet:  document.getElementById('pm-cinsiyet').value,
        uyruk:     document.getElementById('pm-uyruk').value,
        boy:       document.getElementById('pm-boy').value,
        kilo:      document.getElementById('pm-kilo').value,
        il:        document.getElementById('pm-il').value,
        ilce:      document.getElementById('pm-ilce').value,
        adres:     document.getElementById('pm-adres').value,
    });
    bootstrap.Modal.getInstance(document.getElementById('pasaportModal')).hide();
});

// ── CSV Yükleme ────────────────────────────────────────────────────────────
let csvSatirlar = [];

document.getElementById('csv-input').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const text = e.target.result;
        const lines = text.split(/\r?\n/).filter(l => l.trim());
        // Başlık satırını atla
        const baslangic = /^kimlik|^tc|^pasaport/i.test(lines[0]) ? 1 : 0;
        csvSatirlar = [];
        const tbody = document.getElementById('csv-tbody');
        tbody.innerHTML = '';

        lines.slice(baslangic).forEach((line, i) => {
            const cols = line.split(',').map(c => c.trim().replace(/^"|"$/g, ''));
            const [kimlik='', adi='', soyadi='', baba_adi='', dogum_tarihi='',
                   dogum_yeri='', cinsiyet='E', uyruk='', boy='', kilo='',
                   il='', ilce='', adres=''] = cols;
            if (!kimlik) return;
            csvSatirlar.push({ kimlik, adi, soyadi, baba_adi, dogum_tarihi,
                dogum_yeri, cinsiyet, uyruk, boy, kilo, il, ilce, adres });
            const isTC = /^\d{11}$/.test(kimlik);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-muted">${i+1}</td>
                <td class="fw-mono small">${kimlik}</td>
                <td>${adi}</td>
                <td>${soyadi}</td>
                <td>${dogum_tarihi}</td>
                <td><span class="badge ${isTC ? 'bg-secondary' : 'bg-info'}">${isTC ? 'TC' : 'Pasaport'}</span></td>
                <td>${isTC ? '' : uyruk}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('csv-adet').textContent = csvSatirlar.length;
        document.getElementById('csv-preview').classList.remove('d-none');
    };
    reader.readAsText(file, 'UTF-8');
});

document.getElementById('btn-csv-temizle').addEventListener('click', function () {
    csvSatirlar = [];
    document.getElementById('csv-input').value = '';
    document.getElementById('csv-preview').classList.add('d-none');
});

document.getElementById('btn-csv-onayla').addEventListener('click', function () {
    // Manuel tablodaki boş satırları temizle
    document.querySelectorAll('#yolcu-tbody tr').forEach(tr => {
        if (!tr.querySelector('.kimlik-inp')?.value.trim()) tr.remove();
    });
    // CSV'den satır ekle
    csvSatirlar.forEach(veri => satirEkle(veri));
    // Manuel tab'a geç
    document.querySelector('[data-tab="manuel"]').click();
    // CSV önizlemeyi temizle
    csvSatirlar = [];
    document.getElementById('csv-input').value = '';
    document.getElementById('csv-preview').classList.add('d-none');
});

// ── Batch Başlat ──────────────────────────────────────────────────────────
document.getElementById('btn-basla').addEventListener('click', async function () {
    const bas  = document.getElementById('baslangic_tarihi').value;
    const bit  = document.getElementById('bitis_tarihi').value;
    const ulke = document.getElementById('ulke').value.trim();
    if (!bas || !bit || !ulke) { showHata('Başlangıç tarihi, bitiş tarihi ve ülke zorunludur.'); return; }

    const satirlar = [];
    document.querySelectorAll('#yolcu-tbody tr').forEach(tr => {
        const kimlik    = tr.querySelector('.kimlik-inp')?.value.trim() || '';
        const adi       = tr.querySelector('[data-alan="adi"]')?.value.trim() || '';
        const soyadi    = tr.querySelector('[data-alan="soyadi"]')?.value.trim() || '';
        const dogumT    = tr.querySelector('[data-alan="dogum_tarihi"]')?.value || '';
        if (!kimlik || !adi || !soyadi || !dogumT) return;

        const isTC = /^\d{11}$/.test(kimlik);
        const satir = { kimlik, adi, soyadi, dogum_tarihi: dogumT, baslangic_tarihi: bas, bitis_tarihi: bit, ulke };

        if (!isTC) {
            const pd = JSON.parse(tr.dataset.pasaport || '{}');
            Object.assign(satir, {
                baba_adi:   pd.baba_adi   || '',
                dogum_yeri: pd.dogum_yeri || '',
                cinsiyet:   pd.cinsiyet   || 'E',
                uyruk:      pd.uyruk      || '',
                boy:        pd.boy        || '',
                kilo:       pd.kilo       || '',
                il_adi:     pd.il         || '',
                ilce_adi:   pd.ilce       || '',
                adres:      pd.adres      || '',
            });
        }
        satirlar.push(satir);
    });

    if (satirlar.length === 0) { showHata('En az 1 geçerli yolcu girmelisiniz.'); return; }

    this.disabled = true;
    document.getElementById('hata-kutusu').classList.add('d-none');

    const res  = await fetch('{{ route("acente.sigorta.toplu-basla") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ islem_adi: document.getElementById('islem_adi').value, baslangic_tarihi: bas, bitis_tarihi: bit, ulke, satirlar }),
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

    const done  = (data.tamamlanan || 0) + (data.basarisiz || 0);
    const pct   = data.toplam > 0 ? Math.round((done / data.toplam) * 100) : 0;
    document.getElementById('ilerleme-bar').style.width = pct + '%';
    document.getElementById('ilerleme-yuzde').textContent = pct + '%';
    document.getElementById('ilerleme-metin').textContent =
        `${data.tamamlanan} başarılı / ${data.basarisiz} hatalı / ${data.toplam} toplam`;

    if (data.hatali && data.hatali.length > 0) {
        const tbody = document.getElementById('hatali-tbody');
        tbody.innerHTML = '';
        data.hatali.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="fw-mono small">${s.kimlik}</td>
                <td>${s.adi} ${s.soyadi}</td>
                <td class="text-danger small">${s.hata || '—'}</td>`;
            tbody.appendChild(tr);
        });
        document.getElementById('hatali-adet').textContent = data.hatali.length;
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
    document.getElementById('hatali-adet').textContent = '0';
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

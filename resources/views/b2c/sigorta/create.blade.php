@extends('b2c.layouts.app')

@section('title', 'Seyahat Sigortası')

@section('content')
<div class="container py-5" style="max-width:680px">
    <div class="text-center mb-5">
        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
        <h2 class="fw-bold">Yurtdışı Seyahat Sigortası</h2>
        <p class="text-muted">TC kimlik veya pasaportnuzla anında sigorta poliçenizi oluşturun.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-2">
            <span class="badge bg-light text-dark border fw-normal"><i class="fas fa-envelope me-1 text-primary"></i> Poliçe e-posta ile iletilir</span>
            <span class="badge bg-light text-dark border fw-normal"><i class="fas fa-sms me-1 text-success"></i> PDF linki SMS ile gönderilir</span>
            <span class="badge bg-light text-dark border fw-normal"><i class="fas fa-lock me-1 text-secondary"></i> SSL güvenli ödeme</span>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <div class="mb-3">
                <label class="form-label fw-bold">TC Kimlik No / Pasaport <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" id="kimlik" class="form-control form-control-lg"
                        placeholder="TC kimlik no veya pasaport" maxlength="20">
                    <span class="input-group-text bg-white" id="kimlik-tip-badge">🇹🇷 TC</span>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Doğum Tarihi <span class="text-danger">*</span></label>
                    <input type="date" id="dogum_tarihi" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Adınız <span class="text-danger">*</span></label>
                    <input type="text" id="adi" class="form-control" placeholder="Adınız">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Soyadınız <span class="text-danger">*</span></label>
                    <input type="text" id="soyadi" class="form-control" placeholder="Soyadınız">
                </div>
            </div>

            {{-- NPN220 Pasaport ek alanları --}}
            <div id="b2c-pasaport-alanlar" class="d-none">
                <div class="alert alert-info py-2 small mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Yabancı uyruklu sigortalı için ek bilgiler gerekmektedir.
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Baba Adı</label>
                        <input type="text" id="baba_adi" class="form-control" placeholder="Baba adı">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Doğum Yeri <span class="text-danger">*</span></label>
                        <input type="text" id="dogum_yeri" class="form-control" placeholder="Şehir / Ülke">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cinsiyet <span class="text-danger">*</span></label>
                        <select id="cinsiyet" class="form-select">
                            <option value="E">Erkek</option>
                            <option value="K">Kadın</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Uyruk <span class="text-danger">*</span></label>
                        <input type="text" id="uyruk" class="form-control" placeholder="Rus / Alman...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Boy (cm)</label>
                        <input type="number" id="boy" class="form-control" placeholder="175" min="50" max="250">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Kilo (kg)</label>
                        <input type="number" id="kilo" class="form-control" placeholder="70" min="10" max="300">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">İl <span class="text-danger">*</span></label>
                        <input type="text" id="il_adi" class="form-control" placeholder="İstanbul">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">İlçe <span class="text-danger">*</span></label>
                        <input type="text" id="ilce_adi" class="form-control" placeholder="Şişli">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Açık Adres <span class="text-danger">*</span></label>
                        <input type="text" id="adres" class="form-control" placeholder="Mahalle, sokak, kapı no">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Gidilecek Ülke <span class="text-danger">*</span></label>
                    <input type="text" id="ulke" class="form-control" placeholder="Almanya">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Gidiş Tarihi <span class="text-danger">*</span></label>
                    <input type="date" id="baslangic_tarihi" class="form-control" min="{{ today()->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Dönüş Tarihi <span class="text-danger">*</span></label>
                    <input type="date" id="bitis_tarihi" class="form-control">
                </div>
            </div>

            {{-- Honeypot --}}
            <input type="text" name="website" id="website" style="display:none" tabindex="-1" autocomplete="off">

            <div id="hata-kutusu" class="alert alert-danger d-none small"></div>

            <div id="teklif-sonuc" class="d-none">
                <div class="alert alert-success d-flex justify-content-between align-items-center py-3">
                    <div>
                        <div class="small text-muted mb-1">Sigorta Bedeli</div>
                        <div class="fs-4 fw-bold text-success" id="teklif-fiyat">—</div>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                </div>
                <input type="hidden" id="teklif_id">
                <input type="hidden" id="urun_kodu">
                <input type="hidden" id="bprim">
                <input type="hidden" id="dkuru">
                <input type="hidden" id="doviz_turu">
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="button" id="btn-teklif" class="btn btn-outline-primary btn-lg flex-fill">
                    <i class="fas fa-calculator me-2"></i> Fiyat Hesapla
                </button>
                <button type="button" id="btn-onayla" class="btn btn-primary btn-lg flex-fill d-none">
                    <i class="fas fa-check me-2"></i> Poliçe Al
                </button>
            </div>

            <p class="text-center text-muted small mt-3">
                <i class="fas fa-lock me-1"></i> Bilgileriniz güvende. PAO-Net / Nippon Sigorta.
            </p>
        </div>
    </div>

</div>

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

document.getElementById('kimlik').addEventListener('input', function () {
    const v = this.value.trim();
    const isTC = /^\d{11}$/.test(v);
    const isPasaport = v.length > 0 && !isTC;
    document.getElementById('kimlik-tip-badge').textContent = isPasaport ? '🌍 Pasaport' : '🇹🇷 TC';
    document.getElementById('b2c-pasaport-alanlar').classList.toggle('d-none', !isPasaport);
    document.getElementById('teklif-sonuc').classList.add('d-none');
    document.getElementById('btn-onayla').classList.add('d-none');
});

document.getElementById('btn-teklif').addEventListener('click', async function () {
    if (document.getElementById('website').value) return;

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Hesaplanıyor...';
    document.getElementById('hata-kutusu').classList.add('d-none');

    const val = (id) => document.getElementById(id)?.value ?? '';
    const body = {
        kimlik:           val('kimlik'),
        adi:              val('adi'),
        soyadi:           val('soyadi'),
        dogum_tarihi:     val('dogum_tarihi'),
        baslangic_tarihi: val('baslangic_tarihi'),
        bitis_tarihi:     val('bitis_tarihi'),
        ulke:             val('ulke'),
        // NPN220 pasaport alanları
        baba_adi:   val('baba_adi'),
        dogum_yeri: val('dogum_yeri'),
        cinsiyet:   val('cinsiyet'),
        uyruk:      val('uyruk'),
        boy:        val('boy'),
        kilo:       val('kilo'),
        il_adi:     val('il_adi'),
        ilce_adi:   val('ilce_adi'),
        adres:      val('adres'),
    };

    try {
        const res  = await fetch('{{ lroute("b2c.sigorta.teklif-al") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            showHata(data.error || 'Fiyat alınamadı.');
        } else {
            document.getElementById('teklif_id').value  = data.teklif_id;
            document.getElementById('urun_kodu').value  = data.urun_kodu;
            document.getElementById('bprim').value      = data.bprim;
            document.getElementById('dkuru').value      = data.dkuru;
            document.getElementById('doviz_turu').value = data.doviz_turu;
            document.getElementById('teklif-fiyat').textContent = fmt(data.satis_tl) + ' ₺';
            document.getElementById('teklif-sonuc').classList.remove('d-none');
            document.getElementById('btn-onayla').classList.remove('d-none');
        }
    } catch (e) {
        showHata('Bağlantı hatası. Tekrar deneyin.');
    }

    this.disabled = false;
    this.innerHTML = '<i class="fas fa-calculator me-2"></i>Fiyat Hesapla';
});

document.getElementById('btn-onayla').addEventListener('click', async function () {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Ödemeye Yönlendiriliyorsunuz...';

    const body = {
        teklif_id:        document.getElementById('teklif_id').value,
        urun_kodu:        document.getElementById('urun_kodu').value,
        kimlik:           document.getElementById('kimlik').value,
        adi:              document.getElementById('adi').value,
        soyadi:           document.getElementById('soyadi').value,
        dogum_tarihi:     document.getElementById('dogum_tarihi').value,
        baslangic_tarihi: document.getElementById('baslangic_tarihi').value,
        bitis_tarihi:     document.getElementById('bitis_tarihi').value,
        ulke:             document.getElementById('ulke').value,
        bprim:            document.getElementById('bprim').value,
        dkuru:            document.getElementById('dkuru').value,
        doviz_turu:       document.getElementById('doviz_turu').value,
    };

    try {
        const res  = await fetch('{{ lroute("b2c.sigorta.police-uret") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            showHata(data.error || 'Poliçe başlatılamadı.');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check me-2"></i> Poliçe Al';
            return;
        }

        // Ödeme sayfasına yönlendir — PAO-Net burada çağrılmıyor
        window.location.href = data.payment_url;
    } catch (e) {
        showHata('Bağlantı hatası. Tekrar deneyin.');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check me-2"></i> Poliçe Al';
    }
});

function showHata(msg) {
    const el = document.getElementById('hata-kutusu');
    el.textContent = msg; el.classList.remove('d-none');
}
function showModalHata(msg) {
    document.getElementById('modal-yukleniyor').classList.add('d-none');
    document.getElementById('modal-hata').classList.remove('d-none');
    document.getElementById('modal-hata-mesaj').textContent = msg;
}
function fmt(n) {
    return Number(n).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
@endpush
@endsection

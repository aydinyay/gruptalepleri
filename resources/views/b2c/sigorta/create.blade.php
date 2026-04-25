@extends('b2c.layouts.app')

@section('title', 'Seyahat Sigortası')

@section('content')
<div class="container py-5" style="max-width:680px">
    <div class="text-center mb-5">
        <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
        <h2 class="fw-bold">Yurtdışı Seyahat Sigortası</h2>
        <p class="text-muted">TC kimlik veya pasaportnuzla anında sigorta poliçenizi oluşturun.</p>
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

    {{-- Sonuç Modal --}}
    <div class="modal fade" id="policeModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body text-center p-5">
                    <div id="modal-yukleniyor">
                        <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem"></div>
                        <h5>Poliçeniz Hazırlanıyor</h5>
                        <p class="text-muted small">PAO-Net sigorta poliçenizi oluşturuyor...</p>
                    </div>
                    <div id="modal-tamam" class="d-none">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h4 class="fw-bold">Poliçeniz Hazır!</h4>
                        <p class="text-muted">Poliçe No: <strong id="modal-police-no"></strong></p>
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <a id="btn-police-indir" href="#" target="_blank" class="btn btn-danger btn-sm">
                                <i class="fas fa-file-pdf me-1"></i> PDF İndir
                            </a>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
                        </div>
                    </div>
                    <div id="modal-hata" class="d-none">
                        <i class="fas fa-times-circle text-danger fa-4x mb-3"></i>
                        <h5>Bir Sorun Oluştu</h5>
                        <p id="modal-hata-mesaj" class="text-danger small"></p>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

document.getElementById('kimlik').addEventListener('input', function () {
    const v = this.value.trim();
    const tip = /^\d{11}$/.test(v) ? '🇹🇷 TC' : (v.length > 0 ? '🌍 Pasaport' : '🇹🇷 TC');
    document.getElementById('kimlik-tip-badge').textContent = tip;
    document.getElementById('teklif-sonuc').classList.add('d-none');
    document.getElementById('btn-onayla').classList.add('d-none');
});

document.getElementById('btn-teklif').addEventListener('click', async function () {
    if (document.getElementById('website').value) return;

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Hesaplanıyor...';
    document.getElementById('hata-kutusu').classList.add('d-none');

    const body = {
        kimlik: document.getElementById('kimlik').value,
        adi: document.getElementById('adi').value,
        soyadi: document.getElementById('soyadi').value,
        dogum_tarihi: document.getElementById('dogum_tarihi').value,
        baslangic_tarihi: document.getElementById('baslangic_tarihi').value,
        bitis_tarihi: document.getElementById('bitis_tarihi').value,
        ulke: document.getElementById('ulke').value,
    };

    try {
        const res  = await fetch('{{ route("b2c.sigorta.teklif-al") }}', {
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
    const modal = new bootstrap.Modal(document.getElementById('policeModal'));
    modal.show();

    const body = {
        teklif_id: document.getElementById('teklif_id').value,
        urun_kodu: document.getElementById('urun_kodu').value,
        kimlik: document.getElementById('kimlik').value,
        adi: document.getElementById('adi').value,
        soyadi: document.getElementById('soyadi').value,
        dogum_tarihi: document.getElementById('dogum_tarihi').value,
        baslangic_tarihi: document.getElementById('baslangic_tarihi').value,
        bitis_tarihi: document.getElementById('bitis_tarihi').value,
        ulke: document.getElementById('ulke').value,
        bprim: document.getElementById('bprim').value,
        dkuru: document.getElementById('dkuru').value,
        doviz_turu: document.getElementById('doviz_turu').value,
    };

    try {
        const res  = await fetch('{{ route("b2c.sigorta.police-uret") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            showModalHata(data.error || 'Poliçe oluşturulamadı.');
            return;
        }

        pollB2c(data.police_id);
    } catch (e) {
        showModalHata('Bağlantı hatası.');
    }
});

function pollB2c(policeId) {
    let n = 0;
    const iv = setInterval(async () => {
        n++;
        const res  = await fetch(`{{ url('/sigorta/police') }}/${policeId}/durum`);
        const data = await res.json();
        if (data.durum === 'tamamlandi') {
            clearInterval(iv);
            document.getElementById('modal-yukleniyor').classList.add('d-none');
            document.getElementById('modal-tamam').classList.remove('d-none');
            document.getElementById('modal-police-no').textContent = data.police_no || '—';
            document.getElementById('btn-police-indir').href = `{{ url('/sigorta/police') }}/${policeId}/belge/police`;
        } else if (n >= 40) {
            clearInterval(iv);
            showModalHata('Zaman aşımı. Lütfen biraz bekleyip tekrar deneyin.');
        }
    }, 3000);
}

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

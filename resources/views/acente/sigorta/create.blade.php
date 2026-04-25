@extends('layouts.acente-sigorta')

@section('title', 'Tekil Sigorta Poliçesi')

@section('content')
<div class="container py-4" style="max-width:720px">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('acente.sigorta.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <h4 class="mb-0 fw-bold">Yeni Seyahat Sigortası</h4>
    </div>

    @if(!$aktif)
    <div class="alert alert-warning">
        <i class="fas fa-clock me-2"></i>
        Sigorta modülü henüz aktif değil. PAO-Net entegrasyonu tamamlandığında kullanıma açılacak.
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form id="sigorta-form">
                @csrf

                {{-- Sigortalı Bilgileri --}}
                <h6 class="fw-bold text-muted mb-3">Sigortalı Bilgileri</h6>

                <div class="mb-3">
                    <label class="form-label">TC Kimlik No / Pasaport <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" id="kimlik" name="kimlik" class="form-control"
                            placeholder="11 haneli TC veya pasaport no" maxlength="20">
                        <span class="input-group-text" id="kimlik-tip">TC</span>
                    </div>
                    <div id="musteri-bilgi" class="form-text text-success d-none"></div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Doğum Tarihi <span class="text-danger">*</span></label>
                        <input type="date" id="dogum_tarihi" name="dogum_tarihi" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Adı <span class="text-danger">*</span></label>
                        <input type="text" id="adi" name="adi" class="form-control" placeholder="Adı">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Soyadı <span class="text-danger">*</span></label>
                        <input type="text" id="soyadi" name="soyadi" class="form-control" placeholder="Soyadı">
                    </div>
                </div>

                {{-- Pasaport (NPN220) ek alanları — TC girilince gizlenir --}}
                <div id="pasaport-alanlar" class="d-none">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Yabancı uyruklu / pasaportlu sigortalı için ek bilgiler gereklidir (NPN220).
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Doğum Yeri <span class="text-danger">*</span></label>
                            <input type="text" id="dogum_yeri" name="dogum_yeri" class="form-control" placeholder="İstanbul / Moskova">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Uyruk <span class="text-danger">*</span></label>
                            <input type="text" id="uyruk" name="uyruk" class="form-control" placeholder="Rus / Alman / Fransız">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Boy (cm)</label>
                            <input type="number" id="boy" name="boy" class="form-control" placeholder="175" min="50" max="250">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kilo (kg)</label>
                            <input type="number" id="kilo" name="kilo" class="form-control" placeholder="70" min="10" max="300">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">İl <span class="text-danger">*</span></label>
                            <input type="text" id="il_adi" name="il_adi" class="form-control" placeholder="İstanbul">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">İlçe <span class="text-danger">*</span></label>
                            <input type="text" id="ilce_adi" name="ilce_adi" class="form-control" placeholder="Şişli">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açık Adres <span class="text-danger">*</span></label>
                            <input type="text" id="adres" name="adres" class="form-control" placeholder="Mahalle, sokak, kapı no">
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                {{-- Seyahat Bilgileri --}}
                <h6 class="fw-bold text-muted mb-3">Seyahat Bilgileri</h6>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Gidilecek Ülke <span class="text-danger">*</span></label>
                        <input type="text" id="ulke" name="ulke" class="form-control" placeholder="Örn: Almanya">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Başlangıç <span class="text-danger">*</span></label>
                        <input type="date" id="baslangic_tarihi" name="baslangic_tarihi" class="form-control"
                            min="{{ today()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bitiş <span class="text-danger">*</span></label>
                        <input type="date" id="bitis_tarihi" name="bitis_tarihi" class="form-control">
                    </div>
                </div>

                {{-- Teklif Sonucu --}}
                <div id="teklif-sonuc" class="d-none">
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Sigorta Bedeli:</strong>
                            <span id="teklif-fiyat" class="fs-5 fw-bold ms-2">—</span>
                        </div>
                        <div class="text-muted small" id="teklif-doviz"></div>
                    </div>
                    <input type="hidden" id="teklif_id" name="teklif_id">
                    <input type="hidden" id="urun_kodu" name="urun_kodu">
                    <input type="hidden" id="bprim" name="bprim">
                    <input type="hidden" id="dkuru" name="dkuru">
                    <input type="hidden" id="doviz_turu" name="doviz_turu">
                </div>

                <div id="hata-kutusu" class="alert alert-danger d-none"></div>

                <div class="d-flex gap-2 mt-3">
                    <button type="button" id="btn-teklif" class="btn btn-outline-primary" @if(!$aktif) disabled @endif>
                        <i class="fas fa-calculator me-1"></i> Teklif Al
                    </button>
                    <button type="button" id="btn-onayla" class="btn btn-success d-none" @if(!$aktif) disabled @endif>
                        <i class="fas fa-check me-1"></i> Poliçeyi Onayla
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Poliçe Oluşturuldu Modal --}}
    <div class="modal fade" id="policeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Poliçe Oluşturuluyor</h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div id="police-yukleniyor">
                        <div class="spinner-border text-primary mb-3"></div>
                        <p class="text-muted">PAO-Net poliçenizi oluşturuyor, lütfen bekleyin...</p>
                    </div>
                    <div id="police-tamamlandi" class="d-none">
                        <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                        <h5>Poliçe Oluşturuldu!</h5>
                        <p class="text-muted">Poliçe No: <strong id="police-no-goster"></strong></p>
                        <a id="btn-detay-git" href="#" class="btn btn-primary">Poliçeyi Görüntüle</a>
                    </div>
                    <div id="police-hata" class="d-none">
                        <i class="fas fa-times-circle text-danger fa-3x mb-3"></i>
                        <h5>Hata Oluştu</h5>
                        <p id="police-hata-mesaj" class="text-danger"></p>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let policeId = null;
let pollInterval = null;

// Kimlik tipi algılama
document.getElementById('kimlik').addEventListener('input', function () {
    const val = this.value.trim();
    const isTC = /^\d{11}$/.test(val);
    const isPasaport = val.length > 0 && !isTC;
    document.getElementById('kimlik-tip').textContent = isTC ? 'TC' : (isPasaport ? 'Pasaport' : 'TC');
    document.getElementById('pasaport-alanlar').classList.toggle('d-none', !isPasaport);
    document.getElementById('teklif-sonuc').classList.add('d-none');
    document.getElementById('btn-onayla').classList.add('d-none');
});

// Teklif Al
document.getElementById('btn-teklif').addEventListener('click', async function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Hesaplanıyor...';
    document.getElementById('hata-kutusu').classList.add('d-none');

    const body = {
        kimlik:           document.getElementById('kimlik').value,
        adi:              document.getElementById('adi').value,
        soyadi:           document.getElementById('soyadi').value,
        dogum_tarihi:     document.getElementById('dogum_tarihi').value,
        baslangic_tarihi: document.getElementById('baslangic_tarihi').value,
        bitis_tarihi:     document.getElementById('bitis_tarihi').value,
        ulke:             document.getElementById('ulke').value,
        // Pasaport (NPN220) ek alanlar
        dogum_yeri:  document.getElementById('dogum_yeri').value,
        uyruk:       document.getElementById('uyruk').value,
        boy:         document.getElementById('boy').value,
        kilo:        document.getElementById('kilo').value,
        il_adi:      document.getElementById('il_adi').value,
        ilce_adi:    document.getElementById('ilce_adi').value,
        adres:       document.getElementById('adres').value,
    };

    try {
        const res  = await fetch('{{ route("acente.sigorta.teklif-al") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            showHata(data.error || 'Teklif alınamadı.');
        } else {
            document.getElementById('teklif_id').value    = data.teklif_id;
            document.getElementById('urun_kodu').value    = data.urun_kodu;
            document.getElementById('bprim').value        = data.bprim;
            document.getElementById('dkuru').value        = data.dkuru;
            document.getElementById('doviz_turu').value   = data.doviz_turu;
            document.getElementById('teklif-fiyat').textContent = numFmt(data.satis_tl) + ' ₺';
            document.getElementById('teklif-doviz').textContent = data.doviz_turu + ' ' + data.bprim + ' × ' + data.dkuru;
            document.getElementById('teklif-sonuc').classList.remove('d-none');
            document.getElementById('btn-onayla').classList.remove('d-none');
        }
    } catch (e) {
        showHata('Ağ hatası. Tekrar deneyin.');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-calculator me-1"></i> Teklif Al';
});

// Poliçe Onayla
document.getElementById('btn-onayla').addEventListener('click', async function () {
    this.disabled = true;
    document.getElementById('police-yukleniyor').classList.remove('d-none');
    document.getElementById('police-tamamlandi').classList.add('d-none');
    document.getElementById('police-hata').classList.add('d-none');

    const modal = new bootstrap.Modal(document.getElementById('policeModal'));
    modal.show();

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
        const res  = await fetch('{{ route("acente.sigorta.police-uret") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            showPoliceHata(data.error || 'Poliçe oluşturulamadı.');
            return;
        }

        policeId = data.police_id;
        startPoll(policeId);
    } catch (e) {
        showPoliceHata('Ağ hatası. Lütfen poliçe listesini kontrol edin.');
    }
});

function startPoll(id) {
    let deneme = 0;
    const max  = 40; // 40 × 3s = 120s
    pollInterval = setInterval(async () => {
        deneme++;
        try {
            const res  = await fetch(`{{ url('/acente/sigorta/police') }}/${id}/uretim-durum`, {
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            const data = await res.json();

            if (data.durum === 'tamamlandi') {
                clearInterval(pollInterval);
                document.getElementById('police-yukleniyor').classList.add('d-none');
                document.getElementById('police-tamamlandi').classList.remove('d-none');
                document.getElementById('police-no-goster').textContent = data.police_no || '—';
                document.getElementById('btn-detay-git').href = `{{ url('/acente/sigorta/police') }}/${id}`;
            } else if (deneme >= max) {
                clearInterval(pollInterval);
                document.getElementById('police-yukleniyor').classList.add('d-none');
                document.getElementById('police-hata').classList.remove('d-none');
                document.getElementById('police-hata-mesaj').textContent = 'Zaman aşımı. Poliçe listesinden durumu kontrol edin.';
            }
        } catch (e) { /* sessiz devam */ }
    }, 3000);
}

function showHata(msg) {
    const el = document.getElementById('hata-kutusu');
    el.textContent = msg;
    el.classList.remove('d-none');
}

function showPoliceHata(msg) {
    document.getElementById('police-yukleniyor').classList.add('d-none');
    document.getElementById('police-hata').classList.remove('d-none');
    document.getElementById('police-hata-mesaj').textContent = msg;
}

function numFmt(n) {
    return Number(n).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
@endpush
@endsection

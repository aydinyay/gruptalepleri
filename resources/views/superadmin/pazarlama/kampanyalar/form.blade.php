<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yeni Kampanya — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.adim { border-left:3px solid #dee2e6; padding-left:1rem; margin-bottom:1.5rem; }
.adim.aktif { border-left-color:#ffc107; }
.adim-no { width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
           background:#ffc107; color:#000; font-weight:700; font-size:0.8rem; margin-right:8px; flex-shrink:0; }
</style>
</head>
<body>
<x-navbar-superadmin active="kampanyalar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-plus-circle me-2" style="color:#ffc107;"></i>Yeni Kampanya</h5>
            <a href="{{ route('superadmin.kampanyalar.index') }}" class="btn btn-sm btn-outline-light">← Geri</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">
    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('superadmin.kampanyalar.store') }}" id="kampanyaForm">
        @csrf

        <div class="row g-4">
            <div class="col-lg-8">

                {{-- ADIM 1: Temel Bilgi --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-2 d-flex align-items-center">
                        <span class="adim-no">1</span>
                        <span class="fw-semibold">Temel Bilgi</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Kampanya Adı *</label>
                                <input type="text" name="ad" class="form-control" required
                                       value="{{ old('ad') }}" placeholder="ör: 23 Nisan Kutlaması">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Kanal *</label>
                                <select name="tip" id="kanalSelect" class="form-select" required onchange="kanalDegisti()">
                                    <option value="email" {{ old('tip') === 'email' ? 'selected' : '' }}>📧 Email</option>
                                    <option value="sms"   {{ old('tip') === 'sms'   ? 'selected' : '' }}>📱 SMS</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Açıklama</label>
                                <textarea name="aciklama" class="form-control form-control-sm" rows="2"
                                          placeholder="İç not...">{{ old('aciklama') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ADIM 2: Şablon --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-2 d-flex align-items-center">
                        <span class="adim-no">2</span>
                        <span class="fw-semibold">Şablon Seç *</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2" id="sablonListesi">
                            @foreach($sablonlar as $s)
                            <div class="col-md-6">
                                <label class="d-block border rounded p-2 cursor-pointer sablon-kart {{ $s->tip }}"
                                       style="cursor:pointer;">
                                    <input type="radio" name="sablon_id" value="{{ $s->id }}"
                                           class="form-check-input me-2" required
                                           {{ old('sablon_id') == $s->id ? 'checked' : '' }}>
                                    <span class="badge {{ $s->tip === 'email' ? 'bg-danger' : 'bg-info text-dark' }} me-1">
                                        {{ $s->tip === 'email' ? '📧' : '📱' }}
                                    </span>
                                    <strong>{{ $s->ad }}</strong>
                                    @if($s->konu)
                                        <div class="text-muted small mt-1 ms-4">{{ $s->konu }}</div>
                                    @endif
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @if($sablonlar->isEmpty())
                            <div class="alert alert-warning py-2 mb-0">
                                Önce <a href="{{ route('superadmin.sablonlar.create') }}">şablon oluşturun</a>.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ADIM 3: Hedef Kitle --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-2 d-flex align-items-center">
                        <span class="adim-no">3</span>
                        <span class="fw-semibold">Hedef Kitle</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">İl</label>
                                <select name="filtre_il" class="form-select form-select-sm">
                                    <option value="">Tüm İller</option>
                                    @foreach($iller as $il)
                                        <option value="{{ $il }}" {{ old('filtre_il') === $il ? 'selected' : '' }}>{{ $il }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Grup</label>
                                <select name="filtre_grup" class="form-select form-select-sm">
                                    <option value="">Tümü</option>
                                    @foreach(['A','B','C'] as $g)
                                        <option value="{{ $g }}" {{ old('filtre_grup') === $g ? 'selected' : '' }}>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="sadece_yeni" id="sadeceYeni"
                                           value="1" {{ old('sadece_yeni', true) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="sadeceYeni">
                                        Daha önce temas edilmemiş
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ADIM 4: Zamanlama --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-2 d-flex align-items-center">
                        <span class="adim-no">4</span>
                        <span class="fw-semibold">Zamanlama *</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Başlangıç Tarihi <span class="text-muted">(boş = hemen)</span></label>
                                <input type="date" name="baslangic" class="form-control form-control-sm"
                                       value="{{ old('baslangic') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Bitiş Tarihi <span class="text-muted">(boş = süresiz)</span></label>
                                <input type="date" name="bitis" class="form-control form-control-sm"
                                       value="{{ old('bitis') }}">
                            </div>
                        </div>

                        <label class="form-label small fw-semibold">Gönderim Saatleri *</label>
                        <div id="slotlar">
                            <div class="d-flex gap-2 align-items-center mb-2" data-slot>
                                <input type="time" name="slotlar[0][saat]" class="form-control form-control-sm" style="width:130px;" value="09:00" required>
                                <input type="number" name="slotlar[0][adet]" class="form-control form-control-sm" style="width:90px;"
                                       placeholder="Adet" min="1" max="500" value="50" required>
                                <span class="text-muted small">kişi/gün</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="slotEkle()">
                            + Saat Ekle
                        </button>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top:20px;">
                    <div class="card-header py-2 fw-semibold small">Özet</div>
                    <div class="card-body py-2 small text-muted">
                        <p>Kampanya <strong>Taslak</strong> olarak kaydedilir.</p>
                        <p>Kaydettikten sonra listeden <strong>▶ Aktif Et</strong> butonuyla başlatın.</p>
                        <p>Sistem belirlenen saatlerde otomatik gönderim yapar.</p>
                    </div>
                    <div class="card-footer d-grid gap-2">
                        <button type="submit" class="btn btn-warning fw-bold">
                            <i class="fas fa-save me-1"></i>Taslak Kaydet
                        </button>
                        <a href="{{ route('superadmin.kampanyalar.index') }}" class="btn btn-outline-secondary">İptal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let slotIdx = 1;

function slotEkle() {
    const div = document.createElement('div');
    div.setAttribute('data-slot', '');
    div.className = 'd-flex gap-2 align-items-center mb-2';
    div.innerHTML = `
        <input type="time" name="slotlar[${slotIdx}][saat]" class="form-control form-control-sm" style="width:130px;" value="14:00" required>
        <input type="number" name="slotlar[${slotIdx}][adet]" class="form-control form-control-sm" style="width:90px;" placeholder="Adet" min="1" max="500" value="50" required>
        <span class="text-muted small">kişi/gün</span>
        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="this.parentElement.remove()">×</button>
    `;
    document.getElementById('slotlar').appendChild(div);
    slotIdx++;
}

function kanalDegisti() {
    const tip = document.getElementById('kanalSelect').value;
    document.querySelectorAll('.sablon-kart').forEach(el => {
        el.style.display = (el.classList.contains(tip) || tip === '') ? '' : 'none';
        const radio = el.querySelector('input[type=radio]');
        if (el.style.display === 'none' && radio.checked) radio.checked = false;
    });
}

kanalDegisti();
</script>
</body>
</html>

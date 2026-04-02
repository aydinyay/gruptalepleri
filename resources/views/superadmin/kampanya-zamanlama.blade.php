<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Otomatik Kampanya Zamanlaması — Süperadmin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; }
.page-header { background:linear-gradient(135deg,#1a1a2e,#16213e); padding:1.2rem 0; margin-bottom:1.5rem; }
.page-header h5 { color:#fff; font-weight:700; margin:0; }
.page-header p  { color:rgba(255,255,255,0.5); font-size:0.82rem; margin:0; }
.slot-row { display:grid; grid-template-columns:120px 100px 60px 40px; gap:8px; align-items:center; margin-bottom:8px; }
.slot-row label { font-size:0.8rem; color:#6c757d; }
.status-dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
.status-dot.active { background:#198754; }
.status-dot.inactive { background:#dc3545; }
</style>
</head>
<body>

<x-navbar-superadmin active="kampanya-zamanlama" />

<div class="page-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5><i class="fas fa-clock me-2" style="color:#ffc107;"></i>Otomatik Kampanya Zamanlaması</h5>
                <p>Her gün belirlenen saatlerde otomatik email ve SMS gönderimi</p>
            </div>
            <a href="{{ route('tursab.kampanya') }}" class="btn btn-sm btn-outline-light">← Kampanya Hub</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- cPanel Cron Talimatı --}}
    <div class="alert alert-warning d-flex gap-3 align-items-start mb-4">
        <i class="fas fa-exclamation-triangle mt-1"></i>
        <div>
            <strong>cPanel Cron Job Gerekli!</strong> Bu sistemin çalışması için cPanel → Cron Jobs bölümünde şu komutu her dakika çalışacak şekilde ekleyin:<br>
            <code class="d-block mt-1 p-2 bg-dark text-success rounded" style="font-size:0.82rem;">* * * * * cd /home/gruprez1/gruptalepleri.com &amp;&amp; php artisan schedule:run >> /dev/null 2>&amp;1</code>
            <small class="text-muted">Zamanlamayı: Dakika=*, Saat=*, Gün=*, Ay=*, Hafta=*</small>
        </div>
    </div>

    <div class="row g-4">

        {{-- EMAIL ZAMANLAMA --}}
        <div class="col-xl-6">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:#fff0f3;">
                    <span class="fw-bold"><i class="fas fa-envelope-open-text me-2 text-danger"></i>Email Otomasyonu</span>
                    <span class="status-dot {{ $emailAyar['aktif'] ? 'active' : 'inactive' }}"></span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('kampanya.zamanlama.kaydet') }}" id="emailForm">
                        @csrf
                        <input type="hidden" name="tip" value="email">

                        {{-- Aktif/Pasif --}}
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="aktif" id="emailAktif" value="1" @checked($emailAyar['aktif'])>
                            <label class="form-check-label fw-semibold" for="emailAktif">Otomasyonu Aktif Et</label>
                        </div>

                        {{-- Zaman Slotları --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Gönderim Saatleri</label>
                            <div id="emailSlotlar">
                                @foreach($emailAyar['slotlar'] as $i => $slot)
                                <div class="slot-row border rounded p-2 mb-2 bg-light" data-slot>
                                    <div>
                                        <label class="d-block">Saat</label>
                                        <input type="time" name="slot_saat[]" value="{{ $slot['saat'] }}"
                                               class="form-control form-control-sm">
                                    </div>
                                    <div>
                                        <label class="d-block">Adet</label>
                                        <input type="number" name="slot_adet[]" value="{{ $slot['adet'] }}"
                                               min="1" max="500" class="form-control form-control-sm">
                                    </div>
                                    <div class="text-center">
                                        <label class="d-block">Aktif</label>
                                        <input type="checkbox" name="slot_aktif[{{ $i }}]" value="1"
                                               class="form-check-input mt-1" @checked($slot['aktif'])>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSlot(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addSlot('emailSlotlar', emailSlotCount)">
                                + Saat Ekle
                            </button>
                        </div>

                        {{-- Filtreler --}}
                        <hr class="my-3">
                        <div class="fw-semibold small mb-2">Filtreler (opsiyonel)</div>
                        <div class="row g-2 mb-2">
                            <div class="col-sm-4">
                                <label class="form-label small mb-1">İl</label>
                                <select name="filtre_il" class="form-select form-select-sm">
                                    <option value="">Tüm İller</option>
                                    @foreach($iller as $il)
                                        <option value="{{ $il }}" @selected(($emailAyar['filtre']['il'] ?? '') === $il)>{{ $il }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label small mb-1">Grup</label>
                                <select name="filtre_grup" class="form-select form-select-sm">
                                    <option value="">Tümü</option>
                                    @foreach(['A','B','C'] as $g)
                                        <option value="{{ $g }}" @selected(($emailAyar['filtre']['grup'] ?? '') === $g)>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label small mb-1">Şablon</label>
                                <select name="filtre_sablon" class="form-select form-select-sm">
                                    <option value="emails.tursab_davet" @selected(($emailAyar['filtre']['sablon'] ?? '') === 'emails.tursab_davet')>Standart Davet</option>
                                    <option value="emails.tursab_davet_yeni_acente" @selected(($emailAyar['filtre']['sablon'] ?? '') === 'emails.tursab_davet_yeni_acente')>Yeni Acente Tebrik</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-save me-1"></i>Kaydet
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testGonder('email', true)">
                                <i class="fas fa-eye me-1"></i>Önizleme (Dry-Run)
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="testGonder('email', false)"
                                onclick="return confirm('Şimdi gerçekten gönderilecek!')">
                                <i class="fas fa-paper-plane me-1"></i>Şimdi Çalıştır
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- SMS ZAMANLAMA --}}
        <div class="col-xl-6">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:#e0f7ff;">
                    <span class="fw-bold"><i class="fas fa-sms me-2 text-info"></i>SMS Otomasyonu</span>
                    <span class="status-dot {{ $smsAyar['aktif'] ? 'active' : 'inactive' }}"></span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('kampanya.zamanlama.kaydet') }}" id="smsForm">
                        @csrf
                        <input type="hidden" name="tip" value="sms">

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="aktif" id="smsAktif" value="1" @checked($smsAyar['aktif'])>
                            <label class="form-check-label fw-semibold" for="smsAktif">Otomasyonu Aktif Et</label>
                        </div>

                        {{-- SMS Metni --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">SMS Metni</label>
                            <textarea name="sms_mesaj" class="form-control form-control-sm" rows="3"
                                maxlength="160" id="smsMesajOto"
                                placeholder="Otomatik gönderilecek SMS metni (max 160 karakter)...">{{ $smsAyar['mesaj'] ?? '' }}</textarea>
                            <div class="small text-muted mt-1"><span id="smsMesajChar">{{ mb_strlen($smsAyar['mesaj'] ?? '') }}</span>/160</div>
                        </div>

                        {{-- Zaman Slotları --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Gönderim Saatleri</label>
                            <div id="smsSlotlar">
                                @foreach($smsAyar['slotlar'] as $i => $slot)
                                <div class="slot-row border rounded p-2 mb-2 bg-light" data-slot>
                                    <div>
                                        <label class="d-block">Saat</label>
                                        <input type="time" name="slot_saat[]" value="{{ $slot['saat'] }}"
                                               class="form-control form-control-sm">
                                    </div>
                                    <div>
                                        <label class="d-block">Adet</label>
                                        <input type="number" name="slot_adet[]" value="{{ $slot['adet'] }}"
                                               min="1" max="500" class="form-control form-control-sm">
                                    </div>
                                    <div class="text-center">
                                        <label class="d-block">Aktif</label>
                                        <input type="checkbox" name="slot_aktif[{{ $i }}]" value="1"
                                               class="form-check-input mt-1" @checked($slot['aktif'])>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSlot(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addSlot('smsSlotlar', smsSlotCount)">
                                + Saat Ekle
                            </button>
                        </div>

                        {{-- Filtreler --}}
                        <hr class="my-3">
                        <div class="fw-semibold small mb-2">Filtreler (opsiyonel)</div>
                        <div class="row g-2 mb-2">
                            <div class="col-sm-6">
                                <label class="form-label small mb-1">İl</label>
                                <select name="filtre_il" class="form-select form-select-sm">
                                    <option value="">Tüm İller</option>
                                    @foreach($iller as $il)
                                        <option value="{{ $il }}" @selected(($smsAyar['filtre']['il'] ?? '') === $il)>{{ $il }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small mb-1">Grup</label>
                                <select name="filtre_grup" class="form-select form-select-sm">
                                    <option value="">Tümü</option>
                                    @foreach(['A','B','C'] as $g)
                                        <option value="{{ $g }}" @selected(($smsAyar['filtre']['grup'] ?? '') === $g)>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-info btn-sm text-white">
                                <i class="fas fa-save me-1"></i>Kaydet
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testGonder('sms', true)">
                                <i class="fas fa-eye me-1"></i>Önizleme
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="testGonder('sms', false)">
                                <i class="fas fa-sms me-1"></i>Şimdi Çalıştır
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    {{-- Çalışma Logları --}}
    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header py-2 fw-bold small">📧 Email Çalışma Logu (Son 7 Gün)</div>
                <div class="card-body py-2">
                    @if(empty($emailLog))
                        <p class="text-muted small mb-0">Henüz çalışmadı.</p>
                    @else
                        @foreach(array_reverse($emailLog, true) as $tarih => $slotlar)
                        <div class="mb-1 small">
                            <span class="fw-semibold">{{ $tarih }}</span>:
                            @foreach($slotlar as $s)
                                <span class="badge bg-success me-1">{{ $s }}</span>
                            @endforeach
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header py-2 fw-bold small">📱 SMS Çalışma Logu (Son 7 Gün)</div>
                <div class="card-body py-2">
                    @if(empty($smsLog))
                        <p class="text-muted small mb-0">Henüz çalışmadı.</p>
                    @else
                        @foreach(array_reverse($smsLog, true) as $tarih => $slotlar)
                        <div class="mb-1 small">
                            <span class="fw-semibold">{{ $tarih }}</span>:
                            @foreach($slotlar as $s)
                                <span class="badge bg-info me-1">{{ $s }}</span>
                            @endforeach
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Test Çıktısı Modal --}}
    <div class="modal fade" id="testModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Komut Çıktısı</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="testCikti" class="bg-dark text-success p-3 rounded" style="min-height:100px;white-space:pre-wrap;font-size:0.82rem;">Bekleniyor...</pre>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let emailSlotCount = {{ count($emailAyar['slotlar']) }};
let smsSlotCount   = {{ count($smsAyar['slotlar']) }};

function addSlot(containerId, countRef) {
    const container = document.getElementById(containerId);
    const idx = container.querySelectorAll('[data-slot]').length;
    const div = document.createElement('div');
    div.setAttribute('data-slot', '');
    div.className = 'slot-row border rounded p-2 mb-2 bg-light';
    div.innerHTML = `
        <div><label class="d-block">Saat</label><input type="time" name="slot_saat[]" value="09:00" class="form-control form-control-sm"></div>
        <div><label class="d-block">Adet</label><input type="number" name="slot_adet[]" value="50" min="1" max="500" class="form-control form-control-sm"></div>
        <div class="text-center"><label class="d-block">Aktif</label><input type="checkbox" name="slot_aktif[${idx}]" value="1" class="form-check-input mt-1" checked></div>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSlot(this)"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(div);
}

function removeSlot(btn) {
    const row = btn.closest('[data-slot]');
    if (row.parentElement.querySelectorAll('[data-slot]').length <= 1) {
        alert('En az 1 slot olmalı.'); return;
    }
    row.remove();
}

function testGonder(tip, dryRun) {
    if (!dryRun && !confirm((tip === 'email' ? 'Email' : 'SMS') + ' kampanyası şimdi gerçekten çalıştırılacak. Onaylıyor musunuz?')) return;

    const modal = new bootstrap.Modal(document.getElementById('testModal'));
    document.getElementById('testCikti').textContent = 'Çalışıyor...';
    modal.show();

    fetch('{{ route('kampanya.zamanlama.test') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ tip, dry_run: dryRun })
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('testCikti').textContent = d.output || 'Tamamlandı.';
    })
    .catch(() => {
        document.getElementById('testCikti').textContent = 'Hata oluştu.';
    });
}

// SMS karakter sayacı
const smsMesaj = document.getElementById('smsMesajOto');
const smsChar  = document.getElementById('smsMesajChar');
if (smsMesaj) {
    smsMesaj.addEventListener('input', () => smsChar.textContent = smsMesaj.value.length);
}
</script>
</body>
</html>

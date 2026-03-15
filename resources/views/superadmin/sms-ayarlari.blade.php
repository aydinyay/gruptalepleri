<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Ayarları — Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .page-header { background: #1a1a2e; padding: 1.2rem 0; margin-bottom: 1.5rem; }
        .page-header h5 { color: #fff; font-weight: 700; margin: 0; }
        .page-header p { color: rgba(255,255,255,0.5); font-size: 0.82rem; margin: 0; }
        .table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; font-weight: 600; }
        .table td { vertical-align: middle; font-size: 0.875rem; }
        .event-badge { padding: 3px 10px; border-radius: 50px; font-size: 0.72rem; font-weight: 600; }
        .event-new_agency     { background: #cfe2ff; color: #084298; }
        .event-new_request    { background: #d1e7dd; color: #0a3622; }
        .event-offer_added    { background: #fff3cd; color: #856404; }
        .event-offer_accepted { background: #e8d5f5; color: #4a0072; }
        .event-all            { background: #1a1a2e; color: #e94560; }
    </style>
</head>
<body>

<x-navbar-superadmin active="sms-ayarlar" />

<div class="page-header">
    <div class="container-fluid px-4">
        <h5><i class="fas fa-bell me-2" style="color:#e94560;"></i>SMS Bildirim Ayarları</h5>
        <p>Hangi olay gerçekleştiğinde kime SMS gideceğini buradan yönetin. Virgülle birden fazla numara girebilirsiniz.</p>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
    @endif

    {{-- SMS GÖNDERIM SAATLERİ --}}
    <div class="card shadow-sm mb-4 border-info">
        <div class="card-header fw-bold" style="background:#e8f4fd;border-bottom:1px solid #b8daff;">
            <i class="fas fa-moon text-info me-1"></i> SMS Gönderim Saatleri
            <span class="text-muted fw-normal" style="font-size:0.8rem;"> — Bu saat aralığı dışında SMS gönderilmez (push bildirimler etkilenmez)</span>
        </div>
        <div class="card-body py-3">
            <form method="POST" action="{{ route('superadmin.sms.saat') }}" class="d-flex align-items-end gap-3 flex-wrap">
                @csrf
                <div>
                    <label class="form-label small fw-bold mb-1">Başlangıç</label>
                    <input type="time" name="sms_baslangic" class="form-control form-control-sm" value="{{ $smsBaslangic }}" required style="width:130px;">
                </div>
                <div>
                    <label class="form-label small fw-bold mb-1">Bitiş</label>
                    <input type="time" name="sms_bitis" class="form-control form-control-sm" value="{{ $smsBitis }}" required style="width:130px;">
                </div>
                <div>
                    <button type="submit" class="btn btn-sm btn-info text-white fw-bold">
                        <i class="fas fa-save me-1"></i> Kaydet
                    </button>
                </div>
                <div class="text-muted" style="font-size:0.8rem;padding-bottom:4px;">
                    Şu an: <strong>{{ now()->format('H:i') }}</strong>
                    @php $simdi = now()->format('H:i'); @endphp
                    @if($simdi >= $smsBaslangic && $simdi <= $smsBitis)
                        <span class="badge bg-success ms-1">Gönderim aktif</span>
                    @else
                        <span class="badge bg-secondary ms-1">Gönderim kapalı</span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">

        {{-- YENİ KURAL EKLE --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="fas fa-plus me-1 text-success"></i> Yeni Kural Ekle
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.sms.ekle') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kişi / Etiket</label>
                            <input type="text" name="label" class="form-control form-control-sm"
                                   placeholder="Örn: Süper Admin - Güneş" value="{{ old('label') }}" required>
                            <div class="form-text">Kim olduğunu hatırlamak için</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Telefon Numarası</label>
                            <input type="text" name="phone" class="form-control form-control-sm"
                                   placeholder="05321234567 veya 053...,054..." value="{{ old('phone') }}" required>
                            <div class="form-text">Virgülle ayırarak birden fazla numara girebilirsiniz</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Olay</label>
                            <select name="event" class="form-select form-select-sm" required>
                                @foreach($events as $event)
                                <option value="{{ $event }}" {{ old('event') === $event ? 'selected' : '' }}>
                                    {{ \App\Models\SmsNotificationSetting::eventLabel($event) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm w-100" style="background:#e94560;color:#fff;font-weight:600;">
                            <i class="fas fa-plus me-1"></i> Ekle
                        </button>
                    </form>
                </div>
            </div>

            {{-- Olay açıklamaları --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header fw-bold small">Olay Açıklamaları</div>
                <div class="card-body p-3">
                    <div class="d-flex flex-column gap-2" style="font-size:0.82rem;">
                        <div><span class="event-badge event-new_agency">Yeni Acente</span> — Acente kayıt olduğunda</div>
                        <div><span class="event-badge event-new_request">Yeni Talep</span> — Acente talep oluşturduğunda</div>
                        <div><span class="event-badge event-offer_added">Teklif Eklendi</span> — Admin teklif eklediğinde (acenteye gider)</div>
                        <div><span class="event-badge event-offer_accepted">Teklif Kabul</span> — Acente teklifi kabul ettiğinde</div>
                        <div><span class="event-badge event-all">Tümü</span> — Yukarıdaki tüm olaylarda</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MEVCUT KURALLAR --}}
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="fas fa-list me-1"></i> Mevcut Kurallar</span>
                    <span class="badge bg-secondary">{{ $ayarlar->count() }} kural</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kişi</th>
                                <th>Numara(lar)</th>
                                <th>Olay</th>
                                <th>Durum</th>
                                <th>İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ayarlar as $ayar)
                            <tr class="{{ !$ayar->is_active ? 'text-muted' : '' }}">
                                <td class="fw-bold">{{ $ayar->label }}</td>
                                <td>
                                    @foreach(explode(',', $ayar->phone) as $tel)
                                        <span class="badge bg-light text-dark border">{{ trim($tel) }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="event-badge event-{{ $ayar->event }}">
                                        {{ \App\Models\SmsNotificationSetting::eventLabel($ayar->event) }}
                                    </span>
                                </td>
                                <td>
                                    @if($ayar->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Pasif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Düzenle"
                                            onclick="smsDuzenle({{ $ayar->id }}, {{ json_encode($ayar->label) }}, {{ json_encode($ayar->phone) }}, '{{ $ayar->event }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('superadmin.sms.toggle', $ayar) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $ayar->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                    title="{{ $ayar->is_active ? 'Pasif yap' : 'Aktif yap' }}">
                                                <i class="fas {{ $ayar->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('superadmin.sms.sil', $ayar) }}"
                                              onsubmit="return confirm('Bu kuralı silmek istediğinize emin misiniz?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Henüz kural eklenmemiş. Sol taraftan ekleyin.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

    {{-- OPSİYON UYARI AYARLARI --}}
    <div class="row g-4 mt-2">
        <div class="col-12">
            <div class="card shadow-sm border-warning">
                <div class="card-header d-flex justify-content-between align-items-center" style="background:#fff3cd;border-bottom:1px solid #ffc107;">
                    <div>
                        <i class="fas fa-clock text-warning me-1"></i> <span class="fw-bold">Opsiyon Yaklaşma Uyarıları</span>
                        <span class="text-muted fw-normal" style="font-size:0.8rem;"> — Opsiyonu dolmak üzere olan teklifler için otomatik SMS + Push bildirimi</span>
                    </div>
                    {{-- Scheduler aralığı --}}
                    <form method="POST" action="{{ route('superadmin.scheduler.aralik') }}" class="d-flex align-items-center gap-2">
                        @csrf
                        <label class="small fw-bold mb-0 text-nowrap">Kontrol Aralığı:</label>
                        <select name="aralik" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                            @foreach([
                                1    => '1 dakika',
                                5    => '5 dakika',
                                15   => '15 dakika',
                                30   => '30 dakika',
                                60   => '1 saat',
                                360  => '6 saat',
                                720  => '12 saat',
                                1440 => '1 gün',
                            ] as $val => $label)
                            <option value="{{ $val }}" {{ $schedulerAralik == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Yeni kural ekle --}}
                        <div class="col-12 col-md-4">
                            <form method="POST" action="{{ route('superadmin.opsiyon.ekle') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Kaç Saat Önce Uyarılsın?</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="saat_oncesi" class="form-control" min="1" max="168" placeholder="48" required>
                                        <span class="input-group-text">saat</span>
                                    </div>
                                    <div class="form-text">Ör: 48 → opsiyon dolmadan 2 gün önce</div>
                                </div>
                                <div class="mb-3 d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sms_aktif" value="1" id="sms_aktif" checked>
                                        <label class="form-check-label small" for="sms_aktif">SMS gönder</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="push_aktif" value="1" id="push_aktif" checked>
                                        <label class="form-check-label small" for="push_aktif">Push bildirim</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-warning fw-bold w-100">
                                    <i class="fas fa-plus me-1"></i> Ekle
                                </button>
                            </form>
                            <div class="alert alert-light border mt-3 p-2" style="font-size:0.78rem;">
                                <strong>Öneri:</strong> 48s + 24s + 4s + 1s kurallarını ekleyerek opsiyona yaklaştıkça birden fazla uyarı alabilirsiniz.
                            </div>
                        </div>

                        {{-- Mevcut kurallar --}}
                        <div class="col-12 col-md-8">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ne Zaman</th>
                                        <th>SMS</th>
                                        <th>Push</th>
                                        <th>Durum</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($opsiyonAyarlar as $oa)
                                    <tr class="{{ !$oa->is_active ? 'text-muted' : '' }}">
                                        <td>
                                            <span class="fw-bold">{{ $oa->saat_oncesi }} saat önce</span>
                                            @if($oa->saat_oncesi >= 48)
                                                <span class="badge bg-light text-dark border ms-1">{{ round($oa->saat_oncesi/24) }} gün</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($oa->sms_aktif) <i class="fas fa-check text-success"></i>
                                            @else <i class="fas fa-times text-muted"></i> @endif
                                        </td>
                                        <td>
                                            @if($oa->push_aktif) <i class="fas fa-check text-success"></i>
                                            @else <i class="fas fa-times text-muted"></i> @endif
                                        </td>
                                        <td>
                                            @if($oa->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-secondary">Pasif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <form method="POST" action="{{ route('superadmin.opsiyon.toggle', $oa) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $oa->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                            title="{{ $oa->is_active ? 'Pasif yap' : 'Aktif yap' }}">
                                                        <i class="fas {{ $oa->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('superadmin.opsiyon.sil', $oa) }}"
                                                      onsubmit="return confirm('Bu uyarıyı silmek istediğinize emin misiniz?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Henüz opsiyon uyarısı eklenmemiş.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- SMS KURAL DÜZENLEME MODAL --}}
<div class="modal fade" id="smsEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="smsEditForm">
                @csrf @method('PATCH')
                <div class="modal-header" style="background:#1a1a2e;">
                    <h5 class="modal-title text-white"><i class="fas fa-edit me-2" style="color:#e94560;"></i>SMS Kuralı Düzenle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kişi / Etiket</label>
                        <input type="text" name="label" id="se_label" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Telefon Numarası</label>
                        <input type="text" name="phone" id="se_phone" class="form-control form-control-sm" required>
                        <div class="form-text">Virgülle ayırarak birden fazla numara girebilirsiniz</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Olay</label>
                        <select name="event" id="se_event" class="form-select form-select-sm" required>
                            @foreach($events as $event)
                            <option value="{{ $event }}">{{ \App\Models\SmsNotificationSetting::eventLabel($event) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-sm text-white fw-bold" style="background:#e94560;">
                        <i class="fas fa-save me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const smsEditModal = new bootstrap.Modal(document.getElementById('smsEditModal'));
const smsEditForm  = document.getElementById('smsEditForm');

function smsDuzenle(id, label, phone, event) {
    smsEditForm.action = '/superadmin/sms-ayarlari/' + id;
    document.getElementById('se_label').value = label || '';
    document.getElementById('se_phone').value = phone || '';
    document.getElementById('se_event').value = event || '';
    smsEditModal.show();
}
</script>
</body>
</html>

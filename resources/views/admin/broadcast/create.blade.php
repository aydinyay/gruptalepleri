<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('admin.partials.theme-styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Duyuru Gönder — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { font-family:'Segoe UI',sans-serif; }
        .channel-card { border:2px solid #dee2e6; border-radius:10px; padding:14px 18px; cursor:pointer; transition:all 0.15s; user-select:none; }
        .channel-card:hover { border-color:#adb5bd; background:#f8f9fa; }
        .channel-card.active-push  { border-color:#6366f1; background:#eef2ff; }
        .channel-card.active-sms   { border-color:#f59e0b; background:#fffbeb; }
        .channel-card.active-email { border-color:#10b981; background:#ecfdf5; }
        .channel-card .ch-icon { font-size:1.4rem; }
        .channel-card .ch-title { font-weight:700; font-size:0.92rem; }
        .channel-card .ch-desc  { font-size:0.75rem; color:#6c757d; }
        .ts-wrapper { --ts-pr-caret:0.75rem; }
        .section-label { font-weight:700; font-size:0.78rem; text-transform:uppercase; letter-spacing:.08em; color:#6c757d; margin-bottom:8px; }
    </style>
</head>
<body>

<x-navbar-admin active="broadcast" />

<div class="container py-4" style="max-width:760px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">📢 Duyuru Gönder</h4>
        <a href="{{ route('admin.broadcast.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-list me-1"></i>Gönderim Geçmişi
        </a>
    </div>

    @if(!auth()->user()->can_send_broadcast)
        <div class="alert alert-warning">
            <i class="fas fa-lock me-2"></i>
            Duyuru gönderme yetkiniz bulunmamaktadır. Süper admin ile iletişime geçin.
        </div>
    @else

    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.broadcast.store') }}">
        @csrf

        {{-- Emoji + Başlık --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-label">Başlık</div>
                <div class="input-group">
                    <input type="text" name="emoji" class="form-control text-center" style="max-width:70px;font-size:1.3rem;"
                           placeholder="📢" maxlength="8" value="{{ old('emoji') }}" id="emojiInput">
                    <input type="text" name="title" class="form-control"
                           placeholder="Duyuru başlığı" value="{{ old('title') }}" required id="titleInput">
                </div>
            </div>
        </div>

        {{-- Mesaj --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-label">Mesaj</div>
                <textarea name="message" class="form-control" rows="4" id="msgInput"
                          placeholder="Duyuru mesajınızı buraya yazın..." maxlength="1000" required>{{ old('message') }}</textarea>
                <div class="d-flex justify-content-between mt-1">
                    <span class="form-text">SMS gönderecekseniz 160 karakter limitine dikkat edin.</span>
                    <span class="form-text"><span id="charCount">0</span>/1000</span>
                </div>
            </div>
        </div>

        {{-- Kanallar --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-label">Gönderim Kanalları <span class="text-danger">*</span></div>
                <div class="d-flex flex-wrap gap-3">

                    <label class="channel-card flex-fill" id="card-push" for="ch_push">
                        <div class="d-flex align-items-start gap-3">
                            <div class="ch-icon">🔔</div>
                            <div>
                                <div class="ch-title">Push Bildirimi</div>
                                <div class="ch-desc">Uygulama içi bildirim zili — tüm kullanıcılar</div>
                            </div>
                            <div class="ms-auto">
                                <input class="form-check-input" type="checkbox" name="channels[]"
                                       value="push" id="ch_push"
                                       {{ in_array('push', old('channels', ['push'])) ? 'checked' : '' }}
                                       onchange="updateChannelCards()">
                            </div>
                        </div>
                    </label>

                    <label class="channel-card flex-fill" id="card-sms" for="ch_sms">
                        <div class="d-flex align-items-start gap-3">
                            <div class="ch-icon">💬</div>
                            <div>
                                <div class="ch-title">SMS</div>
                                <div class="ch-desc">Kayıtlı telefon numarasına SMS</div>
                            </div>
                            <div class="ms-auto">
                                <input class="form-check-input" type="checkbox" name="channels[]"
                                       value="sms" id="ch_sms"
                                       {{ in_array('sms', old('channels', [])) ? 'checked' : '' }}
                                       onchange="updateChannelCards()">
                            </div>
                        </div>
                    </label>

                    <label class="channel-card flex-fill" id="card-email" for="ch_email">
                        <div class="d-flex align-items-start gap-3">
                            <div class="ch-icon">📧</div>
                            <div>
                                <div class="ch-title">E-posta</div>
                                <div class="ch-desc">Kayıtlı e-posta adresine</div>
                            </div>
                            <div class="ms-auto">
                                <input class="form-check-input" type="checkbox" name="channels[]"
                                       value="email" id="ch_email"
                                       {{ in_array('email', old('channels', [])) ? 'checked' : '' }}
                                       onchange="updateChannelCards()">
                            </div>
                        </div>
                    </label>

                </div>
            </div>
        </div>

        {{-- Hedef Kitle --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-label">Hedef Kitle</div>
                <div class="d-flex flex-wrap gap-3 mt-1">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="t_all" value="all"
                               {{ old('target','all') === 'all' ? 'checked' : '' }} onchange="toggleSecili()">
                        <label class="form-check-label" for="t_all">
                            <i class="fas fa-users text-primary me-1"></i>Tüm Kullanıcılar
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="t_acenteler" value="acenteler"
                               {{ old('target') === 'acenteler' ? 'checked' : '' }} onchange="toggleSecili()">
                        <label class="form-check-label" for="t_acenteler">
                            <i class="fas fa-building text-warning me-1"></i>Sadece Acenteler
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="t_adminler" value="adminler"
                               {{ old('target') === 'adminler' ? 'checked' : '' }} onchange="toggleSecili()">
                        <label class="form-check-label" for="t_adminler">
                            <i class="fas fa-user-shield text-danger me-1"></i>Sadece Adminler
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" id="t_secili" value="secili"
                               {{ old('target') === 'secili' ? 'checked' : '' }} onchange="toggleSecili()">
                        <label class="form-check-label" for="t_secili">
                            <i class="fas fa-user-check text-success me-1"></i>Seçili Kullanıcılar
                        </label>
                    </div>
                </div>

                {{-- Tom Select kullanıcı arama --}}
                <div id="seciliKullanicilar" class="mt-3 d-none">
                    <div class="section-label mb-2">Kullanıcı Ara & Seç</div>
                    <select id="kullaniciSelect" name="target_user_ids[]" multiple placeholder="Ad, firma veya e-posta ile arayın...">
                        @php
                            $adminler  = $kullanicilar->whereIn('role', ['admin','superadmin'])->sortBy('name');
                            $acenteler = $kullanicilar->where('role', 'acente')->sortBy('name');
                        @endphp
                        @if($adminler->isNotEmpty())
                            <optgroup label="— ADMİNLER —">
                                @foreach($adminler as $u)
                                    <option value="{{ $u->id }}"
                                        data-search="{{ strtolower($u->name . ' ' . $u->email) }}"
                                        {{ in_array($u->id, old('target_user_ids', [])) ? 'selected' : '' }}>
                                        [{{ strtoupper($u->role) }}] {{ $u->name }} · {{ $u->email }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if($acenteler->isNotEmpty())
                            <optgroup label="— ACENTELER —">
                                @foreach($acenteler as $u)
                                    <option value="{{ $u->id }}"
                                        data-search="{{ strtolower($u->name . ' ' . $u->email . ' ' . ($u->agency?->company_title ?? '')) }}"
                                        {{ in_array($u->id, old('target_user_ids', [])) ? 'selected' : '' }}>
                                        {{ $u->name }}
                                        @if($u->agency?->company_title) · {{ $u->agency->company_title }}@endif
                                        · {{ $u->email }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <div class="form-text mt-1">
                        <i class="fas fa-info-circle me-1"></i>
                        Ad, firma adı veya e-posta ile arama yapabilirsiniz. Seçilen kullanıcılar yukarıda etiket olarak görünür.
                    </div>
                </div>
            </div>
        </div>

        {{-- Zamanlama --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="section-label">Gönderim Zamanı</div>
                <div class="d-flex gap-4 mt-1">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="zamanlama" id="z_hemen" value="hemen"
                               {{ old('zamanlama','hemen') === 'hemen' ? 'checked' : '' }} onchange="toggleZamanlama()">
                        <label class="form-check-label" for="z_hemen">
                            <i class="fas fa-bolt text-warning me-1"></i>Hemen Gönder
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="zamanlama" id="z_zamanla" value="zamanla"
                               {{ old('zamanlama') === 'zamanla' ? 'checked' : '' }} onchange="toggleZamanlama()">
                        <label class="form-check-label" for="z_zamanla">
                            <i class="fas fa-clock text-primary me-1"></i>Belirli Bir Zamanda
                        </label>
                    </div>
                </div>
                <div id="zamanlamaInput" class="mt-2 d-none">
                    <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm"
                           style="max-width:260px;" value="{{ old('scheduled_at') }}">
                </div>
            </div>
        </div>

        {{-- Önizleme --}}
        <div class="card mb-4 border-0" style="background:#1a1a2e;">
            <div class="card-body">
                <div class="small mb-2" style="color:rgba(255,255,255,0.4);"><i class="fas fa-eye me-1"></i>Önizleme</div>
                <div class="d-flex align-items-start gap-3">
                    <div style="font-size:1.8rem;flex-shrink:0;" id="previewEmoji">📢</div>
                    <div>
                        <div class="fw-bold text-white" id="previewTitle">Duyuru başlığı</div>
                        <div class="small mt-1" style="color:rgba(255,255,255,0.55);" id="previewMsg">Mesaj buraya gelecek...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger px-4">
                <i class="fas fa-paper-plane me-2"></i>Gönder
            </button>
            <a href="{{ route('admin.broadcast.index') }}" class="btn btn-outline-secondary">İptal</a>
        </div>
    </form>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('admin.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Tom Select ──────────────────────────────────────────────────────────
    let tomSelect = null;

    function initTomSelect() {
        if (tomSelect) return;
        try {
            tomSelect = new TomSelect('#kullaniciSelect', {
                plugins: ['remove_button'],
                placeholder: 'Ad, firma veya e-posta ile arayın...',
                searchField: ['text'],
                maxOptions: 500,
                render: {
                    option: function(data, escape) {
                        return '<div style="font-size:0.85rem;">' + escape(data.text) + '</div>';
                    },
                    item: function(data, escape) {
                        const parts = data.text.split('·');
                        return '<div>' + escape(parts[0].trim()) + '</div>';
                    }
                }
            });
        } catch(e) {
            console.error('TomSelect başlatma hatası:', e);
        }
    }

    // ── Hedef kitle toggle ──────────────────────────────────────────────────
    function toggleSecili() {
        const checked = document.querySelector('input[name="target"]:checked');
        const box = document.getElementById('seciliKullanicilar');
        if (!box) return;
        if (checked && checked.value === 'secili') {
            box.classList.remove('d-none');
            initTomSelect();
        } else {
            box.classList.add('d-none');
        }
    }

    // Hem onclick hem onchange dinle — daha güvenilir
    document.querySelectorAll('input[name="target"]').forEach(function(radio) {
        radio.addEventListener('change', toggleSecili);
        radio.addEventListener('click', toggleSecili);
    });

    // ── Zamanlama toggle ────────────────────────────────────────────────────
    function toggleZamanlama() {
        const checked = document.querySelector('input[name="zamanlama"]:checked');
        const el = document.getElementById('zamanlamaInput');
        if (!el) return;
        const isZamanla = checked && checked.value === 'zamanla';
        el.classList.toggle('d-none', !isZamanla);
        el.querySelector('input').disabled = !isZamanla;
    }

    document.querySelectorAll('input[name="zamanlama"]').forEach(function(radio) {
        radio.addEventListener('change', toggleZamanlama);
        radio.addEventListener('click', toggleZamanlama);
    });

    // ── Kanal kartları ──────────────────────────────────────────────────────
    function updateChannelCards() {
        ['push', 'sms', 'email'].forEach(function(ch) {
            const cb   = document.getElementById('ch_' + ch);
            const card = document.getElementById('card-' + ch);
            if (cb && card) card.classList.toggle('active-' + ch, cb.checked);
        });
    }

    document.querySelectorAll('input[name="channels[]"]').forEach(function(cb) {
        cb.addEventListener('change', updateChannelCards);
    });

    // ── Önizleme ────────────────────────────────────────────────────────────
    const emojiInput   = document.getElementById('emojiInput');
    const titleInput   = document.getElementById('titleInput');
    const msgInput     = document.getElementById('msgInput');
    const previewEmoji = document.getElementById('previewEmoji');
    const previewTitle = document.getElementById('previewTitle');
    const previewMsg   = document.getElementById('previewMsg');
    const charCount    = document.getElementById('charCount');

    function syncPreview() {
        previewEmoji.textContent = emojiInput.value.trim() || '📢';
        previewTitle.textContent = titleInput.value.trim() || 'Duyuru başlığı';
        previewMsg.textContent   = msgInput.value.trim()   || 'Mesaj buraya gelecek...';
        charCount.textContent    = msgInput.value.length;
    }

    emojiInput.addEventListener('input', syncPreview);
    titleInput.addEventListener('input', syncPreview);
    msgInput.addEventListener('input', syncPreview);

    // ── İlk yükleme ─────────────────────────────────────────────────────────
    updateChannelCards();
    toggleSecili();
    toggleZamanlama();
    syncPreview(); // old() değerlerini preview'a yansıt
});
</script>
</body>
</html>

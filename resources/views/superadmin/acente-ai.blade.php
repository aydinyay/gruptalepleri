<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Acente Asistanı — GrupTalepleri</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
*{box-sizing:border-box}
html{height:100%}
body{min-height:100%;margin:0;background:#f0f2f5;font-family:'Segoe UI',sans-serif;display:flex;flex-direction:column}

.main-layout{flex:1;display:flex;overflow:hidden;min-height:0}

/* Sidebar */
.sidebar{width:260px;flex-shrink:0;background:#fff;border-right:1px solid #e9ecef;overflow-y:auto;padding:14px 10px}
.sidebar h6{font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;color:#6c757d;margin:10px 0 6px;padding:0 4px}
.prompt-chip{display:block;width:100%;text-align:left;background:#f8f9fa;border:1px solid #e9ecef;border-radius:7px;padding:7px 10px;font-size:.78rem;color:#343a40;cursor:pointer;margin-bottom:5px;transition:all .15s;line-height:1.4}
.prompt-chip:hover{background:#e8f0fe;border-color:#0d6efd55;color:#0d6efd}
.prompt-chip i{color:#6c757d;margin-right:4px;font-size:.72rem;width:14px}

/* Chat */
.chat-area{flex:1;display:flex;flex-direction:column;overflow:hidden}
.messages{flex:1;overflow-y:auto;padding:18px 22px;display:flex;flex-direction:column;gap:14px}

.msg{max-width:860px}
.msg-user{align-self:flex-end}
.msg-ai{align-self:flex-start;width:100%}

.bubble-user{background:#0d6efd;color:#fff;border-radius:16px 16px 4px 16px;padding:9px 15px;font-size:.88rem;max-width:560px;word-break:break-word}
.bubble-ai{background:#fff;border:1px solid #e9ecef;border-radius:4px 16px 16px 16px;padding:13px 16px;font-size:.86rem;color:#212529;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.bubble-ai h1,.bubble-ai h2,.bubble-ai h3{font-size:.95rem;font-weight:700;margin:10px 0 4px;color:#1a1a2e}
.bubble-ai ul,.bubble-ai ol{padding-left:18px;margin:5px 0}
.bubble-ai li{margin-bottom:2px}
.bubble-ai strong{color:#1a1a2e}
.bubble-ai p{margin-bottom:5px}
.bubble-ai code{background:#f0f2f5;padding:1px 5px;border-radius:3px;font-size:.8rem}

.msg-meta{font-size:.68rem;color:#adb5bd;margin-top:3px}
.msg-user .msg-meta{text-align:right}

/* Onay kartı */
.action-card{background:#fff;border:1.5px solid #0d6efd33;border-radius:10px;padding:14px 16px;margin-top:8px;box-shadow:0 2px 8px rgba(13,110,253,.08)}
.action-card.sms .action-card-header{color:#198754}
.action-card.sms{border-color:#19875433}
.action-card-header{font-size:.82rem;font-weight:700;color:#0d6efd;margin-bottom:10px;display:flex;align-items:center;gap:7px}
.action-card-body{font-size:.82rem;color:#495057}
.action-card-body .target-list{margin:8px 0;max-height:120px;overflow-y:auto}
.action-card-body .target-item{padding:4px 8px;background:#f8f9fa;border-radius:5px;margin-bottom:3px;font-size:.78rem}
.sms-editor{width:100%;border:1px solid #dee2e6;border-radius:7px;padding:8px 10px;font-size:.82rem;resize:vertical;min-height:80px;margin:8px 0;font-family:inherit}
.sms-editor:focus{outline:none;border-color:#198754;box-shadow:0 0 0 3px #19875415}
.char-count{font-size:.75rem;font-weight:600;text-align:right;margin-top:3px;padding:3px 6px;border-radius:4px;background:#f8f9fa}
.char-count.ok{color:#198754;background:#d1e7dd}
.char-count.warn{color:#856404;background:#fff3cd}
.char-count.over{color:#842029;background:#f8d7da}
.action-buttons{display:flex;gap:8px;margin-top:10px}
.btn-onayla{background:#0d6efd;color:#fff;border:none;border-radius:7px;padding:7px 18px;font-size:.82rem;cursor:pointer;transition:background .15s}
.btn-onayla:hover{background:#0b5ed7}
.btn-onayla.sms{background:#198754}
.btn-onayla.sms:hover{background:#157347}
.btn-iptal{background:#f8f9fa;color:#6c757d;border:1px solid #dee2e6;border-radius:7px;padding:7px 14px;font-size:.82rem;cursor:pointer}
.btn-iptal:hover{background:#e9ecef}
.zamanlama-input{border:1px solid #dee2e6;border-radius:6px;padding:5px 9px;font-size:.8rem;margin-right:6px}

/* Seçim kartı */
.secim-card{background:#fff;border:1.5px solid #fd7e1433;border-radius:10px;padding:14px 16px;margin-top:8px}
.secim-card-header{font-size:.82rem;font-weight:700;color:#fd7e14;margin-bottom:8px}
.secim-item{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#f8f9fa;border-radius:7px;margin-bottom:5px;cursor:pointer;transition:background .15s}
.secim-item:hover{background:#fff3e0}
.secim-item .si-name{font-size:.82rem;font-weight:600;color:#343a40}
.secim-item .si-detail{font-size:.75rem;color:#6c757d}
.btn-sec{background:#fd7e14;color:#fff;border:none;border-radius:5px;padding:3px 10px;font-size:.75rem;cursor:pointer}

/* Uyarı badge */
.onceki-uyari{background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:5px 10px;font-size:.76rem;color:#856404;margin-bottom:8px}

/* Yazıyor */
.typing-indicator{display:flex;gap:4px;align-items:center;padding:6px 4px}
.typing-dot{width:6px;height:6px;border-radius:50%;background:#adb5bd;animation:bounce .9s infinite}
.typing-dot:nth-child(2){animation-delay:.15s}
.typing-dot:nth-child(3){animation-delay:.3s}
@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}

/* Input */
.input-bar{flex-shrink:0;background:#fff;border-top:1px solid #e9ecef;padding:12px 18px}
.input-wrap{display:flex;gap:9px;align-items:flex-end;max-width:900px}
.input-wrap textarea{flex:1;resize:none;border:1.5px solid #dee2e6;border-radius:11px;padding:9px 13px;font-size:.88rem;font-family:inherit;transition:border-color .15s;outline:none;overflow-y:hidden;max-height:130px}
.input-wrap textarea:focus{border-color:#0d6efd;box-shadow:0 0 0 3px #0d6efd12}
.send-btn{background:#0d6efd;color:#fff;border:none;border-radius:11px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:background .15s}
.send-btn:hover{background:#0b5ed7}
.send-btn:disabled{background:#6c757d;cursor:not-allowed}

.welcome-msg{text-align:center;color:#6c757d;padding:40px 20px}
.welcome-msg .icon-wrap{width:56px;height:56px;background:linear-gradient(135deg,#0d6efd22,#6f42c122);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.4rem;color:#0d6efd}
.welcome-msg h5{font-weight:700;color:#343a40;margin-bottom:5px;font-size:1rem}

@media(max-width:640px){.sidebar{display:none}}
</style>
</head>
<body>

<x-navbar-superadmin active="acente-ai" />

<div class="main-layout">

    {{-- Sidebar --}}
    <div class="sidebar">
        <h6 id="sidebar-q-label"><i class="fas fa-search me-1"></i>Sorgulama</h6>
        <div id="sidebar-sorgulama"></div>
        <h6 class="mt-2" id="sidebar-a-label"><i class="fas fa-paper-plane me-1"></i>Email & SMS</h6>
        <div id="sidebar-eylem"></div>
    </div>

    {{-- Chat --}}
    <div class="chat-area">
        <div class="messages" id="messages">
            <div class="welcome-msg" id="welcome">
                <div class="icon-wrap"><i class="fas fa-robot"></i></div>
                <h5>AI Acente Asistanı</h5>
                <p class="small">Acente veritabanını sorgula, email & SMS gönder.<br>Tüm sorular için onay alınır.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <span class="badge bg-light text-dark border"><i class="fas fa-database text-primary me-1"></i>36K acente</span>
                    <span class="badge bg-light text-dark border"><i class="fas fa-envelope text-danger me-1"></i>Email</span>
                    <span class="badge bg-light text-dark border"><i class="fas fa-sms text-success me-1"></i>SMS</span>
                    <span class="badge bg-light text-dark border"><i class="fas fa-shield-alt text-warning me-1"></i>Onay sistemi</span>
                </div>
            </div>
        </div>
        <div class="input-bar">
            <div class="input-wrap">
                <textarea id="soruInput" rows="1" placeholder="Soru sor veya işlem yap... (Enter = gönder, Shift+Enter = satır)" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
                <button class="send-btn" id="sendBtn" onclick="sendMessage()" title="Gönder">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken  = '{{ csrf_token() }}';
const askUrl     = '{{ route("superadmin.acente.ai.ask") }}';
const emailUrl   = '{{ route("superadmin.acente.ai.email") }}';
const smsUrl     = '{{ route("superadmin.acente.ai.sms") }}';
let isLoading    = false;
let gecmis       = [];       // Konuşma geçmişi
let aktifEylem   = null;     // Bekleyen eylem

// ── Sidebar dinamik havuz ───────────────────────────────────────────────────
const SORGULAR = [
    // Belge / kimlik
    {i:'fas fa-id-card',      t:'12572 belge nosu kime ait?'},
    {i:'fas fa-id-card',      t:'Benim belge numaram nedir?'},
    {i:'fas fa-search',       t:'Hilal Tur\'un belge nosu nedir?'},
    {i:'fas fa-fingerprint',  t:'Group Ticket kaçıncı sırada kurulmuş?'},
    {i:'fas fa-hashtag',      t:'15000 ile 15010 arasındaki belge nolarına sahip acenteler hangileri?'},
    // İl / ilçe / bölge
    {i:'fas fa-map-marker-alt', t:'Van\'da kaç acente var?'},
    {i:'fas fa-map-marker-alt', t:'Ankara\'da kaç acente var?'},
    {i:'fas fa-layer-group',    t:'Ege bölgesinde kaç acente var?'},
    {i:'fas fa-layer-group',    t:'Karadeniz bölgesindeki acenteleri listele'},
    {i:'fas fa-layer-group',    t:'Marmara ve Ege\'yi karşılaştır'},
    {i:'fas fa-building',       t:'İzmir Konak Alsancak\'ta kaç acente var?'},
    {i:'fas fa-building',       t:'Antalya Muratpaşa\'da kaç acente var?'},
    {i:'fas fa-city',           t:'En fazla acente hangi ilçede?'},
    {i:'fas fa-map',            t:'Hangi ilde hiç acente yok?'},
    {i:'fas fa-chart-bar',      t:'İstanbul\'daki acentelerin ilçe dağılımı'},
    // Sıralama / istatistik
    {i:'fas fa-sort-numeric-down', t:'En eski acente hangisi?'},
    {i:'fas fa-sort-numeric-up',   t:'En son kurulan acente hangisi?'},
    {i:'fas fa-sort-numeric-up',   t:'Antalya\'da en son kurulan acente hangisi?'},
    {i:'fas fa-code-branch',       t:'En çok şubesi olan acente hangisi?'},
    {i:'fas fa-code-branch',       t:'5\'ten fazla şubesi olan acenteler hangileri?'},
    {i:'fas fa-trophy',            t:'İstanbul\'da en çok şubeli ilk 5 acente'},
    {i:'fas fa-star',              t:'A grubu kaç acente var, B ve C grubu kaç?'},
    {i:'fas fa-percent',           t:'Kaç acentenin e-postası var, kaçının yok?'},
    {i:'fas fa-mobile-alt',        t:'Kaç acentenin GSM numarası var?'},
    {i:'fas fa-times-circle',      t:'İptal edilmiş kaç acente var?'},
    // Kişisel / arama
    {i:'fas fa-user-check',     t:'Group Ticket Turizm üyemiz mi?'},
    {i:'fas fa-info-circle',    t:'Group Ticket hakkında ne biliyoruz?'},
    {i:'fas fa-store',          t:'Benim acentemle aynı ilçedeki diğer acenteler hangileri?'},
    {i:'fas fa-phone',          t:'0212 ile başlayan telefonu olan kaç acente var?'},
    {i:'fas fa-envelope',       t:'Gmail adresi olan kaç acente var?'},
    {i:'fas fa-globe',          t:'Web sitesi olan kaç acente var?'},
    {i:'fas fa-database',       t:'Bakanlık kaynaklı ile TÜRSAB kaynaklı arasındaki fark ne?'},
    {i:'fas fa-clock',          t:'Bu ay sisteme kaç yeni acente eklendi?'},
];

const EYLEMLER = [
    {i:'fas fa-envelope',       t:'Bana tanıtım emaili gönder'},
    {i:'fas fa-sms',            t:'Bana tanıtım SMS\'i yolla'},
    {i:'fas fa-sms',            t:'Bana bayram tebriği SMS\'i hazırla'},
    {i:'fas fa-clock',          t:'Yarın sabah 9\'da bana motivasyon SMS\'i gönder'},
    {i:'fas fa-paper-plane',    t:'Üyemiz olmayan İzmir acentelerine tanıtım emaili gönder'},
    {i:'fas fa-broadcast-tower',t:'Antalya\'daki GSM\'li acentelere tanıtım SMS\'i gönder'},
    {i:'fas fa-history',        t:'Bu ay kaç acenteye email gönderdik?'},
    {i:'fas fa-history',        t:'Bu ay kaç acenteye SMS gönderdik?'},
    {i:'fas fa-redo',           t:'Daha önce email gönderdiğimiz acenteler hangileri?'},
    {i:'fas fa-ban',            t:'Hiç email göndermediğimiz İstanbul acenteleri kaç tane?'},
];

function shuffle(arr) {
    return arr.slice().sort(() => Math.random() - 0.5);
}

function buildSidebar() {
    const sq = shuffle(SORGULAR).slice(0, 9);
    const se = shuffle(EYLEMLER).slice(0, 5);
    document.getElementById('sidebar-sorgulama').innerHTML =
        sq.map(x => `<button class="prompt-chip" onclick="chipClick(this)"><i class="${x.i}"></i>${x.t}</button>`).join('');
    document.getElementById('sidebar-eylem').innerHTML =
        se.map(x => `<button class="prompt-chip" onclick="chipClick(this)"><i class="${x.i}"></i>${x.t}</button>`).join('');
}

function chipClick(btn) {
    const input = document.getElementById('soruInput');
    input.value = btn.textContent.trim();
    autoResize(input);
    input.focus();
}

// ── Yardımcılar ────────────────────────────────────────────────────────────
function setPrompt(btn) {
    const input = document.getElementById('soruInput');
    input.value = btn.textContent.trim();
    autoResize(input);
    input.focus();
}
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 130) + 'px';
}
function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}
function nowTime() {
    return new Date().toLocaleTimeString('tr', {hour:'2-digit', minute:'2-digit'});
}
function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}
function scrollBottom() {
    const msgs = document.getElementById('messages');
    msgs.scrollTop = msgs.scrollHeight;
}

// ── Mesaj ekle ──────────────────────────────────────────────────────────────
function appendMessage(role, content, eylem) {
    const welcome = document.getElementById('welcome');
    if (welcome) welcome.remove();

    const msgs = document.getElementById('messages');
    const div  = document.createElement('div');
    div.className = `msg msg-${role}`;

    if (role === 'user') {
        div.innerHTML = `
            <div class="bubble-user">${escapeHtml(content)}</div>
            <div class="msg-meta">${nowTime()}</div>`;
    } else if (role === 'typing') {
        div.id = 'typing';
        div.innerHTML = `<div class="bubble-ai"><div class="typing-indicator">
            <div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>
            <span class="ms-2 text-muted small">Sorgulanıyor...</span>
        </div></div>`;
    } else {
        let eylemHtml = '';
        if (eylem) {
            eylemHtml = buildEylemCard(eylem);
        }
        div.innerHTML = `
            <div class="bubble-ai">${marked.parse(content)}</div>
            ${eylemHtml}
            <div class="msg-meta"><i class="fas fa-robot me-1"></i>TURAi · ${nowTime()}</div>`;
    }

    msgs.appendChild(div);
    scrollBottom();
    return div;
}

// ── Eylem kartı HTML'i ──────────────────────────────────────────────────────
function buildEylemCard(eylem) {
    if (!eylem || !eylem.tip) return '';

    // Seçim kartı
    if (eylem.tip === 'secim') {
        const opts = (eylem.secenekler || []).map(s => `
            <div class="secim-item" onclick="secimYap(${JSON.stringify(s).replace(/"/g,'&quot;')})">
                <div>
                    <div class="si-name">${escapeHtml(s.unvan || '')}</div>
                    <div class="si-detail">Belge No: ${s.belge_no} &bull; ${escapeHtml(s.il_ilce || s.il || '')}</div>
                </div>
                <button class="btn-sec">Seç</button>
            </div>`).join('');
        return `<div class="secim-card">
            <div class="secim-card-header"><i class="fas fa-question-circle me-2"></i>Hangisini kastediyorsunuz?</div>
            ${opts}
        </div>`;
    }

    // Email kartı
    if (eylem.tip === 'email_gonder') {
        const hedefSayisi = eylem.hedef_sayisi || (eylem.hedefler || []).length;
        const hedefListesi = (eylem.hedefler || []).slice(0, 8).map(h =>
            `<div class="target-item"><i class="fas fa-envelope me-1 text-muted"></i>${escapeHtml(h.unvan||'')} — ${escapeHtml(h.eposta||'')}</div>`
        ).join('');
        const uyari = eylem.onceki_uyari
            ? `<div class="onceki-uyari"><i class="fas fa-exclamation-triangle me-1"></i>${escapeHtml(eylem.onceki_uyari)}</div>` : '';
        const zamanlamaInput = `<input type="datetime-local" class="zamanlama-input" id="emailZamanlama" title="Boş bırakırsanız hemen gönderilir">`;

        aktifEylem = eylem;
        return `<div class="action-card" id="actionCard">
            <div class="action-card-header"><i class="fas fa-envelope"></i> Email Onayı — ${hedefSayisi} acente</div>
            <div class="action-card-body">
                ${uyari}
                <div class="target-list">${hedefListesi}${hedefSayisi > 8 ? `<div class="target-item text-muted">...ve ${hedefSayisi - 8} daha</div>` : ''}</div>
                <div class="mt-2 text-muted" style="font-size:.76rem"><i class="fas fa-info-circle me-1"></i>Zamanlama (opsiyonel): ${zamanlamaInput}</div>
            </div>
            <div class="action-buttons">
                <button class="btn-onayla" onclick="emailOnayla()"><i class="fas fa-check me-1"></i>Gönder</button>
                <button class="btn-iptal" onclick="eylemIptal()">İptal</button>
            </div>
        </div>`;
    }

    // SMS kartı
    if (eylem.tip === 'sms_gonder') {
        const hedefSayisi = eylem.hedef_sayisi || (eylem.hedefler || []).length;
        const hedefListesi = (eylem.hedefler || []).slice(0, 5).map(h =>
            `<div class="target-item"><i class="fas fa-mobile-alt me-1 text-muted"></i>${escapeHtml(h.unvan||'')} — ${escapeHtml(h.telefon||'')}</div>`
        ).join('');
        const uyari = eylem.onceki_uyari
            ? `<div class="onceki-uyari"><i class="fas fa-exclamation-triangle me-1"></i>${escapeHtml(eylem.onceki_uyari)}</div>` : '';
        const icerik = eylem.icerik || '';
        const charCount = icerik.length;
        const zamanlamaInput = `<input type="datetime-local" class="zamanlama-input" id="smsZamanlama" title="Boş bırakırsanız hemen gönderilir">`;

        aktifEylem = eylem;
        return `<div class="action-card sms" id="actionCard">
            <div class="action-card-header"><i class="fas fa-sms"></i> SMS Onayı — ${hedefSayisi} acente</div>
            <div class="action-card-body">
                ${uyari}
                <div class="target-list">${hedefListesi}${hedefSayisi > 5 ? `<div class="target-item text-muted">...ve ${hedefSayisi - 5} daha</div>` : ''}</div>
                <div class="mt-2" style="font-size:.76rem;color:#6c757d">SMS İçeriği (düzenleyebilirsiniz):</div>
                <textarea class="sms-editor" id="smsIcerik" oninput="charSay(this)">${icerik.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</textarea>
                <div class="char-count ${charCount > 160 ? 'over' : charCount > 130 ? 'warn' : 'ok'}" id="charCount">${charCount} / 160 karakter${charCount > 160 ? ' — çok uzun!' : ''}</div>
                <div class="mt-1 text-muted" style="font-size:.76rem"><i class="fas fa-info-circle me-1"></i>Zamanlama (opsiyonel): ${zamanlamaInput}</div>
            </div>
            <div class="action-buttons">
                <button class="btn-onayla sms" onclick="smsOnayla()"><i class="fas fa-paper-plane me-1"></i>Gönder</button>
                <button class="btn-iptal" onclick="eylemIptal()">İptal</button>
            </div>
        </div>`;
    }

    return '';
}

function charSay(el) {
    const n   = el.value.length;
    const cc  = document.getElementById('charCount');
    if (!cc) return;
    cc.className = 'char-count ' + (n > 160 ? 'over' : n > 130 ? 'warn' : 'ok');
    cc.textContent = `${n} / 160 karakter${n > 160 ? ' — çok uzun!' : n > 130 ? ' — uzuyor' : ''}`;
}

// ── Seçim yapıldığında ──────────────────────────────────────────────────────
function secimYap(secim) {
    const input = document.getElementById('soruInput');
    input.value = `${secim.unvan} (belge no ${secim.belge_no}) hakkında devam et`;
    autoResize(input);
    input.focus();
}

// ── Eylem: Email onayla ─────────────────────────────────────────────────────
async function emailOnayla() {
    if (!aktifEylem) return;
    const zamanlama = document.getElementById('emailZamanlama')?.value || null;
    setActionLoading(true);
    try {
        const res = await fetch(emailUrl, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},
            body: JSON.stringify({
                hedefler:  aktifEylem.hedefler || [],
                hedef_sql: aktifEylem.hedef_sql || null,
                zamanlama
            })
        });
        const data = await res.json();
        eylemIptal();
        if (data.hata) {
            appendMessage('ai', `> **Hata:** ${data.hata}`, null);
        } else {
            gecmis.push({rol:'ai', icerik: data.yanit});
            appendMessage('ai', data.yanit, null);
        }
    } catch(e) {
        eylemIptal();
        appendMessage('ai', '> **Bağlantı hatası.**', null);
    }
}

// ── Eylem: SMS onayla ───────────────────────────────────────────────────────
async function smsOnayla() {
    if (!aktifEylem) return;
    const icerik    = document.getElementById('smsIcerik')?.value?.trim() || aktifEylem.icerik || '';
    const zamanlama = document.getElementById('smsZamanlama')?.value || null;
    if (!icerik) { alert('SMS içeriği boş olamaz.'); return; }
    setActionLoading(true);
    try {
        const res = await fetch(smsUrl, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},
            body: JSON.stringify({
                hedefler:  aktifEylem.hedefler || [],
                hedef_sql: aktifEylem.hedef_sql || null,
                icerik,
                zamanlama
            })
        });
        const data = await res.json();
        eylemIptal();
        if (data.hata) {
            appendMessage('ai', `> **Hata:** ${data.hata}`, null);
        } else {
            gecmis.push({rol:'ai', icerik: data.yanit});
            appendMessage('ai', data.yanit, null);
        }
    } catch(e) {
        eylemIptal();
        appendMessage('ai', '> **Bağlantı hatası.**', null);
    }
}

function setActionLoading(loading) {
    const card = document.getElementById('actionCard');
    if (!card) return;
    const btns = card.querySelectorAll('button');
    btns.forEach(b => b.disabled = loading);
    if (loading) btns[0].innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Gönderiliyor...';
}

function eylemIptal() {
    aktifEylem = null;
    const card = document.getElementById('actionCard');
    if (card) card.remove();
}

// ── Ana sohbet gönder ───────────────────────────────────────────────────────
async function sendMessage() {
    if (isLoading) return;
    const input = document.getElementById('soruInput');
    const soru  = input.value.trim();
    if (!soru) return;

    buildSidebar();  // her gönderimde yenile

    isLoading = true;
    document.getElementById('sendBtn').disabled = true;
    input.value = '';
    autoResize(input);
    aktifEylem = null;

    appendMessage('user', soru, null);
    const typingEl = appendMessage('typing', '', null);

    try {
        const res = await fetch(askUrl, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},
            body: JSON.stringify({soru, gecmis: gecmis.slice(-6)})
        });
        const data = await res.json();
        typingEl.remove();

        if (data.hata) {
            appendMessage('ai', `> **Hata:** ${data.hata}`, null);
        } else {
            gecmis.push({rol:'kullanici', icerik: soru});
            gecmis.push({rol:'ai', icerik: data.yanit || ''});
            appendMessage('ai', data.yanit || '', data.eylem || null);
        }
    } catch(e) {
        typingEl.remove();
        appendMessage('ai', '> **Bağlantı hatası.** Lütfen tekrar deneyin.', null);
    } finally {
        isLoading = false;
        document.getElementById('sendBtn').disabled = false;
        input.focus();
    }
}

document.addEventListener('DOMContentLoaded', buildSidebar);
</script>
</body>
</html>

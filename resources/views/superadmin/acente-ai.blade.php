<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Acente Analisti — GrupTalepleri</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
* { box-sizing: border-box; }
html, body { height: 100%; margin: 0; }
body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; flex-direction: column; }

.page-header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; padding: 16px 28px; flex-shrink: 0; }
.page-header h1 { font-size: 1.35rem; font-weight: 700; margin: 0; }
.page-header p  { margin: 3px 0 0; color: rgba(255,255,255,.55); font-size: .82rem; }

.main-layout { flex: 1; display: flex; overflow: hidden; }

/* Sidebar: örnek sorular */
.sidebar { width: 280px; flex-shrink: 0; background: #fff; border-right: 1px solid #e9ecef; overflow-y: auto; padding: 16px 12px; }
.sidebar h6 { font-size: .75rem; text-transform: uppercase; letter-spacing: .07em; color: #6c757d; margin-bottom: 10px; padding: 0 4px; }
.prompt-chip { display: block; width: 100%; text-align: left; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 8px 11px; font-size: .8rem; color: #343a40; cursor: pointer; margin-bottom: 6px; transition: all .15s; line-height: 1.4; }
.prompt-chip:hover { background: #e8f0fe; border-color: #0d6efd55; color: #0d6efd; }
.prompt-chip i { color: #6c757d; margin-right: 5px; font-size: .75rem; }

/* Chat alanı */
.chat-area { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.messages { flex: 1; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 16px; }

.msg { max-width: 820px; }
.msg-user { align-self: flex-end; }
.msg-ai   { align-self: flex-start; }

.bubble-user { background: #0d6efd; color: #fff; border-radius: 18px 18px 4px 18px; padding: 10px 16px; font-size: .9rem; max-width: 560px; word-break: break-word; }
.bubble-ai   { background: #fff; border: 1px solid #e9ecef; border-radius: 4px 18px 18px 18px; padding: 14px 18px; font-size: .875rem; color: #212529; box-shadow: 0 1px 4px rgba(0,0,0,.06); width: 100%; }
.bubble-ai h1,.bubble-ai h2,.bubble-ai h3 { font-size: 1rem; font-weight: 700; margin-top: 12px; margin-bottom: 5px; color: #1a1a2e; }
.bubble-ai h2 { font-size: .95rem; }
.bubble-ai h3 { font-size: .9rem; color: #0d6efd; }
.bubble-ai ul,.bubble-ai ol { padding-left: 20px; margin: 6px 0; }
.bubble-ai li { margin-bottom: 3px; }
.bubble-ai strong { color: #1a1a2e; }
.bubble-ai p { margin-bottom: 6px; }
.bubble-ai hr { border-color: #f0f2f5; margin: 10px 0; }
.bubble-ai blockquote { border-left: 3px solid #0d6efd; margin: 8px 0; padding: 4px 12px; background: #f0f7ff; border-radius: 0 6px 6px 0; font-size: .85rem; }
.bubble-ai code { background: #f0f2f5; padding: 1px 5px; border-radius: 3px; font-size: .82rem; }

.msg-meta { font-size: .7rem; color: #adb5bd; margin-top: 4px; }
.msg-user .msg-meta { text-align: right; }

.typing-indicator { display: flex; gap: 4px; align-items: center; padding: 8px 4px; }
.typing-dot { width: 7px; height: 7px; border-radius: 50%; background: #adb5bd; animation: bounce .9s infinite; }
.typing-dot:nth-child(2) { animation-delay: .15s; }
.typing-dot:nth-child(3) { animation-delay: .3s; }
@keyframes bounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

/* Input */
.input-bar { flex-shrink: 0; background: #fff; border-top: 1px solid #e9ecef; padding: 14px 20px; }
.input-wrap { display: flex; gap: 10px; align-items: flex-end; max-width: 900px; }
.input-wrap textarea { flex: 1; resize: none; border: 1.5px solid #dee2e6; border-radius: 12px; padding: 10px 14px; font-size: .9rem; font-family: inherit; transition: border-color .15s; outline: none; overflow-y: hidden; max-height: 140px; }
.input-wrap textarea:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px #0d6efd15; }
.send-btn { background: #0d6efd; color: #fff; border: none; border-radius: 12px; width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: background .15s; }
.send-btn:hover { background: #0b5ed7; }
.send-btn:disabled { background: #6c757d; cursor: not-allowed; }

.welcome-msg { text-align: center; color: #6c757d; padding: 40px 20px; }
.welcome-msg .icon-wrap { width: 60px; height: 60px; background: linear-gradient(135deg,#0d6efd22,#6f42c122); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; font-size: 1.5rem; color: #0d6efd; }
.welcome-msg h5 { font-weight: 700; color: #343a40; margin-bottom: 6px; }

@media(max-width:640px) { .sidebar { display: none; } }
</style>
</head>
<body>

<div class="page-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h1><i class="fas fa-robot me-2" style="color:#0d6efd;"></i>AI Acente Analisti</h1>
            <p>Veritabanındaki gerçek verilerle Gemini destekli analiz — {{ now()->format('d.m.Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('superadmin.acenteler.istatistik') }}" class="btn btn-sm btn-outline-light">
                <i class="fas fa-chart-bar me-1"></i>İstatistikler
            </a>
            <a href="{{ route('superadmin.tursab.kampanya') }}" class="btn btn-sm btn-outline-light">
                <i class="fas fa-arrow-left me-1"></i>Kampanya
            </a>
        </div>
    </div>
</div>

<div class="main-layout">

    {{-- Sidebar --}}
    <div class="sidebar">
        <h6><i class="fas fa-lightbulb me-1"></i>Örnek Sorular</h6>

        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-map-marker-alt"></i>Hangi bölge en az gelişmiş? Fırsatlar neler?
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-chart-line"></i>İstanbul'un hakimiyeti normal mi? Diğer ülkelerle kıyasla.
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-code-branch"></i>En çok şubeli acenteleri analiz et. Bu büyüklük ne anlama gelir?
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-envelope"></i>E-posta eksikliği ne kadar büyük bir sorun? Kayıp potansiyel nedir?
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-layer-group"></i>A grubu ile diğer gruplar arasındaki fark ne? Oranlar makul mü?
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-city"></i>Antalya ile İstanbul'u karşılaştır. Turizm türleri açısından yorum yap.
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-search-location"></i>En az acenteli illerde neden az acente var? Sebepler ve öneriler.
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-globe"></i>Türkiye'deki acente yoğunluğu Avrupa ile kıyaslandığında ne söylenebilir?
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-store"></i>Şube yapısı Türkiye turizm sektörü için ne ifade ediyor?
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-dollar-sign"></i>Bu veri setine göre hangi şehirlere yatırım yapılmalı?
        </button>

        <h6 class="mt-3"><i class="fas fa-database me-1"></i>Veri Hakkında</h6>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-question-circle"></i>Veritabanını özetle. Kaç acente var, nereden geliyor?
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-exclamation-triangle"></i>Veri kalitesindeki en büyük eksiklikler neler?
        </button>
        <button class="prompt-chip" onclick="setPrompt(this)">
            <i class="fas fa-star"></i>Bu veritabanının en dikkat çekici 5 istatistiği nedir?
        </button>
    </div>

    {{-- Chat --}}
    <div class="chat-area">
        <div class="messages" id="messages">
            <div class="welcome-msg" id="welcome">
                <div class="icon-wrap"><i class="fas fa-robot"></i></div>
                <h5>AI Acente Analisti</h5>
                <p class="small">Türkiye seyahat acentesi veritabanı hakkında istediğin her soruyu sor.<br>Gerçek verilerle desteklenmiş içgörüler alacaksın.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <span class="badge bg-light text-dark border"><i class="fas fa-check-circle text-success me-1"></i>Gerçek DB verileri</span>
                    <span class="badge bg-light text-dark border"><i class="fas fa-robot text-primary me-1"></i>Gemini 2.5 Flash</span>
                    <span class="badge bg-light text-dark border"><i class="fas fa-language text-warning me-1"></i>Türkçe analiz</span>
                </div>
            </div>
        </div>
        <div class="input-bar">
            <div class="input-wrap">
                <textarea id="soruInput" rows="1" placeholder="Soru sor... (örn: 'En az gelişmiş bölge hangisi?')" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
                <button class="send-btn" id="sendBtn" onclick="sendMessage()" title="Gönder">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="text-muted mt-2" style="font-size:.72rem;max-width:900px;">
                <i class="fas fa-info-circle me-1"></i>Her yanıt, güncel veritabanı verilerini bağlam olarak Gemini'ye iletir. <kbd>Enter</kbd> gönder, <kbd>Shift+Enter</kbd> satır sonu.
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken = '{{ csrf_token() }}';
const askUrl    = '{{ route("superadmin.acente.ai.ask") }}';
let isLoading   = false;

function setPrompt(btn) {
    const input = document.getElementById('soruInput');
    input.value = btn.textContent.trim();
    autoResize(input);
    input.focus();
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 140) + 'px';
}

function handleKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

function now() {
    return new Date().toLocaleTimeString('tr', {hour:'2-digit',minute:'2-digit'});
}

function appendMessage(role, content) {
    const welcome = document.getElementById('welcome');
    if (welcome) welcome.remove();

    const msgs = document.getElementById('messages');
    const div  = document.createElement('div');
    div.className = `msg msg-${role}`;

    if (role === 'user') {
        div.innerHTML = `
            <div class="bubble-user">${escapeHtml(content)}</div>
            <div class="msg-meta">${now()}</div>`;
    } else if (role === 'typing') {
        div.id = 'typing';
        div.innerHTML = `<div class="bubble-ai"><div class="typing-indicator">
            <div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>
            <span class="ms-2 text-muted small">Gemini düşünüyor...</span>
        </div></div>`;
    } else {
        div.innerHTML = `
            <div class="bubble-ai">${marked.parse(content)}</div>
            <div class="msg-meta"><i class="fas fa-robot me-1"></i>Gemini 2.5 Flash · ${now()}</div>`;
    }

    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    return div;
}

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}

async function sendMessage() {
    if (isLoading) return;
    const input = document.getElementById('soruInput');
    const soru  = input.value.trim();
    if (!soru) return;

    isLoading = true;
    document.getElementById('sendBtn').disabled = true;
    input.value = '';
    autoResize(input);

    appendMessage('user', soru);
    const typingEl = appendMessage('typing', '');

    try {
        const res = await fetch(askUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ soru })
        });
        const data = await res.json();
        typingEl.remove();

        if (data.hata) {
            appendMessage('ai', `> **Hata:** ${data.hata}`);
        } else {
            appendMessage('ai', data.yanit);
        }
    } catch (err) {
        typingEl.remove();
        appendMessage('ai', '> **Bağlantı hatası.** Lütfen tekrar deneyin.');
    } finally {
        isLoading = false;
        document.getElementById('sendBtn').disabled = false;
        input.focus();
    }
}
</script>
</body>
</html>

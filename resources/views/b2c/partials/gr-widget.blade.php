{{-- GR (Ciar) — AI Asistan Widget --}}
<style>
#gr-fab {
    position: fixed; bottom: 28px; right: 28px; z-index: 9999;
    width: 56px; height: 56px; border-radius: 50%;
    background: linear-gradient(135deg, #f4a418 0%, #e8890a 100%);
    border: none; cursor: pointer; box-shadow: 0 4px 18px rgba(244,164,24,.45);
    display: flex; align-items: center; justify-content: center;
    transition: transform .2s, box-shadow .2s;
}
#gr-fab:hover { transform: scale(1.08); box-shadow: 0 6px 24px rgba(244,164,24,.6); }
#gr-fab .gr-label {
    font-size: .7rem; font-weight: 800; color: #fff; letter-spacing: .04em;
    line-height: 1; text-align: center; pointer-events: none;
}
#gr-badge {
    position: absolute; top: -4px; right: -4px;
    width: 18px; height: 18px; border-radius: 50%;
    background: #e53e3e; border: 2px solid #fff;
    font-size: .62rem; color: #fff; font-weight: 700;
    display: none; align-items: center; justify-content: center;
}

#gr-panel {
    position: fixed; bottom: 96px; right: 24px; z-index: 9998;
    width: 360px; max-width: calc(100vw - 32px);
    background: #fff; border-radius: 18px;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    display: flex; flex-direction: column; overflow: hidden;
    transform: scale(.92) translateY(16px); opacity: 0;
    pointer-events: none; transition: transform .22s ease, opacity .22s ease;
}
#gr-panel.open { transform: scale(1) translateY(0); opacity: 1; pointer-events: all; }

#gr-header {
    background: linear-gradient(135deg, #0f2444 0%, #1a3c6b 100%);
    padding: 14px 18px; display: flex; align-items: center; gap: 10px;
}
#gr-header .gr-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #f4a418, #e8890a);
    display: flex; align-items: center; justify-content: center;
    font-size: .72rem; font-weight: 800; color: #fff; flex-shrink: 0;
}
#gr-header .gr-info { flex: 1; }
#gr-header .gr-name { font-size: .9rem; font-weight: 700; color: #fff; }
#gr-header .gr-status { font-size: .72rem; color: rgba(255,255,255,.6); }
#gr-header .gr-close {
    background: none; border: none; cursor: pointer; color: rgba(255,255,255,.7);
    font-size: 1.2rem; padding: 0 4px; line-height: 1;
}

#gr-messages {
    flex: 1; overflow-y: auto; padding: 16px 14px;
    min-height: 200px; max-height: 340px;
    display: flex; flex-direction: column; gap: 10px;
    scroll-behavior: smooth;
}
#gr-messages::-webkit-scrollbar { width: 4px; }
#gr-messages::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

.gr-msg { display: flex; flex-direction: column; gap: 6px; max-width: 90%; }
.gr-msg.user { align-self: flex-end; align-items: flex-end; }
.gr-msg.assistant { align-self: flex-start; align-items: flex-start; }

.gr-msg .gr-bubble {
    padding: 9px 13px; border-radius: 14px; font-size: .83rem; line-height: 1.45;
}
.gr-msg.assistant .gr-bubble {
    background: #f1f5f9; color: #1a202c; border-bottom-left-radius: 4px;
    border: 1px solid #e2e8f0;
}
.gr-msg.user .gr-bubble {
    background: linear-gradient(135deg, #f4a418, #e8890a);
    color: #fff; border-bottom-right-radius: 4px;
}

.gr-typing { display: flex; gap: 4px; padding: 10px 14px; align-items: center; }
.gr-typing span {
    width: 7px; height: 7px; border-radius: 50%; background: #94a3b8;
    animation: grDot 1.1s ease-in-out infinite;
}
.gr-typing span:nth-child(2) { animation-delay: .18s; }
.gr-typing span:nth-child(3) { animation-delay: .36s; }
@@keyframes grDot { 0%,80%,100%{transform:scale(.7);opacity:.4} 40%{transform:scale(1);opacity:1} }

.gr-product-cards {
    display: flex; flex-direction: column; gap: 6px; margin-top: 6px;
}
.gr-product-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 8px 10px; display: flex; gap: 8px; align-items: center;
    text-decoration: none; transition: border-color .15s;
}
.gr-product-card:hover { border-color: #f4a418; }
.gr-product-card .gr-card-img {
    width: 42px; height: 42px; border-radius: 7px;
    object-fit: cover; flex-shrink: 0; background: #e2e8f0;
}
.gr-product-card .gr-card-info { flex: 1; min-width: 0; }
.gr-product-card .gr-card-title {
    font-size: .77rem; font-weight: 600; color: #1a202c;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.gr-product-card .gr-card-sub { font-size: .7rem; color: #64748b; }
.gr-product-card .gr-card-price { font-size: .77rem; font-weight: 700; color: #f4a418; white-space: nowrap; }

#gr-footer {
    padding: 10px 12px; border-top: 1px solid #e2e8f0;
    display: flex; gap: 8px; align-items: flex-end;
}
#gr-input {
    flex: 1; border: 1px solid #e2e8f0; border-radius: 22px;
    padding: 9px 14px; font-size: .84rem; resize: none; outline: none;
    font-family: inherit; line-height: 1.4; max-height: 88px; overflow-y: auto;
    transition: border-color .15s;
}
#gr-input:focus { border-color: #f4a418; }
#gr-send {
    width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #f4a418, #e8890a);
    border: none; cursor: pointer; color: #fff;
    display: flex; align-items: center; justify-content: center;
    transition: transform .15s; font-size: .95rem;
}
#gr-send:hover { transform: scale(1.1); }
#gr-send:disabled { opacity: .45; cursor: default; transform: none; }

.gr-clear-btn {
    background: none; border: none; font-size: .68rem; color: #94a3b8;
    cursor: pointer; padding: 2px 6px; border-radius: 4px;
    transition: color .15s;
}
.gr-clear-btn:hover { color: #e53e3e; }
</style>

{{-- FAB Butonu --}}
<button id="gr-fab" aria-label="GR AI Asistan">
    <span class="gr-label">GR</span>
    <span id="gr-badge"></span>
</button>

{{-- Chat Paneli --}}
<div id="gr-panel" role="dialog" aria-label="GR AI Asistan">
    <div id="gr-header">
        <div class="gr-avatar">GR</div>
        <div class="gr-info">
            <div class="gr-name">GR · Asistanın</div>
            <div class="gr-status">● Çevrimiçi</div>
        </div>
        <button class="gr-clear-btn" id="gr-clear-btn" title="Geçmişi temizle">🗑</button>
        <button class="gr-close" id="gr-close" aria-label="Kapat">×</button>
    </div>

    <div id="gr-messages">
        {{-- Karşılama mesajı --}}
        <div class="gr-msg assistant">
            <div class="gr-bubble">
                @if(auth('b2c')->check())
                    Merhaba {{ explode(' ', auth('b2c')->user()->name)[0] }}! 👋 Ne arıyorsun?
                @else
                    Merhaba! 👋 Ben GR, seyahat asistanınım. Ne aramak istersin?
                @endif
            </div>
        </div>
    </div>

    <div id="gr-footer">
        <textarea id="gr-input" placeholder="Bir şey sor…" rows="1"></textarea>
        <button id="gr-send" aria-label="Gönder">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
            </svg>
        </button>
    </div>
</div>

<script>
(function () {
    var fab    = document.getElementById('gr-fab');
    var panel  = document.getElementById('gr-panel');
    var close  = document.getElementById('gr-close');
    var msgs   = document.getElementById('gr-messages');
    var input  = document.getElementById('gr-input');
    var send   = document.getElementById('gr-send');
    var badge  = document.getElementById('gr-badge');
    var clearBtn = document.getElementById('gr-clear-btn');

    var isOpen    = false;
    var isWaiting = false;
    var unread    = 0;

    // Sayfa değişiminden etkilenmemek için mesajlar localStorage'da tutulur
    var LS_KEY = 'gr_chat_v1';
    function loadHistory() {
        try { return JSON.parse(localStorage.getItem(LS_KEY) || '[]'); } catch(e) { return []; }
    }
    function saveToHistory(role, html, products) {
        var hist = loadHistory();
        hist.push({ role: role, html: html, products: products || [] });
        if (hist.length > 60) hist = hist.slice(-60); // max 60 mesaj
        try { localStorage.setItem(LS_KEY, JSON.stringify(hist)); } catch(e) {}
    }
    function clearHistory() {
        try { localStorage.removeItem(LS_KEY); } catch(e) {}
    }

    // ── Aç / kapat ───────────────────────────────────────────────────────────
    function togglePanel() {
        isOpen = !isOpen;
        panel.classList.toggle('open', isOpen);
        if (isOpen) {
            unread = 0;
            badge.style.display = 'none';
            input.focus();
            scrollBottom();
        }
    }
    fab.addEventListener('click', togglePanel);
    close.addEventListener('click', function (e) { e.stopPropagation(); togglePanel(); });

    // ── Mesaj ekle ───────────────────────────────────────────────────────────
    function addMessage(role, html, products, skipSave) {
        var div  = document.createElement('div');
        div.className = 'gr-msg ' + role;
        var bubble = document.createElement('div');
        bubble.className = 'gr-bubble';
        bubble.innerHTML = role === 'assistant' ? renderMarkdown(html) : escapeHtml(html);
        div.appendChild(bubble);
        if (! skipSave) saveToHistory(role, html, products);

        // Ürün kartları
        if (products && products.length) {
            var cards = document.createElement('div');
            cards.className = 'gr-product-cards';
            products.forEach(function (p) {
                var a = document.createElement('a');
                a.href      = '/urun/' + p.slug;
                a.className = 'gr-product-card';
                a.innerHTML =
                    '<img class="gr-card-img" src="' + (p.cover_image || '') + '" onerror="this.style.display=\'none\'">' +
                    '<div class="gr-card-info">' +
                        '<div class="gr-card-title">' + escapeHtml(p.title) + '</div>' +
                        '<div class="gr-card-sub">' + escapeHtml(p.destination_city || '') + '</div>' +
                    '</div>' +
                    '<div class="gr-card-price">' + (p.base_price ? p.base_price + ' ' + (p.currency || '') : '') + '</div>';
                cards.appendChild(a);
            });
            div.appendChild(cards);
        }

        msgs.appendChild(div);
        scrollBottom();

        if (! isOpen && role === 'assistant') {
            unread++;
            badge.textContent   = unread;
            badge.style.display = 'flex';
        }
    }

    function addTyping() {
        var div = document.createElement('div');
        div.className = 'gr-msg assistant';
        div.id = 'gr-typing';
        div.innerHTML = '<div class="gr-bubble gr-typing"><span></span><span></span><span></span></div>';
        msgs.appendChild(div);
        scrollBottom();
    }

    function removeTyping() {
        var t = document.getElementById('gr-typing');
        if (t) t.remove();
    }

    function scrollBottom() {
        msgs.scrollTop = msgs.scrollHeight;
    }

    // ── Gönder ───────────────────────────────────────────────────────────────
    function doSend() {
        var text = input.value.trim();
        if (! text || isWaiting) return;

        addMessage('user', text);
        input.value = '';
        input.style.height = 'auto';
        isWaiting = true;
        send.disabled = true;

        addTyping();

        fetch('/api/b2c/gr-chat', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept':        'application/json',
            },
            body: JSON.stringify({ message: text }),
        })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            removeTyping();
            if (data && data.reply) {
                addMessage('assistant', data.reply, data.products || []);
                if (data.redirect) {
                    setTimeout(function () {
                        window.location.href = data.redirect;
                    }, 1200);
                }
            } else {
                addMessage('assistant', 'Şu an cevap üretemiyorum, birazdan tekrar dene.');
            }
        })
        .catch(function () {
            removeTyping();
            addMessage('assistant', 'Bağlantı hatası oluştu.');
        })
        .finally(function () {
            isWaiting     = false;
            send.disabled = false;
            input.focus();
        });
    }

    send.addEventListener('click', doSend);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && ! e.shiftKey) { e.preventDefault(); doSend(); }
    });

    // ── Textarea auto-resize ─────────────────────────────────────────────────
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 88) + 'px';
    });

    // ── Geçmişi temizle ──────────────────────────────────────────────────────
    clearBtn.addEventListener('click', function () {
        if (! confirm('Sohbet geçmişini temizleyeyim mi?')) return;
        fetch('/api/b2c/gr-clear', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept':       'application/json',
            },
        }).then(function () {
            clearHistory();
            msgs.innerHTML = '';
            addMessage('assistant', 'Geçmiş temizlendi. Yeniden başlayalım! 😊', [], true);
        });
    });

    // Sayfa yüklenince localStorage'daki mesajları yükle
    (function restoreHistory() {
        var hist = loadHistory();
        if (! hist.length) return;
        // Karşılama mesajını kaldır, geçmişi yükle
        msgs.innerHTML = '';
        hist.forEach(function (m) {
            addMessage(m.role, m.html, m.products || [], true); // skipSave=true
        });
    })();

    // ── Yardımcılar ──────────────────────────────────────────────────────────
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderMarkdown(text) {
        return escapeHtml(text)
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }
})();
</script>

{{--
    TURAi Widget Partial
    Değişkenler:
      $turaiGtpnr          — string|null  (talep sayfasında gtpnr, dashboard'da null)
      $turaiEndpoint        — string       (POST URL)
      $turaiAcilEndpoint    — string|null
      $turaiSelfSmsEndpoint — string|null
      $turaiAdminPhones     — array        (admin telefon listesi)
      $turaiWaLink          — string       (WhatsApp linki)
      $turaiAcenteAdi       — string       (acente adı)
      $turaiUserId          — int
      $turaiSubtitle        — string       (header altı metin)
      $turaiOzetler         — array        (acil özetler HTML)
      $turaiChips           — array|null   (ek chip tanımları — opsiyonel)
--}}
@php
    $saat = now('Europe/Istanbul')->hour;
    if      ($saat >= 6  && $saat < 12) { $selamEmoji = '☀️';  $selamMetin = 'Günaydın'; }
    elseif  ($saat >= 12 && $saat < 15) { $selamEmoji = '👋';  $selamMetin = 'Merhaba'; }
    elseif  ($saat >= 15 && $saat < 18) { $selamEmoji = '🌤️'; $selamMetin = 'Tünaydın'; }
    elseif  ($saat >= 18 && $saat < 22) { $selamEmoji = '🌆'; $selamMetin = 'İyi akşamlar'; }
    else                                { $selamEmoji = '🌙'; $selamMetin = 'İyi geceler'; }

    $turaiOzetler = $turaiOzetler ?? [];
    $ozetHtml = !empty($turaiOzetler)
        ? '<div style="margin-top:5px;font-size:0.72rem;">' . implode('<br>', $turaiOzetler) . '</div>'
        : '';
@endphp

<style>
/* ── Widget genel ── */
#turai-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Segoe UI', sans-serif;
}
#turai-fab {
    width: 58px; height: 58px;
    background: linear-gradient(135deg, #1a1a2e 0%, #e94560 100%);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 24px rgba(233,69,96,0.45);
    border: none;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}
#turai-fab:hover { transform: scale(1.08); box-shadow: 0 6px 32px rgba(233,69,96,0.6); }
#turai-fab i { color: #fff; font-size: 1.3rem; transition: all 0.2s; }
#turai-fab .turai-badge {
    position: absolute; top: -4px; right: -4px;
    background: #28a745; color: #fff;
    width: 16px; height: 16px;
    border-radius: 50%; font-size: 0.55rem;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; border: 2px solid #fff;
    animation: turai-pulse 2s infinite;
}
@keyframes turai-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(40,167,69,0.4); }
    50%       { box-shadow: 0 0 0 6px rgba(40,167,69,0); }
}
/* ── Karşılama baloncuğu ── */
#turai-hello {
    position: absolute;
    bottom: 70px; right: 0;
    background: #e8ff00;
    border-radius: 14px 14px 4px 14px;
    padding: 10px 14px;
    font-size: 0.8rem;
    line-height: 1.5;
    color: #1a1a2e;
    width: 230px;
    box-shadow: 0 4px 24px rgba(232,255,0,0.5), 0 2px 8px rgba(0,0,0,0.15);
    animation: turaiHelloPop 0.35s cubic-bezier(.34,1.56,.64,1) forwards;
    cursor: pointer;
    z-index: 10001;
}
#turai-hello strong { color: #1a1a2e; }
#turai-hello::after {
    content: '';
    position: absolute;
    bottom: -8px; right: 18px;
    width: 14px; height: 8px;
    background: #e8ff00;
    clip-path: polygon(0 0, 100% 0, 50% 100%);
}
#turai-hello-close {
    position: absolute; top: 5px; right: 8px;
    background: none; border: none;
    color: rgba(26,26,46,0.5); font-size: 0.75rem;
    cursor: pointer; line-height: 1;
}
@keyframes turaiHelloPop {
    from { opacity: 0; transform: scale(0.85) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
/* ── Panel ── */
#turai-panel {
    position: absolute;
    bottom: 70px; right: 0;
    width: 380px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 80px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.06);
    display: none;
    flex-direction: column;
    overflow: hidden;
    max-height: 600px;
    animation: turai-slide-in 0.25s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes turai-slide-in {
    from { opacity:0; transform: translateY(16px) scale(0.96); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}
@media(max-width:480px) {
    #turai-panel { width: calc(100vw - 32px); right: 0; bottom: 70px; }
}
/* ── Header ── */
#turai-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 14px 16px;
    display: flex; align-items: center; gap: 10px;
    flex-shrink: 0;
}
.turai-avatar {
    width: 38px; height: 38px;
    background: rgba(233,69,96,0.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.turai-avatar i { color: #e94560; font-size: 1rem; }
#turai-header-info { flex: 1; min-width: 0; }
#turai-header-info .name { color: #fff; font-weight: 700; font-size: 0.9rem; }
#turai-header-info .sub {
    color: rgba(255,255,255,0.5); font-size: 0.7rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#turai-header-info .sub .gtpnr { color: #e94560; font-weight: 600; }
#turai-close {
    background: rgba(255,255,255,0.1); border: none; color: #fff;
    width: 28px; height: 28px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 0.8rem; flex-shrink: 0;
    transition: background 0.15s;
}
#turai-close:hover { background: rgba(233,69,96,0.4); }
/* ── Chips ── */
#turai-chips {
    padding: 10px 12px 0;
    display: flex; flex-wrap: wrap; gap: 6px;
    flex-shrink: 0;
}
.turai-chip {
    background: #f0f2f5; border: 1.5px solid #e0e3e8;
    border-radius: 999px; padding: 4px 10px;
    font-size: 0.72rem; font-weight: 600; color: #1a1a2e;
    cursor: pointer; transition: all 0.15s; white-space: nowrap;
}
.turai-chip:hover { background: #1a1a2e; color: #fff; border-color: #1a1a2e; }
.turai-chip-acil { border-color: #e94560 !important; color: #e94560 !important; }
.turai-chip-acil:hover { background: #e94560 !important; color: #fff !important; }
/* ── Mesajlar ── */
#turai-messages {
    flex: 1; overflow-y: auto; overflow-x: hidden;
    padding: 12px 14px;
    display: flex; flex-direction: column; gap: 10px;
    min-height: 200px; scroll-behavior: smooth;
}
#turai-messages::-webkit-scrollbar { width: 4px; }
#turai-messages::-webkit-scrollbar-thumb { background: #e0e3e8; border-radius: 4px; }
.turai-msg { display: flex; gap: 8px; max-width: 90%; min-width: 0; }
.turai-msg.ai   { align-self: flex-start; }
.turai-msg.user { align-self: flex-end; flex-direction: row-reverse; }
.turai-msg .bubble {
    padding: 9px 13px; border-radius: 16px;
    font-size: 0.82rem; line-height: 1.55;
    word-break: break-word; overflow-wrap: anywhere;
    min-width: 0; max-width: 100%; overflow: hidden;
}
.turai-msg.ai   .bubble { background: #f0f2f5; color: #1a1a2e; border-bottom-left-radius: 4px; }
.turai-msg.user .bubble { background: linear-gradient(135deg,#1a1a2e,#0f3460); color:#fff; border-bottom-right-radius:4px; }
.turai-msg .bubble strong { font-weight: 700; }
.turai-msg .bubble ul { margin: 4px 0 0 16px; padding: 0; }
.turai-msg .bubble li { margin-bottom: 2px; }
.turai-msg .bubble a { color: #e94560; }
.turai-ai-icon {
    width: 26px; height: 26px;
    background: rgba(233,69,96,0.1);
    border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    margin-top: 2px;
}
.turai-ai-icon i { color: #e94560; font-size: 0.65rem; }
.turai-typing { display: flex; gap: 4px; padding: 4px 2px; }
.turai-typing span {
    width: 7px; height: 7px;
    background: #adb5bd; border-radius: 50%;
    animation: turai-bounce 1.2s infinite;
}
.turai-typing span:nth-child(2) { animation-delay: 0.2s; }
.turai-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes turai-bounce {
    0%,60%,100% { transform: translateY(0); }
    30%          { transform: translateY(-6px); }
}
/* ── Input ── */
#turai-footer { padding: 10px 12px 12px; border-top: 1px solid #f0f2f5; flex-shrink: 0; }
#turai-input-wrap {
    display: flex; align-items: flex-end; gap: 8px;
    background: #f7f8fa; border: 1.5px solid #e0e3e8;
    border-radius: 14px; padding: 8px 10px;
    transition: border-color 0.15s;
}
#turai-input-wrap:focus-within { border-color: #1a1a2e; }
#turai-input {
    flex: 1; border: none; background: transparent;
    resize: none; outline: none;
    font-size: 0.84rem; line-height: 1.4;
    max-height: 90px; overflow-y: auto;
    font-family: inherit; color: #1a1a2e;
}
#turai-input::placeholder { color: #adb5bd; }
#turai-send {
    width: 34px; height: 34px;
    background: linear-gradient(135deg,#e94560,#c73652);
    border: none; border-radius: 10px;
    color: #fff; cursor: pointer; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; transition: all 0.15s;
}
#turai-send:hover:not(:disabled) { transform: scale(1.05); }
#turai-send:disabled { opacity: 0.5; cursor: not-allowed; }
#turai-hint { font-size: 0.68rem; color: #adb5bd; text-align: center; margin-top: 5px; }
</style>

<div id="turai-widget">
    <button id="turai-fab" onclick="turaiToggle()" title="TURAi ile sohbet et">
        <i class="fas fa-robot" id="turai-fab-icon"></i>
        <span class="turai-badge">AI</span>
    </button>

    <div id="turai-hello" style="display:none;" onclick="turaiHelloKapat()">
        <button id="turai-hello-close" onclick="event.stopPropagation();turaiHelloKapat()" title="Kapat">✕</button>
        <span id="turai-hello-text"></span>
    </div>

    <div id="turai-panel">
        <div id="turai-header">
            <div class="turai-avatar"><i class="fas fa-robot"></i></div>
            <div id="turai-header-info">
                <div class="name">TURAi Asistan</div>
                <div class="sub" id="turai-subtitle">{{ $turaiSubtitle ?? 'Genel Panel' }}</div>
            </div>
            <button id="turai-close" onclick="turaiToggle()"><i class="fas fa-times"></i></button>
        </div>

        <div id="turai-chips">
            <span class="turai-chip" onclick="turaiSend('💳 Havale için IBAN ve banka bilgileri lazım.')">💳 Havale/IBAN</span>
            <span class="turai-chip" onclick="turaiSend('📋 Tüm taleplerimde durum nedir?')">📋 Taleplerim</span>
            <span class="turai-chip" onclick="turaiSend('💰 Bekleyen ödemelerim var mı?')">💰 Ödemeler</span>
            @if(!empty($turaiGtpnr))
            <span class="turai-chip" onclick="turaiSend('💳 {{ $turaiGtpnr }} ödeme vademi ve kalan borcumu söyle.')">💳 Ödeme vadesi</span>
            <span class="turai-chip" onclick="turaiSend('✈️ {{ $turaiGtpnr }} rotasındaki destinasyon hakkında bilgi ver, gezilecek yerler, ulaşım.')">✈️ Destinasyon</span>
            @if(!empty($turaiSelfSmsEndpoint))
            <span class="turai-chip" onclick="turaiSelfSmsGonder(this)" id="turai-self-sms-btn" style="border-color:#198754;color:#198754;" title="Talep bilgilerini kendi telefonunuza SMS olarak gönderin">📱 Bana SMS at</span>
            @endif
            @endif
            <span class="turai-chip turai-chip-acil" onclick="turaiAcilGoster()" id="turai-acil-btn">🆘 Acil</span>
        </div>

        <div id="turai-messages">
            {{-- Mesajlar JS tarafından localStorage'dan veya ilk açılışta yüklenir --}}
        </div>

        <div id="turai-footer">
            <div id="turai-input-wrap">
                <textarea id="turai-input" rows="1"
                          placeholder="Soru sorun..."
                          onkeydown="turaiKeydown(event)"
                          oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"></textarea>
                <button id="turai-send" onclick="turaiSendClick()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div id="turai-hint">Enter ile gönder &nbsp;·&nbsp; Shift+Enter yeni satır</div>
        </div>
    </div>
</div>

<script>
(function () {
    // ── Sabitler ──────────────────────────────────────────────────────────────
    const GTPNR               = @json($turaiGtpnr ?? null);
    const CSRF                = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ENDPOINT            = GTPNR ? '/acente/talep/' + GTPNR + '/turai' : '/acente/turai/dashboard';
    const ACIL_ENDPOINT       = @json($turaiAcilEndpoint ?? null);
    const SELF_SMS_ENDPOINT   = @json($turaiSelfSmsEndpoint ?? null);
    const ADMIN_PHONES        = @json($turaiAdminPhones ?? []);
    const WA_LINK             = @json($turaiWaLink ?? '');
    const ACENTE_ADI          = @json($turaiAcenteAdi ?? '');
    const USER_ID             = @json($turaiUserId ?? 0);
    const OZET_HTML           = @json($ozetHtml ?? '');

    // ── localStorage: günlük persist ─────────────────────────────────────────
    const bugun = new Date().toISOString().slice(0, 10);
    const LS_KEY = 'turai_acente_' + USER_ID + '_' + bugun;

    function lsYukle() {
        try {
            const raw = localStorage.getItem(LS_KEY);
            if (!raw) return { mesajlar: [], gecmis: [] };
            return JSON.parse(raw);
        } catch(e) { return { mesajlar: [], gecmis: [] }; }
    }

    function lsKaydet(mesajlar, gecmis) {
        try {
            // Eski günlerin kayıtlarını temizle
            Object.keys(localStorage)
                .filter(k => k.startsWith('turai_acente_' + USER_ID + '_') && k !== LS_KEY)
                .forEach(k => localStorage.removeItem(k));
            localStorage.setItem(LS_KEY, JSON.stringify({ mesajlar, gecmis }));
        } catch(e) {}
    }

    // ── Durum ─────────────────────────────────────────────────────────────────
    let panelAcik  = false;
    let yukleniyor = false;
    let gecmis     = [];
    let kayitliMesajlar = []; // { rol, icerik, rawHtml } dizisi

    // ── Başlangıç: localStorage'dan yükle ────────────────────────────────────
    const ls = lsYukle();
    gecmis = ls.gecmis || [];
    kayitliMesajlar = ls.mesajlar || [];

    // ── Aç/kapat ──────────────────────────────────────────────────────────────
    window.turaiToggle = function () {
        panelAcik = !panelAcik;
        const panel = document.getElementById('turai-panel');
        const icon  = document.getElementById('turai-fab-icon');
        if (panelAcik) {
            panel.style.display = 'flex';
            icon.className = 'fas fa-times';
            turaiHelloKapat();
            // Eğer hiç mesaj yoksa ilk açılış mesajını göster
            const container = document.getElementById('turai-messages');
            if (container.children.length === 0) {
                turaiIlkAcilis();
            }
            turaiScrollBottom();
            document.getElementById('turai-input').focus();
        } else {
            panel.style.display = 'none';
            icon.className = 'fas fa-robot';
        }
    };

    // ── İlk açılış: localStorage'dan yükle veya hoş geldin mesajı ────────────
    function turaiIlkAcilis() {
        if (kayitliMesajlar.length > 0) {
            // Geçmiş mesajları yeniden render et
            kayitliMesajlar.forEach(m => {
                turaiMesajEkleDOM(m.rol, m.icerik, false, m.rawHtml || false);
            });
        } else {
            // İlk açılış — hoş geldin mesajı
            const hosGeldinHtml = turaiHosGeldinOlustur();
            turaiMesajEkleDOM('ai', hosGeldinHtml, false, true);
            kayitliMesajlar.push({ rol: 'ai', icerik: hosGeldinHtml, rawHtml: true });
            lsKaydet(kayitliMesajlar, gecmis);
        }
    }

    function turaiHosGeldinOlustur(havaDurumu) {
        const saat = new Date();
        const saatStr = saat.getHours().toString().padStart(2,'0') + ':' + saat.getMinutes().toString().padStart(2,'0');
        const hava = havaDurumu ? ` 🌡️ ${havaDurumu}` : '';
        const konum = turaiCity ? `, ${turaiCity}` : '';
        let html = `<strong>Merhaba${ACENTE_ADI ? ', ' + ACENTE_ADI : ''}!</strong> 👋<br>`;
        html += `Ben <strong>TURAi</strong>, GrupTalepleri.com'un size özel AI asistanıyım.<br>`;
        if (konum || hava) {
            html += `<span style="font-size:0.78rem;color:#888;">Saat ${saatStr}${konum}${hava}</span><br>`;
        }
        if (OZET_HTML) {
            html += `<div style="margin-top:6px;padding:6px 8px;background:#fff8e1;border-radius:8px;font-size:0.78rem;">${OZET_HTML}</div>`;
        }
        html += `<br>Ödeme, havale, talep durumu — her şeyi sorabilirsiniz.`;
        return html;
    }

    // ── Karşılama baloncuğu ───────────────────────────────────────────────────
    let turaiCity = '';
    let turaiCityWeather = '';

    window.turaiHelloKapat = function () {
        const el = document.getElementById('turai-hello');
        if (el) el.style.display = 'none';
    };

    function turaiHelloBalonGoster() {
        const el = document.getElementById('turai-hello');
        if (!el || panelAcik) return;

        const saat = new Date().getHours();
        let selam;
        if      (saat >= 6  && saat < 12) selam = '☀️ Günaydın';
        else if (saat >= 12 && saat < 15) selam = '👋 Merhaba';
        else if (saat >= 15 && saat < 18) selam = '🌤️ Tünaydın';
        else if (saat >= 18 && saat < 22) selam = '🌆 İyi akşamlar';
        else                               selam = '🌙 İyi geceler';

        let metin = `<strong>${selam}${ACENTE_ADI ? ', ' + ACENTE_ADI : ''}!</strong><br>`;
        if (turaiCity) metin += `<span style="font-size:0.75rem;">${turaiCity}${turaiCityWeather}</span><br>`;
        metin += `Ben <strong>TURAi</strong>, yapay zeka asistanınızım.`;
        if (OZET_HTML) metin += OZET_HTML;
        metin += `<br><span style="font-size:0.72rem;opacity:0.7;">Konuşmak için tıklayın →</span>`;

        document.getElementById('turai-hello-text').innerHTML = metin;
        el.style.display = 'block';
        setTimeout(turaiHelloKapat, 12000);
    }

    // Baloncuğu 2 saniye sonra göster (GPS sonucu gelmeden önce)
    setTimeout(turaiHelloBalonGoster, 2000);

    // ── GPS + Hava Durumu ─────────────────────────────────────────────────────
    function turaiGpsBaslat() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                // Nominatim ile şehir adı çek
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&accept-language=tr`, {
                    headers: { 'User-Agent': 'GrupTalepleri/1.0' }
                })
                .then(r => r.json())
                .then(data => {
                    const city = data.address?.city
                              || data.address?.town
                              || data.address?.county
                              || data.address?.state
                              || '';
                    if (!city) return;
                    turaiCity = city;
                    // wttr.in ile hava durumu çek
                    return fetch(`https://wttr.in/${encodeURIComponent(city)}?format=j1`)
                        .then(r => r.json())
                        .then(weather => {
                            const temp = weather?.current_condition?.[0]?.temp_C;
                            const descEn = weather?.current_condition?.[0]?.weatherDesc?.[0]?.value || '';
                            const havaTr = turaiHavaTercume(descEn);
                            const hava   = temp !== undefined ? `${temp}°C ${havaTr}` : havaTr;
                            turaiCityWeather = hava ? ` · ${hava}` : '';
                            // Baloncuğu güncelle (henüz kapatılmadıysa)
                            const el = document.getElementById('turai-hello');
                            if (el && el.style.display !== 'none') {
                                // Güncelle
                                turaiHelloKapat();
                                setTimeout(turaiHelloBalonGoster, 100);
                            }
                            // Eğer panel açık ve ilk mesaj hoş geldin ise güncelle
                            turaiIlkMesajiGuncelle(city, hava);
                        });
                })
                .catch(() => {});
            },
            function() {}, // izin reddedildi — sorun değil
            { timeout: 8000 }
        );
    }

    function turaiHavaTercume(en) {
        const harita = {
            'Sunny': '☀️ Güneşli', 'Clear': '☀️ Açık', 'Partly cloudy': '⛅ Az bulutlu',
            'Cloudy': '☁️ Bulutlu', 'Overcast': '☁️ Kapalı', 'Mist': '🌫️ Sisli',
            'Fog': '🌫️ Sisli', 'Light rain': '🌦️ Hafif yağmur', 'Moderate rain': '🌧️ Yağmur',
            'Heavy rain': '🌧️ Şiddetli yağmur', 'Snow': '❄️ Karlı', 'Thunder': '⛈️ Fırtına',
            'Blizzard': '❄️ Tipi', 'Drizzle': '🌦️ Çisenti',
        };
        return harita[en] || '';
    }

    function turaiIlkMesajiGuncelle(city, hava) {
        // Panel açık ve ilk mesaj hoş geldin mesajı ise güncelle
        const container = document.getElementById('turai-messages');
        if (!container || container.children.length === 0) return;
        const ilk = container.children[0];
        const bubble = ilk?.querySelector('.bubble');
        if (!bubble) return;
        const yeniHtml = turaiHosGeldinOlustur(hava);
        bubble.innerHTML = yeniHtml;
        // localStorage güncelle
        if (kayitliMesajlar.length > 0 && kayitliMesajlar[0].rawHtml) {
            kayitliMesajlar[0].icerik = yeniHtml;
            lsKaydet(kayitliMesajlar, gecmis);
        }
    }

    // GPS başlat
    turaiGpsBaslat();

    // ── Acil panel ────────────────────────────────────────────────────────────
    window.turaiAcilGoster = function () {
        if (document.getElementById('turai-acil-panel')) return;
        let telHtml = '';
        if (ADMIN_PHONES.length) {
            ADMIN_PHONES.forEach(function(u) {
                const label   = (u.role === 'superadmin') ? 'Süperadmin' : 'Admin';
                const tel     = (u.phone || '').replace(/[^0-9]/g, '');
                const display = (u.phone || '').replace(/^90/, '+90 ').replace(/(\d{3})(\d{3})(\d{2})(\d{2})$/, '$1 $2 $3 $4');
                telHtml += `<div style="margin:4px 0;">📞 <strong>${label}</strong> (${u.name}): <a href="tel:+${tel}" style="color:#e94560;font-weight:700;">${display}</a></div>`;
            });
        }
        const html = `<div style="font-size:0.88rem;line-height:1.8;">
            <div style="font-weight:700;font-size:0.95rem;margin-bottom:6px;">🚨 Acil İletişim</div>
            ${telHtml}
            <div style="margin:4px 0;">💬 <a href="${WA_LINK}" target="_blank" rel="noopener" style="color:#e94560;font-weight:700;">WhatsApp ile Yaz →</a></div>
            ${ACIL_ENDPOINT ? `<div style="margin-top:10px;"><button id="turai-acil-panel" onclick="turaiAcilSmsGonder(this)"
                style="background:#e94560;color:#fff;border:none;border-radius:8px;padding:7px 16px;font-size:0.82rem;font-weight:700;cursor:pointer;width:100%;">📨 Acil SMS Gönder</button></div>` : ''}
        </div>`;
        turaiMesajEkle('ai', html, false, true);
    };

    window.turaiAcilSmsGonder = function (btn) {
        if (!ACIL_ENDPOINT || btn.dataset.loading) return;
        btn.dataset.loading = '1';
        btn.textContent = '⏳ Gönderiliyor...';
        btn.disabled = true;
        fetch(ACIL_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({}),
        })
        .then(r => r.json())
        .then(() => { btn.textContent = '✅ SMS Gönderildi'; btn.style.background = '#198754'; })
        .catch(() => { delete btn.dataset.loading; btn.disabled = false; btn.textContent = '📨 Acil SMS Gönder'; });
    };

    window.turaiSelfSmsGonder = function (btn) {
        if (!SELF_SMS_ENDPOINT || btn.dataset.loading) return;
        btn.dataset.loading = '1';
        const orijinal = btn.textContent;
        btn.textContent = '⏳ Gönderiliyor...';
        btn.disabled = true;
        fetch(SELF_SMS_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({}),
        })
        .then(r => r.json())
        .then(data => {
            btn.textContent = '✅ SMS Gönderildi';
            btn.style.cssText += ';background:#198754;color:#fff;border-color:#198754;';
            turaiMesajEkle('ai', data.mesaj || '✅ Talep bilgileri telefonunuza gönderildi.');
            setTimeout(() => {
                btn.textContent = orijinal;
                btn.style.background = ''; btn.style.color = '#198754'; btn.style.borderColor = '#198754';
                delete btn.dataset.loading; btn.disabled = false;
            }, 4000);
        })
        .catch(() => { delete btn.dataset.loading; btn.disabled = false; btn.textContent = orijinal; });
    };

    // ── Gönder ───────────────────────────────────────────────────────────────
    window.turaiSend = function (metin) {
        const input = document.getElementById('turai-input');
        input.value = metin;
        input.style.height = 'auto';
        input.style.height = input.scrollHeight + 'px';
        turaiGonder();
    };
    window.turaiSendClick = function () { turaiGonder(); };
    window.turaiKeydown   = function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); turaiGonder(); }
    };

    function turaiGonder() {
        const input = document.getElementById('turai-input');
        const metin = input.value.trim();
        if (!metin || yukleniyor) return;

        turaiMesajEkle('user', metin);
        gecmis.push({ rol: 'kullanici', icerik: metin });
        input.value = ''; input.style.height = 'auto';

        const yaziyorId = turaiYaziyorGoster();
        yukleniyor = true;
        document.getElementById('turai-send').disabled = true;

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ mesaj: metin, gecmis: gecmis.slice(-12), konum: turaiCity || null, hava: turaiCityWeather || null }),
        })
        .then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); }
            catch(e) { throw new Error('HTTP ' + r.status + ': ' + text.substring(0, 400)); }
        })
        .then(data => {
            turaiYaziyorGizle(yaziyorId);
            if (data.hata) {
                turaiMesajEkle('ai', '⚠️ ' + data.hata, true);
            } else {
                const yanit = data.yanit || '';
                gecmis.push({ rol: 'asistan', icerik: yanit });
                turaiMesajEkle('ai', yanit);
            }
        })
        .catch(err => {
            turaiYaziyorGizle(yaziyorId);
            turaiMesajEkle('ai', '⚠️ ' + (err.message || 'Bağlantı hatası.'), true);
        })
        .finally(() => {
            yukleniyor = false;
            document.getElementById('turai-send').disabled = false;
            document.getElementById('turai-input').focus();
        });
    }

    // ── DOM mesaj ekle + localStorage kaydet ─────────────────────────────────
    function turaiMesajEkle(rol, icerik, hata = false, rawHtml = false) {
        turaiMesajEkleDOM(rol, icerik, hata, rawHtml);
        // Hoş geldin mesajı (ilk AI mesaj) rawHtml=true; diğerlerini kaydet
        kayitliMesajlar.push({ rol, icerik, rawHtml });
        // Maksimum 60 mesaj tut
        if (kayitliMesajlar.length > 60) kayitliMesajlar = kayitliMesajlar.slice(-60);
        lsKaydet(kayitliMesajlar, gecmis.slice(-24));
    }

    function turaiMesajEkleDOM(rol, icerik, hata = false, rawHtml = false) {
        const container = document.getElementById('turai-messages');
        const wrap  = document.createElement('div');
        wrap.className = 'turai-msg ' + (rol === 'ai' ? 'ai' : 'user');

        if (rol === 'ai') {
            const iconWrap = document.createElement('div');
            iconWrap.className = 'turai-ai-icon';
            iconWrap.innerHTML = '<i class="fas fa-robot"></i>';
            wrap.appendChild(iconWrap);
        }

        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        if (hata) bubble.style.cssText = 'background:#fff5f5;color:#c0392b;border:1px solid #f5c6cb;';

        if (rawHtml)           bubble.innerHTML = icerik;
        else if (rol === 'ai') bubble.innerHTML = turaiMarkdown(icerik);
        else                   bubble.textContent = icerik;

        wrap.appendChild(bubble);
        container.appendChild(wrap);
        turaiScrollBottom();
    }

    // ── Yazıyor animasyonu ───────────────────────────────────────────────────
    function turaiYaziyorGoster() {
        const container = document.getElementById('turai-messages');
        const wrap = document.createElement('div');
        wrap.className = 'turai-msg ai';
        const uid = 'ty-' + Date.now();
        wrap.id = uid;
        const iconWrap = document.createElement('div');
        iconWrap.className = 'turai-ai-icon';
        iconWrap.innerHTML = '<i class="fas fa-robot"></i>';
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        bubble.innerHTML = '<div class="turai-typing"><span></span><span></span><span></span></div>';
        wrap.appendChild(iconWrap);
        wrap.appendChild(bubble);
        container.appendChild(wrap);
        turaiScrollBottom();
        return uid;
    }
    function turaiYaziyorGizle(id) { document.getElementById(id)?.remove(); }
    function turaiScrollBottom() {
        const el = document.getElementById('turai-messages');
        setTimeout(() => { el.scrollTop = el.scrollHeight; }, 30);
    }

    // ── Minimal markdown render ──────────────────────────────────────────────
    function turaiMarkdown(text) {
        const links = [];
        text = text.replace(/\[([^\]]+)\]\(((?:https?|tel|mailto):[^\)]+)\)/g, (_, label, url) => {
            const isExt = url.startsWith('http');
            const tag = `<a href="${url}"${isExt ? ' target="_blank" rel="noopener"' : ''} style="color:#e94560;font-weight:600;">${label}</a>`;
            links.push(tag);
            return `\x00LINK${links.length - 1}\x00`;
        });
        text = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        // Talep kartları
        text = text.replace(/🎫\s*\*\*([A-Z0-9\-]+)\*\*\s+(.+?)(?=<br>|$)/gm, (_, gtpnr, rest) => {
            const parts   = rest.split('|').map(s => s.trim().replace(/✈️\s*/g, ''));
            const rotaRaw = parts[0] || '';
            const extra   = parts.slice(1).join(' &nbsp;·&nbsp; ');
            const legs    = rotaRaw.split(' / ');
            const legBadge = (txt, isReturn) => {
                const bg = isReturn ? '#e8f4ff' : '#eaf7ee';
                const border = isReturn ? '#b6d8f5' : '#b2dfc0';
                return `<span style="display:inline-block;background:${bg};border:1px solid ${border};border-radius:6px;padding:2px 8px;font-size:0.78rem;font-weight:600;">${txt.trim()}</span>`;
            };
            const rotaHtml = legs.length > 1
                ? legBadge(legs[0], false) + ` <span style="color:#aaa;">🔄</span> ` + legBadge(legs[1], true)
                : legBadge(rotaRaw, false);
            return `<div style="background:#f8f9ff;border:1.5px solid #dde3f5;border-radius:10px;padding:8px 11px;margin:4px 0;font-size:0.82rem;display:flex;flex-wrap:wrap;align-items:center;gap:6px;">
                <span style="background:#1a1a2e;color:#fff;border-radius:5px;padding:2px 8px;font-weight:700;font-size:0.78rem;">${gtpnr}</span>
                ${rotaHtml}
                ${extra ? `<span style="color:#6c757d;font-size:0.78rem;">${extra}</span>` : ''}
            </div>`;
        });
        text = text
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`(.+?)`/g, '<code style="background:#f0f2f5;padding:1px 5px;border-radius:4px;font-size:0.85em;">$1</code>')
            .replace(/^#{1,3}\s+(.+)$/gm, '<strong style="font-size:0.9em;">$1</strong>')
            .replace(/^[-•]\s+(.+)$/gm, '<div style="padding:2px 0 2px 4px;border-left:2px solid #dde3f5;margin:2px 0;">$1</div>')
            .replace(/\n{2,}/g, '<br>')
            .replace(/\n/g, '<br>');
        text = text.replace(/\x00LINK(\d+)\x00/g, (_, i) => links[+i]);
        return text;
    }
})();
</script>

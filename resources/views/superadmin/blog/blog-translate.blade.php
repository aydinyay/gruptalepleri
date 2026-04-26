<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Blog Toplu Çeviri</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body { background:#f8f9fa; font-family:'Segoe UI',sans-serif; padding:32px; }
.log-box { background:#1e1e1e; color:#d4d4d4; font-family:monospace; font-size:.8rem; border-radius:8px; padding:16px; height:400px; overflow-y:auto; }
.log-ok  { color:#4ec9b0; }
.log-err { color:#f48771; }
.log-inf { color:#9cdcfe; }
</style>
</head>
<body>
<div class="container" style="max-width:800px;">
    <h4 class="mb-1">🌍 Blog Toplu Çeviri</h4>
    <p class="text-muted mb-4">Gemini API ile tüm yayında blog yazılarını 7 dile çevirir.</p>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <button class="btn btn-primary" id="startBtn" onclick="startTranslation(false)">
                    ▶ Çevrilmemişleri Çevir
                </button>
                <button class="btn btn-warning" id="forceBtn" onclick="startTranslation(true)">
                    🔄 Tümünü Yeniden Çevir
                </button>
                <button class="btn btn-outline-secondary" id="stopBtn" onclick="stopTranslation()" disabled>
                    ⏹ Durdur
                </button>
            </div>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="progress flex-grow-1" style="height:20px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="progressBar" role="progressbar" style="width:0%">0%</div>
                </div>
                <span id="progressText" class="text-muted" style="white-space:nowrap;">Bekliyor</span>
            </div>
            <div class="log-box" id="logBox">
                <span class="log-inf">Başlatmak için butona tıklayın...</span>
            </div>
        </div>
    </div>
    <a href="{{ route('superadmin.blog.index') }}" class="btn btn-outline-secondary">← Blog Yönetimine Dön</a>
</div>

<script>
let running = false;
let queue   = [];
let done    = 0;
let total   = 0;
const BASE  = '/gitfix.php?t=grt2026fix';

function log(msg, cls) {
    const box = document.getElementById('logBox');
    box.innerHTML += `<div class="${cls || ''}">${msg}</div>`;
    box.scrollTop = box.scrollHeight;
}

function setProgress(pct, text) {
    const bar = document.getElementById('progressBar');
    bar.style.width = pct + '%';
    bar.textContent = Math.round(pct) + '%';
    document.getElementById('progressText').textContent = text;
}

async function startTranslation(force) {
    if (running) return;
    document.getElementById('logBox').innerHTML = '';
    log('📋 Blog listesi alınıyor...', 'log-inf');
    running = true;
    document.getElementById('startBtn').disabled = true;
    document.getElementById('forceBtn').disabled = true;
    document.getElementById('stopBtn').disabled = false;

    const forceParam = force ? '&force=1' : '';
    try {
        const r = await fetch(BASE + '&action=translate-blog-list' + forceParam);
        const data = await r.json();
        total = data.total;
        queue = data.ids.map(i => i.id);
        done  = 0;
        log(`✅ ${total} yazı bulundu.`, 'log-inf');
        if (!total) { finish(); return; }
        processNext();
    } catch(e) {
        log('❌ Liste alınamadı: ' + e.message, 'log-err');
        finish();
    }
}

async function processNext() {
    if (!running || !queue.length) { finish(); return; }
    const id = queue.shift();
    let retries = 2;
    while (retries >= 0) {
        try {
            const r = await fetch(BASE + '&action=translate-blog&id=' + id);
            const text = await r.text();
            let data;
            try { data = JSON.parse(text); }
            catch(_) {
                const preview = text.replace(/<[^>]+>/g, '').trim().slice(0, 120);
                if (retries > 0) { retries--; await new Promise(r => setTimeout(r, 1000)); continue; }
                log(`✗ ID:${id} sunucu hatası — ${preview}`, 'log-err');
                break;
            }
            done++;
            if (data.exit === 0) {
                log(`✓ ID:${id} — ${(data.output||'').trim().split('\n').pop()}`, 'log-ok');
            } else {
                log(`✗ ID:${id} HATA — ${(data.output||data.error||'').trim()}`, 'log-err');
            }
            setProgress(total > 0 ? (done / total * 100) : 0, `${done} / ${total}`);
            break;
        } catch(e) {
            if (retries > 0) { retries--; await new Promise(r => setTimeout(r, 1500)); continue; }
            log(`✗ ID:${id} bağlantı hatası: ${e.message}`, 'log-err');
            done++;
            break;
        }
    }
    await new Promise(r => setTimeout(r, 500));
    processNext();
}

function stopTranslation() {
    running = false;
    log('⏹ Durduruldu.', 'log-inf');
    finish();
}

function finish() {
    running = false;
    document.getElementById('startBtn').disabled = false;
    document.getElementById('forceBtn').disabled = false;
    document.getElementById('stopBtn').disabled = true;
    if (total > 0) {
        setProgress(100, 'Tamamlandı!');
        log(`\n🎉 İşlem tamamlandı. ${done}/${total} yazı işlendi.`, 'log-inf');
    }
}
</script>
</body>
</html>

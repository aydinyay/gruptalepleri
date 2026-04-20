{{-- Günün Turizm Sorusu --}}
<div id="dq-card">
    {{-- Skeleton --}}
    <div id="dq-skeleton">
        <div class="dq-sk-line" style="width:60%;height:14px;margin-bottom:10px;"></div>
        <div class="dq-sk-line" style="width:90%;height:18px;margin-bottom:6px;"></div>
        <div class="dq-sk-line" style="width:80%;height:18px;margin-bottom:20px;"></div>
        <div class="dq-sk-btn"></div>
        <div class="dq-sk-btn"></div>
        <div class="dq-sk-btn"></div>
    </div>

    {{-- İçerik (JS ile doldurulur) --}}
    <div id="dq-content" style="display:none;">
        <div class="dq-badge">🧭 Günün Turizm Sorusu</div>
        <p id="dq-question" class="dq-question"></p>
        <div id="dq-options" class="dq-options"></div>
        <div id="dq-result" class="dq-result" style="display:none;"></div>
    </div>
</div>

<style>
#dq-card {
    background: linear-gradient(160deg, #0f2444 0%, #1a3c6b 100%);
    border-radius: 16px;
    padding: 22px 20px;
    color: #fff;
    position: relative;
    overflow: hidden;
    height: 100%;
    box-sizing: border-box;
    min-height: 280px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
#dq-card::before {
    content: '?';
    position: absolute;
    right: -10px; top: -20px;
    font-size: 9rem;
    font-weight: 900;
    color: rgba(255,255,255,.04);
    line-height: 1;
    pointer-events: none;
}

/* Skeleton */
.dq-sk-line {
    background: rgba(255,255,255,.12);
    border-radius: 6px;
    animation: dqPulse 1.4s ease-in-out infinite;
}
.dq-sk-btn {
    height: 38px;
    background: rgba(255,255,255,.1);
    border-radius: 8px;
    margin-bottom: 8px;
    animation: dqPulse 1.4s ease-in-out infinite;
}
.dq-sk-btn:nth-child(2) { animation-delay: .15s; }
.dq-sk-btn:nth-child(3) { animation-delay: .30s; }
@@keyframes dqPulse {
    0%,100% { opacity:.5; }
    50%      { opacity:1; }
}

/* İçerik */
.dq-badge {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #f4a418;
    margin-bottom: 10px;
}
.dq-question {
    font-size: .95rem;
    font-weight: 600;
    line-height: 1.5;
    color: #e8f0fb;
    margin: 0 0 16px;
}
.dq-options { display: flex; flex-direction: column; gap: 8px; }
.dq-opt {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 8px;
    padding: 9px 14px;
    font-size: .84rem;
    color: #e8f0fb;
    cursor: pointer;
    text-align: left;
    transition: background .15s, border-color .15s, transform .1s;
    width: 100%;
}
.dq-opt:hover:not(:disabled) {
    background: rgba(255,255,255,.18);
    border-color: #f4a418;
    transform: translateX(3px);
}
.dq-opt.correct  { background: #16a34a; border-color: #22c55e; color: #fff; }
.dq-opt.wrong    { background: #dc2626; border-color: #ef4444; color: #fff; }
.dq-opt:disabled { cursor: default; transform: none; }

.dq-result {
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 10px;
    font-size: .82rem;
    line-height: 1.5;
}
.dq-result.correct-res {
    background: rgba(22,163,74,.2);
    border: 1px solid rgba(34,197,94,.3);
    color: #bbf7d0;
}
.dq-result.wrong-res {
    background: rgba(220,38,38,.15);
    border: 1px solid rgba(239,68,68,.25);
    color: #fecaca;
}
.dq-coupon {
    display: inline-block;
    margin-top: 8px;
    background: #f4a418;
    color: #0f2444;
    font-weight: 800;
    font-size: .85rem;
    padding: 5px 12px;
    border-radius: 6px;
    letter-spacing: .05em;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js" defer></script>
<script>
(function () {
    var answered = false;

    function load() {
        fetch('/api/b2c/daily-quiz', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (! data || data.error) return;
                render(data);
            })
            .catch(function () {});
    }

    function render(data) {
        document.getElementById('dq-skeleton').style.display = 'none';
        document.getElementById('dq-content').style.display  = 'block';
        document.getElementById('dq-question').textContent   = data.question;

        var opts  = document.getElementById('dq-options');
        var labels = { a: 'A', b: 'B', c: 'C' };
        ['a','b','c'].forEach(function (key) {
            var btn = document.createElement('button');
            btn.className = 'dq-opt';
            btn.textContent = labels[key] + ')  ' + data.options[key];
            btn.dataset.key = key;
            btn.addEventListener('click', function () {
                if (answered) return;
                answered = true;
                var btns = opts.querySelectorAll('.dq-opt');
                btns.forEach(function (b) { b.disabled = true; });

                var isCorrect = (key === data.correct);
                btn.classList.add(isCorrect ? 'correct' : 'wrong');
                if (! isCorrect) {
                    opts.querySelector('[data-key="' + data.correct + '"]').classList.add('correct');
                }

                showResult(isCorrect, data.explanation);

                if (isCorrect) {
                    setTimeout(function () {
                        if (typeof confetti !== 'undefined') {
                            confetti({ particleCount: 120, spread: 70, origin: { y: 0.6 } });
                        }
                    }, 100);
                }
            });
            opts.appendChild(btn);
        });
    }

    function showResult(correct, explanation) {
        var el = document.getElementById('dq-result');
        el.style.display = 'block';
        if (correct) {
            el.className = 'dq-result correct-res';
            el.innerHTML = '🎉 <strong>Tebrikler!</strong> ' + escHtml(explanation)
                + '<br><span class="dq-coupon">🎁 İndirim Kodunuz: GRUP5</span>';
        } else {
            el.className = 'dq-result wrong-res';
            el.innerHTML = '<strong>Sağlık olsun, işte doğrusu:</strong><br>' + escHtml(explanation);
        }
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Sayfa yüklenince başlat
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
</script>

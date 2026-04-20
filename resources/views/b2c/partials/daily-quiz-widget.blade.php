{{-- Günün Turizm Sorusu --}}
<div id="dq-card">
    <div id="dq-skeleton">
        <div class="dq-sk-line" style="width:55%;height:12px;margin-bottom:12px;"></div>
        <div class="dq-sk-line" style="width:85%;height:16px;margin-bottom:18px;"></div>
        <div class="dq-sk-btn"></div>
        <div class="dq-sk-btn"></div>
        <div class="dq-sk-btn"></div>
    </div>

    <div id="dq-game" style="display:none;">
        <div class="dq-top-bar">
            <span class="dq-badge">🧭 Günün Sorusu</span>
            <span id="dq-progress" class="dq-prog"></span>
        </div>
        <p id="dq-question" class="dq-question"></p>
        <div id="dq-options" class="dq-options"></div>
        <div id="dq-feedback" class="dq-feedback" style="display:none;"></div>
        <button id="dq-next" class="dq-next-btn" style="display:none;">Sonraki →</button>
    </div>

    <div id="dq-result" style="display:none;" class="dq-result-screen">
        <div id="dq-result-inner"></div>
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
    box-sizing: border-box;
    min-height: 280px;
    height: 100%;
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
    margin-bottom: 8px;
    animation: dqPulse 1.4s ease-in-out infinite;
}
.dq-sk-btn {
    height: 36px;
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

/* Game area */
#dq-game { display: flex; flex-direction: column; width: 100%; }

.dq-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.dq-badge {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #f4a418;
}
.dq-prog {
    font-size: .72rem;
    font-weight: 700;
    color: rgba(255,255,255,.55);
    letter-spacing: .04em;
}
.dq-question {
    font-size: .9rem;
    font-weight: 600;
    line-height: 1.45;
    color: #e8f0fb;
    margin: 0 0 12px;
}
.dq-options { display: flex; flex-direction: column; gap: 7px; }
.dq-opt {
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 8px;
    padding: 8px 13px;
    font-size: .82rem;
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

.dq-feedback {
    margin-top: 10px;
    padding: 9px 12px;
    border-radius: 8px;
    font-size: .79rem;
    line-height: 1.45;
}
.dq-feedback.correct-fb {
    background: rgba(22,163,74,.2);
    border: 1px solid rgba(34,197,94,.3);
    color: #bbf7d0;
}
.dq-feedback.wrong-fb {
    background: rgba(220,38,38,.15);
    border: 1px solid rgba(239,68,68,.25);
    color: #fecaca;
}
.dq-next-btn {
    margin-top: 10px;
    align-self: flex-end;
    background: #f4a418;
    color: #0f2444;
    border: none;
    border-radius: 7px;
    padding: 7px 16px;
    font-size: .8rem;
    font-weight: 800;
    cursor: pointer;
    transition: opacity .15s;
}
.dq-next-btn:hover { opacity: .85; }

/* Result screen */
.dq-result-screen {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    width: 100%;
    height: 100%;
}
.dq-result-screen .dq-big-icon { font-size: 2.8rem; margin-bottom: 8px; }
.dq-result-screen .dq-res-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 6px;
}
.dq-result-screen .dq-res-score {
    font-size: 2rem;
    font-weight: 900;
    color: #f4a418;
    margin-bottom: 6px;
}
.dq-result-screen .dq-res-sub {
    font-size: .82rem;
    color: rgba(255,255,255,.65);
    margin-bottom: 14px;
}
.dq-coupon {
    display: inline-block;
    background: #f4a418;
    color: #0f2444;
    font-weight: 800;
    font-size: .88rem;
    padding: 6px 16px;
    border-radius: 7px;
    letter-spacing: .06em;
    margin-bottom: 12px;
}
.dq-retry-btn {
    background: rgba(255,255,255,.12);
    color: #e8f0fb;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 7px;
    padding: 6px 14px;
    font-size: .78rem;
    cursor: pointer;
    margin-top: 4px;
}
.dq-retry-btn:hover { background: rgba(255,255,255,.2); }
</style>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js" defer></script>
<script>
(function () {
    var questions = [];
    var current   = 0;
    var score     = 0;
    var answered  = false;

    function load() {
        fetch('/api/b2c/daily-quiz', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (! data || data.error || ! data.questions || ! data.questions.length) return;
                questions = data.questions;
                showQuestion();
            })
            .catch(function () {});
    }

    function showQuestion() {
        document.getElementById('dq-skeleton').style.display = 'none';
        document.getElementById('dq-game').style.display     = 'flex';
        document.getElementById('dq-result').style.display   = 'none';

        var q = questions[current];
        answered = false;

        document.getElementById('dq-progress').textContent = (current + 1) + ' / ' + questions.length;
        document.getElementById('dq-question').textContent = q.question;

        var opts = document.getElementById('dq-options');
        opts.innerHTML = '';
        ['a','b','c'].forEach(function (key) {
            var btn = document.createElement('button');
            btn.className   = 'dq-opt';
            btn.textContent = key.toUpperCase() + ')  ' + q.options[key];
            btn.dataset.key = key;
            btn.addEventListener('click', function () { onAnswer(key); });
            opts.appendChild(btn);
        });

        var fb = document.getElementById('dq-feedback');
        fb.style.display = 'none';
        fb.className = 'dq-feedback';
        document.getElementById('dq-next').style.display = 'none';
    }

    function onAnswer(key) {
        if (answered) return;
        answered = true;

        var q    = questions[current];
        var btns = document.getElementById('dq-options').querySelectorAll('.dq-opt');
        btns.forEach(function (b) { b.disabled = true; });

        var isCorrect = (key === q.correct);
        if (isCorrect) score++;

        document.querySelector('.dq-opt[data-key="' + key + '"]').classList.add(isCorrect ? 'correct' : 'wrong');
        if (! isCorrect) {
            document.querySelector('.dq-opt[data-key="' + q.correct + '"]').classList.add('correct');
        }

        var fb = document.getElementById('dq-feedback');
        fb.style.display = 'block';
        if (isCorrect) {
            fb.className = 'dq-feedback correct-fb';
            fb.textContent = '✓ ' + q.explanation;
            if (typeof confetti !== 'undefined') {
                confetti({ particleCount: 60, spread: 55, origin: { y: 0.7 }, scalar: 0.85 });
            }
        } else {
            fb.className = 'dq-feedback wrong-fb';
            fb.textContent = '✗ ' + q.explanation;
        }

        var nextBtn = document.getElementById('dq-next');
        nextBtn.style.display = 'block';
        if (current + 1 >= questions.length) {
            nextBtn.textContent = 'Sonuçlar →';
        } else {
            nextBtn.textContent = 'Sonraki →';
        }
    }

    document.getElementById('dq-next').addEventListener('click', function () {
        current++;
        if (current >= questions.length) {
            showResult();
        } else {
            showQuestion();
        }
    });

    function showResult() {
        document.getElementById('dq-game').style.display   = 'none';
        document.getElementById('dq-result').style.display = 'flex';

        var total   = questions.length;
        var won     = score >= 4;
        var icon    = won ? '🏆' : (score >= 2 ? '👏' : '😅');
        var title   = won ? 'Harikasın!' : (score >= 2 ? 'Fena Değil!' : 'Bir Daha Dene!');
        var sub     = score + ' / ' + total + ' doğru';

        var html = '<div class="dq-big-icon">' + icon + '</div>'
                 + '<div class="dq-res-score">' + score + '<span style="font-size:1rem;font-weight:600;color:rgba(255,255,255,.5)">/' + total + '</span></div>'
                 + '<div class="dq-res-title">' + title + '</div>';

        if (won) {
            html += '<div class="dq-res-sub">Tüm soruları geçtin, işte ödülün:</div>'
                  + '<div class="dq-coupon">🎁 GRUP5</div>';
            setTimeout(function () {
                if (typeof confetti !== 'undefined') {
                    confetti({ particleCount: 160, spread: 80, origin: { y: 0.5 } });
                }
            }, 150);
        } else {
            html += '<div class="dq-res-sub">' + sub + ' — tekrar dene!</div>';
        }

        html += '<button class="dq-retry-btn" id="dq-retry">Tekrar Oyna</button>';
        document.getElementById('dq-result-inner').innerHTML = html;

        document.getElementById('dq-retry').addEventListener('click', function () {
            current  = 0;
            score    = 0;
            answered = false;
            showQuestion();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', load);
    } else {
        load();
    }
})();
</script>

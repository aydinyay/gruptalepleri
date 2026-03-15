{{-- Bildirim Zili — tüm navbarlardan <x-notification-bell /> ile kullanılır --}}
<div class="dropdown" id="notif-wrapper" style="position:relative;">
    <button class="btn btn-sm btn-outline-light position-relative" id="notif-btn"
            data-bs-toggle="dropdown" aria-expanded="false" style="border-color:rgba(255,255,255,0.3);">
        <i class="fas fa-bell"></i>
        <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="font-size:0.6rem;display:none;">0</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0 shadow" id="notif-dropdown"
         style="min-width:340px;max-height:480px;overflow-y:auto;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
            <span class="fw-bold small">Bildirimler</span>
            <button class="btn btn-link btn-sm text-muted p-0 small" id="notif-hepsini-oku">Hepsini okundu say</button>
        </div>
        <div id="notif-liste">
            <div class="text-center text-muted py-4 small">Yükleniyor...</div>
        </div>
    </div>
</div>

<script>
(function() {
    const POLL_URL     = '{{ route("bildirimler.liste") }}';
    const OKUNDU_URL   = '{{ route("bildirimler.okundu") }}';
    const HEPSINI_URL  = '{{ route("bildirimler.hepsini-oku") }}';
    const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let sonOkunmamis = 0;

    const badge  = document.getElementById('notif-badge');
    const liste  = document.getElementById('notif-liste');
    const btn    = document.getElementById('notif-btn');

    function typeIcon(type) {
        const icons = {
            new_request:     '📋',
            new_agency:      '🏢',
            offer_added:     '💰',
            offer_accepted:  '✅',
            opsiyon_uyarisi: '⚠️',
            broadcast:       '📢',
            email_sent:      '📧',
        };
        return icons[type] ?? '🔔';
    }

    function render(bildirimler) {
        if (!bildirimler.length) {
            liste.innerHTML = '<div class="text-center text-muted py-4 small">Bildirim yok</div>';
            return;
        }
        liste.innerHTML = bildirimler.map(b => `
            <a href="${b.url ?? '#'}" class="d-flex gap-2 px-3 py-2 text-decoration-none border-bottom notif-item ${b.is_read ? 'bg-white' : 'bg-light'}"
               data-id="${b.id}" style="color:inherit;">
                <div style="font-size:1.1rem;flex-shrink:0;padding-top:2px;">${typeIcon(b.type)}</div>
                <div style="min-width:0;">
                    <div class="fw-bold" style="font-size:0.82rem;${b.is_read ? '' : 'color:#1a1a2e;'}">${escHtml(b.title)}</div>
                    <div class="text-muted" style="font-size:0.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(b.message)}</div>
                    <div class="text-muted" style="font-size:0.7rem;">${b.created_at ? b.created_at.substring(0,16).replace('T',' ') : ''}</div>
                </div>
                ${b.is_read ? '' : '<div style="flex-shrink:0;padding-top:6px;"><span style="width:8px;height:8px;border-radius:50%;background:#e94560;display:inline-block;"></span></div>'}
            </a>
        `).join('');

        // Tıklayınca okundu işaretle
        liste.querySelectorAll('.notif-item').forEach(el => {
            el.addEventListener('click', function() {
                const id = this.dataset.id;
                fetch(OKUNDU_URL, {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
                    body: JSON.stringify({ids: [parseInt(id)]})
                });
            });
        });
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function fetchBildirimler() {
        fetch(POLL_URL)
            .then(r => r.json())
            .then(data => {
                const yeniOkunmamis = data.okunmamis ?? 0;

                // Badge güncelle
                if (yeniOkunmamis > 0) {
                    badge.textContent = yeniOkunmamis > 99 ? '99+' : yeniOkunmamis;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }

                // Yeni bildirim geldiyse tarayıcı bildirimi göster
                if (yeniOkunmamis > sonOkunmamis && sonOkunmamis >= 0) {
                    const yeni = data.bildirimler?.find(b => !b.is_read);
                    if (yeni) showBrowserNotif(yeni);
                }
                sonOkunmamis = yeniOkunmamis;

                render(data.bildirimler ?? []);
            })
            .catch(() => {});
    }

    function showBrowserNotif(b) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        const n = new Notification(b.title, {body: b.message, icon: '/favicon.ico'});
        if (b.url) n.onclick = () => { window.open(b.url, '_self'); n.close(); };
        setTimeout(() => n.close(), 6000);
    }

    // Tarayıcı bildirim izni iste
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    // Hepsini okundu say
    document.getElementById('notif-hepsini-oku').addEventListener('click', function(e) {
        e.preventDefault(); e.stopPropagation();
        fetch(HEPSINI_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN': CSRF},
        }).then(() => { sonOkunmamis = 0; fetchBildirimler(); });
    });

    // İlk yükleme + periyodik polling
    fetchBildirimler();
    setInterval(fetchBildirimler, 30000);
})();
</script>

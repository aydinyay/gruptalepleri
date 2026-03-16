{{-- Bildirim Zili - tum navbarlardan <x-notification-bell /> ile kullanilir --}}
<div class="dropdown" id="notif-wrapper" style="position:relative;">
    <button class="btn btn-sm btn-outline-light position-relative" id="notif-btn"
            data-bs-toggle="dropdown" aria-expanded="false" style="border-color:rgba(255,255,255,0.3);">
        <i class="fas fa-bell"></i>
        <span id="notif-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="font-size:0.6rem;display:none;">0</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0 shadow" id="notif-dropdown"
         style="min-width:360px;max-height:520px;overflow-y:auto;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
            <span class="fw-bold small">Bildirimler</span>
            <button class="btn btn-link btn-sm text-muted p-0 small" id="notif-hepsini-oku">Hepsini okundu say</button>
        </div>
        @if(auth()->check() && auth()->user()->role === 'superadmin')
            <div class="px-3 py-2 border-bottom d-flex gap-2 bg-white">
                <button class="btn btn-outline-danger btn-sm py-1 px-2 small" id="notif-secili-herkesten-sil">
                    Seçileni herkesten sil
                </button>
                <button class="btn btn-danger btn-sm py-1 px-2 small" id="notif-hepsini-herkesten-sil">
                    Tümünü herkesten sil
                </button>
            </div>
        @endif
        <div id="notif-liste">
            <div class="text-center text-muted py-4 small">Yükleniyor...</div>
        </div>
    </div>
</div>

<script>
(function() {
    const POLL_URL = '{{ route("bildirimler.liste") }}';
    const OKUNDU_URL = '{{ route("bildirimler.okundu") }}';
    const HEPSINI_URL = '{{ route("bildirimler.hepsini-oku") }}';
    const IS_SUPERADMIN = @json(auth()->check() && auth()->user()->role === 'superadmin');
    const SIL_HERKESTEN_URL_TEMPLATE = '{{ route("superadmin.bildirim.herkesten-sil", ["bildirim" => "__ID__"]) }}';
    const SIL_SECILEN_HERKESTEN_URL = '{{ route("superadmin.bildirim.secilenleri-herkesten-sil") }}';
    const SIL_HEPSI_HERKESTEN_URL = '{{ route("superadmin.bildirim.hepsini-herkesten-sil") }}';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let sonOkunmamis = 0;
    const badge = document.getElementById('notif-badge');
    const liste = document.getElementById('notif-liste');

    function withId(urlTemplate, id) {
        return urlTemplate.replace('__ID__', String(id));
    }

    function typeIcon(type) {
        const icons = {
            new_request: '📋',
            new_agency: '🏢',
            offer_added: '💰',
            offer_accepted: '✅',
            opsiyon_uyarisi: '⚠️',
            broadcast: '📢',
            email_sent: '📧',
        };
        return icons[type] ?? '🔔';
    }

    function escHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fetchJson(url, options = {}) {
        return fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                ...(options.headers ?? {}),
            },
        });
    }

    function render(bildirimler) {
        if (!bildirimler.length) {
            liste.innerHTML = '<div class="text-center text-muted py-4 small">Bildirim yok</div>';
            return;
        }

        liste.innerHTML = bildirimler.map((b) => `
            <a href="${b.url ?? '#'}"
               class="d-block px-3 py-2 text-decoration-none border-bottom notif-item ${b.is_read ? 'bg-white' : 'bg-light'}"
               data-id="${b.id}"
               style="color:inherit;">
                <div class="d-flex gap-2 align-items-start">
                    ${IS_SUPERADMIN ? `<input type="checkbox" class="form-check-input mt-1 notif-secim notif-ignore-click" data-id="${b.id}" title="Seç">` : ''}
                    <div style="font-size:1.1rem;flex-shrink:0;padding-top:2px;">${typeIcon(b.type)}</div>
                    <div style="min-width:0;flex:1;">
                        <div class="fw-bold" style="font-size:0.82rem;${b.is_read ? '' : 'color:#1a1a2e;'}">${escHtml(b.title)}</div>
                        <div class="text-muted" style="font-size:0.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(b.message)}</div>
                        <div class="text-muted" style="font-size:0.7rem;">${b.created_at ? b.created_at.substring(0,16).replace('T',' ') : ''}</div>
                    </div>
                    <div class="d-flex align-items-start gap-2" style="flex-shrink:0;">
                        ${b.is_read ? '' : '<span style="width:8px;height:8px;margin-top:6px;border-radius:50%;background:#e94560;display:inline-block;"></span>'}
                        ${IS_SUPERADMIN ? '<button type="button" class="btn btn-link btn-sm text-danger p-0 notif-herkesten-sil notif-ignore-click" data-id="' + b.id + '" title="Bu bildirimi herkesten sil">🗑</button>' : ''}
                    </div>
                </div>
            </a>
        `).join('');

        liste.querySelectorAll('.notif-item').forEach((el) => {
            el.addEventListener('click', function(e) {
                if (e.target.closest('.notif-secim')) {
                    e.stopPropagation();
                    return;
                }

                if (e.target.closest('.notif-herkesten-sil')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }

                const id = parseInt(this.dataset.id, 10);
                fetchJson(OKUNDU_URL, {
                    method: 'POST',
                    body: JSON.stringify({ ids: [id] }),
                });
            });
        });

        if (IS_SUPERADMIN) {
            liste.querySelectorAll('.notif-herkesten-sil').forEach((el) => {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = this.dataset.id;

                    if (!confirm('Bu bildirimin herkeste görünen kopyaları silinsin mi?')) {
                        return;
                    }

                    fetchJson(withId(SIL_HERKESTEN_URL_TEMPLATE, id), { method: 'DELETE' })
                        .then(() => fetchBildirimler())
                        .catch(() => {});
                });
            });
        }
    }

    function fetchBildirimler() {
        fetch(POLL_URL, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((data) => {
                const yeniOkunmamis = data.okunmamis ?? 0;

                if (yeniOkunmamis > 0) {
                    badge.textContent = yeniOkunmamis > 99 ? '99+' : yeniOkunmamis;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }

                if (yeniOkunmamis > sonOkunmamis && sonOkunmamis >= 0) {
                    const yeni = data.bildirimler?.find((b) => !b.is_read);
                    if (yeni) {
                        showBrowserNotif(yeni);
                    }
                }

                sonOkunmamis = yeniOkunmamis;
                render(data.bildirimler ?? []);
            })
            .catch(() => {});
    }

    function showBrowserNotif(b) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        const n = new Notification(b.title, { body: b.message, icon: '/favicon.ico' });
        if (b.url) {
            n.onclick = () => {
                window.open(b.url, '_self');
                n.close();
            };
        }
        setTimeout(() => n.close(), 6000);
    }

    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    document.getElementById('notif-hepsini-oku').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        fetchJson(HEPSINI_URL, { method: 'POST' })
            .then(() => {
                sonOkunmamis = 0;
                fetchBildirimler();
            })
            .catch(() => {});
    });

    if (IS_SUPERADMIN) {
        const seciliSilBtn = document.getElementById('notif-secili-herkesten-sil');
        const hepsiSilBtn = document.getElementById('notif-hepsini-herkesten-sil');

        if (seciliSilBtn) {
            seciliSilBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const ids = Array.from(document.querySelectorAll('#notif-liste .notif-secim:checked'))
                    .map((el) => parseInt(el.dataset.id, 10))
                    .filter((id) => !Number.isNaN(id));

                if (!ids.length) {
                    alert('Önce en az bir bildirim seçin.');
                    return;
                }

                if (!confirm(`Seçilen ${ids.length} bildirimin herkesteki kopyaları silinsin mi?`)) {
                    return;
                }

                fetchJson(SIL_SECILEN_HERKESTEN_URL, {
                    method: 'POST',
                    body: JSON.stringify({ ids }),
                })
                    .then(() => fetchBildirimler())
                    .catch(() => {});
            });
        }

        if (hepsiSilBtn) {
            hepsiSilBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (!confirm('Bu listedeki bildirimlerin herkesteki tüm kopyaları silinsin mi?')) {
                    return;
                }

                fetchJson(SIL_HEPSI_HERKESTEN_URL, { method: 'POST' })
                    .then(() => fetchBildirimler())
                    .catch(() => {});
            });
        }
    }

    fetchBildirimler();
    setInterval(fetchBildirimler, 30000);
})();
</script>

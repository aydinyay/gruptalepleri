{{-- Bildirim zili - tum navbarlarda x-notification-bell ile kullanilir --}}
@php
    $isSuperadmin = auth()->check() && auth()->user()->role === 'superadmin';
@endphp

@once
<style>
.gt-notif {
    position: relative;
    display: inline-flex;
    align-items: center;
    flex: 0 0 auto;
}
.gt-notif-btn {
    border-color: rgba(255, 255, 255, 0.3);
}
.gt-notif-badge {
    font-size: 0.6rem;
    display: none;
}
.gt-notif-menu {
    position: absolute;
    top: calc(100% + 0.45rem);
    right: 0;
    min-width: min(360px, calc(100vw - 1rem));
    max-height: 520px;
    overflow-y: auto;
    z-index: 1300;
    border: 1px solid rgba(15, 23, 42, 0.12);
    border-radius: 0.7rem;
    background: #ffffff;
}
.gt-notif-menu[hidden] {
    display: none !important;
}
.gt-notif-header {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f8fafc;
}
.gt-notif-item {
    color: inherit;
}
.gt-notif-item:hover {
    background: rgba(15, 23, 42, 0.04);
}
html[data-theme="dark"] .gt-notif-menu {
    background: #16213e;
    border-color: #2a2a4e;
}
html[data-theme="dark"] .gt-notif-header {
    background: #1f2947;
}
@media (max-width: 575.98px) {
    .gt-notif-menu {
        right: -0.55rem;
        min-width: min(360px, calc(100vw - 0.7rem));
    }
}
</style>
@endonce

<div class="gt-notif" data-gt-notif data-superadmin="{{ $isSuperadmin ? '1' : '0' }}">
    <button
        type="button"
        class="btn btn-sm btn-outline-light position-relative gt-notif-btn"
        data-gt-notif-toggle
        aria-expanded="false"
    >
        <i class="fas fa-bell" aria-hidden="true"></i>
        <span
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger gt-notif-badge"
            data-gt-notif-badge
        >0</span>
    </button>

    <div class="gt-notif-menu p-0 shadow" data-gt-notif-menu hidden>
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom gt-notif-header">
            <span class="fw-bold small">Bildirimler</span>
            <button class="btn btn-link btn-sm text-muted p-0 small" type="button" data-gt-notif-mark-all>
                Hepsini okundu say
            </button>
        </div>

        @if($isSuperadmin)
            <div class="px-3 py-2 border-bottom d-flex gap-2 bg-white">
                <button class="btn btn-outline-danger btn-sm py-1 px-2 small" type="button" data-gt-notif-delete-selected>
                    Secileni herkesten sil
                </button>
                <button class="btn btn-danger btn-sm py-1 px-2 small" type="button" data-gt-notif-delete-all>
                    Tumunu herkesten sil
                </button>
            </div>
        @endif

        <div data-gt-notif-list>
            <div class="text-center text-muted py-4 small">Yukleniyor...</div>
        </div>
    </div>
</div>

@once
<script>
(function () {
    if (window.__gtNotifCenter && typeof window.__gtNotifCenter.init === 'function') {
        window.__gtNotifCenter.init();
        return;
    }

    const POLL_URL = '{{ route("bildirimler.liste") }}';
    const OKUNDU_URL = '{{ route("bildirimler.okundu") }}';
    const HEPSINI_URL = '{{ route("bildirimler.hepsini-oku") }}';
    const SIL_HERKESTEN_URL_TEMPLATE = '{{ route("superadmin.bildirim.herkesten-sil", ["bildirim" => "__ID__"]) }}';
    const SIL_SECILEN_HERKESTEN_URL = '{{ route("superadmin.bildirim.secilenleri-herkesten-sil") }}';
    const SIL_HEPSI_HERKESTEN_URL = '{{ route("superadmin.bildirim.hepsini-herkesten-sil") }}';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let sonOkunmamis = 0;
    let pollTimer = null;
    const roots = new Set();

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

    function closeAllMenus() {
        roots.forEach((root) => {
            const menu = root.querySelector('[data-gt-notif-menu]');
            const toggle = root.querySelector('[data-gt-notif-toggle]');
            if (menu) {
                menu.hidden = true;
            }
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function markRead(id) {
        fetchJson(OKUNDU_URL, {
            method: 'POST',
            body: JSON.stringify({ ids: [id] }),
        }).catch(() => {});
    }

    function renderList(root, bildirimler) {
        const list = root.querySelector('[data-gt-notif-list]');
        const isSuperadmin = root.dataset.superadmin === '1';

        if (!list) {
            return;
        }

        if (!Array.isArray(bildirimler) || bildirimler.length === 0) {
            list.innerHTML = '<div class="text-center text-muted py-4 small">Bildirim yok</div>';
            return;
        }

        list.innerHTML = bildirimler.map((b) => `
            <a href="${b.url ?? '#'}"
               class="d-block px-3 py-2 text-decoration-none border-bottom gt-notif-item ${b.is_read ? 'bg-white' : 'bg-light'}"
               data-gt-notif-item
               data-id="${b.id}">
                <div class="d-flex gap-2 align-items-start">
                    ${isSuperadmin ? `<input type="checkbox" class="form-check-input mt-1 gt-notif-select" data-id="${b.id}" title="Sec">` : ''}
                    <div style="font-size:1.1rem;flex-shrink:0;padding-top:2px;">${typeIcon(b.type)}</div>
                    <div style="min-width:0;flex:1;">
                        <div class="fw-bold" style="font-size:0.82rem;">${escHtml(b.title)}</div>
                        <div class="text-muted" style="font-size:0.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(b.message)}</div>
                        <div class="text-muted" style="font-size:0.7rem;">${b.created_at ? b.created_at.substring(0, 16).replace('T', ' ') : ''}</div>
                    </div>
                    <div class="d-flex align-items-start gap-2" style="flex-shrink:0;">
                        ${b.is_read ? '' : '<span style="width:8px;height:8px;margin-top:6px;border-radius:50%;background:#e94560;display:inline-block;"></span>'}
                        ${isSuperadmin ? `<button type="button" class="btn btn-link btn-sm text-danger p-0 gt-notif-delete-one" data-id="${b.id}" title="Bu bildirimi herkesten sil">🗑</button>` : ''}
                    </div>
                </div>
            </a>
        `).join('');

        list.querySelectorAll('[data-gt-notif-item]').forEach((item) => {
            item.addEventListener('click', (event) => {
                if (event.target.closest('.gt-notif-select') || event.target.closest('.gt-notif-delete-one')) {
                    event.preventDefault();
                    return;
                }
                const id = parseInt(item.dataset.id ?? '', 10);
                if (!Number.isNaN(id)) {
                    markRead(id);
                }
                closeAllMenus();
            });
        });

        if (isSuperadmin) {
            list.querySelectorAll('.gt-notif-delete-one').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    const id = btn.dataset.id;
                    if (!id) {
                        return;
                    }
                    if (!confirm('Bu bildirimin herkeste gorunen kopyalari silinsin mi?')) {
                        return;
                    }

                    fetchJson(withId(SIL_HERKESTEN_URL_TEMPLATE, id), { method: 'DELETE' })
                        .then(() => fetchBildirimler())
                        .catch(() => {});
                });
            });
        }
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

    function renderAll(data) {
        const yeniOkunmamis = data.okunmamis ?? 0;
        const bildirimler = data.bildirimler ?? [];

        roots.forEach((root) => {
            const badge = root.querySelector('[data-gt-notif-badge]');
            if (badge) {
                if (yeniOkunmamis > 0) {
                    badge.textContent = yeniOkunmamis > 99 ? '99+' : String(yeniOkunmamis);
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            }

            renderList(root, bildirimler);
        });

        if (yeniOkunmamis > sonOkunmamis && sonOkunmamis >= 0) {
            const yeni = bildirimler.find((b) => !b.is_read);
            if (yeni) {
                showBrowserNotif(yeni);
            }
        }

        sonOkunmamis = yeniOkunmamis;
    }

    function fetchBildirimler() {
        fetch(POLL_URL, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((data) => renderAll(data))
            .catch(() => {});
    }

    function bindRoot(root) {
        if (root.dataset.gtNotifReady === '1') {
            return;
        }
        root.dataset.gtNotifReady = '1';
        roots.add(root);

        const menu = root.querySelector('[data-gt-notif-menu]');
        const toggle = root.querySelector('[data-gt-notif-toggle]');
        const markAllBtn = root.querySelector('[data-gt-notif-mark-all]');
        const deleteSelectedBtn = root.querySelector('[data-gt-notif-delete-selected]');
        const deleteAllBtn = root.querySelector('[data-gt-notif-delete-all]');

        if (toggle && menu) {
            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const willOpen = menu.hidden;
                closeAllMenus();
                menu.hidden = !willOpen;
                toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        }

        if (markAllBtn) {
            markAllBtn.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                fetchJson(HEPSINI_URL, { method: 'POST' })
                    .then(() => {
                        sonOkunmamis = 0;
                        fetchBildirimler();
                    })
                    .catch(() => {});
            });
        }

        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const ids = Array.from(root.querySelectorAll('.gt-notif-select:checked'))
                    .map((el) => parseInt(el.dataset.id ?? '', 10))
                    .filter((id) => !Number.isNaN(id));

                if (!ids.length) {
                    alert('Once en az bir bildirim secin.');
                    return;
                }
                if (!confirm(`Secilen ${ids.length} bildirimin herkesteki kopyalari silinsin mi?`)) {
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

        if (deleteAllBtn) {
            deleteAllBtn.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (!confirm('Bu listedeki bildirimlerin herkesteki tum kopyalari silinsin mi?')) {
                    return;
                }

                fetchJson(SIL_HEPSI_HERKESTEN_URL, { method: 'POST' })
                    .then(() => fetchBildirimler())
                    .catch(() => {});
            });
        }
    }

    function init() {
        document.querySelectorAll('[data-gt-notif]').forEach((root) => bindRoot(root));

        if (!pollTimer) {
            fetchBildirimler();
            pollTimer = window.setInterval(fetchBildirimler, 30000);
        }

        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-gt-notif]')) {
            return;
        }
        closeAllMenus();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllMenus();
        }
    });

    window.__gtNotifCenter = {
        init,
        refresh: fetchBildirimler,
        closeAll: closeAllMenus,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endonce

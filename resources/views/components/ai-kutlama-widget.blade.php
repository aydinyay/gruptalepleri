@props(['campaign' => null])

@php
    $activeCampaign = $campaign ?? ($_activeAiCelebration ?? null);
@endphp

@if($activeCampaign)
    @php
        $widgetId = 'ai-kutlama-widget-' . $activeCampaign->id;
        $mode = $activeCampaign->display_mode ?: 'banner';
    @endphp

    @once
        <style>
            .ai-kutlama-widget { z-index: 1080; }
            .ai-kutlama-surface {
                border-radius: 14px;
                border: 1px solid rgba(255, 255, 255, 0.16);
                background: linear-gradient(135deg, #1a1a2e 0%, #21406d 100%);
                color: #fff;
                box-shadow: 0 14px 36px rgba(0, 0, 0, 0.22);
                overflow: hidden;
            }
            .ai-kutlama-media {
                width: 100%;
                height: 130px;
                object-fit: cover;
                border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            }
            .ai-kutlama-body { padding: .9rem 1rem 1rem; }
            .ai-kutlama-title {
                font-weight: 700;
                font-size: 1rem;
                margin-bottom: .35rem;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
            .ai-kutlama-message {
                font-size: .86rem;
                color: rgba(255,255,255,.88);
                line-height: 1.42;
                margin-bottom: .55rem;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
            .ai-kutlama-head { min-width: 0; }
            .ai-kutlama-event {
                overflow-wrap: anywhere;
                word-break: break-word;
                line-height: 1.28;
            }
            .ai-kutlama-brand {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                border-radius: 999px;
                font-size: .7rem;
                font-weight: 700;
                background: #e94560;
                color: #fff;
            }
            .ai-kutlama-close {
                border: 0;
                background: transparent;
                color: rgba(255,255,255,.78);
                font-size: 1rem;
                line-height: 1;
            }
            .ai-kutlama-close:hover { color: #fff; }

            .ai-kutlama-banner-wrap {
                position: sticky;
                top: 0;
                padding: .6rem;
                background: rgba(8, 10, 24, 0.28);
                backdrop-filter: blur(2px);
            }
            .ai-kutlama-popup-wrap {
                position: fixed;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(9, 10, 20, .64);
                padding: 1rem;
            }
            .ai-kutlama-popup-wrap .ai-kutlama-surface { width: min(640px, 100%); }
            .ai-kutlama-card-wrap {
                position: fixed;
                right: 14px;
                bottom: 14px;
                width: min(360px, calc(100% - 28px));
            }
            @media (max-width: 575.98px) {
                .ai-kutlama-popup-wrap { align-items: flex-end; }
                .ai-kutlama-popup-wrap .ai-kutlama-surface { margin-bottom: .75rem; }
            }
        </style>
    @endonce

    <div
        id="{{ $widgetId }}"
        class="ai-kutlama-widget {{ $mode === 'popup' ? 'ai-kutlama-popup-wrap' : ($mode === 'card' ? 'ai-kutlama-card-wrap' : 'ai-kutlama-banner-wrap') }}"
        data-campaign-id="{{ $activeCampaign->id }}"
        data-mode="{{ $mode }}"
        data-auth="{{ auth()->check() ? '1' : '0' }}"
        data-url-seen="{{ route('ai-kutlama.seen', $activeCampaign) }}"
        data-url-closed="{{ route('ai-kutlama.closed', $activeCampaign) }}"
        data-url-clicked="{{ route('ai-kutlama.clicked', $activeCampaign) }}"
    >
        <div class="ai-kutlama-surface">
            @if($activeCampaign->image_path)
                <img src="{{ $activeCampaign->image_path }}" alt="Kutlama Görseli" class="ai-kutlama-media">
            @endif
            <div class="ai-kutlama-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="d-flex align-items-center gap-2 ai-kutlama-head">
                        <span class="ai-kutlama-brand">GT</span>
                        <span class="small text-white-50 ai-kutlama-event">{{ $activeCampaign->event_name }}</span>
                    </div>
                    <button type="button" class="ai-kutlama-close" data-ai-close aria-label="Kapat">&times;</button>
                </div>
                <div class="ai-kutlama-title">{{ $activeCampaign->title }}</div>
                <div class="ai-kutlama-message">{{ $activeCampaign->message }}</div>
                @if($activeCampaign->cta_text)
                    <a href="{{ $activeCampaign->cta_url ?: '#' }}" class="btn btn-sm btn-danger" data-ai-click>{{ $activeCampaign->cta_text }}</a>
                @endif
            </div>
        </div>
    </div>

    <script>
    (function () {
        const root = document.getElementById(@json($widgetId));
        if (!root) return;

        const campaignId = root.dataset.campaignId;
        const isAuth = root.dataset.auth === '1';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const guestLocalKey = 'gtp_ai_seen_' + campaignId;

        if (!isAuth && localStorage.getItem(guestLocalKey) === '1') {
            root.remove();
            return;
        }

        const request = (url) => fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            keepalive: true,
        }).catch(() => {});

        request(root.dataset.urlSeen);
        if (!isAuth) {
            localStorage.setItem(guestLocalKey, '1');
        }

        const closeBtn = root.querySelector('[data-ai-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                request(root.dataset.urlClosed);
                if (!isAuth) {
                    localStorage.setItem(guestLocalKey, '1');
                }
                root.remove();
            });
        }

        const ctaBtn = root.querySelector('[data-ai-click]');
        if (ctaBtn) {
            ctaBtn.addEventListener('click', function () {
                request(root.dataset.urlClicked);
            });
        }
    })();
    </script>
@endif

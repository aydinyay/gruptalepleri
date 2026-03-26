<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.partials.theme-styles')
    <title>AI Kutlama Önizleme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            background: #0f1225;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
        }
        .preview-shell {
            min-height: 100vh;
            padding: 1.25rem;
        }
        .preview-head {
            width: min(1140px, 100%);
            margin: 0 auto 1rem;
            padding: .9rem 1rem;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 12px;
            background: rgba(18, 23, 45, .75);
        }
        .preview-grid {
            width: min(1140px, 100%);
            margin: 0 auto;
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }
        .mode-card {
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(17, 24, 46, .72);
        }
        .mode-title {
            font-size: .92rem;
            font-weight: 700;
            letter-spacing: .02em;
            padding: .8rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.12);
            color: #d9e0ff;
        }
        .mode-stage {
            padding: 1rem;
            min-height: 320px;
        }
        .mode-stage.banner-stage {
            background: rgba(8, 12, 26, .36);
        }
        .mode-stage.popup-stage {
            background: rgba(6, 8, 20, .74);
            display: grid;
            place-items: center;
        }
        .mode-stage.card-stage {
            background: rgba(8, 10, 22, .62);
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
        }

        .ai-surface {
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,.16);
            background: linear-gradient(135deg, #1a1a2e 0%, #21406d 100%);
            color: #fff;
            box-shadow: 0 14px 36px rgba(0,0,0,.26);
            overflow: hidden;
            width: 100%;
        }
        .ai-surface.popup { width: min(640px, 100%); }
        .ai-surface.card { width: min(360px, 100%); }

        .ai-media {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-bottom: 1px solid rgba(255,255,255,.18);
            background: #121826;
        }
        .ai-body { padding: .9rem 1rem 1rem; }
        .ai-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .6rem;
            margin-bottom: .45rem;
        }
        .ai-head {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .ai-brand {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #e94560;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            font-weight: 700;
            flex: 0 0 auto;
        }
        .ai-event {
            color: rgba(255,255,255,.74);
            font-size: .84rem;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .ai-close {
            border: 0;
            background: transparent;
            color: rgba(255,255,255,.78);
            font-size: 1rem;
            line-height: 1;
            padding: 0;
            flex: 0 0 auto;
        }
        .ai-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: .35rem;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .ai-message {
            font-size: .88rem;
            color: rgba(255,255,255,.9);
            margin-bottom: .65rem;
            line-height: 1.42;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .ai-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .45rem .75rem;
        }
        .ai-meta {
            color: rgba(255,255,255,.74);
            font-size: .8rem;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .badge-current {
            border: 1px solid rgba(255,255,255,.26);
            color: #dbe4ff;
            background: rgba(255,255,255,.08);
            font-size: .75rem;
            border-radius: 999px;
            padding: .2rem .55rem;
            margin-left: .5rem;
        }
        @media (min-width: 1100px) {
            .preview-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .mode-stage.card-stage { justify-content: center; }
        }
    </style>
</head>
<body>
<x-navbar-superadmin active="ai-kutlama" :show-celebration-widget="false" />
@php
    $modeLabel = match($campaign->display_mode) {
        'popup' => 'Popup',
        'card' => 'Card',
        default => 'Banner',
    };
    $publishText = ($campaign->publish_starts_at?->format('d.m.Y H:i') ?: '-') . ' - ' . ($campaign->publish_ends_at?->format('d.m.Y H:i') ?: '-');
@endphp

<div class="preview-shell">
    <div class="preview-head">
        <div class="fw-semibold mb-1">AI Kutlama Çoklu Önizleme</div>
        <div class="small text-white-50">
            Etkinlik: {{ $campaign->event_name }}
            @if($campaign->event_date)
                · Tarih: {{ $campaign->event_date->format('d.m.Y') }}
            @endif
            <span class="badge-current">Kayıttaki mod: {{ $modeLabel }}</span>
        </div>
    </div>

    <div class="preview-grid">
        <section class="mode-card">
            <div class="mode-title">Top Banner Önizleme</div>
            <div class="mode-stage banner-stage">
                <div class="ai-surface">
                    @if($campaign->image_path)
                        <img src="{{ $campaign->image_path }}" alt="AI Kutlama Görseli" class="ai-media">
                    @endif
                    <div class="ai-body">
                        <div class="ai-row">
                            <div class="ai-head">
                                <span class="ai-brand">GT</span>
                                <span class="ai-event">{{ $campaign->event_name }}</span>
                            </div>
                            <button type="button" class="ai-close" aria-label="Kapat">&times;</button>
                        </div>
                        <div class="ai-title">{{ $campaign->title ?: 'Başlık boş' }}</div>
                        <div class="ai-message">{{ $campaign->message ?: 'Mesaj boş' }}</div>
                        <div class="ai-footer">
                            @if($campaign->cta_text)
                                <span class="btn btn-danger btn-sm disabled">{{ $campaign->cta_text }}</span>
                            @endif
                            <span class="ai-meta">Yayın: {{ $publishText }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mode-card">
            <div class="mode-title">Popup Önizleme</div>
            <div class="mode-stage popup-stage">
                <div class="ai-surface popup">
                    @if($campaign->image_path)
                        <img src="{{ $campaign->image_path }}" alt="AI Kutlama Görseli" class="ai-media">
                    @endif
                    <div class="ai-body">
                        <div class="ai-row">
                            <div class="ai-head">
                                <span class="ai-brand">GT</span>
                                <span class="ai-event">{{ $campaign->event_name }}</span>
                            </div>
                            <button type="button" class="ai-close" aria-label="Kapat">&times;</button>
                        </div>
                        <div class="ai-title">{{ $campaign->title ?: 'Başlık boş' }}</div>
                        <div class="ai-message">{{ $campaign->message ?: 'Mesaj boş' }}</div>
                        <div class="ai-footer">
                            @if($campaign->cta_text)
                                <span class="btn btn-danger btn-sm disabled">{{ $campaign->cta_text }}</span>
                            @endif
                            <span class="ai-meta">Yayın: {{ $publishText }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mode-card">
            <div class="mode-title">Dashboard Card Önizleme</div>
            <div class="mode-stage card-stage">
                <div class="ai-surface card">
                    @if($campaign->image_path)
                        <img src="{{ $campaign->image_path }}" alt="AI Kutlama Görseli" class="ai-media">
                    @endif
                    <div class="ai-body">
                        <div class="ai-row">
                            <div class="ai-head">
                                <span class="ai-brand">GT</span>
                                <span class="ai-event">{{ $campaign->event_name }}</span>
                            </div>
                            <button type="button" class="ai-close" aria-label="Kapat">&times;</button>
                        </div>
                        <div class="ai-title">{{ $campaign->title ?: 'Başlık boş' }}</div>
                        <div class="ai-message">{{ $campaign->message ?: 'Mesaj boş' }}</div>
                        <div class="ai-footer">
                            @if($campaign->cta_text)
                                <span class="btn btn-danger btn-sm disabled">{{ $campaign->cta_text }}</span>
                            @endif
                            <span class="ai-meta">Yayın: {{ $publishText }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@include('admin.partials.theme-script')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

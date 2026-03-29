<!DOCTYPE html>
<html lang="tr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sosyal Medya Stüdyosu — GrupTalepleri</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --sm-bg: #f5f6fa;
            --sm-card: #fff;
            --sm-border: #e0e4ed;
            --sm-text: #1a1d2e;
            --sm-muted: #6b7280;
            --sm-accent: #4f46e5;
            --sm-accent2: #7c3aed;
            --fb: #1877F2; --ig: #E1306C; --li: #0A66C2; --x: #14171A;
        }
        [data-bs-theme="dark"] {
            --sm-bg: #0f1117; --sm-card: #1a1d2e; --sm-border: #2d3147;
            --sm-text: #e8eaf0; --sm-muted: #9ca3af; --x: #fff;
        }
        * { box-sizing: border-box; }
        body { background: var(--sm-bg); color: var(--sm-text); font-family: 'Segoe UI', system-ui, sans-serif; }

        /* ── Page layout ── */
        .sm-page { max-width: 1280px; margin: 0 auto; padding: 0 16px 60px; }

        /* ── Banner ── */
        .sm-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 14px; padding: 16px 22px; margin: 20px 0 18px;
            color: #fff; display: flex; align-items: center; gap: 14px;
        }
        .sm-banner .banner-icon { font-size: 1.8rem; flex-shrink: 0; }
        .sm-banner .banner-msg { flex: 1; font-size: 0.95rem; line-height: 1.5; }
        .sm-banner .banner-btn {
            background: rgba(255,255,255,.2); border: 1px solid rgba(255,255,255,.4);
            color: #fff; border-radius: 8px; padding: 7px 16px; font-size: 0.85rem;
            cursor: pointer; white-space: nowrap; transition: background .2s;
        }
        .sm-banner .banner-btn:hover { background: rgba(255,255,255,.3); }

        /* ── Tabs ── */
        .sm-tabs { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 2px solid var(--sm-border); }
        .sm-tab-btn {
            background: none; border: none; padding: 10px 20px; font-size: 0.9rem;
            font-weight: 500; color: var(--sm-muted); cursor: pointer; border-bottom: 3px solid transparent;
            margin-bottom: -2px; transition: all .2s; border-radius: 6px 6px 0 0;
        }
        .sm-tab-btn.active { color: var(--sm-accent); border-bottom-color: var(--sm-accent); }
        .sm-tab-btn:hover:not(.active) { background: var(--sm-border); color: var(--sm-text); }

        /* ── Tab panes ── */
        .sm-pane { display: none; }
        .sm-pane.active { display: block; }

        /* ── Card ── */
        .sm-card {
            background: var(--sm-card); border: 1px solid var(--sm-border);
            border-radius: 14px; padding: 20px;
        }

        /* ── Platform buttons ── */
        .platform-btn {
            width: 48px; height: 48px; border-radius: 12px; border: 2px solid var(--sm-border);
            background: var(--sm-card); cursor: pointer; font-size: 1.2rem;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s; color: var(--sm-muted);
        }
        .platform-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.15); }
        .platform-btn.active[data-p="facebook"]  { background: var(--fb); color: #fff; border-color: var(--fb); }
        .platform-btn.active[data-p="instagram"] { background: var(--ig); color: #fff; border-color: var(--ig); }
        .platform-btn.active[data-p="linkedin"]  { background: var(--li); color: #fff; border-color: var(--li); }
        .platform-btn.active[data-p="x"]         { background: var(--x); color: #fff; border-color: var(--x); }

        /* ── Toolbar ── */
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .toolbar select, .toolbar input {
            background: var(--sm-card); color: var(--sm-text);
            border: 1.5px solid var(--sm-border); border-radius: 10px;
            padding: 10px 14px; font-size: 0.875rem;
        }
        .toolbar select:focus, .toolbar input:focus {
            outline: none; border-color: var(--sm-accent);
            box-shadow: 0 0 0 3px rgba(79,70,229,.15);
        }
        .toolbar input.konu-input { flex: 1; min-width: 180px; }
        .toolbar .gorsel-toggle { display: flex; align-items: center; gap: 6px; font-size: 0.85rem; cursor: pointer; }
        .btn-uret {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff; border: none; border-radius: 10px;
            padding: 10px 22px; font-weight: 600; font-size: 0.9rem;
            cursor: pointer; display: flex; align-items: center; gap: 8px;
            transition: all .2s; white-space: nowrap;
        }
        .btn-uret:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(79,70,229,.4); }
        .btn-uret:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        /* ── Result Zone ── */
        .result-header { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
        .platform-badge {
            display: flex; align-items: center; gap: 7px; padding: 5px 14px;
            border-radius: 20px; font-weight: 600; font-size: 0.82rem; color: #fff;
        }
        .badge-facebook  { background: var(--fb); }
        .badge-instagram { background: var(--ig); }
        .badge-linkedin  { background: var(--li); }
        .badge-x         { background: var(--x); }
        .ai-stars { color: #f59e0b; font-size: 0.95rem; }
        .char-meter { flex: 1; min-width: 120px; }
        .char-meter .meter-bar { height: 6px; background: var(--sm-border); border-radius: 3px; overflow: hidden; }
        .char-meter .meter-fill { height: 100%; border-radius: 3px; transition: width .3s, background .3s; background: #22c55e; }
        .char-meter .meter-text { font-size: 0.75rem; color: var(--sm-muted); margin-top: 2px; }
        .icerik-box {
            white-space: pre-wrap; font-size: 0.9rem; line-height: 1.7;
            border: 1.5px solid var(--sm-border); border-radius: 10px;
            padding: 16px; min-height: 120px; background: var(--sm-bg);
            color: var(--sm-text); width: 100%;
        }
        .icerik-box[contenteditable="true"]:focus { outline: none; border-color: var(--sm-accent); }
        .result-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
        .result-actions button {
            background: var(--sm-card); color: var(--sm-text);
            border: 1.5px solid var(--sm-border); border-radius: 8px;
            padding: 7px 14px; font-size: 0.83rem; cursor: pointer;
            display: flex; align-items: center; gap: 6px; transition: all .15s;
        }
        .result-actions button:hover { border-color: var(--sm-accent); color: var(--sm-accent); }
        .result-actions .btn-save-primary {
            background: var(--sm-accent); color: #fff; border-color: var(--sm-accent);
        }
        .result-actions .btn-save-primary:hover { background: var(--sm-accent2); border-color: var(--sm-accent2); color: #fff; }

        /* ── Görsel alanı ── */
        .gorsel-zone { margin-top: 14px; }
        .gorsel-prompt-row { display: flex; gap: 8px; }
        .gorsel-prompt-row input {
            flex: 1; background: var(--sm-card); color: var(--sm-text);
            border: 1.5px solid var(--sm-border); border-radius: 10px; padding: 10px 14px; font-size: 0.875rem;
        }
        .gorsel-prompt-row input:focus { outline: none; border-color: var(--sm-accent); }
        .btn-gorsel {
            background: var(--sm-card); color: var(--sm-text);
            border: 1.5px solid var(--sm-border); border-radius: 10px;
            padding: 10px 16px; font-size: 0.85rem; cursor: pointer;
            display: flex; align-items: center; gap: 6px; white-space: nowrap;
            transition: all .2s;
        }
        .btn-gorsel:hover { border-color: var(--ig); color: var(--ig); }
        .gorsel-preview { margin-top: 12px; }
        .gorsel-preview img { max-width: 100%; border-radius: 10px; border: 1.5px solid var(--sm-border); }
        .gorsel-preview .gorsel-actions { margin-top: 8px; display: flex; gap: 8px; }

        /* ── Revizyon Chat ── */
        .rev-section { margin-top: 20px; border-top: 1px solid var(--sm-border); padding-top: 18px; }
        .rev-title { font-size: 0.85rem; font-weight: 600; color: var(--sm-muted); margin-bottom: 10px; }
        .rev-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
        .rev-chip {
            background: var(--sm-bg); border: 1.5px solid var(--sm-border);
            color: var(--sm-text); border-radius: 20px; padding: 5px 14px;
            font-size: 0.8rem; cursor: pointer; transition: all .15s;
        }
        .rev-chip:hover { border-color: var(--sm-accent); color: var(--sm-accent); background: rgba(79,70,229,.06); }
        .rev-bubbles { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; max-height: 280px; overflow-y: auto; padding-right: 4px; }
        .bubble { display: flex; gap: 10px; }
        .bubble.user { flex-direction: row-reverse; }
        .bubble-content {
            max-width: 78%; padding: 10px 14px; border-radius: 14px;
            font-size: 0.875rem; line-height: 1.55; white-space: pre-wrap;
        }
        .bubble.user .bubble-content { background: var(--sm-accent); color: #fff; border-radius: 14px 14px 4px 14px; }
        .bubble.ai .bubble-content { background: var(--sm-border); color: var(--sm-text); border-radius: 14px 14px 14px 4px; }
        .bubble-avatar {
            width: 30px; height: 30px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 0.75rem;
        }
        .bubble.user .bubble-avatar { background: var(--sm-accent2); color: #fff; }
        .bubble.ai .bubble-avatar { background: var(--sm-border); color: var(--sm-muted); }
        .rev-input-row { display: flex; gap: 8px; }
        .rev-input-row input {
            flex: 1; background: var(--sm-card); color: var(--sm-text);
            border: 1.5px solid var(--sm-border); border-radius: 10px; padding: 10px 14px;
            font-size: 0.875rem;
        }
        .rev-input-row input:focus { outline: none; border-color: var(--sm-accent); }
        .btn-rev-send {
            background: var(--sm-accent); color: #fff; border: none;
            border-radius: 10px; padding: 10px 16px; cursor: pointer;
            display: flex; align-items: center; gap: 6px; font-size: 0.875rem;
            transition: background .2s;
        }
        .btn-rev-send:hover { background: var(--sm-accent2); }
        .btn-rev-send:disabled { opacity: .6; cursor: not-allowed; }

        /* ── Takvim (Kanban) ── */
        .kanban { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        @media (max-width: 900px) { .kanban { grid-template-columns: 1fr; } }
        .kanban-col { }
        .kanban-col-header {
            font-size: 0.85rem; font-weight: 700; padding: 10px 14px;
            border-radius: 10px 10px 0 0; display: flex; align-items: center; justify-content: space-between;
        }
        .col-taslak-h  { background: rgba(234,179,8,.15);  color: #92400e; border: 1px solid rgba(234,179,8,.3); }
        .col-planli-h  { background: rgba(79,70,229,.12);  color: #3730a3; border: 1px solid rgba(79,70,229,.25); }
        .col-gond-h    { background: rgba(34,197,94,.12);  color: #14532d; border: 1px solid rgba(34,197,94,.25); }
        [data-bs-theme="dark"] .col-taslak-h { color: #fde68a; }
        [data-bs-theme="dark"] .col-planli-h { color: #a5b4fc; }
        [data-bs-theme="dark"] .col-gond-h   { color: #86efac; }
        .kanban-cards { border: 1px solid var(--sm-border); border-top: none; border-radius: 0 0 10px 10px; min-height: 120px; padding: 8px; display: flex; flex-direction: column; gap: 8px; background: var(--sm-card); }
        .kanban-card {
            background: var(--sm-bg); border: 1px solid var(--sm-border);
            border-radius: 10px; padding: 12px; font-size: 0.83rem;
        }
        .kanban-card .card-platform { display: flex; align-items: center; gap: 6px; font-weight: 600; margin-bottom: 6px; font-size: 0.8rem; }
        .kanban-card .card-excerpt { color: var(--sm-text); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .kanban-card .card-date { color: var(--sm-muted); font-size: 0.75rem; margin-top: 6px; }
        .kanban-card .card-actions { display: flex; gap: 6px; margin-top: 8px; }
        .kanban-card .card-actions button {
            flex: 1; padding: 4px 8px; font-size: 0.75rem; border-radius: 6px;
            border: 1px solid var(--sm-border); background: var(--sm-card);
            color: var(--sm-muted); cursor: pointer; transition: all .15s;
        }
        .kanban-card .card-actions button:hover { border-color: var(--sm-accent); color: var(--sm-accent); }
        .kanban-filter { display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap; }
        .kanban-filter button {
            background: var(--sm-card); border: 1.5px solid var(--sm-border);
            border-radius: 20px; padding: 5px 14px; font-size: 0.8rem;
            cursor: pointer; transition: all .15s; color: var(--sm-text);
        }
        .kanban-filter button.active { border-color: var(--sm-accent); color: var(--sm-accent); background: rgba(79,70,229,.07); }

        /* ── Özel Günler ── */
        .ozel-gunler-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
        .ozel-gun-card {
            background: var(--sm-card); border: 1px solid var(--sm-border);
            border-radius: 14px; padding: 18px; transition: box-shadow .2s;
        }
        .ozel-gun-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        .ozel-gun-card .gun-header { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; }
        .ozel-gun-card .gun-icon { font-size: 1.5rem; flex-shrink: 0; }
        .ozel-gun-card .gun-name { font-weight: 600; font-size: 0.92rem; }
        .ozel-gun-card .gun-kalan { font-size: 0.8rem; margin-top: 2px; }
        .ozel-gun-card .gun-kalan.urgent { color: #ef4444; }
        .ozel-gun-card .gun-kalan.soon   { color: #f59e0b; }
        .ozel-gun-card .gun-kalan.normal { color: var(--sm-muted); }
        .ozel-gun-card .gun-meta { display: flex; gap: 8px; flex-wrap: wrap; margin: 8px 0; }
        .ozel-gun-card .gun-badge {
            font-size: 0.72rem; padding: 2px 10px; border-radius: 20px;
            background: rgba(79,70,229,.1); color: var(--sm-accent); font-weight: 500;
        }
        .ozel-gun-card .gun-aciklama { font-size: 0.82rem; color: var(--sm-muted); line-height: 1.5; margin-bottom: 10px; }
        .btn-hazirla {
            width: 100%; background: none; border: 1.5px solid var(--sm-accent);
            color: var(--sm-accent); border-radius: 8px; padding: 8px;
            font-size: 0.83rem; font-weight: 600; cursor: pointer; transition: all .2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-hazirla:hover { background: var(--sm-accent); color: #fff; }

        /* ── Plan modal ── */
        .planla-form { display: flex; flex-direction: column; gap: 12px; }
        .planla-form label { font-size: 0.85rem; font-weight: 500; }
        .planla-form input, .planla-form select {
            background: var(--sm-card); color: var(--sm-text);
            border: 1.5px solid var(--sm-border); border-radius: 8px; padding: 8px 12px; font-size: 0.875rem;
        }

        /* ── Paylaş butonları ── */
        .paylas-row { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--sm-border); }
        .paylas-label { font-size: .75rem; font-weight: 600; color: var(--sm-muted); white-space: nowrap; }
        .paylas-btn {
            display: flex; align-items: center; gap: 6px;
            border: none; border-radius: 8px; padding: 7px 14px;
            font-size: .82rem; font-weight: 600; cursor: pointer; color: #fff;
            transition: opacity .2s, transform .15s;
        }
        .paylas-btn:hover { opacity: .88; transform: translateY(-1px); }
        .paylas-x  { background: #14171A; }
        .paylas-fb { background: #1877F2; }
        .paylas-li { background: #0A66C2; }
        .paylas-ig { background: linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); }

        /* ── Görsel mod tabs ── */
        .gorsel-mod-tabs { display: flex; gap: 6px; }
        .gorsel-mod-btn {
            flex: 1; padding: 9px 14px; border-radius: 10px; font-size: 0.83rem; font-weight: 600;
            border: 1.5px solid var(--sm-border); background: var(--sm-card); color: var(--sm-muted);
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 7px;
            transition: all .2s;
        }
        .gorsel-mod-btn.active { background: var(--sm-accent); color: #fff; border-color: var(--sm-accent); }
        .gorsel-mod-btn:not(.active):hover { border-color: var(--sm-accent); color: var(--sm-accent); }
        .gorsel-panel { margin-top: 10px; }

        /* ── Ebat bilgi kartı ── */
        .ebat-kart {
            background: var(--sm-bg); border: 1.5px solid var(--sm-border);
            border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;
        }
        .ebat-platform { font-size: .78rem; font-weight: 700; color: var(--sm-muted); margin-bottom: 8px; letter-spacing: .04em; text-transform: uppercase; }
        .ebat-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .ebat-item {
            background: var(--sm-card); border: 1.5px solid var(--sm-border);
            border-radius: 8px; padding: 7px 12px; font-size: .78rem; line-height: 1.5;
        }
        .ebat-item.aktif { border-color: var(--sm-accent); background: rgba(79,70,229,.07); }
        .ebat-item .ebat-format { font-weight: 700; color: var(--sm-text); display: block; }
        .ebat-item .ebat-px { color: var(--sm-accent); font-weight: 600; }
        .ebat-item .ebat-oran { color: var(--sm-muted); font-size: .72rem; }
        .ebat-ipucu { font-size: .75rem; color: var(--sm-muted); margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--sm-border); }

        /* ── Yükle alanı ── */
        .yukle-alan {
            border: 2px dashed var(--sm-border); border-radius: 12px; padding: 32px 20px;
            text-align: center; cursor: pointer; transition: all .2s; color: var(--sm-muted);
        }
        .yukle-alan:hover, .yukle-alan.drag-over {
            border-color: var(--sm-accent); background: rgba(79,70,229,.05); color: var(--sm-accent);
        }
        .yukle-alan i { font-size: 2rem; margin-bottom: 10px; display: block; }
        .yukle-baslik { font-weight: 600; font-size: 0.92rem; margin-bottom: 4px; }
        .yukle-alt { font-size: 0.78rem; }

        /* ── Loading spinner ── */
        .spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Toast ── */
        .sm-toast {
            position: fixed; bottom: 24px; right: 24px; z-index: 9999;
            background: #1a1d2e; color: #fff; padding: 12px 20px; border-radius: 10px;
            font-size: 0.875rem; box-shadow: 0 4px 20px rgba(0,0,0,.3);
            transform: translateY(80px); opacity: 0; transition: all .3s;
        }
        .sm-toast.show { transform: translateY(0); opacity: 1; }
        .sm-toast.success { border-left: 4px solid #22c55e; }
        .sm-toast.error   { border-left: 4px solid #ef4444; }
        .sm-toast.warning { border-left: 4px solid #f59e0b; }

        /* ── Empty state ── */
        .empty-state { text-align: center; padding: 40px 20px; color: var(--sm-muted); }
        .empty-state i { font-size: 2.5rem; margin-bottom: 10px; display: block; }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar .platform-btns { justify-content: center; }
            .toolbar input.konu-input { width: 100%; }
            .sm-banner { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<x-navbar-superadmin active="sosyal-medya" />

<div class="sm-page">

    {{-- ── AI Öneri Banner ── --}}
    <div class="sm-banner" id="smBanner">
        <div class="banner-icon">
            @if(($oneri['tip'] ?? '') === 'ozel_gun') 📅
            @elseif(($oneri['tip'] ?? '') === 'hatirlat') 💡
            @elseif(($oneri['tip'] ?? '') === 'istatistik') 📊
            @else 🚀
            @endif
        </div>
        <div class="banner-msg">{!! $oneri['mesaj'] ?? 'Bugün ne paylaşalım?' !!}</div>
        @if(!empty($oneri['konu']))
        <button class="banner-btn" onclick="bannerHazirla()">
            <i class="fas fa-wand-magic-sparkles me-1"></i> Hemen Üret
        </button>
        @endif
    </div>

    {{-- ── Tab Nav ── --}}
    <div class="sm-tabs">
        <button class="sm-tab-btn active" onclick="switchTab('studyo', this)">
            <i class="fas fa-magic me-1"></i> Stüdyo
        </button>
        <button class="sm-tab-btn" onclick="switchTab('takvim', this)">
            <i class="fas fa-calendar-days me-1"></i> Takvim
        </button>
        <button class="sm-tab-btn" onclick="switchTab('ozel-gunler', this)">
            <i class="fas fa-star me-1"></i> Özel Günler
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 1 — STÜDYO                                                --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="sm-pane active" id="pane-studyo">

        {{-- Zone 1: Toolbar --}}
        <div class="sm-card mb-3">
            <div class="toolbar">
                {{-- Platform Buttons --}}
                <div class="platform-btns d-flex gap-2">
                    <button class="platform-btn active" data-p="facebook" onclick="setPlatform('facebook', this)" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </button>
                    <button class="platform-btn" data-p="instagram" onclick="setPlatform('instagram', this)" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </button>
                    <button class="platform-btn" data-p="linkedin" onclick="setPlatform('linkedin', this)" title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </button>
                    <button class="platform-btn" data-p="x" onclick="setPlatform('x', this)" title="X (Twitter)">
                        <i class="fab fa-x-twitter"></i>
                    </button>
                </div>

                {{-- Format --}}
                <select id="formatSel" onchange="updateLimit()">
                    <option value="durum">Durum / Gönderi</option>
                </select>

                {{-- Konu --}}
                <input type="text" class="konu-input" id="konuInput" placeholder="Konu veya tema… (AI önerir)" maxlength="300">

                {{-- Ton --}}
                <select id="tonSel">
                    <option value="profesyonel">Profesyonel</option>
                    <option value="samimi">Samimi</option>
                    <option value="ilham_verici">İlham Verici</option>
                    <option value="bilgilendirici">Bilgilendirici</option>
                    <option value="eglenceli">Eğlenceli</option>
                </select>

                {{-- Görsel toggle --}}
                <label class="gorsel-toggle">
                    <input type="checkbox" id="gorselToggle">
                    <i class="fas fa-image"></i> Görsel İste
                </label>

                {{-- Üret button --}}
                <button class="btn-uret" id="btnUret" onclick="uretIcerik()">
                    <i class="fas fa-wand-magic-sparkles"></i> <span id="btnUretTxt">İçerik Üret</span>
                </button>
            </div>

            {{-- Özel not --}}
            <div class="mt-2">
                <input type="text" id="ozelNot" class="w-100"
                    style="background:var(--sm-bg);border:1.5px solid var(--sm-border);border-radius:8px;padding:8px 12px;font-size:0.82rem;color:var(--sm-text);"
                    placeholder="Özel not (isteğe bağlı — örn: kampanya kodu ekle, emoji kullanma)">
            </div>
        </div>

        {{-- Zone 2: Sonuç --}}
        <div class="sm-card mb-3" id="zoneResult" style="display:none;">
            <div class="result-header">
                <span class="platform-badge" id="resultBadge">
                    <i class="fab fa-facebook-f"></i> Facebook
                </span>
                <div class="ai-stars" id="aiStars"></div>
                <div class="char-meter" id="charMeter">
                    <div class="meter-bar"><div class="meter-fill" id="meterFill" style="width:0%"></div></div>
                    <div class="meter-text" id="meterText">0 / 63.206</div>
                </div>
            </div>

            <div class="icerik-box" id="icerikBox" contenteditable="true"></div>

            <div class="result-actions">
                <button onclick="kopyala()"><i class="fas fa-copy"></i> Kopyala</button>
                <button onclick="yenidenUret()"><i class="fas fa-rotate-right"></i> Yeniden Üret</button>
                <button onclick="kaydetTaslak()" class="btn-save-primary"><i class="fas fa-floppy-disk"></i> Taslak Kaydet</button>
                <button onclick="showPlanlaModal()"><i class="fas fa-calendar-plus"></i> Planla</button>
            </div>

            {{-- Paylaş Butonları --}}
            <div class="paylas-row" id="paralasRow" style="display:none;">
                <span class="paylas-label">Paylaş:</span>
                <button class="paylas-btn paylas-x"        id="btnPaylasX"  onclick="paylasX()">
                    <i class="fab fa-x-twitter"></i> X'te Paylaş
                </button>
                <button class="paylas-btn paylas-fb"       id="btnPaylasFb" onclick="paylasFb()">
                    <i class="fab fa-facebook-f"></i> Facebook
                </button>
                <button class="paylas-btn paylas-li"       id="btnPaylasLi" onclick="paylasLi()">
                    <i class="fab fa-linkedin-in"></i> LinkedIn
                </button>
                <button class="paylas-btn paylas-ig"       id="btnPaylasIg" onclick="paylasIg()">
                    <i class="fab fa-instagram"></i> Instagram
                </button>
            </div>

            {{-- Görsel / Video Alanı --}}
            <div class="gorsel-zone" id="gorselZone" style="display:none;">

                {{-- Mod seçici --}}
                <div class="gorsel-mod-tabs mt-3">
                    <button class="gorsel-mod-btn active" id="modAiBtn" onclick="switchGorselMod('ai')">
                        <i class="fas fa-wand-magic-sparkles"></i> AI ile Üret
                    </button>
                    <button class="gorsel-mod-btn" id="modYukleBtn" onclick="switchGorselMod('yukle')">
                        <i class="fas fa-upload"></i> Görsel / Video Yükle
                    </button>
                </div>

                {{-- AI Üret Paneli --}}
                <div id="panelAi" class="gorsel-panel">
                    <div class="gorsel-prompt-row mt-2">
                        <input type="text" id="gorselPromptInput"
                            placeholder="Sahne / atmosfer tanımla — yazı, ekran, logo isteme"
                            oninput="gorselPromptUyar(this.value)">
                        <button class="btn-gorsel" id="btnGorsel" onclick="uretGorsel()">
                            <i class="fas fa-image"></i> <span id="btnGorselTxt">Üret</span>
                        </button>
                    </div>
                    <div id="gorselPromptUyari" style="display:none;font-size:.75rem;color:#f59e0b;margin-top:4px;padding:6px 10px;background:rgba(245,158,11,.08);border-radius:6px;">
                        ⚠️ "Ekran / monitör / dashboard / yazı" içeren promptlar AI'ın yanlış metin yazmasına yol açar.
                        Bunlar yerine sahne tanımla — örn: <em>"modern ofis, uçak penceresi, İstanbul Boğazı gün batımı"</em>
                    </div>
                </div>

                {{-- Yükle Paneli --}}
                <div id="panelYukle" class="gorsel-panel" style="display:none;">

                    {{-- Boyut Bilgi Kartı --}}
                    <div class="ebat-kart" id="ebatKart">
                        <div class="ebat-platform" id="ebatPlatform"></div>
                        <div class="ebat-grid" id="ebatGrid"></div>
                        <div class="ebat-ipucu" id="ebatIpucu"></div>
                    </div>

                    <div class="yukle-alan" id="yukleDrop"
                        onclick="document.getElementById('gorselDosya').click()"
                        ondragover="event.preventDefault();this.classList.add('drag-over')"
                        ondragleave="this.classList.remove('drag-over')"
                        ondrop="gorselDrop(event)">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="yukle-baslik">Görsel veya video yükle</div>
                        <div class="yukle-alt">Tıkla veya sürükle — JPG, PNG, GIF, MP4, MOV (max 50 MB)</div>
                        <input type="file" id="gorselDosya" accept="image/*,video/*" style="display:none" onchange="gorselDosyaSec(this)">
                    </div>
                    <div id="yukleVideoWrap" style="display:none;margin-top:12px;">
                        <video id="yukleVideoEl" controls style="max-width:100%;border-radius:10px;border:1.5px solid var(--sm-border);"></video>
                        <div style="font-size:.75rem;color:var(--sm-muted);margin-top:6px;">
                            <i class="fas fa-info-circle me-1"></i>Video sosyal medya uygulamasında yayınlanacak — buradan önizleme amaçlıdır.
                        </div>
                    </div>
                </div>

                {{-- Önizleme (AI veya yüklenen görsel) --}}
                <div id="gorselPreviewWrap" class="gorsel-preview" style="display:none;">
                    <img id="gorselPreviewImg" src="" alt="Görsel">
                    <div class="gorsel-actions">
                        <button onclick="gorselIndir()" style="background:var(--sm-card);border:1.5px solid var(--sm-border);border-radius:8px;padding:7px 14px;font-size:.83rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-download"></i> İndir
                        </button>
                        <button onclick="gorselTemizle()" style="background:var(--sm-card);border:1.5px solid #fca5a5;color:#ef4444;border-radius:8px;padding:7px 14px;font-size:.83rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-trash"></i> Kaldır
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- Zone 3: Revizyon Chat --}}
        <div class="sm-card" id="zoneRevizyon" style="display:none;">
            <div class="rev-title"><i class="fas fa-comment-dots me-1"></i> Revizyon</div>
            <div class="rev-chips">
                <button class="rev-chip" onclick="sendChip('Daha kısa yaz')">Daha kısa</button>
                <button class="rev-chip" onclick="sendChip('Emoji ekle ve daha çekici yap')">Emoji ekle</button>
                <button class="rev-chip" onclick="sendChip('CTA\'yı güçlendir')">CTA güçlendir</button>
                <button class="rev-chip" onclick="sendChip('Daha resmi ve profesyonel bir ton kullan')">Resmi yap</button>
                <button class="rev-chip" onclick="sendChip('İngilizce\'ye çevir')">İngilizce çevir</button>
                <button class="rev-chip" onclick="sendChip('Hashtag\'leri çıkar')">Hashtag çıkar</button>
                <button class="rev-chip" onclick="sendChip('Tamamen farklı bir versiyon yaz')">Farklı versiyon</button>
                <button class="rev-chip" onclick="sendChip('Daha uzun ve detaylı yaz')">Uzat</button>
            </div>
            <div class="rev-bubbles" id="revBubbles"></div>
            <div class="rev-input-row">
                <input type="text" id="revInput" placeholder="Revizyon isteğini yaz… (Enter = gönder)"
                    onkeydown="if(event.key==='Enter')sendRevizyon()">
                <button class="btn-rev-send" id="btnRev" onclick="sendRevizyon()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>

        {{-- Empty state --}}
        <div id="emptyState" class="empty-state sm-card">
            <i class="fas fa-wand-magic-sparkles" style="color:var(--sm-accent);opacity:.5;"></i>
            <div style="font-weight:600;margin-bottom:4px;">Stüdyo hazır</div>
            <div style="font-size:.85rem;">Platform, format ve konu seçin — AI içeriği oluştursin.</div>
        </div>

    </div>{{-- /pane-studyo --}}

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 2 — TAKVİM (Kanban)                                       --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="sm-pane" id="pane-takvim">
        <div class="mb-3">
            <div class="kanban-filter" id="kanbanFilter">
                <button class="active" onclick="filterKanban('tumu', this)"><i class="fas fa-th me-1"></i> Tümü</button>
                <button onclick="filterKanban('facebook', this)"><i class="fab fa-facebook-f me-1" style="color:var(--fb)"></i> Facebook</button>
                <button onclick="filterKanban('instagram', this)"><i class="fab fa-instagram me-1" style="color:var(--ig)"></i> Instagram</button>
                <button onclick="filterKanban('linkedin', this)"><i class="fab fa-linkedin-in me-1" style="color:var(--li)"></i> LinkedIn</button>
                <button onclick="filterKanban('x', this)"><i class="fab fa-x-twitter me-1"></i> X</button>
            </div>
        </div>

        <div id="takvimLoading" class="text-center py-4" style="display:none;">
            <div class="spinner" style="border-top-color:var(--sm-accent);border-color:var(--sm-border);width:28px;height:28px;"></div>
        </div>

        <div class="kanban" id="kanbanBoard">
            {{-- Taslak --}}
            <div class="kanban-col">
                <div class="kanban-col-header col-taslak-h">
                    <span><i class="fas fa-file-pen me-1"></i> Taslak</span>
                    <span class="badge bg-warning text-dark" id="taslakCount">0</span>
                </div>
                <div class="kanban-cards" id="colTaslak">
                    <div class="empty-state" style="padding:20px 10px;font-size:.82rem;"><i class="fas fa-file-pen" style="font-size:1.5rem;margin-bottom:6px;opacity:.3;"></i><div>Taslak yok</div></div>
                </div>
            </div>
            {{-- Planlandı --}}
            <div class="kanban-col">
                <div class="kanban-col-header col-planli-h">
                    <span><i class="fas fa-calendar-check me-1"></i> Planlandı</span>
                    <span class="badge" style="background:rgba(79,70,229,.2);color:var(--sm-accent);" id="planliCount">0</span>
                </div>
                <div class="kanban-cards" id="colPlanli">
                    <div class="empty-state" style="padding:20px 10px;font-size:.82rem;"><i class="fas fa-calendar-check" style="font-size:1.5rem;margin-bottom:6px;opacity:.3;"></i><div>Planlanmış içerik yok</div></div>
                </div>
            </div>
            {{-- Gönderildi --}}
            <div class="kanban-col">
                <div class="kanban-col-header col-gond-h">
                    <span><i class="fas fa-check-circle me-1"></i> Gönderildi</span>
                    <span class="badge bg-success" id="gondCount">0</span>
                </div>
                <div class="kanban-cards" id="colGond">
                    <div class="empty-state" style="padding:20px 10px;font-size:.82rem;"><i class="fas fa-check-circle" style="font-size:1.5rem;margin-bottom:6px;opacity:.3;"></i><div>Gönderilen içerik yok</div></div>
                </div>
            </div>
        </div>

        <div class="mt-3 text-muted" style="font-size:.8rem;"><i class="fas fa-info-circle me-1"></i> İçerikleri kopyalayarak doğrudan platformlara yapıştırabilirsiniz. Buffer entegrasyonu yakında geliyor.</div>
    </div>{{-- /pane-takvim --}}

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- TAB 3 — ÖZEL GÜNLER                                           --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="sm-pane" id="pane-ozel-gunler">
        <div class="ozel-gunler-grid">
            @forelse($yaklasanGunlerTumu as $gun)
            @php
                $kalanHam = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($gun->tarih)->startOfDay(), false);
                $kalan = (int) $kalanHam;
                $kalanLabel = $kalan <= 0 ? 'Bugün!' : "{$kalan} gün kaldı";
                $urgency = $kalan <= 7 ? 'urgent' : ($kalan <= 21 ? 'soon' : 'normal');
                $ikon = match($gun->kategori) {
                    'bayram'   => '🎉',
                    'festival' => '🎪',
                    'resmi'    => '🇹🇷',
                    'sezon'    => '☀️',
                    'turizm'   => '✈️',
                    'ulusal'   => '🌍',
                    'platform' => '🚀',
                    default    => '📅',
                };
                $hizmetLabel = match($gun->hizmet_baglantisi ?? '') {
                    'air_charter' => 'Air Charter',
                    'transfer'    => 'Transfer',
                    'leisure'     => 'Leisure',
                    default       => 'Platform',
                };
            @endphp
            <div class="ozel-gun-card">
                <div class="gun-header">
                    <span class="gun-icon">{{ $ikon }}</span>
                    <div>
                        <div class="gun-name">{{ $gun->ad }}</div>
                        <div class="gun-kalan {{ $urgency }}">
                            @if($kalan <= 0)
                                🔴 Bugün!
                            @else
                                {{ $kalanLabel }}
                            @endif
                            @php
                                $aylar = ['','Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
                                $dt = \Carbon\Carbon::parse($gun->tarih);
                                $tarihTR = $dt->day . ' ' . $aylar[$dt->month] . ' ' . $dt->year;
                            @endphp
                            — {{ $tarihTR }}
                        </div>
                    </div>
                </div>
                <div class="gun-meta">
                    <span class="gun-badge">{{ ucfirst($gun->kategori) }}</span>
                    <span class="gun-badge">{{ $hizmetLabel }}</span>
                    @if($gun->tekrar === 'yearly')
                        <span class="gun-badge" style="background:rgba(34,197,94,.1);color:#16a34a;">Yıllık</span>
                    @endif
                </div>
                @if($gun->aciklama)
                <div class="gun-aciklama">{{ Str::limit($gun->aciklama, 100) }}</div>
                @endif
                <button class="btn-hazirla" onclick="hazirlaGun({{ json_encode($gun->ad) }}, {{ json_encode($gun->aciklama ?? '') }})">
                    <i class="fas fa-wand-magic-sparkles"></i> İçerik Hazırla
                </button>
            </div>
            @empty
            <div class="empty-state sm-card" style="grid-column:1/-1;">
                <i class="fas fa-calendar-xmark"></i>
                <div>Önümüzdeki 12 ayda tanımlı özel gün bulunmuyor.</div>
            </div>
            @endforelse
        </div>
    </div>{{-- /pane-ozel-gunler --}}

</div>{{-- /sm-page --}}

{{-- ── Planla Modal ── --}}
<div class="modal fade" id="planlaModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="background:var(--sm-card);border:1px solid var(--sm-border);">
            <div class="modal-header" style="border-color:var(--sm-border);">
                <h6 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>İçeriği Planla</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="planla-form">
                    <div>
                        <label>Yayın Tarihi & Saati</label>
                        <input type="datetime-local" id="planlaDate" class="w-100 mt-1">
                    </div>
                    <div>
                        <label>Durum</label>
                        <select id="planlaStatus" class="w-100 mt-1">
                            <option value="planli">Planlandı</option>
                            <option value="gonderildi">Gönderildi</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-color:var(--sm-border);">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="kaydetPlanla()"
                    style="background:var(--sm-accent);border-color:var(--sm-accent);">Kaydet</button>
            </div>
        </div>
    </div>
</div>

{{-- Toast --}}
<div class="sm-toast" id="smToast"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
// ── Data from PHP ──────────────────────────────────────────────────────────
const LIMITLER       = @json($limitler);
const FORMAT_ETK     = @json($formatEtiketler);
const BANNER_ONERI   = @json($oneri);
const CSRF           = document.querySelector('meta[name="csrf-token"]').content;

// ── State ──────────────────────────────────────────────────────────────────
let curPlatform = 'facebook';
let curFormat   = 'durum';
let curLimit    = 63206;
let sonIcerik   = '';
let sonTema     = '';
let sonAiSkor   = 0;
let sonGorselP  = '';
let revGecmis   = [];
let gorselData  = null;
let takvimData  = null;
let kanbanFilter_aktif = 'tumu';

// ── Yardımcılar ────────────────────────────────────────────────────────────
async function postJson(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(data),
    });
    const text = await res.text();
    try {
        return JSON.parse(text);
    } catch {
        // Sunucu JSON değil HTML döndürdü (PHP hatası, 403, 419 vb.)
        const snippet = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 300);
        throw new Error(`HTTP ${res.status} — ${snippet}`);
    }
}

function toast(msg, type = 'success') {
    const el = document.getElementById('smToast');
    el.textContent = msg;
    el.className = `sm-toast show ${type}`;
    const dur = (type === 'warning' || type === 'error') ? 8000 : 3200;
    setTimeout(() => el.classList.remove('show'), dur);
}

function pfIcon(p) {
    return { facebook:'fab fa-facebook-f', instagram:'fab fa-instagram', linkedin:'fab fa-linkedin-in', x:'fab fa-x-twitter' }[p] || 'fas fa-share-nodes';
}
function pfLabel(p) {
    return { facebook:'Facebook', instagram:'Instagram', linkedin:'LinkedIn', x:'X (Twitter)' }[p] || p;
}
function pfColor(p) {
    return { facebook:'#1877F2', instagram:'#E1306C', linkedin:'#0A66C2', x:'#14171A' }[p] || '#4f46e5';
}

// ── Tab geçiş ─────────────────────────────────────────────────────────────
function switchTab(id, btn) {
    document.querySelectorAll('.sm-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('pane-' + id).classList.add('active');
    btn.classList.add('active');
    if (id === 'takvim') loadTakvim();
}

// ── Platform seç ──────────────────────────────────────────────────────────
function setPlatform(p, btn) {
    curPlatform = p;
    document.querySelectorAll('.platform-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    updateFormats();
    updateLimit();
}

function updateFormats() {
    const sel = document.getElementById('formatSel');
    const formats = FORMAT_ETK[curPlatform] || {};
    sel.innerHTML = '';
    Object.entries(formats).forEach(([val, label]) => {
        sel.add(new Option(label, val));
    });
    curFormat = sel.value;
    updateLimit();
}

function updateLimit() {
    curFormat = document.getElementById('formatSel').value;
    curLimit  = (LIMITLER[curPlatform] && LIMITLER[curPlatform][curFormat]) || 3000;
    updateMeter(sonIcerik ? sonIcerik.length : 0);
    updateEbat(); // yükleme paneli açıksa boyutları güncelle
}

// ── Görsel toggle ─────────────────────────────────────────────────────────
document.getElementById('gorselToggle').addEventListener('change', function() {
    document.getElementById('gorselZone').style.display = this.checked && sonIcerik ? 'block' : 'none';
});

// ── Banner hızlı üret ─────────────────────────────────────────────────────
function bannerHazirla() {
    if (BANNER_ONERI && BANNER_ONERI.konu) {
        document.getElementById('konuInput').value = BANNER_ONERI.konu;
        switchTab('studyo', document.querySelector('.sm-tab-btn'));
        window.scrollTo({ top: 200, behavior: 'smooth' });
    }
}

// ── Özel gün → Stüdyo ────────────────────────────────────────────────────
function hazirlaGun(ad, aciklama) {
    document.getElementById('konuInput').value = ad + (aciklama ? ' — ' + aciklama.substring(0, 100) : '');
    switchTab('studyo', document.querySelector('.sm-tab-btn'));
    window.scrollTo({ top: 200, behavior: 'smooth' });
}

// ── İçerik Üret ──────────────────────────────────────────────────────────
async function uretIcerik() {
    const konu = document.getElementById('konuInput').value.trim();
    if (!konu) { toast('Konu boş olamaz.', 'error'); document.getElementById('konuInput').focus(); return; }

    const btn = document.getElementById('btnUret');
    const txt = document.getElementById('btnUretTxt');
    btn.disabled = true;
    txt.innerHTML = '<span class="spinner"></span> Üretiliyor…';

    try {
        const data = await postJson('/superadmin/sosyal-medya/uret', {
            platform: curPlatform,
            format:   curFormat,
            konu:     konu,
            ton:      document.getElementById('tonSel').value,
            ozel_not: document.getElementById('ozelNot').value.trim() || null,
        });

        if (data.hata) { toast(data.hata, 'error'); return; }

        sonIcerik  = data.icerik;
        sonTema    = data.tema || '';
        sonAiSkor  = data.ai_skor || 3;
        sonGorselP = data.gorsel_prompt_onerisi || '';
        curLimit   = data.limit || curLimit;
        revGecmis  = [];

        renderResult(sonIcerik, sonAiSkor, data.karakter, curLimit);
        updatePaylasBtnlari();

        // Görsel prompt önerisi
        if (sonGorselP) document.getElementById('gorselPromptInput').value = sonGorselP;

        // Görsel toggle
        const gt = document.getElementById('gorselToggle');
        if (gt.checked) document.getElementById('gorselZone').style.display = 'block';

        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('zoneRevizyon').style.display = 'block';
        document.getElementById('revBubbles').innerHTML = '';

    } catch (e) {
        toast('Bağlantı hatası.', 'error');
    } finally {
        btn.disabled = false;
        txt.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> İçerik Üret';
    }
}

function yenidenUret() { uretIcerik(); }

// ── Render sonuç ──────────────────────────────────────────────────────────
function renderResult(icerik, skor, karakter, limit) {
    document.getElementById('zoneResult').style.display = 'block';

    // Badge
    const badge = document.getElementById('resultBadge');
    badge.className = `platform-badge badge-${curPlatform}`;
    badge.innerHTML = `<i class="${pfIcon(curPlatform)}"></i> ${pfLabel(curPlatform)}`;

    // Stars
    const stars = document.getElementById('aiStars');
    stars.innerHTML = Array.from({length:5}, (_,i) =>
        `<i class="${i < skor ? 'fas' : 'far'} fa-star"></i>`
    ).join('');

    // İçerik kutusu
    document.getElementById('icerikBox').textContent = icerik;

    // Meter
    updateMeter(karakter);
}

function updateMeter(len) {
    const pct    = Math.min(100, Math.round(len / curLimit * 100));
    const fill   = document.getElementById('meterFill');
    const text   = document.getElementById('meterText');
    if (!fill) return;
    fill.style.width = pct + '%';
    fill.style.background = pct > 90 ? '#ef4444' : pct > 75 ? '#f59e0b' : '#22c55e';
    text.textContent = `${len.toLocaleString('tr')} / ${curLimit.toLocaleString('tr')}`;
}

// ── Kopyala ──────────────────────────────────────────────────────────────
function kopyala() {
    const txt = document.getElementById('icerikBox').innerText || document.getElementById('icerikBox').textContent;
    navigator.clipboard.writeText(txt).then(() => toast('İçerik kopyalandı!'));
}

// ── Paylaş fonksiyonları ─────────────────────────────────────────────────
function getIcerik() {
    return (document.getElementById('icerikBox').innerText || '').trim();
}

// ── Görsel varsa otomatik indir ───────────────────────────────────────────
function gorselOtomatikIndir(platform) {
    if (!gorselData || gorselData.startsWith('data:video')) return false;
    const a = document.createElement('a');
    a.href = gorselData;
    a.download = `gruptalepleri-${platform}-${Date.now()}.png`;
    a.click();
    return true;
}

function paylasX() {
    const txt = getIcerik();
    if (!txt) { toast('Önce içerik üretin.', 'error'); return; }
    const gorselVar = gorselOtomatikIndir('x');
    // X intent URL — metin otomatik dolar
    const url = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(txt);
    window.open(url, '_blank', 'width=600,height=450');
    toast(gorselVar ? 'Görsel indirildi — X penceresinde "Medya ekle" ile ekleyin.' : 'X paylaşım penceresi açıldı.');
}

function paylasFb() {
    const txt = getIcerik();
    if (!txt) { toast('Önce içerik üretin.', 'error'); return; }
    navigator.clipboard.writeText(txt);
    const gorselVar = gorselOtomatikIndir('facebook');
    // Sharer değil, direkt gönderi oluşturma sayfası — görsel eklenebilir
    window.open('https://www.facebook.com/', '_blank');
    toast(gorselVar
        ? 'Metin kopyalandı + görsel indirildi — Facebook\'ta "Gönderi oluştur → Fotoğraf/Video ekle" ile yükleyin.'
        : 'Metin kopyalandı — Facebook\'ta "Gönderi oluştur"a yapıştırın.');
}

function paylasLi() {
    const txt = getIcerik();
    if (!txt) { toast('Önce içerik üretin.', 'error'); return; }
    navigator.clipboard.writeText(txt);
    const gorselVar = gorselOtomatikIndir('linkedin');
    window.open('https://www.linkedin.com/feed/', '_blank');
    toast(gorselVar
        ? 'Metin kopyalandı + görsel indirildi — LinkedIn\'de "Gönderi Oluştur → Görsel ekle" ile yükleyin.'
        : 'Metin kopyalandı — LinkedIn\'de "Gönderi Oluştur"a yapıştırın.');
}

function paylasIg() {
    const txt = getIcerik();
    if (!txt) { toast('Önce içerik üretin.', 'error'); return; }
    navigator.clipboard.writeText(txt);
    const gorselVar = gorselOtomatikIndir('instagram');
    window.open('https://www.instagram.com/', '_blank');
    toast(gorselVar
        ? 'Metin kopyalandı + görsel indirildi — Instagram\'da "+" → görsel seç → caption yapıştır.'
        : 'Metin kopyalandı — Instagram\'da "+" ile yeni gönderi açıp yapıştırın.');
}

// Paylaş butonlarını sadece seçili platforma göre öne çıkar
function updatePaylasBtnlari() {
    const row = document.getElementById('paralasRow');
    if (!row || !sonIcerik) return;
    row.style.display = 'flex';
    // Seçili platforma göre opacity
    ['X','Fb','Li','Ig'].forEach(p => {
        const btn = document.getElementById('btnPaylas' + p);
        if (!btn) return;
        const platform = { X:'x', Fb:'facebook', Li:'linkedin', Ig:'instagram' }[p];
        btn.style.opacity = (platform === curPlatform) ? '1' : '0.45';
        btn.title = platform === curPlatform ? '' : 'İçerik ' + pfLabel(curPlatform) + ' için üretildi';
    });
}

// ── Platform görsel boyutları ─────────────────────────────────────────────
const EBATLAR = {
    facebook: {
        renk: '#1877F2',
        formatlar: {
            durum:           { label: 'Durum / Gönderi', px: '1200 × 630', oran: '1.91:1', dosya: 'JPG/PNG', max: '8 MB', ipucu: 'Yatay dikdörtgen. Mobilde kırpılabilir, önemli alanı ortada tut.' },
            gorsel_aciklama: { label: 'Görsel Açıklaması', px: '1200 × 630', oran: '1.91:1', dosya: 'JPG/PNG', max: '8 MB', ipucu: 'Paylaşımda bağlantı önizlemesi olarak görünür.' },
            reels:           { label: 'Reels', px: '1080 × 1920', oran: '9:16', dosya: 'MP4/MOV', max: '4 GB', sure: 'Maks. 90 sn', ipucu: 'Dikey tam ekran video. Alt/üst ~15% güvenli alan bırak.' },
            hikaye:          { label: 'Hikaye', px: '1080 × 1920', oran: '9:16', dosya: 'JPG/PNG/MP4', max: '4 GB', sure: 'Fotoğraf 5 sn / Video 60 sn', ipucu: 'Alt %20 ve üst %10 çıkartma/profil alanı — önemli içeriği ortada tut.' },
        },
        genel: 'Facebook görselleri JPEG veya PNG olmalı. Metin alanı görselin %20\'sini geçmemeli.'
    },
    instagram: {
        renk: '#E1306C',
        formatlar: {
            akis:   { label: 'Akış (Feed) — Kare',    px: '1080 × 1080', oran: '1:1',   dosya: 'JPG/PNG', max: '8 MB', ipucu: 'En evrensel format. Profilde kare görünür.' },
            reels:  { label: 'Reels',                  px: '1080 × 1920', oran: '9:16',  dosya: 'MP4/MOV', max: '4 GB', sure: 'Maks. 90 sn', ipucu: 'Dikey video. Üst %15 ve alt %20 güvenli alan bırak (profil/buton alanı).' },
            hikaye: { label: 'Hikaye',                 px: '1080 × 1920', oran: '9:16',  dosya: 'JPG/PNG/MP4', max: '4 GB', sure: 'Fotoğraf 7 sn / Video 60 sn', ipucu: 'Feed\'den farklı: yatay (4:5) veya dikey (9:16) de paylaşılabilir.' },
        },
        genel: 'Instagram sRGB renk uzayını tercih eder. CMYK görseller soluk görünebilir.'
    },
    linkedin: {
        renk: '#0A66C2',
        formatlar: {
            gonderi: { label: 'Gönderi',              px: '1200 × 627',  oran: '1.91:1', dosya: 'JPG/PNG/GIF', max: '5 MB', ipucu: 'Profesyonel yatay format. Logolu kurumsal görseller için ideal.' },
            makale:  { label: 'Makale Kapak Görseli', px: '1920 × 1080', oran: '16:9',  dosya: 'JPG/PNG', max: '5 MB', ipucu: 'Makale başlık görseli. Geniş ve net bir kompozisyon seç.' },
        },
        genel: 'LinkedIn\'de görseller otomatik sıkıştırılır. Yüksek kaliteli PNG yükle.'
    },
    x: {
        renk: '#14171A',
        formatlar: {
            tweet:  { label: 'Tweet Görseli', px: '1600 × 900', oran: '16:9', dosya: 'JPG/PNG/GIF/WEBP', max: '5 MB', ipucu: 'Zaman tünelinde 16:9 kırpılır. Önemli alan ortada olsun.' },
            thread: { label: 'Thread Görseli', px: '1600 × 900', oran: '16:9', dosya: 'JPG/PNG/GIF/WEBP', max: '5 MB', ipucu: 'Her tweete ayrı görsel eklenebilir. Seri görseller için tutarlı stil kullan.' },
        },
        genel: 'X animasyonlu GIF destekler (max 5 MB). MP4 video: maks. 2 dakika 20 sn.'
    },
};

function updateEbat() {
    const kart = document.getElementById('ebatKart');
    if (!kart) return;
    const pData = EBATLAR[curPlatform];
    if (!pData) return;

    document.getElementById('ebatPlatform').innerHTML =
        `<i class="${pfIcon(curPlatform)}" style="color:${pData.renk}"></i>&nbsp; ${pfLabel(curPlatform)} — Önerilen Görsel Boyutları`;

    const grid = document.getElementById('ebatGrid');
    grid.innerHTML = Object.entries(pData.formatlar).map(([key, f]) => {
        const aktif = key === curFormat;
        return `<div class="ebat-item${aktif ? ' aktif' : ''}">
            <span class="ebat-format">${f.label}</span>
            <span class="ebat-px">${f.px} px</span>
            <span class="ebat-oran">${f.oran}${f.sure ? ' · ' + f.sure : ''} · ${f.dosya} · Max ${f.max}</span>
        </div>`;
    }).join('');

    const aktifFormat = pData.formatlar[curFormat] || Object.values(pData.formatlar)[0];
    document.getElementById('ebatIpucu').innerHTML =
        `💡 <strong>Seçili format:</strong> ${aktifFormat.ipucu} &nbsp;|&nbsp; ${pData.genel}`;
}

// ── Görsel mod geçiş ─────────────────────────────────────────────────────
let gorselMod = 'ai'; // 'ai' | 'yukle'

function switchGorselMod(mod) {
    gorselMod = mod;
    document.getElementById('panelAi').style.display    = mod === 'ai'    ? 'block' : 'none';
    document.getElementById('panelYukle').style.display = mod === 'yukle' ? 'block' : 'none';
    document.getElementById('modAiBtn').classList.toggle('active',    mod === 'ai');
    document.getElementById('modYukleBtn').classList.toggle('active', mod === 'yukle');
    if (mod === 'yukle') updateEbat();
    gorselTemizle();
}

// ── Dosya seç / bırak ────────────────────────────────────────────────────
function gorselDrop(e) {
    e.preventDefault();
    document.getElementById('yukleDrop').classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) yukleIsle(file);
}

function gorselDosyaSec(input) {
    if (input.files[0]) yukleIsle(input.files[0]);
}

function yukleIsle(file) {
    if (file.size > 50 * 1024 * 1024) { toast('Dosya 50 MB\'ı geçemez.', 'error'); return; }
    const isVideo = file.type.startsWith('video/');
    const reader  = new FileReader();
    reader.onload = (e) => {
        if (isVideo) {
            // Video önizleme — data URL olarak sakla
            gorselData = e.target.result;
            const vid  = document.getElementById('yukleVideoEl');
            vid.src    = gorselData;
            document.getElementById('yukleVideoWrap').style.display  = 'block';
            document.getElementById('gorselPreviewWrap').style.display = 'none';
            toast('Video yüklendi!');
        } else {
            // Görsel — logoyu üzerine bindir
            bindirLogo(e.target.result, 'https://gruptalepleri.com/logo.png').then(result => {
                gorselData = result;
                const img  = document.getElementById('gorselPreviewImg');
                img.src    = gorselData;
                document.getElementById('gorselPreviewWrap').style.display = 'block';
                document.getElementById('yukleVideoWrap').style.display    = 'none';
                toast('Görsel yüklendi — logo eklendi!');
            });
        }
    };
    reader.readAsDataURL(file);
}

// ── Görsel temizle ───────────────────────────────────────────────────────
function gorselTemizle() {
    gorselData = null;
    document.getElementById('gorselPreviewWrap').style.display = 'none';
    document.getElementById('yukleVideoWrap').style.display    = 'none';
    document.getElementById('gorselPreviewImg').src = '';
    const vid = document.getElementById('yukleVideoEl');
    vid.src = '';
    const input = document.getElementById('gorselDosya');
    if (input) input.value = '';
}

// ── Prompt uyarısı ───────────────────────────────────────────────────────
function gorselPromptUyar(val) {
    const risk = /ekran|monitör|monitor|dashboard|panel|yaz[ıi]|logo|ui|screen|text|title|header/i.test(val);
    document.getElementById('gorselPromptUyari').style.display = risk ? 'block' : 'none';
}

// ── Görsel Üret ──────────────────────────────────────────────────────────
let gorselGeriSayimTimer = null;

function gorselGeriSayimBaslat(saniye, onBitis) {
    const btn = document.getElementById('btnGorsel');
    const txt = document.getElementById('btnGorselTxt');
    btn.disabled = true;
    let kalan = saniye;

    gorselGeriSayimTimer = setInterval(() => {
        kalan--;
        txt.innerHTML = `⏳ ${kalan}s bekle…`;
        if (kalan <= 0) {
            clearInterval(gorselGeriSayimTimer);
            gorselGeriSayimTimer = null;
            btn.disabled = false;
            txt.innerHTML = 'Görsel Üret';
            if (onBitis) onBitis();
        }
    }, 1000);
}

async function uretGorsel() {
    const prompt = document.getElementById('gorselPromptInput').value.trim();
    if (!prompt) { toast('Görsel promptu boş olamaz.', 'error'); return; }

    if (gorselGeriSayimTimer) return; // geri sayım devam ediyorsa çift tıklamayı engelle

    const btn = document.getElementById('btnGorsel');
    const txt = document.getElementById('btnGorselTxt');
    btn.disabled = true;
    txt.innerHTML = '<span class="spinner" style="border-top-color:#4f46e5;border-color:#e5e7eb;"></span> Üretiliyor…';

    try {
        const data = await postJson('/superadmin/sosyal-medya/gorsel', {
            gorsel_prompt: prompt,
            platform: curPlatform,
        });

        if (data.hata) {
            const isRateLimit = data.hata.includes('429') || data.hata.includes('quota') ||
                                data.hata.includes('RESOURCE_EXHAUSTED') || data.hata.includes('rate');
            if (isRateLimit) {
                btn.disabled = false;
                txt.innerHTML = 'Görsel Üret';
                toast('⏳ Rate limit: 65 saniye sonra otomatik yeniden denenecek…', 'warning');
                gorselGeriSayimBaslat(65, () => {
                    toast('Yeniden deneniyor…');
                    uretGorsel();
                });
            } else {
                btn.disabled = false;
                txt.innerHTML = 'Görsel Üret';
                toast('Görsel hatası: ' + data.hata.substring(0, 300), 'error');
            }
            return;
        }

        // Gerçek logoyu üzerine canvas ile yapıştır
        const logoSrc = data.logo || data.logo_url || 'https://gruptalepleri.com/logo.png';
        gorselData = await bindirLogo(data.gorsel, logoSrc);

        const img = document.getElementById('gorselPreviewImg');
        img.src = gorselData;
        document.getElementById('gorselPreviewWrap').style.display = 'block';
        toast('Görsel üretildi — logo eklendi!');
    } catch (e) {
        toast('Bağlantı hatası: ' + (e.message || e), 'error');
    } finally {
        if (!gorselGeriSayimTimer) {
            btn.disabled = false;
            txt.innerHTML = 'Görsel Üret';
        }
    }
}

// ── Logo + yazı bindirme: AI görseli tamamen yazısız gelir, biz ekleriz ──
async function bindirLogo(gorselSrc, logoSrc) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx    = canvas.getContext('2d');
        const bgImg  = new Image();
        bgImg.crossOrigin = 'anonymous';

        bgImg.onload = () => {
            canvas.width  = bgImg.naturalWidth;
            canvas.height = bgImg.naturalHeight;
            ctx.drawImage(bgImg, 0, 0);

            const W   = canvas.width;
            const H   = canvas.height;
            const pad = Math.round(W * 0.03);

            // ── URL yazısı — sağ alt ───────────────────────────────────────
            const urlFontSize = Math.max(18, Math.round(W * 0.022));
            ctx.save();
            ctx.font         = `600 ${urlFontSize}px 'Segoe UI', Arial, sans-serif`;
            ctx.textBaseline = 'bottom';
            const urlText    = 'www.gruptalepleri.com';
            const urlW       = ctx.measureText(urlText).width;
            const urlX       = W - urlW - pad;
            const urlY       = H - pad;

            // Pill arka plan
            drawPill(ctx, urlX - 12, urlY - urlFontSize - 8, urlW + 24, urlFontSize + 16, 8, 'rgba(0,0,0,0.55)');
            ctx.fillStyle = '#ffffff';
            ctx.fillText(urlText, urlX, urlY);
            ctx.restore();

            // ── Logo — sağ üst ────────────────────────────────────────────
            const logoW  = Math.min(Math.round(W * 0.20), 240);
            const logoImg = new Image();
            logoImg.crossOrigin = 'anonymous';

            logoImg.onload = () => {
                const logoH = Math.round(logoImg.naturalHeight * (logoW / logoImg.naturalWidth));
                const lx    = W - logoW - pad;
                const ly    = pad;

                // Pill arka plan
                drawPill(ctx, lx - 10, ly - 8, logoW + 20, logoH + 16, 10, 'rgba(255,255,255,0.88)');
                ctx.drawImage(logoImg, lx, ly, logoW, logoH);
                resolve(canvas.toDataURL('image/png'));
            };
            logoImg.onerror = () => {
                // Dış URL CORS ile yüklenemedi — fetch ile base64'e çevirip dene
                if (!logoSrc.startsWith('data:')) {
                    fetch(logoSrc)
                        .then(r => r.blob())
                        .then(b => {
                            const reader = new FileReader();
                            reader.onload = e2 => { logoImg.src = e2.target.result; };
                            reader.readAsDataURL(b);
                        })
                        .catch(() => resolve(canvas.toDataURL('image/png')));
                } else {
                    resolve(canvas.toDataURL('image/png'));
                }
            };
            // Base64 ise direkt ata, dış URL ise crossOrigin ile dene
            logoImg.src = logoSrc.startsWith('data:') ? logoSrc : (logoSrc + '?cb=' + Date.now());
        };

        bgImg.onerror = () => resolve(gorselSrc);
        bgImg.src = gorselSrc;
    });
}

function drawPill(ctx, x, y, w, h, r, color) {
    ctx.save();
    ctx.globalAlpha = 1;
    ctx.fillStyle   = color;
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
    ctx.fill();
    ctx.restore();
}

function gorselIndir() {
    if (!gorselData) return;
    const a = document.createElement('a');
    a.href = gorselData;
    a.download = `gruptalepleri-${curPlatform}-${Date.now()}.png`;
    a.click();
}

// ── Revizyon ─────────────────────────────────────────────────────────────
function sendChip(mesaj) {
    document.getElementById('revInput').value = mesaj;
    sendRevizyon();
}

async function sendRevizyon() {
    const mesaj = document.getElementById('revInput').value.trim();
    if (!mesaj) return;
    if (!sonIcerik && !document.getElementById('icerikBox').textContent.trim()) {
        toast('Önce içerik üretin.', 'error'); return;
    }

    const mevcutIcerik = document.getElementById('icerikBox').textContent.trim();
    document.getElementById('revInput').value = '';

    // Kullanıcı balonu
    addBubble('user', mesaj);

    const btn = document.getElementById('btnRev');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>';

    const loadingBubbleId = 'load-' + Date.now();
    addBubble('ai', '…', loadingBubbleId);

    try {
        const data = await postJson('/superadmin/sosyal-medya/revize', {
            icerik:   mevcutIcerik,
            mesaj:    mesaj,
            platform: curPlatform,
            format:   curFormat,
            gecmis:   revGecmis.slice(-4),
        });

        removeBubble(loadingBubbleId);

        if (data.hata) { addBubble('ai', '⚠️ ' + data.hata); return; }

        // Güncelle
        sonIcerik = data.icerik;
        document.getElementById('icerikBox').textContent = sonIcerik;
        updateMeter(data.karakter || sonIcerik.length);
        updatePaylasBtnlari();
        addBubble('ai', sonIcerik);

        revGecmis.push({ rol: 'kullanici', icerik: mesaj });
        revGecmis.push({ rol: 'asistan',  icerik: sonIcerik });

    } catch (e) {
        removeBubble(loadingBubbleId);
        addBubble('ai', '⚠️ Bağlantı hatası.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    }
}

function addBubble(rol, icerik, id) {
    const box  = document.getElementById('revBubbles');
    const div  = document.createElement('div');
    div.className = `bubble ${rol}`;
    if (id) div.id = id;
    const avatarIcon = rol === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
    div.innerHTML = `
        <div class="bubble-avatar">${avatarIcon}</div>
        <div class="bubble-content">${escHtml(icerik)}</div>`;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

function removeBubble(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Kaydet ───────────────────────────────────────────────────────────────
async function kaydetTaslak() {
    await kaydet('taslak', null);
}

function showPlanlaModal() {
    if (!sonIcerik && !document.getElementById('icerikBox').textContent.trim()) {
        toast('Önce içerik üretin.', 'error'); return;
    }
    const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('planlaDate').value = now.toISOString().slice(0,16);
    new bootstrap.Modal(document.getElementById('planlaModal')).show();
}

function kaydetPlanla() {
    const tarih  = document.getElementById('planlaDate').value;
    const status = document.getElementById('planlaStatus').value;
    kaydet(status, tarih || null);
    bootstrap.Modal.getInstance(document.getElementById('planlaModal'))?.hide();
}

async function kaydet(durum, planlananTarih) {
    const icerik = document.getElementById('icerikBox').textContent.trim();
    if (!icerik) { toast('İçerik boş.', 'error'); return; }
    const konu   = document.getElementById('konuInput').value.trim();

    try {
        const data = await postJson('/superadmin/sosyal-medya/kaydet', {
            platform:        curPlatform,
            format:          curFormat,
            tema:            sonTema || null,
            konu:            konu || null,
            icerik:          icerik,
            gorsel_base64:   gorselData || null,
            durum:           durum,
            planlanan_tarih: planlananTarih || null,
            ai_skor:         sonAiSkor || null,
        });

        if (data.id) {
            const msg = durum === 'taslak' ? 'Taslak kaydedildi.' : 'İçerik planlandı.';
            toast(msg);
            takvimData = null; // takvim cache'i sıfırla
        } else {
            toast(data.mesaj || 'Kaydedildi.');
        }
    } catch (e) {
        toast('Kaydetme hatası.', 'error');
    }
}

// ── Takvim Yükle ─────────────────────────────────────────────────────────
async function loadTakvim(force = false) {
    if (takvimData && !force) { renderKanban(takvimData); return; }

    document.getElementById('takvimLoading').style.display = 'block';
    document.getElementById('kanbanBoard').style.opacity = '0.4';

    try {
        const res = await fetch('/superadmin/sosyal-medya/takvim?json=1', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        takvimData = await res.json();
        renderKanban(takvimData);
    } catch (e) {
        toast('Takvim yüklenemedi.', 'error');
    } finally {
        document.getElementById('takvimLoading').style.display = 'none';
        document.getElementById('kanbanBoard').style.opacity = '1';
    }
}

function renderKanban(data) {
    const fil = kanbanFilter_aktif;
    const filter = arr => fil === 'tumu' ? arr : arr.filter(r => r.platform === fil);

    const taslaklar   = filter(data.taslaklar || []);
    const planlilar   = filter(data.planlilar || []);
    const gonderilenler = filter(data.gonderilenler || []);

    document.getElementById('taslakCount').textContent = taslaklar.length;
    document.getElementById('planliCount').textContent = planlilar.length;
    document.getElementById('gondCount').textContent   = gonderilenler.length;

    renderKanbanCol('colTaslak',  taslaklar,    'taslak');
    renderKanbanCol('colPlanli',  planlilar,    'planli');
    renderKanbanCol('colGond',    gonderilenler,'gonderildi');
}

function renderKanbanCol(colId, items, durum) {
    const col = document.getElementById(colId);
    if (!items.length) {
        col.innerHTML = `<div class="empty-state" style="padding:20px 10px;font-size:.82rem;"><i class="fas fa-inbox" style="font-size:1.5rem;margin-bottom:6px;opacity:.3;"></i><div>İçerik yok</div></div>`;
        return;
    }
    col.innerHTML = items.map(r => kanbanCardHtml(r, durum)).join('');
}

function kanbanCardHtml(r, durum) {
    const excerpt = r.icerik ? r.icerik.substring(0, 100) + (r.icerik.length > 100 ? '…' : '') : '';
    const dateStr = durum === 'planli' ? (r.planlanan_tarih ? formatDate(r.planlanan_tarih) : '') :
                    durum === 'gonderildi' ? (r.gonderim_tarihi ? formatDate(r.gonderim_tarihi) : '') :
                    formatDate(r.created_at);

    const actions = durum === 'taslak'
        ? `<button onclick="takvimDuzenle(${r.id})"><i class="fas fa-edit"></i> Düzenle</button>
           <button onclick="takvimSil(${r.id})" style="color:#ef4444;border-color:#fca5a5"><i class="fas fa-trash"></i> Sil</button>`
        : durum === 'planli'
        ? `<button onclick="takvimDuzenle(${r.id})"><i class="fas fa-edit"></i> Düzenle</button>
           <button onclick="takvimIptal(${r.id})"><i class="fas fa-times"></i> İptal</button>`
        : `<button onclick="takvimTekrar(${r.id})"><i class="fas fa-rotate-right"></i> Tekrar Üret</button>`;

    return `
    <div class="kanban-card" id="kcard-${r.id}" data-platform="${r.platform}">
        <div class="card-platform">
            <i class="${pfIcon(r.platform)}" style="color:${pfColor(r.platform)}"></i>
            ${pfLabel(r.platform)} &middot; ${escHtml(r.format || '')}
            ${r.ai_skor ? `<span style="margin-left:auto;color:#f59e0b;font-size:.75rem;">${'★'.repeat(r.ai_skor)}${'☆'.repeat(5-r.ai_skor)}</span>` : ''}
        </div>
        <div class="card-excerpt">${escHtml(excerpt)}</div>
        <div class="card-date"><i class="fas fa-clock me-1"></i>${dateStr}</div>
        <div class="card-actions">${actions}</div>
    </div>`;
}

function formatDate(str) {
    if (!str) return '';
    const d = new Date(str);
    return d.toLocaleDateString('tr-TR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function filterKanban(platform, btn) {
    kanbanFilter_aktif = platform;
    document.querySelectorAll('.kanban-filter button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (takvimData) renderKanban(takvimData);
}

async function takvimSil(id) {
    if (!confirm('Bu içeriği silmek istediğinize emin misiniz?')) return;
    try {
        const res = await fetch(`/superadmin/sosyal-medya/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.hata) { toast(data.hata, 'error'); return; }
        takvimData = null; loadTakvim(true);
        toast('İçerik silindi.');
    } catch (e) { toast('Silme hatası.', 'error'); }
}

function takvimDuzenle(id) {
    if (!takvimData) return;
    const all = [...(takvimData.taslaklar||[]), ...(takvimData.planlilar||[]), ...(takvimData.gonderilenler||[])];
    const r = all.find(x => x.id === id);
    if (!r) return;

    // Stüdyo'ya yükle
    setPlatform(r.platform, document.querySelector(`.platform-btn[data-p="${r.platform}"]`));
    updateFormats();
    const fSel = document.getElementById('formatSel');
    for (let i=0; i<fSel.options.length; i++) if (fSel.options[i].value === r.format) { fSel.selectedIndex = i; break; }
    document.getElementById('konuInput').value = r.konu || '';
    sonIcerik = r.icerik; sonTema = r.tema || ''; sonAiSkor = r.ai_skor || 3;
    renderResult(sonIcerik, sonAiSkor, sonIcerik.length, curLimit);
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('zoneRevizyon').style.display = 'block';
    document.getElementById('revBubbles').innerHTML = '';
    switchTab('studyo', document.querySelector('.sm-tab-btn'));
}

function takvimIptal(id) { takvimSil(id); }

function takvimTekrar(id) {
    takvimDuzenle(id);
    setTimeout(() => uretIcerik(), 300);
}

// ── İçerik kutusundaki değişiklikleri takip et ────────────────────────────
document.getElementById('icerikBox').addEventListener('input', function() {
    const len = this.textContent.length;
    updateMeter(len);
});

// ── Init ─────────────────────────────────────────────────────────────────
updateFormats();
updateLimit();
</script>
</body>
</html>

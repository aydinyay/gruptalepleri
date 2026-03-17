<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Kutlama Onizleme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#0f1225; color:#fff; font-family:'Segoe UI',sans-serif; margin:0; padding:0; }
        .preview-shell { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }
        .preview-card {
            width:min(980px, 100%);
            border-radius:18px;
            border:1px solid rgba(255,255,255,0.2);
            background:linear-gradient(135deg, #1a1a2e 0%, #1f3a68 100%);
            overflow:hidden;
            box-shadow:0 20px 50px rgba(0,0,0,0.35);
        }
        .preview-image {
            width:100%;
            height:300px;
            object-fit:cover;
            background:#121826;
            border-bottom:1px solid rgba(255,255,255,0.18);
        }
        .preview-content { padding:1.2rem 1.4rem 1.4rem; }
        .badge-mode { font-size:0.72rem; border:1px solid rgba(255,255,255,0.45); color:#fff; }
        .meta { color:rgba(255,255,255,0.7); font-size:0.85rem; }
    </style>
</head>
<body>
<div class="preview-shell">
    <div class="preview-card">
        @if($campaign->image_path)
            <img src="{{ $campaign->image_path }}" alt="AI Kutlama Gorseli" class="preview-image">
        @endif
        <div class="preview-content">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <span class="badge rounded-pill badge-mode px-3 py-2">{{ strtoupper($campaign->display_mode) }}</span>
                <span class="meta">
                    {{ $campaign->event_name }} @if($campaign->event_date) · {{ $campaign->event_date->format('d.m.Y') }} @endif
                </span>
            </div>
            <h3 class="mb-2">{{ $campaign->title ?: 'Baslik bos' }}</h3>
            <p class="mb-3">{{ $campaign->message ?: 'Mesaj bos' }}</p>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                @if($campaign->cta_text)
                    <a href="{{ $campaign->cta_url ?: '#' }}" class="btn btn-danger btn-sm">{{ $campaign->cta_text }}</a>
                @endif
                <span class="meta">Yayin: {{ $campaign->publish_starts_at?->format('d.m.Y H:i') ?: '-' }} - {{ $campaign->publish_ends_at?->format('d.m.Y H:i') ?: '-' }}</span>
            </div>
        </div>
    </div>
</div>
</body>
</html>


<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $offer->request->productLabel() }} - {{ $offer->request->gtpnr }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f8fafc; color: #0f172a; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 24px; }
        .sheet { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; overflow: hidden; }
        .hero { padding: 28px; background: linear-gradient(135deg, #0f172a, #1d4ed8); color: #fff; }
        .meta-grid, .info-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .info-card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; background: #fff; }
        .label { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #64748b; font-weight: 700; }
        .value { font-size: 18px; font-weight: 700; margin-top: 6px; }
        .section { padding: 24px 28px; border-top: 1px solid #e2e8f0; }
        .section h3 { margin: 0 0 14px; font-size: 20px; }
        .list { margin: 0; padding-left: 18px; line-height: 1.7; }
        .timeline { display: grid; gap: 8px; }
        .timeline-item { border: 1px solid #dbeafe; border-radius: 12px; padding: 12px 14px; background: #f8fbff; }
        .media-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .media-card { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #fff; }
        .media-card img { width: 100%; height: 180px; object-fit: cover; display: block; }
        .media-card .caption { padding: 10px 12px; font-size: 14px; }
        .actions { display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 18px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 10px; text-decoration: none; font-weight: 700; border: 1px solid #cbd5e1; color: #0f172a; background: #fff; }
        .btn-primary { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }
        .muted { color: #64748b; }
        @media print {
            body { background: #fff; }
            .actions { display: none !important; }
            .wrap { max-width: none; padding: 0; }
            .sheet { border: 0; border-radius: 0; }
        }
        @media (max-width: 768px) {
            .meta-grid, .info-grid, .media-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
@php
    $requestItem = $offer->request;
    $includes = array_values(array_filter(array_merge($offer->includes_snapshot[$lang] ?? [], $offer->includes_snapshot['supplier'] ?? [])));
    $excludes = array_values(array_filter(array_merge($offer->excludes_snapshot[$lang] ?? [], $offer->excludes_snapshot['supplier'] ?? [])));
    $timeline = preg_split('/\r\n|\r|\n/', (string) ($lang === 'en' ? $offer->timeline_en : $offer->timeline_tr));
    $mediaItems = collect($offer->media_snapshot ?? [])->filter(fn ($item) => ($item['media_type'] ?? 'photo') === 'photo')->values();
@endphp

<div class="wrap">
    @unless($isShare)
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">Yazdir / PDF</button>
        </div>
    @endunless

    <div class="sheet">
        <div class="hero">
            <div class="muted" style="color: rgba(255,255,255,.8);">{{ $requestItem->gtpnr }}</div>
            <h1 style="margin: 8px 0 10px;">{{ $offer->package_label }}</h1>
            <p style="margin: 0; color: rgba(255,255,255,.86);">{{ $requestItem->productLabel() }} · {{ optional($requestItem->service_date)->format('d.m.Y') }} · {{ $requestItem->guest_count }} kisi</p>
        </div>

        <div class="section">
            <div class="meta-grid">
                <div class="info-card">
                    <div class="label">Toplam Fiyat</div>
                    <div class="value">{{ number_format((float) $offer->total_price, 2, ',', '.') }} {{ $offer->currency }}</div>
                </div>
                <div class="info-card">
                    <div class="label">Kisi Basi</div>
                    <div class="value">{{ number_format((float) $offer->per_person_price, 2, ',', '.') }} {{ $offer->currency }}</div>
                </div>
                <div class="info-card">
                    <div class="label">Transfer</div>
                    <div class="value">{{ $requestItem->transfer_required ? 'Dahil / Planli' : 'Talep edilmedi' }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Talep Ozeti</h3>
            <div class="info-grid">
                <div class="info-card">
                    <div class="label">Misafir</div>
                    <div class="value">{{ $requestItem->guest_count }} kisi</div>
                </div>
                <div class="info-card">
                    <div class="label">Paket</div>
                    <div class="value">{{ \Illuminate\Support\Str::headline($requestItem->package_level ?: 'standard') }}</div>
                </div>
                <div class="info-card">
                    <div class="label">Dil</div>
                    <div class="value">{{ strtoupper($lang) }}</div>
                </div>
            </div>
            @if($lang === 'en' ? $offer->offer_note_en : $offer->offer_note_tr)
                <p style="margin: 16px 0 0;">{{ $lang === 'en' ? $offer->offer_note_en : $offer->offer_note_tr }}</p>
            @endif
        </div>

        <div class="section">
            <div class="info-grid">
                <div>
                    <h3>Dahil Olanlar</h3>
                    <ul class="list">
                        @forelse($includes as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>Bilgi eklenmedi.</li>
                        @endforelse
                    </ul>
                </div>
                <div>
                    <h3>Haric Olanlar</h3>
                    <ul class="list">
                        @forelse($excludes as $item)
                            <li>{{ $item }}</li>
                        @empty
                            <li>Haric kalem belirtilmedi.</li>
                        @endforelse
                    </ul>
                </div>
                <div>
                    <h3>Ekstralar</h3>
                    <ul class="list">
                        @forelse($offer->extras_snapshot ?? [] as $extra)
                            <li>{{ $extra['title'] ?? '-' }} @if(!empty($extra['agency_note'])) - {{ $extra['agency_note'] }} @endif</li>
                        @empty
                            <li>Ekstra secim yok.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>Operasyon Akisi</h3>
            <div class="timeline">
                @foreach($timeline as $line)
                    @if(trim((string) $line) !== '')
                        <div class="timeline-item">{{ $line }}</div>
                    @endif
                @endforeach
            </div>
        </div>

        @if($mediaItems->isNotEmpty())
            <div class="section">
                <h3>Gorseller</h3>
                <div class="media-grid">
                    @foreach($mediaItems as $item)
                        <div class="media-card">
                            <img src="{{ $item['url'] ?? '' }}" alt="{{ $item['title_' . $lang] ?? $item['title_tr'] ?? 'Gorsel' }}">
                            <div class="caption">{{ $item['title_' . $lang] ?? $item['title_tr'] ?? 'Gorsel' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
</body>
</html>

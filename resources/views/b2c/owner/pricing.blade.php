<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fiyat Analizi — Özel Yönetim</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f4f8; margin: 0; }
    .page-header {
        background: linear-gradient(135deg, #0f2444, #1a3c6b);
        color: #fff;
        padding: 20px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .page-header h1 { font-size: 1.1rem; font-weight: 700; margin: 0; }
    .summary-bar {
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 32px;
        display: flex;
        gap: 32px;
        flex-wrap: wrap;
    }
    .stat { text-align: center; }
    .stat-val { font-size: 1.3rem; font-weight: 800; color: #1a3c6b; }
    .stat-lbl { font-size: .72rem; color: #718096; text-transform: uppercase; letter-spacing: .04em; }
    .tbl-wrap { padding: 24px 32px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,.08); font-size: .85rem; }
    th { background: #1a3c6b; color: #fff; padding: 10px 12px; text-align: left; font-weight: 600; font-size: .75rem; white-space: nowrap; }
    td { padding: 9px 12px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #f8faff; }
    .type-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: .7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .type-transfer { background: #dbeafe; color: #1e40af; }
    .type-charter  { background: #e0f2fe; color: #0369a1; }
    .type-leisure  { background: #dcfce7; color: #15803d; }
    .type-tour     { background: #fef9c3; color: #854d0e; }
    .type-other    { background: #f3f4f6; color: #374151; }
    .status-dot {
        display: inline-block;
        width: 8px; height: 8px;
        border-radius: 50%;
        margin-right: 5px;
    }
    .dot-on  { background: #22c55e; }
    .dot-off { background: #d1d5db; }
    .num-input {
        width: 100px;
        padding: 4px 8px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: .83rem;
        text-align: right;
    }
    .num-input:focus { outline: none; border-color: #1a3c6b; }
    .notes-input {
        width: 180px;
        padding: 4px 8px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: .78rem;
    }
    .notes-input:focus { outline: none; border-color: #1a3c6b; }
    .save-btn {
        background: #1a3c6b;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 5px 12px;
        font-size: .78rem;
        cursor: pointer;
        white-space: nowrap;
    }
    .save-btn:hover { background: #2a5298; }
    .kazanc-pos { color: #15803d; font-weight: 700; }
    .kazanc-neg { color: #dc2626; font-weight: 700; }
    .kazanc-na  { color: #9ca3af; font-style: italic; }
    .alert-success-custom {
        background: #dcfce7;
        border: 1px solid #86efac;
        color: #15803d;
        padding: 10px 20px;
        border-radius: 8px;
        margin: 0 32px 16px;
        font-size: .85rem;
    }
    .pricing-type-badge {
        font-size: .68rem;
        padding: 1px 6px;
        border-radius: 4px;
        background: #f3f4f6;
        color: #6b7280;
    }
    @media (max-width: 900px) { .tbl-wrap { padding: 12px 8px; } .page-header, .summary-bar { padding: 12px 16px; } }
</style>
</head>
<body>

<div class="page-header">
    <div>
        <h1><i class="bi bi-bar-chart-line-fill me-2"></i>Fiyat & Maliyet Analizi</h1>
        <div style="font-size:.75rem;opacity:.7;margin-top:2px;">gruprezervasyonlari.com — Özel Yönetim Paneli</div>
    </div>
    <div style="font-size:.75rem;opacity:.6;">
        <i class="bi bi-shield-lock-fill me-1"></i>Korumalı
    </div>
</div>

@php
$published = $items->where('is_published', true);
$totalSale = $published->sum(fn($i) => (float)$i->base_price);
$totalCost = $published->sum(fn($i) => (float)$i->cost_price);
$totalKazanc = $totalSale - $totalCost;
$avgMargin = $totalSale > 0 ? round(($totalKazanc / $totalSale) * 100, 1) : 0;
$fixedCount = $published->where('pricing_type', 'fixed')->count();
$quoteCount = $published->where('pricing_type', '!=', 'fixed')->count();
@endphp

<div class="summary-bar">
    <div class="stat">
        <div class="stat-val">{{ $items->count() }}</div>
        <div class="stat-lbl">Toplam Ürün</div>
    </div>
    <div class="stat">
        <div class="stat-val">{{ $published->count() }}</div>
        <div class="stat-lbl">Yayında</div>
    </div>
    <div class="stat">
        <div class="stat-val">{{ $fixedCount }}</div>
        <div class="stat-lbl">Sabit Fiyatlı</div>
    </div>
    <div class="stat">
        <div class="stat-val" style="color:{{ $totalCost > 0 ? '#1a3c6b' : '#9ca3af' }};">
            {{ $totalCost > 0 ? number_format($totalCost, 0, ',', '.') . ' ₺' : '—' }}
        </div>
        <div class="stat-lbl">Toplam Maliyet</div>
    </div>
    <div class="stat">
        <div class="stat-val" style="color:#1a3c6b;">
            {{ $totalSale > 0 ? number_format($totalSale, 0, ',', '.') . ' ₺' : '—' }}
        </div>
        <div class="stat-lbl">Toplam Satış</div>
    </div>
    <div class="stat">
        <div class="stat-val" style="color:{{ $totalKazanc >= 0 ? '#15803d' : '#dc2626' }};">
            {{ $totalCost > 0 ? number_format($totalKazanc, 0, ',', '.') . ' ₺' : '—' }}
        </div>
        <div class="stat-lbl">Toplam Kazanç</div>
    </div>
    <div class="stat">
        <div class="stat-val" style="color:{{ $avgMargin >= 20 ? '#15803d' : ($avgMargin > 0 ? '#d97706' : '#9ca3af') }};">
            {{ $totalCost > 0 ? '%' . $avgMargin : '—' }}
        </div>
        <div class="stat-lbl">Ort. Marj</div>
    </div>
</div>

@if(session('updated'))
    <div class="alert-success-custom mt-3">
        <i class="bi bi-check-circle-fill me-1"></i>{{ session('updated') }}
    </div>
@endif

<div class="tbl-wrap">
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Ürün / Hizmet</th>
            <th>Tip</th>
            <th>Durum</th>
            <th>Fiyat Tipi</th>
            <th>Maliyet (₺)</th>
            <th>Satış Fiyatı (₺)</th>
            <th>Kazanç (₺)</th>
            <th>Marj %</th>
            <th>Not</th>
            <th>Kaydet</th>
        </tr>
    </thead>
    <tbody>
    @foreach($items as $i => $item)
        @php
        $sale  = (float) $item->base_price;
        $cost  = (float) $item->cost_price;
        $kazanc = ($sale > 0 && $cost > 0) ? $sale - $cost : null;
        $marj   = ($kazanc !== null && $sale > 0) ? round(($kazanc / $sale) * 100, 1) : null;
        @endphp
        <form method="POST" action="{{ route('b2c.owner.pricing.update', [$item->id, 't' => $token]) }}">
            @csrf
            <tr>
                <td style="color:#9ca3af;font-size:.75rem;">{{ $i+1 }}</td>
                <td>
                    <div style="font-weight:600;color:#1a202c;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $item->title }}
                    </div>
                    @if($item->destination_city)
                        <div style="font-size:.72rem;color:#718096;">{{ $item->destination_city }}</div>
                    @endif
                </td>
                <td>
                    <span class="type-badge type-{{ $item->product_type }}">{{ $item->product_type }}</span>
                </td>
                <td>
                    <span class="status-dot {{ $item->is_published ? 'dot-on' : 'dot-off' }}"></span>
                    {{ $item->is_published ? 'Yayında' : 'Taslak' }}
                </td>
                <td>
                    <span class="pricing-type-badge">
                        {{ ['fixed'=>'Sabit','quote'=>'Teklif','request'=>'Talep'][$item->pricing_type] ?? $item->pricing_type }}
                    </span>
                </td>
                <td>
                    <input type="number" name="cost_price" class="num-input"
                           value="{{ $item->cost_price ? number_format((float)$item->cost_price, 0, '.', '') : '' }}"
                           placeholder="0" min="0" step="1">
                </td>
                <td>
                    <input type="number" name="base_price" class="num-input"
                           value="{{ $item->base_price ? number_format((float)$item->base_price, 0, '.', '') : '' }}"
                           placeholder="0" min="0" step="1">
                </td>
                <td>
                    @if($kazanc !== null)
                        <span class="{{ $kazanc >= 0 ? 'kazanc-pos' : 'kazanc-neg' }}">
                            {{ number_format($kazanc, 0, ',', '.') }}
                        </span>
                    @else
                        <span class="kazanc-na">—</span>
                    @endif
                </td>
                <td>
                    @if($marj !== null)
                        <span style="font-weight:700;color:{{ $marj >= 30 ? '#15803d' : ($marj >= 15 ? '#d97706' : '#dc2626') }};">
                            %{{ $marj }}
                        </span>
                    @else
                        <span class="kazanc-na">—</span>
                    @endif
                </td>
                <td>
                    <input type="text" name="pricing_notes" class="notes-input"
                           value="{{ $item->pricing_notes }}"
                           placeholder="Not ekle...">
                </td>
                <td>
                    <button type="submit" class="save-btn">
                        <i class="bi bi-check2"></i> Kaydet
                    </button>
                </td>
            </tr>
        </form>
    @endforeach
    </tbody>
</table>
</div>

<div style="text-align:center;padding:24px;color:#9ca3af;font-size:.75rem;">
    Kazanç ve marj değerleri maliyet ve satış fiyatına göre otomatik hesaplanır.
    <span style="background:#dcfce7;color:#15803d;padding:2px 6px;border-radius:4px;margin-left:8px;">Yeşil ≥%30</span>
    <span style="background:#fef3c7;color:#d97706;padding:2px 6px;border-radius:4px;margin-left:4px;">Sarı %15–30</span>
    <span style="background:#fee2e2;color:#dc2626;padding:2px 6px;border-radius:4px;margin-left:4px;">Kırmızı &lt;%15</span>
</div>

<script>
// Kazanç alanlarını canlı güncelle
document.querySelectorAll('form').forEach(form => {
    const costInput = form.querySelector('[name="cost_price"]');
    const saleInput = form.querySelector('[name="base_price"]');
    const kazancCell = form.querySelector('tr td:nth-child(8)');
    const marjCell   = form.querySelector('tr td:nth-child(9)');

    function recalc() {
        const cost = parseFloat(costInput.value) || 0;
        const sale = parseFloat(saleInput.value) || 0;
        if (cost > 0 && sale > 0) {
            const kaz  = sale - cost;
            const marj = Math.round((kaz / sale) * 1000) / 10;
            const kazColor = kaz >= 0 ? '#15803d' : '#dc2626';
            const marjColor = marj >= 30 ? '#15803d' : (marj >= 15 ? '#d97706' : '#dc2626');
            kazancCell.innerHTML = '<span style="font-weight:700;color:' + kazColor + '">' + kaz.toLocaleString('tr-TR') + '</span>';
            marjCell.innerHTML   = '<span style="font-weight:700;color:' + marjColor + '">%' + marj + '</span>';
        } else {
            kazancCell.innerHTML = '<span style="color:#9ca3af;font-style:italic">—</span>';
            marjCell.innerHTML   = '<span style="color:#9ca3af;font-style:italic">—</span>';
        }
    }

    costInput && costInput.addEventListener('input', recalc);
    saleInput && saleInput.addEventListener('input', recalc);
});
</script>
</body>
</html>

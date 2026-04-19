<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fiyat Analizi — Özel Yönetim</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f4f8; }

.page-subheader {
    background: linear-gradient(135deg, #0f2444, #1a3c6b);
    color: #fff;
    padding: 12px 24px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.2);
}
.page-subheader h1 { font-size: 1rem; font-weight: 700; margin: 0; }
.page-subheader .sub { font-size: .7rem; opacity: .65; margin-top: 1px; }

.summary-bar {
    background: #fff;
    border-bottom: 2px solid #e2e8f0;
    padding: 10px 24px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
}
.stat { text-align: center; min-width: 80px; }
.stat-val { font-size: 1.2rem; font-weight: 800; color: #1a3c6b; }
.stat-lbl { font-size: .65rem; color: #718096; text-transform: uppercase; letter-spacing: .05em; margin-top: 2px; }
.stat-sep { width: 1px; height: 36px; background: #e2e8f0; }

.kur-bar {
    background: #fffbeb;
    border-bottom: 1px solid #fde68a;
    padding: 6px 24px;
    font-size: .75rem;
    color: #92400e;
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}
.kur-bar b { color: #78350f; }

.alert-ok {
    background: #dcfce7; border: 1px solid #86efac; color: #15803d;
    padding: 10px 32px; font-size: .83rem;
}

.tbl-wrap { padding: 12px 24px 24px; overflow-x: auto; }

table {
    border-collapse: collapse;
    width: 100%;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 8px rgba(0,0,0,.08);
    font-size: .78rem;
    min-width: 900px;
}
th {
    background: #1a3c6b;
    color: #fff;
    padding: 9px 10px;
    text-align: left;
    font-weight: 600;
    font-size: .72rem;
    white-space: nowrap;
}
th.group-maliyet { background: #1e4d8c; }
th.group-gt      { background: #1a6b3c; }
th.group-gr      { background: #6b1a1a; }
th.group-kazanc  { background: #4a1a6b; }

td { padding: 8px 10px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8faff; }

.type-badge {
    display: inline-block; padding: 2px 7px; border-radius: 4px;
    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
}
.type-transfer { background: #dbeafe; color: #1e40af; }
.type-charter  { background: #e0f2fe; color: #0369a1; }
.type-leisure  { background: #dcfce7; color: #15803d; }
.type-tour     { background: #fef9c3; color: #854d0e; }
.type-hotel    { background: #fce7f3; color: #9d174d; }
.type-visa     { background: #ede9fe; color: #5b21b6; }
.type-other    { background: #f3f4f6; color: #374151; }

.status-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 4px; }
.dot-on  { background: #22c55e; }
.dot-off { background: #d1d5db; }

.price-cell { min-width: 120px; }
.price-input {
    width: 90px; padding: 4px 7px;
    border: 1px solid #d1d5db; border-radius: 6px;
    font-size: .82rem; text-align: right;
    background: #fff;
}
.price-input:focus { outline: none; border-color: #1a3c6b; box-shadow: 0 0 0 2px rgba(26,60,107,.1); }
.price-try {
    font-size: .67rem; color: #9ca3af; margin-top: 2px;
    white-space: nowrap;
}
.curr-label { font-size: .72rem; color: #6b7280; margin-left: 3px; }

.kazanc-cell { min-width: 90px; }
.k-pos  { color: #15803d; font-weight: 700; }
.k-neg  { color: #dc2626; font-weight: 700; }
.k-na   { color: #9ca3af; font-style: italic; }

.marj-chip {
    display: inline-block; padding: 2px 8px; border-radius: 20px;
    font-size: .72rem; font-weight: 700;
}
.marj-green { background: #dcfce7; color: #15803d; }
.marj-yellow { background: #fef3c7; color: #d97706; }
.marj-red   { background: #fee2e2; color: #dc2626; }
.marj-na    { background: #f3f4f6; color: #9ca3af; }

.notes-input {
    width: 110px; padding: 3px 6px;
    border: 1px solid #d1d5db; border-radius: 6px;
    font-size: .72rem;
}
.notes-input:focus { outline: none; border-color: #1a3c6b; }

.save-btn {
    background: #1a3c6b; color: #fff; border: none;
    border-radius: 6px; padding: 5px 12px;
    font-size: .75rem; cursor: pointer; white-space: nowrap;
}
.save-btn:hover { background: #2a5298; }

.cur-sel {
    padding: 4px 6px; border: 1px solid #d1d5db; border-radius: 6px;
    font-size: .75rem; background: #fff; color: #374151;
}
.cur-sel:focus { outline: none; border-color: #1a3c6b; }

.legend { display: flex; gap: 10px; align-items: center; padding: 12px 24px; font-size: .72rem; color: #6b7280; flex-wrap: wrap; }
.legend span { padding: 2px 8px; border-radius: 4px; }
.color-gt    { color: #1a6b3c; }
.color-gr    { color: #6b1a1a; }
.color-green { color: #15803d; }
.color-na    { color: #9ca3af; }
.stat-big    { font-size: 1.4rem; }
</style>
</head>
<body data-usd="{{ $usdKuru }}" data-eur="{{ $eurKuru }}">

<x-navbar-superadmin active="b2c" />

<div class="page-subheader">
    <i class="bi bi-bar-chart-line-fill" style="font-size:1.1rem;opacity:.8;"></i>
    <div>
        <div class="h1">Fiyat & Maliyet Analizi</div>
        <div class="sub">gruprezervasyonlari.com — Özel Yönetim Paneli</div>
    </div>
</div>

@php
$published = $items->where('is_published', true);
$usdRate   = $usdKuru;
$eurRate   = $eurKuru;

function tryVal($price, $currency, $usdRate, $eurRate): float {
    $price = (float) $price;
    return match($currency) {
        'USD' => $price * $usdRate,
        'EUR' => $price * $eurRate,
        'GBP' => $price * $eurRate * 1.15,
        default => $price,
    };
}

$totalGtKazancTry = 0;
$totalGrKazancTry = 0;
$totalKazancTry   = 0;
foreach ($published as $item) {
    $c = $item->currency ?? 'TRY';
    $cost = (float) $item->cost_price;
    $gt   = (float) $item->gt_price;
    $gr   = (float) $item->base_price;
    if ($cost > 0 && $gt > 0) $totalGtKazancTry += tryVal($gt - $cost, $c, $usdRate, $eurRate);
    if ($gt > 0 && $gr > 0)   $totalGrKazancTry += tryVal($gr - $gt, $c, $usdRate, $eurRate);
    if ($cost > 0 && $gr > 0) $totalKazancTry   += tryVal($gr - $cost, $c, $usdRate, $eurRate);
}
@endphp

<div class="kur-bar">
    <i class="bi bi-info-circle"></i>
    <span>Kur (tahmini): <b>1 USD ≈ {{ number_format($usdKuru, 0) }} ₺</b></span>
    <span><b>1 EUR ≈ {{ number_format($eurKuru, 0) }} ₺</b></span>
    <span style="opacity:.7;">TRY karşılıkları bu kur ile hesaplanır. Kuru güncellemek için Site Ayarları → <code>usd_kuru</code> / <code>eur_kuru</code> ayarlarını düzenleyin.</span>
</div>

@php
$statAll   = $items->count();
$statGr    = $items->where('publish_status', 'b2c')->count();
$statGt    = $items->where('publish_status', 'b2b')->count();
$statFixed = $items->where('pricing_type', 'fixed')->count();
@endphp

<div class="summary-bar">
    <div class="stat">
        <div class="stat-val">{{ $statAll }}</div>
        <div class="stat-lbl">Toplam Ürün</div>
    </div>
    <div class="stat">
        <div class="stat-val" style="color:#15803d;">{{ $statGr }}</div>
        <div class="stat-lbl">GR Yayında</div>
    </div>
    <div class="stat">
        <div class="stat-val" style="color:#1a3c6b;">{{ $statGt }}</div>
        <div class="stat-lbl">GT Yayında</div>
    </div>
    <div class="stat">
        <div class="stat-val">{{ $statFixed }}</div>
        <div class="stat-lbl">Sabit Fiyatlı</div>
    </div>
    <div class="stat-sep"></div>
    <div class="stat">
        <div class="stat-val {{ $totalGtKazancTry > 0 ? 'color-gt' : 'color-na' }}">
            {{ $totalGtKazancTry > 0 ? number_format($totalGtKazancTry, 0, ',', '.') . ' ₺' : '—' }}
        </div>
        <div class="stat-lbl">GT Toplam Kazanç</div>
    </div>
    <div class="stat">
        <div class="stat-val {{ $totalGrKazancTry > 0 ? 'color-gr' : 'color-na' }}">
            {{ $totalGrKazancTry > 0 ? number_format($totalGrKazancTry, 0, ',', '.') . ' ₺' : '—' }}
        </div>
        <div class="stat-lbl">GR Toplam Kazanç</div>
    </div>
    <div class="stat-sep"></div>
    <div class="stat">
        <div class="stat-val stat-big {{ $totalKazancTry > 0 ? 'color-green' : 'color-na' }}">
            {{ $totalKazancTry > 0 ? number_format($totalKazancTry, 0, ',', '.') . ' ₺' : '—' }}
        </div>
        <div class="stat-lbl">Toplam Net Kazanç</div>
    </div>
</div>

@if(session('updated'))
    <div class="alert-ok"><i class="bi bi-check-circle-fill" style="margin-right:6px;"></i>{{ session('updated') }}</div>
@endif

<div class="tbl-wrap">
<table>
    <thead>
        <tr>
            <th rowspan="2">#</th>
            <th rowspan="2">Ürün / Hizmet</th>
            <th rowspan="2">Tip</th>
            <th rowspan="2">Durum</th>
            <th rowspan="2">Para<br>Birimi</th>
            <th class="group-maliyet">Maliyet</th>
            <th class="group-gt">GT Satış Fiyatı</th>
            <th class="group-gr">GR Satış Fiyatı</th>
            <th class="group-kazanc">GT Kazancı</th>
            <th class="group-kazanc">GR Kazancı</th>
            <th class="group-kazanc">Top. Kazanç</th>
            <th class="group-kazanc">Marj %</th>
            <th rowspan="2">Not</th>
            <th rowspan="2">Kaydet</th>
        </tr>
        <tr>
            <th class="group-maliyet" style="font-size:.65rem;font-weight:400;opacity:.8;">tedarikçi</th>
            <th class="group-gt" style="font-size:.65rem;font-weight:400;opacity:.8;">B2B acente</th>
            <th class="group-gr" style="font-size:.65rem;font-weight:400;opacity:.8;">B2C müşteri</th>
            <th class="group-kazanc" style="font-size:.65rem;font-weight:400;opacity:.8;">GT−maliyet</th>
            <th class="group-kazanc" style="font-size:.65rem;font-weight:400;opacity:.8;">GR−GT</th>
            <th class="group-kazanc" style="font-size:.65rem;font-weight:400;opacity:.8;">GR−maliyet</th>
            <th class="group-kazanc" style="font-size:.65rem;font-weight:400;opacity:.8;">top/GR</th>
        </tr>
    </thead>
    <tbody>
    @foreach($items as $i => $item)
        @php
        $curr     = $item->currency ?? 'TRY';
        $cost     = (float) $item->cost_price;
        $gtPrice  = (float) $item->gt_price;
        $grPrice  = (float) $item->base_price;

        $gtKazanc  = ($cost > 0 && $gtPrice > 0) ? $gtPrice - $cost : null;
        $grKazanc  = ($gtPrice > 0 && $grPrice > 0) ? $grPrice - $gtPrice : null;
        $topKazanc = ($cost > 0 && $grPrice > 0) ? $grPrice - $cost : null;
        $marj      = ($topKazanc !== null && $grPrice > 0) ? round(($topKazanc / $grPrice) * 100, 1) : null;

        $rate = match($curr) { 'USD' => $usdKuru, 'EUR' => $eurKuru, default => 1 };
        @endphp
        <form method="POST" action="{{ route('b2c.owner.pricing.update', [$item->id, 't' => $token]) }}">
            @csrf
            <tr>
                <td style="color:#9ca3af;font-size:.72rem;">{{ $i+1 }}</td>
                <td style="max-width:180px;">
                    <div style="font-weight:600;color:#1a202c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px;" title="{{ $item->title }}">
                        {{ $item->title }}
                    </div>
                    @if($item->destination_city)
                        <div style="font-size:.68rem;color:#718096;">{{ $item->destination_city }}</div>
                    @endif
                </td>
                <td><span class="type-badge type-{{ $item->product_type }}">{{ $item->product_type }}</span></td>
                <td style="white-space:nowrap;">
                    @php $ps = $item->publish_status ?? ($item->is_published ? 'b2c' : 'draft'); @endphp
                    @if($ps === 'b2c')
                        <span class="status-dot dot-on"></span><span style="color:#15803d;font-weight:600;">GR Yayında</span>
                    @elseif($ps === 'b2b')
                        <span class="status-dot" style="background:#1a3c6b;"></span><span style="color:#1a3c6b;font-weight:600;">GT Yayında</span>
                    @else
                        <span class="status-dot dot-off"></span><span style="color:#9ca3af;">Taslak</span>
                    @endif
                </td>
                <td>
                    <select name="currency" class="cur-sel">
                        @foreach(['TRY','USD','EUR','GBP'] as $c)
                            <option value="{{ $c }}" {{ $curr === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="price-cell">
                    <div style="display:flex;align-items:center;gap:3px;">
                        <input type="number" name="cost_price" class="price-input" data-field="cost"
                               value="{{ $cost > 0 ? number_format($cost, 2, '.', '') : '' }}"
                               placeholder="0" min="0" step="0.01">
                        <span class="curr-label curr-display">{{ $curr }}</span>
                    </div>
                    <div class="price-try try-cost">
                        @if($cost > 0 && $curr !== 'TRY')≈ {{ number_format($cost * $rate, 0, ',', '.') }} ₺@endif
                    </div>
                </td>
                <td class="price-cell">
                    <div style="display:flex;align-items:center;gap:3px;">
                        <input type="number" name="gt_price" class="price-input" data-field="gt"
                               value="{{ $gtPrice > 0 ? number_format($gtPrice, 2, '.', '') : '' }}"
                               placeholder="0" min="0" step="0.01">
                        <span class="curr-label curr-display">{{ $curr }}</span>
                    </div>
                    <div class="price-try try-gt">
                        @if($gtPrice > 0 && $curr !== 'TRY')≈ {{ number_format($gtPrice * $rate, 0, ',', '.') }} ₺@endif
                    </div>
                </td>
                <td class="price-cell">
                    <div style="display:flex;align-items:center;gap:3px;">
                        <input type="number" name="base_price" class="price-input" data-field="gr"
                               value="{{ $grPrice > 0 ? number_format($grPrice, 2, '.', '') : '' }}"
                               placeholder="0" min="0" step="0.01">
                        <span class="curr-label curr-display">{{ $curr }}</span>
                    </div>
                    <div class="price-try try-gr">
                        @if($grPrice > 0 && $curr !== 'TRY')≈ {{ number_format($grPrice * $rate, 0, ',', '.') }} ₺@endif
                    </div>
                </td>
                <td class="kazanc-cell">
                    <div class="kaz-gt-val {{ $gtKazanc !== null ? ($gtKazanc >= 0 ? 'k-pos' : 'k-neg') : 'k-na' }}">
                        @if($gtKazanc !== null){{ number_format($gtKazanc, 0, ',', '.') }} {{ $curr }}@else—@endif
                    </div>
                    @if($gtKazanc !== null && $curr !== 'TRY')
                    <div class="price-try">≈ {{ number_format($gtKazanc * $rate, 0, ',', '.') }} ₺</div>
                    @endif
                </td>
                <td class="kazanc-cell">
                    <div class="kaz-gr-val {{ $grKazanc !== null ? ($grKazanc >= 0 ? 'k-pos' : 'k-neg') : 'k-na' }}">
                        @if($grKazanc !== null){{ number_format($grKazanc, 0, ',', '.') }} {{ $curr }}@else—@endif
                    </div>
                    @if($grKazanc !== null && $curr !== 'TRY')
                    <div class="price-try">≈ {{ number_format($grKazanc * $rate, 0, ',', '.') }} ₺</div>
                    @endif
                </td>
                <td class="kazanc-cell">
                    <div class="kaz-top-val {{ $topKazanc !== null ? ($topKazanc >= 0 ? 'k-pos' : 'k-neg') : 'k-na' }}">
                        @if($topKazanc !== null){{ number_format($topKazanc, 0, ',', '.') }} {{ $curr }}@else—@endif
                    </div>
                    @if($topKazanc !== null && $curr !== 'TRY')
                    <div class="price-try">≈ {{ number_format($topKazanc * $rate, 0, ',', '.') }} ₺</div>
                    @endif
                </td>
                <td>
                    @if($marj !== null)
                        <span class="marj-chip {{ $marj >= 30 ? 'marj-green' : ($marj >= 15 ? 'marj-yellow' : 'marj-red') }}">
                            %{{ $marj }}
                        </span>
                    @else
                        <span class="marj-chip marj-na">—</span>
                    @endif
                </td>
                <td>
                    <input type="text" name="pricing_notes" class="notes-input"
                           value="{{ $item->pricing_notes }}" placeholder="Not...">
                </td>
                <td>
                    <button type="submit" class="save-btn"><i class="bi bi-check2"></i> Kaydet</button>
                </td>
            </tr>
        </form>
    @endforeach
    </tbody>
</table>
</div>

<div class="legend">
    <span style="background:#dcfce7;color:#15803d;">Yeşil: ≥%30 marj</span>
    <span style="background:#fef3c7;color:#d97706;">Sarı: %15–30</span>
    <span style="background:#fee2e2;color:#dc2626;">Kırmızı: &lt;%15</span>
    <span style="color:#6b7280;">GT Kazancı = GT Fiyatı − Maliyet &nbsp;|&nbsp; GR Kazancı = GR Fiyatı − GT Fiyatı &nbsp;|&nbsp; Top. Kazanç = GR Fiyatı − Maliyet</span>
</div>

<script>
(function() {
    var USD_RATE = parseFloat(document.body.dataset.usd) || 34;
    var EUR_RATE = parseFloat(document.body.dataset.eur) || 37;

    function getRate(curr) {
        if (curr === 'USD') return USD_RATE;
        if (curr === 'EUR') return EUR_RATE;
        if (curr === 'GBP') return EUR_RATE * 1.15;
        return 1;
    }

    function fmtTRY(val, rate) {
        return '≈ ' + Math.round(val * rate).toLocaleString('tr-TR') + ' ₺';
    }

    function fmtNum(val, curr) {
        return val.toLocaleString('tr-TR', {maximumFractionDigits: 0}) + ' ' + curr;
    }

    function marjClass(m) {
        if (m >= 30) return 'marj-chip marj-green';
        if (m >= 15) return 'marj-chip marj-yellow';
        return 'marj-chip marj-red';
    }

    document.querySelectorAll('form').forEach(function(form) {
        var costIn  = form.querySelector('[name="cost_price"]');
        var gtIn    = form.querySelector('[name="gt_price"]');
        var grIn    = form.querySelector('[name="base_price"]');
        var curSel  = form.querySelector('[name="currency"]');
        var tryCost = form.querySelector('.try-cost');
        var tryGt   = form.querySelector('.try-gt');
        var tryGr   = form.querySelector('.try-gr');
        var kazGt   = form.querySelector('.kaz-gt-val');
        var kazGr   = form.querySelector('.kaz-gr-val');
        var kazTop  = form.querySelector('.kaz-top-val');
        var marjEl  = form.querySelector('.marj-chip');
        var currLabels = form.querySelectorAll('.curr-display');

        function recalc() {
            var curr  = curSel.value;
            var rate  = getRate(curr);
            var cost  = parseFloat(costIn.value) || 0;
            var gt    = parseFloat(gtIn.value)   || 0;
            var gr    = parseFloat(grIn.value)   || 0;

            // Update currency labels
            currLabels.forEach(function(el) { el.textContent = curr; });

            // TRY equivalents
            tryCost.textContent = (cost > 0 && curr !== 'TRY') ? fmtTRY(cost, rate) : '';
            tryGt.textContent   = (gt > 0 && curr !== 'TRY')   ? fmtTRY(gt, rate)   : '';
            tryGr.textContent   = (gr > 0 && curr !== 'TRY')   ? fmtTRY(gr, rate)   : '';

            // Kazanç
            if (cost > 0 && gt > 0) {
                var kgt = gt - cost;
                kazGt.className = 'kaz-gt-val ' + (kgt >= 0 ? 'k-pos' : 'k-neg');
                kazGt.textContent = fmtNum(kgt, curr);
            } else {
                kazGt.className = 'kaz-gt-val k-na'; kazGt.textContent = '—';
            }
            if (gt > 0 && gr > 0) {
                var kgr = gr - gt;
                kazGr.className = 'kaz-gr-val ' + (kgr >= 0 ? 'k-pos' : 'k-neg');
                kazGr.textContent = fmtNum(kgr, curr);
            } else {
                kazGr.className = 'kaz-gr-val k-na'; kazGr.textContent = '—';
            }
            if (cost > 0 && gr > 0) {
                var ktop = gr - cost;
                kazTop.className = 'kaz-top-val ' + (ktop >= 0 ? 'k-pos' : 'k-neg');
                kazTop.textContent = fmtNum(ktop, curr);
                if (marjEl) {
                    var m = Math.round((ktop / gr) * 1000) / 10;
                    marjEl.className = marjClass(m);
                    marjEl.textContent = '%' + m;
                }
            } else {
                kazTop.className = 'kaz-top-val k-na'; kazTop.textContent = '—';
                if (marjEl) { marjEl.className = 'marj-chip marj-na'; marjEl.textContent = '—'; }
            }
        }

        [costIn, gtIn, grIn, curSel].forEach(function(el) {
            el && el.addEventListener('input', recalc);
            el && el.addEventListener('change', recalc);
        });
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

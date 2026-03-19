<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">Cari Pozisyon Ozeti (Acenta Bazli)</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-3">
                <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Toplam Alacak (Acentalardan)</small>
                    <div class="fs-5 fw-bold text-primary">{{ number_format((float) ($balanceSummary['receivable_total'] ?? 0), 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Toplam Borc (Acentalara Cikis)</small>
                    <div class="fs-5 fw-bold text-danger">{{ number_format((float) ($balanceSummary['payable_total'] ?? 0), 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Bekleyen Iade Talebi</small>
                    <div class="fs-5 fw-bold text-warning">{{ number_format((float) ($balanceSummary['pending_refund_total'] ?? 0), 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="border rounded p-3 h-100">
                    <small class="text-muted d-block">Net Pozisyon</small>
                    @php
                        $net = (float) ($balanceSummary['net_total'] ?? 0);
                    @endphp
                    <div class="fs-5 fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($net, 2, ',', '.') }}
                    </div>
                    <small class="text-muted">{{ $net >= 0 ? 'Net alacakli' : 'Net borclu' }}</small>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Acenta</th>
                    <th>Kayit</th>
                    <th>Toplam Is</th>
                    <th>Toplam Tahsilat</th>
                    <th>Kalan Alacak</th>
                    <th>Cikis / Borc</th>
                    <th>Bekleyen Iade</th>
                    <th>Net</th>
                </tr>
                </thead>
                <tbody>
                @forelse($agencyBalances as $row)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row['agency_name'] }}</div>
                            <small class="text-muted">{{ $row['agency_email'] ?: '-' }}</small>
                        </td>
                        <td>{{ (int) $row['record_count'] }}</td>
                        <td>{{ number_format((float) $row['gross_total'], 2, ',', '.') }}</td>
                        <td class="text-success">{{ number_format((float) $row['paid_total'], 2, ',', '.') }}</td>
                        <td class="text-primary">{{ number_format((float) $row['receivable_total'], 2, ',', '.') }}</td>
                        <td class="text-danger">{{ number_format((float) $row['payable_total'], 2, ',', '.') }}</td>
                        <td class="text-warning">{{ number_format((float) $row['pending_refund_total'], 2, ',', '.') }}</td>
                        <td>
                            @php
                                $rowNet = (float) $row['net_total'];
                            @endphp
                            <span class="fw-semibold {{ $rowNet >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($rowNet, 2, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">Cari pozisyon verisi bulunamadi.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(!$coreReady)
    <div class="alert alert-warning mb-0">
        Finans cekirdek tablolari henuz hazir degil. Once migration calistirildiginda bu ekran aktif olacaktir.
    </div>
@else
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Acik Borc (Brut)</small>
                    <div class="fs-5 fw-bold">{{ number_format($summary['open_total'] ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Tahsil Edilen (Net)</small>
                    <div class="fs-5 fw-bold text-success">{{ number_format($summary['paid_total'] ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Kalan Bakiye</small>
                    <div class="fs-5 fw-bold text-danger">{{ number_format($summary['remaining_total'] ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Bekleyen Islem</small>
                    <div class="fs-5 fw-bold">{{ (int) ($summary['pending_transactions'] ?? 0) }}</div>
                    @if(isset($summary['pending_receipts']))
                        <small class="text-muted">Dekont bekleyen: {{ (int) $summary['pending_receipts'] }}</small>
                    @endif
                </div>
            </div>
        </div>
        @if(isset($summary['requested_refunds']))
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted d-block">Bekleyen Iade Talebi</small>
                        <div class="fs-5 fw-bold text-warning">{{ (int) ($summary['requested_refunds'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        @endif
        @if(isset($summary['due_in_7_days']))
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted d-block">7 Gun Icinde Vadesi Gelen</small>
                        <div class="fs-5 fw-bold text-primary">{{ (int) ($summary['due_in_7_days'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        @endif
        @if(isset($summary['overdue_installments']))
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted d-block">Geciken Taksit</small>
                        <div class="fs-5 fw-bold text-danger">{{ (int) ($summary['overdue_installments'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="card shadow-sm">
        <div class="card-header fw-semibold">Finans Kayitlari</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Belge</th>
                    <th>Hizmet</th>
                    <th>Toplam</th>
                    <th>Odenen</th>
                    <th>Kalan</th>
                    <th>Durum</th>
                    <th>Son Islem</th>
                </tr>
                </thead>
                <tbody>
                @forelse($records as $record)
                    @php
                        $lastTx = $record->transactions->first();
                    @endphp
                    <tr>
                        <td>{{ $record->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $record->document_ref ?: '-' }}</div>
                            <small class="text-muted">{{ $record->title }}</small>
                        </td>
                        <td>{{ $record->service_type ?: 'manual' }}</td>
                        <td>{{ number_format((float) $record->gross_amount, 2, ',', '.') }} {{ $record->currency }}</td>
                        <td class="text-success">{{ number_format((float) $record->paid_amount, 2, ',', '.') }} {{ $record->currency }}</td>
                        <td class="text-danger">{{ number_format((float) $record->remaining_amount, 2, ',', '.') }} {{ $record->currency }}</td>
                        <td><span class="badge bg-secondary">{{ $record->status }}</span></td>
                        <td>
                            @if($lastTx)
                                <div>{{ $lastTx->status }} - {{ number_format((float) $lastTx->gross_amount, 2, ',', '.') }} {{ $lastTx->currency }}</div>
                                <small class="text-muted">{{ optional($lastTx->payment_date)->format('d.m.Y') ?: '-' }}</small>
                            @else
                                <span class="text-muted">Islem yok</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Kayit bulunamadi.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($records, 'links'))
            <div class="card-footer">
                {{ $records->links() }}
            </div>
        @endif
    </div>
@endif

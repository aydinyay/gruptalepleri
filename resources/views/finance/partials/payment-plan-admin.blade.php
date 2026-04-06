@php
    $isSuperadminFinance = auth()->user()->role === 'superadmin';
    $storePlanRoute = $isSuperadminFinance ? 'superadmin.finance.payment-plan.store' : 'admin.finance.payment-plan.store';
    $updatePlanRoute = $isSuperadminFinance ? 'superadmin.finance.payment-plan.update' : 'admin.finance.payment-plan.update';
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold">Odeme Plani / Taksit Yonetimi</div>
    <div class="card-body">
        <form method="post" action="{{ route($storePlanRoute) }}" class="row g-2 mb-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label mb-1">Finans Kaydi</label>
                <select name="finance_record_id" class="form-select form-select-sm" required>
                    <option value="">Seciniz</option>
                    @foreach($openRecords as $recordOption)
                        <option value="{{ $recordOption->id }}">
                            #{{ $recordOption->id }} {{ $recordOption->document_ref ?: '-' }} -
                            Kalan {{ number_format((float) $recordOption->remaining_amount, 2, ',', '.') }} {{ $recordOption->currency }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">Taksit Adedi</label>
                <input type="number" min="1" max="24" name="installment_count" value="2" class="form-control form-control-sm" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1">İlk Vade</label>
                <input type="date" name="first_due_date" class="form-control form-control-sm" required>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Ara Gün</label>
                <input type="number" min="1" max="365" name="interval_days" value="30" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Toplam Tutar</label>
                <input type="number" min="0.01" step="0.01" name="total_amount" class="form-control form-control-sm" placeholder="Boş = kalan">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Kur</label>
                <input type="text" name="currency" value="TRY" maxlength="8" class="form-control form-control-sm" required>
            </div>
            <div class="col-12 col-md-10">
                <label class="form-label mb-1">Not</label>
                <input type="text" name="note" class="form-control form-control-sm" maxlength="2000">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Plan Olustur</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kayit</th>
                    <th>Vade</th>
                    <th>Plan Tutar</th>
                    <th>Odenen</th>
                    <th>Durum</th>
                    <th>Aksiyon</th>
                </tr>
                </thead>
                <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>{{ $plan->sequence }}</td>
                        <td>
                            <div class="fw-semibold">{{ $plan->record->document_ref ?? ('#'.$plan->finance_record_id) }}</div>
                            <small class="text-muted">{{ $plan->record->agencyUser->name ?? '-' }}</small>
                        </td>
                        <td>{{ optional($plan->due_date)->format('d.m.Y') ?: '-' }}</td>
                        <td>{{ number_format((float) $plan->amount, 2, ',', '.') }} {{ $plan->currency }}</td>
                        <td>{{ number_format((float) $plan->paid_amount, 2, ',', '.') }} {{ $plan->currency }}</td>
                        <td><span class="badge bg-secondary">{{ $plan->status }}</span></td>
                        <td>
                            @if(in_array($plan->status, ['planned', 'cancelled'], true))
                                <form method="post" action="{{ route($updatePlanRoute, $plan) }}" class="d-flex gap-1">
                                    @csrf
                                    @method('patch')
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="planned" @selected($plan->status === 'planned')>planned</option>
                                        <option value="cancelled" @selected($plan->status === 'cancelled')>cancelled</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary">Guncelle</button>
                                </form>
                            @else
                                <span class="text-muted">Otomatik</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">Odeme plani kaydi yok.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

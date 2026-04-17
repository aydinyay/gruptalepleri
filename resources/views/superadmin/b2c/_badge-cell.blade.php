@php
$badgeColors = [
    'Öne Çıkan'  => '#FF5533',
    'Popüler'    => '#3182ce',
    'Yeni'       => '#38a169',
    'Son Fırsat' => '#e53e3e',
    'İndirim'    => '#dd6b20',
    'Sınırlı'   => '#805ad5',
];
@endphp
@if($ci)
<form method="POST" action="{{ route('superadmin.b2c.catalog.set-badge', $ci) }}" class="d-flex align-items-center gap-1">
    @csrf
    <select name="badge_label" class="form-select form-select-sm" style="font-size:.74rem;padding:2px 4px;min-width:90px;" onchange="this.form.submit()">
        <option value="">—</option>
        @foreach(['Öne Çıkan','Popüler','Yeni','Son Fırsat','İndirim','Sınırlı'] as $bl)
        <option value="{{ $bl }}"
            @if(($ci->badge_label ?? '') === $bl) selected @endif
            style="background:{{ $badgeColors[$bl] ?? '#718096' }};color:#fff;">{{ $bl }}</option>
        @endforeach
    </select>
</form>
@else
<span class="text-muted small">—</span>
@endif

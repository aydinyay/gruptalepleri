@props([
    'panelClass' => '',
    'isCollapsible' => false,
    'collapseId' => 'charterAdvisoryCollapse',
    'title' => 'Canlı Talep Rehberi',
])

<div class="card charter-advisory-card {{ $panelClass }}">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="fw-semibold">
            <i class="fas fa-compass me-1 text-primary"></i>{{ $title }}
        </div>
        @if($isCollapsible)
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                Göster
            </button>
        @endif
    </div>
    <div class="card-body {{ $isCollapsible ? 'collapse' : '' }}" id="{{ $isCollapsible ? $collapseId : '' }}">
        <div class="charter-advisory-message mb-3 js-advisory-confidence">
            Talebiniz birden fazla operatör tarafından değerlendirilir.
        </div>

        <div class="charter-advisory-grid">
            <div class="charter-advisory-item">
                <div class="charter-advisory-label">Uygun Uçak Kategorisi</div>
                <div class="charter-advisory-value js-advisory-category">-</div>
            </div>
            <div class="charter-advisory-item">
                <div class="charter-advisory-label">Tahmini Uçuş Süresi</div>
                <div class="charter-advisory-value js-advisory-duration">-</div>
            </div>
            <div class="charter-advisory-item">
                <div class="charter-advisory-label">Talep Hazırlık Durumu</div>
                <div class="charter-advisory-value">
                    <span class="badge js-advisory-prep-badge bg-secondary">-</span>
                </div>
            </div>
            <div class="charter-advisory-item">
                <div class="charter-advisory-label">Operasyonel Uygunluk</div>
                <div class="charter-advisory-value">
                    <span class="badge js-advisory-operational-badge bg-secondary">-</span>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <div class="charter-advisory-label mb-1">Eksik Bilgi Önerileri</div>
            <ul class="charter-advisory-list js-advisory-suggestions">
                <li>Alanlar dolduruldukça canlı öneri güncellenir.</li>
            </ul>
        </div>

        <div class="mt-3">
            <div class="charter-advisory-label mb-2">Süreç Akışı</div>
            <div class="charter-timeline-wrap js-advisory-timeline"></div>
        </div>

        <div class="charter-advisory-disclaimer mt-3 js-advisory-disclaimer">
            Bu panel karar destek amaçlıdır. Nihai şartlar operatör değerlendirmesi sonrası netleşir.
        </div>
    </div>
</div>


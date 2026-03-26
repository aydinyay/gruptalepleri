<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceRecord;
use App\Models\LeisureClientOffer;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;
use App\Models\LeisureRequest;
use App\Models\LeisureSupplierQuote;
use App\Services\Leisure\TimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class BaseLeisureManagementController extends Controller
{
    abstract protected function productType(): string;

    abstract protected function routeKey(): string;

    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');

        $requests = LeisureRequest::query()
            ->where('product_type', $this->productType())
            ->with(['user', 'supplierQuotes', 'clientOffers', 'booking'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.leisure.index', [
            'requests' => $requests,
            'status' => $status,
            'productType' => $this->productType(),
            'routePrefix' => $this->routePrefix(),
            'panelRole' => $this->panelRole(),
            'navActive' => $this->routeKey(),
        ]);
    }

    public function show(LeisureRequest $leisureRequest)
    {
        abort_unless($leisureRequest->product_type === $this->productType(), 404);

        $leisureRequest->load([
            'user',
            'dinnerCruiseDetail',
            'yachtDetail',
            'extras.option',
            'supplierQuotes',
            'clientOffers.packageTemplate',
            'booking.clientOffer',
            'booking.payments',
        ]);

        $financeRecord = $leisureRequest->booking
            ? FinanceRecord::query()
                ->where('service_type', 'leisure_booking')
                ->where('service_id', $leisureRequest->booking->id)
                ->first()
            : null;

        return view('admin.leisure.show', [
            'leisureRequest' => $leisureRequest,
            'financeRecord' => $financeRecord,
            'packageTemplates' => LeisurePackageTemplate::query()
                ->where('product_type', $this->productType())
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            'mediaAssets' => LeisureMediaAsset::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('product_type')->orWhere('product_type', $this->productType());
                })
                ->orderBy('sort_order')
                ->limit(18)
                ->get(),
            'supplierBrief' => $this->buildSupplierBrief($leisureRequest),
            'productType' => $this->productType(),
            'routePrefix' => $this->routePrefix(),
            'panelRole' => $this->panelRole(),
            'navActive' => $this->routeKey(),
        ]);
    }

    public function storeSupplierQuote(Request $request, LeisureRequest $leisureRequest): RedirectResponse
    {
        abort_unless($leisureRequest->product_type === $this->productType(), 404);

        $validated = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'supplier_contact_name' => 'nullable|string|max:255',
            'supplier_email' => 'nullable|email|max:255',
            'supplier_phone' => 'nullable|string|max:40',
            'supplier_package_name' => 'nullable|string|max:255',
            'cost_total' => 'required|numeric|min:0',
            'currency' => 'required|string|max:8',
            'includes_text' => 'nullable|string|max:5000',
            'excludes_text' => 'nullable|string|max:5000',
            'supplier_note' => 'nullable|string|max:5000',
            'operation_note' => 'nullable|string|max:5000',
        ]);

        LeisureSupplierQuote::query()->create([
            'leisure_request_id' => $leisureRequest->id,
            'supplier_name' => $validated['supplier_name'],
            'supplier_contact_name' => $validated['supplier_contact_name'] ?? null,
            'supplier_email' => $validated['supplier_email'] ?? null,
            'supplier_phone' => $validated['supplier_phone'] ?? null,
            'supplier_package_name' => $validated['supplier_package_name'] ?? null,
            'cost_total' => $validated['cost_total'],
            'currency' => strtoupper($validated['currency']),
            'includes_json' => $this->parseBulletText($validated['includes_text'] ?? null),
            'excludes_json' => $this->parseBulletText($validated['excludes_text'] ?? null),
            'supplier_note' => $validated['supplier_note'] ?? null,
            'operation_note' => $validated['operation_note'] ?? null,
            'status' => 'received',
        ]);

        return back()->with('success', 'Tedarikci teklifi kaydedildi.');
    }

    public function storeClientOffer(
        Request $request,
        LeisureRequest $leisureRequest,
        TimelineService $timelineService
    ): RedirectResponse {
        abort_unless($leisureRequest->product_type === $this->productType(), 404);

        $validated = $request->validate([
            'supplier_quote_id' => 'nullable|integer|exists:leisure_supplier_quotes,id',
            'package_template_id' => 'required|integer|exists:leisure_package_templates,id',
            'total_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:8',
            'offer_note_tr' => 'nullable|string|max:5000',
            'offer_note_en' => 'nullable|string|max:5000',
            'media_asset_ids' => 'nullable|array',
            'media_asset_ids.*' => 'nullable|integer|exists:leisure_media_assets,id',
        ]);

        $supplierQuote = null;
        if (! empty($validated['supplier_quote_id'])) {
            $supplierQuote = LeisureSupplierQuote::query()
                ->where('leisure_request_id', $leisureRequest->id)
                ->findOrFail($validated['supplier_quote_id']);
        }

        $template = LeisurePackageTemplate::query()
            ->where('product_type', $this->productType())
            ->findOrFail($validated['package_template_id']);

        $mediaAssets = LeisureMediaAsset::query()
            ->whereIn('id', $validated['media_asset_ids'] ?? [])
            ->get();

        $timelines = $timelineService->build($leisureRequest);
        $hasLiveOffer = $leisureRequest->clientOffers()->whereIn('status', ['sent', 'accepted'])->exists();

        $templateIncludesTr = collect($template->includes_tr ?? [])->filter()->values()->all();
        $templateIncludesEn = collect($template->includes_en ?? [])->filter()->values()->all();
        $templateExcludesTr = collect($template->excludes_tr ?? [])->filter()->values()->all();
        $templateExcludesEn = collect($template->excludes_en ?? [])->filter()->values()->all();

        $supplierIncludes = collect($supplierQuote?->includes_json ?? [])->filter()->values()->all();
        $supplierExcludes = collect($supplierQuote?->excludes_json ?? [])->filter()->values()->all();

        $offer = LeisureClientOffer::query()->create([
            'leisure_request_id' => $leisureRequest->id,
            'supplier_quote_id' => $supplierQuote?->id,
            'package_template_id' => $template->id,
            'package_label' => $template->name_tr,
            'total_price' => $validated['total_price'],
            'per_person_price' => $leisureRequest->guest_count > 0
                ? round(((float) $validated['total_price']) / $leisureRequest->guest_count, 2)
                : null,
            'currency' => strtoupper($validated['currency']),
            'includes_snapshot' => [
                'tr' => $templateIncludesTr,
                'en' => $templateIncludesEn,
                'supplier' => $supplierIncludes,
            ],
            'excludes_snapshot' => [
                'tr' => $templateExcludesTr,
                'en' => $templateExcludesEn,
                'supplier' => $supplierExcludes,
            ],
            'extras_snapshot' => $leisureRequest->extras->map(function ($extra) {
                return [
                    'title' => $extra->title,
                    'agency_note' => $extra->agency_note,
                    'unit_price' => $extra->unit_price,
                    'quantity' => $extra->quantity,
                    'currency' => $extra->currency,
                    'status' => $extra->status,
                ];
            })->values()->all(),
            'media_snapshot' => $mediaAssets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'title_tr' => $asset->title_tr,
                    'title_en' => $asset->title_en,
                    'url' => $asset->resolvedUrl(),
                    'category' => $asset->category,
                    'media_type' => $asset->media_type,
                ];
            })->values()->all(),
            'timeline_tr' => $timelines['tr'] ?? null,
            'timeline_en' => $timelines['en'] ?? null,
            'offer_note_tr' => $validated['offer_note_tr'] ?? $template->summary_tr,
            'offer_note_en' => $validated['offer_note_en'] ?? $template->summary_en,
            'status' => 'sent',
            'shared_at' => now(),
        ]);

        $leisureRequest->update([
            'status' => $hasLiveOffer ? LeisureRequest::STATUS_REVISED : LeisureRequest::STATUS_OFFER_SENT,
        ]);

        return back()->with('success', 'Acente teklifi olusturuldu. Paylasim linki hazir.');
    }

    public function startOperation(LeisureRequest $leisureRequest): RedirectResponse
    {
        abort_unless($leisureRequest->product_type === $this->productType(), 404);
        $leisureRequest->load('booking');

        if (! $leisureRequest->booking) {
            return back()->with('error', 'Once kabul edilmis teklif ve booking olusmali.');
        }

        if ((float) $leisureRequest->booking->remaining_amount > 0.0001) {
            return back()->with('error', 'Odeme tamamlanmadan operasyon baslatilamaz.');
        }

        $leisureRequest->booking->update([
            'status' => 'in_operation',
        ]);

        $leisureRequest->update([
            'status' => LeisureRequest::STATUS_IN_OPERATION,
            'operated_at' => now(),
        ]);

        return back()->with('success', 'Operasyon durumu baslatildi.');
    }

    protected function routePrefix(): string
    {
        return $this->panelRole() . '.' . $this->routeKey();
    }

    protected function panelRole(): string
    {
        return request()->routeIs('superadmin.*') ? 'superadmin' : 'admin';
    }

    protected function parseBulletText(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($line) => trim((string) preg_replace('/^[\-\*\•\s]+/u', '', (string) $line)))
            ->filter()
            ->values()
            ->all();
    }

    protected function buildSupplierBrief(LeisureRequest $request): string
    {
        $lines = [
            $request->productLabel() . ' Talebi',
            'GTPNR: ' . $request->gtpnr,
            'Tarih: ' . optional($request->service_date)->format('d.m.Y'),
            'Kisi Sayisi: ' . $request->guest_count,
            'Transfer: ' . ($request->transfer_required ? 'Var' : 'Yok'),
            'Paket: ' . ($request->package_level ?: 'Belirtilmedi'),
            'Dil: ' . strtoupper((string) $request->language_preference),
            'Alkol: ' . ($request->alcohol_preference ?: 'Belirtilmedi'),
            'Menu: ' . ($request->menu_preference ?: 'Belirtilmedi'),
        ];

        if ($request->transfer_required) {
            $lines[] = 'Pickup: ' . collect([
                $request->transfer_region,
                $request->hotel_name,
                $request->guest_name,
                $request->guest_phone,
            ])->filter()->implode(' | ');
        }

        if ($request->product_type === LeisureRequest::PRODUCT_DINNER_CRUISE && $request->dinnerCruiseDetail) {
            $lines[] = 'Seans: ' . ($request->dinnerCruiseDetail->session_time ?: 'Belirtilmedi');
            $lines[] = 'Iskele: ' . ($request->dinnerCruiseDetail->pier_name ?: 'Belirtilmedi');
            $lines[] = 'Yetiskin/Cocuk/Bebek: ' . implode(' / ', [
                $request->dinnerCruiseDetail->adult_count ?? 0,
                $request->dinnerCruiseDetail->child_count ?? 0,
                $request->dinnerCruiseDetail->infant_count ?? 0,
            ]);
            $lines[] = 'Kutlama Tipi: ' . ($request->dinnerCruiseDetail->celebration_type ?: 'Belirtilmedi');
            $lines[] = 'Shared Cruise: ' . ($request->dinnerCruiseDetail->shared_cruise ? 'Evet' : 'Hayir');
        }

        if ($request->product_type === LeisureRequest::PRODUCT_YACHT && $request->yachtDetail) {
            $lines[] = 'Baslangic Saati: ' . ($request->yachtDetail->start_time ?: 'Belirtilmedi');
            $lines[] = 'Sure: ' . ($request->yachtDetail->duration_hours ?: '-') . ' saat';
            $lines[] = 'Marina: ' . ($request->yachtDetail->marina_name ?: 'Belirtilmedi');
            $lines[] = 'Rota: ' . ($request->yachtDetail->route_plan ?: 'Belirtilmedi');
            $lines[] = 'Etkinlik Tipi: ' . ($request->yachtDetail->event_type ?: 'Belirtilmedi');
            $lines[] = 'Tekne Stili: ' . ($request->yachtDetail->vessel_style ?: 'Belirtilmedi');
        }

        if ($request->extras->isNotEmpty()) {
            $lines[] = 'Ekstra Talepler:';
            foreach ($request->extras as $extra) {
                $lines[] = '- ' . $extra->title . ($extra->agency_note ? ' (' . $extra->agency_note . ')' : '');
            }
        }

        if ($request->notes) {
            $lines[] = 'Not: ' . $request->notes;
        }

        if ($request->extra_requests) {
            $lines[] = 'Ekstra Not: ' . $request->extra_requests;
        }

        return implode(PHP_EOL, $lines);
    }
}

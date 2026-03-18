<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CharterBooking;
use App\Models\CharterExtra;
use App\Models\CharterPayment;
use App\Models\CharterQuote;
use App\Models\CharterRequest;
use App\Models\CharterSalesQuote;
use App\Models\CharterSupplierQuote;
use App\Services\Charter\MarkupService;
use App\Services\Charter\PaymentService;
use App\Services\Charter\RFQService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CharterController extends Controller
{
    private function assertAuthorized(): void
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'], true), 403);
    }

    private function routePrefix(): string
    {
        return auth()->user()->role === 'superadmin' ? 'superadmin.charter' : 'admin.charter';
    }

    public function index(Request $request)
    {
        $this->assertAuthorized();

        $transportType = (string) $request->query('transport_type', '');
        $status = (string) $request->query('status', '');

        $query = CharterRequest::query()->with(['user', 'salesQuotes', 'booking'])->latest();

        if (in_array($transportType, [CharterRequest::TYPE_JET, CharterRequest::TYPE_HELICOPTER, CharterRequest::TYPE_AIRLINER], true)) {
            $query->where('transport_type', $transportType);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->withQueryString();

        return view('admin.charter.index', [
            'requests' => $requests,
            'routePrefix' => $this->routePrefix(),
            'transportType' => $transportType,
            'status' => $status,
        ]);
    }

    public function show(CharterRequest $charterRequest)
    {
        $this->assertAuthorized();

        $charterRequest->load([
            'user',
            'jetDetail',
            'helicopterDetail',
            'airlinerDetail',
            'extras',
            'supplierQuotes',
            'salesQuotes.supplierQuote',
            'booking.payments',
            'quotes',
        ]);

        return view('admin.charter.show', [
            'charterRequest' => $charterRequest,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function storeSupplierQuote(Request $request, CharterRequest $charterRequest): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'model_name' => 'nullable|string|max:255',
            'aircraft_image_url' => 'nullable|url|max:2048',
            'supplier_price' => 'required|numeric|min:1',
            'currency' => 'required|string|max:8',
            'supplier_note' => 'nullable|string|max:4000',
            'whatsapp_text' => 'nullable|string|max:4000',
        ]);

        $aiScore = 50.0;
        $base = (float) $charterRequest->ai_price_min;
        if ($base > 0) {
            $ratio = (float) $validated['supplier_price'] / $base;
            $aiScore = $ratio <= 1 ? 90 : max(35, 95 - (($ratio - 1) * 70));
        }

        $supplierQuote = CharterSupplierQuote::query()->create([
            'charter_request_id' => $charterRequest->id,
            'supplier_name' => $validated['supplier_name'],
            'model_name' => $validated['model_name'] ?? null,
            'aircraft_image_url' => $validated['aircraft_image_url'] ?? null,
            'supplier_price' => $validated['supplier_price'],
            'currency' => strtoupper($validated['currency']),
            'supplier_note' => $validated['supplier_note'] ?? null,
            'whatsapp_text' => $validated['whatsapp_text'] ?? null,
            'ai_analysis' => [
                'label' => $aiScore >= 80 ? 'avantajli' : ($aiScore >= 55 ? 'denge' : 'riskli'),
                'relative_to_ai_min' => $base,
            ],
            'ai_score' => round($aiScore, 2),
            'status' => 'received',
        ]);

        CharterQuote::query()->create([
            'charter_request_id' => $charterRequest->id,
            'quote_type' => 'supplier',
            'status' => 'received',
            'title' => 'Supplier Teklifi',
            'description' => $supplierQuote->supplier_name . ' teklif girisi',
            'payload' => ['supplier_quote_id' => $supplierQuote->id, 'ai_score' => $supplierQuote->ai_score],
        ]);

        return back()->with('success', 'Supplier teklifi kaydedildi.');
    }

    public function priceExtra(Request $request, CharterRequest $charterRequest, CharterExtra $extra): RedirectResponse
    {
        $this->assertAuthorized();
        abort_unless($extra->charter_request_id === $charterRequest->id, 422);

        $validated = $request->validate([
            'admin_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:8',
            'status' => 'required|in:pending_pricing,priced,rejected',
        ]);

        $extra->update([
            'admin_price' => $validated['admin_price'],
            'currency' => strtoupper($validated['currency']),
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Ek hizmet fiyatlama guncellendi.');
    }

    public function createSalesQuote(Request $request, CharterRequest $charterRequest, MarkupService $markupService): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'supplier_quote_id' => 'required|integer|exists:charter_supplier_quotes,id',
            'override_markup_percent' => 'nullable|numeric|min:0|max:99.99',
            'override_min_profit' => 'nullable|numeric|min:0',
            'override_reason' => 'nullable|string|max:1000',
        ]);

        $supplierQuote = CharterSupplierQuote::query()
            ->where('charter_request_id', $charterRequest->id)
            ->findOrFail((int) $validated['supplier_quote_id']);

        $calc = $markupService->calculate(
            (float) $supplierQuote->supplier_price,
            (string) $charterRequest->transport_type,
            isset($validated['override_markup_percent']) ? (float) $validated['override_markup_percent'] : null,
            isset($validated['override_min_profit']) ? (float) $validated['override_min_profit'] : null
        );

        $extrasTotal = (float) $charterRequest->extras()
            ->where('status', 'priced')
            ->sum('admin_price');

        $finalSalePrice = round(((float) $calc['sale_price']) + $extrasTotal, 2);

        $salesQuote = CharterSalesQuote::query()->create([
            'charter_request_id' => $charterRequest->id,
            'supplier_quote_id' => $supplierQuote->id,
            'base_supplier_price' => $supplierQuote->supplier_price,
            'markup_percent' => $calc['markup_percent'],
            'min_profit' => $calc['min_profit'],
            'sale_price' => $finalSalePrice,
            'currency' => $supplierQuote->currency,
            'is_override' => isset($validated['override_markup_percent']) || isset($validated['override_min_profit']),
            'override_reason' => $validated['override_reason'] ?? null,
            'status' => 'sent',
        ]);

        $charterRequest->update(['status' => CharterRequest::STATUS_QUOTED_TO_AGENCY]);

        CharterQuote::query()->create([
            'charter_request_id' => $charterRequest->id,
            'quote_type' => 'sales',
            'status' => 'sent',
            'title' => 'Acente Satis Teklifi',
            'description' => 'Satis teklifi #' . $salesQuote->id . ' olusturuldu',
            'payload' => [
                'sales_quote_id' => $salesQuote->id,
                'sale_price' => $salesQuote->sale_price,
                'extras_total' => $extrasTotal,
            ],
        ]);

        return back()->with('success', 'Satis teklifi olusturuldu ve acenteye hazir.');
    }

    public function sendRfq(Request $request, CharterRequest $charterRequest, RFQService $rfqService): RedirectResponse
    {
        $this->assertAuthorized();

        $confirmedId = (int) $request->input('request_id_confirm', 0);
        if ($confirmedId > 0 && $confirmedId !== (int) $charterRequest->id) {
            return back()->with('error', 'RFQ gonderimi durduruldu: talep dogrulamasi basarisiz.');
        }

        $charterRequest->refresh();
        $result = $rfqService->dispatch($charterRequest);

        $routeLabel = strtoupper((string) ($charterRequest->from_iata ?: '-')) . '-' . strtoupper((string) ($charterRequest->to_iata ?: '-'));
        return back()->with(
            'success',
            "RFQ #{$charterRequest->id} ({$routeLabel}) dagitimi tamamlandi. Gonderilen: {$result['sent']} / Hata: {$result['failed']}"
        );
    }

    public function storePayment(Request $request, CharterBooking $booking, PaymentService $paymentService): RedirectResponse
    {
        $this->assertAuthorized();

        $validated = $request->validate([
            'method' => 'required|in:card,bank_transfer',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|max:8',
            'provider' => 'nullable|string|max:30',
            'provider_reference' => 'nullable|string|max:255',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $paymentService->createPayment($booking, $validated);

        return back()->with('success', 'Odeme kaydi eklendi. Onay bekliyor.');
    }

    public function approvePayment(Request $request, CharterPayment $payment, PaymentService $paymentService): RedirectResponse
    {
        $this->assertAuthorized();
        $paymentService->approve($payment, (int) auth()->id(), (string) $request->input('admin_note'));
        return back()->with('success', 'Odeme onaylandi.');
    }

    public function rejectPayment(Request $request, CharterPayment $payment, PaymentService $paymentService): RedirectResponse
    {
        $this->assertAuthorized();
        $paymentService->reject($payment, (int) auth()->id(), (string) $request->input('admin_note'));
        return back()->with('success', 'Odeme reddedildi.');
    }

    public function startOperation(CharterBooking $booking): RedirectResponse
    {
        $this->assertAuthorized();

        if ((float) $booking->remaining_amount > 0.0001) {
            return back()->with('error', 'Odeme tamamlanmadan operasyon baslatilamaz.');
        }

        DB::transaction(function () use ($booking): void {
            $booking->update(['status' => 'operation_started']);
            $booking->request()->update(['status' => CharterRequest::STATUS_OPERATION_STARTED]);
        });

        return back()->with('success', 'Operasyon baslatildi.');
    }
}

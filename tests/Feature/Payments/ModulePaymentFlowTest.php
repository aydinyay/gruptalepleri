<?php

namespace Tests\Feature\Payments;

use App\Models\CharterBooking;
use App\Models\CharterRequest;
use App\Models\CharterSalesQuote;
use App\Models\FinanceTransaction;
use App\Models\LeisureBooking;
use App\Models\LeisureClientOffer;
use App\Models\LeisurePayment;
use App\Models\LeisureRequest;
use App\Models\Offer;
use App\Models\Request as LegacyRequest;
use App\Models\RequestPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModulePaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('transfer.paynkolay.base_url', 'https://paynkolay.test');
        config()->set('transfer.paynkolay.sx', 'test-sx');
        config()->set('transfer.paynkolay.merchant_secret_key', 'test-secret');
        config()->set('transfer.paynkolay.environment', 'API');
        config()->set('transfer.paynkolay.currency_number', '949');
        config()->set('transfer.paynkolay.use_3d', true);
        config()->set('transfer.paynkolay.installment', 1);

        Http::fake([
            'https://paynkolay.test/*' => Http::response([
                'RESPONSE_CODE' => '2',
                'url' => 'https://paynkolay.test/checkout/demo-link',
                'referenceCode' => 'PK-REF-TEST',
            ], 200),
        ]);
    }

    public function test_acente_can_start_legacy_payment_and_complete_with_simulation(): void
    {
        $agencyUser = User::factory()->create([
            'role' => 'acente',
        ]);

        $legacyRequest = LegacyRequest::query()->create([
            'gtpnr' => 'LEGTST',
            'user_id' => $agencyUser->id,
            'type' => 'group_flight',
            'status' => 'depozitoda',
            'agency_name' => 'Test Agency',
            'phone' => '5550000000',
            'email' => 'agency@example.com',
            'pax_total' => 10,
        ]);

        Offer::query()->create([
            'request_id' => $legacyRequest->id,
            'airline' => 'TK',
            'currency' => 'TRY',
            'price_per_pax' => 1000,
            'total_price' => 10000,
            'is_visible' => true,
            'is_accepted' => true,
        ]);

        RequestPayment::query()->create([
            'request_id' => $legacyRequest->id,
            'sequence' => 1,
            'payment_type' => 'depozito',
            'payment_method' => 'havale',
            'amount' => 2000,
            'currency' => 'TRY',
            'status' => 'alindi',
            'created_by' => 'seed',
        ]);

        $this->actingAs($agencyUser)
            ->post(route('acente.requests.gateway-payment.start', $legacyRequest->gtpnr))
            ->assertRedirect('https://paynkolay.test/checkout/demo-link');

        $gatewayPayment = RequestPayment::query()
            ->where('request_id', $legacyRequest->id)
            ->where('gateway_provider', 'paynkolay')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('bekleniyor', $gatewayPayment->status);
        $this->assertSame(8000.0, (float) $gatewayPayment->amount);
        $this->assertNotNull($gatewayPayment->gateway_internal_reference);

        $this->actingAs($agencyUser)
            ->get(route('payment.paynkolay.simulate', [
                'reference' => $gatewayPayment->gateway_internal_reference,
                'status' => 'paid',
            ]))
            ->assertRedirect(route('acente.requests.show', $legacyRequest->gtpnr));

        $gatewayPayment->refresh();
        $this->assertSame('alindi', $gatewayPayment->status);
        $this->assertSame('paid', $gatewayPayment->gateway_status);

        $this->assertDatabaseHas('finance_transactions', [
            'source_type' => 'request_payment',
            'source_id' => $gatewayPayment->id,
            'status' => 'approved',
        ]);
    }

    public function test_legacy_gateway_start_uses_remaining_amount_even_if_client_posts_custom_amount(): void
    {
        $agencyUser = User::factory()->create([
            'role' => 'acente',
        ]);

        $legacyRequest = LegacyRequest::query()->create([
            'gtpnr' => 'LEGFULL',
            'user_id' => $agencyUser->id,
            'type' => 'group_flight',
            'status' => 'depozitoda',
            'agency_name' => 'Full Payment Agency',
            'phone' => '5550000001',
            'email' => 'full@example.com',
            'pax_total' => 10,
        ]);

        Offer::query()->create([
            'request_id' => $legacyRequest->id,
            'airline' => 'TK',
            'currency' => 'TRY',
            'price_per_pax' => 1200,
            'total_price' => 12000,
            'is_visible' => true,
            'is_accepted' => true,
        ]);

        RequestPayment::query()->create([
            'request_id' => $legacyRequest->id,
            'sequence' => 1,
            'payment_type' => 'depozito',
            'payment_method' => 'havale',
            'amount' => 3000,
            'currency' => 'TRY',
            'status' => 'alindi',
            'created_by' => 'seed',
        ]);

        $this->actingAs($agencyUser)
            ->post(route('acente.requests.gateway-payment.start', $legacyRequest->gtpnr), [
                'amount' => 1,
            ])
            ->assertRedirect('https://paynkolay.test/checkout/demo-link');

        $payment = RequestPayment::query()
            ->where('request_id', $legacyRequest->id)
            ->where('gateway_provider', 'paynkolay')
            ->latest('id')
            ->firstOrFail();

        // Full payment rule: posted custom amount is ignored, remaining amount is used.
        $this->assertSame(9000.0, (float) $payment->amount);
    }

    public function test_admin_can_start_charter_payment_and_booking_becomes_paid_after_callback(): void
    {
        $adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
        $agencyUser = User::factory()->create([
            'role' => 'acente',
        ]);

        $charterRequest = CharterRequest::query()->create([
            'user_id' => $agencyUser->id,
            'requester_type' => 'agency',
            'transport_type' => CharterRequest::TYPE_JET,
            'status' => CharterRequest::STATUS_PENDING_PAYMENT,
            'name' => 'Agency',
            'phone' => '5550000011',
            'email' => 'agency@example.com',
            'from_iata' => 'IST',
            'to_iata' => 'ESB',
            'departure_date' => now()->addDays(7)->toDateString(),
            'pax' => 6,
            'ai_currency' => 'TRY',
        ]);

        $salesQuote = CharterSalesQuote::query()->create([
            'charter_request_id' => $charterRequest->id,
            'base_supplier_price' => 5000,
            'markup_percent' => 0,
            'min_profit' => 0,
            'sale_price' => 5000,
            'currency' => 'TRY',
            'status' => 'accepted',
        ]);

        $booking = CharterBooking::query()->create([
            'charter_request_id' => $charterRequest->id,
            'sales_quote_id' => $salesQuote->id,
            'status' => 'pending_payment',
            'total_amount' => 5000,
            'total_paid' => 0,
            'remaining_amount' => 5000,
        ]);

        $this->actingAs($adminUser)
            ->post(route('admin.charter.payments.gateway-start', $booking))
            ->assertRedirect('https://paynkolay.test/checkout/demo-link');

        $payment = $booking->payments()->latest('id')->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertNotNull($payment->internal_reference);

        $this->actingAs($adminUser)
            ->get(route('payment.paynkolay.simulate', [
                'reference' => $payment->internal_reference,
                'status' => 'paid',
            ]))
            ->assertRedirect(route('admin.charter.show', $charterRequest));

        $payment->refresh();
        $booking->refresh();
        $charterRequest->refresh();

        $this->assertSame('approved', $payment->status);
        $this->assertSame('paid', $booking->status);
        $this->assertSame(0.0, (float) $booking->remaining_amount);
        $this->assertSame(CharterRequest::STATUS_PAID, $charterRequest->status);

        $this->assertDatabaseHas('finance_transactions', [
            'source_type' => 'charter_payment',
            'source_id' => $payment->id,
            'status' => 'approved',
        ]);
    }

    public function test_charter_gateway_start_converts_non_try_amount_with_tcmb_snapshot(): void
    {
        $adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
        $agencyUser = User::factory()->create([
            'role' => 'acente',
        ]);

        $charterRequest = CharterRequest::query()->create([
            'user_id' => $agencyUser->id,
            'requester_type' => 'agency',
            'transport_type' => CharterRequest::TYPE_JET,
            'status' => CharterRequest::STATUS_PENDING_PAYMENT,
            'name' => 'Agency',
            'phone' => '5550000012',
            'email' => 'agency2@example.com',
            'from_iata' => 'IST',
            'to_iata' => 'ADB',
            'departure_date' => now()->addDays(5)->toDateString(),
            'pax' => 4,
            'ai_currency' => 'EUR',
        ]);

        $salesQuote = CharterSalesQuote::query()->create([
            'charter_request_id' => $charterRequest->id,
            'base_supplier_price' => 100,
            'markup_percent' => 0,
            'min_profit' => 0,
            'sale_price' => 100,
            'currency' => 'EUR',
            'status' => 'accepted',
        ]);

        $booking = CharterBooking::query()->create([
            'charter_request_id' => $charterRequest->id,
            'sales_quote_id' => $salesQuote->id,
            'status' => 'pending_payment',
            'total_amount' => 100,
            'total_paid' => 0,
            'remaining_amount' => 100,
        ]);

        config()->set('services.tcmb.url', 'https://tcmb.test/today.xml');
        Http::fake([
            'https://tcmb.test/today.xml' => Http::response(
                <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Tarih_Date>
  <Currency CurrencyCode="EUR">
    <ForexSelling>43.5000</ForexSelling>
  </Currency>
</Tarih_Date>
XML,
                200
            ),
            'https://paynkolay.test/*' => Http::response([
                'RESPONSE_CODE' => '2',
                'url' => 'https://paynkolay.test/checkout/demo-link',
                'referenceCode' => 'PK-REF-EUR',
            ], 200),
        ]);

        $this->actingAs($adminUser)
            ->post(route('admin.charter.payments.gateway-start', $booking))
            ->assertRedirect('https://paynkolay.test/checkout/demo-link');

        $payment = $booking->payments()->latest('id')->firstOrFail();
        $this->assertSame('EUR', $payment->currency);
        $this->assertSame(100.0, (float) $payment->amount);
        $this->assertSame(4350.0, (float) $payment->charged_try_amount);
        $this->assertSame(43.5, round((float) $payment->fx_rate, 1));
        $this->assertSame('EUR', (string) $payment->source_currency);
        $this->assertNotNull($payment->fx_timestamp);
    }

    public function test_acente_can_start_leisure_payment_and_booking_updates_after_callback(): void
    {
        $agencyUser = User::factory()->create([
            'role' => 'acente',
        ]);

        $leisureRequest = LeisureRequest::query()->create([
            'user_id' => $agencyUser->id,
            'gtpnr' => 'LSR0001',
            'product_type' => LeisureRequest::PRODUCT_DINNER_CRUISE,
            'status' => LeisureRequest::STATUS_APPROVED,
            'service_date' => now()->addDays(3)->toDateString(),
            'guest_count' => 5,
            'transfer_required' => false,
            'language_preference' => 'tr',
            'package_level' => 'premium',
        ]);

        $offer = LeisureClientOffer::query()->create([
            'leisure_request_id' => $leisureRequest->id,
            'package_label' => 'Premium',
            'total_price' => 4200,
            'currency' => 'TRY',
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $booking = LeisureBooking::query()->create([
            'leisure_request_id' => $leisureRequest->id,
            'client_offer_id' => $offer->id,
            'status' => 'pending_payment',
            'total_amount' => 4200,
            'total_paid' => 0,
            'remaining_amount' => 4200,
            'currency' => 'TRY',
        ]);

        $this->actingAs($agencyUser)
            ->post(route('acente.leisure.payments.gateway-start', $booking))
            ->assertRedirect('https://paynkolay.test/checkout/demo-link');

        /** @var LeisurePayment $payment */
        $payment = LeisurePayment::query()->where('leisure_booking_id', $booking->id)->latest('id')->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertNotNull($payment->internal_reference);

        $this->actingAs($agencyUser)
            ->get(route('payment.paynkolay.simulate', [
                'reference' => $payment->internal_reference,
                'status' => 'paid',
            ]))
            ->assertRedirect(route('acente.dinner-cruise.show', $leisureRequest));

        $payment->refresh();
        $booking->refresh();

        $this->assertSame('approved', $payment->status);
        $this->assertSame('paid', $booking->status);
        $this->assertSame(0.0, (float) $booking->remaining_amount);

        $this->assertDatabaseHas('finance_transactions', [
            'source_type' => 'leisure_payment',
            'source_id' => $payment->id,
            'status' => 'approved',
        ]);
    }

    public function test_callback_with_invalid_hash_is_rejected_and_payment_state_is_not_changed(): void
    {
        $agencyUser = User::factory()->create([
            'role' => 'acente',
        ]);

        $legacyRequest = LegacyRequest::query()->create([
            'gtpnr' => 'LEGHASH',
            'user_id' => $agencyUser->id,
            'type' => 'group_flight',
            'status' => 'depozitoda',
            'agency_name' => 'Hash Agency',
            'phone' => '5550000002',
            'email' => 'hash@example.com',
            'pax_total' => 6,
        ]);

        Offer::query()->create([
            'request_id' => $legacyRequest->id,
            'airline' => 'TK',
            'currency' => 'TRY',
            'price_per_pax' => 1000,
            'total_price' => 6000,
            'is_visible' => true,
            'is_accepted' => true,
        ]);

        $this->actingAs($agencyUser)
            ->post(route('acente.requests.gateway-payment.start', $legacyRequest->gtpnr))
            ->assertRedirect('https://paynkolay.test/checkout/demo-link');

        $gatewayPayment = RequestPayment::query()
            ->where('request_id', $legacyRequest->id)
            ->where('gateway_provider', 'paynkolay')
            ->latest('id')
            ->firstOrFail();

        $this->post(route('payment.paynkolay.success'), [
            'clientRefCode' => $gatewayPayment->gateway_internal_reference,
            'response_code' => '2',
            // hashDatav2 intentionally omitted
        ])->assertStatus(400);

        $gatewayPayment->refresh();
        $this->assertSame('bekleniyor', $gatewayPayment->status);
        $this->assertSame('pending', $gatewayPayment->gateway_status);
        $this->assertNull($gatewayPayment->processed_at);
    }
}

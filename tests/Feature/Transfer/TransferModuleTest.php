<?php

namespace Tests\Feature\Transfer;

use App\Models\Agency;
use App\Models\SistemAyar;
use App\Models\TransferAirport;
use App\Models\TransferBooking;
use App\Models\TransferCancellationPolicy;
use App\Models\TransferPaymentTransaction;
use App\Models\TransferPricingRule;
use App\Models\TransferQuoteLock;
use App\Models\TransferSupplier;
use App\Models\TransferSupplierCoverage;
use App\Models\TransferVehicleType;
use App\Models\TransferZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Services\Transfer\PaynkolayService;
use Tests\TestCase;

class TransferModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('transfer.provider', 'internal');
        config()->set('transfer.paynkolay.sx', '');
        config()->set('transfer.paynkolay.merchant_secret_key', '');
        config()->set('transfer.paynkolay.merchant_no', '');

        SistemAyar::set(SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_TEXT, 'Varsayilan transfer tedarikci sozlesmesi');
        SistemAyar::set(SistemAyar::KEY_TRANSFER_SUPPLIER_TERMS_VERSION, '1');
    }

    public function test_transfer_group_renders_and_is_active_for_all_core_roles(): void
    {
        $acente = User::factory()->create(['role' => 'acente']);
        $admin = User::factory()->create(['role' => 'admin']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($acente)
            ->get(route('acente.transfer.index'))
            ->assertOk()
            ->assertSee('data-gt-active-group="transfer"', false)
            ->assertSee('data-gt-role="acente"', false);

        $this->actingAs($admin)
            ->get(route('admin.transfer.index'))
            ->assertOk()
            ->assertSee('data-gt-active-group="transfer"', false)
            ->assertSee('data-gt-role="admin"', false);

        $this->actingAs($superadmin)
            ->get(route('superadmin.transfer.index'))
            ->assertOk()
            ->assertSee('data-gt-active-group="transfer"', false)
            ->assertSee('data-gt-role="superadmin"', false);
    }

    public function test_acente_charter_create_view_no_longer_renders_transfer_widget(): void
    {
        $acente = User::factory()->create(['role' => 'acente']);

        $response = $this->actingAs($acente)->get(route('acente.charter.create'));

        $response->assertOk();
        $response->assertDontSee('Transfer Ara (Widget)');
        $response->assertDontSee('transferWidgetOpenLink');
        $response->assertDontSee('transferWidgetInlineBtn');
    }

    public function test_internal_airports_and_zones_endpoints_return_seeded_json(): void
    {
        $acente = User::factory()->create(['role' => 'acente']);
        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();

        $airportsResponse = $this->actingAs($acente)->getJson(route('acente.transfer.airports'));
        $airportsResponse->assertOk();
        $airportsResponse->assertJsonPath('ok', true);
        $this->assertSame('IST', strtoupper((string) data_get($airportsResponse->json(), 'data.0.code')));

        $zonesResponse = $this->actingAs($acente)->getJson(route('acente.transfer.zones', ['airport_id' => $airport->id]));
        $zonesResponse->assertOk();
        $zonesResponse->assertJsonPath('ok', true);
        $this->assertNotEmpty((array) data_get($zonesResponse->json(), 'data', []));
    }

    public function test_internal_search_returns_ranked_options_and_local_checkout_url(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente']);

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $payload = [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'pax' => 2,
            'currency' => 'TRY',
        ];

        $response = $this->actingAs($acente)->postJson(route('acente.transfer.search'), $payload);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $this->assertNotEmpty((array) data_get($response->json(), 'data.options', []));
        $this->assertStringContainsString('/acente/transfer/checkout/', (string) data_get($response->json(), 'data.options.0.booking_url'));
        $this->assertDatabaseCount('transfer_quote_locks', 1);
    }

    public function test_quote_token_expired_then_checkout_redirects_back_to_transfer_search(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente']);

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $searchResponse = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'pax' => 2,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($searchResponse->json(), 'data.options.0.quote_token');
        $quote = TransferQuoteLock::query()->where('token', $quoteToken)->firstOrFail();
        $quote->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($acente)
            ->get(route('acente.transfer.checkout.show', ['quoteToken' => $quoteToken]))
            ->assertRedirect(route('acente.transfer.index'))
            ->assertSessionHas('error');
    }

    public function test_paynkolay_success_callback_is_idempotent_and_sets_booking_confirmed_once(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente', 'phone' => '5551234567']);
        config()->set('transfer.paynkolay.merchant_secret_key', 'test-secret-key');
        config()->set('transfer.paynkolay.merchant_no', '987654');

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $search = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(3)->format('Y-m-d'),
            'pickup_time' => '11:00',
            'pax' => 2,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($search->json(), 'data.options.0.quote_token');

        $bookResponse = $this->actingAs($acente)->post(route('acente.transfer.checkout.book', ['quoteToken' => $quoteToken]), [
            'contact_name' => 'Test User',
            'contact_phone' => '5551234567',
            'notes' => 'test',
        ]);
        $bookResponse->assertRedirect();

        $booking = TransferBooking::query()->latest('id')->firstOrFail();
        $payment = TransferPaymentTransaction::query()->where('transfer_booking_id', $booking->id)->latest('id')->firstOrFail();

        $payload = [
            'MERCHANT_NO' => '987654',
            'REFERENCE_CODE' => 'PK-REF-123',
            'CLIENT_REFERENCE_CODE' => $payment->reference,
            'AUTH_CODE' => 'AUTH-987',
            'RESPONSE_CODE' => '2',
            'USE_3D' => 'true',
            'RND' => '2026-03-25 14:00:00',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => number_format((float) $payment->amount, 2, '.', ''),
            'CURRENCY_CODE' => '949',
        ];

        $payload['hashDataV2'] = app(PaynkolayService::class)->responseHashFromPayload(
            $payload,
            'test-secret-key'
        );

        $this->post(route('transfer.payment.paynkolay.success'), $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->post(route('transfer.payment.paynkolay.success'), $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame('paid', (string) $payment->fresh()->status);
        $this->assertSame(TransferBooking::STATUS_CONFIRMED, (string) $booking->fresh()->status);
        $this->assertDatabaseCount('transfer_settlement_entries', 1);
    }

    public function test_paynkolay_success_callback_rejects_invalid_hash(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente', 'phone' => '5552223344']);
        config()->set('transfer.paynkolay.merchant_secret_key', 'test-secret-key');
        config()->set('transfer.paynkolay.merchant_no', '987654');

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $search = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '12:00',
            'pax' => 2,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($search->json(), 'data.options.0.quote_token');

        $this->actingAs($acente)->post(route('acente.transfer.checkout.book', ['quoteToken' => $quoteToken]), [
            'contact_name' => 'Test User',
            'contact_phone' => '5552223344',
            'notes' => 'test',
        ])->assertRedirect();

        $booking = TransferBooking::query()->latest('id')->firstOrFail();
        $payment = TransferPaymentTransaction::query()->where('transfer_booking_id', $booking->id)->latest('id')->firstOrFail();

        $payload = [
            'MERCHANT_NO' => '987654',
            'REFERENCE_CODE' => 'PK-REF-FAIL',
            'CLIENT_REFERENCE_CODE' => $payment->reference,
            'AUTH_CODE' => 'AUTH-FAIL',
            'RESPONSE_CODE' => '2',
            'USE_3D' => 'true',
            'RND' => '2026-03-25 14:30:00',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => number_format((float) $payment->amount, 2, '.', ''),
            'CURRENCY_CODE' => '949',
            'hashDataV2' => 'invalid-hash',
        ];

        $this->post(route('transfer.payment.paynkolay.success'), $payload)
            ->assertStatus(400)
            ->assertJsonPath('ok', false);

        $this->assertSame('pending', (string) $payment->fresh()->status);
        $this->assertSame(TransferBooking::STATUS_PAYMENT_PENDING, (string) $booking->fresh()->status);
    }

    public function test_paynkolay_fail_callback_marks_booking_failed(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente', 'phone' => '5558887766']);
        config()->set('transfer.paynkolay.merchant_secret_key', 'test-secret-key');
        config()->set('transfer.paynkolay.merchant_no', '987654');

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $search = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '16:00',
            'pax' => 2,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($search->json(), 'data.options.0.quote_token');

        $this->actingAs($acente)->post(route('acente.transfer.checkout.book', ['quoteToken' => $quoteToken]), [
            'contact_name' => 'Test User',
            'contact_phone' => '5558887766',
            'notes' => 'test',
        ])->assertRedirect();

        $booking = TransferBooking::query()->latest('id')->firstOrFail();
        $payment = TransferPaymentTransaction::query()->where('transfer_booking_id', $booking->id)->latest('id')->firstOrFail();

        $payload = [
            'MERCHANT_NO' => '987654',
            'REFERENCE_CODE' => 'PK-REF-FAIL',
            'CLIENT_REFERENCE_CODE' => $payment->reference,
            'AUTH_CODE' => '0',
            'RESPONSE_CODE' => '0',
            'USE_3D' => 'true',
            'RND' => '2026-03-25 15:10:00',
            'INSTALLMENT' => '1',
            'AUTHORIZATION_AMOUNT' => number_format((float) $payment->amount, 2, '.', ''),
            'CURRENCY_CODE' => '949',
        ];

        $payload['hashDataV2'] = app(PaynkolayService::class)->responseHashFromPayload(
            $payload,
            'test-secret-key'
        );

        $this->post(route('transfer.payment.paynkolay.fail'), $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame('failed', (string) $payment->fresh()->status);
        $this->assertSame(TransferBooking::STATUS_FAILED, (string) $booking->fresh()->status);
    }

    public function test_paynkolay_request_hash_matches_document_formula(): void
    {
        $service = app(PaynkolayService::class);

        $hash = $service->requestHashForPayment(
            sx: 'SX123',
            clientRefCode: 'REF456',
            amount: '100.00',
            successUrl: 'https://example.com/success',
            failUrl: 'https://example.com/fail',
            rnd: '25-03-2026 14:00:00',
            customerKey: '',
            merchantSecretKey: 'SECRET789',
        );

        $raw = 'SX123|REF456|100.00|https://example.com/success|https://example.com/fail|25-03-2026 14:00:00||SECRET789';
        $expected = base64_encode(hash('sha512', mb_convert_encoding($raw, 'UTF-8'), true));

        $this->assertSame($expected, $hash);
    }

    public function test_checkout_uses_paynkolay_by_link_create_payload_and_redirects_to_returned_url(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente', 'phone' => '5553332211']);

        config()->set('transfer.paynkolay.base_url', 'https://paynkolaytest.nkolayislem.com.tr');
        config()->set('transfer.paynkolay.by_link_create_path', '/Vpos/by-link-create');
        config()->set('transfer.paynkolay.sx', 'SX_TEST');
        config()->set('transfer.paynkolay.merchant_secret_key', 'SECRET_TEST');
        config()->set('transfer.paynkolay.environment', 'API');
        config()->set('transfer.paynkolay.currency_number', '949');
        config()->set('transfer.paynkolay.use_3d', true);
        config()->set('transfer.paynkolay.installment', 1);

        Http::fake(function ($request) {
            $this->assertSame('https://paynkolaytest.nkolayislem.com.tr/Vpos/by-link-create', (string) $request->url());

            $payload = $request->data();
            $this->assertSame('SX_TEST', (string) data_get($payload, 'sx'));
            $this->assertSame('API', (string) data_get($payload, 'environment'));
            $this->assertSame('949', (string) data_get($payload, 'currencyNumber'));
            $this->assertSame('SALES', (string) data_get($payload, 'transactionType'));
            $this->assertSame('true', (string) data_get($payload, 'use3D'));

            $service = app(PaynkolayService::class);
            $expectedHash = $service->requestHashForPayment(
                sx: (string) data_get($payload, 'sx'),
                clientRefCode: (string) data_get($payload, 'clientRefCode'),
                amount: (string) data_get($payload, 'amount'),
                successUrl: (string) data_get($payload, 'successUrl'),
                failUrl: (string) data_get($payload, 'failUrl'),
                rnd: (string) data_get($payload, 'rnd'),
                customerKey: '',
                merchantSecretKey: 'SECRET_TEST',
            );

            $this->assertSame($expectedHash, (string) data_get($payload, 'hashDatav2'));

            return Http::response([
                'url' => 'https://paynkolaytest.nkolayislem.com.tr/Vpos/by-link?q=abc123',
                'referenceCode' => 'PKREF123',
            ], 200);
        });

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $search = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '18:20',
            'pax' => 2,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($search->json(), 'data.options.0.quote_token');

        $response = $this->actingAs($acente)->post(route('acente.transfer.checkout.book', ['quoteToken' => $quoteToken]), [
            'contact_name' => 'Test User',
            'contact_phone' => '5553332211',
            'notes' => 'test',
        ]);

        $response->assertRedirect('https://paynkolaytest.nkolayislem.com.tr/Vpos/by-link?q=abc123');

        $payment = TransferPaymentTransaction::query()->latest('id')->firstOrFail();
        $this->assertSame('PKREF123', (string) $payment->provider_transaction_id);
    }

    public function test_checkout_uses_form_action_when_paynkolay_response_has_no_url(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente', 'phone' => '5553332211']);

        config()->set('transfer.paynkolay.base_url', 'https://paynkolaytest.nkolayislem.com.tr');
        config()->set('transfer.paynkolay.by_link_create_path', '/Vpos/by-link-create');
        config()->set('transfer.paynkolay.sx', 'SX_TEST');
        config()->set('transfer.paynkolay.merchant_secret_key', 'SECRET_TEST');

        Http::fake([
            'https://paynkolaytest.nkolayislem.com.tr/Vpos/by-link-create' => Http::response([
                'URL' => null,
                'FORM' => "<html><body><form id='PostForm' action='https://paynkolaytest.nkolayislem.com.tr/VPos/by-link-url?q=form123' method='POST'></form></body></html>",
                'RESPONSE_CODE' => 2,
                'RESPONSE_DATA' => 'Islem Basarili',
                'REFERENCE_CODE' => 'PKREFFORM',
            ], 200),
        ]);

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $search = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '18:20',
            'pax' => 2,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($search->json(), 'data.options.0.quote_token');

        $response = $this->actingAs($acente)->post(route('acente.transfer.checkout.book', ['quoteToken' => $quoteToken]), [
            'contact_name' => 'Test User',
            'contact_phone' => '5553332211',
            'notes' => 'test',
        ]);

        $response->assertRedirect('https://paynkolaytest.nkolayislem.com.tr/VPos/by-link-url?q=form123');
    }

    public function test_checkout_surfaces_paynkolay_error_message_when_gateway_rejects_request(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente', 'phone' => '5553332211']);

        config()->set('transfer.paynkolay.base_url', 'https://paynkolaytest.nkolayislem.com.tr');
        config()->set('transfer.paynkolay.by_link_create_path', '/Vpos/by-link-create');
        config()->set('transfer.paynkolay.sx', 'SX_TEST');
        config()->set('transfer.paynkolay.merchant_secret_key', 'SECRET_TEST');

        Http::fake([
            'https://paynkolaytest.nkolayislem.com.tr/Vpos/by-link-create' => Http::response([
                'URL' => null,
                'FORM' => null,
                'RESPONSE_CODE' => 0,
                'RESPONSE_DATA' => 'Girdiginiz bilgileri kontrol ediniz',
            ], 200),
        ]);

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $search = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '18:20',
            'pax' => 2,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($search->json(), 'data.options.0.quote_token');

        $checkoutUrl = route('acente.transfer.checkout.show', ['quoteToken' => $quoteToken]);
        $response = $this->from($checkoutUrl)
            ->actingAs($acente)
            ->post(route('acente.transfer.checkout.book', ['quoteToken' => $quoteToken]), [
                'contact_name' => 'Test User',
                'contact_phone' => '5553332211',
                'notes' => 'test',
            ]);

        $response->assertRedirect($checkoutUrl);
        $response->assertSessionHasErrors('payment');
        $this->assertStringContainsString(
            'Girdiginiz bilgileri kontrol ediniz',
            (string) session('errors')?->first('payment')
        );

        $booking = TransferBooking::query()->latest('id')->firstOrFail();
        $payment = TransferPaymentTransaction::query()->latest('id')->firstOrFail();
        $this->assertSame(TransferBooking::STATUS_FAILED, (string) $booking->status);
        $this->assertSame('failed', (string) $payment->status);
    }

    public function test_failed_payment_init_reopens_quote_token_for_retry(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente', 'phone' => '5553332211']);

        config()->set('transfer.paynkolay.base_url', 'https://paynkolaytest.nkolayislem.com.tr');
        config()->set('transfer.paynkolay.by_link_create_path', '/Vpos/by-link-create');
        config()->set('transfer.paynkolay.sx', 'SX_TEST');
        config()->set('transfer.paynkolay.merchant_secret_key', 'SECRET_TEST');

        Http::fake([
            'https://paynkolaytest.nkolayislem.com.tr/Vpos/by-link-create' => Http::response([
                'URL' => null,
                'FORM' => null,
                'RESPONSE_CODE' => 0,
                'RESPONSE_DATA' => 'Girdiginiz bilgileri kontrol ediniz',
            ], 200),
        ]);

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $search = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '18:20',
            'pax' => 2,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($search->json(), 'data.options.0.quote_token');
        $checkoutUrl = route('acente.transfer.checkout.show', ['quoteToken' => $quoteToken]);

        $this->from($checkoutUrl)
            ->actingAs($acente)
            ->post(route('acente.transfer.checkout.book', ['quoteToken' => $quoteToken]), [
                'contact_name' => 'Test User',
                'contact_phone' => '5553332211',
                'notes' => 'test',
            ])
            ->assertRedirect($checkoutUrl);

        $quote = TransferQuoteLock::query()->where('token', $quoteToken)->firstOrFail();
        $this->assertNull($quote->consumed_at);

        $this->actingAs($acente)
            ->get($checkoutUrl)
            ->assertOk();
    }

    public function test_acente_supplier_panel_requires_superadmin_permission(): void
    {
        $acente = User::factory()->create(['role' => 'acente']);

        $this->actingAs($acente)
            ->get(route('acente.transfer.supplier.index'))
            ->assertForbidden();

        $this->actingAs($acente)
            ->get(route('acente.transfer.supplier.terms.show'))
            ->assertForbidden();
    }

    public function test_approved_supplier_must_accept_terms_before_panel_access(): void
    {
        $supplierUser = User::factory()->create(['role' => 'acente']);
        $this->createSupplierFixture($supplierUser, acceptTerms: false);

        $this->actingAs($supplierUser)
            ->get(route('acente.transfer.supplier.index'))
            ->assertRedirect(route('acente.transfer.supplier.terms.show'));

        $this->actingAs($supplierUser)
            ->get(route('acente.transfer.supplier.terms.show'))
            ->assertOk()
            ->assertSee('Transfer tedarikci sozlesme onayi');

        $this->actingAs($supplierUser)
            ->post(route('acente.transfer.supplier.terms.accept'), ['accept_terms' => '1'])
            ->assertRedirect(route('acente.transfer.supplier.index'));

        $this->assertDatabaseHas('transfer_suppliers', [
            'user_id' => $supplierUser->id,
            'terms_version_accepted' => SistemAyar::transferSupplierTermsVersion(),
        ]);

        $this->actingAs($supplierUser)
            ->get(route('acente.transfer.supplier.index'))
            ->assertOk();
    }

    public function test_supplier_booking_visibility_is_limited_to_own_supplier_record(): void
    {
        $supplierUserA = User::factory()->create(['role' => 'acente']);
        $supplierUserB = User::factory()->create(['role' => 'acente']);
        $agencyUser = User::factory()->create(['role' => 'acente']);

        $supplierA = $this->createSupplierFixture($supplierUserA);
        $supplierB = $this->createSupplierFixture($supplierUserB);

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();
        $vehicleType = TransferVehicleType::query()->where('code', 'sedan')->firstOrFail();

        $bookingForB = TransferBooking::query()->create([
            'booking_ref' => 'TRFTESTB1',
            'supplier_id' => $supplierB->id,
            'agency_user_id' => $agencyUser->id,
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'vehicle_type_id' => $vehicleType->id,
            'direction' => 'FROM_AIRPORT',
            'pax' => 2,
            'pickup_at' => now()->addDays(1),
            'status' => TransferBooking::STATUS_CONFIRMED,
            'currency' => 'TRY',
            'subtotal_amount' => 100,
            'commission_amount' => 10,
            'total_amount' => 100,
        ]);

        $bookingForA = TransferBooking::query()->create([
            'booking_ref' => 'TRFTESTA1',
            'supplier_id' => $supplierA->id,
            'agency_user_id' => $agencyUser->id,
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'vehicle_type_id' => $vehicleType->id,
            'direction' => 'FROM_AIRPORT',
            'pax' => 2,
            'pickup_at' => now()->addDays(1),
            'status' => TransferBooking::STATUS_CONFIRMED,
            'currency' => 'TRY',
            'subtotal_amount' => 100,
            'commission_amount' => 10,
            'total_amount' => 100,
        ]);

        $this->actingAs($supplierUserA)
            ->get(route('acente.transfer.supplier.bookings.show', ['booking' => $bookingForB->id]))
            ->assertForbidden();

        $this->actingAs($supplierUserA)
            ->get(route('acente.transfer.supplier.bookings.show', ['booking' => $bookingForA->id]))
            ->assertOk();
    }

    public function test_superadmin_can_toggle_transfer_supplier_permission_for_acente(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $acenteUser = User::factory()->create(['role' => 'acente']);

        $agency = Agency::query()->create([
            'user_id' => $acenteUser->id,
            'company_title' => 'Test Agency',
            'contact_name' => 'Test User',
            'email' => $acenteUser->email,
            'phone' => $acenteUser->phone,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.acenteler.transfer-supplier-toggle', $agency))
            ->assertRedirect();

        $this->assertDatabaseHas('transfer_suppliers', [
            'user_id' => $acenteUser->id,
            'is_approved' => true,
        ]);

        $this->actingAs($superadmin)
            ->post(route('superadmin.acenteler.transfer-supplier-toggle', $agency))
            ->assertRedirect();

        $this->assertDatabaseHas('transfer_suppliers', [
            'user_id' => $acenteUser->id,
            'is_approved' => false,
        ]);
    }

    public function test_superadmin_can_open_supplier_panel_by_selecting_supplier(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $supplierUser = User::factory()->create(['role' => 'acente']);
        $supplier = $this->createSupplierFixture($supplierUser, acceptTerms: false);

        $this->actingAs($superadmin)
            ->get(route('acente.transfer.supplier.index', ['supplier_id' => $supplier->id]))
            ->assertOk()
            ->assertSee('Superadmin gorunumu')
            ->assertSee($supplier->company_name);

        // Supplier id session'da tutuldugu icin ikinci istek query olmadan da devam eder.
        $this->actingAs($superadmin)
            ->get(route('acente.transfer.supplier.index'))
            ->assertOk();
    }

    public function test_terms_version_bump_requires_reacceptance_and_filters_search_results(): void
    {
        $supplierUser = User::factory()->create(['role' => 'acente']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $searchUser = User::factory()->create(['role' => 'acente']);

        $this->createSupplierFixture($supplierUser, acceptTerms: true);

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $payload = [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'pax' => 2,
            'currency' => 'TRY',
        ];

        $firstSearch = $this->actingAs($searchUser)->postJson(route('acente.transfer.search'), $payload);
        $firstSearch->assertOk();
        $this->assertNotEmpty((array) data_get($firstSearch->json(), 'data.options', []));

        $this->actingAs($superadmin)
            ->patch(route('superadmin.transfer.ops.terms.update'), [
                'terms_text' => 'Yeni transfer tedarikci sozlesmesi metni ' . now()->timestamp,
            ])
            ->assertRedirect();

        $this->actingAs($supplierUser)
            ->get(route('acente.transfer.supplier.index'))
            ->assertRedirect(route('acente.transfer.supplier.terms.show'));

        $secondSearch = $this->actingAs($searchUser)->postJson(route('acente.transfer.search'), $payload);
        $secondSearch->assertOk();
        $this->assertSame([], (array) data_get($secondSearch->json(), 'data.options', []));

        $this->actingAs($supplierUser)
            ->post(route('acente.transfer.supplier.terms.accept'), ['accept_terms' => '1'])
            ->assertRedirect(route('acente.transfer.supplier.index'));

        $thirdSearch = $this->actingAs($searchUser)->postJson(route('acente.transfer.search'), $payload);
        $thirdSearch->assertOk();
        $this->assertNotEmpty((array) data_get($thirdSearch->json(), 'data.options', []));
    }

    public function test_supplier_can_update_existing_pricing_rule_without_creating_duplicates(): void
    {
        $supplierUser = User::factory()->create(['role' => 'acente']);
        $supplier = $this->createSupplierFixture($supplierUser, acceptTerms: true);

        $rule = TransferPricingRule::query()
            ->where('supplier_id', $supplier->id)
            ->firstOrFail();

        $this->actingAs($supplierUser)
            ->post(route('acente.transfer.supplier.pricing.store'), [
                'pricing_rule_id' => $rule->id,
                'airport_id' => $rule->airport_id,
                'zone_id' => $rule->zone_id,
                'vehicle_type_id' => $rule->vehicle_type_id,
                'direction' => 'FROM_AIRPORT',
                'currency' => 'TRY',
                'base_fare' => 1600,
                'per_km' => 0,
                'per_minute' => 0,
                'minimum_fare' => 1600,
                'night_multiplier' => 1,
                'peak_multiplier' => 1,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(
            1,
            TransferPricingRule::query()->where('supplier_id', $supplier->id)->count()
        );

        $this->assertDatabaseHas('transfer_pricing_rules', [
            'id' => $rule->id,
            'supplier_id' => $supplier->id,
            'direction' => 'FROM_AIRPORT',
            'base_fare' => 1600.0,
            'per_km' => 0.0,
            'per_minute' => 0.0,
            'minimum_fare' => 1600.0,
        ]);
    }

    public function test_booking_stores_operation_details_and_renders_them_on_detail_page(): void
    {
        $this->createSupplierFixture();
        $acente = User::factory()->create(['role' => 'acente', 'phone' => '5551112233']);

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();

        $search = $this->actingAs($acente)->postJson(route('acente.transfer.search'), [
            'direction' => 'FROM_AIRPORT',
            'airport_id' => $airport->id,
            'zone_id' => $zone->id,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '19:30',
            'pax' => 3,
            'currency' => 'TRY',
        ]);

        $quoteToken = (string) data_get($search->json(), 'data.options.0.quote_token');

        $this->actingAs($acente)
            ->post(route('acente.transfer.checkout.book', ['quoteToken' => $quoteToken]), [
                'contact_name' => 'Aydin Yaylaciklilar',
                'contact_phone' => '5551112233',
                'passenger_names' => "Aydin Yaylaciklilar\nAyse Yilmaz",
                'flight_number' => 'TK1983',
                'terminal' => 'Dis Hatlar',
                'pickup_sign_name' => 'AYDIN YAYLACIKLILAR',
                'exact_pickup_address' => 'Le Meridien Etiler, Nispetiye Cd. No:34 Besiktas / Istanbul',
                'luggage_count' => 3,
                'child_seat_count' => 1,
                'notes' => 'Sofor cikis kapisi 13 onunde beklesin.',
            ])
            ->assertRedirect();

        $booking = TransferBooking::query()->latest('id')->firstOrFail();

        $this->assertSame('Aydin Yaylaciklilar', (string) data_get($booking->price_snapshot_json, 'contact.name'));
        $this->assertSame('5551112233', (string) data_get($booking->price_snapshot_json, 'contact.phone'));
        $this->assertSame('TK1983', (string) data_get($booking->price_snapshot_json, 'operation_details.flight_number'));
        $this->assertSame('Dis Hatlar', (string) data_get($booking->price_snapshot_json, 'operation_details.terminal'));
        $this->assertSame('AYDIN YAYLACIKLILAR', (string) data_get($booking->price_snapshot_json, 'operation_details.pickup_sign_name'));
        $this->assertSame(3, (int) data_get($booking->price_snapshot_json, 'operation_details.luggage_count'));
        $this->assertSame(1, (int) data_get($booking->price_snapshot_json, 'operation_details.child_seat_count'));

        $this->actingAs($acente)
            ->get(route('acente.transfer.booking.show', ['booking' => $booking->id]))
            ->assertOk()
            ->assertSee('Yolcu / operasyon bilgileri')
            ->assertSee('TK1983')
            ->assertSee('AYDIN YAYLACIKLILAR')
            ->assertSee('Le Meridien Etiler');
    }

    private function createSupplierFixture(?User $supplierUser = null, bool $acceptTerms = true): TransferSupplier
    {
        $supplierUser ??= User::factory()->create(['role' => 'acente']);
        $termsVersion = SistemAyar::transferSupplierTermsVersion();

        $supplier = TransferSupplier::query()->updateOrCreate(
            ['user_id' => $supplierUser->id],
            [
                'company_name' => 'Istanbul Transfer Co',
                'contact_name' => $supplierUser->name,
                'email' => $supplierUser->email,
                'phone' => $supplierUser->phone ?: '5550000000',
                'city' => 'Istanbul',
                'commission_rate' => 12,
                'is_active' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'terms_accepted_at' => $acceptTerms ? now() : null,
                'terms_version_accepted' => $acceptTerms ? $termsVersion : null,
            ]
        );

        TransferCancellationPolicy::query()->updateOrCreate(
            ['supplier_id' => $supplier->id],
            [
                'free_cancel_before_minutes' => 180,
                'refund_percent_after_deadline' => 20,
                'no_show_refund_percent' => 0,
                'is_active' => true,
            ]
        );

        $airport = TransferAirport::query()->where('code', 'IST')->firstOrFail();
        $zone = TransferZone::query()->where('airport_id', $airport->id)->orderBy('id')->firstOrFail();
        $vehicleType = TransferVehicleType::query()->where('code', 'sedan')->firstOrFail();

        TransferSupplierCoverage::query()->updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'airport_id' => $airport->id,
                'zone_id' => $zone->id,
                'direction' => 'BOTH',
            ],
            [
                'is_active' => true,
            ]
        );

        TransferPricingRule::query()->updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'airport_id' => $airport->id,
                'zone_id' => $zone->id,
                'vehicle_type_id' => $vehicleType->id,
                'direction' => 'BOTH',
                'currency' => 'TRY',
            ],
            [
                'base_fare' => 300,
                'per_km' => 18,
                'per_minute' => 2,
                'minimum_fare' => 450,
                'night_multiplier' => 1.1,
                'peak_multiplier' => 1.2,
                'is_active' => true,
            ]
        );

        return $supplier->fresh();
    }
}

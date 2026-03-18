<?php

namespace Tests\Feature\Charter;

use App\Models\CharterExtra;
use App\Models\CharterJetRequest;
use App\Models\CharterQuote;
use App\Models\CharterRfqSupplier;
use App\Models\CharterRequest;
use App\Models\User;
use App\Services\Charter\RFQService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CharterFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_open_charter_pages_and_submit_lead(): void
    {
        $this->get(route('charter.public.jet'))->assertOk();
        $this->get(route('charter.public.helicopter'))->assertOk();
        $this->get(route('charter.public.airliner'))->assertOk();

        $response = $this->post(route('charter.public.store'), [
            'transport_type' => 'jet',
            'name' => 'Test Public',
            'phone' => '905551112233',
            'email' => 'public@example.com',
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'departure_date' => now()->addDays(10)->toDateString(),
            'pax' => 8,
            'notes' => 'Public lead',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('charter_requests', [
            'requester_type' => 'public',
            'transport_type' => 'jet',
            'status' => CharterRequest::STATUS_AI_QUOTED,
            'email' => 'public@example.com',
        ]);
    }

    public function test_acente_can_create_charter_request_and_see_detail(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'name' => 'Acente User',
            'email' => 'acente-charter@example.com',
            'phone' => '905550001122',
        ]);

        $this->actingAs($acente)->get(route('acente.charter.create'))->assertOk();

        $response = $this->actingAs($acente)->post(route('acente.charter.store'), [
            'transport_type' => 'helicopter',
            'from_iata' => 'SAW',
            'to_iata' => 'ECN',
            'departure_date' => now()->addDays(15)->toDateString(),
            'pax' => 5,
            'helicopter' => [
                'pickup' => 'Sabiha Gokcen',
                'dropoff' => 'Lefkosa Merkez',
                'landing_details' => 'Helipad uygun',
            ],
            'extras' => [
                ['title' => 'VIP transfer', 'agency_note' => 'Otelden havaalanina'],
            ],
        ]);

        $response->assertRedirect();

        $request = CharterRequest::query()->where('user_id', $acente->id)->latest()->first();
        $this->assertNotNull($request);

        $detailResponse = $this->actingAs($acente)->get(route('acente.charter.show', $request));
        $detailResponse->assertOk();
    }

    public function test_acente_can_open_charter_index_and_only_see_own_requests(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'agency-a@example.com',
            'phone' => '905551110001',
        ]);
        $otherAcente = User::factory()->create([
            'role' => 'acente',
            'email' => 'agency-b@example.com',
            'phone' => '905551110002',
        ]);

        $own = CharterRequest::query()->create([
            'user_id' => $acente->id,
            'requester_type' => 'agency',
            'transport_type' => CharterRequest::TYPE_JET,
            'status' => CharterRequest::STATUS_AI_QUOTED,
            'name' => $acente->name,
            'email' => $acente->email,
            'phone' => $acente->phone,
            'from_iata' => 'IST',
            'to_iata' => 'AYT',
            'departure_date' => now()->addDays(15)->toDateString(),
            'pax' => 6,
        ]);

        $other = CharterRequest::query()->create([
            'user_id' => $otherAcente->id,
            'requester_type' => 'agency',
            'transport_type' => CharterRequest::TYPE_HELICOPTER,
            'status' => CharterRequest::STATUS_LEAD,
            'name' => $otherAcente->name,
            'email' => $otherAcente->email,
            'phone' => $otherAcente->phone,
            'from_iata' => 'SAW',
            'to_iata' => 'ECN',
            'departure_date' => now()->addDays(20)->toDateString(),
            'pax' => 4,
        ]);

        $response = $this->actingAs($acente)->get(route('acente.charter.index'));

        $response->assertOk();
        $response->assertSee(route('acente.charter.show', $own), false);
        $response->assertDontSee(route('acente.charter.show', $other), false);
    }

    public function test_jet_round_trip_without_different_route_saves_auto_reverse_return_route(): void
    {
        $acente = User::factory()->create([
            'role' => 'acente',
            'email' => 'agency-roundtrip@example.com',
            'phone' => '905551110003',
        ]);

        $this->actingAs($acente)->post(route('acente.charter.store'), [
            'transport_type' => 'jet',
            'from_iata' => 'SAW',
            'to_iata' => 'AYT',
            'departure_date' => Carbon::now()->addDays(10)->toDateString(),
            'pax' => 5,
            'jet' => [
                'round_trip' => 1,
                'return_date' => Carbon::now()->addDays(12)->toDateString(),
                'different_return_route' => 0,
                'luggage_count' => 2,
            ],
        ])->assertRedirect();

        $request = CharterRequest::query()
            ->where('user_id', $acente->id)
            ->latest()
            ->firstOrFail();

        $this->assertSame('SAW', strtoupper((string) $request->from_iata));
        $this->assertSame('AYT', strtoupper((string) $request->to_iata));
        $this->assertTrue((bool) $request->jetDetail?->round_trip);
        $this->assertSame('AYT', strtoupper((string) data_get($request->jetDetail?->specs_json, 'return_from_iata')));
        $this->assertSame('SAW', strtoupper((string) data_get($request->jetDetail?->specs_json, 'return_to_iata')));
        $this->assertFalse((bool) data_get($request->jetDetail?->specs_json, 'different_return_route', false));
    }

    public function test_admin_can_open_charter_index_and_detail(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin-charter@example.com',
        ]);

        $charterRequest = CharterRequest::query()->create([
            'requester_type' => 'public',
            'transport_type' => 'airliner',
            'status' => CharterRequest::STATUS_LEAD,
            'name' => 'Lead User',
            'email' => 'lead@example.com',
            'phone' => '905500009999',
            'from_iata' => 'IST',
            'to_iata' => 'DXB',
            'departure_date' => now()->addDays(30)->toDateString(),
            'pax' => 120,
        ]);

        $this->actingAs($admin)->get(route('admin.charter.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.charter.show', $charterRequest))->assertOk();
    }

    public function test_rfq_dispatch_uses_current_request_data_and_logs_snapshot(): void
    {
        CharterRfqSupplier::query()->create([
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
            'phone' => '905551112233',
            'service_types' => [CharterRequest::TYPE_JET],
            'is_active' => true,
        ]);

        $charterRequest = CharterRequest::query()->create([
            'requester_type' => 'agency',
            'transport_type' => CharterRequest::TYPE_JET,
            'status' => CharterRequest::STATUS_AI_QUOTED,
            'name' => 'Test Agency',
            'email' => 'agency@example.com',
            'phone' => '905550001122',
            'from_iata' => 'SAW',
            'to_iata' => 'AYT',
            'departure_date' => Carbon::create(2026, 3, 29),
            'pax' => 5,
            'is_flexible' => true,
            'group_type' => 'Is adamlari',
            'notes' => 'Lounge hizmeti de istiyorlar',
            'ai_price_min' => 28520,
            'ai_price_max' => 36580,
            'ai_currency' => 'EUR',
        ]);

        CharterJetRequest::query()->create([
            'charter_request_id' => $charterRequest->id,
            'round_trip' => true,
            'pet_onboard' => true,
            'specs_json' => [
                'return_date' => '2026-03-31',
                'return_from_iata' => 'AYT',
                'return_to_iata' => 'SAW',
            ],
        ]);

        CharterExtra::query()->create([
            'charter_request_id' => $charterRequest->id,
            'title' => 'Yer Hizmeti',
            'agency_note' => 'Lounge kullanimi',
            'status' => 'pending_pricing',
        ]);

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $text, callable $callback) use ($charterRequest): bool {
                return str_contains($text, 'Talep No: #' . $charterRequest->id)
                    && str_contains($text, 'Ucus Tipi: Ozel Jet')
                    && str_contains($text, 'Rota: SAW -> AYT')
                    && str_contains($text, 'Pax: 5')
                    && str_contains($text, 'Talep Notu: Lounge hizmeti de istiyorlar')
                    && str_contains($text, 'Ek Hizmet: Yer Hizmeti (Lounge kullanimi)')
                    && str_contains($text, 'Merhaba, sayin Test Supplier');
            });

        $result = app(RFQService::class)->dispatch($charterRequest->fresh());

        $this->assertSame(1, $result['sent']);
        $this->assertSame(0, $result['failed']);
        $this->assertDatabaseHas('charter_quotes', [
            'charter_request_id' => $charterRequest->id,
            'quote_type' => 'rfq',
            'status' => 'sent',
        ]);

        $quote = CharterQuote::query()
            ->where('charter_request_id', $charterRequest->id)
            ->where('quote_type', 'rfq')
            ->latest('id')
            ->first();

        $this->assertNotNull($quote);
        $this->assertSame($charterRequest->id, (int) data_get($quote->payload, 'request_id'));
        $this->assertSame('SAW - AYT', (string) data_get($quote->payload, 'route'));
        $this->assertSame('SAW', (string) data_get($quote->payload, 'request_snapshot.from_iata'));
        $this->assertSame('AYT', (string) data_get($quote->payload, 'request_snapshot.to_iata'));
    }

    public function test_send_rfq_stops_when_request_confirmation_id_mismatches(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-rfq-confirm@example.com',
        ]);

        $charterRequest = CharterRequest::query()->create([
            'requester_type' => 'agency',
            'transport_type' => CharterRequest::TYPE_JET,
            'status' => CharterRequest::STATUS_AI_QUOTED,
            'name' => 'Agency',
            'email' => 'agency-confirm@example.com',
            'phone' => '905550009999',
            'from_iata' => 'IST',
            'to_iata' => 'ADB',
            'departure_date' => now()->addDays(8)->toDateString(),
            'pax' => 4,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.charter.send-rfq', $charterRequest), [
            'request_id_confirm' => $charterRequest->id + 999,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('charter_quotes', [
            'charter_request_id' => $charterRequest->id,
            'quote_type' => 'rfq',
        ]);
        $this->assertDatabaseHas('charter_requests', [
            'id' => $charterRequest->id,
            'status' => CharterRequest::STATUS_AI_QUOTED,
        ]);
    }
}

<?php

namespace Tests\Feature\Charter;

use App\Models\CharterRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

<?php

namespace Tests\Feature\QuickReply;

use App\Models\Agency;
use App\Models\FlightSegment;
use App\Models\QuickReplySession;
use App\Models\Request as RequestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickReplyFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_acente_cannot_open_quick_reply_page(): void
    {
        $acente = User::factory()->create(['role' => 'acente']);

        $response = $this->actingAs($acente)->get(route('admin.quick-reply.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_parse_raw_text_and_create_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $agencyUser = User::factory()->create(['role' => 'acente', 'phone' => '905320000001']);
        $agency = Agency::create([
            'user_id' => $agencyUser->id,
            'company_title' => 'ECCO TURIZM',
            'contact_name' => 'Ecco Yetkili',
            'phone' => '905320000001',
            'email' => 'ecco@example.com',
            'is_active' => true,
        ]);

        $request = RequestModel::create([
            'gtpnr' => 'UG-ABC123',
            'user_id' => $agencyUser->id,
            'type' => 'group_flight',
            'status' => RequestModel::STATUS_BEKLEMEDE,
            'agency_name' => 'ECCO TURIZM',
            'phone' => '905320000001',
            'email' => 'ecco@example.com',
            'trip_type' => 'round_trip',
            'pax_total' => 27,
            'pax_adult' => 27,
            'pax_child' => 0,
            'pax_infant' => 0,
        ]);

        FlightSegment::create([
            'request_id' => $request->id,
            'order' => 0,
            'from_iata' => 'SAW',
            'from_city' => 'Istanbul',
            'to_iata' => 'ECN',
            'to_city' => 'Lefkosa',
            'departure_date' => '2026-05-14',
            'departure_time' => '07:15',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.quick-reply.parse'), [
            'manual_agency_id' => $agency->id,
            'membership_mode' => 'auto',
            'raw_text' => "ECCO\n27 pax AJET SAW ECN SAW\nkisi basi:9452 TL\nopsiyon: 19 mart saat 12:00\nVF153 14/05/2026 SAW - ECN 07:15-08:45",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('quick_reply_sessions', [
            'user_id' => $admin->id,
            'manual_agency_id' => $agency->id,
            'status' => QuickReplySession::STATUS_NEEDS_REVIEW,
        ]);
    }
}

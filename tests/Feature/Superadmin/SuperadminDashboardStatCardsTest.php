<?php

namespace Tests\Feature\Superadmin;

use App\Models\Offer;
use App\Models\Request as TalepModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminDashboardStatCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stat_cards_render_expected_links(): void
    {
        if (config('database.connections.' . config('database.default') . '.driver') === 'sqlite') {
            $this->markTestSkipped('Superadmin dashboard view uses MySQL date functions for option alerts.');
        }

        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-dashboard-stats@example.com',
        ]);

        $today = today()->toDateString();

        $response = $this->actingAs($superadmin)->get(route('superadmin.dashboard'));

        $response->assertOk();
        $response->assertSee('href="#kullanici-yonetimi"', false);
        $response->assertSee(route('superadmin.acenteler'), false);
        $response->assertSee(route('admin.requests.index', ['durum' => 'tumu']), false);
        $response->assertSee(route('admin.requests.index', ['durum' => 'beklemede']), false);
        $response->assertSee(route('admin.requests.index', ['durum' => 'biletlendi']), false);
        $response->assertSee(route('admin.requests.index', ['durum' => 'tumu', 'teklif' => 1]), false);
        $response->assertSee(route('admin.requests.index', ['durum' => 'tumu', 'opsiyon' => 1]), false);
        $response->assertSee(route('admin.requests.index', ['durum' => 'tumu', 'tarih_baslangic' => $today, 'tarih_bitis' => $today]), false);
        $response->assertSee('Opsiyonlar');
    }

    public function test_requests_index_teklif_filter_shows_only_requests_with_offers(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-teklif-filter@example.com',
        ]);
        $agencyUser = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-teklif-filter@example.com',
        ]);

        $withOffer = $this->createRequest($agencyUser, 'TEKLIF001');
        $withoutOffer = $this->createRequest($agencyUser, 'TEKLIF002');

        Offer::query()->create([
            'request_id' => $withOffer->id,
            'airline' => 'TK',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.requests.index', [
            'durum' => 'tumu',
            'teklif' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('TEKLIF001');
        $response->assertDontSee('TEKLIF002');
    }

    public function test_requests_index_opsiyon_filter_shows_only_requests_with_option_date(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'email' => 'superadmin-opsiyon-filter@example.com',
        ]);
        $agencyUser = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-opsiyon-filter@example.com',
        ]);

        $withOption = $this->createRequest($agencyUser, 'OPSYN001');
        $withoutOption = $this->createRequest($agencyUser, 'OPSYN002');
        $expiredOption = $this->createRequest($agencyUser, 'OPSYN003');

        Offer::query()->create([
            'request_id' => $withOption->id,
            'airline' => 'TK',
            'option_date' => now()->addDay()->toDateString(),
        ]);

        Offer::query()->create([
            'request_id' => $withoutOption->id,
            'airline' => 'TK',
        ]);

        Offer::query()->create([
            'request_id' => $expiredOption->id,
            'airline' => 'TK',
            'option_date' => now()->subDay()->toDateString(),
            'option_time' => '10:00:00',
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.requests.index', [
            'durum' => 'tumu',
            'opsiyon' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('OPSYN001');
        $response->assertDontSee('OPSYN002');
        $response->assertDontSee('OPSYN003');
    }

    private function createRequest(User $agencyUser, string $gtpnr): TalepModel
    {
        return TalepModel::query()->create([
            'gtpnr' => $gtpnr,
            'user_id' => $agencyUser->id,
            'status' => TalepModel::STATUS_BEKLEMEDE,
            'agency_name' => 'Test Agency',
            'phone' => '5550000000',
            'email' => 'agency@example.com',
            'pax_total' => 10,
            'pax_adult' => 10,
            'pax_child' => 0,
            'pax_infant' => 0,
        ]);
    }
}

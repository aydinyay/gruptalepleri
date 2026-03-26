<?php

namespace Tests\Feature\Leisure;

use App\Models\DinnerCruiseRequestDetail;
use App\Models\LeisureExtraOption;
use App\Models\LeisureMediaAsset;
use App\Models\LeisurePackageTemplate;
use App\Models\LeisureRequest;
use App\Models\User;
use App\Models\YachtCharterRequestDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcenteLeisureIndexRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_dinner_index_renders_new_showcase_sections_and_cta(): void
    {
        $user = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-leisure-redesign-1@example.com',
        ]);

        $this->truncateLeisureCatalog();

        LeisurePackageTemplate::query()->create([
            'product_type' => 'dinner_cruise',
            'code' => 'vip',
            'level' => 'vip',
            'name_tr' => 'VIP',
            'name_en' => 'VIP',
            'summary_tr' => 'VIP paket ozeti.',
            'summary_en' => 'VIP package summary.',
            'includes_tr' => ['Shuttle transfer', 'VIP masa'],
            'includes_en' => ['Shuttle transfer', 'VIP table'],
            'excludes_tr' => ['Private yacht'],
            'excludes_en' => ['Private yacht'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        LeisureExtraOption::query()->create([
            'product_type' => 'dinner_cruise',
            'category' => 'transfer',
            'code' => 'shuttle_transfer',
            'title_tr' => 'Shuttle Transfer',
            'title_en' => 'Shuttle Transfer',
            'default_included' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        LeisureMediaAsset::query()->create([
            'product_type' => 'dinner_cruise',
            'category' => 'show',
            'media_type' => 'photo',
            'source_type' => 'upload',
            'title_tr' => 'Semazen gosterisi',
            'title_en' => 'Whirling dervish performance',
            'file_path' => '/uploads/leisure-media/seed-bosphorus/bosphorus-show-dervish.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $request = LeisureRequest::query()->create([
            'user_id' => $user->id,
            'gtpnr' => 'DCTEST1',
            'product_type' => 'dinner_cruise',
            'status' => LeisureRequest::STATUS_NEW,
            'service_date' => now()->addDays(7)->toDateString(),
            'guest_count' => 12,
            'transfer_required' => false,
            'package_level' => 'vip',
            'language_preference' => 'tr',
        ]);

        DinnerCruiseRequestDetail::query()->create([
            'leisure_request_id' => $request->id,
            'session_time' => '20:30',
            'pier_name' => 'Kabatas',
            'shared_cruise' => true,
        ]);

        $response = $this->actingAs($user)->get(route('acente.dinner-cruise.index'));

        $response->assertOk();
        $response->assertSee('Hizli teklif karti');
        $response->assertSee('Paket Varyantlari');
        $response->assertSee('Son talepleriniz');
        $response->assertSee('Semazen gosterisi');
        $response->assertSee(route('acente.dinner-cruise.create', ['package_level' => 'vip']));
    }

    public function test_yacht_index_shows_empty_states_when_real_data_is_missing(): void
    {
        $user = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-leisure-redesign-2@example.com',
        ]);

        $this->truncateLeisureCatalog();
        LeisureRequest::query()->delete();

        $response = $this->actingAs($user)->get(route('acente.yacht-charter.index'));

        $response->assertOk();
        $response->assertSee('Henuz aktif paket tanimi bulunmuyor.');
        $response->assertSee('Henuz aktif medya kaydi yok.');
        $response->assertSee('Bu kategoride daha once talep acmadiniz.');
    }

    public function test_selected_package_query_marks_selection_and_updates_primary_cta(): void
    {
        $user = User::factory()->create([
            'role' => 'acente',
            'email' => 'acente-leisure-redesign-3@example.com',
        ]);

        $this->truncateLeisureCatalog();

        LeisurePackageTemplate::query()->create([
            'product_type' => 'dinner_cruise',
            'code' => 'standard',
            'level' => 'standard',
            'name_tr' => 'Standart',
            'name_en' => 'Standard',
            'summary_tr' => 'Standart paket ozeti.',
            'summary_en' => 'Standard package summary.',
            'includes_tr' => ['Standart menu'],
            'includes_en' => ['Standard menu'],
            'excludes_tr' => [],
            'excludes_en' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        LeisurePackageTemplate::query()->create([
            'product_type' => 'dinner_cruise',
            'code' => 'premium',
            'level' => 'premium',
            'name_tr' => 'Premium',
            'name_en' => 'Premium',
            'summary_tr' => 'Premium paket ozeti.',
            'summary_en' => 'Premium package summary.',
            'includes_tr' => ['Premium menu'],
            'includes_en' => ['Premium menu'],
            'excludes_tr' => [],
            'excludes_en' => [],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('acente.dinner-cruise.index', ['package_level' => 'premium']));

        $response->assertOk();
        $response->assertSee('<option value="premium" data-summary="Premium paket ozeti." selected>', false);
        $response->assertSee(route('acente.dinner-cruise.create', ['package_level' => 'premium']));
        $response->assertSee('Premium paket ozeti.');
    }

    private function truncateLeisureCatalog(): void
    {
        LeisurePackageTemplate::query()->delete();
        LeisureExtraOption::query()->delete();
        LeisureMediaAsset::query()->delete();
        YachtCharterRequestDetail::query()->delete();
        DinnerCruiseRequestDetail::query()->delete();
    }
}

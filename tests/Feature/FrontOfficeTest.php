<?php

namespace Tests\Feature;

use App\Models\EmergencyContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Neighborhood;
use App\Models\PowerOutage;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class FrontOfficeTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    public function test_home_page_shows_the_platform_statistics(): void
    {
        $n = Neighborhood::factory()->create();
        $this->resident($n);
        $this->resident($n);
        $this->admin();
        PowerOutage::create(['neighborhood_id' => $n->id, 'status' => 'reported', 'source' => 'user', 'started_at' => now()]);
        PowerOutage::create(['neighborhood_id' => $n->id, 'status' => 'resolved', 'source' => 'user', 'started_at' => now(), 'resolved_at' => now()]);

        $this->get(route('front.home'))
            ->assertOk()
            ->assertViewHas('stats', [
                'neighborhoods' => 1, 'outages_tracked' => 2, 'outages_resolved' => 1, 'residents' => 2,
            ]);
    }

    public function test_home_page_shows_at_most_four_active_priority_contacts(): void
    {
        $this->resident(); // le rôle « resident » doit exister pour les statistiques
        EmergencyContact::factory()->count(5)->create(['is_priority' => true]);
        EmergencyContact::factory()->create(['is_priority' => true, 'is_active' => false]);

        $this->get(route('front.home'))
            ->assertOk()
            ->assertViewIs('front.home')
            ->assertViewHas('priorityContacts', fn ($list) => $list->count() === 4 && $list->every->is_active);
    }

    public function test_map_page_is_public(): void
    {
        $this->get(route('front.map'))->assertOk()->assertViewIs('front.map');
    }

    public function test_map_data_endpoint_is_public(): void
    {
        $this->getJson(route('front.map.data'))->assertOk();
    }
}

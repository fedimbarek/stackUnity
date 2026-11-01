<?php

namespace Tests\Feature\Ai;

use App\Models\Neighborhood;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class NeighborhoodRiskTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    public function test_guest_and_resident_cannot_see_the_risk_ranking(): void
    {
        $this->get(route('ai.risk'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('ai.risk'))->assertForbidden();
    }

    public function test_html_page_receives_the_ranking(): void
    {
        Neighborhood::factory()->count(2)->create();

        $this->actingAs($this->gestionnaire())->get(route('ai.risk'))
            ->assertOk()
            ->assertViewIs('ai.risk')
            ->assertViewHas('ranking', fn ($ranking) => count($ranking) === 2);
    }

    public function test_json_response_lists_name_score_level_and_factors(): void
    {
        Neighborhood::factory()->create(['name' => 'Radès']);

        $this->actingAs($this->admin())->getJson(route('ai.risk'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.neighborhood', 'Radès')
            ->assertJsonStructure([['neighborhood', 'score', 'level', 'factors']]);
    }
}

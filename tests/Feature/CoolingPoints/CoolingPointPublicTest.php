<?php

namespace Tests\Feature\CoolingPoints;

use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use App\Services\CoolingPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class CoolingPointPublicTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Cache::flush();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cooling-points.index'))->assertRedirect(route('login'));
        $this->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18])->assertUnauthorized();
    }

    public function test_index_page_is_displayed_for_a_resident(): void
    {
        CoolingPoint::factory()->count(2)->create();

        $this->actingAs($this->resident())
            ->get(route('cooling-points.index'))
            ->assertOk()
            ->assertViewIs('cooling-points.index')
            ->assertViewHas('points', fn ($points) => $points->count() === 2);
    }

    public function test_fetch_validates_the_input(): void
    {
        $this->actingAs($this->resident());

        $this->postJson(route('cooling-points.fetch'), [])->assertJsonValidationErrors(['lat', 'lng']);
        $this->postJson(route('cooling-points.fetch'), ['lat' => 'x', 'lng' => 10])->assertJsonValidationErrors('lat');
        $this->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18, 'radius' => 50])->assertJsonValidationErrors('radius');
        $this->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18, 'radius' => 20000])->assertJsonValidationErrors('radius');
    }

    public function test_fetch_returns_only_the_points_around_the_position_with_their_type(): void
    {
        Http::fake(['*' => Http::response(['elements' => []], 200)]);
        $type = CoolingPointType::factory()->create(['name' => 'Parc', 'slug' => 'parc', 'icon' => 'fa-tree', 'color' => '#1cc88a']);
        CoolingPoint::factory()->create(['name' => 'Proche', 'latitude' => 36.801, 'longitude' => 10.181, 'cooling_point_type_id' => $type->id]);
        CoolingPoint::factory()->create(['name' => 'Loin', 'latitude' => 35.0, 'longitude' => 9.0, 'cooling_point_type_id' => $type->id]);

        $response = $this->actingAs($this->resident())
            ->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18, 'radius' => 2000])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('points.0.name', 'Proche')
            ->assertJsonPath('points.0.type', 'parc')
            ->assertJsonPath('points.0.type_icon', 'fa-tree')
            ->assertJsonPath('points.0.type_color', '#1cc88a')
            ->assertJsonPath('types.0.slug', 'parc');

        $this->assertIsFloat($response->json('points.0.latitude'));
    }

    public function test_point_without_type_gets_the_default_values(): void
    {
        Http::fake(['*' => Http::response(['elements' => []], 200)]);
        CoolingPoint::factory()->create([
            'latitude' => 36.8, 'longitude' => 10.18, 'cooling_point_type_id' => null, 'type' => 'mall',
        ]);

        $this->actingAs($this->resident())
            ->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18])
            ->assertJsonPath('points.0.type', 'mall')
            ->assertJsonPath('points.0.type_name', 'Autre')
            ->assertJsonPath('points.0.type_icon', 'fa-map-marker-alt')
            ->assertJsonPath('points.0.type_color', '#858796');
    }

    public function test_osm_is_only_refreshed_once_per_zone(): void
    {
        Http::fake(['*' => Http::response(['elements' => []], 200)]);
        $this->actingAs($this->resident());

        $this->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18])->assertOk();
        $this->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18])->assertOk();
        Http::assertSentCount(1);

        // autre zone => nouvel appel
        $this->postJson(route('cooling-points.fetch'), ['lat' => 35.5, 'lng' => 9.5])->assertOk();
        Http::assertSentCount(2);
    }

    public function test_an_osm_failure_does_not_break_the_response(): void
    {
        $this->mock(CoolingPointService::class, function ($mock) {
            $mock->shouldReceive('fetchNearby')->once()->andThrow(new \RuntimeException('osm down'));
        });
        CoolingPoint::factory()->create(['latitude' => 36.8, 'longitude' => 10.18]);

        $this->actingAs($this->resident())
            ->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 1);

        // le cache court évite de re-spammer OSM
        $this->postJson(route('cooling-points.fetch'), ['lat' => 36.8, 'lng' => 10.18])->assertOk();
    }
}

<?php

namespace Tests\Feature\CoolingPoints;

use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use App\Services\CoolingPointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class CoolingPointServiceTest extends TestCase
{
    use RefreshDatabase;

    private const MAIN = 'overpass-api.de/*';
    private const MIRROR = 'overpass.kumi.systems/*';

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake(); // le client HTTP fait un retry avec pause : on ne veut pas attendre
    }

    private function overpass(array $elements)
    {
        return Http::response(['elements' => $elements], 200);
    }

    private function seedTypes(): void
    {
        foreach (['parc', 'fontaine', 'climatise'] as $slug) {
            CoolingPointType::factory()->create(['name' => ucfirst($slug), 'slug' => $slug]);
        }
    }

    public function test_osm_elements_are_normalized_and_saved(): void
    {
        $this->seedTypes();
        Http::fake([self::MAIN => $this->overpass([
            ['type' => 'node', 'id' => 1, 'lat' => 36.8, 'lon' => 10.18,
                'tags' => ['amenity' => 'fountain', 'name' => 'Fontaine Lac', 'addr:street' => 'Rue du Lac', 'opening_hours' => '24/7']],
            ['type' => 'way', 'id' => 2, 'center' => ['lat' => 36.81, 'lon' => 10.19],
                'tags' => ['leisure' => 'park']],
            ['type' => 'node', 'id' => 3, 'lat' => 36.82, 'lon' => 10.2,
                'tags' => ['shop' => 'mall', 'name' => 'Tunisia Mall']],
        ])]);

        $points = app(CoolingPointService::class)->fetchNearby(36.8, 10.18, 1500);

        $this->assertCount(3, $points);
        $this->assertSame(3, CoolingPoint::count());

        $fountain = CoolingPoint::where('osm_id', '1_node')->first();
        $this->assertSame('Fontaine Lac', $fountain->name);
        $this->assertSame('fountain', $fountain->type);
        $this->assertSame('osm', $fountain->source);
        $this->assertSame('Rue du Lac', $fountain->address);
        $this->assertSame('24/7', $fountain->opening_hours);
        $this->assertSame('fontaine', $fountain->coolingPointType->slug);

        // way : coordonnées prises dans "center", nom par défaut, type parc
        $park = CoolingPoint::where('osm_id', '2_way')->first();
        $this->assertSame('Point de fraîcheur', $park->name);
        $this->assertSame(36.81, $park->latitude);
        $this->assertSame('parc', $park->coolingPointType->slug);

        $this->assertSame('climatise', CoolingPoint::where('osm_id', '3_node')->first()->coolingPointType->slug);
    }

    public function test_query_uses_the_given_position_and_radius(): void
    {
        Http::fake([self::MAIN => $this->overpass([])]);

        app(CoolingPointService::class)->fetchNearby(36.8, 10.18, 1234);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_contains($request['data'], 'around:1234,36.8,10.18')
                && $request->hasHeader('User-Agent');
        });
    }

    public function test_elements_without_coordinates_are_skipped(): void
    {
        Http::fake([self::MAIN => $this->overpass([
            ['type' => 'node', 'id' => 10, 'tags' => ['amenity' => 'fountain']],
            ['type' => 'node', 'id' => 11, 'lat' => 36.8, 'lon' => 10.18, 'tags' => ['amenity' => 'drinking_water']],
        ])]);

        $points = app(CoolingPointService::class)->fetchNearby(36.8, 10.18);

        $this->assertCount(1, $points);
        $this->assertSame('fountain', $points[0]['type']); // drinking_water => fontaine
        $this->assertSame(1, CoolingPoint::count());
    }

    public function test_calling_twice_does_not_duplicate_points(): void
    {
        Http::fake([self::MAIN => Http::sequence()
            ->push(['elements' => [['type' => 'node', 'id' => 1, 'lat' => 36.8, 'lon' => 10.18, 'tags' => ['name' => 'Avant']]]])
            ->push(['elements' => [['type' => 'node', 'id' => 1, 'lat' => 36.8, 'lon' => 10.18, 'tags' => ['name' => 'Après']]]]),
        ]);
        $service = app(CoolingPointService::class);
        $service->fetchNearby(36.8, 10.18);
        $service->fetchNearby(36.8, 10.18);

        $this->assertSame(1, CoolingPoint::count());
        $this->assertSame('Après', CoolingPoint::first()->name);
    }

    public function test_type_is_null_when_the_matching_type_does_not_exist(): void
    {
        Http::fake([self::MAIN => $this->overpass([
            ['type' => 'node', 'id' => 1, 'lat' => 36.8, 'lon' => 10.18, 'tags' => ['amenity' => 'fountain']],
        ])]);

        app(CoolingPointService::class)->fetchNearby(36.8, 10.18);

        $this->assertNull(CoolingPoint::first()->cooling_point_type_id);
    }

    public function test_falls_back_to_the_next_mirror_when_the_first_fails(): void
    {
        Http::fake([
            self::MAIN => Http::response('overloaded', 500),
            self::MIRROR => $this->overpass([
                ['type' => 'node', 'id' => 5, 'lat' => 36.8, 'lon' => 10.18, 'tags' => ['name' => 'Via miroir']],
            ]),
        ]);

        $points = app(CoolingPointService::class)->fetchNearby(36.8, 10.18);

        $this->assertCount(1, $points);
        $this->assertSame('Via miroir', CoolingPoint::first()->name);
    }

    public function test_falls_back_to_the_next_mirror_when_the_first_throws(): void
    {
        Http::fake([
            self::MAIN => fn () => throw new ConnectionException('timeout'),
            self::MIRROR => $this->overpass([
                ['type' => 'node', 'id' => 6, 'lat' => 36.8, 'lon' => 10.18, 'tags' => []],
            ]),
        ]);

        $this->assertCount(1, app(CoolingPointService::class)->fetchNearby(36.8, 10.18));
    }

    public function test_returns_an_empty_array_when_every_mirror_fails(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);

        $this->assertSame([], app(CoolingPointService::class)->fetchNearby(36.8, 10.18));
        $this->assertSame(0, CoolingPoint::count());
    }
}

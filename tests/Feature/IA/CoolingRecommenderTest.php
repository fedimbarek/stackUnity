<?php

namespace Tests\Feature\IA;

use App\IA\CoolingIA\CoolingRecommender;
use App\IA\CoolingIA\ModelStorage;
use App\IA\CoolingIA\Trainer;
use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoolingRecommenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_produces_a_usable_model(): void
    {
        $result = (new Trainer())->train(400, 100);

        $this->assertNotNull($result['model']);
        $this->assertGreaterThanOrEqual(0.6, $result['test']['accuracy']);
        $this->assertLessThan(0.1, $result['test']['mse']);
    }

    public function test_model_can_be_saved_and_loaded(): void
    {
        $result = (new Trainer())->train(200, 50);
        ModelStorage::save($result['model']);

        $loaded = ModelStorage::load();
        $this->assertNotNull($loaded);
        $this->assertEqualsWithDelta(
            $result['model']->bias, $loaded->bias, 1e-6
        );
    }

    public function test_recommender_ranks_close_open_points_higher(): void
    {
        $type = CoolingPointType::factory()->create([
            'slug' => 'fontaine', 'name' => 'Fontaine',
        ]);

        $good = CoolingPoint::factory()->create([
            'cooling_point_type_id' => $type->id,
            'latitude' => 36.81, 'longitude' => 10.18,
            'address' => 'Centre', 'opening_hours' => '24/7',
            'source' => 'manual',
        ]);
        $bad = CoolingPoint::factory()->create([
            'cooling_point_type_id' => $type->id,
            'latitude' => 40.0, 'longitude' => 10.0,
            'address' => null, 'opening_hours' => null,
            'source' => 'osm',
        ]);

        $result = (new Trainer())->train(200, 50);
        ModelStorage::save($result['model']);

        $rec = new CoolingRecommender();
        $this->assertTrue($rec->isReady());

        $ranked = $rec->rank(collect([$bad, $good]), [
            'lat' => 36.8065, 'lng' => 10.1815,
            'radius' => 2000, 'temperature' => 35, 'now' => now(),
        ]);

        $this->assertEquals($good->id, $ranked->first()->id);
        $this->assertGreaterThan($ranked->last()->ai_score, $ranked->first()->ai_score);
    }
}
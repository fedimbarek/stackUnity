<?php

namespace Tests\Feature\Weather;

use App\Events\HeatwaveDetected;
use App\Models\AlertThreshold;
use App\Models\Neighborhood;
use App\Models\OutageRisk;
use App\Models\WeatherForecast;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    use RefreshDatabase;

    private Neighborhood $quartier;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.weather.heat_warning', 35);
        config()->set('services.weather.heat_threshold', 38);

        $this->quartier = Neighborhood::create([
            'name' => 'El Menzah',
            'city' => 'Tunis',
            'latitude' => 36.83,
            'longitude' => 10.16,
        ]);

        Http::preventStrayRequests();
        Event::fake([HeatwaveDetected::class]);
    }

    private function fakeMeteo(array $temperatures): void
    {
        $dates = [];
        $minimums = [];

        foreach ($temperatures as $i => $temp) {
            $dates[] = today()->addDays($i)->toDateString();
            $minimums[] = $temp - 10;
        }

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'daily' => [
                    'time' => $dates,
                    'temperature_2m_max' => $temperatures,
                    'temperature_2m_min' => $minimums,
                ],
            ], 200),
        ]);
    }

    public function test_import_previsions(): void
    {
        $this->fakeMeteo([25, 26, 27]);

        $count = app(WeatherService::class)
            ->refreshNeighborhood($this->quartier);

        $this->assertEquals(3, $count);
        $this->assertEquals(
            3,
            WeatherForecast::count()
        );

        Http::assertSentCount(1);
    }

    public function test_temperature_normale(): void
    {
        $this->fakeMeteo([25]);

        app(WeatherService::class)
            ->refreshNeighborhood($this->quartier);

        $this->assertDatabaseHas('weather_forecasts', [
            'level' => 'normal',
        ]);

        $this->assertEquals(0, OutageRisk::count());

        Event::assertNotDispatched(
            HeatwaveDetected::class
        );
    }

    public function test_forte_chaleur(): void
    {
        $this->fakeMeteo([36]);

        app(WeatherService::class)
            ->refreshNeighborhood($this->quartier);

        $this->assertDatabaseHas('weather_forecasts', [
            'level' => 'warning',
        ]);

        $this->assertDatabaseHas('outage_risks', [
            'risk_level' => 'moyen',
        ]);

        Event::assertDispatched(
            HeatwaveDetected::class
        );
    }

    public function test_canicule(): void
    {
        $this->fakeMeteo([40]);

        app(WeatherService::class)
            ->refreshNeighborhood($this->quartier);

        $this->assertDatabaseHas('weather_forecasts', [
            'level' => 'canicule',
        ]);

        $this->assertDatabaseHas('outage_risks', [
            'risk_level' => 'eleve',
        ]);

        Event::assertDispatched(
            HeatwaveDetected::class
        );
    }

    public function test_lecture_seuils_configures(): void
    {
        AlertThreshold::create([
            'level' => 'warning',
            'temp_max' => 33,
        ]);

        AlertThreshold::create([
            'level' => 'canicule',
            'temp_max' => 37,
        ]);

        $result = app(WeatherService::class)
            ->thresholds();

        $this->assertEquals(33, $result['warning']);
        $this->assertEquals(37, $result['canicule']);
    }

    public function test_seuil_personnalise(): void
    {
        AlertThreshold::create([
            'level' => 'warning',
            'temp_max' => 30,
        ]);

        $this->fakeMeteo([32]);

        app(WeatherService::class)
            ->refreshNeighborhood($this->quartier);

        $this->assertDatabaseHas('weather_forecasts', [
            'level' => 'warning',
        ]);
    }

    public function test_pas_de_doublons_previsions(): void
    {
        $this->fakeMeteo([36]);

        $service = app(WeatherService::class);

        $service->refreshNeighborhood($this->quartier);
        $service->refreshNeighborhood($this->quartier);

        $this->assertEquals(1, WeatherForecast::count());
        $this->assertEquals(1, OutageRisk::count());

        Event::assertDispatchedTimes(
            HeatwaveDetected::class,
            1
        );
    }
}

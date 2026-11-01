<?php

namespace Tests\Unit\Services;

use App\Events\HeatwaveDetected;
use App\Models\AlertThreshold;
use App\Models\Neighborhood;
use App\Models\OutageRisk;
use App\Models\WeatherForecast;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceRefreshTest extends TestCase
{
    use RefreshDatabase;

    /** @var float[] Températures max renvoyées par l'API simulée (un jour par entrée). */
    private array $dailyMax = [25];

    /** Code HTTP renvoyé par l'API simulée. */
    private int $apiStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-10 09:00:00');
        config(['services.weather.heat_warning' => 35, 'services.weather.heat_threshold' => 38]);
        Event::fake([HeatwaveDetected::class]);
        Http::preventStrayRequests();

        // Une seule déclaration : un 2e Http::fake() ne remplacerait pas la première réponse.
        Http::fake(['api.open-meteo.com/*' => fn () => Http::response(['daily' => [
            'time' => array_map(fn ($i) => today()->addDays($i)->toDateString(), array_keys($this->dailyMax)),
            'temperature_2m_max' => $this->dailyMax,
            'temperature_2m_min' => array_map(fn ($t) => $t - 10, $this->dailyMax),
        ]], $this->apiStatus)]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fakeDaily(array $max): void
    {
        $this->dailyMax = $max;
    }

    public function test_demo_mode_generates_seven_days_per_neighborhood_without_calling_the_api(): void
    {
        Neighborhood::factory()->count(2)->create();

        $count = app(WeatherService::class)->refreshAll(true);

        $this->assertSame(14, $count);
        $this->assertSame(14, WeatherForecast::count());
        Http::assertNothingSent();

        WeatherForecast::all()->each(function ($f) {
            $this->assertGreaterThanOrEqual(30.0, $f->temp_max);
            $this->assertLessThanOrEqual(43.0, $f->temp_max);
            $this->assertLessThan($f->temp_max, $f->temp_min);
            $this->assertContains($f->level, ['normal', 'warning', 'canicule']);
        });
    }

    public function test_refresh_all_sums_the_imported_days_of_every_neighborhood(): void
    {
        Neighborhood::factory()->count(3)->create();
        $this->fakeDaily([25, 26]);

        $this->assertSame(6, app(WeatherService::class)->refreshAll());
        Event::assertNotDispatched(HeatwaveDetected::class);
    }

    public function test_levels_follow_the_thresholds_and_risks_are_created_once(): void
    {
        $n = Neighborhood::factory()->create();
        $this->fakeDaily([40, 36, 25]);
        $service = app(WeatherService::class);

        $service->refreshNeighborhood($n);
        $service->refreshNeighborhood($n); // 2e actualisation : pas de doublon

        $levels = WeatherForecast::orderBy('date')->pluck('level')->all();
        $this->assertSame(['canicule', 'warning', 'normal'], $levels);

        $this->assertSame(2, OutageRisk::count());
        $this->assertSame('eleve', OutageRisk::whereHas('forecast', fn ($q) => $q->where('level', 'canicule'))->value('risk_level'));
        $this->assertSame('moyen', OutageRisk::whereHas('forecast', fn ($q) => $q->where('level', 'warning'))->value('risk_level'));
    }

    public function test_an_event_is_dispatched_for_new_forecasts_and_only_again_when_the_level_changes(): void
    {
        $n = Neighborhood::factory()->create();
        $service = app(WeatherService::class);

        $this->fakeDaily([40]);
        $service->refreshNeighborhood($n);
        Event::assertDispatchedTimes(HeatwaveDetected::class, 1);

        $service->refreshNeighborhood($n); // rien n'a changé
        Event::assertDispatchedTimes(HeatwaveDetected::class, 1);

        $this->fakeDaily([36]);            // canicule -> warning
        $service->refreshNeighborhood($n);
        Event::assertDispatchedTimes(HeatwaveDetected::class, 2);
    }

    public function test_no_event_for_forecasts_beyond_the_alert_window(): void
    {
        $n = Neighborhood::factory()->create();
        $this->fakeDaily([25, 25, 25, 41]); // jour +3 en canicule : hors fenêtre de 2 jours

        app(WeatherService::class)->refreshNeighborhood($n);

        Event::assertNotDispatched(HeatwaveDetected::class);
        $this->assertSame(1, OutageRisk::count()); // le risque est quand même créé
    }

    public function test_thresholds_stored_in_database_override_the_configuration(): void
    {
        AlertThreshold::create(['level' => 'warning', 'temp_max' => 30]);
        AlertThreshold::create(['level' => 'canicule', 'temp_max' => 33]);
        $n = Neighborhood::factory()->create();
        $this->fakeDaily([34, 31, 29]);

        app(WeatherService::class)->refreshNeighborhood($n);

        $this->assertSame(['canicule', 'warning', 'normal'], WeatherForecast::orderBy('date')->pluck('level')->all());
    }

    public function test_an_api_error_is_propagated(): void
    {
        $n = Neighborhood::factory()->create();
        $this->apiStatus = 500;

        $this->expectException(RequestException::class);

        app(WeatherService::class)->refreshNeighborhood($n);
    }
}

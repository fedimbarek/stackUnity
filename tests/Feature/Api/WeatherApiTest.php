<?php

namespace Tests\Feature\Api;

use App\Models\Neighborhood;
use App\Models\OutageRisk;
use App\Models\WeatherForecast;
use App\Services\CurrentWeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class WeatherApiTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function reading(): array
    {
        return [
            'temperature' => 31.5, 'apparent_temperature' => 33.0, 'humidity' => 55,
            'wind_speed' => 12.4, 'observed_at' => '2026-07-10T12:00',
        ];
    }

    public function test_guest_gets_a_json_401(): void
    {
        $this->getJson('/api/weather/current')->assertUnauthorized()->assertJsonStructure(['message']);
        $this->getJson('/api/weather/forecast')->assertUnauthorized();
    }

    public function test_current_weather_uses_the_neighborhood_of_the_user(): void
    {
        $n = Neighborhood::factory()->create(['name' => 'Menzah', 'city' => 'Tunis']);
        $this->mock(CurrentWeatherService::class, fn ($m) => $m->shouldReceive('get')
            ->once()->withArgs(fn ($arg) => $arg->is($n))->andReturn($this->reading()));

        $this->actingAs($this->resident($n))->getJson(route('api.weather.current'))
            ->assertOk()
            ->assertJsonPath('data.neighborhood', ['id' => $n->id, 'name' => 'Menzah', 'city' => 'Tunis'])
            ->assertJsonPath('data.temperature', 31.5)
            ->assertJsonPath('data.humidity', 55);
    }

    public function test_current_weather_accepts_an_explicit_neighborhood(): void
    {
        $mine = Neighborhood::factory()->create();
        $other = Neighborhood::factory()->create(['name' => 'Autre']);
        $this->mock(CurrentWeatherService::class, fn ($m) => $m->shouldReceive('get')
            ->once()->withArgs(fn ($arg) => $arg->is($other))->andReturn($this->reading()));

        $this->actingAs($this->resident($mine))
            ->getJson(route('api.weather.current', ['neighborhood_id' => $other->id]))
            ->assertOk()
            ->assertJsonPath('data.neighborhood.name', 'Autre');
    }

    public function test_a_neighborhood_is_required_when_the_user_has_none(): void
    {
        $this->actingAs($this->resident())->getJson(route('api.weather.current'))
            ->assertStatus(422)
            ->assertJsonPath('message', "neighborhood_id est requis : aucun quartier n'est associé à ce compte.");

        $this->actingAs($this->resident())->getJson(route('api.weather.forecast'))->assertStatus(422);
    }

    public function test_an_unknown_neighborhood_is_rejected(): void
    {
        $this->actingAs($this->resident())
            ->getJson(route('api.weather.current', ['neighborhood_id' => 999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('neighborhood_id');

        $this->actingAs($this->resident())
            ->getJson(route('api.weather.forecast', ['neighborhood_id' => 'abc']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('neighborhood_id');
    }

    public function test_current_weather_returns_503_when_the_service_fails(): void
    {
        $n = Neighborhood::factory()->create();
        $this->mock(CurrentWeatherService::class, fn ($m) => $m->shouldReceive('get')->andThrow(new \RuntimeException('down')));

        $this->actingAs($this->resident($n))->getJson(route('api.weather.current'))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Le service météo est indisponible pour le moment.');
    }

    public function test_forecast_lists_upcoming_days_with_their_risks(): void
    {
        $n = Neighborhood::factory()->create(['name' => 'Menzah']);
        $other = Neighborhood::factory()->create();

        WeatherForecast::factory()->ofLevel('canicule')->create(['neighborhood_id' => $n->id, 'date' => today()->subDay()->toDateString()]);
        $tomorrow = WeatherForecast::factory()->ofLevel('warning')->create(['neighborhood_id' => $n->id, 'date' => today()->addDay()->toDateString()]);
        $today = WeatherForecast::factory()->ofLevel('canicule')->create(['neighborhood_id' => $n->id, 'date' => today()->toDateString()]);
        WeatherForecast::factory()->create(['neighborhood_id' => $other->id, 'date' => today()->toDateString()]);
        OutageRisk::factory()->for($today, 'forecast')->level('eleve')->create(['description' => 'Délestage possible dans le quartier.']);

        $this->actingAs($this->resident($n))->getJson(route('api.weather.forecast'))
            ->assertOk()
            ->assertJsonPath('data.neighborhood.name', 'Menzah')
            ->assertJsonCount(2, 'data.forecasts')
            ->assertJsonPath('data.forecasts.0.date', today()->toDateString())
            ->assertJsonPath('data.forecasts.0.level', 'canicule')
            ->assertJsonPath('data.forecasts.0.outage_risks.0.risk_level', 'eleve')
            ->assertJsonPath('data.forecasts.0.outage_risks.0.description', 'Délestage possible dans le quartier.')
            ->assertJsonPath('data.forecasts.1.date', $tomorrow->date->toDateString())
            ->assertJsonPath('data.forecasts.1.outage_risks', []);
    }

    public function test_forecast_accepts_an_explicit_neighborhood(): void
    {
        $mine = Neighborhood::factory()->create();
        $other = Neighborhood::factory()->create();
        WeatherForecast::factory()->create(['neighborhood_id' => $other->id, 'date' => today()->toDateString()]);

        $this->actingAs($this->resident($mine))
            ->getJson(route('api.weather.forecast', ['neighborhood_id' => $other->id]))
            ->assertOk()
            ->assertJsonPath('data.neighborhood.id', $other->id)
            ->assertJsonCount(1, 'data.forecasts');
    }
}

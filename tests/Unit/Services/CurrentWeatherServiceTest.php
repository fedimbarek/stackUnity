<?php

namespace Tests\Unit\Services;

use App\Models\Neighborhood;
use App\Services\CurrentWeatherService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrentWeatherServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    private function neighborhood(int $id = 1): Neighborhood
    {
        $n = new Neighborhood(['name' => 'Menzah', 'city' => 'Tunis', 'latitude' => 36.83, 'longitude' => 10.16]);
        $n->id = $id;

        return $n;
    }

    private function fakeApi(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response(['current' => [
                'time' => '2026-07-10T12:00',
                'temperature_2m' => 36.4,
                'apparent_temperature' => 38.1,
                'relative_humidity_2m' => 48,
                'wind_speed_10m' => 15.2,
            ]]),
        ]);
    }

    public function test_it_maps_the_open_meteo_response_and_sends_the_coordinates(): void
    {
        $this->fakeApi();

        $weather = app(CurrentWeatherService::class)->get($this->neighborhood());

        $this->assertSame([
            'temperature' => 36.4,
            'apparent_temperature' => 38.1,
            'humidity' => 48,
            'wind_speed' => 15.2,
            'observed_at' => '2026-07-10T12:00',
        ], $weather);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'latitude=36.83')
            && str_contains($request->url(), 'longitude=10.16')
            && str_contains($request->url(), 'current='));
    }

    public function test_the_result_is_cached_for_ten_minutes_per_neighborhood(): void
    {
        $this->fakeApi();
        $service = app(CurrentWeatherService::class);

        $service->get($this->neighborhood(1));
        $service->get($this->neighborhood(1));
        Http::assertSentCount(1);

        $service->get($this->neighborhood(2));
        Http::assertSentCount(2);
    }

    public function test_a_failing_api_throws_and_nothing_is_cached(): void
    {
        Http::fake(['api.open-meteo.com/*' => Http::response([], 500)]);
        $service = app(CurrentWeatherService::class);

        try {
            $service->get($this->neighborhood());
            $this->fail('Une RequestException était attendue.');
        } catch (RequestException $e) {
            $this->assertSame(500, $e->response->status());
        }

        $this->assertFalse(Cache::has('weather.current.1'));
    }
}

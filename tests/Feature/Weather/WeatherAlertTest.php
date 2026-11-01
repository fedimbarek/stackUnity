<?php

namespace Tests\Feature\Weather;

use App\Events\HeatwaveDetected;
use App\Jobs\SendNotificationJob;
use App\Listeners\CreateWeatherAlert;
use App\Models\Neighborhood;
use App\Models\WeatherAlert;
use App\Models\WeatherForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WeatherAlertTest extends TestCase
{
    use RefreshDatabase;

    private WeatherForecast $forecast;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $quartier = Neighborhood::create([
            'name' => 'El Menzah',
            'city' => 'Tunis',
            'latitude' => 36.83,
            'longitude' => 10.16,
        ]);

        $this->forecast = WeatherForecast::create([
            'neighborhood_id' => $quartier->id,
            'date' => today()->toDateString(),
            'temp_max' => 40,
            'temp_min' => 29,
            'level' => 'canicule',
        ]);
    }

    private function declencherAlerte(): void
    {
        app(CreateWeatherAlert::class)->handle(
            new HeatwaveDetected($this->forecast)
        );
    }

    public function test_creation_alerte_automatique(): void
    {
        $this->declencherAlerte();

        $this->assertEquals(1, WeatherAlert::count());

        $this->assertDatabaseHas('weather_alerts', [
            'neighborhood_id' => $this->forecast->neighborhood_id,
            'level' => 'canicule',
            'source' => 'auto',
        ]);
    }

    public function test_message_alerte(): void
    {
        $this->declencherAlerte();

        $message = WeatherAlert::first()->message;

        $this->assertStringContainsString(
            'El Menzah',
            $message
        );

        $this->assertStringContainsString(
            '40',
            $message
        );
    }

    public function test_notification_declenchee(): void
    {
        $this->declencherAlerte();

        Queue::assertPushed(
            SendNotificationJob::class,
            1
        );
    }

    public function test_alerte_sans_doublon(): void
    {
        $this->declencherAlerte();
        $this->declencherAlerte();

        $this->assertEquals(1, WeatherAlert::count());

        Queue::assertPushed(
            SendNotificationJob::class,
            1
        );
    }

    public function test_alerte_forte_chaleur(): void
    {
        $this->forecast->update([
            'temp_max' => 36,
            'level' => 'warning',
        ]);

        $this->declencherAlerte();

        $this->assertDatabaseHas('weather_alerts', [
            'level' => 'warning',
            'source' => 'auto',
        ]);
    }
}

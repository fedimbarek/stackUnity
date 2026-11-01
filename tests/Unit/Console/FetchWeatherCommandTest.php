<?php

namespace Tests\Unit\Console;

use App\Services\WeatherService;
use Tests\TestCase;

class FetchWeatherCommandTest extends TestCase
{
    public function test_command_reports_the_number_of_imported_forecasts(): void
    {
        $this->mock(WeatherService::class, function ($mock) {
            $mock->shouldReceive('refreshAll')->once()->andReturn(14);
        });

        $this->artisan('weather:fetch')
            ->expectsOutput('14 prévisions importées.')
            ->assertSuccessful();
    }

    public function test_command_works_when_nothing_was_imported(): void
    {
        $this->mock(WeatherService::class, function ($mock) {
            $mock->shouldReceive('refreshAll')->once()->andReturn(0);
        });

        $this->artisan('weather:fetch')
            ->expectsOutput('0 prévisions importées.')
            ->assertSuccessful();
    }
}

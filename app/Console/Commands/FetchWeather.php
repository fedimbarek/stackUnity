<?php

namespace App\Console\Commands;

use App\Services\WeatherService;
use Illuminate\Console\Command;

class FetchWeather extends Command
{
    protected $signature = 'weather:fetch';
    protected $description = 'Importe les prévisions Open-Meteo pour chaque quartier';

    public function handle(WeatherService $service): int
    {
        $this->info($service->refreshAll() . ' prévisions importées.');
        return self::SUCCESS;
    }
}
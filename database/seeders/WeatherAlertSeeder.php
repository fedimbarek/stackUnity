<?php

namespace Database\Seeders;

use App\Models\Neighborhood;
use App\Models\WeatherAlert;
use App\Models\WeatherForecast;
use Illuminate\Database\Seeder;

class WeatherAlertSeeder extends Seeder
{
    public function run(): void
    {
        // Rejouable : on ne peuple que si la table est vide
        if (WeatherAlert::exists()) {
            return;
        }

        $neighborhoods = Neighborhood::all();

        // Une alerte manuelle par quartier
        foreach ($neighborhoods as $neighborhood) {
            WeatherAlert::factory()->for($neighborhood)->create();
        }

        // Une alerte automatique liée à une prévision de canicule, si elle existe
        $forecast = WeatherForecast::where('level', 'canicule')->first();

        if ($forecast) {
            WeatherAlert::factory()
                ->auto()
                ->level('canicule')
                ->for($forecast->neighborhood)
                ->create([
                    'weather_forecast_id' => $forecast->id,
                    'message' => "Canicule prévue le {$forecast->date->format('d/m/Y')} : jusqu'à {$forecast->temp_max}°C.",
                ]);
        }
    }
}
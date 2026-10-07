<?php

namespace Database\Seeders;

use App\Models\OutageRisk;
use App\Models\WeatherForecast;
use Illuminate\Database\Seeder;

class OutageRiskSeeder extends Seeder
{
    public function run(): void
    {
        $forecasts = WeatherForecast::where('level', '!=', 'normal')
            ->doesntHave('outageRisks')
            ->get();

        foreach ($forecasts as $forecast) {
            OutageRisk::factory()
                ->for($forecast, 'forecast')
                ->level($forecast->level === 'canicule' ? 'eleve' : 'moyen')
                ->create();
        }
    }
}
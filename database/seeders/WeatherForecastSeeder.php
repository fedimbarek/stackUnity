<?php

namespace Database\Seeders;

use App\Models\Neighborhood;
use App\Models\WeatherForecast;
use Illuminate\Database\Seeder;

class WeatherForecastSeeder extends Seeder
{
    public function run(): void
    {
        // Profil sur 7 jours, décalé d'un quartier à l'autre
        $profile = ['normal', 'warning', 'canicule', 'canicule', 'warning', 'normal', 'normal'];

        foreach (Neighborhood::all() as $index => $neighborhood) {
            foreach ($profile as $i => $level) {
                $date = today()->addDays($i)->toDateString();

                // Rejouable : on ne recrée pas une prévision qui existe déjà
                if (WeatherForecast::where('neighborhood_id', $neighborhood->id)->whereDate('date', $date)->exists()) {
                    continue;
                }

                $levelForDay = $profile[($i + $index) % count($profile)];

                WeatherForecast::factory()
                    ->for($neighborhood)
                    ->ofLevel($levelForDay)
                    ->create(['date' => $date]);
            }
        }
    }
}
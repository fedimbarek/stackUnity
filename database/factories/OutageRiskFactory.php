<?php

namespace Database\Factories;

use App\Models\WeatherForecast;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutageRiskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'weather_forecast_id' => WeatherForecast::factory(),
            'risk_level' => fake()->randomElement(['faible', 'moyen', 'eleve']),
            'description' => fake()->randomElement([
                'Forte demande de climatisation : risque de surcharge du réseau en fin de journée.',
                'Consommation électrique en hausse : coupures possibles aux heures de pointe.',
                'Délestage possible dans le quartier entre 12h et 17h.',
                'Chaleur prolongée : le transformateur local peut atteindre sa limite.',
            ]),
        ];
    }

    public function level(string $level): static
    {
        return $this->state(fn () => ['risk_level' => $level]);
    }
}
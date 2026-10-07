<?php

namespace Database\Factories;

use App\Models\Neighborhood;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeatherAlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'neighborhood_id' => Neighborhood::query()->inRandomOrder()->value('id'),
            'weather_forecast_id' => null,
            'created_by' => User::role('admin')->value('id'),
            'level' => fake()->randomElement(['warning', 'canicule']),
            'source' => 'manual',
            'message' => fake()->randomElement([
                'Forte chaleur annoncée demain : pensez à vous hydrater et à limiter les efforts.',
                'Canicule prévue cette semaine : préparez-vous à d\'éventuelles coupures de courant.',
                'Risque de surcharge du réseau en fin de journée : réduisez votre consommation.',
            ]),
        ];
    }

    /** Alerte générée automatiquement (pas d'auteur). */
    public function auto(): static
    {
        return $this->state(fn () => ['source' => 'auto', 'created_by' => null]);
    }

    public function level(string $level): static
    {
        return $this->state(fn () => ['level' => $level]);
    }
}
<?php

namespace Database\Factories;

use App\Models\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeatherForecastFactory extends Factory
{
    public function definition(): array
    {
        return [
            'neighborhood_id' => Neighborhood::query()->inRandomOrder()->value('id'),
            'date' => today()->toDateString(),
        ] + $this->temperatures(fake()->randomElement(['normal', 'warning', 'canicule']));
    }

    /** Températures et niveau cohérents avec les seuils par défaut (35 °C et 38 °C). */
    private function temperatures(string $level): array
    {
        $max = match ($level) {
            'canicule' => fake()->randomFloat(1, 38, 45),
            'warning' => fake()->randomFloat(1, 35, 37.9),
            default => fake()->randomFloat(1, 24, 34.9),
        };

        return [
            'temp_max' => $max,
            'temp_min' => round($max - fake()->randomFloat(1, 7, 12), 1),
            'level' => $level,
        ];
    }

    /** Ne change que les températures et le niveau : le quartier et la date restent ceux fournis. */
    public function ofLevel(string $level): static
    {
        return $this->state(fn () => $this->temperatures($level));
    }
}
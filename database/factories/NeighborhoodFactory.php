<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NeighborhoodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->streetName(),
            'city' => fake()->randomElement(['Tunis', 'Ariana', 'Sousse', 'Sfax', 'Nabeul']),
            'latitude' => fake()->latitude(33, 37),
            'longitude' => fake()->longitude(8, 11),
        ];
    }
}
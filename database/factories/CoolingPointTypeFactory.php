<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CoolingPointTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Parc', 'Fontaine', 'Espace climatisé', 'Bibliothèque', 'Jardin']);

        return [
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'icon'        => fake()->randomElement(['fa-tree', 'fa-tint', 'fa-snowflake', 'fa-book']),
            'color'       => fake()->randomElement(['#1cc88a', '#36b9cc', '#f6c23e', '#4e73df']),
            'description' => fake()->sentence(),
        ];
    }
}

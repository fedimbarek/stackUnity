<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CoolingPointTypeFactory extends Factory
{
    public function definition(): array
    {
        // Le nom doit être unique (contrainte cooling_point_types_name_unique) :
        // on suffixe le nom de base avec un compteur unique pour éviter les tests aléatoires.
        $suffix = fake()->unique()->numberBetween(1, 99999);
        $name = fake()->randomElement(['Parc', 'Fontaine', 'Espace climatisé', 'Bibliothèque', 'Jardin']).' '.$suffix;

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'icon'        => fake()->randomElement(['fa-tree', 'fa-tint', 'fa-snowflake', 'fa-book']),
            'color'       => fake()->randomElement(['#1cc88a', '#36b9cc', '#f6c23e', '#4e73df']),
            'description' => fake()->sentence(),
        ];
    }
}
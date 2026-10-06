<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EquipementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->words(2, true),
            'type_equipement' => fake()->randomElement(['Camping', 'Sport', 'Outillage']),
            'date_ajout' => fake()->date(),
            'image' => 'equipements/default.jpg',
            'prix_louer' => fake()->randomFloat(2, 5, 200),
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\ContactCategory;
use App\Models\EmergencyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmergencyContact> */
class EmergencyContactFactory extends Factory
{
    protected $model = EmergencyContact::class;

    public function definition(): array
    {
        return [
            // relation : crée une catégorie si aucune n'est fournie
            'contact_category_id' => ContactCategory::factory(),
            'name'        => fake()->company(),
            'phone'       => fake()->numerify('7# ### ###'),
            'address'     => fake()->streetAddress(),
            'city'        => fake()->randomElement(['Tunis', 'Ariana', 'Ben Arous', 'La Marsa', 'Sfax', 'Sousse', 'Nabeul', 'Bizerte']),
            'description' => fake()->sentence(12),
            'is_24h'      => fake()->boolean(40),
            'is_priority' => false,
            'is_active'   => true,
        ];
    }
}

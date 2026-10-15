<?php

namespace Database\Factories;

use App\Models\CoolingPointType;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoolingPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cooling_point_type_id' => CoolingPointType::factory(),
            'name'                  => 'Point de fraîcheur ' . fake()->unique()->numberBetween(1, 9999),
            'type'                  => 'park',
            'latitude'              => fake()->latitude(36.75, 36.90),
            'longitude'             => fake()->longitude(10.10, 10.30),
            'address'               => fake()->streetAddress(),
            'opening_hours'         => fake()->randomElement(['08:00-20:00', '24/7', '09:00-18:00']),
            'source'                => 'manual',
            'osm_id'                => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Neighborhood;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-2 months', '-1 week');

        return [
            'title' => 'Rapport '.fake()->monthName(),
            'period_start' => $start,
            'period_end' => (clone $start)->modify('+6 days'),
            'neighborhood_id' => Neighborhood::inRandomOrder()->first()?->id,
            'generated_by' => User::role('admin')->inRandomOrder()->first()?->id ?? User::factory(),
            'status' => fake()->randomElement(['generated', 'sent']),
        ];
    }
}
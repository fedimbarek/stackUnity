<?php

namespace Database\Seeders;

use App\Models\Neighborhood;
use Illuminate\Database\Seeder;

class NeighborhoodSeeder extends Seeder
{
    public function run(): void
    {
        $neighborhoods = [
            ['name' => 'El Menzah', 'city' => 'Tunis', 'latitude' => 36.8412, 'longitude' => 10.1622],
            ['name' => 'Ariana Ville', 'city' => 'Ariana', 'latitude' => 36.8625, 'longitude' => 10.1956],
            ['name' => 'La Marsa', 'city' => 'Tunis', 'latitude' => 36.8783, 'longitude' => 10.3247],
            ['name' => 'Sousse Centre', 'city' => 'Sousse', 'latitude' => 35.8256, 'longitude' => 10.6412],
        ];

        foreach ($neighborhoods as $n) {
            Neighborhood::firstOrCreate(['name' => $n['name']], $n);
        }
    }
}
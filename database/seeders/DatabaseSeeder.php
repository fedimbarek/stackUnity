<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            NeighborhoodSeeder::class,
            UserSeeder::class,
            AlertThresholdSeeder::class,
            WeatherForecastSeeder::class,
            OutageRiskSeeder::class,
            WeatherAlertSeeder::class,
        ]);
    }
}
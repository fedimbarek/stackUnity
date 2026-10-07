<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;

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
        Report::factory(6)->create();
    }
}
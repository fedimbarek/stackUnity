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
            DemoDataSeeder::class,

            // Module météo (dhia)
            AlertThresholdSeeder::class,
            WeatherForecastSeeder::class,
            OutageRiskSeeder::class,
            WeatherAlertSeeder::class,

            // Équipe (main) : parents avant enfants
            ContactCategorySeeder::class,
            EmergencyContactSeeder::class,
            CoolingPointTypeSeeder::class,
            CoolingPointSeeder::class,
        ]);

        Report::factory(6)->create();
    }
}
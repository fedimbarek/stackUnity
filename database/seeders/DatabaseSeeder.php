<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Report;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            NeighborhoodSeeder::class,
            UserSeeder::class,
            DemoDataSeeder::class,
             ContactCategorySeeder::class,
             EmergencyContactSeeder::class,

        ]);
        Report::factory(6)->create();
    }
}
<?php

namespace Database\Seeders;

use App\Models\Neighborhood;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        Neighborhood::factory(5)->create()->each(function (Neighborhood $neighborhood) {
            User::factory(3)
                ->create(['neighborhood_id' => $neighborhood->id])
                ->each(fn (User $user) => $user->assignRole('resident'));
        });
    }
}
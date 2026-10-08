<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'resident']);
        Role::firstOrCreate(['name' => 'gestionnaire']);
        Role::firstOrCreate(['name' => 'admin']);
    }
}
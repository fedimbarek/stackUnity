<?php

namespace Database\Seeders;

use App\Models\Neighborhood;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $neighborhood = Neighborhood::first(); // créé par NeighborhoodSeeder

        $accounts = [
            ['name' => 'Admin HeatAlert', 'email' => 'admin@heatalert.tn', 'role' => 'admin'],
            ['name' => 'Gestionnaire Résidence', 'email' => 'gestionnaire@heatalert.tn', 'role' => 'gestionnaire'],
            ['name' => 'Résident Démo', 'email' => 'resident@heatalert.tn', 'role' => 'resident'],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('TestHeatAlert'), // à changer avant la démo !
                    'neighborhood_id' => $neighborhood?->id,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
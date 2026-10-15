<?php

namespace Database\Seeders;

use App\Models\ContactCategory;
use App\Models\EmergencyContact;
use Illuminate\Database\Seeder;

class ContactCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Protection civile',     'icon' => 'bi-fire',             'color' => '#dc3545'],
            ['name' => 'Santé & Hôpitaux',      'icon' => 'bi-hospital',         'color' => '#0d6efd'],
            ['name' => 'Pharmacies de garde',   'icon' => 'bi-capsule',          'color' => '#198754'],
            ['name' => 'Électricité (STEG)',    'icon' => 'bi-lightning-charge', 'color' => '#fd7e14'],
            ['name' => 'Eau (SONEDE)',          'icon' => 'bi-droplet',          'color' => '#0aa2c0'],
            ['name' => 'Police & Sécurité',     'icon' => 'bi-shield-check',     'color' => '#6f42c1'],
        ];

        foreach ($categories as $data) {
            // Catégorie + 3 contacts liés (relation 1-N) via factory
            ContactCategory::factory()
                ->has(EmergencyContact::factory()->count(3), 'contacts')
                ->create($data);
        }
    }
}

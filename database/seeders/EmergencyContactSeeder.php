<?php

namespace Database\Seeders;

use App\Models\ContactCategory;
use Illuminate\Database\Seeder;

/**
 * Numéros d'urgence officiels (appel rapide).
 * À lancer APRÈS ContactCategorySeeder.
 */
class EmergencyContactSeeder extends Seeder
{
    public function run(): void
    {
        $priority = [
            ['cat' => 'Police & Sécurité',  'name' => 'Police secours',   'phone' => '197'],
            ['cat' => 'Police & Sécurité',  'name' => 'Garde nationale',  'phone' => '193'],
            ['cat' => 'Protection civile',  'name' => 'Protection civile (pompiers)', 'phone' => '198'],
            ['cat' => 'Santé & Hôpitaux',   'name' => 'SAMU',             'phone' => '190'],
        ];

        foreach ($priority as $item) {
            $category = ContactCategory::where('name', $item['cat'])->first();
            if (! $category) {
                continue;
            }

            $category->contacts()->updateOrCreate(
                ['phone' => $item['phone']],
                [
                    'name'        => $item['name'],
                    'city'        => 'Toute la Tunisie',
                    'description' => 'Numéro d\'urgence national, joignable 24h/24.',
                    'is_24h'      => true,
                    'is_priority' => true,
                    'is_active'   => true,
                ]
            );
        }
    }
}

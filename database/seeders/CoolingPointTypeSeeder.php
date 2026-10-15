<?php

namespace Database\Seeders;

use App\Models\CoolingPointType;
use Illuminate\Database\Seeder;

class CoolingPointTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Parc',             'slug' => 'parc',      'icon' => 'fa-tree',      'color' => '#1cc88a', 'description' => 'Espaces verts ombragés'],
            ['name' => 'Fontaine',         'slug' => 'fontaine',  'icon' => 'fa-tint',      'color' => '#36b9cc', 'description' => 'Points d\'eau potable'],
            ['name' => 'Espace climatisé', 'slug' => 'climatise', 'icon' => 'fa-snowflake', 'color' => '#f6c23e', 'description' => 'Lieux climatisés'],
            ['name' => 'Bibliothèque',     'slug' => 'biblio',    'icon' => 'fa-book',      'color' => '#4e73df', 'description' => 'Salles de lecture fraîches'],
        ];

        foreach ($types as $type) {
            CoolingPointType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}

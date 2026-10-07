<?php

namespace Database\Seeders;

use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use Illuminate\Database\Seeder;

class CoolingPointSeeder extends Seeder
{
    public function run(): void
    {
        CoolingPointType::all()->each(function ($type) {
            CoolingPoint::factory()->count(3)->create([
                'cooling_point_type_id' => $type->id,
                'type' => match ($type->slug) {
                    'parc'      => 'park',
                    'fontaine'  => 'fountain',
                    'climatise' => 'mall',
                    'biblio'    => 'mall',
                    default     => 'park',
                },
            ]);
        });
    }
}

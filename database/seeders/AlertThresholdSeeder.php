<?php

namespace Database\Seeders;

use App\Models\AlertThreshold;
use Illuminate\Database\Seeder;

class AlertThresholdSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['level' => 'warning', 'temp_max' => 35], ['level' => 'canicule', 'temp_max' => 38]] as $t) {
            AlertThreshold::firstOrCreate(['level' => $t['level']], $t);
        }
    }
}
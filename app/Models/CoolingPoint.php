<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoolingPoint extends Model
{
    protected $fillable = [
        'name',
        'type',        // 'park', 'fountain', 'mall'
        'latitude',
        'longitude',
        'address',
        'opening_hours',
        'source',      // 'osm' ou 'manual'
        'osm_id',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
    ];
}

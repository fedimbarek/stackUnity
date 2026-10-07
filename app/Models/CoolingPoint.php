<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoolingPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'cooling_point_type_id',
        'name',
        'type',
        'latitude',
        'longitude',
        'address',
        'opening_hours',
        'source',
        'osm_id',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    // Relation inverse : un point appartient à un type
    public function coolingPointType()
    {
        return $this->belongsTo(CoolingPointType::class);
    }
}

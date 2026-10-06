<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PowerOutage extends Model
{
    use HasFactory;

    protected $fillable = [
        'neighborhood_id', 'status', 'latitude', 'longitude',
        'source', 'created_by', 'started_at', 'confirmed_at',
        'resolved_at', 'estimated_end_time',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'estimated_end_time' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function reports()
    {
        return $this->hasMany(OutageReport::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutageReport extends Model
{
    protected $fillable = [
        'power_outage_id', 'user_id', 'latitude', 'longitude',
        'trust_weight', 'trust_flags',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'trust_weight' => 'float',
            'trust_flags' => 'array',
        ];
    }

    public function outage()
    {
        return $this->belongsTo(PowerOutage::class, 'power_outage_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
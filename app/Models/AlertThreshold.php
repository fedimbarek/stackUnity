<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertThreshold extends Model
{
    protected $fillable = ['level', 'temp_max'];

    protected function casts(): array
    {
        return ['temp_max' => 'float'];
    }
}
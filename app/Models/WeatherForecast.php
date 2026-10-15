<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherForecast extends Model
{
    use HasFactory;

    protected $fillable = ['neighborhood_id', 'date', 'temp_max', 'temp_min', 'level'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'temp_max' => 'float', 'temp_min' => 'float'];
    }

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function outageRisks()
    {
        return $this->hasMany(OutageRisk::class);
    }
}
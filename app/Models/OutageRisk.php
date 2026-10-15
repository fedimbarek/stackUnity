<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutageRisk extends Model
{
    use HasFactory;

    protected $fillable = ['weather_forecast_id', 'risk_level', 'description'];

    public function forecast()
    {
        return $this->belongsTo(WeatherForecast::class, 'weather_forecast_id');
    }
}
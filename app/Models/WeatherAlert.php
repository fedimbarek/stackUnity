<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherAlert extends Model
{
    protected $fillable = [
        'neighborhood_id', 'weather_forecast_id', 'created_by',
        'level', 'source', 'message',
    ];

    public function neighborhood()
    {
        return $this->belongsTo(Neighborhood::class);
    }

    public function forecast()
    {
        return $this->belongsTo(WeatherForecast::class, 'weather_forecast_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
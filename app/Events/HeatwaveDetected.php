<?php

namespace App\Events;

use App\Models\WeatherForecast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HeatwaveDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(public WeatherForecast $forecast)
    {
    }
}
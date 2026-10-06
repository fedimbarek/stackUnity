<?php

namespace App\Services;

use App\Models\Neighborhood;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrentWeatherService
{
    public function get(Neighborhood $n): array
    {
        return Cache::remember("weather.current.{$n->id}", now()->addMinutes(10), function () use ($n) {
            $c = Http::timeout(30)
                ->connectTimeout(20)
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $n->latitude,
                    'longitude' => $n->longitude,
                    'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,wind_speed_10m',
                    'timezone' => 'auto',
                ])->throw()->json('current');

            return [
                'temperature' => $c['temperature_2m'],
                'apparent_temperature' => $c['apparent_temperature'],
                'humidity' => $c['relative_humidity_2m'],
                'wind_speed' => $c['wind_speed_10m'],
                'observed_at' => $c['time'],
            ];
        });
    }
}
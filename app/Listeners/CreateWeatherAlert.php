<?php

namespace App\Listeners;

use App\Events\HeatwaveDetected;
use App\Jobs\SendNotificationJob;
use App\Models\WeatherAlert;

class CreateWeatherAlert
{
    public function handle(HeatwaveDetected $event): void
    {
        $f = $event->forecast->loadMissing('neighborhood');

        $date = $f->date->format('d/m/Y');
        $place = $f->neighborhood->name;

        $message = $f->level === 'canicule'
            ? "Canicule prévue le {$date} à {$place} : jusqu'à {$f->temp_max}°C. Hydratez-vous, limitez les efforts et préparez-vous à d'éventuelles coupures."
            : "Forte chaleur prévue le {$date} à {$place} : jusqu'à {$f->temp_max}°C. Pensez à vous hydrater et à limiter votre consommation d'énergie.";

        // Une seule alerte par prévision et par niveau (pas de doublon à chaque actualisation)
        $alert = WeatherAlert::firstOrCreate(
            [
                'neighborhood_id' => $f->neighborhood_id,
                'weather_forecast_id' => $f->id,
                'level' => $f->level,
            ],
            [
                'source' => 'auto',
                'message' => $message,
            ]
        );

        // On ne notifie que les alertes qui viennent d'être créées
        if ($alert->wasRecentlyCreated) {
            SendNotificationJob::dispatch($alert);
        }
    }
}
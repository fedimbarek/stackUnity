<?php

namespace App\Services;

use App\Events\HeatwaveDetected;
use App\Models\AlertThreshold;
use App\Models\Neighborhood;
use App\Models\OutageRisk;
use App\Models\WeatherForecast;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    /** Nombre de jours à l'avance pour lesquels on déclenche une alerte automatique. */
    private const ALERT_DAYS_AHEAD = 2;

    public function refreshAll(bool $demo = false): int
    {
        $count = 0;

        foreach (Neighborhood::all() as $neighborhood) {
            $count += $this->refreshNeighborhood($neighborhood, $demo);
        }

        return $count;
    }

    public function refreshNeighborhood(Neighborhood $n, bool $demo = false): int
    {
        $data = $demo ? $this->demoData() : $this->fetchDaily($n);

        foreach ($data['time'] as $i => $day) {
            $tmax = $data['temperature_2m_max'][$i];

            $forecast = WeatherForecast::updateOrCreate(
                ['neighborhood_id' => $n->id, 'date' => $day],
                [
                    'temp_max' => $tmax,
                    'temp_min' => $data['temperature_2m_min'][$i],
                    'level' => $this->level($tmax),
                ]
            );

            if ($forecast->level === 'normal') {
                continue;
            }

            if ($forecast->outageRisks()->doesntExist()) {
                OutageRisk::create($this->risk($forecast));
            }

            // Alerte automatique : prévision nouvelle ou dont le niveau vient de changer,
            // et dans les prochains jours seulement.
            $isNewOrChanged = $forecast->wasRecentlyCreated || $forecast->wasChanged('level');

            if ($isNewOrChanged && $forecast->date->lte(today()->addDays(self::ALERT_DAYS_AHEAD))) {
                HeatwaveDetected::dispatch($forecast);
            }
        }

        return count($data['time']);
    }

    private function fetchDaily(Neighborhood $n): array
    {
        return Http::timeout(30)
            ->connectTimeout(20)
            ->withOptions(['force_ip_resolve' => 'v4'])
            ->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $n->latitude,
                'longitude' => $n->longitude,
                'daily' => 'temperature_2m_max,temperature_2m_min',
                'timezone' => 'auto',
                'forecast_days' => 7,
            ])->throw()->json('daily');
    }

    private function demoData(): array
    {
        $data = ['time' => [], 'temperature_2m_max' => [], 'temperature_2m_min' => []];

        for ($i = 0; $i < 7; $i++) {
            $max = rand(300, 430) / 10;
            $data['time'][] = today()->addDays($i)->toDateString();
            $data['temperature_2m_max'][] = $max;
            $data['temperature_2m_min'][] = round($max - rand(80, 120) / 10, 1);
        }

        return $data;
    }

    /** Seuils lus en base, avec repli sur .env si la table est vide. */
    public function thresholds(): array
    {
        $db = AlertThreshold::pluck('temp_max', 'level');

        return [
            'warning' => (float) ($db['warning'] ?? config('services.weather.heat_warning')),
            'canicule' => (float) ($db['canicule'] ?? config('services.weather.heat_threshold')),
        ];
    }

    private function level(float $tmax): string
    {
        $t = $this->thresholds();

        if ($tmax >= $t['canicule']) return 'canicule';
        if ($tmax >= $t['warning']) return 'warning';

        return 'normal';
    }

    private function risk(WeatherForecast $f): array
    {
        $canicule = $f->level === 'canicule';

        return [
            'weather_forecast_id' => $f->id,
            'risk_level' => $canicule ? 'eleve' : 'moyen',
            'description' => $canicule
                ? "Canicule ({$f->temp_max}°C) : forte demande de climatisation, risque de surcharge du réseau et de délestage."
                : "Forte chaleur ({$f->temp_max}°C) : consommation électrique en hausse, coupures possibles.",
        ];
    }
}
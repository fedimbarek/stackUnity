<?php

namespace App\Services;

use App\Models\WeatherForecast;
use Illuminate\Support\Collection;

/**
 * Indicateur pédagogique de vigilance, et non indice météorologique officiel.
 * Calcule un score à partir des températures maximales sur 3 jours.
 */
class HeatRiskScoreService
{
    public function calculate(Collection $forecasts, float $warning, float $canicule): array
    {
        $days = $forecasts->sortBy(fn (WeatherForecast $f) => $f->date->format('Y-m-d'))
            ->take(3)->values();
        if ($days->isEmpty()) {
            return ['score' => 0, 'level' => 'indisponible', 'label' => 'Données indisponibles',
                'recommendation' => 'Actualisez les prévisions pour calculer le score.', 'days' => 0];
        }

        $max = (float) $days->max('temp_max');
        $hotDays = $days->filter(fn ($f) => $f->temp_max >= $warning)->count();
        $heatwaveDays = $days->filter(fn ($f) => $f->temp_max >= $canicule)->count();

        // Score transparent et borné : température + persistance de la chaleur.
        // Progression continue avant le seuil d'alerte :
        // 0 °C => 0 point, 24,8 °C => ~21 points, 35 °C => 30 points.
        // Ne pas assimiler une temperature normale a une absence de donnees.
        $temperaturePoints = $max < $warning
            ? ($warning > 0 ? max(0, min(29, ($max / $warning) * 30)) : 0)
            : 30 + max(0, $max - $warning) * 5;
        $score = (int) min(100, round($temperaturePoints + max(0, $hotDays - 1) * 10 + $heatwaveDays * 10));

        if ($score >= 80) {
            [$level, $label, $recommendation] = ['critique', 'Critique',
                'Évitez les efforts aux heures chaudes, hydratez-vous et surveillez les alertes de coupure.'];
        } elseif ($score >= 60) {
            [$level, $label, $recommendation] = ['eleve', 'Élevé',
                'Limitez les activités extérieures et réduisez la consommation électrique aux heures de pointe.'];
        } elseif ($score >= 30) {
            [$level, $label, $recommendation] = ['modere', 'Modéré',
                'Hydratez-vous régulièrement et surveillez les prochaines prévisions.'];
        } else {
            [$level, $label, $recommendation] = ['faible', 'Faible',
                'Aucune précaution supplémentaire au-delà des recommandations habituelles.'];
        }

        return compact('score', 'level', 'label', 'recommendation') + ['days' => $days->count()];
    }
}

<?php

namespace App\Services;

use App\Models\WeatherForecast;
use Illuminate\Support\Collection;

/**
 * Score indicatif de vigilance chaleur.
 * Analyse les températures maximales sur 3 jours.
 */
class HeatRiskScoreService
{
    public function calculate(
        Collection $forecasts,
        float $warning,
        float $canicule
    ): array {

        // Récupérer les 3 prochains jours
        $days = $forecasts
            ->sortBy(fn (WeatherForecast $f) =>
                $f->date->format('Y-m-d')
            )
            ->take(3)
            ->values();

        // Aucune prévision disponible
        if ($days->isEmpty()) {
            return [
                'score' => 0,
                'level' => 'indisponible',
                'label' => 'Données indisponibles',
                'recommendation' =>
                    'Actualisez les prévisions pour calculer le score.',
                'days' => 0,
            ];
        }

        // Température maximale
        $max = (float) $days->max('temp_max');

        // Nombre de jours de forte chaleur
        $hotDays = $days->filter(
            fn ($f) => $f->temp_max >= $warning
        )->count();

        // Nombre de jours de canicule
        $heatwaveDays = $days->filter(
            fn ($f) => $f->temp_max >= $canicule
        )->count();

        // Calcul progressif du score
        $temperaturePoints = $max < $warning
            ? ($warning > 0
                ? max(0, min(29, ($max / $warning) * 30))
                : 0)
            : 30 + max(0, $max - $warning) * 5;

        // Ajouter les points liés à la durée
        $score = (int) min(100, round(
            $temperaturePoints
            + max(0, $hotDays - 1) * 10
            + $heatwaveDays * 10
        ));

        // Déterminer le niveau de risque
        if ($score >= 80) {
            $level = 'critique';
            $label = 'Critique';
            $recommendation =
                'Évitez les efforts aux heures chaudes, hydratez-vous et surveillez les alertes de coupure.';

        } elseif ($score >= 60) {
            $level = 'eleve';
            $label = 'Élevé';
            $recommendation =
                'Limitez les activités extérieures et réduisez la consommation électrique aux heures de pointe.';

        } elseif ($score >= 30) {
            $level = 'modere';
            $label = 'Modéré';
            $recommendation =
                'Hydratez-vous régulièrement et surveillez les prochaines prévisions.';

        } else {
            $level = 'faible';
            $label = 'Faible';
            $recommendation =
                'Aucune précaution supplémentaire au-delà des recommandations habituelles.';
        }

        return [
            'score' => $score,
            'level' => $level,
            'label' => $label,
            'recommendation' => $recommendation,
            'days' => $days->count(),
        ];
    }
}

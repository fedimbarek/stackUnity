<?php

namespace App\Services;

use App\Models\Neighborhood;
use App\Models\OutageReport;
use App\Models\PowerOutage;
use App\Models\WeatherForecast;
use Carbon\Carbon;

class OutageForecastService
{
    private const DEFAULT_MINUTES = 90;
    private const HISTORY_DAYS = 180;

    /** @return array{minutes:int, eta:Carbon, confidence:string, sample:int, factors:array} */
    public function predictDuration(PowerOutage $outage): array
    {
        $local = $this->durations($outage->neighborhood_id);
        $global = $this->durations(null);
        $factors = [];

        if (count($local) >= 5) {
            $base = $this->median($local);
            $sample = count($local);
            $confidence = $sample >= 15 ? 'haute' : 'moyenne';
            $factors[] = "médiane des {$sample} coupures passées du quartier";
        } elseif (count($global) >= 5) {
            $base = $this->median($global);
            $sample = count($global);
            $confidence = 'faible';
            $factors[] = "médiane globale ({$sample} coupures) faute d'historique local";
        } else {
            $base = self::DEFAULT_MINUTES;
            $sample = 0;
            $confidence = 'faible';
            $factors[] = 'valeur par défaut (pas assez de données)';
        }

        $started = $outage->started_at ?? now();
        $multiplier = 1.0;

        if ($started->hour >= 22 || $started->hour < 6) {
            $multiplier *= 1.25;
            $factors[] = 'coupure de nuit (+25 %)';
        }

        $heatLevel = WeatherForecast::where('neighborhood_id', $outage->neighborhood_id)
            ->whereDate('date', $started->toDateString())
            ->value('level');

        if ($heatLevel === 'canicule') {
            $multiplier *= 1.3;
            $factors[] = 'canicule en cours (+30 %)';
        }

        $reports = $outage->reports()->count();
        if ($reports >= 10) {
            $multiplier *= 1.2;
            $factors[] = "{$reports} signalements : panne étendue (+20 %)";
        }

        $minutes = (int) round($base * $multiplier);

        return [
            'minutes' => $minutes,
            'eta' => $started->copy()->addMinutes($minutes),
            'confidence' => $confidence,
            'sample' => $sample,
            'factors' => $factors,
        ];
    }

    /** @return array{score:int, level:string, factors:array<string,int>} */
    public function riskScore(Neighborhood $neighborhood): array
    {
        $factors = [];

        // 1) Historique 30 jours : max 40 pts
        $recent = PowerOutage::where('neighborhood_id', $neighborhood->id)
            ->where('started_at', '>=', now()->subDays(30))
            ->count();
        $factors['historique_30j'] = (int) round(min($recent, 8) / 8 * 40);

        // 2) Chaleur prévue sur 3 jours : max 35 pts
        $maxTemp = WeatherForecast::where('neighborhood_id', $neighborhood->id)
            ->whereBetween('date', [today(), today()->addDays(3)])
            ->max('temp_max');
        $warning = (float) config('services.weather.heat_warning', 35);
        $heat = 0;
        if ($maxTemp !== null && $maxTemp >= $warning - 5) {
            $heat = (int) round(min(1, ($maxTemp - ($warning - 5)) / 10) * 35);
        }
        $factors['chaleur_prevue'] = $heat;

        // 3) Activité en cours : max 25 pts
        $active = PowerOutage::where('neighborhood_id', $neighborhood->id)
            ->whereIn('status', ['reported', 'confirmed'])
            ->exists();
        $reports24h = OutageReport::whereHas('outage', fn ($q) => $q->where('neighborhood_id', $neighborhood->id))
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $factors['activite_en_cours'] = $active ? 25 : (int) round(min($reports24h, 5) / 5 * 15);

        $score = (int) min(100, array_sum($factors));

        return [
            'score' => $score,
            'level' => match (true) {
                $score >= 70 => 'critique',
                $score >= 45 => 'élevé',
                $score >= 20 => 'modéré',
                default => 'faible',
            },
            'factors' => $factors,
        ];
    }

    /** Tous les quartiers, du plus risqué au moins risqué. */
    public function rankNeighborhoods(): array
    {
        return Neighborhood::orderBy('name')->get()
            ->map(fn (Neighborhood $n) => ['neighborhood' => $n] + $this->riskScore($n))
            ->sortByDesc('score')
            ->values()
            ->all();
    }

    private function durations(?int $neighborhoodId): array
    {
        return PowerOutage::where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->where('started_at', '>=', now()->subDays(self::HISTORY_DAYS))
            ->when($neighborhoodId, fn ($q) => $q->where('neighborhood_id', $neighborhoodId))
            ->get(['started_at', 'resolved_at'])
            ->map(fn ($o) => $o->started_at->diffInMinutes($o->resolved_at))
            ->filter(fn ($m) => $m > 0 && $m < 60 * 48)
            ->values()
            ->all();
    }

    private function median(array $values): float
    {
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }
}
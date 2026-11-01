<?php

namespace App\Services;

use App\Events\OutageConfirmed;
use App\Events\OutageResolved;
use App\Models\Neighborhood;
use App\Models\OutageReport;
use App\Models\PowerOutage;
use App\Models\User;

class OutageAggregationService
{
    private const CONFIRM_THRESHOLD = 3.0;          // somme des poids de confiance nécessaire
    private const CONFIRM_WINDOW_MINUTES = 15;
    private const DEDUPE_WINDOW_MINUTES = 60;
    private const NEARBY_RADIUS_METERS = 500;
    private const AUTO_CLOSE_AFTER_HOURS = 3;
    private const AUTO_CLOSE_SILENCE_MINUTES = 120;

    public function __construct(
        private ReporterTrustService $trust,
        private OutageForecastService $forecast,
    ) {}

    public function report(User $user, Neighborhood $neighborhood, ?float $latitude, ?float $longitude): PowerOutage
    {
        $outage = $this->findMatchingOutage($neighborhood, $latitude, $longitude);

        if (! $outage) {
            $outage = PowerOutage::create([
                'neighborhood_id' => $neighborhood->id,
                'status' => 'reported',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'source' => 'user',
                'started_at' => now(),
            ]);
        }

        $evaluation = $this->trust->evaluate($user, $neighborhood, $latitude, $longitude);

        OutageReport::create([
            'power_outage_id' => $outage->id,
            'user_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'trust_weight' => $evaluation['weight'],
            'trust_flags' => $evaluation['flags'],
        ]);

        // ETA automatique (si un gestionnaire ne l'a pas déjà saisie)
        if (! $outage->estimated_end_time) {
            $outage->update(['estimated_end_time' => $this->forecast->predictDuration($outage)['eta']]);
        }

        $this->maybeConfirm($outage);

        return $outage->fresh();
    }

    public function confirm(PowerOutage $outage): PowerOutage
    {
        if ($outage->status !== 'confirmed') {
            $outage->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            event(new OutageConfirmed($outage));
        }

        return $outage;
    }

    public function resolve(PowerOutage $outage): PowerOutage
    {
        if ($outage->status !== 'resolved') {
            $outage->update(['status' => 'resolved', 'resolved_at' => now()]);
            event(new OutageResolved($outage));
        }

        return $outage;
    }

    public function closeStaleOutages(): int
    {
        $stale = PowerOutage::whereIn('status', ['reported', 'confirmed'])
            ->where('started_at', '<=', now()->subHours(self::AUTO_CLOSE_AFTER_HOURS))
            ->whereDoesntHave('reports', function ($q) {
                $q->where('created_at', '>=', now()->subMinutes(self::AUTO_CLOSE_SILENCE_MINUTES));
            })
            ->get();

        foreach ($stale as $outage) {
            $this->resolve($outage);
        }

        return $stale->count();
    }

    private function findMatchingOutage(Neighborhood $neighborhood, ?float $latitude, ?float $longitude): ?PowerOutage
    {
        $candidates = PowerOutage::where('neighborhood_id', $neighborhood->id)
            ->whereIn('status', ['reported', 'confirmed'])
            ->where('started_at', '>=', now()->subMinutes(self::DEDUPE_WINDOW_MINUTES))
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        if (! $latitude || ! $longitude) {
            return $candidates->first();
        }

        foreach ($candidates as $candidate) {
            if (! $candidate->latitude || ! $candidate->longitude) {
                continue;
            }

            if ($this->distanceInMeters($latitude, $longitude, $candidate->latitude, $candidate->longitude) <= self::NEARBY_RADIUS_METERS) {
                return $candidate;
            }
        }

        return null;
    }

    private function maybeConfirm(PowerOutage $outage): void
    {
        if ($outage->status !== 'reported') {
            return;
        }

        // Chaque utilisateur compte une seule fois (son meilleur poids)
        $score = OutageReport::where('power_outage_id', $outage->id)
            ->where('created_at', '>=', now()->subMinutes(self::CONFIRM_WINDOW_MINUTES))
            ->get(['user_id', 'trust_weight'])
            ->groupBy('user_id')
            ->sum(fn ($rows) => (float) $rows->max('trust_weight'));

        if ($score >= self::CONFIRM_THRESHOLD) {
            $this->confirm($outage);
        }
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
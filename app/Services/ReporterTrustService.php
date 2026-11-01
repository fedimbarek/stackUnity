<?php

namespace App\Services;

use App\Models\Neighborhood;
use App\Models\OutageReport;
use App\Models\User;

class ReporterTrustService
{
    /** @return array{weight: float, flags: string[]} */
    public function evaluate(User $user, Neighborhood $neighborhood, ?float $lat, ?float $lng): array
    {
        $weight = 1.0;
        $flags = [];

        if ($lat && $lng && $neighborhood->latitude && $neighborhood->longitude) {
            $km = $this->distanceKm($lat, $lng, (float) $neighborhood->latitude, (float) $neighborhood->longitude);

            if ($km > 10) {
                $weight *= 0.2;
                $flags[] = 'gps_tres_loin';
            } elseif ($km > 3) {
                $weight *= 0.6;
                $flags[] = 'gps_hors_quartier';
            }
        }

        $last24h = OutageReport::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        if ($last24h >= 5) {
            $weight *= 0.3;
            $flags[] = 'flood_24h';
        }

        if (! $user->hasVerifiedEmail()) {
            $weight *= 0.5;
            $flags[] = 'email_non_verifie';
        }

        if ($user->created_at && $user->created_at->gt(now()->subHour())) {
            $weight *= 0.7;
            $flags[] = 'compte_tres_recent';
        }

        $total = OutageReport::where('user_id', $user->id)->count();
        if ($total >= 3) {
            $good = OutageReport::where('user_id', $user->id)
                ->whereHas('outage', fn ($q) => $q->whereNotNull('confirmed_at'))
                ->count();
            $ratio = $good / $total;

            if ($ratio < 0.2) {
                $weight *= 0.5;
                $flags[] = 'reputation_faible';
            } elseif ($ratio > 0.7) {
                $weight = min(1.2, $weight * 1.2);
                $flags[] = 'reputation_forte';
            }
        }

        return ['weight' => round(max(0.05, $weight), 2), 'flags' => $flags];
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
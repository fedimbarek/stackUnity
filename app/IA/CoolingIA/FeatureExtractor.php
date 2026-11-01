<?php

namespace App\IA\CoolingIA;

use App\Models\CoolingPoint;

class FeatureExtractor
{
    public const FEATURES = [
        'distance_score',
        'heat_level',
        'type_heat_fit',
        'hour_open',
        'has_address',
        'has_hours',
        'source_manual',
        'heat_fit_interaction',
    ];

    public function extract(CoolingPoint $point, array $context): array
    {
        $userLat = (float) ($context['lat'] ?? $point->latitude);
        $userLng = (float) ($context['lng'] ?? $point->longitude);
        $radiusM = (float) ($context['radius'] ?? 2000);
        $tempC   = (float) ($context['temperature'] ?? 30);

        $now = $context['now'] ?? now();
        if (! $now instanceof \DateTimeInterface) {
            $now = now();
        }

        $distanceM = $this->haversine(
            $userLat, $userLng,
            (float) $point->latitude, (float) $point->longitude
        );

        $distanceScore = $radiusM > 0
            ? max(0.0, min(1.0, 1.0 - $distanceM / $radiusM))
            : 0.0;

        $heatLevel   = max(0.0, min(1.0, $tempC / 45.0));
        $typeSlug    = $point->coolingPointType?->slug ?? $point->type ?? 'park';
        $typeHeatFit = $this->typeHeatFit($typeSlug, $heatLevel);
        $isOpen      = $this->isOpenNow($point->opening_hours, $now);

        return [
            'distance_score'       => $distanceScore,
            'heat_level'           => $heatLevel,
            'type_heat_fit'        => $typeHeatFit,
            'hour_open'            => $isOpen ? 1.0 : 0.0,
            'has_address'          => $point->address ? 1.0 : 0.0,
            'has_hours'            => $point->opening_hours ? 1.0 : 0.0,
            'source_manual'        => $point->source === 'manual' ? 1.0 : 0.0,
            'heat_fit_interaction' => $heatLevel * $typeHeatFit,
        ];
    }

    protected function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected function typeHeatFit(string $slug, float $heatLevel): float
    {
        $coolTypes = ['fontaine', 'climatise', 'biblio'];
        if ($heatLevel >= 0.6) {
            return in_array($slug, $coolTypes, true) ? 1.0 : 0.5;
        }
        return $slug === 'parc' ? 1.0 : 0.6;
    }

    /** ✅ Signature corrigée — plus de warning P1132 */
    protected function isOpenNow(?string $hours, \DateTimeInterface $now): bool
    {
        if (! $hours) {
            return true;
        }
        if (stripos($hours, '24/7') !== false) {
            return true;
        }
        if (! preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $hours, $m)) {
            return true;
        }

        $start = (int) $m[1] * 60 + (int) $m[2];
        $end   = (int) $m[3] * 60 + (int) $m[4];
        $cur   = (int) $now->format('H') * 60 + (int) $now->format('i');

        return $end < $start
            ? ($cur >= $start || $cur <= $end)
            : ($cur >= $start && $cur <= $end);
    }
}
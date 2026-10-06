<?php

namespace App\Services;

use App\Models\PowerOutage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    public function kpis(?int $neighborhoodId, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $cacheKey = "kpis:{$neighborhoodId}:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($neighborhoodId, $from, $to) {
            $query = PowerOutage::whereBetween('started_at', [$from, $to])
                ->when($neighborhoodId, fn ($q) => $q->where('neighborhood_id', $neighborhoodId));

            $total = (clone $query)->count();
            $confirmed = (clone $query)->whereIn('status', ['confirmed', 'resolved'])->count();
            $resolved = (clone $query)->where('status', 'resolved')->count();

            $avgDurationMinutes = (clone $query)
                ->whereNotNull('resolved_at')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, started_at, resolved_at)) as avg_minutes'))
                ->value('avg_minutes');

            return [
                'total_outages' => $total,
                'confirmed_outages' => $confirmed,
                'resolved_outages' => $resolved,
                'confirmation_rate' => $total > 0 ? round(($confirmed / $total) * 100, 1) : 0,
                'avg_resolution_minutes' => $avgDurationMinutes ? round($avgDurationMinutes) : null,
            ];
        });
    }

    /** Variation en % par rapport à la période précédente de même durée. */
    public function compareWithPreviousPeriod(?int $neighborhoodId, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $days = $from->diffInDays($to) + 1;
        $previousFrom = (clone $from)->subDays($days);
        $previousTo = (clone $from)->subSecond();

        $current = $this->kpis($neighborhoodId, $from, $to);
        $previous = $this->kpis($neighborhoodId, $previousFrom, $previousTo);

        $variation = $previous['total_outages'] > 0
            ? round((($current['total_outages'] - $previous['total_outages']) / $previous['total_outages']) * 100, 1)
            : ($current['total_outages'] > 0 ? 100 : 0);

        return [
            'current' => $current,
            'previous' => $previous,
            'variation_percent' => $variation,
        ];
    }

    public function byNeighborhood(Carbon $from, Carbon $to)
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        return PowerOutage::whereBetween('started_at', [$from, $to])
            ->join('neighborhoods', 'neighborhoods.id', '=', 'power_outages.neighborhood_id')
            ->groupBy('neighborhoods.id', 'neighborhoods.name')
            ->select('neighborhoods.name as neighborhood', DB::raw('COUNT(*) as total'))
            ->orderByDesc('total')
            ->get();
    }
}
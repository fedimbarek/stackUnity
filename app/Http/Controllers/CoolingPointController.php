<?php

namespace App\Http\Controllers;

use App\IA\CoolingIA\CoolingRecommender;
use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use App\Services\CoolingPointService;
use Illuminate\Http\Request;

class CoolingPointController extends Controller
{
    public function index()
    {
        $points = CoolingPoint::all();
        return view('cooling-points.index', compact('points'));
    }

    public function fetch(Request $request, CoolingPointService $service)
    {
        $data = $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'nullable|integer|min:100|max:10000',
        ]);

        $lat    = (float) $data['lat'];
        $lng    = (float) $data['lng'];
        $radius = (int) ($data['radius'] ?? 2000);

        $dLat = $radius / 111320;
        $dLng = $radius / (111320 * cos(deg2rad($lat)));

        $rawPoints = CoolingPoint::with('coolingPointType')
            ->whereBetween('latitude',  [$lat - $dLat, $lat + $dLat])
            ->whereBetween('longitude', [$lng - $dLng, $lng + $dLng])
            ->limit(500)
            ->get();

        $recommender = new CoolingRecommender();
        $aiRank = [];

        if ($recommender->isReady()) {
            $temperature = 30.0;
            try {
                $forecast = \App\Models\WeatherForecast::query()->latest('id')->first();
                if ($forecast) {
                    $t = $forecast->temperature
                      ?? $forecast->temperature_max
                      ?? $forecast->temp
                      ?? null;
                    if (is_numeric($t)) {
                        $temperature = (float) $t;
                    }
                }
            } catch (\Throwable $e) { /* garde 30 °C */ }

            $context = [
                'lat'         => $lat,
                'lng'         => $lng,
                'radius'      => $radius,
                'temperature' => $temperature,
                'now'         => now(),
            ];

            $rawPoints = $recommender->rank($rawPoints, $context);
            $aiRank    = $rawPoints->take(3)->pluck('id')->values()->all();
        }

        $points = $rawPoints->map(function ($p, $i) {
            $type = $p->coolingPointType;

            return [
                'id'            => $p->id,
                'name'          => $p->name,
                'type'          => $type->slug   ?? ($p->type ?? 'other'),
                'type_name'     => $type->name   ?? 'Autre',
                'type_icon'     => $type->icon   ?? 'fa-map-marker-alt',
                'type_color'    => $type->color  ?? '#858796',
                'latitude'      => (float) $p->latitude,
                'longitude'     => (float) $p->longitude,
                'address'       => $p->address,
                'opening_hours' => $p->opening_hours,
                'ai_score'      => $p->ai_score ?? null,
                'ai_rank'       => $i + 1,
            ];
        })->values();

        $types = CoolingPointType::orderBy('name')
            ->get(['id', 'name', 'slug', 'icon', 'color'])
            ->map(fn ($t) => [
                'id'    => $t->id,
                'name'  => $t->name,
                'slug'  => $t->slug,
                'icon'  => $t->icon,
                'color' => $t->color,
            ]);

        return response()->json([
            'success'        => true,
            'count'          => $points->count(),
            'points'         => $points,
            'types'          => $types,
            'ai_recommended' => $aiRank,
        ]);
    }
}
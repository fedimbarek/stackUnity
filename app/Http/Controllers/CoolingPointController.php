<?php

namespace App\Http\Controllers;

use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use App\Services\CoolingPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        // ✅ OSM : on ne rafraîchit qu'UNE fois toutes les 30 min par zone
        //    (clé basée sur la position arrondie à ~1 km → même cellule)
        $cacheKey = 'osm_refresh_' . round($lat, 2) . '_' . round($lng, 2) . '_' . $radius;

        if (! Cache::has($cacheKey)) {
            try {
                $service->fetchNearby($lat, $lng, $radius);
                Cache::put($cacheKey, true, now()->addMinutes(30));
            } catch (\Throwable $e) {
                report($e);
                // Même en cas d'échec OSM, on met un petit cache pour éviter le spam
                Cache::put($cacheKey, true, now()->addMinutes(5));
            }
        }

        // Lecture en base dans un carré autour de l'utilisateur
        $dLat = $radius / 111320;
        $dLng = $radius / (111320 * cos(deg2rad($lat)));

        $points = CoolingPoint::with('coolingPointType')
            ->whereBetween('latitude',  [$lat - $dLat, $lat + $dLat])
            ->whereBetween('longitude', [$lng - $dLng, $lng + $dLng])
            ->limit(500)              // ✅ sécurité perf
            ->get()
            ->map(function ($p) {
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
                ];
            })
            ->values();

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
            'success' => true,
            'count'   => $points->count(),
            'points'  => $points,
            'types'   => $types,
        ]);
    }
}

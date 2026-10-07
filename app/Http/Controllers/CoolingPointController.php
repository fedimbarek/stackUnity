<?php

namespace App\Http\Controllers;

use App\Models\CoolingPoint;
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

        // 1) Rafraîchir depuis OSM (si Overpass est en panne, on continue avec la base)
        try {
            $service->fetchNearby($lat, $lng, $radius);
        } catch (\Throwable $e) {
            report($e);
        }

        // 2) Lire en base dans un carré autour de l'utilisateur
        $dLat = $radius / 111320;
        $dLng = $radius / (111320 * cos(deg2rad($lat)));

        // Slug de la table cooling_point_types -> type compris par le JavaScript
        $typeMap = [
            'parc'      => 'park',
            'fontaine'  => 'fountain',
            'climatise' => 'mall',
            'biblio'    => 'mall',
        ];

        $points = CoolingPoint::with('coolingPointType')
            ->whereBetween('latitude',  [$lat - $dLat, $lat + $dLat])
            ->whereBetween('longitude', [$lng - $dLng, $lng + $dLng])
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'type'          => $typeMap[$p->coolingPointType->slug ?? ''] ?? $p->type,
                'latitude'      => (float) $p->latitude,
                'longitude'     => (float) $p->longitude,
                'address'       => $p->address,
                'opening_hours' => $p->opening_hours,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'count'   => $points->count(),
            'points'  => $points,
        ]);
    }
}

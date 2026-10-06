<?php

namespace App\Http\Controllers;

use App\Services\CoolingPointService;
use App\Models\CoolingPoint;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\CoolingPointTypeController;
use App\Http\Controllers\Admin\CoolingPointController as AdminCoolingPointController;
class CoolingPointController extends Controller
{
    public function index()
    {
        $points = CoolingPoint::all();
        return view('cooling-points.index', compact('points'));
    }

    public function fetch(Request $request, CoolingPointService $service)
    {
        $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'nullable|integer|min:100|max:10000',
        ]);

        $points = $service->fetchNearby(
            $request->lat,
            $request->lng,
            $request->radius ?? 2000
        );

        return response()->json($points);
    }
}

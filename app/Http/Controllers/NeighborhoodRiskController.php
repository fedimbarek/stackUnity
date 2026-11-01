<?php

namespace App\Http\Controllers;

use App\Services\OutageForecastService;
use Illuminate\Http\Request;

class NeighborhoodRiskController extends Controller
{
    public function index(Request $request, OutageForecastService $forecast)
    {
        $ranking = $forecast->rankNeighborhoods();

        if ($request->wantsJson()) {
            return response()->json(collect($ranking)->map(fn ($r) => [
                'neighborhood' => $r['neighborhood']->name,
                'score' => $r['score'],
                'level' => $r['level'],
                'factors' => $r['factors'],
            ]));
        }

        return view('ai.risk', compact('ranking'));
    }
}
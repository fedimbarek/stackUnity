<?php

namespace App\Http\Controllers;

use App\Models\WeatherAlert;
use App\Models\WeatherForecast;
use App\Services\AdviceService;
use App\Services\WeatherService;

class WeatherController extends Controller
{
    private function upcoming()
    {
        $neighborhoodId = auth()->user()->neighborhood_id;

        return WeatherForecast::with(['neighborhood', 'outageRisks'])
            ->when($neighborhoodId, fn ($q) => $q->where('neighborhood_id', $neighborhoodId))
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->get();
    }

    public function index()
    {
        $neighborhoodId = auth()->user()->neighborhood_id;

        $alerts = WeatherAlert::with('neighborhood')
            ->when($neighborhoodId, fn ($q) => $q->where('neighborhood_id', $neighborhoodId))
            ->where('created_at', '>=', now()->subDays(3))
            ->latest()
            ->take(5)
            ->get();

        return view('weather.index', [
            'forecasts' => $this->upcoming(),
            'alerts' => $alerts,
        ]);
    }

    public function refresh(WeatherService $service)
    {
        try {
            $service->refreshAll();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->with('status', "Impossible de joindre l'API météo, réessaie plus tard.");
        }

        return back()->with('status', 'Météo mise à jour.');
    }

    public function advice(AdviceService $advice)
    {
        return back()->with('advice', $advice->generate($this->upcoming()));
    }
}
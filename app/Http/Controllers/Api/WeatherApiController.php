<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Neighborhood;
use App\Models\WeatherForecast;
use App\Services\CurrentWeatherService;
use Illuminate\Http\Request;

class WeatherApiController extends Controller
{
    /** Quartier demandé (neighborhood_id), sinon celui de l'utilisateur. */
    private function neighborhood(Request $request): Neighborhood
    {
        $request->validate([
            'neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
        ]);

        $id = $request->input('neighborhood_id', $request->user()->neighborhood_id);

        abort_if(! $id, 422, "neighborhood_id est requis : aucun quartier n'est associé à ce compte.");

        return Neighborhood::findOrFail($id);
    }

    private function place(Neighborhood $n): array
    {
        return ['id' => $n->id, 'name' => $n->name, 'city' => $n->city];
    }

    // GET /api/weather/current?neighborhood_id=
    public function current(Request $request, CurrentWeatherService $service)
    {
        $n = $this->neighborhood($request);

        try {
            $weather = $service->get($n);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => "Le service météo est indisponible pour le moment."], 503);
        }

        return response()->json(['data' => ['neighborhood' => $this->place($n)] + $weather]);
    }

    // GET /api/weather/forecast?neighborhood_id=
    public function forecast(Request $request)
    {
        $n = $this->neighborhood($request);

        $forecasts = WeatherForecast::with('outageRisks')
            ->where('neighborhood_id', $n->id)
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->get()
            ->map(fn ($f) => [
                'date' => $f->date->toDateString(),
                'temp_max' => $f->temp_max,
                'temp_min' => $f->temp_min,
                'level' => $f->level,
                'outage_risks' => $f->outageRisks->map(fn ($r) => [
                    'risk_level' => $r->risk_level,
                    'description' => $r->description,
                ])->values(),
            ])
            ->values();

        return response()->json(['data' => [
            'neighborhood' => $this->place($n),
            'forecasts' => $forecasts,
        ]]);
    }
}
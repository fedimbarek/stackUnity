<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutageRiskRequest;
use App\Models\OutageRisk;
use App\Models\WeatherForecast;

class OutageRiskController extends Controller
{
    public function index()
    {
        $risks = OutageRisk::with('forecast.neighborhood')->latest()->paginate(15);

        return view('admin.weather.risks.index', compact('risks'));
    }

    public function create()
    {
        return view('admin.weather.risks.create', [
            'risk' => new OutageRisk(),
            'forecasts' => WeatherForecast::with('neighborhood')->orderByDesc('date')->get(),
        ]);
    }

    public function store(OutageRiskRequest $request)
    {
        OutageRisk::create($request->validated());

        return redirect()->route('admin.weather.risks.index')
            ->with('status', 'Risque ajouté.');
    }

    public function edit(OutageRisk $risk)
    {
        return view('admin.weather.risks.edit', [
            'risk' => $risk,
            'forecasts' => WeatherForecast::with('neighborhood')->orderByDesc('date')->get(),
        ]);
    }

    public function update(OutageRiskRequest $request, OutageRisk $risk)
    {
        $risk->update($request->validated());

        return redirect()->route('admin.weather.risks.index')
            ->with('status', 'Risque modifié.');
    }

    public function destroy(OutageRisk $risk)
    {
        $risk->delete();

        return redirect()->route('admin.weather.risks.index')
            ->with('status', 'Risque supprimé.');
    }
}
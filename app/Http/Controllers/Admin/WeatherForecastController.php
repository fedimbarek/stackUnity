<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WeatherForecastRequest;
use App\Models\Neighborhood;
use App\Models\WeatherForecast;

class WeatherForecastController extends Controller
{
    public function index()
    {
        $forecasts = WeatherForecast::with('neighborhood')
            ->withCount('outageRisks')
            ->orderByDesc('date')
            ->paginate(15);

        return view('admin.weather.forecasts.index', compact('forecasts'));
    }

    public function show(WeatherForecast $forecast)
    {
        $forecast->load(['neighborhood', 'outageRisks']);

        return view('admin.weather.forecasts.show', compact('forecast'));
    }

    public function create()
    {
        return view('admin.weather.forecasts.create', [
            'forecast' => new WeatherForecast(),
            'neighborhoods' => Neighborhood::orderBy('name')->get(),
        ]);
    }

    public function store(WeatherForecastRequest $request)
    {
        WeatherForecast::create($request->validated());

        return redirect()->route('admin.weather.forecasts.index')->with('status', 'Prévision ajoutée.');
    }

    public function edit(WeatherForecast $forecast)
    {
        return view('admin.weather.forecasts.edit', [
            'forecast' => $forecast,
            'neighborhoods' => Neighborhood::orderBy('name')->get(),
        ]);
    }

    public function update(WeatherForecastRequest $request, WeatherForecast $forecast)
    {
        $forecast->update($request->validated());

        return redirect()->route('admin.weather.forecasts.show', $forecast)->with('status', 'Prévision modifiée.');
    }

    public function destroy(WeatherForecast $forecast)
    {
        $forecast->delete();

        return redirect()->route('admin.weather.forecasts.index')->with('status', 'Prévision supprimée.');
    }
}
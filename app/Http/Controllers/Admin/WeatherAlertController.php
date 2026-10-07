<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WeatherAlertRequest;
use App\Jobs\SendNotificationJob;
use App\Models\Neighborhood;
use App\Models\WeatherAlert;

class WeatherAlertController extends Controller
{
    public function index()
    {
        $alerts = WeatherAlert::with(['neighborhood', 'creator'])->latest()->paginate(15);

        return view('admin.weather.alerts.index', compact('alerts'));
    }

    public function show(WeatherAlert $alert)
    {
        $alert->load(['neighborhood', 'forecast', 'creator']);

        return view('admin.weather.alerts.show', compact('alert'));
    }

    public function create()
    {
        return view('admin.weather.alerts.create', [
            'alert' => new WeatherAlert(),
            'neighborhoods' => Neighborhood::orderBy('name')->get(),
        ]);
    }

    public function store(WeatherAlertRequest $request)
    {
        $alert = WeatherAlert::create($request->validated() + [
            'source' => 'manual',
            'created_by' => auth()->id(),
        ]);

        SendNotificationJob::dispatch($alert);

        return redirect()->route('admin.weather.alerts.index')
            ->with('status', 'Alerte déclenchée : les habitants du quartier vont être notifiés.');
    }

    public function edit(WeatherAlert $alert)
    {
        return view('admin.weather.alerts.edit', [
            'alert' => $alert,
            'neighborhoods' => Neighborhood::orderBy('name')->get(),
        ]);
    }

    public function update(WeatherAlertRequest $request, WeatherAlert $alert)
    {
        $data = $request->validated();

        // Une alerte automatique est liée à une prévision : son quartier ne change pas.
        if ($alert->source === 'auto') {
            unset($data['neighborhood_id']);
        }

        $alert->update($data);

        return redirect()->route('admin.weather.alerts.show', $alert)
            ->with('status', "Alerte modifiée. Aucune nouvelle notification n'est envoyée.");
    }

    public function destroy(WeatherAlert $alert)
    {
        $alert->delete();

        return redirect()->route('admin.weather.alerts.index')->with('status', 'Alerte supprimée.');
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AlertThresholdRequest;
use App\Models\AlertThreshold;
use App\Services\WeatherService;

class AlertThresholdController extends Controller
{
    public function edit(WeatherService $service)
    {
        return view('admin.weather.thresholds.edit', ['thresholds' => $service->thresholds()]);
    }

    public function update(AlertThresholdRequest $request)
    {
        $data = $request->validated();

        foreach (['warning', 'canicule'] as $level) {
            AlertThreshold::updateOrCreate(['level' => $level], ['temp_max' => $data[$level]]);
        }

        return redirect()->route('admin.weather.thresholds.edit')
            ->with('status', "Seuils enregistrés. Clique sur « Actualiser la météo » (page Prévisions canicule) pour recalculer les niveaux.");
    }
}
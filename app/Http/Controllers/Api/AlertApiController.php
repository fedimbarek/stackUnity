<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AlertThresholdRequest;
use App\Http\Requests\WeatherAlertRequest;
use App\Jobs\SendNotificationJob;
use App\Models\AlertThreshold;
use App\Models\WeatherAlert;
use App\Services\WeatherService;
use Illuminate\Http\Request;

class AlertApiController extends Controller
{
    private function format(WeatherAlert $a): array
    {
        return [
            'id' => $a->id,
            'neighborhood' => $a->neighborhood->name,
            'level' => $a->level,
            'source' => $a->source,
            'message' => $a->message,
            'created_at' => $a->created_at->toIso8601String(),
        ];
    }

    // GET /api/alerts
    public function index(Request $request)
    {
        $request->validate([
            'neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
        ]);

        $user = $request->user();
        $query = WeatherAlert::with('neighborhood')->latest();

        if ($user->hasAnyRole(['admin', 'gestionnaire'])) {
            // Admin et gestionnaire voient tout, avec un filtre optionnel par quartier
            $query->when($request->input('neighborhood_id'), fn ($q, $id) => $q->where('neighborhood_id', $id));
        } else {
            $query->where('neighborhood_id', $user->neighborhood_id);
        }

        return response()->json($query->paginate(15)->through(fn ($a) => $this->format($a)));
    }

    // POST /api/alerts (admin)
    public function store(WeatherAlertRequest $request)
    {
        $alert = WeatherAlert::create($request->validated() + [
            'source' => 'manual',
            'created_by' => $request->user()->id,
        ]);

        SendNotificationJob::dispatch($alert);

        return response()->json([
            'message' => 'Alerte déclenchée : les habitants du quartier vont être notifiés.',
            'data' => $this->format($alert->load('neighborhood')),
        ], 201);
    }

    // PUT /api/alerts/thresholds (admin)
    public function thresholds(AlertThresholdRequest $request, WeatherService $service)
    {
        $data = $request->validated();

        foreach (['warning', 'canicule'] as $level) {
            AlertThreshold::updateOrCreate(['level' => $level], ['temp_max' => $data[$level]]);
        }

        return response()->json([
            'message' => 'Seuils enregistrés.',
            'data' => $service->thresholds(),
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\WeatherAlert;
use App\Models\WeatherForecast;
use App\Services\AdviceService;
use App\Services\WeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    /**
     * Prévisions à venir, filtrées sur le quartier de l'utilisateur
     * (toutes les prévisions si l'utilisateur n'a pas de quartier).
     */
    private function upcoming()
    {
        $neighborhoodId = auth()->user()->neighborhood_id;

        return WeatherForecast::with(['neighborhood', 'outageRisks'])
            ->when($neighborhoodId, fn ($q) => $q->where('neighborhood_id', $neighborhoodId))
            ->whereDate('date', '>=', today())
            ->orderBy('date')
            ->get();
    }

    /**
     * Page « Prévisions canicule ».
     */
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
            'alerts'    => $alerts,
        ]);
    }

    /**
     * Bouton « Actualiser la météo » (POST, admin + gestionnaire).
     */
    public function refresh(WeatherService $service)
    {
        try {
            $service->refreshAll();
        } catch (ConnectionException $e) {
            Log::warning('Météo : API injoignable', ['message' => $e->getMessage()]);

            return back()->with('status', "Impossible de joindre l'API météo, réessaie plus tard.");
        } catch (\Throwable $e) {
            Log::error('Météo : échec de la mise à jour', ['message' => $e->getMessage()]);

            return back()->with('status', "Erreur lors de la mise à jour de la météo.");
        }

        return back()->with('status', 'Météo mise à jour.');
    }

    /**
     * Bouton « Conseils IA » (POST, utilisateurs connectés).
     */
    public function advice(AdviceService $advice)
    {
        $forecasts = $this->upcoming();

        if ($forecasts->isEmpty()) {
            return back()->with('status', 'Aucune prévision à venir : impossible de générer des conseils.');
        }

        try {
            $text = $advice->generate($forecasts);
        } catch (ConnectionException $e) {
            Log::warning('Conseils IA : service injoignable', ['message' => $e->getMessage()]);

            return back()->with('status', "Impossible de joindre le service de conseils, réessaie plus tard.");
        } catch (\Throwable $e) {
            Log::error('Conseils IA : échec de la génération', ['message' => $e->getMessage()]);

            return back()->with('status', "Erreur lors de la génération des conseils.");
        }

        return back()->with('advice', $text);
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class AdviceService
{
    public function generate(Collection $forecasts): string
    {
        if ($forecasts->isEmpty()) {
            return "Aucune prévision disponible pour générer des conseils.";
        }

        $key = config('services.gemini.key');

        if (!$key) {
            return $this->fallback($forecasts);
        }

        $place = $forecasts->first()->neighborhood->name;

        $lines = $forecasts->map(fn ($f) =>
            "{$f->date->format('d/m')} : max {$f->temp_max}°C, min {$f->temp_min}°C, niveau {$f->level}"
        )->implode("\n");

        $prompt = "Tu es l'assistant de l'application HeatAlert, qui aide les habitants à anticiper canicules "
            . "et coupures de courant. Prévisions des prochains jours pour le quartier {$place} :\n{$lines}\n\n"
            . "Donne 5 conseils courts et concrets en français : hydratation, économie d'énergie, "
            . "protection des équipements sensibles à la chaleur ou aux coupures, personnes fragiles, "
            . "préparation à une coupure. Adapte-les aux températures ci-dessus. "
            . "Format : liste numérotée, sans introduction ni conclusion.";

        try {
            $model = config('services.gemini.model');

            $text = Http::withHeaders(['x-goog-api-key' => $key])
                ->timeout(30)
                ->connectTimeout(20)
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                ])
                ->throw()
                ->json('candidates.0.content.parts.0.text');

            return $text ?: $this->fallback($forecasts);
        } catch (\Throwable $e) {
            report($e);
            return $this->fallback($forecasts);
        }
    }

    private function fallback(Collection $forecasts): string
    {
        $max = $forecasts->max('temp_max');
        $canicule = $forecasts->contains('level', 'canicule');

        $advice = [
            "Buvez de l'eau régulièrement, même sans soif, et évitez l'alcool et les boissons très sucrées.",
            "Fermez volets et rideaux le jour, aérez la nuit quand il fait plus frais.",
            "Limitez l'usage des appareils énergivores entre 12h et 17h pour réduire la charge du réseau.",
            "Ne laissez jamais un enfant, une personne âgée ou un animal dans un véhicule fermé.",
        ];

        if ($max >= config('services.weather.heat_warning')) {
            $advice[] = "Préparez-vous à une coupure : chargez téléphones et batteries, gardez une lampe et de l'eau à portée de main.";
            $advice[] = "Débranchez les appareils sensibles pendant les pics de chaleur pour éviter les surtensions au retour du courant.";
        }

        if ($canicule) {
            $advice[] = "Repérez les points de fraîcheur proches (parcs, salles climatisées) et rendez visite aux voisins fragiles.";
        }

        $lines = [];
        foreach ($advice as $i => $text) {
            $lines[] = ($i + 1) . '. ' . $text;
        }

        return "Conseils de prévention (température maximale prévue : {$max}°C) :\n" . implode("\n", $lines);
    }
}
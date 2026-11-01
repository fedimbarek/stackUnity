<?php

namespace App\Services;

use App\Models\Report;

class ReportInsightService
{
    public function __construct(
        private GeminiClient $gemini,
        private StatisticsService $stats,
    ) {}

    public function generate(Report $report): string
    {
        $data = $this->stats->compareWithPreviousPeriod(
            $report->neighborhood_id,
            $report->period_start,
            $report->period_end
        );

        $top = $this->stats->byNeighborhood($report->period_start, $report->period_end)->take(3);
        $scope = $report->neighborhood?->name ?? 'tous les quartiers';

        $cur = $data['current'];
        $prev = $data['previous'];
        $topLines = $top->map(fn ($r) => "- {$r->neighborhood} : {$r->total} coupures")->implode("\n") ?: '- aucune donnée';

        $facts = "Périmètre : {$scope}\n"
            ."Période : {$report->period_start->format('d/m/Y')} au {$report->period_end->format('d/m/Y')}\n"
            ."Coupures : {$cur['total_outages']} (période précédente : {$prev['total_outages']}, variation {$data['variation_percent']} %)\n"
            ."Confirmées : {$cur['confirmed_outages']} (taux {$cur['confirmation_rate']} %)\n"
            ."Résolues : {$cur['resolved_outages']}\n"
            .'Durée moyenne de résolution : '.($cur['avg_resolution_minutes'] ?? 'inconnue')." min\n"
            ."Quartiers les plus touchés :\n{$topLines}";

        $prompt = "Tu es analyste pour HeatAlert, application de suivi des coupures de courant liées aux canicules.\n"
            ."À partir des seuls chiffres ci-dessous, rédige en français :\n"
            ."1) Un résumé exécutif de 3 phrases maximum.\n"
            ."2) 3 recommandations opérationnelles concrètes (liste à puces).\n"
            ."N'invente aucun chiffre absent des données.\n\nDONNÉES :\n{$facts}";

        $summary = $this->gemini->generate($prompt) ?? $this->fallback($data, $scope, $top);

        $report->forceFill(['ai_summary' => $summary, 'ai_generated_at' => now()])->save();

        return $summary;
    }

    private function fallback(array $data, string $scope, $top): string
    {
        $cur = $data['current'];
        $trend = $data['variation_percent'] > 0 ? 'en hausse' : ($data['variation_percent'] < 0 ? 'en baisse' : 'stable');
        $worst = $top->first()?->neighborhood ?? 'aucun quartier';

        return "Sur la période ({$scope}), {$cur['total_outages']} coupures ont été enregistrées, soit une tendance {$trend} "
            ."({$data['variation_percent']} %) par rapport à la période précédente. Le quartier le plus touché est : {$worst}.\n\n"
            ."- Renforcer la surveillance du quartier le plus touché.\n"
            ."- Accélérer la confirmation des signalements en attente.\n"
            .'- Prévenir les habitants avant les pics de chaleur annoncés.';
    }
}
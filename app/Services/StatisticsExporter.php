<?php

namespace App\Services;

use Carbon\Carbon;

class StatisticsExporter
{
    public function __construct(private StatisticsService $stats) {}

    /**
     * Génère le CSV et retourne le nom du fichier (pas le chemin complet).
     */
    public function exportToCsv(Carbon $from, Carbon $to, string $fileName): string
    {
        $directory = storage_path('reports');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $fullPath = $directory.DIRECTORY_SEPARATOR.$fileName;

        $handle = fopen($fullPath, 'w');

        // BOM UTF-8 : pour qu'Excel affiche correctement les accents français
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['Quartier', 'Nombre de coupures']);

        foreach ($this->stats->byNeighborhood($from, $to) as $row) {
            fputcsv($handle, [$row->neighborhood, $row->total]);
        }

        fclose($handle);

        return $fileName;
    }
}
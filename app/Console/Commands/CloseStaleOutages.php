<?php

namespace App\Console\Commands;

use App\Services\OutageAggregationService;
use Illuminate\Console\Command;

class CloseStaleOutages extends Command
{
    protected $signature = 'app:close-stale-outages';
    protected $description = 'Clôture automatiquement les coupures sans nouveau signalement depuis 2h';

    public function handle(OutageAggregationService $service): int
    {
        $count = $service->closeStaleOutages();
        $this->info("{$count} coupure(s) clôturée(s) automatiquement.");
        return self::SUCCESS;
    }
}
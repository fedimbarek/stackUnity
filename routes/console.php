<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\WeeklyReportJob;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Fermeture automatique des coupures obsolètes
Schedule::command('app:close-stale-outages')->everyThirtyMinutes();


// Rapport hebdomadaire
// Schedule::job(new WeeklyReportJob)->weeklyOn(1, '06:00'); // chaque lundi à 6h
//test
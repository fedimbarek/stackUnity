<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\WeeklyReportJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('app:close-stale-outages')->everyThirtyMinutes();

// Schedule::job(new WeeklyReportJob)->weeklyOn(1, '06:00'); // chaque lundi à 6h
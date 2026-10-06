<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Models\Neighborhood;
use App\Models\Report;
use App\Models\User;
use App\Observers\AuditObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Paginator::useBootstrapFour();
         User::observe(AuditObserver::class);
        Neighborhood::observe(AuditObserver::class);
        Report::observe(AuditObserver::class);
    }
}

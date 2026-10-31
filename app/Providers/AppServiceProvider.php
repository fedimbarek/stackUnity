<?php

namespace App\Providers;

use App\Models\Neighborhood;
use App\Models\Report;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        // Évite l'erreur « clé trop longue » (index sur colonnes string en utf8mb4)
        Schema::defaultStringLength(191);

        // Pagination au style Bootstrap 4 (SB Admin 2)
        Paginator::useBootstrapFour();

        // Assure la présence des rôles par défaut pour le guard web.
        if (Schema::hasTable('roles')) {
            foreach (['resident', 'gestionnaire', 'admin'] as $roleName) {
                Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Journal d'audit
        User::observe(AuditObserver::class);
        Neighborhood::observe(AuditObserver::class);
        Report::observe(AuditObserver::class);
    }
}
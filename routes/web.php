<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/

// Admin
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OutageRiskController;
use App\Http\Controllers\Admin\WeatherForecastController;
use App\Http\Controllers\Admin\AlertThresholdController;
use App\Http\Controllers\Admin\WeatherAlertController;
use App\Http\Controllers\Admin\BroadcastController;

// Général
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\FrontOfficeController;
use App\Http\Controllers\NeighborhoodController;
use App\Http\Controllers\OutageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\NotificationPreferenceController;

// Équipements
use App\Http\Controllers\EquipementC\Equipementc;


/*
|--------------------------------------------------------------------------
| FRONT OFFICE (public)
|--------------------------------------------------------------------------
*/

Route::get('/', [FrontOfficeController::class, 'home'])
    ->name('front.home');

Route::get('/carte', [FrontOfficeController::class, 'map'])
    ->name('front.map');

Route::get('/carte/data', [OutageController::class, 'map'])
    ->name('front.map.data');


/*
|--------------------------------------------------------------------------
| DASHBOARD RÉSIDENT
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    return view('dashboard');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| PROFIL
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

});


/*
|--------------------------------------------------------------------------
| COUPURES
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Liste
    Route::get('/outages', [OutageController::class, 'index'])
        ->name('outages.index');

    // Création
    Route::post('/outages', [OutageController::class, 'store'])
        ->name('outages.store');

    // Carte (doit rester AVANT /outages/{outage})
    Route::get('/outages/map', [OutageController::class, 'map'])
        ->name('outages.map');

    // Gestion : admin + gestionnaire
    Route::middleware('role:admin|gestionnaire')->group(function () {

        Route::get('/outages/{outage}/edit', [OutageController::class, 'edit'])
            ->name('outages.edit');

        Route::put('/outages/{outage}', [OutageController::class, 'update'])
            ->name('outages.update');

        Route::delete('/outages/{outage}', [OutageController::class, 'destroy'])
            ->name('outages.destroy');

        Route::put('/outages/{outage}/confirm', [OutageController::class, 'confirm'])
            ->name('outages.confirm');

        Route::put('/outages/{outage}/resolve', [OutageController::class, 'resolve'])
            ->name('outages.resolve');

    });

    // Détails (en dernier : /outages/{outage} capture tout le reste)
    Route::get('/outages/{outage}', [OutageController::class, 'show'])
        ->name('outages.show');

});


/*
|--------------------------------------------------------------------------
| QUARTIERS (admin + gestionnaire)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {

    Route::resource('neighborhoods', NeighborhoodController::class);

});


/*
|--------------------------------------------------------------------------
| RAPPORTS (admin + gestionnaire)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {

    // Avant le resource, pour ne pas être masquées par reports/{report}
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])
        ->name('reports.download');

    Route::post('/reports/{report}/send', [ReportController::class, 'send'])
        ->name('reports.send');

    Route::resource('reports', ReportController::class);

});


/*
|--------------------------------------------------------------------------
| ADMINISTRATION (admin uniquement)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard admin
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        // Utilisateurs
        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('users.index');

        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])
            ->name('users.updateRole');

        // Journal d'audit
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

    });


/*
|--------------------------------------------------------------------------
| NOTIFICATION À UN QUARTIER (admin uniquement)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/notifications')
    ->name('admin.notifications.')
    ->group(function () {

        Route::get('/broadcast', [BroadcastController::class, 'create'])
            ->name('broadcast.create');

        Route::post('/broadcast', [BroadcastController::class, 'store'])
            ->name('broadcast.store');

    });


/*
|--------------------------------------------------------------------------
| PRÉVISIONS CANICULE
|--------------------------------------------------------------------------
*/

// Tous les utilisateurs connectés
Route::middleware('auth')->group(function () {

    // Page des prévisions
    Route::get('/weather', [WeatherController::class, 'index'])
        ->name('weather.index');

    // Bouton « Conseils IA » (formulaire POST)
    Route::post('/weather/advice', [WeatherController::class, 'advice'])
        ->name('weather.advice');

});

// Admin + gestionnaire
Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {

    // Bouton « Actualiser la météo » (formulaire POST)
    Route::post('/weather/refresh', [WeatherController::class, 'refresh'])
        ->name('weather.refresh');

});


/*
|--------------------------------------------------------------------------
| MODULE MÉTÉO - ADMIN / GESTIONNAIRE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|gestionnaire'])
    ->prefix('admin/weather')
    ->name('admin.weather.')
    ->group(function () {

        // Gestion des prévisions
        Route::resource('forecasts', WeatherForecastController::class);

        // Risques de coupure
        Route::resource('risks', OutageRiskController::class);

        // Alertes météo (index, create, store, show, edit, update, destroy)
        Route::resource('alerts', WeatherAlertController::class);

        // Seuils d'alerte : admin uniquement
        Route::middleware('role:admin')->group(function () {

            Route::get('thresholds', [AlertThresholdController::class, 'edit'])
                ->name('thresholds.edit');

            Route::put('thresholds', [AlertThresholdController::class, 'update'])
                ->name('thresholds.update');

        });

    });


/*
|--------------------------------------------------------------------------
| ÉQUIPEMENTS (admin + gestionnaire)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|gestionnaire'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/equipements', [Equipementc::class, 'index'])
            ->name('equipements.index');

        Route::get('/equipements/create', [Equipementc::class, 'create'])
            ->name('equipements.create');

        Route::post('/equipements', [Equipementc::class, 'store'])
            ->name('equipements.store');

        Route::get('/equipements/{equipement}/edit', [Equipementc::class, 'edit'])
            ->name('equipements.edit');

        Route::put('/equipements/{equipement}', [Equipementc::class, 'update'])
            ->name('equipements.update');

        Route::delete('/equipements/{equipement}', [Equipementc::class, 'destroy'])
            ->name('equipements.destroy');

    });


/*
|--------------------------------------------------------------------------
| NOTIFICATIONS UTILISATEUR + PRÉFÉRENCES
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Liste des notifications
    Route::get('/notifications', [UserNotificationController::class, 'index'])
        ->name('notifications.index');

    // Bouton « Tout marquer comme lu »
    Route::put('/notifications/read-all', [UserNotificationController::class, 'readAll'])
        ->name('notifications.readAll');

    // Marquer UNE notification comme lue
    Route::match(['put', 'patch', 'post'], '/notifications/{id}/read', [UserNotificationController::class, 'read'])
        ->name('notifications.read');

    // Préférences
    Route::get('/notifications/preferences', [NotificationPreferenceController::class, 'edit'])
        ->name('notifications.preferences.edit');

    Route::put('/notifications/preferences', [NotificationPreferenceController::class, 'update'])
        ->name('notifications.preferences.update');

});


/*
|--------------------------------------------------------------------------
| API INTERNE (future application mobile)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')
    ->prefix('api')
    ->group(function () {

        Route::get('/outages', [OutageController::class, 'index']);

        Route::post('/outages', [OutageController::class, 'store']);

        Route::get('/outages/map', [OutageController::class, 'map']);

        Route::get('/outages/{outage}', [OutageController::class, 'show']);

        Route::middleware('role:admin|gestionnaire')->group(function () {

            Route::put('/outages/{outage}/confirm', [OutageController::class, 'confirm']);

            Route::put('/outages/{outage}/resolve', [OutageController::class, 'resolve']);

        });

    });


/*
|--------------------------------------------------------------------------
| AUTHENTIFICATION
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
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
use App\Http\Controllers\Admin\CoolingPointController as AdminCoolingPointController;
use App\Http\Controllers\Admin\CoolingPointTypeController;
use App\Http\Controllers\Admin\ContactCategoryController;
use App\Http\Controllers\Admin\EmergencyContactController as AdminContactController;

// API JSON (vérifie les namespaces, voir les explications après le fichier)
use App\Http\Controllers\Api\WeatherApiController;
use App\Http\Controllers\Api\AlertApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Middleware\ForceJsonResponse;

// Général
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\FrontOfficeController;
use App\Http\Controllers\NeighborhoodController;
use App\Http\Controllers\OutageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CoolingPointController;
use App\Http\Controllers\EmergencyContactController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\NotificationPreferenceController;

// Équipements
use App\Http\Controllers\EquipementC\Equipementc;
use App\Http\Controllers\ReservationController;


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

Route::get('/Equipements', [Equipementc::class, 'frontIndex'])
    ->name('front.equipements');

Route::post('/equipements/{equipement}/reservations', [ReservationController::class, 'store'])
    ->name('front.equipements.reservations.store');



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

        // CRUD admin des points de fraîcheur
        // (URLs : /admin/cooling-points, /admin/cooling-point-types)
        Route::resource('cooling-point-types', CoolingPointTypeController::class);
        Route::resource('cooling-points', AdminCoolingPointController::class);

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
| POINTS DE FRAÎCHEUR (utilisateurs connectés)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/cooling-points', [CoolingPointController::class, 'index'])
        ->name('cooling-points.index');

    Route::post('/cooling-points/fetch', [CoolingPointController::class, 'fetch'])
        ->name('cooling-points.fetch');

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

        Route::get('/equipements/{equipement}/reservations', [ReservationController::class, 'index'])
            ->name('equipements.reservations.index');

        Route::post('/equipements/{equipement}/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])
            ->name('equipements.reservations.confirm');

        Route::delete('/equipements/{equipement}/reservations/{reservation}', [ReservationController::class, 'destroy'])
            ->name('equipements.reservations.destroy');

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
| API JSON : météo, alertes, notifications
|--------------------------------------------------------------------------
*/

Route::middleware([ForceJsonResponse::class, 'auth'])
    ->prefix('api')
    ->name('api.')
    ->group(function () {

        Route::get('/weather/current', [WeatherApiController::class, 'current'])
            ->name('weather.current');

        Route::get('/weather/forecast', [WeatherApiController::class, 'forecast'])
            ->name('weather.forecast');

        Route::get('/alerts', [AlertApiController::class, 'index'])
            ->name('alerts.index');

        Route::get('/notifications', [NotificationApiController::class, 'index'])
            ->name('notifications.index');

        Route::get('/notifications/preferences', [NotificationApiController::class, 'preferences'])
            ->name('notifications.preferences');

        Route::put('/notifications/preferences', [NotificationApiController::class, 'updatePreferences'])
            ->name('notifications.preferences.update');

        Route::put('/notifications/{id}/read', [NotificationApiController::class, 'read'])
            ->name('notifications.read');

        Route::middleware('role:admin')->group(function () {

            Route::post('/alerts', [AlertApiController::class, 'store'])
                ->name('alerts.store');

            Route::put('/alerts/thresholds', [AlertApiController::class, 'thresholds'])
                ->name('alerts.thresholds');

            Route::post('/notifications/broadcast', [NotificationApiController::class, 'broadcast'])
                ->name('notifications.broadcast');

        });

    });


/*
|--------------------------------------------------------------------------
| API INTERNE : coupures (future application mobile)
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
| CONTACTS D'URGENCE
|--------------------------------------------------------------------------
*/

// Public
Route::get('/contacts', [EmergencyContactController::class, 'index'])
    ->name('contacts.index');

Route::get('/contacts/{contact}', [EmergencyContactController::class, 'show'])
    ->whereNumber('contact')
    ->name('contacts.show');

// Back office (admin + gestionnaire)
Route::middleware(['auth', 'role:admin|gestionnaire'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::resource('contacts', AdminContactController::class)->except('show');
        Route::resource('contact-categories', ContactCategoryController::class)->except('show');

    });


/*
|--------------------------------------------------------------------------
| AUTHENTIFICATION
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
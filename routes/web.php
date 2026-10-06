<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\FrontOfficeController;
use App\Http\Controllers\NeighborhoodController;
use App\Http\Controllers\OutageController;
use App\Http\Controllers\OutageUpdateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CoolingPointController;

use App\Http\Controllers\Admin\OutageRiskController;
use App\Http\Controllers\Admin\WeatherForecastController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\Admin\AlertThresholdController;
use App\Http\Controllers\Admin\WeatherAlertController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\NotificationPreferenceController;

use App\Http\Controllers\Api\AlertApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\WeatherApiController;
use App\Http\Middleware\ForceJsonResponse;

use App\Http\Controllers\EquipementC\Equipementc;

use App\Http\Controllers\Admin\ContactCategoryController;
use App\Http\Controllers\Admin\EmergencyContactController as AdminContactController;
use App\Http\Controllers\EmergencyContactController;

use Illuminate\Support\Facades\Route;


// ===== FrontOffice (public, sans authentification) =====
Route::get('/', [FrontOfficeController::class, 'home'])->name('front.home');
Route::get('/carte', [FrontOfficeController::class, 'map'])->name('front.map');
Route::get('/carte/data', [OutageController::class, 'map'])->name('front.map.data');


// ===== Dashboard résident =====
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// ===== Profil =====
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// ===== Coupures (BackOffice, connecté) =====
Route::middleware('auth')->group(function () {

    Route::get('/outages', [OutageController::class, 'index'])
        ->name('outages.index');

    Route::post('/outages', [OutageController::class, 'store'])
        ->name('outages.store');

    Route::get('/outages/map', [OutageController::class, 'map'])
        ->name('outages.map');

    Route::get('/outages/{outage}', [OutageController::class, 'show'])
        ->name('outages.show');

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

        // Routes pour les mises à jour des coupures
        // Route::post('/outages/{outage}/updates', [OutageUpdateController::class, 'store'])
        //     ->name('outages.updates.store');

        // Route::put('/outages/{outage}/updates/{update}', [OutageUpdateController::class, 'update'])
        //     ->name('outages.updates.update');

        // Route::delete('/outages/{outage}/updates/{update}', [OutageUpdateController::class, 'destroy'])
        //     ->name('outages.updates.destroy');
    });
});


// ===== Quartiers (admin/gestionnaire) =====
Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {
    Route::resource('neighborhoods', NeighborhoodController::class);
});


// ===== Rapports (admin/gestionnaire) =====
Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {

    Route::resource('reports', ReportController::class);

    Route::get('/reports/{report}/download', [ReportController::class, 'download'])
        ->name('reports.download');

    Route::post('/reports/{report}/send', [ReportController::class, 'send'])
        ->name('reports.send');
});


// ===== Admin (dashboard KPI, utilisateurs, audit) =====
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('users.index');

        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])
            ->name('users.updateRole');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');
    });


// ===== API (pour la future appli mobile) =====
Route::middleware('auth')->prefix('api')->group(function () {

    Route::get('/outages', [OutageController::class, 'index']);

    Route::post('/outages', [OutageController::class, 'store']);

    Route::get('/outages/map', [OutageController::class, 'map']);

    Route::get('/outages/{outage}', [OutageController::class, 'show']);

    Route::middleware('role:admin|gestionnaire')->group(function () {

        Route::put('/outages/{outage}/confirm', [OutageController::class, 'confirm']);

        Route::put('/outages/{outage}/resolve', [OutageController::class, 'resolve']);
    });
});


// ===== Points de fraîcheur (Cooling Points) =====
Route::middleware(['auth'])->group(function () {
    Route::get('/cooling-points', [CoolingPointController::class, 'index'])
        ->name('cooling-points.index');

    Route::post('/cooling-points/fetch', [CoolingPointController::class, 'fetch'])
        ->name('cooling-points.fetch');
});


// ===== Équipements (admin/gestionnaire) =====
Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {

    Route::name('admin.')->group(function () {

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
});

Route::middleware('auth')->group(function () {
    Route::get('/weather', [WeatherController::class, 'index'])->name('weather.index');
    Route::post('/weather/advice', [WeatherController::class, 'advice'])->name('weather.advice');

    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::post('/weather/refresh', [WeatherController::class, 'refresh'])->name('weather.refresh');
    });
});


// ===== Module météo (back office) =====
Route::middleware(['auth', 'role:admin|gestionnaire'])
    ->prefix('admin/weather')
    ->name('admin.weather.')
    ->group(function () {

        Route::resource('forecasts', WeatherForecastController::class)->except('show');
        Route::resource('risks', OutageRiskController::class)->except('show');

        Route::get('alerts', [WeatherAlertController::class, 'index'])->name('alerts.index');

        Route::middleware('role:admin')->group(function () {
            Route::get('alerts/create', [WeatherAlertController::class, 'create'])->name('alerts.create');
            Route::post('alerts', [WeatherAlertController::class, 'store'])->name('alerts.store');
            Route::delete('alerts/{alert}', [WeatherAlertController::class, 'destroy'])->name('alerts.destroy');

            Route::get('thresholds', [AlertThresholdController::class, 'edit'])->name('thresholds.edit');
            Route::put('thresholds', [AlertThresholdController::class, 'update'])->name('thresholds.update');
        });
    });


// ===== Historique des notifications =====
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [UserNotificationController::class, 'index'])
        ->name('notifications.index');

    Route::put('/notifications/read-all', [UserNotificationController::class, 'readAll'])
        ->name('notifications.readAll');

    Route::put('/notifications/{id}/read', [UserNotificationController::class, 'read'])
        ->name('notifications.read');
});


// ===== Préférences de notification =====
Route::middleware('auth')->group(function () {
    Route::get('/notifications/preferences', [NotificationPreferenceController::class, 'edit'])
        ->name('notifications.preferences.edit');

    Route::put('/notifications/preferences', [NotificationPreferenceController::class, 'update'])
        ->name('notifications.preferences.update');
});


// ===== Notification manuelle ciblée =====
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/notifications')
    ->name('admin.notifications.')
    ->group(function () {
        Route::get('broadcast', [BroadcastController::class, 'create'])
            ->name('broadcast.create');

        Route::post('broadcast', [BroadcastController::class, 'store'])
            ->name('broadcast.store');
    });


// ===== API JSON : météo, alertes, notifications =====
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


// ===== Contacts d'urgence (public) =====
Route::get('/contacts', [EmergencyContactController::class, 'index'])->name('contacts.index');
Route::get('/contacts/{contact}', [EmergencyContactController::class, 'show'])
    ->whereNumber('contact')
    ->name('contacts.show');


// ---------- BackOffice contacts d'urgence ----------
Route::middleware(['auth', 'role:admin|gestionnaire'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('contacts', AdminContactController::class)->except('show');
        Route::resource('contact-categories', ContactCategoryController::class)->except('show');
    });


require __DIR__.'/auth.php';
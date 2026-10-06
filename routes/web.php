<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\FrontOfficeController;
use App\Http\Controllers\NeighborhoodController;
use App\Http\Controllers\OutageController;
use App\Http\Controllers\ProfileController;

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

use Illuminate\Support\Facades\Route;


// ===== FrontOffice (public) =====
Route::get('/', [FrontOfficeController::class, 'home'])->name('front.home');
Route::get('/carte', [FrontOfficeController::class, 'map'])->name('front.map');
Route::get('/carte/data', [OutageController::class, 'map'])->name('front.map.data');


// ===== Dashboard =====
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// ===== Profil =====
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// ===== Admin =====
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');
    });


// ===== Coupures =====
Route::middleware('auth')->group(function () {
    Route::get('/outages', [OutageController::class, 'index'])->name('outages.index');
    Route::post('/outages', [OutageController::class, 'store'])->name('outages.store');
    Route::get('/outages/map', [OutageController::class, 'map'])->name('outages.map');
    Route::get('/outages/{outage}', [OutageController::class, 'show'])->name('outages.show');

    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::get('/outages/{outage}/edit', [OutageController::class, 'edit'])->name('outages.edit');
        Route::put('/outages/{outage}', [OutageController::class, 'update'])->name('outages.update');
        Route::delete('/outages/{outage}', [OutageController::class, 'destroy'])->name('outages.destroy');
        Route::put('/outages/{outage}/confirm', [OutageController::class, 'confirm'])->name('outages.confirm');
        Route::put('/outages/{outage}/resolve', [OutageController::class, 'resolve'])->name('outages.resolve');
    });
});


// ===== API Coupures =====
Route::middleware('auth')->prefix('api')->group(function () {
    Route::get('/outages', [OutageController::class, 'index']);
    Route::post('/outages', [OutageController::class, 'store']);
    Route::get('/outages/map', [OutageController::class, 'map']);
    Route::get('/outages/{outage}', [OutageController::class, 'show']);

    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::get('/outages/{outage}/edit', [OutageController::class, 'edit'])->name('outages.edit');
        Route::put('/outages/{outage}', [OutageController::class, 'update'])->name('outages.update');
        Route::delete('/outages/{outage}', [OutageController::class, 'destroy'])->name('outages.destroy');
        Route::put('/outages/{outage}/confirm', [OutageController::class, 'confirm']);
        Route::put('/outages/{outage}/resolve', [OutageController::class, 'resolve']);
    });
});


// ===== Neighborhoods =====
Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {
    Route::resource('neighborhoods', NeighborhoodController::class);
});


// ===== Module météo (front) =====
Route::middleware('auth')->group(function () {
    Route::get('/weather', [WeatherController::class, 'index'])->name('weather.index');
    Route::post('/weather/advice', [WeatherController::class, 'advice'])->name('weather.advice');

    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::post('/weather/refresh', [WeatherController::class, 'refresh'])->name('weather.refresh');
    });
});


// Module météo (back office)
Route::middleware(['auth', 'role:admin|gestionnaire'])
    ->prefix('admin/weather')->name('admin.weather.')
    ->group(function () {
        Route::resource('forecasts', WeatherForecastController::class);
        Route::resource('risks', OutageRiskController::class);

        Route::get('alerts', [WeatherAlertController::class, 'index'])->name('alerts.index');

        // Réservé à l'admin (définies avant alerts/{alert})
        Route::middleware('role:admin')->group(function () {
            Route::get('alerts/create', [WeatherAlertController::class, 'create'])->name('alerts.create');
            Route::post('alerts', [WeatherAlertController::class, 'store'])->name('alerts.store');
            Route::get('alerts/{alert}/edit', [WeatherAlertController::class, 'edit'])->name('alerts.edit');
            Route::put('alerts/{alert}', [WeatherAlertController::class, 'update'])->name('alerts.update');
            Route::delete('alerts/{alert}', [WeatherAlertController::class, 'destroy'])->name('alerts.destroy');

            Route::get('thresholds', [AlertThresholdController::class, 'edit'])->name('thresholds.edit');
            Route::put('thresholds', [AlertThresholdController::class, 'update'])->name('thresholds.update');
        });

        Route::get('alerts/{alert}', [WeatherAlertController::class, 'show'])->name('alerts.show');
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


require __DIR__.'/auth.php';
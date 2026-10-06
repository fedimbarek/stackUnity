<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\FrontOfficeController;
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
use App\Http\Controllers\NeighborhoodController;
use App\Http\Controllers\EquipementC\Equipementc;
use App\Http\Controllers\ReservationController;

// ===== FrontOffice (public, sans authentification) =====
Route::get('/', [FrontOfficeController::class, 'home'])->name('front.home');
Route::get('/carte', [FrontOfficeController::class, 'map'])->name('front.map');
Route::get('/carte/data', [OutageController::class, 'map'])->name('front.map.data');
Route::get('/Equipements', [Equipementc::class, 'frontIndex'])->name('front.equipements');
Route::post('/equipements/{equipement}/reservations', [ReservationController::class, 'store'])
    ->name('front.equipements.reservations.store');

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
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');
});

// ===== Coupures (BackOffice, connecté) =====
Route::middleware('auth')->group(function () {
    Route::get('/outages', [OutageController::class, 'index'])->name('outages.index');
    Route::post('/outages', [OutageController::class, 'store'])->name('outages.store');
    Route::get('/outages/map', [OutageController::class, 'map'])->name('outages.map'); // AVANT {outage} !
    Route::get('/outages/{outage}', [OutageController::class, 'show'])->name('outages.show');

    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::get('/outages/{outage}/edit', [OutageController::class, 'edit'])->name('outages.edit');
        Route::put('/outages/{outage}', [OutageController::class, 'update'])->name('outages.update');
        Route::delete('/outages/{outage}', [OutageController::class, 'destroy'])->name('outages.destroy');
        Route::put('/outages/{outage}/confirm', [OutageController::class, 'confirm'])->name('outages.confirm');
        Route::put('/outages/{outage}/resolve', [OutageController::class, 'resolve'])->name('outages.resolve');
    });
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

Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {
    Route::resource('neighborhoods', NeighborhoodController::class);
    Route::name('admin.')->group(function () {
        Route::get('/equipements', [Equipementc::class, 'index'])->name('equipements.index');
        Route::get('/equipements/create', [Equipementc::class, 'create'])->name('equipements.create');
        Route::post('/equipements', [Equipementc::class, 'store'])->name('equipements.store');
        Route::get('/equipements/{equipement}/reservations', [Equipementc::class, 'reservations'])
            ->name('equipements.reservations.index');
        Route::get('/equipements/{equipement}/edit', [Equipementc::class, 'edit'])->name('equipements.edit');
        Route::put('/equipements/{equipement}', [Equipementc::class, 'update'])->name('equipements.update');
        Route::delete('/equipements/{equipement}', [Equipementc::class, 'destroy'])->name('equipements.destroy');
    });
});

require __DIR__.'/auth.php';
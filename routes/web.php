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

        // Route::post('/outages/{outage}/updates', [OutageUpdateController::class, 'store'])->name('outages.updates.store');
        // Route::put('/outages/{outage}/updates/{update}', [OutageUpdateController::class, 'update'])->name('outages.updates.update');
        // Route::delete('/outages/{outage}/updates/{update}', [OutageUpdateController::class, 'destroy'])->name('outages.updates.destroy');
    });
});

// ===== Quartiers (admin/gestionnaire) =====
Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {
    Route::resource('neighborhoods', NeighborhoodController::class);
});

// ===== Rapports (admin/gestionnaire) =====
Route::middleware(['auth', 'role:admin|gestionnaire'])->group(function () {
    Route::resource('reports', ReportController::class);
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::post('/reports/{report}/send', [ReportController::class, 'send'])->name('reports.send');
});

// ===== Admin (dashboard KPI, utilisateurs, audit) =====
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
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

require __DIR__.'/auth.php';
<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OutageController;
Route::get('/', function () {
    //return view('welcome');
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    //Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.updateRole');
});


Route::middleware('auth')->group(function () {
    Route::get('/outages', [OutageController::class, 'index'])->name('outages.index');
    Route::post('/outages', [OutageController::class, 'store'])->name('outages.store');
    Route::get('/outages/map', [OutageController::class, 'map'])->name('outages.map'); // AVANT {outage} !
    Route::get('/outages/{outage}', [OutageController::class, 'show'])->name('outages.show');

    Route::middleware('role:admin|gestionnaire')->group(function () {
        Route::put('/outages/{outage}/confirm', [OutageController::class, 'confirm'])->name('outages.confirm');
        Route::put('/outages/{outage}/resolve', [OutageController::class, 'resolve'])->name('outages.resolve');
    });
});

// Mêmes endpoints, exposés sous /api/... pour la future appli mobile
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
require __DIR__.'/auth.php';
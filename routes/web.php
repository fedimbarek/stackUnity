<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EquipementC\Equipementc;


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
    Route::get('/equipements', [Equipementc::class, 'index'])->name('equipements.index');
    Route::get('/equipements/create', [Equipementc::class, 'create'])->name('equipements.create');
    Route::post('/equipements', [Equipementc::class, 'store'])->name('equipements.store');
    Route::get('/equipements/{equipement}/edit', [Equipementc::class, 'edit'])->name('equipements.edit');
    Route::put('/equipements/{equipement}', [Equipementc::class, 'update'])->name('equipements.update');
    Route::delete('/equipements/{equipement}', [Equipementc::class, 'destroy'])->name('equipements.destroy');

});

require __DIR__.'/auth.php';
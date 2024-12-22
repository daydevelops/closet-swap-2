<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\WantedAdController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [BrowseController::class,'dashboard'])->name('dashboard');

Route::get('/wanted-ads', [WantedAdController::class,'index'])->name('wanted');

Route::middleware('auth')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/{user}', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';

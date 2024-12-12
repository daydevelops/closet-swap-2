<?php

use App\Http\Controllers\BlockController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware('auth')->group(function () {
    Route::post('/block/{user}', [BlockController::class,'store'])->name('block');
    Route::delete('/block/{user}', [BlockController::class,'destroy'])->name('block.destroy');
});

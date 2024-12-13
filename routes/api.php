<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\FollowController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware('auth')->group(function () {
    Route::post('/block/{user}', [BlockController::class,'store'])->name('block');
    Route::delete('/block/{user}', [BlockController::class,'destroy'])->name('block.destroy');

    Route::get('/followers/{user}', [FollowController::class,'followers'])->name('followers'); // Get all followers of a user
    Route::get('/following/{user}', [FollowController::class,'following'])->name('following'); // Get all users this user is following
    Route::post('/follow/{user}', [FollowController::class,'follow'])->name('follow');
    Route::delete('/follow/{user}', [FollowController::class,'unfollow'])->name('unfollow');
});

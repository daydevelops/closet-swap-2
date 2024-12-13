<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/block/{user}', [BlockController::class,'store'])->name('block');
    Route::delete('/block/{user}', [BlockController::class,'destroy'])->name('block.destroy');

    Route::get('/followers/{user}', [FollowController::class,'followers'])->name('followers'); // Get all followers of a user
    Route::get('/following/{user}', [FollowController::class,'following'])->name('following'); // Get all users this user is following
    Route::post('/follow/{user}', [FollowController::class,'follow'])->name('follow');
    Route::delete('/follow/{user}', [FollowController::class,'unfollow'])->name('unfollow');

    Route::get('/likes', [LikeController::class,'getMyLikes'])->name('likes.mine'); // Get all items I have liked
    Route::get('/likes/{clothingItem}', [LikeController::class,'getItemLikes'])->name('likes.item'); // Get all users that have liked a clothing item
    Route::post('/like/{clothingItem}', [LikeController::class,'store'])->name('like'); // Like a clothing item
    Route::delete('/like/{clothingItem}', [LikeController::class,'destroy'])->name('unlike'); // Unlike a clothing item
});

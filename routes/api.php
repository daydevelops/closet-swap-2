<?php

use App\Http\Controllers\BlockController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\WantedAdController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PasswordForgotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\ClothingItemController;

use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/password/email', [PasswordForgotController::class, 'sendResetLinkEmail'])->name('password.email');

Route::get('/dashboard', [BrowseController::class,'dashboard'])->name('dashboard');
Route::get('/wanted-ads', [BrowseController::class,'wantedAds'])->name('wanted');

Route::get('/items/getOptions', [ClothingItemController::class, 'create'])->name('items.create');

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/password/change', [PasswordResetController::class, 'changePassword']);
    Route::post('/password/reset', [PasswordResetController::class, 'resetPassword'])->name('password.reset');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::prefix('profile')->group(function () {
        Route::get('/{user}', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

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

    Route::prefix('wanted')->group(function () {
        Route::post('/', [WantedAdController::class,'store'])->name('wanted.store');
        Route::post('/{wantedAd}', [WantedAdController::class,'update'])->name('wanted.update');
        Route::delete('/{wantedAd}', [WantedAdController::class,'destroy'])->name('wanted.destroy');
    });

    Route::prefix('items')->group(function () {
        Route::post('/', [ClothingItemController::class, 'store'])->name('items.store');
//        Route::get('/{clothingItem}', [ClothingItemController::class, 'show'])->name('items.show');
//        Route::patch('/{clothingItem}', [ClothingItemController::class, 'update'])->name('items.update');
//        Route::delete('/{clothingItem}', [ClothingItemController::class, 'destroy'])->name('items.destroy');
    });
});

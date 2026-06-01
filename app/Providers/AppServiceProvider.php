<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Point password-reset links at the React frontend instead of APP_URL
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $email    = urlencode($notifiable->getEmailForPasswordReset());
            $frontend = config('app.frontend_url', 'http://localhost:5173');
            return "{$frontend}/forgot-password/reset?token={$token}&email={$email}";
        });
    }
}

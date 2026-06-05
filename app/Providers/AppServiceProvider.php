<?php

namespace App\Providers;

use App\Models\ClothingItem;
use App\Observers\ClothingItemObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ClothingItem::observe(ClothingItemObserver::class);

        // Point email verification links at the React frontend instead of APP_URL
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $signedUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
            );
            parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);

            $frontend = config('app.frontend_url', 'http://localhost:5173');
            $id       = $notifiable->getKey();
            $hash     = sha1($notifiable->getEmailForVerification());

            return "{$frontend}/verify-email?id={$id}&hash={$hash}&expires={$params['expires']}&signature={$params['signature']}";
        });

        // Point password-reset links at the React frontend instead of APP_URL
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $email    = urlencode($notifiable->getEmailForPasswordReset());
            $frontend = config('app.frontend_url', 'http://localhost:5173');
            return "{$frontend}/forgot-password/reset?token={$token}&email={$email}";
        });
    }
}

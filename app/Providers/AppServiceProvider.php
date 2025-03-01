<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        $tokenExpiration = (int) env('PASSPORT_TOKEN_EXPIRATION', 60);
        Passport::tokensExpireIn(Carbon::now()->addMinutes($tokenExpiration));
    }
}

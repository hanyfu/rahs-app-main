<?php

namespace App\Providers;

use App\Auth\RahsUserProvider;
use App\Contracts\ClamScanner;
use App\Http\Middleware\EnsureRole;
use App\Services\ClamAvScanner;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClamScanner::class, function () {
            return ClamAvScanner::fromConfig();
        });
    }

    public function boot(): void
    {
        Auth::provider('rahs', function ($app, array $config) {
            return new RahsUserProvider;
        });

        Route::aliasMiddleware('role', EnsureRole::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}

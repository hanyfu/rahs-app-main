<?php

namespace App\Providers;

use App\Auth\RahsUserProvider;
use App\Contracts\ClamScanner;
use App\Services\ClamAvScanner;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}

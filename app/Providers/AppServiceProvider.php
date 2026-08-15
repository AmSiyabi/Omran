<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Engineering law #7: N+1 queries fail outside production
        Model::preventLazyLoading(! app()->isProduction());

        // نموذج التواصل العام — spec Phase 3: honeypot + rate limit
        RateLimiter::for('contact', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->ip()),
                Limit::perHour(12)->by($request->ip()),
            ];
        });
    }
}

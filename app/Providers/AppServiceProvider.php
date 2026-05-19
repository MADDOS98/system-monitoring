<?php

namespace App\Providers;

use App\Models\ApacheLog;
use App\Observers\ApacheLogObserver;
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
        ApacheLog::observe(ApacheLogObserver::class);
    }
}

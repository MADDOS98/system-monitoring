<?php

namespace App\Providers;

use App\Models\ApacheLog;
use App\Models\ConnectionMetric;
use App\Models\CpuMetric;
use App\Models\DiskIoMetric;
use App\Models\DiskUsageMetric;
use App\Models\NetworkMetric;
use App\Models\RamMetric;
use App\Observers\ApacheLogObserver;
use App\Observers\ConnectionMetricObserver;
use App\Observers\CpuMetricObserver;
use App\Observers\DiskIoMetricObserver;
use App\Observers\DiskUsageMetricObserver;
use App\Observers\NetworkMetricObserver;
use App\Observers\RamMetricObserver;
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

        RamMetric::observe(RamMetricObserver::class);
        NetworkMetric::observe(NetworkMetricObserver::class);
        CpuMetric::observe(CpuMetricObserver::class);
        DiskIoMetric::observe(DiskIoMetricObserver::class);
        DiskUsageMetric::observe(DiskUsageMetricObserver::class);
        ConnectionMetric::observe(ConnectionMetricObserver::class);
    }
}

<?php

namespace App\Livewire\Metrics;

use App\Services\Monitoring\DiskMetricsQuery;
use Livewire\Component;

class DiskGrowthForecast extends Component
{
    public function render()
    {
        $tz   = config('app.timezone');
        $data = app(DiskMetricsQuery::class)->growthForecast($tz);

        return view('livewire.metrics.disk-growth-forecast', $data);
    }
}

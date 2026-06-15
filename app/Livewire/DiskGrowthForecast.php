<?php

namespace App\Livewire;

use App\Services\Monitoring\DiskMetricsQuery;
use Livewire\Component;

class DiskGrowthForecast extends Component
{
    public function render()
    {
        $tz   = config('app.timezone');
        $data = app(DiskMetricsQuery::class)->growthForecast($tz);

        return view('livewire.disk-growth-forecast', $data);
    }
}

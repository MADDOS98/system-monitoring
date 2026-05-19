<?php

namespace App\Observers;

use App\Events\MetricCollected;
use App\Models\CpuMetric;

class CpuMetricObserver
{
    public function created(CpuMetric $m): void
    {
        try {
            MetricCollected::dispatch('cpu', (int) $m->collected_at, [
                'total_usage'    => (float) $m->total_usage,
                'per_core_usage' => $m->per_core_usage ?? [], // deja array via cast
                'stolen_usage'   => (float) $m->stolen_usage,
            ]);
        } catch (\Throwable $e) {
            // Reverb oprit — nu blocam insertul.
        }
    }
}

<?php

namespace App\Observers;

use App\Events\MetricCollected;
use App\Models\RamMetric;

class RamMetricObserver
{
    public function created(RamMetric $m): void
    {
        try {
            MetricCollected::dispatch('ram', (int) $m->collected_at, [
                'total_kb' => (int) $m->total_kb,
                'used_kb'  => (int) $m->used_kb,
            ]);
        } catch (\Throwable $e) {
            // Reverb oprit — nu blocam insertul.
        }
    }
}

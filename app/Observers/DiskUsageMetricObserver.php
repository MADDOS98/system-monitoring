<?php

namespace App\Observers;

use App\Events\MetricCollected;
use App\Models\DiskUsageMetric;

class DiskUsageMetricObserver
{
    public function created(DiskUsageMetric $m): void
    {
        try {
            MetricCollected::dispatch('disk_usage', (int) $m->collected_at, [
                'total_bytes' => (int) $m->total_bytes,
                'used_bytes'  => (int) $m->used_bytes,
            ]);
        } catch (\Throwable $e) {
            // Reverb oprit — nu blocam insertul.
        }
    }
}

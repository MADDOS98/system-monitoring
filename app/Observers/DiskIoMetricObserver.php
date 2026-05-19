<?php

namespace App\Observers;

use App\Events\MetricCollected;
use App\Models\DiskIoMetric;

class DiskIoMetricObserver
{
    public function created(DiskIoMetric $m): void
    {
        try {
            MetricCollected::dispatch('disk_io', (int) $m->collected_at, [
                'read_bytes'  => (int) $m->read_bytes,
                'write_bytes' => (int) $m->write_bytes,
            ]);
        } catch (\Throwable $e) {
            // Reverb oprit — nu blocam insertul.
        }
    }
}

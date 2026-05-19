<?php

namespace App\Observers;

use App\Events\MetricCollected;
use App\Models\NetworkMetric;

class NetworkMetricObserver
{
    public function created(NetworkMetric $m): void
    {
        try {
            MetricCollected::dispatch('network', (int) $m->collected_at, [
                'rx_bytes' => (int) $m->rx_bytes,
                'tx_bytes' => (int) $m->tx_bytes,
            ]);
        } catch (\Throwable $e) {
            // Reverb oprit — nu blocam insertul.
        }
    }
}

<?php

namespace App\Livewire\Metrics;

use App\Services\Monitoring\NetworkMetricsQuery;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class NetworkMetrics extends Component
{
    public int $fromTs = 0;
    public int $toTs   = 0;

    public function mount(): void
    {
        $now          = Carbon::now();
        $this->toTs   = $now->timestamp;
        $this->fromTs = $now->copy()->subMinutes(5)->timestamp;
    }

    #[On('setTimeRange')]
    public function setTimeRange(string $from, string $to): void
    {
        $this->fromTs = (int) $from;
        $this->toTs   = (int) $to;
    }

    public function render()
    {
        $data = app(NetworkMetricsQuery::class)->snapshot($this->fromTs, $this->toTs);

        return view('livewire.metrics.network-metrics', $data);
    }
}

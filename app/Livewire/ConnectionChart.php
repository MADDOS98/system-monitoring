<?php

namespace App\Livewire;

use App\Services\Monitoring\NetworkMetricsQuery;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ConnectionChart extends Component
{
    public string $name;
    public int    $fromTs = 0;
    public int    $toTs   = 0;

    public function mount(string $name): void
    {
        $this->name = $name;

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
        $data = app(NetworkMetricsQuery::class)
            ->connectionSnapshot($this->name, $this->fromTs, $this->toTs);

        return view('livewire.connection-chart', $data);
    }
}
